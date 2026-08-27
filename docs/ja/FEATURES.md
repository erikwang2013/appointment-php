# 機能説明
> **Languages**: [中文](../FEATURES.md) · [English](../en/FEATURES.md) · [한국어](../ko/FEATURES.md) · [Русский](../ru/FEATURES.md) · [Deutsch](../de/FEATURES.md) · [Français](../fr/FEATURES.md) · [Español](../es/FEATURES.md) · [Português](../pt/FEATURES.md) · [हिन्दी](../hi/FEATURES.md) · [العربية](../ar/FEATURES.md) · [বাংলা](../bn/FEATURES.md) · [Bahasa Indonesia](../id/FEATURES.md)

> **プロジェクトステータス**: すべて完了 ✅ | 109 コントローラー | 103 モデル | 344 テスト（service 240 / admin 104） | WebSocket | 支払いコールバック | 呼び出し番号 | 試験 | コミュニティ

## 一、ユーザー端（微信ミニプログラム + Flutter APP）

ユーザー端ミニプログラムと APP の機能は完全に同一です。統一アカウントで顧客/スタッフの身分切替をサポートします。

### 1. 認証

| 機能 | 説明 |
|------|------|
| 携帯番号登録 | 携帯番号+認証コード+パスワード+確認パスワード、紹介コード対応 |
| パスワードログイン | 登録済み携帯番号+パスワード |
| 認証コードログイン | 登録済み携帯番号+認証コード |
| 微信ログイン | 微信認可ログイン、初回は携帯番号のバインドが必要 |
| ゲストモード | 閲覧は可能、注文は不可（注文には登録が必要） |
| パスワード忘れ | 認証コードでパスワード変更 |
| ユーザー規約/プライバシー規約 | 管理バックエンドで編集可、登録時に表示 |

### 2. ホームページ

| 機能 | 説明 |
|------|------|
| LBS位置情報 | 所在エリアを特定し、そのエリアのサービスを表示、都市切替対応 |
| バナー | 自動スライド、管理バックエンドでジャンプ先を設定（Web/詳細/操作なし） |
| お知らせ | スクロール表示、タップで一覧表示、管理バックエンドで追加 |
| サービスカテゴリ | 画像/名称/価格/売上、タップで詳細へ |
| 新規ユーザークーポン | 登録時に自動取得 |

### 3. サービス項目

| 機能 | 説明 |
|------|------|
| 基本情報 | 画像/名称/価格/売上/仕様/サービス時間/項目詳細 |
| ユーザー評価 | 評価内容の表示、さらに見る対応 |
| 予約サービス | 注文確認ページへ |
| 店舗選択 | 来店サービスの店舗住所（ナビ）/営業時間/電話番号 |
| スタッフ選択 | スタッフ名/アバター/評価 |
| サービス時間 | 予約時間帯の選択 |
| オフピーク9割 | 10-12時/17-18時/21:00以降 |
| 事前予約95割 | 30分前まで、クーポンとの併用不可 |
| クーポン | 利用可能金額の表示、使用/不使用 |
| 備考 | サービス要望メモ（文字数制限） |
| サービス規約 | 提出前に閲覧・確認 |

### 4. 商品検索とカート

| 機能 | 説明 |
|------|------|
| 商品検索 | 名称検索 |
| カテゴリ絞り込み | 分類で検索 |
| 商品詳細 | 購入可能数/お気に入り/共有/カート追加/すぐ購入 |
| カート | 選択/削除/数量変更 |

### 5. 注文

| 機能 | 説明 |
|------|------|
| 全注文 | ステータス別 Tab で表示 |
| 未支払い | 表示/支払い |
| 未発送/店舗受取 | 発送催促/注文キャンセル/表示 |
| 未受取 | 物流情報/受取確認 |
| 未評価 | 注文詳細/テキスト+画像評価 |
| 完了 | 注文情報の表示 |
| 返金ルール | 注文から15分以内または>6hは100%返金 / <6hは90% / 開始後は80% / 確認後は返金なし |

### 6. スタッフ（顧客視点）

| 機能 | 説明 |
|------|------|
| スタッフ一覧 | 距離の近い順/アバター/名前/注文数/評価/お気に入り/距離/予約可能時間/すぐ予約 |
| スタッフ詳細 | 画像/名前/距離/注文/評価/お気に入り/対応サービス一覧 |
| スタッフ入驻 | 情報を記入してスタッフを申請、スタッフ端 APP をダウンロード |

### 7. スタッフワークベンチ（スタッフ身分切替後）

| 機能 | 説明 |
|------|------|
| 今日の概要 | 今日の注文/収入の概観 |
| シフト設定 | 日単位で予約可能な時間帯を設定 |
| マイ注文 | 予約済み未核销/完了 |
| QR核销 | ユーザーのQRコードをスキャンして回数を核销 |
| 会員管理 | 対応済み会員一覧/受講データ/回数券/プロフィール編集 |
| 収益管理 | 今日の収入/精算中/ウォレット残高 |
| 在途資金 | 核销済み未精算、3日で自動確認 |
| 出金 | 毎月20日、T+1で微信零錢へ到着；管理端審査、金額≥500 は二段階承認（店長→財務）；申請時に残高在途予約、承認送金前に再確認、並行承認の二重送金防止（2026-08-26 強化） |
| 勤怠 | 出勤/退勤/衛生写真のアップロード |
| リピーター特典 | 30日以内の2回目消費でボーナス記録 |
| 専門研修 | 動画コース/図文コース |
| 今日のタスク | WorkController today：今日のToDoをリアルタイム取得 |
| 完了記録 | WorkController records：履歴完了記録 |
| サービス開始/完了 | WorkController start/complete：行ロック+ステートマシンガード+冪等、完了後にサイト内通知を自動書き込み |
| ミニプログラムスタッフワークベンチ | tech-work 三Tab：QR核销/今日のタスク/完了記録 |

### 8. マイページ

| 機能 | 説明 |
|------|------|
| 個人情報 | アバター/ニックネーム/携帯番号 |
| 身分切替 | 顧客 ↔ スタッフ |
| メッセージ通知 | サイト内通知（appointment_notification）；メッセージセンターページ：ページング/プルリフレッシュ/既読ハイライト/既読マーク/全既読 |
| マイ会員カード | 月額カード/VIP年額カード/回数券（期限/回数/使用済み/残り） |
| マイポイント | 獲得記録/利用可能ポイント/利用記録（1:100でギフトカード交換）；チェックイン/消費でポイント獲得、返金は比例回収、明細ページング+type/sourceフィルタ |
| マイギフトカード | 現金カード/実物ギフト；cash タイプは交換でウォレットに直接チャージ |
| クーポン | 取得済み利用可能/使用済み/期限切れ |
| マイお気に入り | お気に入りのサービス項目 |
| 公式アカウントフォロー | QRコードポップアップ、長押し保存 |
| ユーザー紹介 | 紹介説明/QRコードポスター/紹介ユーザー一覧/ポイント報酬 |
| 意見フィードバック | テキスト+画像提出、24h返信 |
| 私たちについて | LOGO/紹介/カスタマー電話/公式サイト/メール |

