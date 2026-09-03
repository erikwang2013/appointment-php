# 予約サービスシステム — プロジェクト構成
> **Languages**: [中文](../STRUCTURE.md) · [English](../en/STRUCTURE.md) · [한국어](../ko/STRUCTURE.md) · [Русский](../ru/STRUCTURE.md) · [Deutsch](../de/STRUCTURE.md) · [Français](../fr/STRUCTURE.md) · [Español](../es/STRUCTURE.md) · [Português](../pt/STRUCTURE.md) · [हिन्दी](../hi/STRUCTURE.md) · [العربية](../ar/STRUCTURE.md) · [বাংলা](../bn/STRUCTURE.md) · [Bahasa Indonesia](../id/STRUCTURE.md)

## リポジトリ概要

```
appointment-php/
├── admin/              # 管理バックエンド (webman v2 + Flutter Web)
├── service/            # 業務APIサービス (webman v2)
├── apps/               # ユーザー端フロントエンドアプリ
│   ├── wechat/         #   微信小程序（ネイティブ）
│   ├── flutter/        #   Flutter APP（iOS + Android）
│   └── harmonyos/      #   HarmonyOS APP（鴻蒙ネイティブ）
├── docs/               # プロジェクトドキュメント
└── .claude/            # Claude Code 設定
```

## プロジェクト間の関係

```
┌──────────────────────────────────────────────┐
│                   apps/                       │
│  ┌─────────────┐  ┌──────────┐  ┌─────────┐  │
│  │ wechat/      │  │ flutter/  │  │harmonyos/│  │
│  │ 微信小程序    │  │iOS/Android│  │ 鴻蒙 APP │  │
│  └──────┬──────┘  └────┬─────┘  └────┬────┘  │
│         │     機能完全相同      │            │
│         └──────────┬─────────┘            │
│                    │ HTTP API                 │
├────────────────────┼─────────────────────────┤
│              service/                         │
│         業務API (webman v2)                    │
│             ポート: 8787                       │
│                    │                          │
│                    │ 共有 MySQL/Redis/ES       │
│                    │                          │
│              admin/                           │
│         管理バックエンドAPI (webman v2)          │
│             ポート: 8787                       │
│                    │                          │
│         ┌──────────┴──────────┐               │
│         │                     │               │
│    admin/apps/flutter/    Flutter Web         │
│    管理バックエンドフロント (PC)                │
└──────────────────────────────────────────────┘
```

## admin/ — 管理バックエンド

