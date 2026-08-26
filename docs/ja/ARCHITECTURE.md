# アーキテクチャ説明

## システム概要

予約サービスシステムは三端 + 双サービスのアーキテクチャを採用しています：

```
┌─────────────────────────────────────────────────────┐
│                    ユーザー端末層                      │
│  ┌──────────────┐  ┌──────────────┐                 │
│  │ 微信ミニプログラム │  │ Flutter APP   │                │
│  │ apps/wechat/  │  │ apps/flutter/ │                │
│  └──────┬───────┘  └──────┬───────┘                 │
│         │     機能同等      │                         │
│         └────────┬─────────┘                         │
│                  │ 顧客/スタッフ 身分切替               │
├──────────────────┼──────────────────────────────────┤
│              業務API層                                 │
│  ┌──────────────┐  ┌──────────────┐                 │
│  │ service/ API  │  │ admin/ API    │                │
│  │ ポート 8787   │  │ ポート 8787   │                │
│  └──────┬───────┘  └──────┬───────┘                 │
│         │                  │                          │
│         └────────┬─────────┘                          │
│                  │ 共有 MySQL/Redis/ES                 │
├──────────────────┼──────────────────────────────────┤
│                  データ層                               │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌──────────┐     │
│  │ MySQL  │ │ Redis  │ │  ES    │ │ 第三者サービス │     │
│  └────────┘ └────────┘ └────────┘ └──────────┘     │
└─────────────────────────────────────────────────────┘
```

## プロジェクト構成

### service/ — 業務APIサービス

微信ミニプログラムと Flutter APP にすべての業務 API を提供します。webman v2、ポート 8787。

**モジュール構成：**

| モジュール | パス | 認証 | 説明 |
|------|------|------|------|
| 公開API | `api/` | なし | ログイン/登録/認証コード/微信コールバック |
| ユーザーモジュール | `user/` | JWT | プロフィール/住所/お気に入り/フィードバック/紹介 |
| スタッフモジュール | `technician/` | JWT+スタッフ | プロフィール/シフト/ワークベンチ/核销/会員/収益/出金 |
| サービスモジュール | `service/` | 混合 | カテゴリ/項目/検索/店舗 |
| 注文モジュール | `order/` | JWT | カート/注文/支払い/返金/核销/評価（OrderController は業務ドメインごとに 10 の trait に分割、ルートとメソッド名は不変） |
| マーケティングモジュール | `marketing/` | JWT | クーポン/会員カード(回数券)/ポイント/ギフトカード/会員特典 |
| ウォレットモジュール | `wallet/` | JWT | 残高/チャージ/取引履歴/残高払い |
| コンテンツモジュール | `content/` | 混合 | バナー/お知らせ/通知 |
| LBSモジュール | `lbs/` | 公開 | 都市/周辺店舗 |

### admin/ — 管理バックエンド

PC 管理バックエンド。webman v2 + Flutter Web、ポート 8787。

**既存モジュール：** 認証、ダッシュボード、ユーザー管理、ロール権限、システム設定、操作ログ、ファイルアップロード、セキュリティ防御