### 9. 設定

| 機能 | 説明 |
|------|------|
| パスワード変更 | 現在のパスワード+新パスワード+確認パスワード |
| 携帯番号変更 | 現在の携帯認証コード+新携帯認証コード |
| ユーザー規約 | テキスト表示、バックエンドで編集可 |
| プライバシー規約 | テキスト表示、バックエンドで編集可 |
| 更新チェック | バージョン番号+更新 |
| アカウント削除 | 削除説明+確認操作 |
| ログアウト | ログイン状態のクリア |

### 10. チャージウォレット（第6ラウンド）

| 機能 | 説明 |
|------|------|
| ウォレット残高 | GET /api/wallet 残高+取引履歴（user_wallet/wallet_recharge/wallet_txn テーブル） |
| チャージ | POST /api/wallet/recharge チャージ注文作成；POST /api/wallet/recharge/{id}/pay 微信決済チャージ、コールバックは R プレフィックス注文番号を使用 |
| 残高払い | 注文支払いチャネル pay_channel=balance |
| 返金回充 | 微信/残高返金は残高へ自動回充（refundToBalance / creditRefundToWallet） |

### 11. 購読メッセージ（第6+8ラウンド）

| 機能 | 説明 |
|------|------|
| 購読シナリオ | 注文イベント 3 シナリオ：支払い成功 / 返金到着 / 核销成功 |
| 冪等 | push_sent_at マークで重複プッシュ防止 |
| ダウングレード | 購読テンプレート未設定時はサイト内通知に自動ダウングレード |

### 12. 回数券核销クローズドループ（第8ラウンド）

| 機能 | 説明 |
|------|------|
| マイ回数券 | GET /api/marketing/cards/my で used_up/expired をリアルタイム計算 |
| 核销で回数減算 | POST /api/marketing/cards/use：Redis NX 冪等 + lockForUpdate 行ロック、completed 注文 + OrderItem + OrderPayment(pay_type='card') を直接作成 |

### 13. クーポン割引（第9ラウンド）

| 機能 | 説明 |
|------|------|
| 注文時にクーポン選択 | 注文時 user_coupon_id を渡せ、PriceCalculator.applyCoupon が読み取り専用で検証+金額計算 |
| 割引タイプ | fixed 固定金額 / percent パーセント、min_amount 満額割引閾値 |
| 消費と返却 | 支払い成功時に consume で used に；返金時に restoreCouponAndCard が冪等で返却 |

### 14. ギフトカード（第9ラウンド）

| 機能 | 説明 |
|------|------|
| 交換 | redeem：cash タイプはウォレットにチャージ（行ロックで二重入金防止、WalletTxn type='gift_card'）、gift タイプはマークのみ |
| マイギフトカード | GET /api/marketing/gift-cards/my |

### 15. ポイント体系（第9+10ラウンド）

| 機能 | 説明 |
|------|------|
| チェックインポイント | CheckIn 毎日チェックイン |
| 消費ポイント | 核销時 floor(paid×1)、order_id 冪等、balance スナップショット |
| 返金回収 | clawbackOrderPoints が比例回収（3 箇所接続） |
| ポイント現金化 | 支払い時 use_points を渡し、100ポイント=1元（config app.points_rate）、SUM 集計で残高検証、消費流水 source=points_offset 冪等 |
| ポイント回補（第15ラウンド） | キャンセル/返金で points_offset ポイントを返却：refundOffsetPoints 5 接続点（doCancel 3 経路/doRefund 微信トランザクション/creditRefundToWallet/completeOneRefundCompensation）、source=points_refund 冪等 |
| ポイント明細 | GET /api/marketing/points ページング + type/source フィルタ、type は earn に統一 |

### 16. ミニプログラム注文チェーン（第10ラウンド）

| 機能 | 説明 |
|------|------|
| サービス詳細ページ | service/detail |
| 注文確認ページ | order/confirm：クーポン選択/閾値グレーアウト/クライアント側予定金額 → POST /order → 微信/残高払い |
| ページ規模 | ミニプログラムは現在全 20 ページ |

### 17. ユーザー側三入口（第10ラウンド）

| 機能 | 説明 |
|------|------|
| お気に入り | favorite お気に入りページ（user ページ入口） |
| 紹介 | referral：招待コード/リンクコピー/被紹介ユーザー一覧 |
| フィードバック | feedback フィードバックフォーム |

### 18. 購読メッセージ認可（第14ラウンド）

| 機能 | 説明 |
|------|------|
| 購読認可 | utils/subscribe.js でテンプレート ID を集中管理（キー名はサーバー側 appointment_system_config.wechat_app.template_ids と整合） |
| トリガーシナリオ | 予約成功/支払い成功後のジェスチャーコールバック内で wx.requestSubscribeMessage、テンプレート ID 未設定またはユーザー拒否はいずれもサイレント |
| サーバー側チェーン | WechatTemplateMessageService 送信 + NotificationReminderService 予約 2h~1h 前リマインダー + AutoCancelTimer プロセススキャン |

### 19. アフターサービス返品交換（第14ラウンド）

| 機能 | 説明 |
|------|------|
| アフターサービス申請 | POST /api/aftersales：type=refund/exchange、本人注文/paid+completed/同一注文の重複申請を検証 |
| マイアフターサービス | GET /api/aftersales ページング一覧 + GET /api/aftersales/{id} 詳細 |
| 審査フロー | 管理端 approve/reject（rejected は remark 必須）；approved はステータス遷移のみ、返金は注文返金 API を踏襲 |

### 20. グループ購入/タイムセール（第15ラウンド）

> 2026-08 より FLASH_SALE チャネルは廃止：PromotionController::index は flash_sale をフィルタ、show/join は 400 を返却、タイムセールは一律「43. 秒杀（第24ラウンド）」チャネルへ；`Promotion::TYPE_FLASH_SALE` 定数は履歴データ互換のため保持。本節および「27. 秒杀下单」は履歴記録です。

| 機能 | 説明 |
|------|------|
| 活動一覧/詳細 | GET /api/promotions + /api/promotions/{id}、type で group_buy/flash_sale をフィルタ |
| 参加 | POST /api/promotions/join/{id}：Redis NX ロックで過剰販売防止（flash_sale は max_people を在庫上限に）、重複参加は 422、group_buy 満員ロック、期限までに満員でない場合は遅延クローズ（show/join 時に status を 0 に） |
| 参加者一覧 | GET /api/promotions/{id}/participants |
| ステータス修正 | PromotionParticipant のステータスを整数定数 0/1/2/3 に変更（厳密モードで join 1366 破損を修正） |

### 21. グループ購入成团注文（第16ラウンド）

