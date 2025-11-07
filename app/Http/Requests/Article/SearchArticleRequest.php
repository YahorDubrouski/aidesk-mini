<?php

declare(strict_types=1);

namespace App\Http\Requests\Article;

use Illuminate\Foundation\Http\FormRequest;

final class SearchArticleRequest extends FormRequest
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
