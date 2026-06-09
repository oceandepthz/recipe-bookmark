<?php

namespace App\Http\Requests;

use App\Rules\AllowedDomain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecipeRequest extends FormRequest
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
            'url' => [
                'required', 'string', 'max:2048', 'url:http,https', new AllowedDomain(),
                // URLの一意性はユーザ単位（別ユーザは同じURLを登録できる）
                Rule::unique('recipes', 'url')->where('user_id', $this->user()?->id),
            ],
            'tags' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'url' => 'URL',
            'tags' => 'タグ',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.unique' => 'このURLは既に登録されています。',
        ];
    }
}