```
admin/
├── app/
│   ├── admin/controller/       # 管理端コントローラー
│   │   ├── BaseController          # ベースコントローラー
│   │   ├── DashboardController     # ダッシュボード
│   │   ├── UserController          # ユーザー管理
│   │   ├── RoleController          # ロール管理
│   │   ├── PermissionController    # 権限管理
│   │   ├── ConfigController        # システム設定
│   │   ├── LogController           # 操作ログ
│   │   ├── ProfileController       # マイページ
│   │   ├── ExportController        # エクスポート
│   │   ├── ImportController        # インポート
│   │   ├── UploadController        # ファイルアップロード
│   │   ├── HealthController        # ヘルスチェック
│   │   ├── DocsController          # APIドキュメント
│   │   ├── MetricsController       # Prometheus指標
│   │   │                            # ✅ 実装済み業務モジュール:
│   │   ├── TechnicianController    #   スタッフ管理(リスト/審査/排班/エクスポート)
│   │   ├── MemberController        #   会員管理(レベル/消費)
│   │   ├── StoreController         #   店舗CRUD
│   │   ├── ServiceController       #   サービス項目CRUD
│   │   ├── ServiceCategoryController # サービス分類CRUD(ツリー型)
│   │   ├── ProductController       #   商品CRUD
│   │   ├── MallOrderController     #   モール注文/発送/アフターサービス
│   │   ├── SalesStatsController    #   販売統計(Redisキャッシュ)
│   │   ├── AppointmentOrderController  # 予約注文(キャンセル/完了)
│   │   ├── MemberCardController    #   会員カード定義CRUD
│   │   ├── ReviewController        #   サービス評価管理
│   │   ├── ReportController        #   データレポート集計
│   │   ├── CouponController        #   クーポンCRUD
│   │   ├── FinanceController       #   財務流水/統計
│   │   ├── WithdrawalController    #   出金審査(承認/却下/完了)
│   │   ├── CommissionController    #   歩合設定/賞罰
│   │   ├── WithdrawalAccountController # 出金アカウント管理
│   │   ├── WithdrawalConfigController  # 出金制限設定
│   │   ├── BannerController        #   カルーセル画像CRUD
│   │   ├── AnnouncementController  #   お知らせCRUD/公開
│   │   ├── FaqController           #   よくある質問CRUD
│   │   ├── FeedbackController      #   意見フィードバック/返信
│   │   ├── MomentController        #   モーメンツ審査
│   │   ├── AgreementController     #   規約編集/公開
│   │   ├── AboutController         #   会社概要設定
│   │   └── SystemMessageController #   システムメッセージテンプレート/送信
│   │   │                            # ✅ 拡張モジュール:
│   │   ├── ServiceCardController    #   カード項目設計
│   │   ├── SystemMonitorController  #   システム監視
│   │   ├── IpBlacklistController    #   IPブラックリスト管理
│   │   ├── DbBackupController       #   データベースバックアップ
│   │   ├── SmsConfigController      #   短信設定
│   │   ├── StorageConfigController  #   ストレージ設定
│   │   ├── StoreManagerController   #   店長アカウント
│   │   ├── TrainingController       #   スタッフ研修
│   │   ├── ScheduledTaskController  #   スケジュールタスク
│   │   ├── CustomerProfileController #  顧客ペルソナ
│   │   ├── BatchMessageController   #   バッチプッシュ
│   │   ├── RefundWorkflowController #   返金審査
│   │   ├── TechnicianTierController #   スタッフレベル
│   │   │                            # ✅ 第22-25ラウンド新規:
│   │   ├── FullReductionController  #   満減キャンペーン
│   │   ├── AttendanceController     #   スタッフ勤怠
│   │   ├── ProfitSharingController  #   微信分账
│   │   ├── LuckyWheelController     #   ポイントルーレット
│   │   ├── PointsExchangeGoodsController # ポイント交換商品
│   │   ├── ReviewAuditController    #   評価画像審査
│   │   ├── InvoiceController        #   電子インボイス
│   │   ├── TicketController         #   カスタマーサポートチケット
│   │   ├── ReferralRewardController #   1級返金記録
│   │   ├── ReferralLevel2Controller #   2級返金記録
│   │   ├── ReturnCustomerController #   リピーター報酬
│   │   ├── SeckillController        #   秒殺キャンペーン
│   │   ├── VersionController        #   APPバージョン管理
│   │   ├── TechnicianScheduleController # 排班管理/CSVエクスポート
│   │   ├── AftersaleController      #   アフターサービス処理
│   │   ├── OrderVerificationController # 核销記録
│   │   ├── CommunityModerationController # コミュニティ審査
│   │   ├── VideoAuditController     #   動画審査
│   │   └── InstallController        #   インストールウィザード
│   ├── api/v1/controller/      # 公開API v1
│   │   ├── AuthController
│   │   └── CaptchaController
│   ├── common/                 # 共通ユーティリティ
│   │   ├── HashidsService
│   │   ├── SnowflakeService
│   │   ├── EncryptionService
│   │   ├── TechnicianWithdrawalService
│   │   └── WechatPayService
│   ├── middleware/             # ミドルウェア
│   │   ├── Cors
│   │   ├── RateLimit
│   │   ├── AdminAuth
│   │   ├── AdminPermission
│   │   └── OperationLog
│   ├── model/                  # データモデル（特有モデルのみ 6 個：AdminPermission/AdminRole/AdminUser/OperationLog/OperationLogDetail/SystemConfig；その他は psr-4 で service 版を共有）
│   ├── queue/                  # キュータスク
│   └── process/                # プロセス
├── apps/
│   ├── flutter/                # Flutter Web 管理バックエンドフロント
│   │   └── lib/app/
│   │       ├── pages/           #   ページ（20個）
│   │       │   ├── dashboard/   #   ダッシュボード
│   │       │   ├── login/       #   ログイン
│   │       │   ├── user/        #   ユーザー管理
│   │       │   ├── member/      #   会員管理
│   │       │   ├── role/        #   ロール権限
│   │       │   ├── config/      #   システム設定
│   │       │   ├── log/         #   操作ログ
│   │       │   ├── profile/     #   マイページ
│   │       │   ├── technician/  #   スタッフ管理
│   │       │   ├── schedule/    #   排班
│   │       │   ├── service/     #   サービス/商品管理
│   │       │   ├── service_card/#   カード項目設計
│   │       │   ├── order/       #   注文管理
│   │       │   ├── verification/#   核销記録
│   │       │   ├── coupon/      #   クーポン
│   │       │   ├── withdrawal/  #   出金審査
│   │       │   ├── report/      #   レポート集計
│   │       │   ├── review/      #   評価管理
│   │       │   ├── announcement/#   お知らせ
│   │       │   └── faq/         #   よくある質問
│   │       ├── services/        #   APIサービス層
│   │       ├── layouts/         #   レイアウト
│   │       └── theme/           #   テーマ
│   ├── harmonyos/               # HarmonyOS 管理端（ArkTS）
│   └── weixin/                  # 微信管理端
├── config/                     # 設定ファイル
│   ├── route.php
│   ├── middleware.php
│   ├── database.php
│   ├── jwt.php
│   ├── snowflake.php
│   ├── hashids.php
│   ├── encryption.php
│   ├── encryptable.php
│   └── ...
├── database/
│   └── backup/                 # バックアップスクリプト（テーブル構造とシードデータの統一は docs/install.sql 参照）
├── docs/                       # 管理バックエンドドキュメント
├── public/                     # エントリファイル
├── runtime/                    # ランタイム
├── tests/                      # テスト
├── vendor/                     # 依存関係
├── CLAUDE.md
├── composer.json
├── Dockerfile
└── docker-compose.yml
```

