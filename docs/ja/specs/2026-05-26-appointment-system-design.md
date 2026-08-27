# 予約サービスシステム設計仕様
> **Languages**: [中文](../../superpowers/specs/2026-05-26-appointment-system-design.md) · [English](../../en/specs/2026-05-26-appointment-system-design.md) · [한국어](../../ko/specs/2026-05-26-appointment-system-design.md) · [Русский](../../ru/specs/2026-05-26-appointment-system-design.md) · [Deutsch](../../de/specs/2026-05-26-appointment-system-design.md) · [Français](../../fr/specs/2026-05-26-appointment-system-design.md) · [Español](../../es/specs/2026-05-26-appointment-system-design.md) · [Português](../../pt/specs/2026-05-26-appointment-system-design.md) · [हिन्दी](../../hi/specs/2026-05-26-appointment-system-design.md) · [العربية](../../ar/specs/2026-05-26-appointment-system-design.md) · [বাংলা](../../bn/specs/2026-05-26-appointment-system-design.md) · [Bahasa Indonesia](../../id/specs/2026-05-26-appointment-system-design.md)

## 概要

三端予約サービスシステム：ユーザー端（微信小程序 + Flutter APP）+ スタッフワークベンチ（同APP内で身分切替）+ 管理バックエンド（PC Web）。

## アーキテクチャ決定

| 決定 | 案 |
|------|------|
| バックエンドアーキテクチャ | `admin/`（管理バックエンドAPI）+ `service/`（業務API）、双サービスで MySQL/Redis を共有 |
| ユーザー端小程序 | ネイティブ微信小程序 `apps/wechat/` |
| ユーザー端APP | Flutter `apps/flutter/`（iOS + Android） |
| ユーザー身分 | 統一アカウント、顧客/スタッフ身分を切替可能 |
| 小程序とAPPの関係 | 機能は完全に同一、プラットフォームの違いのみ |
| 管理バックエンドフロント | 既存の Flutter Web (`admin/apps/flutter/`) を拡張 |
| 管理バックエンドバックエンド | 既存の webman v2 (`admin/`) に業務モジュールを拡張 |
| 第三者サービス | 微信ログイン/支払い/短信/地図 — 連携案を予約 |

## システムアーキテクチャ図

```
┌──────────────────────────────────────────────────────────┐
│                      ユーザー端末層                        │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ 微信小程序        │  │ Flutter APP       │              │
│  │ apps/wechat/      │  │ apps/flutter/     │              │
│  │ (ネイティブWXML/WXSS) │  │ (iOS + Android)   │              │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │         機能は完全に同一 │                        │
│           └──────────┬──────────┘                        │
│                      │ 顧客身分 / スタッフ身分切替          │
├──────────────────────┼──────────────────────────────────┤
│              業務APIゲートウェイ                           │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ service/ API      │  │ admin/ API        │              │
│  │ (webman v2)       │  │ (webman v2)       │              │
│  │ ユーザー/注文/支払い/ │  │ 管理バックエンド接口 │              │
│  │ スタッフ/店舗/マーケ  │  │ (構築済み + 拡張)   │              │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │                      │                        │
│           └──────────┬───────────┘                        │
│                      │                                    │
├──────────────────────┼──────────────────────────────────┤
│                  データ層                                │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────────────┐    │
│  │ MySQL  │ │ Redis  │ │  ES    │ │ 第三者サービス    │    │
│  │ 8.0    │ │ キャッシュ/ │ │ 検索   │ │ 微信/短信/地図   │    │
│  │        │ │ レート制限/ │ │        │ │ (連携予約)     │    │
│  │        │ │ Session │ │        │ │                │    │
│  └────────┘ └────────┘ └────────┘ └────────────────┘    │
└──────────────────────────────────────────────────────────┘
```

## データベースのコアテーブル

全テーブルは `appointment_` プレフィックスを使用、BIGINT 非自動採番主キー（Snowflake生成）。機密フィールドは encryptable trait で暗号化/復号。

### ユーザーと身分ドメイン

| テーブル名 | 説明 | コアフィールド |
|------|------|----------|
| `appointment_user` | 統一ユーザーテーブル | phone, password, wx_openid, wx_unionid, avatar, nickname, user_type(customer/technician), status。technicianユーザーは顧客機能も同時に持ち、現在のアクティブ身分を自由に切替可能 |
| `appointment_user_address` | ユーザー住所 | user_id, contact_name, contact_phone, province, city, district, detail, is_default |
| `appointment_technician_profile` | スタッフカルテ | user_id, real_name, gender, id_card, id_card_front, id_card_back, avatar, rating, order_count, status(pending/approved/rejected), intro |
| `appointment_technician_schedule` | スタッフ排班 | technician_id, date, time_slots(JSON), status |
| `appointment_technician_service` | スタッフ提供可能なサービス項目 | technician_id, service_id |
| `appointment_technician_earnings` | スタッフ収益流水 | technician_id, order_id, type(commission/bonus/penalty), amount, status |
| `appointment_technician_withdrawal` | スタッフ出金記録 | technician_id, amount, actual_amount, commission_fee, account_info, status, reviewed_at |
| `appointment_technician_attendance` | スタッフ勤怠 | technician_id, date, check_in_at, check_out_at, clean_photo |
| `appointment_technician_member_note` | 会員カルテ | technician_id, user_id, content, written_at |

