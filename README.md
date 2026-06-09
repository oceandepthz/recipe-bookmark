# Recipe Bookmark 🍳

料理レシピ専用の「後で読む」サービス。
許可したレシピサイトのURLを登録すると、タイトル・画像・本文を自動取得して保存します。
タグ付け・「作ってみた」メモにも対応。

**マルチユーザ対応**：レシピ・作ってみたメモはユーザ単位に分離され、各ユーザは自分のデータのみ
閲覧・編集・削除できます（他人のレシピには 403）。ユーザは CLI（`app:create-user`）で追加します
（サインアップ画面はありません）。

## 技術スタック

- PHP 8.4 / Laravel 13
- SQLite（ファイルDB）
- Docker（nginx + php-fpm の2コンテナ）
- Blade + [Pico.css](https://picocss.com/)
- 本文抽出: `fivefilters/readability.php`

## セットアップ

```bash
# 0. .env を用意（clone 直後は存在しないため）
cp .env.example .env        # Windows(PowerShell): copy .env.example .env

# 1. イメージをビルドして起動
docker compose up -d --build

# 2. （初回のみ）依存パッケージをインストール
docker compose exec app composer install

# 3. アプリkeyが無い場合は生成（.env の APP_KEY が空のとき）
docker compose exec app php artisan key:generate

# 4. マイグレーション
docker compose exec app php artisan migrate

# 5. ログインユーザーを作成
docker compose exec app php artisan app:create-user
#   → 対話で 表示名 / メール / パスワード を入力
#   非対話で作る場合:
#   docker compose exec app php artisan app:create-user --name=私 --email=me@example.com --password=********
```

ブラウザで http://localhost:8080 を開く → ログイン。

## 登録可能ドメインの設定

`.env` の `ALLOWED_RECIPE_DOMAINS` にカンマ区切りで指定します（サブドメインも自動許可）。

```dotenv
ALLOWED_RECIPE_DOMAINS=cookpad.com,kurashiru.com,delishkitchen.tv,recipe.rakuten.co.jp
```

変更後は設定キャッシュをクリア（キャッシュしている場合）:

```bash
docker compose exec app php artisan config:clear
```

## 機能

| 機能 | 内容 |
|---|---|
| ログイン | セッション認証。ユーザーは `app:create-user` コマンドで作成 |
| レシピ一覧 | サムネイル・タグ・作ってみた件数を表示。タグ絞り込み／キーワード検索 |
| レシピ登録 | 許可ドメインのURLを貼ると本文・画像・概要を自動取得 |
| レシピ閲覧 | 保存した本文をオフラインで閲覧 |
| レシピ編集 | タイトル・概要・画像・本文・タグを編集。URLからの再取得も可能 |
| タグ | カンマ区切りで付与（`firstOrCreate` で共有） |
| 作ってみたメモ | レシピごとに感想・作った日を記録 |

## 管理コマンド（`docker compose exec`）

すべて `docker compose exec app php artisan <コマンド>` の形で実行します。

| コマンド | 説明 |
|---|---|
| `migrate` | マイグレーション実行 |
| `migrate:fresh` | 全テーブルを作り直す（**データは消える**） |
| `app:create-user` | ログインユーザーを作成（`--name= --email= --password=` で非対話実行可） |
| `app:list-users` | ユーザー一覧を表示（ID・名前・メール・レシピ数・作成日） |
| `app:reset-password <email>` | パスワードを強制上書き（`--password=` 省略時は対話入力） |
| `app:delete-user <email>` | ユーザー削除。本人のレシピ・メモも連動削除。**自動化時は `--force`** |
| `app:unlock-logins` | ログイン試行のレート制限ロックを解除 |

実行例:

```bash
# ユーザー一覧
docker compose exec app php artisan app:list-users

# パスワード上書き（対話）
docker compose exec -it app php artisan app:reset-password me@example.com
#   非対話: --password=新しいパスワード を付ける

# ユーザー削除（確認あり。-it で対話、または --force）
docker compose exec -it app php artisan app:delete-user old@example.com
docker compose exec    app php artisan app:delete-user old@example.com --force

# ログインがロックされたとき解除（手動なら cache:clear でも可）
docker compose exec app php artisan app:unlock-logins
```

> 補足: `app:unlock-logins` はレート制限カウンタ（cache 保存）をクリアします。内部的には既定の
> cache ストア全体をクリアするため、`php artisan cache:clear` と同じ効果です。

## バックアップ / リストア

DB は SQLite 1ファイル `database/database.sqlite`（ホストにバインドマウント）。
書き込みが無いタイミングでホスト側からファイルをコピーするだけでバックアップできます。

```powershell
# バックアップ（PowerShell）
Copy-Item database\database.sqlite "database\backup-$(Get-Date -Format yyyyMMdd-HHmm).sqlite"
```

```bash
# バックアップ（bash）
cp database/database.sqlite "database/backup-$(date +%Y%m%d-%H%M).sqlite"
```

リストアは、コンテナを止めてからファイルを置き換えて再起動します:

```bash
docker compose down
# database/database.sqlite をバックアップで置き換える
docker compose up -d
```

> バックアップファイル（`database/*.sqlite`）は `.gitignore` 済みでコミットされません。

## データのリセット

検証用に作成したユーザー／レシピを消して作り直す場合:

```bash
docker compose exec app php artisan migrate:fresh
docker compose exec app php artisan app:create-user
```

## メモ

- 本ツールはローカル開発用です。php-fpm プールはバインドマウントへ
  書き込めるよう `storage/`・`bootstrap/cache/`・SQLite の権限を起動時に緩めています
  （`docker/php/entrypoint.sh`）。
- 保存した本文HTMLは外部サイト由来の信頼できない入力のため、HTMLPurifier
  （`mews/purifier`）で許可リスト方式にサニタイズしてから保存・表示します（XSS対策）。
