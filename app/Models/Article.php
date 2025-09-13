<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Locale\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property string $title
 * @property string $slug
 * @property string $body
 * @property string|null $summary
 * @property Language $language
 * @property bool $is_published
 * @property int $embedding_version
 * @property string|null $embedding_external_id
 * @property Carbon|null $embedded_at
 * @property string|null $checksum_sha256
 * @property array|null $tags
 */
final class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'is_published'          => 'boolean',
        'embedding_version'     => 'integer',
        'embedded_at'           => 'datetime',
        'tags'                  => 'array',
        'language'              => Language::class,
    ];

    protected $fillable = [
        'public_id',
        'title',
        'slug',
        'body',
        'summary',
        'language',
        'is_published',
        'embedding_version',
        'embedding_external_id',
        'embedded_at',
        'checksum_sha256',
        'tags',
    ];
}
