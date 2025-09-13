<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Message\MessageAuthorType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int|null $author_id
 * @property MessageAuthorType $author_type user|agent|ai
 * @property string $body
 * @property bool $is_internal
 * @property array|null $meta
 */
final class Message extends Model
{
    use HasFactory;

    protected $casts = [
        'is_internal' => 'boolean',
        'meta'        => 'array',
        'author_type' => MessageAuthorType::class,

    ];

    protected $fillable = [
        'ticket_id','author_id','author_type','body','is_internal','meta',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
