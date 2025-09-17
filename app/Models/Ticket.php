<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Ticket\TicketSentiment;
use App\Enums\Ticket\TicketStatus;
use App\Enums\Ticket\TicketUrgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int|null $user_id
 * @property string $requester_email
 * @property string|null $subject
 * @property string $body
 * @property TicketStatus $status
 * @property string|null $category
 * @property TicketUrgency $urgency
 * @property TicketSentiment|null $sentiment
 * @property string|null $product
 * @property string|null $summary
 * @property Carbon|null $answered_at
 */
final class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'answered_at' => 'datetime',
        'status' => TicketStatus::class,
        'urgency' => TicketUrgency::class,
        'sentiment' => TicketSentiment::class,
    ];

    protected $fillable = [
        'public_id',
        'user_id',
        'requester_email',
        'subject',
        'body',
        'status',
        'category',
        'urgency',
        'sentiment',
        'product',
        'summary',
        'answered_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(AiAnalysis::class, 'ticket_id');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }
}
