<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 登録を許可するドメイン
    |--------------------------------------------------------------------------
    |
    | レシピとして登録できる URL のドメインのホワイトリスト。
    | .env の ALLOWED_RECIPE_DOMAINS にカンマ区切りで設定する。
    | 例: cookpad.com,kurashiru.com
    | 各エントリは「完全一致」または「サブドメイン一致」で許可される
    | （例: cookpad.com を指定すると www.cookpad.com も許可）。
    |
    */

    'allowed_domains' => array_values(array_filter(array_map(
        static fn (string $domain): string => strtolower(trim($domain)),
        explode(',', (string) env('ALLOWED_RECIPE_DOMAINS', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | ページ取得のタイムアウト（秒）
    |--------------------------------------------------------------------------
    */

    'fetch_timeout' => (int) env('RECIPE_FETCH_TIMEOUT', 10),

];