**モデル配置：** `admin/app/model/` には特有モデル 6 つのみ保持（AdminPermission/AdminRole/AdminUser/OperationLog/OperationLogDetail/SystemConfig）、その他のモデルは composer psr-4（`app\model\` → `../service/app/model/`）で service 版を共有し、二重モデルの乖離を防止；`support\Model` 基底クラスは service と揃え、`UserPointsExchange::user()` リレーションは service 版モデルに統合。

**拡張モジュール：** スタッフ管理、会員管理、店舗管理、サービス/商品管理、注文管理、クーポン、会員カード、出金審査、評価管理、レポート集計、財務管理、コンテンツ管理、システム設定

### apps/ — ユーザー端フロントエンド

| ディレクトリ | 技術 | プラットフォーム |
|------|------|------|
| `apps/wechat/` | ネイティブ微信ミニプログラム | 微信 |
| `apps/flutter/` | Flutter 3.x + GetX + Dio | iOS + Android |

## コアコンポーネント

### Snowflake ID

すべての主キーは `erikwang2013/snowflake-php` で生成され、BIGINT 非自動採番で分散環境のグローバル一意性を保証します。`service/support/Model::nextId()` はプロセス内で単一の Snowflake インスタンスを再利用し、64 モデルの `generateId()` コピーは削除済み（基底クラス実装に統一）。

### Hashids

API リクエスト/レスポンス内の ID は `erikwang2013/hashids` でエンコードされ、外部には hash 文字列として公開されます。

### JWT認証

`erikwang2013/jwt-webman` Bearer Token、7 日間有効、リフレッシュとブラックリストをサポート。

### データ暗号化

- **API層**：`erikwang2013/encryption` 機密データの暗号化/復号
- **DB層**：`erikwang2013/encryptable` trait によるフィールドの自動暗号化/復号

### セキュリティ防御

- `erikwang2013/security-php`：31 種の攻撃検知
- `erikwang2013/poster-php`：敏感操作のランダム検証
- ログインロック：5 回失敗で 15 分ロック
- 同時実行制限：有効 Token は最大 3 つ

### APIドキュメント

`hg/apidoc` で OpenAPI 3.0 仕様ドキュメントを生成、管理端とクライアントで分離：

| 端 | アドレス | 説明 |
|------|------|------|
| 管理端 | `admin/ GET /api/docs` | 管理バックエンドAPI（JWT+RBAC） |
| クライアント | `service/ GET /api/docs` | 業務API（JWT Bearer） |

ドキュメントは公開アクセス可能で、Swagger UI にインポートしてインタラクティブな API ドキュメントを確認できます。

### Elasticsearch

`erikwang2013/webman-scout` がモデルを自動で ES に同期し、全文検索をサポートします。

## ミドルウェア実行チェーン

### service/ ミドルウェア

```
公開API:  Cors → Security(31種検知) → RateLimit → ApiVersion → Controller
ユーザーAPI:  Cors → Security → RateLimit → Auth(JWT) → Controller
スタッフAPI:  Cors → Security → RateLimit → ApiVersion → Auth → TechnicianAuth → Controller
```

### admin/ ミドルウェア

```
公開API:  Cors → Security → RateLimit → Controller
管理API:  Cors → Security → RateLimit → AdminAuth(JWT) → AdminPermission(RBAC) → OperationLog → Controller
ヘルスチェック: Cors → Security → RateLimit → Controller
```

## データフロー

### リクエストフロー

```
クライアント → Cors → Security → RateLimit → Auth(JWT) → [TechnicianAuth] → Controller
    → Model(encryptable暗号化/復号) → BaseController(hashidsエンコード) → JSONレスポンス
```

### 予約フロー

```
サービス閲覧 → 店舗/スタッフ/時間を選択 → 注文送信 → Redisでスタッフを3分間ロック
    → 微信決済 → スタッフへ通知 → サービス開始 → サービス完了 → 評価 → 注文完了
```

## 8つの操作来源端

## 最新の拡張

| カテゴリ | 機能 |
|------|------|
| リアルタイム | WebSocket プッシュ / 支払いコールバック / APNs+FCM |
| メッセージ | 購読メッセージプッシュ（sendSubscribeMessage 注文イベント3シナリオ） |
| ウォレット | 残高チャージ / 残高払い / 返金回充 |
| 店舗 | ブルートゥース印刷 / 電子印鑑 / 待ち番号呼び出し |
| スタッフ | オンライン試験 / ショート動画表示 / ワークベンチ（today/records/start/complete） |
| コミュニティ | 投稿/コメント/いいね/審査 |
| システム | 多言語（中/英） / 注文自動キャンセル / データシード |

`source` フィールドは操作来源を記録：web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### 第三者サービス統合

| サービス | クラス | 機能 |
|------|------|------|
| 微信決済 | WechatPayService | 統一下単/照会/返金/零錢への出金 |
| 短信 | SmsService | 阿里云/腾讯云 双チャネル |
| 地図 | MapService | 高徳/腾讯 逆ジオコーディング/距離/ナビ |
| テンプレートメッセージ | WechatTemplateMessageService | 注文/返金/リマインダープッシュ + 購読メッセージ（sendSubscribeMessage 注文イベント3シナリオ） |
