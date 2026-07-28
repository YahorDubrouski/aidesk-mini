<?php

declare(strict_types=1);

namespace App\DTOs\Ticket;

use App\DTOs\Ai\UsageData;
use App\Enums\Ai\AiModel;
use App\Enums\Ai\AiProvider;

final readonly class SuggestedReplyResult
{
    public const REFUSE_REASON_EMPTY_PASSAGES = 'empty_passages';

    public const REFUSE_REASON_INSUFFICIENT_CONTEXT = 'insufficient_context';

    public const REFUSE_REASON_INVALID_MODEL_RESPONSE = 'invalid_model_response';

    public const DEFAULT_REFUSE_ANSWER = "I don't know based on the available knowledge articles.";

    /**
     * @param  list<SuggestedReplySource>  $sources
     */
    public function __construct(
        public string $answer,
        public array $sources,
        public bool $refused,
        public ?string $refuseReason,
        public AiProvider $provider,
        public AiModel $model,
        public UsageData $usage,
    ) {}

    public function toArray(): array
    {
        return [
            'answer' => $this->answer,
            'sources' => array_map(
                static fn (SuggestedReplySource $source): array => $source->toArray(),
                $this->sources,
            ),
            'refused' => $this->refused,
            'refuse_reason' => $this->refuseReason,
            'provider' => $this->provider->value,
            'model' => $this->model->value,
            'usage' => $this->usage->toArray(),
        ];
    }

    public static function fromArray(array $data): self
    {
        $sources = [];
        foreach ($data['sources'] ?? [] as $source) {
            if (! is_array($source)) {
                continue;
            }

            $sources[] = SuggestedReplySource::fromArray($source);
        }

        return new self(
            answer: (string) ($data['answer'] ?? ''),
            sources: $sources,
            refused: (bool) ($data['refused'] ?? false),
            refuseReason: isset($data['refuse_reason']) ? (string) $data['refuse_reason'] : null,
            provider: AiProvider::from($data['provider'] ?? AiProvider::OpenAI->value),
            model: AiModel::from($data['model'] ?? AiModel::Gpt4oMini->value),
            usage: UsageData::fromArray($data['usage'] ?? []),
        );
    }

    public static function refused(
        string $refuseReason,
        AiProvider $provider,
        AiModel $model,
        UsageData $usage,
        string $answer = self::DEFAULT_REFUSE_ANSWER,
    ): self {
        return new self(
            answer: $answer,
            sources: [],
            refused: true,
            refuseReason: $refuseReason,
            provider: $provider,
            model: $model,
            usage: $usage,
        );
    }
}
