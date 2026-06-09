<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DeleteUserCommand extends Command
{
    protected $signature = 'app:delete-user
                            {email : 削除するユーザーのメールアドレス}
                            {--force : 確認なしで削除する}';

    protected $description = 'ログインユーザーを削除する（本人のレシピ・作ってみたメモも連動削除）';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $user = User::query()->withCount('recipes')->where('email', $email)->first();

        if (! $user) {
            $this->error("該当するユーザーが見つかりません: {$email}");

            return self::FAILURE;
        }

        if (! $this->option('force')
            && ! $this->confirm("ユーザー {$user->email} とレシピ {$user->recipes_count} 件を削除します。よろしいですか？", false)) {
            $this->info('中止しました。');

            return self::SUCCESS;
        }

        // FK の cascadeOnDelete により recipes / cooking_logs / recipe_tag も連動削除される。
        $user->delete();

        $this->info("削除しました: {$email}");

        return self::SUCCESS;
    }
}