## service/ — 業務API

```
service/
├── app/
│   ├── api/v1/controller/       # 公開API v1（26 コントローラー）
│   │   ├── AuthController          # ログイン/登録/パスワード忘れ/リフレッシュ/身分切替
│   │   ├── CaptchaController       # 短信認証コード(Redisレート制限)
│   │   ├── CommonController        # 共通設定/規約/エリア
│   │   ├── ContentController       # カルーセル画像/お知らせ/記事
│   │   ├── DocsController          # OpenAPIドキュメント(hg/apidoc)
│   │   ├── LbsController           # 近くの店舗(Haversine)/逆ジオコーディング
│   │   ├── GuestController         # ゲストモード（未ログインの読み取り専用閲覧、Redisキャッシュ）
│   │   ├── SeckillController       # 秒殺キャンペーン/購入（独立チャネル）
│   │   ├── PromotionController     # 拼团（旧 flash_sale チャネルは廃止）
│   │   ├── ServiceController       # サービス分類/項目/商品/店舗
│   │   ├── ServicePackageController # サービスパッケージ
│   │   ├── StoreManagerController  # 店長ワークベンチ（overview/orders/technicians/revenue）
│   │   ├── TechnicianController    # スタッフ公開情報
│   │   ├── BrowseHistoryController # 閲覧履歴
│   │   ├── CalendarController      # 予約月カレンダー（月/日ビュー）
│   │   ├── CommunityController     # コミュニティ動態
│   │   ├── CommunityCommentController # コミュニティコメント
│   │   ├── FullReductionController # 満減キャンペーン
│   │   ├── PaymentNotifyController # 支払いコールバック（微信/支付宝）
│   │   ├── PrintController         # 印刷
│   │   ├── PrivacyController       # プライバシーコンプライアンス（データエクスポート/アカウント削除）
│   │   ├── QueueController         # 待ち番号呼び出し
│   │   ├── VersionController       # APPバージョン管理/更新チェック
│   │   ├── VideoController         # 動画
│   │   ├── WechatController        # 微信関連
│   │   └── WheelController         # ポイントラッキールーレット
│   ├── user/v1/controller/      # ユーザーモジュール v1（14 コントローラー）
│   │   ├── ProfileController       # 個人情報/パスワード/携帯番号/アカウント削除/ログアウト
│   │   ├── AddressController       # 住所CRUD(デフォルト住所管理)
│   │   ├── FavoriteController      # お気に入り(サービス/スタッフ)
│   │   ├── FeedbackController      # 意見フィードバック(テキスト+画像)
│   │   ├── ReferralController      # 紹介/QRコード/紹介済みユーザー
│   │   ├── CheckInController       # チェックイン
│   │   ├── DeviceController        # ユーザーデバイス管理
│   │   ├── GrowthController        # 成長レベル（概览/records/levels）
│   │   ├── HealthProfileController # 健康カルテ
│   │   ├── InvoiceController       # 電子インボイス申請/リスト/詳細
│   │   ├── InvoiceTitleController  # インボイス宛名ライブラリ
│   │   ├── NotifySettingController # メッセージ通知設定
│   │   ├── PointsTransferController# ポイント転贈
│   │   └── TicketController        # カスタマーサポートチケット
│   ├── technician/v1/controller/ # スタッフモジュール v1（10 コントローラー）
│   │   ├── ProfileController       # スタッフカルテ/参入申請
│   │   ├── ScheduleController      # 排班照会/設定
│   │   ├── OrderController         # スタッフ注文リスト
│   │   ├── WorkController          # ワークベンチ(today/records/start/complete)
│   │   ├── EarningController       # 収益概要+流水
│   │   ├── WithdrawController      # 出金申請（毎月 config('withdraw.gate_day') 日、設定可）
│   │   ├── ServiceRecordController # サービス記録
│   │   ├── ExamController          # オンライン試験
│   │   ├── AttendanceController    # 出勤/退勤の打刻勤怠
│   │   └── ReviewController        # スタッフによる評価への返信
│   ├── order/v1/controller/     # 注文モジュール v1（8 コントローラー + 9 trait）
│   │   ├── OrderController         # 注文(スタッフロック)/リスト/詳細/キャンセル/支払い/返金/核销（集約エントリ、38行、メソッドはすべて trait 由来）
│   │   ├── OrderCreateTrait        # 注文作成 store/価格計算補助 (475行)
│   │   ├── OrderQueryTrait         # 注文照会 リスト/詳細/物流 (205行)
│   │   ├── OrderPayTrait           # 支払い pay/残高支払い/ポイント充当 (415行)
│   │   ├── OrderCancelTrait        # 注文キャンセル (272行)
│   │   ├── OrderRefundTrait        # 返金申請 (379行)
│   │   ├── OrderCompensateTrait    # 返金補償スキャン+割引/ポイント返還 (345行)
│   │   ├── OrderVerifyTrait        # 核销 歩合/ポイント付与 (256行)
│   │   ├── OrderRescheduleTrait    # 予約改期 (181行)
│   │   ├── OrderNotifyTrait        # 通知 購読/テンプレート/站内/WebSocket (195行)
│   │   └── OrderLockTrait          # 分散ロックツール (80行)
│   │   ├── AftersaleController     # アフターサービス
│   │   ├── CartController          # ショッピングカート
│   │   ├── IcsController           # ICSカレンダーエクスポート
│   │   ├── ReviewController        # 評価/追記
│   │   ├── SignatureController     # 署名
│   │   ├── TimelineController      # 注文状態タイムライン
│   │   └── WaitlistController      # ウェイティングリスト
│   ├── wallet/v1/controller/    # ウォレットモジュール v1（2 コントローラー）
│   │   ├── WalletController        # 残高/チャージ/取引流水/残高支払い
│   │   └── WalletTransferController# ユーザー間振込
│   ├── marketing/v1/controller/ # マーケティングモジュール v1（7 コントローラー）
│   │   ├── CouponController        # クーポンリスト/受け取り/注文時充当
│   │   ├── CardController          # 会員カードリスト/購入/回数券 my/use
│   │   ├── PointController         # ポイント流水/消費還元
│   │   ├── GiftCardController      # ギフトカード/交換 redeem
│   │   ├── MemberBenefitController # 会員特典
│   │   ├── MemberCardController    # 会員カード定義
│   │   └── PointsExchangeController# ポイント交換モール
│   ├── notification/v1/controller/ # 通知モジュール v1（1 コントローラー）
│   │   └── NotificationController  # 通知リスト/既読マーク
│   ├── common/                  # 共通能力（BaseController など）
│   ├── middleware/              # ミドルウェア
│   │   ├── Auth                    # JWT認証+ユーザー状態検証
│   │   ├── Cors                    # クロスオリジン処理
│   │   ├── Security                # セキュリティ検知(security-php)
│   │   └── TechnicianAuth          # スタッフ身分検証
│   └── model/                   # データモデル(81個)
│       ├── User.php → appointment_user
│       ├── TechnicianProfile.php → appointment_technician_profile
│       ├── Service.php → appointment_service (ES: appointment_services)
│       ├── Product.php → appointment_product (ES: appointment_products)
│       ├── Store.php → appointment_store
│       ├── Order.php → appointment_order (返金ルール/ステートマシン含む)
│       ├── Coupon.php → appointment_coupon
│       ├── MemberCard.php → appointment_member_card
│       ├── Notification.php → appointment_notification
│       └── ... (全81個のモデルファイル；admin に特有モデル 6 個を加え、合計 87)
├── config/                     # 設定ファイル
├── public/                     # エントリ
├── runtime/                    # ランタイム
├── vendor/                     # 依存関係
├── start.php
├── composer.json
└── Dockerfile
```

