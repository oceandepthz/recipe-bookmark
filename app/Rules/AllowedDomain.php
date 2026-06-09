<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AllowedDomain implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $host = strtolower((string) parse_url((string) $value, PHP_URL_HOST));

        if ($host === '') {
            $fail('有効なURLを入力してください。');

            return;
        }

        /** @var list<string> $allowed */
        $allowed = config('recipe.allowed_domains', []);

        if ($allowed === []) {
            $fail('許可ドメインが設定されていません。管理者に連絡してください（ALLOWED_RECIPE_DOMAINS）。');

            return;
        }

        foreach ($allowed as $domain) {
            // 完全一致、またはサブドメイン一致（例: www.cookpad.com は cookpad.com にマッチ）
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return;
            }
        }

        $fail('このドメインは登録できません。許可されたレシピサイトのURLを入力してください。');
    }
}
