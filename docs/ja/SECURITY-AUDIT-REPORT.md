# セキュリティ審査レポート — 予約システム (appointment-php)
> **Languages**: [中文](../SECURITY-AUDIT-REPORT.md) · [English](../en/SECURITY-AUDIT-REPORT.md) · [한국어](../ko/SECURITY-AUDIT-REPORT.md) · [Русский](../ru/SECURITY-AUDIT-REPORT.md) · [Deutsch](../de/SECURITY-AUDIT-REPORT.md) · [Français](../fr/SECURITY-AUDIT-REPORT.md) · [Español](../es/SECURITY-AUDIT-REPORT.md) · [Português](../pt/SECURITY-AUDIT-REPORT.md) · [हिन्दी](../hi/SECURITY-AUDIT-REPORT.md) · [العربية](../ar/SECURITY-AUDIT-REPORT.md) · [বাংলা](../bn/SECURITY-AUDIT-REPORT.md) · [Bahasa Indonesia](../id/SECURITY-AUDIT-REPORT.md)

**日付**: 2026-08-04
**審査範囲**: service（予約サービスシステム）、admin（オープン管理バックエンド）
**PHP バージョン**: 8.3.7
**フレームワーク**: webman v2

---

## 一、テスト結果

| テスト項目 | Service | Admin |
|--------|---------|-------|
| PHP 構文チェック（全量） | 合格 | 合格 |
| PHPUnit ユニットテスト | 59 tests / 165 assertions PASS | 59 tests / 165 assertions PASS |
| PHPStan 静的解析 | 未インストール (dev 依存のダウンロードタイムアウト) | 未インストール (dev 依存のダウンロードタイムアウト) |

---

## 二、セキュリティ防御の階層概要

```
リクエスト → Nginx (セキュリティヘッダー+機密ファイル保護) → Cors (CORS+セキュリティヘッダー) → SecurityMiddleware (31種の攻撃検知) → RateLimit (Redisスライディングウィンドウ) → Auth (JWT) → Controller
                                                                                                   ↓
                                                                                    IPブラックリスト (5回攻撃/60s → 15min封禁)
                                                                                    アカウントロック (5回失敗/15min → 15minロック)
```

---

## 三、修正済みの問題

### 3.1 Service CORS にセキュリティレスポンスヘッダーが不足 → 修正済み
**ファイル**: `service/app/middleware/Cors.php`
- セキュリティヘッダー 6 個を追加：X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, CSP, X-Permitted-Cross-Domain-Policies
- 現在は admin のセキュリティヘッダー設定と一致

### 3.2 Service にログイン失敗ロックがない → 修正済み
**ファイル**: `service/app/api/v1/controller/AuthController.php`
- `login()` と `loginByCode()` に Redis 失敗カウントを追加
- 5回失敗/15分ロック → HTTP 429
- Redis 障害時は優雅にフォールバック

### 3.3 CORS Origin がハードコード `*` → 修正済み
**ファイル**: `service/app/middleware/Cors.php`, `admin/app/middleware/Cors.php`
- `CORS_ALLOW_ORIGIN` 環境変数による設定に変更
- 空欄時はデフォルト `*`（後方互換）

### 3.4 Service に security-php 依存がない → 修正済み
**操作**:
- `allow-plugins.erikwang2013/security-php` を composer.json に追加
- `composer install --no-dev` を実行して依存をインストール
- 設定ファイルは `config/plugin/erikwang2013/security-php/app.php` に公開済み
- CSRF Origin 検知器 (`csrf_origin`) を有効化 (block モード)

### 3.5 Service Nginx に Permissions-Policy がない → 修正済み
**ファイル**: `service/docs/nginx.conf`
- `add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;` を追加

### 3.6 エコシステム設定の補完 → 修正済み
- `service/.env.example` と `admin/.env.example` に `CORS_ALLOW_ORIGIN` を追加
- `service/.env.docker` と `admin/.env.docker` に `CORS_ALLOW_ORIGIN` を追加

---

## 四、現在のセキュリティ防御の完全リスト

### 4.1 WAF 層 — 31 種の攻撃検知器

| モード | 検知器 | 数量 |
|------|--------|------|
| **block** (403で遮断) | XSS, SQLインジェクション, コマンドインジェクション, パストラバーサル, ファイルアップロード, SSRF, XXE, 逆シリアル化, LDAPインジェクション, メールヘッダーインジェクション, Open Redirect, JWT攻撃, Hostヘッダー攻撃, Request Smuggling, GraphQLインジェクション, XPATHインジェクション, JNDI/Log4Shell, SSIインジェクション, CSVインジェクション, データ漏えい, Prototype Pollution, WebSocketハイジャック, CORSバイパス, DNS Rebinding, HTTPメソッド検証, リクエストボディサイズ(10MB), Content-Typeホワイトリスト, CSRF Origin | 28 |
| **log** (記録のみ) | レスポンスヘッダーインジェクション, SSTI, NoSQLインジェクション | 3 |

### 4.2 認証と認可

| メカニズム | Service | Admin |
|------|---------|-------|
| JWT 認証 | Auth ミドルウェア | AdminAuth ミドルウェア |
| JWT ブラックリスト | ログアウト時に追加 | ログアウト+セッション超過時に追加 |
| RBAC 権限 | — | method.path 形式, Redis 60s キャッシュ |
| アカウントロック | 5回/15分 (Redis) | 5回/15分 (Redis) |
| 並行セッション制限 | — | 最大 3 Token |
| パスワードハッシュ | bcrypt | bcrypt |