### サービスと商品ドメイン

| テーブル名 | 説明 | コアフィールド |
|------|------|----------|
| `appointment_service_category` | サービス分類 | name, icon, parent_id, sort, status |
| `appointment_service` | サービス項目 | category_id, name, description, cover_image, images(JSON), price, duration, sales_volume, specs(JSON), status |
| `appointment_product` | 商品 | category_id, name, cover_image, price, stock, sales_volume, type, status |
| `appointment_store` | 店舗 | name, address, lat, lng, phone, business_hours(JSON), images, status |

### 注文ドメイン

| テーブル名 | 説明 | コアフィールド |
|------|------|----------|
| `appointment_order` | 注文マスターテーブル | order_no, user_id, technician_id, store_id, total_amount, discount_amount, paid_amount, status, service_time, cancel_reason, remark |
| `appointment_order_item` | 注文明細 | order_id, service_id, product_id, type, name, price, quantity, spec_info |
| `appointment_order_payment` | 支払い記録 | order_id, pay_type(wechat), transaction_id, amount, status, paid_at |
| `appointment_order_refund` | 返金記録 | order_id, payment_id, refund_no, amount, ratio, reason, status |
| `appointment_order_review` | サービス評価 | order_id, user_id, technician_id, rating, content, images |
| `appointment_order_verification` | 核销記録 | order_id, code, verified_at, verified_by, location |

### マーケティングドメイン

| テーブル名 | 説明 | コアフィールド |
|------|------|----------|
| `appointment_coupon` | クーポン定義 | name, type, amount, min_amount, total_qty, remain_qty, start_at, end_at, status |
| `appointment_user_coupon` | ユーザークーポン | user_id, coupon_id, status(available/used/expired), used_at |
| `appointment_member_card` | 会員カード定義 | name, type(month/vip/times), price, duration_days, total_times, services(JSON) |
| `appointment_user_member_card` | ユーザー会員カード | user_id, card_id, start_at, end_at, total_times, used_times, status |
| `appointment_member_card_usage` | 回数券使用記録 | user_card_id, order_id, service_id, used_at |
| `appointment_user_points` | ポイント流水 | user_id, type(earn/use), points, source, order_id |
| `appointment_gift_card` | ギフトカード | code, type, amount_or_gift, status, used_by, used_at |
| `appointment_user_referral` | ユーザー紹介 | referrer_id, referred_user_id, reward_type, reward_amount, registered_at, first_order_at |

### コンテンツと通知ドメイン

| テーブル名 | 説明 | コアフィールド |
|------|------|----------|
| `appointment_banner` | カルーセル画像 | position, image, jump_type(url/detail/none), jump_value, sort, status |
| `appointment_announcement` | お知らせ | content, status, published_at |
| `appointment_platform_agreement` | プラットフォーム規約 | type(user_agreement/privacy_policy/service_agreement), title, content, version |
| `appointment_faq` | よくある質問 | title, content, sort |
| `appointment_feedback` | 意見フィードバック | user_id, content, images, handler_reply, status(pending/handled) |
| `appointment_moment` | モーメンツ動態 | content, images, published_at |
| `appointment_notification` | メッセージ通知 | user_id, type(order/system), title, content, is_read, created_at |

### 財務ドメイン（admin側）

| テーブル名 | 説明 | コアフィールド |
|------|------|----------|
| `appointment_finance_transaction` | 収支流水 | user_id, order_id, type, direction(income/expense), amount, actual_amount, commission, status |
| `appointment_technician_commission_config` | 歩合設定 | technician_id, commission_rate, settlement_cycle |
| `appointment_withdrawal_account` | 出金アカウント | user_id, type(wechat), account_name, account_no |
| `appointment_withdrawal_config` | 出金制限設定 | min_amount, reserve_amount, round_to_hundred |

## Service API モジュール

### 公開API（認証不要）
- **AuthController** — ログイン/登録/パスワード忘れ/ゲストモード/身分切替
- **CaptchaController** — 短信認証コード
- **WechatController** — 微信認証/ログイン/支払いコールバック
- **CommonController** — 規約テキスト/会社概要/バージョン情報

