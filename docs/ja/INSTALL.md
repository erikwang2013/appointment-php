# 予約サービスシステム — インストールガイド
> **Languages**: [中文](../INSTALL.md) · [English](../en/INSTALL.md) · [한국어](../ko/INSTALL.md) · [Русский](../ru/INSTALL.md) · [Deutsch](../de/INSTALL.md) · [Français](../fr/INSTALL.md) · [Español](../es/INSTALL.md) · [Português](../pt/INSTALL.md) · [हिन्दी](../hi/INSTALL.md) · [العربية](../ar/INSTALL.md) · [বাংলা](../bn/INSTALL.md) · [Bahasa Indonesia](../id/INSTALL.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## 環境要件

| コンポーネント | 最低バージョン | 説明 |
|------|----------|------|
| PHP | 8.3+ | 拡張: bcmath, curl, gd, mbstring, pdo, pdo_mysql, pcntl, redis |
| MySQL | 8.0+ | テーブルプレフィックス `erik_`、文字セット utf8mb4 |
| Redis | 6.0+ | キャッシュ / レート制限 / Session / 認証コード保存 |
| Composer | 2.x | PHP 依存管理 |
| Elasticsearch | 8.x (任意) | 全文検索、未インストールでもコア機能に影響なし |

---

## 一、Web インストールウィザード（推奨）

管理バックエンド起動後、ブラウザで `/install` にアクセスするとワンクリックインストールウィザードが起動します：

```bash
# 1. 依存関係をインストールして起動
cd admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
php start.php start -d     # デフォルトポート 8787
```

ブラウザで `http://localhost:8787/install` を開き、4ステップで完了：

1. **環境チェック** — PHP バージョン、必須拡張、ファイル権限を自動検出
2. **データベース設定** — MySQL 接続情報を入力し、接続テストをクリック
3. **管理者アカウント** — アプリケーション名、管理者ユーザー名とパスワードを設定
4. **インストール実行** — SQL を自動インポート → 管理者を作成 → .env 設定を書き込み

インストール完了後、設定したユーザー名とパスワードでログインします。インストール成功時には `.install.lock` ファイルが書き込まれ、`/install` インターフェースは二重検証（ファイルロック + isInstalled）で再インストールを防止します。`.install.lock` は `.gitignore` に追加済みです。本番環境では `admin/config/route.php` の `/install` ルートを削除することを推奨します。

---

## 二、手動インストール

### 2.1 プロジェクトのクローン

```bash
git clone <repo-url> appointment-php
cd appointment-php
```

### 1.2 PHP 依存関係のインストール

```bash
# 業務 API サービス
cd service/
cp .env.example .env
composer install --no-dev --optimize-autoloader

# 管理バックエンド
cd ../admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
```

### 1.3 環境変数の設定

`service/.env`（業務 API）と `admin/.env`（管理バックエンド）を編集し、以下の主要設定を変更します：

```bash
# データベース接続
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=appointment          # service は appointment、admin は open_admin
DB_USERNAME=root
DB_PASSWORD=your-password

# Redis 接続
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# JWT 鍵 — 本番環境では必ず 64 文字のランダム文字列に変更
JWT_SECRET_KEY=your-64-char-random-string

# 暗号化鍵 — 本番環境では必ず変更
ENCRYPTION_KEY=your-32-byte-key
ENCRYPTABLE_KEY=your-32-byte-key

# Hashids ソルト — 本番環境では必ず変更
HASHIDS_SALT=your-random-salt

# デバッグモード — 本番環境では必ず false
APP_DEBUG=false
```

> 全変数の説明は `service/.env.example` と `admin/.env.example` を参照してください。

### 1.4 データベースの作成とインポート

```bash
# データベースを作成（service と admin は同一データベースでも分けても可）
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS appointment DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS open_admin DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 統一インストールスクリプトをインポート（全 54+ テーブル + 権限データ + デモデータを含む）
mysql -u root -p appointment < docs/install.sql
mysql -u root -p open_admin < docs/install.sql
```

> `docs/install.sql` は全マイグレーションファイルを統合したもので、合計 2723 行、管理バックエンドと業務サービスの全テーブル構造とシードデータを含みます。新規インストールは一度で実行できます。既存データベースへの再実行は主キー/カラムの競合で中断されるため、アップグレードの場合は先にバックアップを取るか手動で競合を処理してください。

### 1.5 サービスの起動

```bash
# 業務 API サービスを起動（デフォルトポート 8787）
cd service/
php start.php start -d

# 管理バックエンドを起動（デフォルトポート 8787）
cd ../admin/
php start.php start -d
```

### 1.6 インストールの検証

```bash
# 業務 API
curl http://localhost:8787/api/common/config

# 管理バックエンドのヘルスチェック
curl http://localhost:8787/health

# 管理バックエンドログイン（デフォルトアカウントは下記参照）
curl -X POST http://localhost:8787/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### 1.7 デフォルトアカウント

| ロール | ユーザー名 | パスワード | 説明 |
|------|--------|------|------|
| スーパー管理者 | `admin` | `admin123` | 全権限を所有 |

> 初回ログイン後はすぐにパスワードを変更してください。

---

## 三、Docker デプロイ

### 2.1 業務 API サービス

```bash
cd service/
cp .env.docker .env
# .env を編集し、鍵とパスワードを変更
docker-compose up -d
```

構成: nginx (80/443) + app (8787) + mysql (3306) + redis (6379) + elasticsearch (9200)

### 2.2 管理バックエンド

```bash
cd admin/
cp .env.docker .env
docker-compose up -d
```

### 2.3 Docker 環境へのデータベースインポート

```bash
# install.sql をコンテナにコピーして実行
docker cp docs/install.sql appointment-svc-mysql:/tmp/
docker exec -it appointment-svc-mysql mysql -u root -p appointment < /tmp/install.sql
```

---

## 四、データベース構造の概要

| ドメイン | テーブル数 | コアテーブル |
|----|------|--------|
| 管理バックエンド | 8 | `erik_admin_user`, `erik_admin_role`, `erik_admin_permission`, `erik_operation_log` |
| ユーザードメイン | 4 | `erik_user`, `erik_user_address`, `erik_user_favorite`, `erik_user_device` |
| スタッフドメイン | 8 | `erik_technician_profile`, `erik_technician_schedule`, `erik_technician_earning`, `erik_technician_withdrawal`, `erik_technician_tier_config` |
| サービスドメイン | 4 | `erik_service_category`, `erik_service`, `erik_service_package`, `erik_service_record` |
| 注文ドメイン | 5 | `erik_order`, `erik_order_item`, `erik_order_payment`, `erik_order_refund`, `erik_order_review` |
| マーケティングドメイン | 8 | `erik_coupon`, `erik_member_card`, `erik_gift_card`, `erik_user_points`, `erik_promotion` |
| 待ち番号 | 1 | `erik_queue_number` |
| コンテンツドメイン | 5 | `erik_banner`, `erik_announcement`, `erik_faq`, `erik_feedback`, `erik_platform_agreement` |
| コミュニティドメイン | 3 | `erik_post`, `erik_comment`, `erik_moment` |
| 店舗 | 1 | `erik_store` |
| 研修 | 2 | `erik_training_course`, `erik_training_progress` |
| 試験 | 3 | `erik_exam`, `erik_exam_question`, `erik_exam_attempt` |
| システム | 3 | `erik_system_config`, `erik_notification`, `erik_signature` |
| **合計** | **55** | |

全テーブルは `erik_` プレフィックスを使用し、主キー `id` は BIGINT 非自動採番（snowflake-php でアプリケーション層にて生成）。

---

## 五、テストの実行

```bash
# 業務 API テスト（21 tests）
cd service/
php vendor/bin/phpunit

# 管理バックエンドテスト（59 tests）
cd admin/
php vendor/bin/phpunit

# 静的解析
php vendor/bin/phpstan analyse --level=5 app/

# コードスタイルチェック
php vendor/bin/php-cs-fixer fix --dry-run --diff
```

---

## 六、第三者サービス設定

管理バックエンドの「システム設定」で以下の設定グループを入力します：

| 設定グループ | 用途 | 必須 |
|--------|------|------|
| `wechat_pay` | 微信支付商户号 / API 密钥 / 証明書 | 支払い機能に必要 |
| `wechat_app` | 微信小程序 AppID / AppSecret | 微信ログインに必要 |
| `sms` | 短信サービス事業者 (aliyun/tencent) + 署名/テンプレート | 短信認証コードに必要 |
| `map_service` | 地図サービス (amap/tencent) + API Key | LBS 機能に必要 |
| `storage` | オブジェクトストレージ (oss/cos) + AccessKey/Endpoint | ファイルアップロードに必要 |

---

## 七、よくある質問

**Q: 起動エラー `Class 'support\Model' not found`**
A: `composer dump-autoload` を実行してください。

**Q: データベース接続失敗 `SQLSTATE[HY000] [2002]`**
A: `.env` の `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD` 設定を確認してください。

**Q: SQL インポート時にエンコーディングエラー**
A: `mysql -u root -p --default-character-set=utf8mb4 < docs/install.sql` を使用してください。

**Q: Redis 接続失敗**
A: Redis が起動しているか確認し、`REDIS_HOST`/`REDIS_PORT` 設定を確認してください。

**Q: ポートが使用中**
A: `config/server.php` の `listen` ポートを変更してください。

**Q: 認証コードが表示されない**
A: GD 拡張がインストールされているか確認し、`POSTER_CAPTCHA_STORAGE` 設定が正しいか確認してください（ローカルは `file`、本番は `redis` を使用可）。

**Q: Elasticsearch が動作しない**
A: ES は任意コンポーネントです。`SCOUT_HOSTS` 設定が正しいか、ES サービスが起動しているか確認してください。

---

## 八、ディレクトリ構造

```
appointment-php/
├── admin/                    # 管理バックエンド (webman v2)
│   ├── app/                  # コントローラー / モデル / ミドルウェア
│   ├── config/               # ルート / データベース / ミドルウェア設定
│   ├── database/             # バックアップスクリプト（テーブル構造とシードデータの統一は docs/install.sql 参照）
│   ├── tests/                # PHPUnit テスト (59 tests)
│   ├── .env.example          # 環境変数テンプレート
│   ├── .env.docker           # Docker 環境変数
│   ├── Dockerfile            # Docker ビルドファイル
│   └── docker-compose.yml    # Docker オーケストレーション
├── service/                  # 業務 API サービス (webman v2)
│   ├── app/                  # コントローラー / モデル / ミドルウェア
│   ├── config/               # セキュリティ / ルート / データベース設定
│   ├── seed.php              # デモデータシードランナー（docs/install.sql のデモデータ部分を読み込み）
│   ├── tests/                # PHPUnit テスト (21 tests)
│   ├── .env.example          # 環境変数テンプレート
│   ├── .env.docker           # Docker 環境変数
│   ├── Dockerfile            # Docker ビルドファイル
│   └── docker-compose.yml    # Docker オーケストレーション
├── docs/                     # ドキュメント
│   ├── INSTALL.md            # 本インストールガイド
│   ├── install.sql           # 統一データベースインストールスクリプト（2723 行）
│   ├── ARCHITECTURE.md       # アーキテクチャ設計ドキュメント
│   ├── API.md                # API リファレンスドキュメント
│   └── AUDIT-REPORT.md       # 審査レポート
└── .github/workflows/        # CI/CD パイプライン
    └── ci.yml
```
