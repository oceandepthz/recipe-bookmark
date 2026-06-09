<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UnlockLoginsCommand extends Command
{
    protected $signature = 'app:unlock-logins';

    protected $description = 'ログイン試行のレート制限（ロック）を解除する';

    public function handle(): int
    {
        // レート制限のカウンタは cache に保存されるため、cache をクリアして解除する。
        // 注意: 既定の cache ストア全体をクリアする（本アプリでは実害は小さい）。
        $this->call('cache:clear');

        $this->info('ログインのロックを解除しました（レート制限のカウンタをリセット）。');

        return self::SUCCESS;
    }
}
