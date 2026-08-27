# 予約サービスシステム
> **Languages**: [中文](../README.md) · [English](../en/README.md) · [한국어](../ko/README.md) · [Русский](../ru/README.md) · [Deutsch](../de/README.md) · [Français](../fr/README.md) · [Español](../es/README.md) · [Português](../pt/README.md) · [हिन्दी](../hi/README.md) · [العربية](../ar/README.md) · [বাংলা](../bn/README.md) · [Bahasa Indonesia](../id/README.md)

四端予約サービス管理プラットフォーム：ユーザー端は微信ミニプログラム + Flutter APP + HarmonyOS APP（同一アカウントで身分切替）、PC 管理バックエンド。

> **プロジェクトステータス**: すべて完了 ✅ | 143 コントローラー（service 69 / admin 74） | 87 モデル | 722 テスト（service 558 / admin 164） | 95 データテーブル | 388 ルート（service 227 / admin 161）

## プロジェクト紹介

<img src="diagrams/mascot.svg" alt="予約サービスシステムのマスコット——予約うさぎ（SVG アニメーション）" width="200" align="right">

**予約サービスシステム**は、生活サービス業界向けの四端予約管理プラットフォームです。ユーザー端は**微信ミニプログラム、Flutter APP、HarmonyOS APP** の三端をカバーし、同一アカウントで端末をまたいで自由に切り替えできます。「PC 管理バックエンド」と組み合わせて、「ユーザー予約 → スタッフ受注 → バックエンド運営」の全工程デジタルクローズドループを実現します。店舗予約、スタッフサービス、会員マーケティング、財務精算まで、1 つのシステムですべて完結します。

**ワンストップ予約体験**

ユーザー三端の体験は同一です。カレンダーで直感的に時間を選んで予約、クーポン/回数券/ポイントの割引、タイムセールとグループ購入の割引、微信/残高での支払い、注文ステータスは全行程追跡可能——変更、キャンセル、返金、アフターサービス、電子領収書までオンラインで完結。スタッフ端はワークベンチ、出退勤打刻、一括シフト設定、サービス核销と出金審査を提供し、運営効率が一目で分かります。

**フルチェーンマーケティング成長**

満額割引キャンペーン、タイムセール、グループ購入、クーポン贈与、ポイントモールとラッキーくじ、会員カード/成長レベル特典、二段階紹介報酬、リピーター特典など十数種のマーケティングツールを内蔵。メッセージ購読プッシュと APP プッシュを組み合わせ、事業者の新規顧客獲得・維持・リピート促進を継続的に支援します。

**エンタープライズ級のセキュリティとコンプライアンス**

自社開発のセキュリティコンポーネントを採用：JWT 認証、ID 難読化、31 種の攻撃検知、機密データの二重暗号化、価格のサーバーサイド検証、支払いコールバックの厳密な照合と冪等な重複防止。さらに微信公式の分配、プライバシーデータのエクスポート、アカウント削除にも対応し、コンプライアンス要件を満たします。

**成熟した技術基盤**

PHP 8.3 + webman 高性能常駐フレームワークをベースに、MySQL 8.0 + Redis + Elasticsearch で支えます。95 のデータテーブル、388 の API、285 の細粒度権限ポイント、722 の自動化テストがすべて成功。充実した中英アーキテクチャドキュメントとワンクリックインストールスクリプトを備え、すぐに使えて二次開発も容易です。

単店予約でも多店舗チェーンでも、予約サービスシステムは安定・安全・拡張可能な一体型ソリューションを提供します。

## プロジェクト構成