| 機能 | 説明 |
|------|------|
| グループ価格 | join レスポンスで discount_percent/original_price/group_price を返却 |
| グループ購入注文 | POST /api/order に promotion_id を渡す：group_buy のみ/活動有効/呼び出し者が参加者/未満員/サービス一致を検証；グループ価格=原価×discount_percent/100、クーポン/回数券/ポイントの併用は無効（422） |
| 注文マーク | appointment_order に promotion_id/participant_id カラム + インデックス追加 |
| 未成团処理 | 期限までに満員でない→活動クローズ+該当活動の pending 注文を一括キャンセル（冪等）；pay() の遅延判定でクローズ済みなら注文を自動キャンセルしスタッフロックを解放 |

### 22. 紹介報酬（第16ラウンド）

| 機能 | 説明 |
|------|------|
| 支給ルール | 被紹介者の初回注文 completed 後に支給：金額=paid_amount×reward_rate（appointment_system_config referral.reward_rate デフォルト 0.05、不正値は定数へフォールバック）、>0 の場合のみ支給 |
| 接続点 | ReferralRewardService::handleOrderCompleted を WorkController::complete のトランザクション内に接続（serving→completed の唯一入口、核销 verify は serving まででトリガーしない）、失敗時は全体ロールバックで再試行可 |
| 冪等 | appointment_user_referral 行ロック lockForUpdate + rewarded_at 空判定 + ロック内の初回注文再確認（並行/重複呼び出しでも 1 回のみ支給） |
| 入金 | ウォレット行ロックで加算 + WalletTxn type='referral_reward'（balance_after + 注文番号 remark）；紹介記録に reward_type/reward_amount/rewarded_at/first_order_at を書き込み |
| 明細 | GET /api/user/referral/earnings ページング（被紹介者ニックネーム/アバター/注文番号/金額/時間） |

### 23. ポイント交換モール（第16ラウンド）

| 機能 | 説明 |
|------|------|
| 交換商品 | appointment_points_exchange_goods：type=coupon/gift_card/wallet、points_cost/value（DECIMAL(25,2) でスノーフレーク ID の精度損失を防止）/stock/status |
| 商品一覧 | GET /api/marketing/points-exchange：公開商品 + リアルタイム残り在庫 + 交換済み数 |
| 交換 | POST /api/marketing/points-exchange/{id}：Redis NX ロック + 商品行ロックで超過交換防止；ポイント SUM 検証（不足は 422）+ UserPoints type='consume' source='exchange' で減算；coupon 発行 / wallet 残高入金（WalletTxn points_exchange）/ gift_card カードキー返却 |
| 冪等 | uk_user_goods 一意インデックスで同一ユーザー同一商品は 1 回限り + ロック内再確認 + 1062 フォールバック；交換記録は appointment_user_points_exchange にスナップショット |

### 24. 予約変更（第17ラウンド）

| 機能 | 説明 |
|------|------|
| API | POST /api/order/reschedule/{id}：new_service_time（必須）+ reason（任意）、同一スタッフで時間変更 |
| ルール | 本人注文のみ（本人以外 404）；appointment タイプかつ pending/paid/confirmed のみ（その他 422）；元サービス開始まで ≥ 6 時間（全額返金ウィンドウと一致） |
| 並行防御 | B1 order_lock（pay/cancel/refund と同一の相互排他族）→ 新時間帯のスタッフロック Redis SETNX EX 180（並行変更の過剰販売防止）→ トランザクション内行ロック再読 + B2 シフト重複 DB 検証（本注文を除外） |
| 締め | service_time 更新 + appointment_order_reschedule 記録（reason 含む）+ 元時間帯ロック/新時間帯ロックの本注文分を解放；失敗時はトランザクションをロールバックし新時間帯ロックも解放 |
| 通知 | SCENE_RESCHEDULE 購読メッセージ（テンプレート未設定は「予約変更成功」のサイト内通知にダウングレード）+ pushOrderUpdate |

### 25. クーポン贈与（第17ラウンド）

| 機能 | 説明 |
|------|------|
| API | POST /api/marketing/coupons/transfer（user_coupon_id）で 8 桁の難読化一意贈与コードを生成（uk_code で二重防止、7日有効）；POST /api/marketing/coupons/claim（code）で受領；GET /api/marketing/coupons/transfers 送信済み(pending/claimed/expired)+受領済み(claimed) ページング |
| 検証 | クーポンが本人所有/available/券定義が期限内/贈与済みでない（422）；自分が贈与したクーポンの受領不可、受領者は元所有者でないこと |
| 乱用防止 | Redis NX ロック coupon_transfer_claim:{code}（30s）+ トランザクション内行ロック再確認で二重引き換え防止；uk_user_coupon 一意インデックスで同一クーポンの贈与は 1 回限り；譲受済みクーポンは再贈与不可（新券には贈与記録がなく自然にブロック）；遅延判定で期限切れは expired に + 元クーポンを available に復元 |
| 受領 | トランザクション内で元クーポンを used に + 受領者に紐づく新規 UserCoupon を生成（coupon_id 不変=有効期限不変）+ 贈与記録を claimed に |

### 26. ポイント期限切れ（第17ラウンド）

| 機能 | 説明 |
|------|------|
| 有効期限 | appointment_user_points.expires_at カラム；すべての earn（チェックイン/消費返/回補）は落庫時に expires_at = now + points.expiry_days（デフォルト 365、≤0 は期限なし）；consume/use は空 |
| 期限切れ実行 | PointsExpiryTimer 定期プロセスが 60 秒ごとにカーソルスキャン（100/バッチ）で expires_at < now の earn 行 → type=expire の負値減算行を作成（source=expiry + order_id で元流水を遡る）→ ユーザーごとに集約して「Xポイントが期限切れになりました」のサイト内通知 |
| 冪等 | ① expire 行の order_id は元 earn 流水を指し、トランザクション内で元行を lockForUpdate + exists 再確認（並行プロセスは行ロック上で直列化）② id カーソルページング ③ 通知は実際に減算したラウンドでのみ発生 |
| 口径 | 利用可能残高の SUM 集計に expire 負値行を含む；期限切れポイントは現金化/交換不可 |

### 27. 秒杀注文（第18ラウンド、廃止）

> 第24ラウンドの `/api/seckill` チャネルに置換済み（store() のプロモーション分岐はグループ購入のみ残存）、「43. 秒杀」を参照。

| 機能 | 説明 |
|------|------|
| API | POST /api/order に promotion_id（flash_sale タイプ）を渡す：秒杀価格 = round(total × (100 − discount_percent)/100, 2)、PromotionController の秒杀価格口径と一致 |
| 検証 | タイプ白リスト [group_buy, flash_sale]（その他 422）；活動進行中；呼び出し者が参加者；注文サービスと活動が一致；売り切れは participants_count ≥ max_people で 422「売り切れました」；クーポン/回数券/ポイントの併用は無効 422 |
| 期限切れ | pay() の遅延判定 isFlashSaleClosed（isGroupBuyClosed と同パターン）：秒杀期限切れ → 活動を 0 に + 該当活動の pending 注文を一括キャンセル + 本注文を自動キャンセル + スタッフロック解放 422 |

### 28. サービスリマインダー + 期限リマインダー（第18ラウンド）