## apps/ — ユーザー端フロントエンド

### apps/wechat/ — 微信小程序

```
apps/wechat/
├── app.js                      # アプリケーションエントリ
├── app.json                    # グローバル設定
├── app.wxss                    # グローバルスタイル
├── pages/
│   ├── auth/                   # 認証
│   │   ├── login               #   ログイン
│   │   ├── register            #   登録
│   │   ├── forget-password     #   パスワード忘れ
│   │   └── agreement           #   規約閲覧
│   ├── home/                   # ホーム（カルーセル画像/お知らせ/分類/検索）
│   ├── service/                # サービス
│   │   ├── list                #   サービスリスト
│   │   └── detail              #   サービス詳細
│   ├── order/                  # 注文
│   │   ├── list                #   注文リスト
│   │   ├── detail              #   注文詳細
│   │   └── confirm             #   注文確認
│   ├── cart/                   # ショッピングカート
│   ├── cards/                  # 会員カード（購入/マイ/回数券使用 my/use）
│   ├── gift-cards/             # ギフトカード（交換 redeem/入帳）
│   ├── points/                 # ポイント（流水/交換）
│   ├── marketing/              # マーケティング（クーポンなど）
│   ├── favorite/               # お気に入り
│   ├── feedback/               # 意見フィードバック
│   ├── referral/               # 紹介
│   ├── message/                # メッセージ
│   │   ├── list                #   メッセージリスト
│   │   └── detail              #   メッセージ詳細
│   ├── tech-work/              # スタッフワークベンチ
│   │   ├── index               #   ワークベンチホーム(today/records/start/complete)
│   │   ├── schedule            #   排班
│   │   ├── order-list          #   注文
│   │   ├── scan-verify         #   スキャン核销
│   │   ├── member-list         #   会員リスト
│   │   ├── member-detail       #   会員詳細
│   │   ├── earnings            #   収益
│   │   ├── withdrawal          #   出金
│   │   ├── transaction-list    #   取引明細
│   │   └── training            #   研修
│   ├── user/                   # マイページ
│   │   ├── index               #   個人情報
│   │   ├── settings            #   設定
│   │   └── switch-role         #   身分切替
│   └── wallet/                 # ウォレット（残高/チャージ/取引流水）
├── components/                 # 共通コンポーネント
│   ├── navbar
│   ├── tabbar
│   ├── service-card
│   ├── technician-card
│   ├── coupon-popup
│   └── lbs-selector
├── utils/                      # ユーティリティ
│   ├── api.js                  #   HTTPリクエスト
│   ├── auth.js                 #   認証管理
│   ├── location.js             #   LBS位置情報
│   └── constants.js            #   定数
├── styles/                     # 共通スタイル
└── images/                     # 画像リソース
```

