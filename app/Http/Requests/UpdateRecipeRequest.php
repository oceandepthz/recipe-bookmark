<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'image_url' => ['nullable', 'string', 'max:2048', 'url:http,https'],
            'content_html' => ['nullable', 'string'],
            'tags' => ['nullable', 'string', 'max:255'],
            'refetch' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'タイトル',
            'excerpt' => '概要',
            'image_url' => '画像URL',
            'content_html' => '本文',
            'tags' => 'タグ',
        ];
    }
}