| 機能 | 説明 |
|------|------|
| サービス開始前リマインダー | ServiceReminderTimer 60秒で service_time ∈ [now+1h, now+1h+60s)、status confirmed/serving、appointment タイプの注文をスキャン → サイト内通知（type='service_reminder'、サービス/スタッフ/店舗/時間を含む）+ SCENE_REMINDER 購読メッセージ |
| 期限リマインダー | ExpiryReminderTimer 6時間で end_at ∈ (now, now+3d+6h] をスキャン：active 会員カード（type='card_expiry'）+ available クーポン（type='coupon_expiry'、whereHas で券定義 end_at と関連付け）+ SCENE_EXPIRY 購読メッセージ |
| 冪等 | いずれも id カーソル 100/バッチ + トランザクション内行ロック再確認 + 通知重複チェック（order_id カラムに来源 id/注文 id を記録し重複防止キーに）；購読メッセージのプッシュ成功時のみ push_sent_at を書き込み、失敗は次ラウンドで再試行 |
| ダウングレード | テンプレート未設定（WECHAT_SUBSCRIBE_TEMPLATE_REMINDER / _EXPIRY）はサイト内通知のみに自動ダウングレード |

### 29. スタッフの評価返信（第18ラウンド）

| 機能 | 説明 |
|------|------|
| API | POST /api/technician/review/reply/{order_id}（スタッフ身分ミドルウェア）：評価なし/本人以外は一律 404；返信済みは 422（冪等拒否、上書きしない）；空返信 422 |
| 返信後 | ユーザーにサイト内通知（type='review_reply'、非ブロッキング try/catch + Log） |
| データ | appointment_order_review に replied_at カラムを冪等追加（reply カラムは建表時から存在）；管理端の評価 list/show は decorate()->toArray() 経由で reply/replied_at を透過出力 |

### 30. チャージ到着通知（第18ラウンド）

| 機能 | 説明 |
|------|------|
| API | 微信チャージコールバック（R プレフィックス注文番号）handleRechargeNotify のトランザクション内：WalletTxn の後にサイト内通知 type='wallet_recharge' を書き込み、「¥X.XX のチャージが完了しました」（金額は元単位、number_format 2桁） |
| 冪等 | 既存コールバック冪等を再利用（チャージ注文行 lockForUpdate + status 再確認、初回のみ pending→paid で通知に到達）；通知とステータス変更は同一トランザクションで原子的にコミット、crash ギャップなし；署名検証失敗/注文なし/金額不一致は通知を書き込まない |
| フォールトトレランス | 通知書き込みは try/catch、失敗時は warning ログのみでメインフローをブロックしない |

### 31. 残高送金（第19ラウンド）

| 機能 | 説明 |
|------|------|
| API | POST /api/wallet/transfer：受取人 hashid デコード+存在性 404、自分への送金 422、金額 0.01-1000/件 422（DECIMAL 比較で float 禁止）、残高不足 422、1 日累計 5000 元 422 |
| 並行/冪等 | Redis NX ロック wallet_transfer:{from} 30s で送金元を直列化；トランザクション内で双方の user_id 昇順に lockForUpdate ウォレット行（固定順でデッドロック防止）；client_token 成功後に SETNX 24h で重複提出防止（失敗リクエストは token を残さず再試行可） |
| 入金 | 送金元を減算 + 受取人を加算 + WalletTxn 二重取引履歴（transfer_out/transfer_in、balance_after スナップショット含む）+ 送金記録 completed + 受取人にサイト内通知 type='balance_received'（失敗はログのみ） |
| 記録 | GET /api/wallet/transfers（direction=out/in ページング）+ GET /transfers/{id}（双方のみ可視 404） |

### 32. ポイント贈与（第19ラウンド）

| 機能 | 説明 |
|------|------|
| API | POST /api/user/points/transfer：受取人存在 404、自分への贈与 422、ポイント 1-10000 422、残高 SUM 集計不足 422、1 日累計 10000 上限 422 |
| 並行/冪等 | Redis NX ロック points_transfer:{user} 30s；トランザクション内で双方の最終取引履歴 lockForUpdate（user_id 昇順で相互贈与デッドロック防止）+ ロック内で残高/上限/受取人を再確認 |
| 取引履歴仕様 | 送信側 type=consume source=points_transfer 負値（balance=前スナップショット-今回分、points_offset/exchange と同一口径）；受取側 type=earn source=points_transfer 正値、expires_at 含む（PointsExpiryTimer が正常に期限切れ処理）；トランザクション内で贈与記録を書き込み、commit 後に受取人へサイト内通知 type='points_received' |
| 記録 | GET /api/user/points/transfers（direction=sent/received ページング、相手のニックネーム付き） |

### 33. 評価追記 + 提出ルート補完（第19ラウンド）

| 機能 | 説明 |
|------|------|
| 追記 | POST /api/order/review/{order_id}/append：評価なし/本人以外は一律 404、非 completed 422、重複追記 422（append_content/append_at のいずれか非空で拒否）、空内容 422；成功で append_content/append_images(JSON)/append_at を書き込み + スタッフにサイト内通知 type='review_append' |
| 評価提出 | POST /api/order/review/{order_id} のルートを補完登録（ReviewController::store は元々ルート未登録で到達不能）；あわせて潜伏 TypeError を修正：findByOrderId が int を受け取り string シグネチャに違反（append の (string) 変換と対照）、ルート補完で即座に 500 を曝露するため |
| データ | appointment_order_review に append_content TEXT/append_images JSON/append_at DATETIME の三カラム追加（冪等マイグレーション）；レスポンスに append フィールドを透過出力 |

### 34. ユーザー端物流追跡（第19ラウンド）

| 機能 | 説明 |
|------|------|
| API | GET /api/order/logistics/{id}：本人の product 注文のみ照会可（本人以外/非商品/未発送は一律 404） |
| データ | order.remark JSON を読み取り（shipping_company/tracking_no/shipped_at、admin MallOrderController::ship() の発送時に書き込み）；parseShippingInfo/parseReceiver の二重解析で旧形式もフォールバック |
| マスク | 受取人携帯番号は maskPhone（138****5678）で表示、漏洩防止 |

### 35. 通知設定（第19ラウンド）

| 機能 | 説明 |
|------|------|
| データ | appointment_user_notify_setting テーブル（user_id+type 複合一意キー uk_user_type、行なし=デフォルトオン）；5 種：service_reminder サービスリマインダー / card_expiry 期限リマインダー（カード+クーポン統一の傘）/ points_expiry ポイント期限切れ / marketing マーケティング（予約）/ system システム（オフ不可、PUT で強制 1） |
| API | GET /api/user/notify-settings で 5 種の全量スイッチを返却；PUT でバッチ upsert、重複行を生成しない |
| ゲーティング | NotificationReminderService::notifySettingEnabled を 3 タイマープロセス（ServiceReminderTimer/ExpiryReminderTimer カード+クーポン/PointsExpiryTimer、タイマーは appointment_notification テーブルに直接挿入するためサービス書き込み経路を通らないので各々同じゲートを追加）+ 購読イベント（sendSubscribeForOrderEvent/Notification シナリオマッピング PAY/REFUND/VERIFIED/RESCHEDULE→system は常時送信、REMINDER→service_reminder、EXPIRY→card_expiry）に接続；タイプがオフのときはサイト内通知と購読メッセージの両方をスキップ |