```
appointment-php/
├── admin/                     # 管理バックエンド (webman v2 + Flutter Web、独立デプロイ :8787)
│   ├── app/                   #   admin(バックエンドコントローラー)/api/model/middleware/process/view
│   ├── apps/                  #   Flutter Web バックエンド / HarmonyOS / 微信管理端
│   ├── config/                #   ルート/データベース/プロセス/プラグイン設定
│   ├── database/              #   バックアップスクリプト（テーブル構造とシードデータは docs/install.sql に統一）
│   ├── tests/                 #   PHPUnit（#[\Test] 属性スタイル）
│   └── start.php
├── service/                   # 業務APIサービス (webman v2、独立デプロイ :8787)
│   ├── app/                   #   api/user/technician/order/wallet/marketing/notification などのモジュール
│   ├── config/                #   ルート/データベース/プロセス/決済などの設定
│   ├── support/               #   Model 基底クラス（generateId）/Request/Response
│   ├── tests/                 #   PHPUnit
│   └── start.php
├── apps/                      # ユーザー端フロントエンドアプリ
│   ├── wechat/                #   微信ミニプログラム（ネイティブ）
│   ├── flutter/               #   Flutter APP（iOS + Android）
│   └── harmonyos/             #   HarmonyOS APP（鴻蒙ネイティブ）
└── docs/                      # プロジェクトドキュメント
    ├── API.md / FEATURES.md / STRUCTURE.md / install.sql / README.md ...
    └── diagrams/              #   アーキテクチャ/フローチャート（SVG + mermaid）
```

## クイックスタート

### 環境要件

- PHP 8.3+
- MySQL 8.0+
- Redis
- Composer

### Web インストールウィザード（推奨）

```bash
cd admin/
cp .env.example .env
composer install
php start.php start -d
```

ブラウザで `http://localhost:8787/install` を開き、案内に従ってデータベースと管理者アカウントを入力すればインストール完了です。

### 手動インストール

```bash
# 1. 依存関係のインストール
cd service/ && cp .env.example .env && composer install
cd ../admin/ && cp .env.example .env && composer install

# 2. データベースを一括インポート（全 95 テーブル + 権限/設定シードを含む）
mysql -u root -p < docs/install.sql

# 3. サービスの起動
cd service/ && php start.php start -d   # 業務API → :8787
cd ../admin/ && php start.php start -d  # 管理バックエンド → :8787
```

### Docker デプロイ

```bash
cd admin/ && cp .env.docker .env && docker-compose up -d
cd ../service/ && cp .env.docker .env && docker-compose up -d
```

## 技術スタック

| レイヤー | 技術 | 説明 |
|------|------|------|
| バックエンドフレームワーク | webman v2 (PHP 8.3+) | 高性能常駐メモリ HTTP サービス |
| データベース | MySQL 8.0 | テーブルプレフィックス `appointment_` |
| キャッシュ | Redis | キャッシュ/レート制限/Session/キュー |
| 検索 | Elasticsearch | 全文検索（webman-scout 経由） |
| 管理バックエンドフロント | Flutter Web | PC 管理バックエンドスタイル |
| ユーザー端APP | Flutter | iOS + Android |
| ユーザー端ミニプログラム | ネイティブ微信ミニプログラム | WXML/WXSS/JS |
| ユーザー端鴻蒙APP | HarmonyOS ArkTS | ネイティブ @ohos.net.http |
| ID生成 | erikwang2013/snowflake-php | BIGINT 非自動採番主キー |
| API ID暗号化 | erikwang2013/hashids | 外部に実IDを隠蔽 |
| JWT認証 | erikwang2013/jwt-webman | Bearer Token |
| 機密データ暗号化 | erikwang2013/encryption + encryptable | API + DB 二重暗号化 |
| セキュリティ防御 | erikwang2013/security-php | 31 種の攻撃検知 |
| 操作検証 | erikwang2013/poster-php | 敏感操作のランダム検証 |
| 国旗 | erikwang2013/season | 国旗アイコン |
| ES同期 | erikwang2013/webman-scout | モデルの自動同期 |

## システムアーキテクチャ

<img src="diagrams/ja-architecture.svg" alt="ja-architecture.svg" width="100%">

## コアフロー

### サービス予約フロー

<img src="diagrams/ja-appointment-flow.svg" alt="ja-appointment-flow.svg" width="100%">

### 支払いと返金フロー

<img src="diagrams/ja-payment-refund.svg" alt="ja-payment-refund.svg" width="100%">

## 注文ライフサイクル

<img src="diagrams/ja-order-lifecycle.svg" alt="ja-order-lifecycle.svg" width="100%">

## セキュリティアーキテクチャ

### 多層防御七層体制

<img src="diagrams/ja-security-defense.svg" alt="ja-security-defense.svg" width="100%">

> さらに詳しい図解：[フローチャート](diagrams/FLOWCHART.md)（スタッフ出金/身分切替を含む）| [機能マインドマップ](diagrams/FUNCTION-DIAGRAM.md) | [全ライフサイクル](diagrams/LIFECYCLE-DIAGRAM.md) | [完全なセキュリティアーキテクチャ](diagrams/SECURITY-ARCHITECTURE.md)

