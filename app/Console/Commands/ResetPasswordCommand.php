<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password as promptPassword;

class ResetPasswordCommand extends Command
{
    protected $signature = 'app:reset-password
                            {email : 対象ユーザーのメールアドレス}
                            {--password= : 新しいパスワード（省略時は対話入力）}';

    protected $description = 'ログインユーザーのパスワードを強制的に上書きする';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("該当するユーザーが見つかりません: {$email}");

            return self::FAILURE;
        }

        $plainPassword = $this->option('password') ?: promptPassword('新しいパスワード（8文字以上）', required: true);

        $validator = Validator::make(
            ['password' => $plainPassword],
            ['password' => ['required', 'string', 'min:8']]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        // password は User モデルの 'hashed' キャストで自動的にハッシュ化される。
        $user->password = $plainPassword;
        $user->save();

        $this->info("パスワードを更新しました: {$user->email}");

        return self::SUCCESS;
    }
}
