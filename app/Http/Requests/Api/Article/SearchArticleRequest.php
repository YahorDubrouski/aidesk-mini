<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Article;

use App\Http\Requests\BaseRequest;

final class SearchArticleRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:3', 'max:500'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
