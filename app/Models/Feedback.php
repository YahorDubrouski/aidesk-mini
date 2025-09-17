<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int $rating // 1..5
 * @property string|null $comment
 * @property bool $used_suggestion
 */
final class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedback';

    protected $casts = [
        'rating' => 'integer',
        'used_suggestion' => 'boolean',
    ];

    protected $fillable = [
        'ticket_id',
        'rating',
        'comment',
        'used_suggestion',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