---

## 二、管理バックエンド（PC Web）

Flutter Web シングルページアプリ、全 21 ページ：dashboard/ユーザー/ロール/設定/ログ/核销/シフト/サービス/スタッフ/注文/クーポン/会員/回数券/お知らせ/FAQ/出金/評価/レポート/マイページ/店舗ワークベンチ。

### 1. ホームダッシュボード

- リアルタイム統計：ユーザー数/注文総数/スタッフ数/サービス注文数
- 折れ線グラフ：注文量トレンド/金額トレンド/新規ユーザー/アクティビティ
- クイックナビゲーション：未処理モジュールボタン
- サイト内メッセージ：新規注文通知/返金通知

### 2. スタッフ管理

- スタッフ一覧：UID/携帯番号/氏名/所在地/登録時間で検索
- 一覧表示：番号/UID/携帯番号/ニックネーム/紹介者/ステータス/学员数/業績/アカウントステータス/登録時間/最終ログイン/所在地
- 操作：エクスポート/上位者変更/下位者表示/パスワード・携帯番号変更/シフト管理/サービス項目設定/コース進捗表示
- 新規追加：氏名/性別/携帯番号/身分証/身分証写真
- 入驻申請の審査

### 3. ユーザー管理

- 会員一覧：名称/携帯番号/アバター/レベル/消費金額
- 検索：UID/携帯番号/ニックネーム/登録時間
- 操作：詳細/上位者変更/下位者表示/パスワード・携帯番号変更/会員レベル設定

### 4. 店舗管理

- 店舗一覧：有効/無効切り替え、削除
- 新規店舗：名称/住所/座標/電話/営業時間/画像

### 5. サービス管理

- サービス一覧：名称/分類で検索；番号/名称/タイプ/割引/最安値/売上/カバー/並び順/ステータス/時間
- 操作：新規/変更/削除/カード項目設計
- 商品一覧：タイプ/名称/割引/最安値/売上/在庫/カバー/並び順/ステータス/時間

### 6. モール管理

- モール注文：明細/発送/物流/印刷
- アフター注文：表示/審査/印刷
- 評価管理：表示/審査（show/hide）/削除（ReviewController index/show/audit/destroy）
- 支払い取引履歴
- 売上統計

### 7. 注文管理

- 未使用注文：複数条件検索
- 操作：詳細/プラットフォームキャンセル/完了確認

### 8. クーポン活動

- 一覧：番号/画像/タイプ/名称/公開・非公開/総数/残り/管理者/時間/終了日
- 操作：新規/変更/削除

### 9. 財務管理

- 注文分配：検索/詳細
- スタッフ出金：WithdrawalController 審査；金額≥500 は二段階承認（店長 store_approved_at → 財務 finance_approved_at）；ステートマシン pending→approved→completed（rejected/failed）
- コミッション設定：報酬率/精算周期/賞罰/残高の変更
- 収支取引履歴
- 出金アカウント管理
- 出金制限設定

### 10. コンテンツ管理

- バナーCRUD
- 私たちについて設定
- モーメンツ動態審査
- よくある質問CRUD
- 意見フィードバック処理
- プラットフォームお知らせCRUD

### 11. 設定

- プラットフォーム規約編集（ユーザー規約/プライバシー規約/サービス規約）
- スタッフ統一コミッション設定
- システムメッセージテンプレート（ミニプログラム購読メッセージテンプレート設定含む、未設定はサイト内通知に自動ダウングレード）
- サブアカウント権限管理（店長はクーポン発行+シフト設定可）

### 12. 拡張機能

- カード項目設計：項目+商品組み合わせ/手数料/歩合設定
- システム監視：CPU/メモリ/ディスク/Redis/MySQL/キューのリアルタイムボード
- IPブラックリスト：security-php 攻撃記録の可視化+手動ブロック
- データベースバックアップ：Web 画面でバックアップ/ダウンロード/リストア
- 顧客ペルソナ：360度ビュー/消費嗜好/層別マーケティング
- 一括プッシュ：テンプレートメッセージ/セグメント一括送信
- 返金審査フロー：二段階承認（店長→財務）
- スタッフレベル：junior/senior/expert 自動判定
- 定期タスク：自動キャンセル/精算/期限切れ処理
- 短信設定：阿里云/腾讯云 マルチチャネル管理
- ストレージ設定：ローカル/OSS/COS/CDN
- レポート強化：カスタムフィールド/定期メールレポート
- シフトエクスポート：Excel で予約記録/出勤一覧をエクスポート
- スタッフ性別制限：特定項目の性別制御
- スタッフ研修：コース管理/学習進捗追跡
- 店長アカウント：store_id データ分離+専用権限

### 13. データレポート（第7ラウンド）

- ReportController 3 エンドポイント：注文統計 / スタッフ業績 / 店舗分布
- Redis キャッシュ svc:admin_report:{type}:{start}:{end}、TTL 300

### 14. 会員カード管理（第10ラウンド）

- appointment_user.member_level 会員レベルカラム（マイグレーション 000008）
- MemberCardController 完全 CRUD（権限 365-369）：GET/POST/PUT/DELETE /admin/member-cards
- Flutter 会員カード定義管理ページ

### 15. アフターサービス管理（第14ラウンド）

- appointment_order_aftersale テーブル（マイグレーション 000009）：type=refund/exchange、status=pending/approved/rejected/completed
- AftersaleController：GET /admin/aftersales（ページング+status/uid/order_no フィルタ）+ POST /admin/aftersales/{id}/review（approve/reject+remark）
- Flutter アフターサービス管理ページ（一覧+審査ダイアログ、権限 370/371）、レイアウト登録済み

### 16. 店長ワークベンチ（第15ラウンド）

- service /api/store-manager：overview（今日の注文/売上/進行中/スタッフ数/核销数）+ orders（ページング+ステータスフィルタ）+ technicians（今日のシフト含む）+ revenue（直近 7 日集計）、requireStoreId() で store_id 分離を強制（店舗なし 403）
- admin StoreController::workbenchOverview（GET /admin/stores/workbench-overview?store_id=、口径は service と一致）+ AppointmentOrderController 注文一覧の store_id フィルタ（hashid デコード）
- Flutter 店舗ワークベンチページ：店舗ドロップダウン + ステータスフィルタ + 概要カード 5 枚 + 注文 DataTable + ページング（権限 372）

### 17. ポイント交換商品（第16ラウンド）

- PointsExchangeGoodsController：GET/POST/PUT/DELETE /admin/points-exchange-goods + POST {id}/toggle-status（公開/非公開）+ GET {id}/exchanges（交換記録、携帯番号+result JSON 解析含む）
- マイグレーション 000012（二テーブル）+ 000013（権限 373-378）適用済み