### 4.3 レート制限

| ルート | Service | Admin |
|------|---------|-------|
| デフォルト | 60 回/分/IP | 60 回/分/IP |
| ログイン | 10 回/分 | — |
| 登録 | 5 回/分 | — |
| 短信/パスワード忘れ | 5 回/分 | — |

### 4.4 データセキュリティ

| 措置 | Service | Admin |
|------|---------|-------|
| データベースフィールド暗号化 | AES-256-CBC (6 モデル) | AES-256-CBC |
| API 転送暗号化 | AES-256-CBC | AES-256-CBC |
| ID 混淆 (Hashids) | すべての対外 ID | すべての対外 ID |
| Snowflake ID | 非自動採番 BIGINT | 非自動採番 BIGINT |
| 機密フィールドのマスキング | 携帯番号マスキング | エクスポートデータのマスキング |

---

## 五、未処理の提案

### 5.1 提案：security-php のストレージを Redis に変更（本番環境）
**現在**: 両サービスとも `file` タイプのストレージ（ローカル JSON ファイル）を使用
**リスク**: マルチインスタンスデプロイ時は IP ブラックリストが共有されず、攻撃者がインスタンスを切り替えて回避できる
**提案**: 本番環境では `storage.type` を `redis` に変更

### 5.2 提案：Session Cookie のセキュリティ属性
**現在**: `secure: false`, `same_site: ''`
**リスク**: Cookie が HTTP で転送可能、CSRF 防御が弱まる
**提案**: 本番環境では `secure: true`, `same_site: 'Lax'` に設定

### 5.3 提案：PHPStan 開発依存のインストール
**現在**: `composer install --dev` がネットワークタイムアウトで失敗
**操作**: `composer install --dev` または `composer require --dev phpstan/phpstan`

### 5.4 注意：本番デプロイ前にすべての鍵を変更
`.env.docker` のプレースホルダー鍵は本番デプロイ前にランダム生成値へ置き換える必要があります：
- `JWT_SECRET_KEY`
- `HASHIDS_SALT`, `HASHIDS_ALT_SALT`
- `ENCRYPTION_KEY`, `ENCRYPTABLE_KEY`
- `DB_PASSWORD`

---

## 六、ドキュメント成果物

| ドキュメント | パス |
|------|------|
| Service セキュリティアーキテクチャ | `service/docs/SECURITY.md` |
| Admin セキュリティアーキテクチャ | `admin/docs/SECURITY.md` |
| 本審査レポート | `docs/SECURITY-AUDIT-REPORT.md` |

---

## 七、審査結論

**セキュリティ防御の総合評価：良好**

- 多層防御の階層が完全（Nginx → WAF → Rate Limit → Auth → RBAC）
- 31 種の攻撃検知器をグローバルにカバー、28 種が遮断モード
- JWT + ブラックリスト + アカウントロック + IP ブラックリストの多層認証防御
- データ層の AES-256-CBC 暗号化 + Hashids 混淆
- service 側のセキュリティレスポンスヘッダー欠落、ログインロック欠落、WAF パッケージ欠落の 3 つの重要問題を修正済み
- 提案項目は本番環境の設定最適化であり、セキュリティ脆弱性ではない

---

## 八、2026-08-26 修正ラウンド（セキュリティ強化）

| 項 | 修正内容 |
|----|---------|
| 注文改ざん防止 | OrderController::store() の注文項目価格は一律データベース記録に従う（service→appointment_service、product→appointment_product）、クライアント価格は計算に参加しない；不明な target_type 422；target_id は hashid 必須（raw id は 0 にデコード → 422「商品不存在或已下架」）；拼团/秒殺価格も同様に DB 基準 |
| 秒殺在庫の引き落とし統一 | 在庫は統一して /api/order store() トランザクション内の行ロックで引き落とし；SeckillController::buy は在庫を事前引き落とししない（Redis 活動ロック + client_token 冪等を保持）；/api/order に直接 seckill_id を渡しても同様に在庫を引き落とす |
| スタッフ出金 | 申請時に残高から在途（pending/approved）分を差し引いて予約；審査・振込前に settled−withdrawn−在途 ≥ 出金額を再検証；並行審査でも二重入金なし |
| 支払いコールバック | 微信コールバックの total_fee と注文支払額を厳密に比較、不一致は拒否；支付宝コールバックのログはマスキング（buyer_id/seller_id などを含めない） |
| /install 防御 | インストール成功で .install.lock を書き込み、install インターフェースは二重検証（ファイルロック + isInstalled）；.gitignore は .install.lock を無視済み |
| 依存収束 | webman-scout を 2.0.5 に統一（service/admin）；opensearch-project/opensearch-php ^2.6 を追加；dompdf/security-php/webman-database はバージョンを正確にロック（"*" ワイルドカードを廃止） |
| エンジニアリング | service/app/common/StorageService.php を削除（死コード）；admin/app/common/ に TechnicianWithdrawalService/WechatPayService を追加（admin は独立デプロイで service コードに依存しない）；両アプリの phpstan.neon を修正して実行可能に（php -d memory_limit=2G） |
