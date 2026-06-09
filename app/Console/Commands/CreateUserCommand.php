<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password as promptPassword;
use function Laravel\Prompts\text;

class CreateUserCommand extends Command
{
    protected $signature = 'app:create-user
                            {--name= : 表示名}
                            {--email= : ログイン用メールアドレス}
                            {--password= : パスワード（省略時は対話入力）}';

    protected $description = 'ログイン用ユーザーを作成する';

    public function handle(): int
    {
        $name = $this->option('name') ?: text('表示名', required: true);
        $email = $this->option('email') ?: text('メールアドレス', required: true);
        $plainPassword = $this->option('password') ?: promptPassword('パスワード（8文字以上）', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $plainPassword],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        // password は User モデルの 'hashed' キャストで自動的にハッシュ化される。
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $plainPassword,
        ]);

        $this->info("ユーザーを作成しました: {$user->email}");

        return self::SUCCESS;
    }
}
