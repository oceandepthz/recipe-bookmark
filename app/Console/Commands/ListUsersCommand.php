<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListUsersCommand extends Command
{
    protected $signature = 'app:list-users';

    protected $description = 'ログインユーザーの一覧を表示する';

    public function handle(): int
    {
        $users = User::query()->withCount('recipes')->orderBy('id')->get();

        if ($users->isEmpty()) {
            $this->warn('ユーザーが登録されていません。');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', '名前', 'メール', 'レシピ数', '作成日'],
            $users->map(fn (User $u): array => [
                $u->id,
                $u->name,
                $u->email,
                $u->recipes_count,
                $u->created_at?->format('Y-m-d H:i'),
            ])->all()
        );

        return self::SUCCESS;
    }
}