### apps/flutter/ — Flutter APP

```
apps/flutter/
├── lib/
│   ├── main.dart               # エントリ
│   ├── app.dart                # App設定/ルート/テーマ
│   ├── pages/                  # ページ（小程序と同一構造）
│   │   ├── auth/
│   │   ├── home/
│   │   ├── service/
│   │   ├── order/
│   │   ├── cart/
│   │   ├── technician/
│   │   ├── tech_work/
│   │   ├── user/
│   │   ├── marketing/
│   │   ├── message/
│   │   ├── store/
│   │   └── other/
│   ├── widgets/                # 共通コンポーネント
│   ├── services/               # APIサービス
│   │   ├── api_service         #   HTTP (Dio)
│   │   ├── auth_service        #   認証
│   │   └── location_service    #   位置情報
│   ├── models/                 # データモデル
│   ├── state/                  # 状態管理
│   └── utils/                  # ユーティリティ
├── android/                    # Androidプロジェクト
├── ios/                        # iOSプロジェクト
├── pubspec.yaml
└── ...
```

## ミドルウェア実行チェーン

### service/

```
公開API:  Cors → Security → RateLimit → Controller
ユーザーAPI:  Cors → Security → RateLimit → Auth → Controller
スタッフAPI:  Cors → Security → RateLimit → Auth → TechnicianAuth → Controller
支払いコールバック: Cors → Security → Controller
```

