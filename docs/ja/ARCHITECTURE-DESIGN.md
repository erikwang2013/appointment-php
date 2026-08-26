# アーキテクチャ設計
> **Languages**: [中文](../ARCHITECTURE-DESIGN.md) · [English](../en/ARCHITECTURE-DESIGN.md) · [한국어](../ko/ARCHITECTURE-DESIGN.md) · [Русский](../ru/ARCHITECTURE-DESIGN.md) · [Deutsch](../de/ARCHITECTURE-DESIGN.md) · [Français](../fr/ARCHITECTURE-DESIGN.md) · [Español](../es/ARCHITECTURE-DESIGN.md) · [Português](../pt/ARCHITECTURE-DESIGN.md) · [हिन्दी](../hi/ARCHITECTURE-DESIGN.md) · [العربية](../ar/ARCHITECTURE-DESIGN.md) · [বাংলা](../bn/ARCHITECTURE-DESIGN.md) · [Bahasa Indonesia](../id/ARCHITECTURE-DESIGN.md)

## 階層アーキテクチャ

```
┌─────────────────────────────────────────┐
│              表現層 (Presentation)        │
│  微信小程序 / Flutter APP / Flutter Web   │
├─────────────────────────────────────────┤
│              ルート層 (Route)             │
│  config/route.php — ルートグループ + ミドルウェアバインド │
├─────────────────────────────────────────┤
│            ミドルウェア層 (Middleware)      │
│  Cors → Security → RateLimit → Auth      │
│  → TechnicianAuth → OperationLog         │
├─────────────────────────────────────────┤
│            コントローラー層 (Controller)    │
│  BaseController → 各業務Controller        │
├─────────────────────────────────────────┤
│             サービス層 (Service)           │
│  common/ — Snowflake/Hashids/Encryption  │
├─────────────────────────────────────────┤
│             モデル層 (Model)               │
│  Eloquent ORM + Encryptable + Scout      │
├─────────────────────────────────────────┤
│              データ層 (Data)               │
│  MySQL / Redis / Elasticsearch           │
└─────────────────────────────────────────┘
```

## ミドルウェア設計

### 実行チェーン

```
Cors → Security(31種の攻撃検知) → RateLimit → Auth(JWT+ユーザー状態)
    → [TechnicianAuth(スタッフ身分)] → [AdminPermission(RBAC)] → [OperationLog(8つの操作来源端)]
    → Controller
```

### ミドルウェアの責務

| ミドルウェア | スコープ | 機能 |
|--------|--------|------|
| Cors | グローバル | OPTIONSプリフライト + CORSレスポンスヘッダー |
| Security | グローバル | erikwang2013/security-php、31種の攻撃検知 |
| RateLimit | グローバル | Redisスライディングウィンドウ+Lua原子化 |
| Auth | ルートグループ | JWT解析 + ユーザーの存在性/状態検証 |
| TechnicianAuth | ルートグループ | スタッフカルテ照会 + approved状態検証 |
| AdminAuth | ルートグループ | Admin端JWT認証 + ブラックリスト |
| AdminPermission | ルートグループ | RBAC権限検証、Redis 60秒キャッシュ |
| OperationLog | ルートグループ | 操作ログ + 8つの操作来源端の自動検出 |

### レート制限ポリシー

| インターフェース | 制限 |
|------|------|
| デフォルト | 60回/分/IP |
| ログイン | 10回/分 |
| 登録 | 5回/分 |
| 認証コード | 1回/60秒/携帯番号 |

## データベース設計原則

### 主キー戦略

- すべての主キー：BIGINT UNSIGNED NOT NULL、非自動採番
- `erikwang2013/snowflake-php` でアプリケーション層にて生成
- Model: `$incrementing = false`, `$keyType = 'string'`

### テーブルプレフィックス

統一 `erik_` プレフィックス、`config/database.php` で設定。Modelは元のテーブル名を書き、ORMが自動的にプレフィックスを付与します。

### 機密フィールドの暗号化

`erikwang2013/encryptable` trait を使用：

```php
use Erikwang2013\Encryptable\Encryptable;

class User extends Model
{
    use Encryptable;
    protected array $encryptable = [
        'phone', 'wx_openid', 'wx_unionid', 'real_name',
    ];
}
```

暗号化フィールドのVARCHAR長は500に設定（暗号化データの膨張分）。

### ソフトデリートとタイムスタンプ

- Eloquent SoftDeletes: `deleted_at` DATETIME DEFAULT NULL
- すべてのテーブルに `created_at` + `updated_at`

## API ID暗号化/復号メカニズム

### リクエスト：decodeIds()

フロントがhashidsエンコードされたIDを送信 → コントローラーが `$this->decodeIds($request->all())` を呼び出して復号します。

### レスポンス：encodeIds()

DBクエリ結果のID → `BaseController::success()` が自動的に `encodeIds()` でエンコード → hashids文字列を返します。

### ルール

配列内のキー名が `id` または `_id` で終わるフィールドを再帰的に処理します。

## セキュリティ設計

### 多層防御

```
WAF → Cors → Security(31種検知) → RateLimit → Auth(JWT+状態)
    → [身分検証] → [RBAC] → Controller(Model暗号化) → レスポンス
```

### 認証セキュリティ

- パスワード：bcryptハッシュ
- JWT：7日間有効 + リフレッシュ + ブラックリスト
- ロック：5回失敗→15分
- 並行制限：最大3トークン

### データセキュリティ

- API層：erikwang2013/encryption
- DB層：erikwang2013/encryptable trait
- ログ：機密データはログに記録しない

### 操作セキュリティ

- erikwang2013/poster-php：削除/審査/出金前に検証
- Securityミドルウェア：XSS/SQLインジェクション/CSRF/パストラバーサル検知

## Elasticsearch統合

`erikwang2013/webman-scout` がモデルをESに自動同期：

```php
use Erikwang2013\WebmanScout\Searchable;

class Service extends Model
{
    use Searchable;
    public function searchableAs(): string { return 'erik_services'; }
}
```

## Excel/PDFエクスポート

- Excel：PhpSpreadsheet、機密フィールドは自動マスキング
- PDF：Dashboardパネルのビジュアルエクスポート

## 8つの操作来源端の検出

OperationLogはUser-Agentを解析：

```
iPad → iPadOS / Mac → macOS / Windows → Windows
Linux → Linux / iPhone → ios / Android → android
HarmonyOS → harmonyOS / その他 → web
```

## TDD テスト

| 項目 | テスト数 | 状態 |
|------|--------|------|
| admin/ | 60 | ✅ 合格 |
| service/ | 21 | ✅ 合格 |
| 合計 | 81 | ✅ |

テストカバレッジ: 返金ルール / 注文状態 / Hashids / 待ち番号システム / 暗号化 / 認証コード