### 18. 報酬記録（第16ラウンド）

- ReferralRewardController：GET /admin/referral-rewards（rewarded_at 非空の記録のみ、ページング + keyword で紹介者/被紹介者のニックネームまたは携帯番号をフィルタ、hashid エンコード、権限 379）

### 19. スタッフレベル自動判定（第17ラウンド）

- TierRatingService::evaluate(technicianId, allowDowngrade=false)：リアルタイムで appointment_order completed 注文数 + appointment_order_review 平均点（四捨五入 1 桁）を集計し profile.order_count/rating に書き戻し、appointment_technician_tier_config（min_orders/min_rating）に従って高い方からマッチング、該当なしは最下位レベル
- 昇降格ルール：昇格のみで降格なし（レベルはコミッション率と価格係数に紐づくため、自動降格はスタッフ収入に影響し紛争を招きやすい。下降は admin の手動フォールバック）；allowDowngrade=true（バックエンド手動再評価シナリオ）の場合のみ降格実行、降格も同様にログ+通知
- 冪等：取得レベルと profile.tier_id が一致する場合は統計同期のみで、ログも通知も書かない
- ログ：変更は appointment_technician_tier_log（id/technician_id/old_tier_id/new_tier_id/reason/created_at）+ サイト内通知（type='tier'）
- トリガー点：WorkController::complete / ReviewController の評価書き込み / ProfileController プロフィール表示の遅延判定
- 管理端：TechnicianTierController が手動設定機能を保持；GET /admin/technician-tiers/logs ページングで変更ログを表示（スタッフ名と新旧レベル名を join、ID は hashid エンコード、権限 380）

### 20. 評価返信表示（第18ラウンド）

- ReviewController に reply() を追加：GET /admin/reviews/{id}/reply 返信詳細（decodeId → find → 404 → decorate 出力、未返信時は reply=''、reply/replied_at は toArray 経由で透過出力）
- ルートは静的ルート（audit の前に配置、resource より先に定義）；権限シード id 381（slug 'get.admin/reviews/{id}/reply'、type 3、超管ロールに冪等関連付け）
- 権限ポイント：381

### 21. 予約カレンダー（第20ラウンド）

- CalendarController 月/日表示：GET /api/calendar/technician/{id}（月表示）+ /day（日表示）
- データソース：technician_schedule.time_slots JSON を曜日ごとに時間枠へ展開、appointment_order の当日予約済み時間帯を除外（status ∈ pending/paid/confirmed/serving）、残りの予約可能枠を出力
- 用途：店舗シフトの可視化による時間選択、フロントは日単位で横スクロール + 時間グリッド選択

### 22. ユーザー成長レベル（第20ラウンド）

- appointment_user_growth（取引履歴）+ appointment_growth_level（ランクシード 5 級：青銅0/銀100/金500/プラチナ2000/ダイヤ5000）
- 成長値入金点：チェックイン +10（CheckInController）；評価提出 +20（ReviewController::store、追記は入金なし）；消費 floor(paid) で 1 元ごと 1 ポイント（WechatPayService::markOrderPaid、既存の支払いステータス再確認を再利用し自然に冪等、重複コールバックで重複入金なし）
- API：GET /api/growth（現在レベル概要：balance/level/次ランクまでの差額）；GET /api/growth/records（取引履歴ページング）；GET /api/growth/levels（公開ランク一覧、ログイン不要）
- 失敗ポリシー：任意の入金点は try/catch でログ記録、メインフローに影響なし

### 23. 電子領収書（第20ラウンド）

- appointment_invoice：uk_order_type(order_id,order_type) で同一注文の重複申請防止（重複申請 422、MySQL 1062 キャッチのフォールバック含む）；idx_user_created/idx_status
- ユーザー端：POST /api/invoices（申請、金額/名義はサーバー側で注文から導出、改ざん不可）；GET /api/invoices（一覧）；GET /api/invoices/{id}（詳細）
- 管理端：InvoiceController issue（発行：invoice_no + status=issued + issued_at を書き込み）/ reject（却下：status=rejected + reject_reason）、権限 382 一覧/383 発行/384 却下
- ステートマシン：pending → issued / rejected

### 24. カスタマーサポートチケット（第20ラウンド）

- appointment_ticket：ユーザーがチケットを提出（title/content）、バックエンドが返信を追記（reply_content/replied_at）、ユーザーがクローズ可能（closed_at）
- ユーザー端：POST /api/tickets（提出）；GET /api/tickets（一覧）；GET /api/tickets/{id}（詳細、本人のみ）；POST /api/tickets/{id}/close（クローズ）
- 管理端：TicketController index（一覧）/ reply（返信）、静的ルートを resource より先に定義し {id} shadow を回避；権限 385 チケット返信/387 チケット一覧表示
- ステートマシン：open → replied（返信後は open に戻り再返信可）/ closed

### 25. 多段階紹介-二段階報酬（第20ラウンド）

- ReferralRewardService::payLevel2Reward(paidAmount, orderId)：注文支払い成功後、一階級紹介者の紹介者（二階級紹介関係）を検索し paid×level2_rate（システム設定 referral.level2_rate、デフォルト 0.02）を支給
- 冪等：トランザクション内行ロック + uk_order_referred(order_id, level2_user_id) 一意キー、重複支払いコールバック/並行でも重複支給なし；try/catch 失敗はログのみで支払いメインフローに影響なし
- 入金：WalletTxn type='referral_level2'（TYPE_REFERRAL_LEVEL2 定数）+ ウォレット残高加算
- 管理端：ReferralLevel2Controller index ページング記録（権限 386）、二階級ユーザーのニックネームを join

### 26. 成長レベル特典の実装（第21ラウンド）

- GrowthLevel.benefits JSON の中身を実装：マイグレーションシード 5 ランク（青銅 {"discount_rate":1.0,"points_multiplier":1.0}、銀 0.98/1.1、金 0.95/1.2、プラチナ 0.92/1.3、ダイヤ 0.9/1.5）
- レベル割引：OrderController::store applyGrowthDiscount() —— 標準注文のみ（promotion_id 空、グループ購入/秒杀は併用無効）；順序：クーポン/回数券割引後の支払額 × discount_rate；割引額は discount_amount に合算、注文備考に「レベル割引：銀9.8割、割引¥2.00」を追記し追跡可能；最低価格保護：割引後実払い ≥0.01 元（分制 ≥100）、不足時は割引を 0 に切り捨て
- ポイント倍率：WechatPayService::markOrderPaid の成長値を floor(paid) から floor(paid × points_multiplier) に変更、倍率は支払い時点のレベルで取得（入金前に累計、本注文では昇格しない）；R20 の try/catch 接続点は完全に保持
- クエリ再利用：GrowthLevel::levelForGrowth() が累計成長値でランクを取得し、注文/支払いで再利用；GET /api/growth は benefits と next_gap を既に返却（R20 実装、変更不要）

### 27. 領収書名義管理（第21ラウンド）

