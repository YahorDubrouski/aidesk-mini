<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Ai\AiModel;
use App\Enums\Ai\AiProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $ticket_id
 * @property AiProvider $provider
 * @property AiModel $model
 * @property int $schema_version
 * @property int $usage_prompt_tokens
 * @property int $usage_completion_tokens
 * @property int $usage_total_tokens
 * @property string|null $cost_usd
 * @property array|null $result
 * @property string|null $error_code
 * @property string|null $error_message
 */
final class AiAnalysis extends Model
{
    use HasFactory;

    protected $table = 'ai_analyses';

    protected $casts = [
        'schema_version' => 'integer',
        'usage_prompt_tokens' => 'integer',
        'usage_completion_tokens' => 'integer',
        'usage_total_tokens' => 'integer',
        'cost_usd' => 'decimal:4',
        'result' => 'array',
        'provider'                => AiProvider::class,
        'model'                   => AiModel::class,
    ];

    protected $fillable = [
        'ticket_id',
        'provider',
        'model',
        'schema_version',
        'usage_prompt_tokens',
        'usage_completion_tokens',
        'usage_total_tokens',
        'cost_usd',
        'result',
        'error_code',
        'error_message',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