### ユーザーモジュール `user/`（認証必要）
- **ProfileController** — 個人情報/パスワード変更/携帯番号変更/アカウント削除
- **AddressController** — 配送先住所CRUD
- **FavoriteController** — お気に入り
- **FeedbackController** — 意見フィードバック
- **ReferralController** — 紹介/推薦ユーザーリスト

### スタッフモジュール `technician/`（スタッフ身分 + TechnicianAuth中間ウェア必要）
- **ProfileController** — スタッフカルテ/参入申請
- **ScheduleController** — 排班設定
- **OrderController** — 予約済み未核销/完了済み/スキャン核销
- **MemberController** — 自分の会員/会員カルテ
- **EarningsController** — 収益/在途資金
- **WithdrawalController** — 出金
- **AttendanceController** — 勤怠/衛生写真

### サービスモジュール `service/`
- **CategoryController** — サービス分類
- **ItemController** — サービス/商品リストと詳細
- **SearchController** — 検索
- **StoreController** — 店舗リスト/詳細

### 注文モジュール `order/`（認証必要）
- **CartController** — ショッピングカート
- **OrderController** — 注文/注文リスト/詳細/キャンセル
- **PaymentController** — 支払い/返金
- **VerificationController** — QRコード核销
- **ReviewController** — 評価

### マーケティングモジュール `marketing/`（認証必要）
- **CouponController** — クーポンリスト/受け取り/使用
- **MemberCardController** — 会員カード/回数券
- **PointsController** — ポイント
- **GiftCardController** — ギフトカード

### コンテンツモジュール `content/`
- **BannerController** — カルーセル画像
- **AnnouncementController** — お知らせ
- **NotificationController** — メッセージ通知

### LBSモジュール
- **LocationController** — 位置情報/都市切替/近くの店舗

### 共通能力 `common/`
- SnowflakeService — ID生成
- HashidsService — ID暗号化/復号
- EncryptionService — 機密データの暗号化/復号
- WechatPayService — 微信支払い（予約）
- WechatAuthService — 微信ログイン（予約）
- SmsService — 短信サービス（予約）
- MapService — 地図サービス（予約）

### ミドルウェア
- Auth — JWT認証（adminと erikwang2013/jwt-webman パッケージを共有）
- TechnicianAuth — スタッフ身分検証
- RateLimit — レート制限（adminと共有）

## Admin 管理バックエンド拡張

既存フレームワークの上にコントローラーを追加：

### スタッフ管理
- **TechnicianController** — スタッフリスト/検索/エクスポート/審査/排班管理/技術サービス項目設定/コース学習進捗

### ユーザー管理拡張
- **MemberController** — 会員リスト/レベル設定/消費統計

### 店舗管理
- **StoreController** — 店舗CRUD/有効無効

### サービス管理
- **ServiceController** — サービスリスト/CRUD/カード項目設計
- **ServiceCategoryController** — 分類管理
- **ProductController** — 商品リスト/CRUD

### モール管理
- **MallOrderController** — モール注文/発送/アフターサービス/評価
- **SalesStatsController** — 販売統計

### 注文管理
- **AppointmentOrderController** — 未使用注文/キャンセル/完了確認

### クーポンキャンペーン
- **CouponController** — クーポンCRUD/配布

### 財務管理
- **FinanceController** — 注文分账/収支流水
- **WithdrawalController** — スタッフ出金審査/完了
- **CommissionController** — 歩合設定/賞罰/残高照会
- **WithdrawalAccountController** — 出金アカウント管理
- **WithdrawalConfigController** — 出金制限設定

### コンテンツ管理
- **BannerController** — カルーセル画像CRUD
- **AnnouncementController** — お知らせCRUD
- **FaqController** — FAQ CRUD
- **FeedbackController** — 意見フィードバック処理
- **MomentController** — モーメンツ動態審査
- **AgreementController** — 規約編集（ユーザー規約/プライバシー規約/サービス規約）
- **AboutController** — 会社概要設定

### 設定
- **SystemMessageController** — システムメッセージ設定
- **AdminUserController** — サブアカウント管理（既存RBACベース）

### Dashboard 拡張
- リアルタイム統計カード：ユーザー数/注文総数/スタッフ数/サービス注文数
- 折れ線グラフ：注文量/金額/日別新規ユーザー/アクティビティ
- クイックナビゲーション：未処理モジュールボタン
- 站内メッセージ：新規注文通知/返金通知

## ユーザー端ページ構成

微信小程序と Flutter APP の機能は完全に同一。

### auth/ — 認証
- login — ログイン（携帯番号/認証コード/微信/ゲスト入口）
- register — 登録（携帯番号+認証コード+パスワード+紹介コード）
- forget-password — パスワード忘れ
- agreement — 規約閲覧

