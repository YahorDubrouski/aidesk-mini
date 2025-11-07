<?php

declare(strict_types=1);

namespace App\DTOs\Ai;

use App\Enums\Ai\AiModel;
use App\Enums\Ai\AiProvider;

final readonly class TextAnalysisResult
{
    public function __construct(
        public AiProvider $provider,
        public AiModel $model,
        public TextAnalysisData $result,
        public UsageData $usage,
    ) {
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider->value,
            'model' => $this->model->value,
            'result' => $this->result->toArray(),
            'usage' => $this->usage->toArray(),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            provider: AiProvider::from($data['provider']),
            model: AiModel::from($data['model']),
            result: TextAnalysisData::fromArray($data['result'] ?? []),
            usage: UsageData::fromArray($data['usage'] ?? []),
        );
    }
}
