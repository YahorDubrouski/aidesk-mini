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
 * @property string $answer
 * @property bool $refused
 * @property string|null $refuse_reason
 * @property array $sources
 * @property int $usage_prompt_tokens
 * @property int $usage_completion_tokens
 * @property int $usage_total_tokens
 * @property string|null $cost_usd
 */
final class TicketSuggestedReply extends Model
{
    /** @use HasFactory<\Database\Factories\TicketSuggestedReplyFactory> */
    use HasFactory;

    protected $table = 'ticket_suggested_replies';

    protected $casts = [
        'schema_version' => 'integer',
        'refused' => 'boolean',
        'sources' => 'array',
        'usage_prompt_tokens' => 'integer',
        'usage_completion_tokens' => 'integer',
        'usage_total_tokens' => 'integer',
        'cost_usd' => 'decimal:4',
        'provider' => AiProvider::class,
        'model' => AiModel::class,
    ];

    protected $fillable = [
        'ticket_id',
        'provider',
        'model',
        'schema_version',
        'answer',
        'refused',
        'refuse_reason',
        'sources',
        'usage_prompt_tokens',
        'usage_completion_tokens',
        'usage_total_tokens',
        'cost_usd',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
