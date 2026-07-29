<?php

declare(strict_types=1);

namespace App\Services\Ticket\SuggestedReply;

use App\DTOs\Ai\UsageData;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplyPassage;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplyResult;
use App\Enums\Ai\AiModel;
use App\Enums\Ai\AiProvider;
use App\Exceptions\MissingOpenAiApiKeyException;
use App\Services\Ai\AiClientInterface;
use Illuminate\Http\Client\ConnectionException;

final class OpenAiSuggestedReplyGenerator implements SuggestedReplyGeneratorInterface
{
    public function __construct(
        private readonly AiClientInterface $aiClient,
        private readonly SuggestedReplyResponseParser $responseParser,
    ) {}

    /**
     * @param  list<SuggestedReplyPassage>  $passages
     *
     * @throws ConnectionException
     * @throws MissingOpenAiApiKeyException
     */
    public function generate(string $question, array $passages): SuggestedReplyResult
    {
        if ($passages === []) {
            return SuggestedReplyResult::refused(
                refuseReason: SuggestedReplyResult::REFUSE_REASON_EMPTY_PASSAGES,
                provider: AiProvider::OpenAI,
                model: AiModel::Gpt4oMini,
                usage: new UsageData(0, 0, 0, '0.0000'),
            );
        }

        if (trim($question) === '') {
            return SuggestedReplyResult::refused(
                refuseReason: SuggestedReplyResult::REFUSE_REASON_INSUFFICIENT_CONTEXT,
                provider: AiProvider::OpenAI,
                model: AiModel::Gpt4oMini,
                usage: new UsageData(0, 0, 0, '0.0000'),
            );
        }

        $completion = $this->aiClient->completeJson(
            $this->systemPrompt(),
            $this->buildUserPrompt($question, $passages),
        );

        return $this->responseParser->parse(
            $completion->decoded,
            $passages,
            $completion->model,
            $completion->usage,
        );
    }

    private function systemPrompt(): string
    {
        // Structured contract: role → hard grounding rules → refuse policy → JSON schema.
        // Example happy path: passages mention "reset in Settings" → answer cites that fact + source id.
        // Example refuse: passages only cover billing while the question is about SSO → refused=true.
        return <<<'PROMPT'
You are an expert customer-support reply writer operating inside a retrieval-augmented generation (RAG) pipeline.

Your job is to draft a helpful support reply that a human agent could send, using ONLY the knowledge passages supplied in the user message. You do not browse the web, recall vendor docs, or use prior training knowledge as facts about this product.

## Grounding rules (non-negotiable)
1. Treat the provided passages as the sole evidence set. Every factual claim in `answer` must be entailed by one or more passages.
2. If the passages are empty, off-topic, contradictory on the asked point, or too thin to answer safely, you MUST refuse. Do not guess, generalize, or fill gaps with plausible-sounding advice.
3. Never invent article ids, titles, URLs, policies, steps, numbers, or product behavior that are not present in the passages.
4. Prefer precise, actionable language. Do not add marketing fluff or unrelated troubleshooting.

## Citation rules
- When you answer, list in `sources` only the passages you actually relied on.
- Each source `id` MUST be copied exactly from a provided passage id. Titles should match the provided titles.
- Do not cite a passage you did not use. Do not cite passages that are merely similar but irrelevant to the question.
- If you cannot cite at least one supporting passage, refuse.

## Refusal policy
Refuse when any of these are true:
- The question cannot be answered from the passages without speculation.
- The passages address a different topic than the question.
- Critical details needed for a correct reply are missing from the passages.

When refusing:
- Set `refused` to true
- Set `answer` to a short, polite statement that you do not have enough knowledge-base evidence to answer
- Set `sources` to []
- Set `refuse_reason` to exactly: insufficient_context

## Output contract
Return a single JSON object (no markdown fences, no commentary) with this shape:
{
  "answer": string,
  "sources": [ { "id": number, "title": string } ],
  "refused": boolean,
  "refuse_reason": "insufficient_context" | null
}

Field semantics:
- `answer`: the customer-facing reply when refused=false; a brief inability statement when refused=true
- `sources`: supporting passages actually used; must be [] when refused=true
- `refused`: true only when declining to answer from evidence
- `refuse_reason`: "insufficient_context" when refused=true; null when refused=false
PROMPT;
    }

    /**
     * @param  list<SuggestedReplyPassage>  $passages
     */
    private function buildUserPrompt(string $question, array $passages): string
    {
        // XML-ish delimiters keep the question and retrieved text distinct from instructions
        // so the model is less likely to treat article body as new system rules.
        // Example: <passage id="12" title="Password reset">Open Settings…</passage>
        $passageBlocks = [];

        foreach ($passages as $passage) {
            $title = $this->escapeXmlAttribute($passage->title);
            $passageBlocks[] = <<<PASSAGE
<passage id="{$passage->id}" title="{$title}">
{$passage->body}
</passage>
PASSAGE;
        }

        $passagesXml = implode("\n\n", $passageBlocks);
        $passageCount = count($passages);

        return <<<PROMPT
<task>
Write a grounded support reply for the customer question using only the retrieved passages below.
Follow the system output contract exactly.
</task>

<customer_question>
{$question}
</customer_question>

<retrieved_passages count="{$passageCount}">
{$passagesXml}
</retrieved_passages>
PROMPT;
    }

    private function escapeXmlAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