## コア機能ハイライト（第 6-24 ラウンド）

| 機能 | 説明 |
|------|------|
| チャージウォレット | user_wallet / wallet_recharge / wallet_txn テーブル；残高+取引履歴、微信決済チャージ（コールバック R プレフィックス注文番号）、注文の残高払い（pay_channel=balance）、微信/残高返金の残高自動回充 |
| 管理バックエンド UI 完全整備 | Flutter Web 20 ページ：dashboard/ユーザー/ロール/設定/ログ/核销/シフト/サービス/スタッフ/注文/クーポン/会員/回数券/お知らせ/FAQ/出金/評価/レポート/マイページ |
| ミニプログラム購読メッセージ | 注文 3 シナリオの購読プッシュ（支払い成功/返金到着/核销成功）；push_sent_at 冪等；テンプレート未設定時は自動でサイト内通知にダウングレード |
| スタッフ出金 | 管理端審査；金額 ≥500 は二段階承認（店長→財務）；ステートマシン pending→approved→completed（rejected/failed） |
| 回数券核销クローズドループ | マイ回数券で used_up/expired をリアルタイム計算；核销は Redis NX 冪等 + 行ロックで回数減算、completed 注文 + OrderItem + OrderPayment(pay_type='card') を直接作成 |
| スタッフワークベンチ | 今日のタスク/完了記録/開始・完了（行ロック + ステートマシンガード + 冪等、完了後にサイト内通知）；ミニプログラム tech-work 三タブ |
| クーポン割引 | PriceCalculator：applyCoupon は読み取り専用で金額計算 / consume は支払い時に used / restoreCouponAndCard は返金時に冪等で返却；fixed/percent + min_amount 閾値 |
| ギフトカード | redeem 時 cash タイプはウォレットにチャージ（行ロックで二重入金防止、WalletTxn type='gift_card'）、gift タイプはマークのみ |
| ポイント体系 | チェックインでポイント獲得；核销消費で floor(paid×1) ポイント獲得（order_id 冪等、balance スナップショット）；返金は比例回収；明細ページング + type/source フィルタ |
| 会員管理 | appointment_user.member_level カラム（マイグレーション 000008）；管理端会員カードの完全 CRUD（権限 365-369） |
| ミニプログラム注文チェーン | サービス詳細 → 注文確認（クーポン選択/閾値グレーアウト/クライアント側予定金額）→ POST /order → 微信/残高払い；ミニプログラム全 20 ページ |
| グループ購入クローズドループ | join の重複参加は 422 + 満員ロック + 期限切れの遅延クローズ；成团注文は store に promotion_id を渡してグループ価格（discount_percent）で注文、クーポン/回数券/ポイントの併用は無効、未成团は注文を自動キャンセルしスタッフロックを解放（旧 FLASH_SALE プロモーションチャネルは廃止、タイムセールは独立チャネル） |
| 店長ワークベンチ | service /api/store-manager 4 API（overview/orders/technicians/revenue）store_id 強制分離（店舗なしは 403）；admin 店舗ワークベンチ概要 + 注文 store_id フィルタ + Flutter ページ + 権限 372 |
| 紹介報酬 | 被紹介者の初回注文 completed 後に paid_amount × reward_rate（システム設定、デフォルト 0.05）を紹介者へ報酬としてウォレット入金（WalletTxn referral_reward）；行ロック+空判定+初回注文再確認の三重冪等；earnings 明細 + admin 記録閲覧（権限 379） |
| ポイント交換モール | 交換商品/交換記録の二テーブル；交換 API は Redis NX + 行ロックで超過交換防止 + uk_user_goods で同一ユーザーは 1 回限り；coupon 発行 / wallet 入金 / gift_card カードキー の三結果；admin CRUD + 公開/非公開 + 記録（権限 373-378） |
| 予約変更 | POST /api/order/reschedule/{id} 同一スタッフで時間変更；pending/paid/confirmed かつ元サービス開始まで ≥6h のみ変更可；order_lock + 新時間帯のスタッフロック SETNX(180s) で並行過剰予約防止 + B2 シフト重複チェック；appointment_order_reschedule + SCENE_RESCHEDULE 購読メッセージに記録 |
| クーポン贈与 | 8 桁の一意な贈与コード（uk_code で二重防止、7 日有効）；claim の乱用防止：Redis NX ロック + 行ロック再確認で二重引き換え防止、uk_user_coupon で贈与は 1 回限り、譲受済みクーポンは再贈与不可、自分への受領不可；遅延期限切れで元クーポンを復元 |
| ポイント期限切れ | expires_at（デフォルト 365 日、設定 points.expiry_days）；PointsExpiryTimer 60 秒ごとのカーソルスキャンで type=expire の負値を減算（三重冪等）+ 集約サイト内通知；期限切れポイントは現金化/交換不可 |
| スタッフレベル自動判定 | TierRatingService が注文数+平均点をリアルタイム集計して profile に書き戻し、tier_config に従って高い方からマッチング；昇格のみで降格なし（allowDowngrade は手動再評価用）；変更は appointment_technician_tier_log + サイト内通知に記録；admin ログ閲覧（権限 380） |
| タイムセール注文クローズドループ | /api/seckill キャンペーン + buy 冪等/並行防止、注文時に seckill_id を注入して store() を再利用、在庫はトランザクション内の行ロックで一律減算（タイムセール価格 = seckill_price は DB を基準）、売り切れは 422「売り切れました」、キャンセルで在庫は戻さない；旧 promotion flash_sale チャネルは廃止 |
| サービス開始前リマインダー | ServiceReminderTimer 60 秒ごとに 1 時間以内開始の confirmed/serving 注文をスキャン → SCENE_REMINDER 購読メッセージ+サイト内通知（order_id+type で重複防止、三重冪等）；テンプレート未設定時は自動でサイト内通知にダウングレード |
| 期限リマインダー | ExpiryReminderTimer 6 時間ごとに 3 日以内期限の会員カード/クーポンをスキャン → type=card_expiry/coupon_expiry + SCENE_EXPIRY 購読メッセージ（order_id で送信元を記録し重複防止） |
| スタッフの評価返信 | POST /api/technician/review/reply/{order_id}：本人以外は 404、重複返信は 422、返信成功でユーザーにサイト内通知；appointment_order_review に replied_at 追加；admin 返信詳細（権限 381） |
| チャージ到着通知 | 微信チャージコールバックのトランザクション内でサイト内通知 type='wallet_recharge' を作成（コールバック冪等を再利用、同一トランザクションで原子的にコミット、失敗してもメインフローをブロックしない） |
| 残高送金 | POST /api/wallet/transfer ユーザー間送金：金額 0.01-1000/件 + 1 日 5000 上限；Redis NX ロック + 双方ウォレットの行ロック（user_id 昇順でデッドロック防止）+ client_token 24h 冪等；WalletTxn transfer_out/transfer_in の二重取引履歴に balance_after スナップショット；受取人にサイト内通知 type='balance_received' |
| ポイント贈与 | POST /api/user/points/transfer ユーザー間贈与：1-10000 ポイント + 1 日累計 10000 上限；Redis NX ロック + 双方の最終取引履歴 lockForUpdate（昇順でデッドロック防止）+ ロック内再確認；送信側 consume/受取側 earn の二重取引履歴（受取側は expires_at 付きで通常期限切れ）；受取人にサイト内通知 type='points_received' |
| 評価追記 | POST /api/order/review/{order_id}/append：本人以外 404/重複 422/空内容 422/非 completed 422、成功でスタッフにサイト内通知 type='review_append'；appointment_order_review に append_content/append_images(JSON)/append_at 追加；あわせて登録ユーザーによる評価提出ルートを補完（元 store はルート未登録で到達不能）し潜伏 TypeError を修正 |
| ユーザー端物流追跡 | GET /api/order/logistics/{id}：本人の product 注文のみ（本人以外/非商品/未発送は 404）；order.remark JSON を読み取り（shipping_company/tracking_no/shipped_at、admin 発送時に書き込み）；受取人携帯番号はマスク表示 138\*\*\*\*5678 |
| 通知設定 | appointment_user_notify_setting テーブル（uk_user_type 一意キー、行なし=デフォルト全オン）；GET/PUT /api/user/notify-settings；5 種のスイッチ service_reminder/card_expiry/points_expiry/marketing/system（system は常時オンでオフ不可）；notifySettingEnabled で 3 タイマー + 購読イベントを制御、オフ時はサイト内通知と購読メッセージの両方をスキップ |
| 予約カレンダー | GET /api/calendar/technician/{id}（月表示）+ /day（日表示）：time_slots JSON を時間枠に展開、appointment_order の予約済み時間帯を除外；店舗シフトを可視化して時間選択 |
| ユーザー成長レベル | appointment_user_growth + appointment_growth_level（青銅 0/銀 100/金 500/プラチナ 2000/ダイヤ 5000）；チェックイン+10、評価+20、消費 1 元ごと 1 ポイント（既存ステータス再確認を再利用し自然に冪等）；GET /api/growth（概要/records/levels 公開ランク） |
| 電子領収書 | POST/GET /api/invoices（申請/一覧/詳細）：uk_order_type(order_id,order_type) で重複申請防止、金額はサーバー側で導出；admin 発行/却下（権限 382-384） |
| カスタマーサポートチケット | POST/GET /api/tickets + /{id}/close：ユーザー提出/一覧/詳細/クローズ；admin 返信（権限 385/387） |
| 多段階紹介-二段階報酬 | 注文支払い後、一階級紹介者の紹介者に paid×level2_rate（設定 0.02）を付与：トランザクション行ロック + uk_order_referred 冪等で重複付与防止；WalletTxn TYPE_REFERRAL_LEVEL2；admin 記録閲覧（権限 386） |
| 成長レベル特典 | GrowthLevel.benefits の中身を実装：注文時にレベルに応じた discount_rate 割引（標準注文のみ、クーポン/回数券→レベル割引は併用可、割引額は discount_amount + 備考で追跡可能、下限保護で 0 に切り捨て）；支払いコールバックで成長値 floor(paid×points_multiplier) 倍率を入金（支払い時点のランクを採用、昇格はしない） |
| 領収書名義管理 | appointment_invoice_title よく使う名義ライブラリ：保存/編集/削除/デフォルト（先頭が自動デフォルト、デフォルト削除は自動移行、デフォルト設定はトランザクションでクリア）；申請時に title_id を指定可能、手入力も後方互換で保持 |
| チケット満足度 | クローズ時に 1-5 で評価可能（範囲外は 422、未提供は NULL 互換）；admin 満足度集計：平均点/1-5 星分布/評価済み・未評価カウント（権限 388） |
| 評価画像審査 | admin ReviewAuditController：画像付き評価一覧（JSON_LENGTH フィルタ + ユーザー/スタッフ名 join）、非表示/復元（hide は visible のみ、restore は hidden のみ、422 双方向チェック）；非表示後はスタッフの評価一覧から自動的に見えなくなる（権限 389-391） |
| 閲覧履歴 | appointment_browse_history（uk_user_item で重複閲覧は viewed_at 更新のみ）：サービス詳細に接続して記録（try/catch でメインフローをブロックしない、未ログインはスキップ）；一覧はサービス情報 + hashid を join；1 件削除/全削除は本人のみ |