- appointment_invoice_title（uk_user_title(user_id, title_type, invoice_title) で重複防止 + idx_user_default）
- API：POST /api/invoice-titles（保存、company は tax_no 必須、重複 422）；GET（一覧、デフォルトを先頭に）；PUT /{id}（編集、本人のみ）；DELETE /{id}（削除、本人のみ）；POST /{id}/default（デフォルト設定、トランザクションで同ユーザーの他行をクリア）
- デフォルトルール：先頭の保存が自動でデフォルト；デフォルト削除後は最も古い 1 件を自動指定
- 申請連携：InvoiceController::store は任意の title_id を解析して名義を invoice_title/tax_no/title_type に引き継ぎ、title_id なしの場合は手入力を維持；uk_order_type の重複防止ロジックは変更なし

### 28. チケット満足度（第21ラウンド）

- appointment_ticket に rating TINYINT NULL + rated_at DATETIME NULL を追加（マイグレーション 000303）
- クローズ評価：TicketController::close() は任意の rating 1-5 をサポート（filter_var 整数検証、範囲外/非整数は 422；提供時は rating+rated_at を書き込み、未提供は NULL のまま旧クライアント互換；open チケットのみクローズのルールは保持）
- バックエンド統計：GET /admin/tickets/satisfaction（静的ルートを resource より先に定義し {id} shadow を回避）で total/rated_count/unrated_count/average（1 桁）/distribution（1-5 星の各数、欠けた星は 0 補完）を返却；権限 388

### 29. 評価画像審査（第21ラウンド）

- admin ReviewAuditController（新規作成、既存の ReviewController は変更しない）：GET /admin/review-audit 画像付き評価一覧（JSON_LENGTH(images)>0 フィルタ + leftJoin ユーザーニックネームとスタッフ名 + status フィルタ + hashid エンコード）；POST /{id}/hide 非表示；POST /{id}/restore 復元
- ステートマシン：hide は visible のみ非表示可、restore は hidden のみ復元可（双方向 422）；OrderReview ステータスは整数体系（STATUS_HIDDEN=0/STATUS_VISIBLE=1）
- 効果チェーン：ユーザー端のスタッフ評価一覧は status で既にフィルタ → 非表示後は自動的に見えなくなる
- 権限：389 一覧 / 390 非表示 / 391 復元

### 30. ユーザー閲覧履歴（第21ラウンド）

- appointment_browse_history（uk_user_item(user_id, item_id) 一意、重複閲覧は viewed_at の更新のみで重複挿入なし；idx_user_viewed でソート）
- 記録接続：ServiceController::detail() 成功後に記録（try/catch + Log::warning でメインフローに影響なし；公開ルートは JWT なし、user_id 空判定で匿名はスキップ）
- API：GET /api/browse-history（appointment_service の名称/カバー/価格/原価を join、viewed_at 降順、per_page デフォルト 15 上限 50、item_id は hashid）；DELETE /{item_id}（本人のみ、不正/他人は 404）；DELETE /（全削除は本人のみ）

### 31. 満額割引マーケティング（第22ラウンド）

- appointment_full_reduction_activity（threshold/reduction/title/status/start_at/end_at + idx_status_status_time）
- 注文時併用：標準注文のみ（グループ購入/秒杀はスキップ）、クーポン/回数券割引後の支払額で閾値を判定、順序は **クーポン/回数券 → 満額割引 → レベル割引**；割引額最大の活動を採用；割引額は discount_amount に合算 + 備考「満額割引：満X減Y」；満額割引後の実払い下限 0.01 元（分制）
- ユーザー端 GET /api/full-reduction-activities（公開、有効中を割引額降順）
- admin FullReductionController：CRUD + toggle-status 公開/非公開（destroy は confirmPassword 付き）
- 権限：396 一覧 / 397 新規 / 398 編集 / 399 公開/非公開 / 400 削除（1 権限レコードは 1 つの method.path slug のみ、5 ルートは 5 件に分割）

### 32. マイ予約 ICS エクスポート（第22ラウンド）

- IcsController GET /api/order/ics：90 日以内の pending/paid/confirmed/serving 注文を iCal（RFC5545）でエクスポート、本人のみ
- VEVENT：UID=注文ID、DTSTAMP(UTC)、TZID=Asia/Shanghai、デフォルト時間 1h、サマリー「予約：サービス名」（欠落時は「予約」に退化）、説明はスタッフ/店舗/住所（欠落時はスキップ）、LOCATION；テキストエスケープ（\, \; \\ \n）+ 75 バイト行折り畳み
- 注文なしは有効な空カレンダーを返却（`BEGIN:VCALENDAR` 骨格）

### 33. スタッフ勤怠（第22ラウンド）

- appointment_technician_attendance（date/check_in_at/check_out_at/status + uk_technician_date 一意インデックスで並行重複打刻防止）
- スタッフ端（TechnicianAuth）：check-in 当日重複 422；check-out 未出勤/既退勤 422 + 行ロック；>10:00 は遅刻マーク；GET 当月一覧 + 出勤日数/総労働時間/平均労働時間（?month=YYYY-MM 不正 422）
- admin：GET /admin/attendance（date+スタッフ名フィルタ、real_name join、hashid）+ /stats（スタッフごとにグループ化した統計）
- 権限：392 一覧 / 393 統計

### 34. APP プッシュサービス（第22ラウンド）

- AppPushService（config group=push：enabled デフォルト 0 / provider jpush/getui/placeholder）：未有効時はサイレントにログのみにダウングレード；有効時はプラットフォーム/タイトル/内容/payload 構造を組み立て Log 記録 + appointment_push_log 書き込み（status=sent）；ベンダー SDK 接続は TODO を残す（資格情報なしで実際の送信はしない）
- 5 箇所のイベント接続：支払い成功（WechatPayService::markOrderPaid）、自動返金（autoRefundCancelledOrder）、手動返金（doRefund/refundToBalance）、返金補償（completeOneRefundCompensation）、サービス開始リマインダー（ServiceReminderTimer）；すべて try/catch でメインフローをブロックしない
- appointment_push_log（user_id/title/content/payload JSON/status/provider + idx_user）

### 35. 微信公式分配（第22ラウンド）

- WechatProfitSharingService（config group=profit_sharing：enabled/receiver_ratio、資格情報は wechat_pay を再利用）：未有効は disabled でログのみ・落庫なし；有効→金額検証（>0 かつ ≤paid、実払い×0.7 デフォルト）+ 冪等（同一注文の pending/success はスキップ）→ pending 記録を作成 →「请求单次分账」構造を構築（資格情報なしで HTTP は実行せず、リクエスト内容はログ記録、記録は pending のまま）；HTTP は分離したプライベート doRequest でテスト可能
- WechatPayService::markOrderPaid 送信後に requestSharing を接続（try/catch 失敗はログのみ）
- appointment_profit_sharing（uk_sharing_no 一意 + idx_order）；admin GET /admin/profit-sharing 一覧（注文番号/スタッフニックネーム join、ステータス/注文番号/スタッフ名フィルタ）
- 権限：394