### admin/

```
公開API:  Cors → Security → RateLimit → Controller
管理API:  Cors → Security → RateLimit → AdminAuth → AdminPermission → OperationLog → Controller
ヘルスチェック: Cors → Security → RateLimit → Controller
```

## データベーステーブル一覧

全テーブルは `appointment_` プレフィックスを使用、BIGINT 非自動採番主キー（Snowflake生成）。

| ドメイン | テーブル名 | 説明 |
|----|------|------|
| ユーザー | appointment_user | 統一ユーザーテーブル |
| ユーザー | appointment_user_address | 配送先住所 |
| スタッフ | appointment_technician_profile | スタッフカルテ |
| スタッフ | appointment_technician_schedule | スタッフ排班 |
| スタッフ | appointment_technician_service | スタッフ提供可能なサービス項目 |
| スタッフ | appointment_technician_earnings | スタッフ収益流水 |
| スタッフ | appointment_technician_withdrawal | スタッフ出金記録 |
| スタッフ | appointment_technician_attendance | スタッフ勤怠 |
| スタッフ | appointment_technician_member_note | 会員カルテ |
| サービス | appointment_service_category | サービス分類 |
| サービス | appointment_service | サービス項目 |
| サービス | appointment_product | 商品 |
| サービス | appointment_store | 店舗 |
| 注文 | appointment_order | 注文マスターテーブル（秒殺 seckill_id 関連列、第24ラウンド） |
| 注文 | appointment_order_item | 注文明細 |
| 注文 | appointment_order_payment | 支払い記録 |
| 注文 | appointment_order_refund | 返金記録 |
| 注文 | appointment_order_review | サービス評価 |
| 注文 | appointment_order_verification | 核销記録 |
| 注文 | appointment_order_reschedule | 予約改期記録（第17ラウンド） |
| マーケティング | appointment_coupon | クーポン定義 |
| マーケティング | appointment_user_coupon | ユーザークーポン |
| マーケティング | appointment_user_coupon_transfer | クーポン転贈記録（第17ラウンド） |
| マーケティング | appointment_user_points_transfer | ポイント転贈記録（第19ラウンド） |
| マーケティング | appointment_technician_tier_log | スタッフレベル変更ログ（第17ラウンド） |
| マーケティング | appointment_member_card | 会員カード定義 |
| マーケティング | appointment_user_member_card | ユーザー会員カード |
| マーケティング | appointment_member_card_usage | 回数券使用記録 |
| マーケティング | appointment_user_points | ポイント流水 |
| マーケティング | appointment_gift_card | ギフトカード |
| マーケティング | appointment_user_referral | ユーザー紹介 |
| マーケティング | appointment_user_favorite | ユーザーお気に入り |
| ウォレット | appointment_user_wallet | ユーザーウォレット残高 |
| ウォレット | appointment_wallet_recharge | ウォレットチャージ記録 |
| ウォレット | appointment_wallet_txn | ウォレット取引流水 |
| ウォレット | appointment_wallet_transfer | ユーザー間振込記録（第19ラウンド） |
| ユーザー | appointment_user_notify_setting | メッセージ通知設定（第19ラウンド） |
| コンテンツ | appointment_banner | カルーセル画像 |
| コンテンツ | appointment_announcement | お知らせ |
| コンテンツ | appointment_platform_agreement | プラットフォーム規約 |
| コンテンツ | appointment_faq | よくある質問 |
| コンテンツ | appointment_feedback | 意見フィードバック |
| コンテンツ | appointment_moment | モーメンツ動態 |
| コンテンツ | appointment_notification | メッセージ通知 |
| 財務 | appointment_finance_transaction | 収支流水 |
| 財務 | appointment_technician_commission_config | 歩合設定 |
| 財務 | appointment_withdrawal_account | 出金アカウント |
| 財務 | appointment_withdrawal_config | 出金制限設定 |
| システム | appointment_admin_user | 管理ユーザー（構築済み） |
| システム | appointment_admin_role | ロール（構築済み） |
| システム | appointment_admin_permission | 権限（構築済み） |
| システム | appointment_admin_user_role | ユーザーロール関連（構築済み） |
| システム | appointment_admin_role_permission | ロール権限関連（構築済み） |
| システム | appointment_system_config | システム設定（構築済み） |
| システム | appointment_operation_log | 操作ログ（構築済み） |
| ユーザー | appointment_user_growth | 成長値流水（第20ラウンド） |
| ユーザー | appointment_growth_level | 成長レベル段階（第20ラウンド） |
| 注文 | appointment_invoice | 電子インボイス（第20ラウンド） |
| ユーザー | appointment_ticket | カスタマーサポートチケット（第20ラウンド） |
| マーケティング | appointment_referral_level2_reward | 2級返金記録（第20ラウンド） |
| ユーザー | appointment_invoice_title | インボイス宛名ライブラリ（第21ラウンド） |
| ユーザー | appointment_browse_history | 閲覧履歴（第21ラウンド） |
| マーケティング | appointment_full_reduction_activity | 満減キャンペーン（第22ラウンド） |
| スタッフ | appointment_technician_attendance | スタッフ勤怠（第22ラウンド） |
| システム | appointment_push_log | APPプッシュ記録（第22ラウンド） |
| 財務 | appointment_profit_sharing | 微信分账記録（第22ラウンド） |
| 注文 | appointment_order_status_log | 注文状態タイムライン（第23ラウンド） |
| ユーザー | appointment_user_health_profile | ユーザー健康カルテ（第23ラウンド） |
| マーケティング | appointment_lucky_wheel | ルーレット賞品定義（第23ラウンド） |
| マーケティング | appointment_wheel_record | ルーレット抽選記録（第23ラウンド） |
| マーケティング | appointment_seckill_activity | 秒殺キャンペーン（第24ラウンド） |
| システム | appointment_app_version | APPバージョン（第24ラウンド） |