### home/ — ホーム
- index — ホーム（カルーセル画像+お知らせ+サービス分類+おすすめ）
- search — 検索ページ

### service/ — サービス
- list — サービスリスト（分類でフィルタ）
- detail — サービス詳細（基本情報+評価+今すぐ予約）
- product-list — 商品リスト

### order/ — 注文
- confirm — 注文確認（店舗/スタッフ/時間/クーポン/備考/規約）
- payment — 支払いページ
- payment-success — 支払い成功
- list — 全注文（状態Tabでフィルタ）
- detail — 注文詳細
- review — サービス評価
- verification — QRコード核销

### cart/ — ショッピングカート
- index — カートリスト

### technician/ — スタッフ（顧客視点）
- list — スタッフリスト（距離の近い順）
- detail — スタッフ詳細（評価/提供可能サービス/今すぐ予約）
- apply — スタッフ参入申請

### tech-work/ — スタッフワークベンチ（スタッフ身分）
- index — ワークベンチホーム（今日の注文/収入概要）
- schedule — 排班設定
- order-list — 自分の注文（予約済み未核销/完了済み）
- scan-verify — スキャン核销
- member-list — 自分の会員
- member-detail — 会員詳細/カルテ編集
- earnings — 自分の収益
- withdrawal — 出金
- transaction-list — 取引明細
- attendance — 勤怠/衛生写真アップロード
- training — 専門研修

### user/ — マイページ
- index — 個人情報（アバター/ニックネーム/会員カード/お気に入り/クーポン入口）
- settings — 設定（パスワード変更/携帯番号変更/規約/更新/アカウント削除/ログアウト）
- switch-role — 身分切替（顧客 ↔ スタッフ）

### marketing/ — マーケティング
- coupon-list — クーポンリスト
- member-card — 自分の会員カード
- points — 自分のポイント
- gift-card — 自分のギフトカード
- referral — 紹介（説明+QRコードポスター+推薦ユーザーリスト）

### その他のページ
- message/ — メッセージリスト/詳細
- store/list, store/detail — 店舗リスト（LBS順）/詳細（ナビ）
- other/about — 会社概要
- other/feedback — 意見フィードバック
- other/official-account — 公式アカウントをフォロー

### 共通コンポーネント
- navbar, tabbar, service-card, technician-card
- coupon-popup, lbs-selector, empty-state, loading

### 身分切替ロジック
- 顧客身分のボトムナビゲーション：ホーム / サービス / カート / 注文 / マイ
- スタッフ身分のボトムナビゲーション：ワークベンチ / 注文 / 会員 / 収益 / マイ
- 「マイ」ページに身分切替の入口を提供
- まだスタッフになっていないユーザーがスタッフ身分に切り替える際は参入申請ページへ誘導

## 購入フロー説明

システムには2種類の異なる購入フローがあります：

### サービス予約フロー（直接注文、カートなし）
- サービス項目詳細ページ → 注文確認（店舗/スタッフ/時間を選択）→ 支払い → 核销
- スタッフリソース独占：注文確認ページに入った時点でスタッフを3分間ロック
- 推拿、美容などのオフラインサービス項目に使用

### 商品購入フロー（カートモード）
- 商品リスト → カートに追加 → カートで確認 → 注文送信 → 支払い → 発送/受け取り
- 数量変更、商品削除に対応
- 実物商品またはカード券の販売に使用

## 主要ビジネスルール

### スタッフロックメカニズム
- 同じ時間に複数人が同時に1人のスタッフを予約することはできない
- ユーザーが注文確認ページに入ると、Redis SETNX でスタッフを3分間ロック
- 予約ページを離れるかタイムアウトで自動的にロック解放

### 返金ルール
| 条件 | 返金比率 |
|------|----------|
| 注文から15分以内 または 開始まで >6時間 | 100% |
| 開始まで ≤6時間 | 90% |
| 開始済みだがサービス未確認 | 80% |
| サービス開始確認後 | 0%（返金なし）|

### 割引ルール
- オフピーク時間帯（10-12時/17-18時/21:00以降）9割
- 30分以上前に予約 95割（クーポンと併用不可）

### スタッフ出金
- 毎月20日に出金可能、T+1営業日で到着
- 微信零錢への出金に対応
- 核销済み未精算の注文は、3日以内にシステムが自動確認
- 24時間以内に会員カルテを完了する必要あり、それ以外は歩合なし

### リピーター報酬
- 30日以内に同じスタッフへ2回目の消費 → ボーナスを記録
- サービス後に衛生写真をアップロード

### ポイントルール
- 1:100でギフトカードと交換（バックエンド設定可）
- 推薦ユーザーが登録成功し注文すると指定ポイントを獲得（バックエンド設定）