### 36. プライバシーコンプライアンス（第22ラウンド）

- GET /api/privacy/data：データエクスポート（personal/orders/points/wallet_txns/reviews/addresses/invoices グループ；ログはマスク済み携帯番号+件数のみ記録）
- 削除クローズドループ：close-request（残高非 0 / 未完了注文 / 進行中チケットは 422 → close_status=1）→ close-cancel（1→0）→ close-confirm（72h 経過 → close_status=2 + close_at + phone/nickname を user{id} に匿名化 + status=0）
- appointment_user に close_status/close_requested_at/close_at を追加（冪等 ALTER マイグレーション）；AuthController login/loginByCode は close_status=2 に対して 403「アカウントは削除されました」を返却

### 37. ユーザー健康プロフィール（第23ラウンド）

- GET/PUT/DELETE /api/health-profile：一人 1 件（uk_user 一意インデックス）、upsert は提供されたフィールドのみ更新
- allergies/health_notes は上限 500 文字、preferred_technician_id は存在性を検証、レスポンスは hashid エンコード
- マイグレーション 000504_user_health_profile；HealthProfileTest 6 tests

### 38. ウォレット支払いパスワード（第23ラウンド）

- POST /api/wallet/pay-password/{set,verify,check}：6 桁数字検証、password_hash 保存 + pay_password_set_at
- 設定済みの場合の変更は旧パスワード必須 422；verify は検証のみで落庫なし；check は設定済みかどうかを返却
- マイグレーション 000502（INFORMATION_SCHEMA 冪等 ALTER 二カラム）；WalletPayPasswordTest 7 tests

### 39. スタッフ一括シフト（第23ラウンド）

- POST /api/technician/schedule/batch：日付範囲 ≤7 日 + weekdays フィルタ、既存シフトのある日はスキップ
- 単条設定も時間帯重複検出を有効化（422「既存シフトと時間が衝突：HH:MM-HH:MM」）
- ScheduleConflictTest 5 tests

### 40. 注文ステータスタイムライン（第23ラウンド）

- GET /api/order/{id}/timeline：本人のみ照会可（他人 404）、降順で返却；admin 注文詳細に timeline 配列を統合
- OrderStatusLog::record() 静的埋め込み 8 種の変更：提出/支払い/キャンセル/確認/返金申請/返金通過/サービス開始/サービス完了/タイムアウト自動キャンセル/バックエンド操作（operator=admin）
- 支払いコールバック markOrderPaid が単一消費点；record() 内部は try/catch + Log::warning でメインフローを絶対にブロックしない
- マイグレーション 000501_order_status_log；OrderTimelineTest 4 tests

### 41. ポイントラッキーくじ（第23ラウンド）

- GET /api/wheel/prizes（weight/stock は非表示）；POST /api/wheel/spin：Redis NX + 行ロックで並行防止、random_int 重み付き抽選、client_token 冪等
- 景品の入金：ポイント→earn 取引履歴（有効期限付き、PointsExpiryTimer が正常に期限切れ処理）、残高→lockForUpdate、クーポン→pending 手動発行、はずれ→lose
- GET /api/wheel/records マイ記録ページング；admin /admin/lucky-wheel CRUD + 公開/非公開 + 記録（権限 401-406）
- マイグレーション 000503（appointment_lucky_wheel + appointment_wheel_record + w60/w40 デモシード）+ 000505（権限シード）；LuckyWheelTest admin 3 + service 6 tests

### 42. ゲストモード（第24ラウンド）

- GET /api/guest/{home,services,services/{id},stores,technicians}：認証不要（ApiVersion ミドルウェアのみ）の未ログイン閲覧入口
- home はバナー/お知らせ/サービス分類/人気サービスを集約、Redis キャッシュ svc:guest:home 300s；services はカテゴリフィルタ + newest/sales/price ソート（page/per_page≤50）；technicians は審査通過のみ、service_id フィルタ可、評価降順
- GuestControllerTest でカバー

### 43. 秒杀（第24ラウンド）

- appointment_seckill_activity（name/service_id/seckill_price/original_price/stock/start_at/end_at/status）；販売済み数 = appointment_order.seckill_id の注文数
- GET /api/seckill（status=1 + 時間窓）、/{id}（state=not_started/ongoing/ended）、POST /{id}/buy：client_token（8-64 文字、SETNX 24h）冪等 + Redis NX 30s 並行防止 + 活動検証（2026-08-26 より在庫の予約減算なし）
- 注文に seckill_id を注入して OrderController::store を再利用；在庫は一律 store() トランザクション内の行ロックで減算（/api/order を seckill_id 付きで直接呼んでも在庫は減る）、秒杀価格 = seckill_price（DB 基準）、クーポン/ポイント/会員カードは併用不可；注文キャンセルで在庫は戻さない；旧プロモーション FLASH_SALE チャネルは削除済み（store() のプロモーション分岐はグループ購入のみ、PromotionController index は flash_sale をフィルタ、show/join 400）、秒杀は本チャネルのみ
- admin /admin/seckill CRUD + 公開/非公開 + 注文一覧（権限 407-411、420）；マイグレーション 000606 権限シード；SeckillTest service + admin

### 44. APP バージョン管理と更新検知（第24ラウンド）

- appointment_app_version（platform/version_code/version_name/force_update/changelog/download_url/status）
- GET /api/app/version?platform=android|ios 公開の更新検知（platform 不正 422；status=1 から最新を取得；なければ空オブジェクト）
- admin /admin/versions CRUD（権限 416-419）；マイグレーション 000609 権限シード；VersionTest service + admin

### 45. リピーター特典（第24ラウンド）

- ReturnCustomerRewardService：ユーザーが同一スタッフに対し 30 日以内の 2 回目消費（注文完了）でスタッフにボーナス = 実払い paid_amount × ratio（system_config group=return_customer、ratio デフォルト 0.05、enabled スイッチ、不正値はデフォルトへフォールバック）
- appointment_technician_earnings（type=return_customer、status=pending）に記録しコミッション精算チェーンを再利用、スタッフ端の earnings 集計に自動的に含まれる；同一 order_id+type で冪等；WorkController::complete の行ロックトランザクション内で呼び出し
- admin /admin/return-customer/config（GET/PUT）+ /rewards（?keyword スタッフ名/注文番号/ユーザーニックネーム）（権限 412-414）；マイグレーション 000607 権限シード；ReturnCustomerRewardServiceTest

### 46. シフトエクスポート（第24ラウンド）

- GET /admin/technician-schedule/export：CSV（UTF-8 BOM、Excel で直接開ける）、ファイル名 schedules_{YmdHis}.csv
- start_date/end_date 必須（YYYY-MM-DD、不正 422）かつ範囲 ≤31 日；technician_id 任意（hashid、不正 422）
- 列：スタッフID/スタッフ氏名/日付/時間帯明細（time_slots JSON を "09:00-12:00, 14:00-18:00" に解析）
- 権限：415；マイグレーション 000608 権限シード；ScheduleExportTest でカバー
