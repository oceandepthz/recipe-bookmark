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

# 5. ログインユーザーを作成（対話で 表示名 / メール / パスワード を入力）
#    オプション指定での非対話作成は「管理コマンド」セクションを参照。
docker compose exec -it app php artisan app:create-user
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

## レシピの取り込み（サイト別抽出）

URL 登録時、`config/recipe.php` の `extractors` に登録されたエクストラクタを上から順に試します。

- **`JsonLdRecipeExtractor`（汎用）**: ページに schema.org/Recipe の JSON-LD があれば、そこから
  タイトル・画像・説明・**材料**・**作り方**を構造化して正確に取り込みます（みんなのきょうの料理・
  楽天レシピなど多くのサイトが対象）。
- **`SirogohanExtractor`（サイト固有）**: 白ごはん.com（JSON-LD 非対応）専用。`.material`/`.howto` の
  HTML 構造から材料・作り方を抽出する、個別定義の実例。
- **`GenericExtractor`（フォールバック）**: JSON-LD が無いサイトは readability で本文を抽出します。

動作確認済みの例: みんなのきょうの料理 / 楽天レシピ / デリッシュキッチン（いずれも汎用 JSON-LD で自動対応）、
白ごはん.com（専用エクストラクタ）。

### 食材の自動タグ化

構造化抽出できたレシピは、**利用食材が自動でタグになります**（調味料・油・粉類・だし・水などは除外）。
除外する語は `config/recipe.php` の `ingredient_tag_denylist` で調整できます（完全一致または後方一致で除外。
例: `油`→`サラダ油/ごま油`、`こしょう`→`黒こしょう`）。手動で入力したタグとは併せて付与されます。

### サイト固有の抽出定義を追加する

特定サイト向けに独自抽出を足したい場合:

1. `App\Services\Extraction\RecipeExtractor` を実装したクラスを作成
   （`extract(url, html)` 内で対象ホスト以外は `null` を返す）。
2. `config/recipe.php` の `extractors` 配列の **先頭側**（`JsonLdRecipeExtractor` より上）に追加する（先勝ち）。

> 取り込んだ本文 HTML は HTMLPurifier（`config/purifier.php`）でサニタイズして保存・表示します。

## 機能

| 機能 | 内容 |
|---|---|
| ログイン | セッション認証。ユーザーは `app:create-user` コマンドで作成 |
| レシピ一覧 | サムネイル・タグ・作ってみた件数を表示。タグ絞り込み／キーワード検索 |
| レシピ登録 | 許可ドメインのURLを貼ると本文・画像・概要を自動取得 |
| レシピ閲覧 | 保存した本文をオフラインで閲覧 |
| レシピ編集 | タイトル・概要・画像・本文・タグを編集。URLからの再取得も可能 |
| タグ | 手動（カンマ区切り）＋ 構造化抽出時は利用食材を自動タグ化（調味料等は除外） |
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
# ユーザー作成（対話）。非対話なら --name= --email= --password= を指定
docker compose exec -it app php artisan app:create-user
docker compose exec    app php artisan app:create-user --name=私 --email=me@example.com --password=********

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

## 本番デプロイ / セキュリティ（VPS公開時）

公開前の必須チェックリスト:

1. **本番 .env を用意**（`.env.production.example` を雛形に）。`APP_KEY` は **VPS で新規生成**する
   （開発用キーは流用しない）。
   ```bash
   cp .env.production.example .env
   docker compose exec app php artisan key:generate
   ```
   要点: `APP_ENV=production` / `APP_DEBUG=false` / `APP_URL=https://...` /
   `SESSION_SECURE_COOKIE=true` / `LOG_LEVEL=warning`。
2. **HTTPS（TLS）を前段に置く**。Caddy や nginx + Let's Encrypt 等で TLS 終端し、**443 のみ公開**する。
   - 本リポジトリの `web`(nginx)/`app`(php-fpm) コンテナのポートは **直接公開しない**
     （`docker-compose.yml` の `ports` を外す、または `127.0.0.1:8080:80` のようにローカル束縛して
     ホスト側のTLSプロキシから繋ぐ）。
   - 信頼するプロキシは私的IPレンジ（`10/8`,`172.16/12`,`192.168/16`,`127.0.0.1`）に限定しています
     （`bootstrap/app.php`）。**前段プロキシがこのレンジ外**（別ホストの公開IP等）の場合は、その IP/CIDR を
     同ファイルに追加してください。生成URLは `APP_URL` に固定（Host注入対策）。
   - ファイアウォール（ufw 等）で 443（と必要なら 80→443 リダイレクト）以外を遮断。
   - **公開ポートの変更（git管理外で制御）**: ポートは `.env` の `WEB_PUBLISH` で指定する
     （`docker-compose.yml` は編集不要＝git差分なし）。既存サービスと衝突する場合や前段 nginx 配下では、
     空きポートを localhost に束縛する:
     ```dotenv
     # .env（git管理外）
     WEB_PUBLISH=127.0.0.1:8090
     ```
     前段 nginx 側（同ホストで TLS 終端する例）:
     ```nginx
     location / {
         proxy_pass http://127.0.0.1:8090;
         proxy_set_header Host $host;
         proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
         proxy_set_header X-Forwarded-Proto https;   # 前段で TLS 終端する場合
     }
     ```
     （`X-Forwarded-Host` は送らなくてよい＝アプリは `APP_URL` にURLを固定。コンテナ間の送信元は私的IPの
     ため `trustProxies` に信頼され、HTTPS 判定・セキュアCookie が有効になる。）
3. **本番ビルド最適化**:
   ```bash
   docker compose exec app composer install --no-dev --optimize-autoloader
   docker compose exec app php artisan migrate --force
   docker compose exec app php artisan config:cache
   docker compose exec app php artisan route:cache
   docker compose exec app php artisan view:cache
   docker compose exec app php artisan app:create-user
   ```
   （設定変更後は `config:clear`→再 `config:cache`。env() は config ファイル内のみで参照しているため
   `config:cache` 後も動作します。）
4. **運用**: ログインロック解除は `app:unlock-logins`、バックアップは上記「バックアップ/リストア」、
   ログは `storage/logs`。

### 残存リスク（把握しておくこと）
- **SSRF**: レシピ取得は `ALLOWED_RECIPE_DOMAINS` のドメインに限定しますが、取得時のリダイレクトは
  追従するため、許可ドメインのオープンリダイレクト経由で内部リソース（例: クラウドメタデータ）へ
  到達する余地があります。トリガーは認証済みの所有者のみですが、**信頼できるレシピドメインだけ**を
  許可リストに入れてください（取得時のリダイレクト再検証・内部IPブロックは今後の課題）。
- 本文中の外部画像を読み込むため、閲覧時に各レシピサイトへ閲覧者のIPが渡ります（プライバシー上の留意点）。

## メモ

- php-fpm プール（www-data）がバインドマウントへ書き込めるよう、起動時に
  `storage/`・`bootstrap/cache/`・SQLite の権限を緩めています（`docker/php/entrypoint.sh`）。
  公開運用ではコンテナのポートを直接公開せず、TLSプロキシ背後に置いてください（「本番デプロイ」参照）。
- 保存した本文HTMLは外部サイト由来の信頼できない入力のため、HTMLPurifier
  （`mews/purifier`）で許可リスト方式にサニタイズしてから保存・表示します（XSS対策）。