### 補足リスト（docs/install.sql の 95 テーブルのうち上記に含まれないもの。完全かつ権威ある一覧は install.sql を参照）

| ドメイン | テーブル名 | 説明 |
|----|------|------|
| マーケティング | appointment_card_transfer | 回数券転贈 |
| ユーザー | appointment_check_in | チェックイン |
| コンテンツ | appointment_community_post | コミュニティ動態 |
| コンテンツ | appointment_community_comment | コミュニティコメント |
| スタッフ | appointment_exam | 試験 |
| スタッフ | appointment_exam_question | 試験問題 |
| スタッフ | appointment_exam_attempt | 試験答案 |
| システム | appointment_operation_log_detail | 操作ログ詳細 |
| 注文 | appointment_order_aftersale | 注文アフターサービス |
| マーケティング | appointment_points_exchange_goods | ポイント交換商品 |
| マーケティング | appointment_promotion | 拼团キャンペーン |
| マーケティング | appointment_promotion_participant | 拼团参加者 |
| 注文 | appointment_queue_number | 待ち番号呼び出し |
| サービス | appointment_service_package | サービスパッケージ |
| スタッフ | appointment_service_record | サービス記録 |
| コンテンツ | appointment_share | シェア記録 |
| 注文 | appointment_signature | 署名 |
| スタッフ | appointment_technician_tier_config | スタッフレベル設定 |
| スタッフ | appointment_training_course | 研修コース |
| スタッフ | appointment_training_progress | 研修進捗 |
| ユーザー | appointment_user_device | ユーザーデバイス |
| マーケティング | appointment_user_points_exchange | ポイント交換記録 |
| コンテンツ | appointment_video_post | 動画動態 |
| 注文 | appointment_waitlist | ウェイティングリスト |

## 外部サービス予約

| サービス | 用途 | 接続ポイント |
|------|------|--------|
| 微信オープンプラットフォーム | 微信ログイン/UnionID | WechatAuthService |
| 微信支払い | 支払い/返金/出金 | WechatPayService |
| 短信サービス事業者 | 認証コード/通知 | SmsService |
| 地図サービス | LBS位置情報/ナビ/距離計算 | MapService |