> 第 8 ラウンドの運用系修正：Poster::verify の潜伏 fatal 12 箇所を除去；DashboardController の統計を Capsule Manager クエリに変更。
>
> Round-15 補足：ポイント回補（キャンセル/返金時に points_offset ポイントを返却、refundOffsetPoints 5 接続点で冪等）；PromotionParticipant のステータスを整数定数に変更（厳密モードで join 1366 破損を修正）。
>
> Round-16 補足：ポイント交換（PointsExchangeController、タイプ consume/source=exchange）；グループ購入注文（appointment_order に promotion_id/participant_id カラム追加）；紹介報酬（ReferralRewardService を WorkController::complete に接続）。
>
> Round-17 補足：予約変更（appointment_order_reschedule + reschedule API）；クーポン贈与（appointment_user_coupon_transfer + transfer/claim/transfers）；ポイント期限切れ（expires_at + PointsExpiryTimer プロセス）；スタッフレベル自動判定（TierRatingService + appointment_technician_tier_log、権限 380）。
>
> Round-17 修正：AutoCancelTimer の通知挿入を \support\Model::generateId() に変更（元は存在しない Snowflake::generate() を呼び、自動キャンセル通知が静かに失敗していた）。
>
> Round-18 補足：タイムセール注文（store() が flash_sale タイムセール価格をサポート）；サービス開始前リマインダー（ServiceReminderTimer + SCENE_REMINDER）；会員カード/クーポン期限リマインダー（ExpiryReminderTimer + SCENE_EXPIRY）；スタッフの評価返信（review reply API + replied_at カラム + 権限 381）；チャージ到着通知（コールバックトランザクション内 type='wallet_recharge'）。
>
> Round-19 補足：残高送金（appointment_wallet_transfer + WalletTransferController、権限内二重行ロック + client_token 冪等）；ポイント贈与（appointment_user_points_transfer + PointsTransferController、1 日上限 + 双方向取引履歴）；評価追記（appointment_order_review append 三カラム + append API + store ルート補完）；ユーザー端物流追跡（logistics API + remark JSON 解析 + 携帯番号マスク）；通知設定（appointment_user_notify_setting + NotifySettingController + 3 タイマー制御）。
>
> Round-20 補足：予約カレンダー（CalendarController 月/日表示 + 予約済み除外）；ユーザー成長レベル（appointment_user_growth + appointment_growth_level 5 ランク + チェックイン/評価/消費接続）；電子領収書（appointment_invoice + uk_order_type 重複防止 + バックエンド発行/却下、権限 382-384）；カスタマーサポートチケット（appointment_ticket 提出/一覧/詳細/クローズ + バックエンド返信、権限 385/387）；多段階紹介-二段階報酬（payLevel2Reward トランザクション行ロック + uk_order_referred 冪等、権限 386）。
>
> Round-21 補足：成長レベル特典の実装（注文 discount_rate 割引 + 支払い points_multiplier ポイント倍率、マイグレーションシード 5 ランク benefits）；領収書名義管理（appointment_invoice_title 名義ライブラリ + 申請 title_id 連携）；チケット満足度（クローズ評価 rating/rated_at + admin 集計統計、権限 388）；評価画像審査（ReviewAuditController 非表示/復元、権限 389-391）；ユーザー閲覧履歴（appointment_browse_history + 詳細接続 + 一覧/削除/全削除）。
>
> Round-22 補足：満額割引キャンペーン（appointment_full_reduction 自動値引き + 閾値チェック、権限 396-400）；ICS カレンダーエクスポート（RFC5545 マイ予約）；スタッフ打刻勤怠（appointment_technician_attendance 出退勤打刻 + 遅刻マーク + admin 統計、権限 392-393）；APP プッシュサービス（設定駆動抽象 + 5 箇所のイベント接続、appointment_push_log）；微信公式分配（appointment_profit_sharing_log 設定駆動 + ダウングレード、権限 394）；プライバシーコンプライアンス（データエクスポート + アカウント削除 72h ステートマシン close_status）。
>
> Round-23 補足：ユーザー健康プロフィール（appointment_user_health_profile）；ウォレット支払いパスワード（appointment_user_wallet pay_password 設定/検証）；スタッフ一括シフト（batch インポート + 重複時間帯検出）；注文ステータスタイムライン（appointment_order_status_log 8 ステータス埋め込み + ユーザー端/バックエンド表示）；ポイントラッキーくじ（appointment_lucky_wheel + appointment_wheel_record 重み付き抽選、権限 401-406）；ポイント有効期限（points.expiry_days 設定 + 新規 earn 取引履歴に expires_at）。
>
> Round-24 補足：ゲストモード（/api/guest/* 未ログイン読み取り専用閲覧 + Redis キャッシュ）；タイムセール（appointment_seckill_activity + Redis NX 行ロックでの購入 + appointment_order.seckill_id 注入注文、権限 407-411/420）；APP バージョン管理と更新検知（appointment_app_version + /api/app/version、権限 416-419）；リピーター特典（30 日以内の二回目消費ボーナス type=return_customer、権限 412-414）；シフト CSV エクスポート（UTF-8 BOM + 時間枠明細、権限 415）。
>
> 2026-08-26 セキュリティ強化：注文 API の注文項目価格は一律データベース記録を基準（クライアント価格は信頼しない、不明な target_type は 422、target_id は hashid 必須）、グループ購入/タイムセール価格も同様に DB 基準；タイムセール在庫は一律 /api/order store() のトランザクション内行ロックで減算（SeckillController::buy は予約減算せず、Redis キャンペーンロック + client_token 冪等を維持）；スタッフ出金申請時に在途予約、承認送金前に再確認、並行承認の二重送金防止；微信支払いコールバックの total_fee と注文支払額を厳密照合、支付宝コールバックログはマスク；/install インストール成功時に .install.lock 二重チェックで再インストール防止；依存バージョン収束（webman-scout 2.0.5 / opensearch-php ^2.6 / dompdf、security-php、webman-database を正確にロック）；両アプリの phpstan.neon 修正で実行可能（php -d memory_limit=2G）。

## ドキュメントナビゲーション

| ドキュメント | 説明 |
|------|------|
| [アーキテクチャ説明](ARCHITECTURE.md) | システムアーキテクチャ、三端の関係、技術コンポーネント、データフロー |
| [機能説明](FEATURES.md) | ユーザー端/スタッフ端/管理バックエンドの完全な機能リスト |
| [アーキテクチャ設計](ARCHITECTURE-DESIGN.md) | 階層設計、ミドルウェアチェーン、データベース設計、セキュリティ設計 |
| [機能設計](FEATURE-DESIGN.md) | コア業務フロー、業務ルール、ステートマシン、返金ルール |
| [APIドキュメント](API.md) | 業務API + 管理バックエンドAPI、リクエスト/レスポンス例 + OpenAPI エンドポイント |
| [インストール説明](INSTALL.md) | 環境要件、Docker デプロイ、環境変数、サードパーティ設定、よくある質問 |
| [使用説明](USAGE.md) | 管理バックエンド設定、ユーザー端/スタッフ端操作、返金ルール（API は API.md 参照） |
| [プロジェクト構成](STRUCTURE.md) | 完全なディレクトリレイアウト、ミドルウェア実行チェーン、データベーステーブルリスト |
| [テストレポート](TEST-REPORT.md) | 全量テストカバレッジ監査（558 ケース / 2508 アサーション） |
| [設計仕様](superpowers/specs/2026-05-26-appointment-system-design.md) | システム設計仕様 |
| [実装計画](superpowers/plans/2026-05-26-appointment-system-plan.md) | 段階的実装計画 |

## プロジェクト支援 / Support

このプロジェクトが役に立ったなら、ぜひ支援してください！ご支援ありがとうございます :heart:

If this project helps you, your support is welcome and appreciated!

<table>
  <tr>
    <td align="center" width="50%">
      <img src="../weixinpay.png" alt="微信支付 / WeChat Pay" width="130" height="130"><br>
      <b>微信決済</b><br>WeChat Pay
    </td>
    <td align="center" width="50%">
      <img src="../alipay.png" alt="支付宝 / Alipay" width="130" height="130"><br>
      <b>支付宝</b><br>Alipay
    </td>
  </tr>
</table>

### グローバル銀行振込 / Global Bank Transfer

グローバル銀行振込による投げ銭にも対応しています（香港ドル / 人民元 / 米ドル / その他通貨）。ご厚意に感謝します :heart:

Global bank transfer donations are welcome (HKD / CNY / USD / other currencies). Thank you for your generosity!

| 項目 Item | 情報 Details |
|-----------|-------------|
| 受取人名 Beneficiary Name | WANG KEXUN |
| 受取口座番号 Account Number | 881015918251 |
| 受取銀行 Bank | ZA Bank Limited（SWIFT Code：AABLHKHHXXX、銀行番号 Bank Code：387） |
| 銀行住所 Bank Address | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **クロスボーダー送金の中継銀行（必要な場合）/ Intermediary Bank (if required)**
> これはクロスボーダー送金の中継銀行（転送銀行）の情報であり、受取銀行の情報ではありません。送金銀行に提供が必要かどうかをご確認ください。
> Note: this is intermediary bank information, not the receiving bank. Please check with your remitting bank whether it is required.
>
> - 香港ドル・人民元・米ドルの送金（For HKD / CNY / USD）：**Citibank N.A. Hong Kong** — SWIFT Code：CITIHKHXXXX、銀行番号 Bank Code：006、支店名 Branch：Hong Kong Branch、支店番号 Branch Code：391、住所 Address：Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - その他通貨の送金（For other currencies）：**The Bank of New York Mellon** — SWIFT Code：IRVTUS3NXXX、住所 Address：240 Greenwich Street, New York, United States

## 著作権

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
