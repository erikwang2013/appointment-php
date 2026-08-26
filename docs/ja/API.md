# API 説明ドキュメント

## 概要

- **業務API** (service/): `http://localhost:8787` — 小程序/APPに業務インターフェースを提供
- **管理バックエンドAPI** (admin/): `http://localhost:8787` — 管理バックエンドのFlutter Webにインターフェースを提供
- **認証方式**: Bearer Token (JWT)、リクエストヘッダー `Authorization: Bearer <token>`
- **バージョン管理**: リクエストヘッダー `API-Version: v1` でAPIバージョンを制御、URLには含めない。デフォルトは v1
- **IDエンコード**: すべてのリクエスト/レスポンスのIDフィールドは hashids でエンコードされ、実データベースIDを外部に公開しない
- **OpenAPIドキュメント**: `hg/apidoc` で生成、管理端とクライアントで分離

| 端 | OpenAPIドキュメントURL | 説明 |
|------|------|------|
| 管理端 | `GET http://localhost:8787/api/docs` | 管理バックエンドAPI完全仕様（OpenAPI 3.0 JSON） |
| クライアント | `GET http://localhost:8787/api/docs` | 業務API完全仕様（OpenAPI 3.0 JSON） |

Swagger UI などのツールで上記URLをインポートすると、インタラクティブなドキュメントを確認できます。

- **共通レスポンス形式**:

```json
{
  "code": 0,
  "message": "操作成功",
  "data": {}
}
```

ページネーションレスポンス:
```json
{
  "code": 0,
  "message": "success",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

---

## 一、業務API (service/ :8787)

### 1. 公開インターフェース（認証不要）

#### 1.1 認証コード

**`POST /api/captcha/send`** — 短信認証コードを送信

リクエスト:
```json
{
  "phone": "13800138000"
}
```
レスポンス: `{"code":0,"message":"验证码已发送","data":null}`

制限: 60秒ごとに1回のみ送信可能、認証コードは5分間有効。

---

#### 1.2 認証

**`POST /api/auth/register`** — 携帯番号登録

リクエスト:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "abc123",
  "confirm_password": "abc123",
  "referral_code": "A1B2C3D4"
}
```
レスポンス:
```json
{
  "code": 0,
  "message": "注册成功",
  "data": {
    "token": "eyJhbGciOi...",
    "user": {
      "id": "aB3xK9mQ",
      "phone": "138****8000",
      "nickname": "用户138****8000",
      "user_type": "customer",
      "active_role": "customer",
      "referral_code": "E5F6G7H8"
    }
  }
}
```

---

**`POST /api/auth/login`** — パスワードログイン

リクエスト:
```json
{
  "phone": "13800138000",
  "password": "abc123"
}
```
レスポンス: 登録レスポンスと同じ、token と user 情報を含む。

---

**`POST /api/auth/login-by-code`** — 認証コードログイン

リクエスト:
```json
{
  "phone": "13800138000",
  "code": "123456"
}
```
レスポンス: ログインと同じ。未登録ユーザーはアカウントを自動作成。

---

**`POST /api/auth/forget-password`** — パスワード忘れ

リクエスト:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "newpass123",
  "confirm_password": "newpass123"
}
```

---

**`POST /api/auth/refresh`** — Tokenリフレッシュ

リクエストヘッダー: `Authorization: Bearer <旧token>`
レスポンス: `{"code":0,"data":{"token":"eyJhbGciOi..."}}`

---

#### 1.3 微信

**`POST /api/wechat/mini-login`** — 小程序ログイン

リクエスト: `{"code":"微信登录code"}`
説明: 初回ログイン後、後続で `/api/wechat/phone` を呼び出して携帯番号を紐付ける必要があります。

---

**`POST /api/wechat/phone`** — 携帯番号の紐付け

リクエスト: `{"code":"微信手机号组件code"}`

---

**`POST /api/wechat/oa-login`** — 公式アカウントログイン

リクエスト: `{"code":"公众号授权code"}`

---

#### 1.4 共通サービス

**`GET /api/common/config`** — 共通設定

レスポンス: 規約テキスト（ユーザー規約/プライバシー規約/サービス規約）、会社概要情報、バージョン番号を含む。

---

**`GET /api/common/area`** — 都市・エリアリスト

---

#### 1.5 サービス照会

**`GET /api/service/categories`** — 分類リスト

パラメータ: `?parent_id=0`

---

**`GET /api/service/items`** — サービス項目リスト

パラメータ: `?category_id=&page=1&per_page=10&sort=sales`

---

**`GET /api/service/detail/{id}`** — サービス詳細

レスポンスには: 画像/名称/価格/規格/所要時間/売上/評価リスト を含む。

---

**`GET /api/service/products`** — 商品リスト

**`GET /api/service/stores`** — 店舗リスト

パラメータ: `?lat=&lng=&city=`

---

#### 1.6 スタッフ照会

**`GET /api/technician/list`** — スタッフリスト

パラメータ: `?lat=&lng=&service_id=&page=1`
距離の近い順にソートし、返却: アバター/名前/評価/注文数/お気に入り数/距離/最速で予約可能な時間/サービス可能かどうか。

---

**`GET /api/technician/detail/{id}`** — スタッフ詳細

レスポンスには: 画像/名前/紹介/評価/距離/提供可能なサービス項目リスト/評価 を含む。

---

**`GET /api/technician/schedule/{id}`** — スタッフ排班

パラメータ: `?date=2026-05-26`
該当日の予約可能な時間帯と利用可否状態を返します。

---

#### 1.7 コンテンツ

**`GET /api/content/banners`** — カルーセル画像

パラメータ: `?position=home`

**`GET /api/content/articles`** — お知らせ/記事リスト

パラメータ: `?type=announcement&page=1`

**`GET /api/content/article/{id}`** — 記事詳細

---

#### 1.8 LBS

**`GET /api/lbs/nearby-stores`** — 近くの店舗

パラメータ: `?lat=&lng=&radius=5000`

**`GET /api/lbs/geocode`** — 逆ジオコーディング

パラメータ: `?lat=&lng=`

---

### 2. ユーザーインターフェース（JWT認証必要）

すべてのインターフェースのリクエストヘッダーに `Authorization: Bearer <token>` を含める

#### 2.1 個人プロフィール

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/user/profile` | 個人情報の取得 |
| PUT | `/api/user/profile` | ニックネーム/アバター/性別の更新 |
| POST | `/api/user/change-password` | パスワード変更 (old_password/new_password/confirm_password) |
| POST | `/api/user/change-phone` | 携帯番号変更 (old_code/new_phone/new_code) |
| POST | `/api/user/cancel-account` | アカウント削除 (パスワード検証が必要) |
| POST | `/api/user/logout` | ログアウト (tokenをブラックリストへ) |
| POST | `/api/user/switch-role` | 身分切替 (role: customer/technician) |

technician への切替には approved 状態のスタッフカルテが必要です。

#### 2.2 住所管理

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/user/addresses` | 住所リスト |
| POST | `/api/user/addresses` | 住所追加 (contact_name/contact_phone/province/city/district/detail/lat/lng/is_default) |
| GET | `/api/user/addresses/{id}` | 住所詳細 |
| PUT | `/api/user/addresses/{id}` | 住所更新 |
| DELETE | `/api/user/addresses/{id}` | 住所削除 |

デフォルトに設定すると、他のデフォルト住所は自動的に解除されます。

#### 2.3 お気に入り

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/user/favorites` | お気に入りリスト (?type=service/technician) |
| POST | `/api/user/favorites` | お気に入り追加 (target_type/target_id) |
| DELETE | `/api/user/favorites/{id}` | お気に入り解除 |

#### 2.4 意見フィードバック

`POST /api/user/feedback` — フィードバック送信 (content + images配列)

#### 2.5 紹介・推薦

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/user/referral` | 紹介情報 (紹介コード/紹介人数/初回注文人数/獲得ポイント) |
| GET | `/api/user/referral/qrcode` | 紹介QRコード (紹介コード+招待リンク) |
| GET | `/api/user/referral/referred-users` | 紹介済みユーザーリスト |
| GET | `/api/user/referral/earnings` | 分销返金明細 (ページネーション: 被紹介者ニックネーム/アバター/注文番号/金額/発行時間) |

**分销返金**: 被紹介者の初回注文 completed 後に発行、金額 = paid_amount × reward_rate（erik_system_config referral.reward_rate、デフォルト 0.05、不正値は定数にフォールバック）。行ロック + rewarded_at 空チェック + 初回注文再確認の三重冪等；入帳は WalletTxn type=referral_reward。

#### 2.6 ポイント転贈（第19ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| POST | `/api/user/points/transfer` | ポイント転贈 (to_user_id hashid/points) |
| GET | `/api/user/points/transfers` | 転贈記録 (?direction=sent/received&page=1) |

**ポイント転贈**: 受取人の hashid デコード+存在性 404、自分への転送 422、ポイント数 1-10000 422、残高 SUM 集計不足 422、単日累計 10000 上限 422。並行防御：Redis NX ロック points_transfer:{user} 30s → トランザクション内で双方の最新流水を lockForUpdate（user_id 昇順で相互転送のデッドロック防止）→ ロック内で残高/上限/受取人を再検証。流水仕様：送信側 type=consume/source=points_transfer 負値（balance=前回スナップショット-今回分）、受信側 type=earn/source=points_transfer 正値で expires_at 含む（PointsExpiryTimer で正常に失効可能）；commit 後に受信側へ站内通知 type='points_received'（失敗時は warn のみ）。

#### 2.7 メッセージ通知設定（第19ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/user/notify-settings` | 通知スイッチの照会（5 種全量） |
| PUT | `/api/user/notify-settings` | スイッチの一括更新 (types: {service_reminder: 0/1, ...}) |

**通知スイッチ**: erik_user_notify_setting テーブル（user_id+type 複合ユニークキー、欠落行=デフォルトオン）。5 種：service_reminder サービスリマインダー / card_expiry 期限切れリマインダー（カード+クーポン統一の傘型）/ points_expiry ポイント失効 / marketing マーケティング（予約）/ system システム（オフ不可、PUT で強制的に 1）。ゲート制御：notifySettingEnabled が ServiceReminderTimer/ExpiryReminderTimer/PointsExpiryTimer の 3 タイマープロセス + 購読イベントシナリオマッピング（PAY/REFUND/VERIFIED/RESCHEDULE→system 恒常送信、REMINDER→service_reminder、EXPIRY→card_expiry）に接続；タイプがオフの場合は站内通知と購読メッセージをまとめてスキップ。

---

### 3. スタッフインターフェース（JWT + スタッフ身分が必要）

#### 3.1 スタッフカルテ

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/technician/profile` | スタッフカルテの取得 |
| PUT | `/api/technician/profile` | カルテ更新 (avatar/intro/real_name/gender/id_card/id_card_front/id_card_back) |

初回の完全記入は参入申請とみなされ、status=pending で審査待ち。

#### 3.2 排班

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/technician/schedule` | 排班照会 (?start_date=&end_date=) |
| PUT | `/api/technician/schedule` | 排班設定 (date/time_slots/status)、時間帯が重複すると 422「与已有排班时间冲突」 |
| POST | `/api/technician/schedule/batch` | 一括排班（第23ラウンド）：日付範囲 ≤7 日 + weekdays フィルタ、既存排班のある日はスキップ、レスポンスは created/skipped |

#### 3.3 スタッフ注文

`GET /api/technician/orders` — 注文リスト (?status=&page=1)

#### 3.4 収益

`GET /api/technician/earnings` — 収益概要 (today_income/pending_settlement/balance + 流水リスト)

#### 3.5 出金

`POST /api/technician/withdraw` — 出金申請 (amount)
ルール: 毎月20日に出金可能、T+1で到着、最低金額/100単位制限はバックエンドで設定。

**在途予約（2026-08-26）**: 申請時に残高から在途（pending/approved）分を差し引いて予約；審査・振込前に settled − withdrawn − 在途 ≥ 出金額を再検証；並行審査でも二重入金は発生しない。

#### 3.6 評価への返信（第18ラウンド）

`POST /api/technician/review/reply/{order_id}` — スタッフが評価に返信 (reply)。評価が存在しない/本人以外は統一 404（存在性を漏らさない）；返信済み 422（冪等拒否で上書きしない）；空返信 422。返信成功で站内通知（type='review_reply'）。

#### 3.6 ワークベンチ

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/technician/work/today` | 今日のタスクリスト |
| GET | `/api/technician/work/records` | 完了記録のページネーション |
| POST | `/api/technician/work/{id}/start` | サービス開始 |
| POST | `/api/technician/work/{id}/complete` | サービス完了 |

**今日のタスク**: status ∈ [confirmed, serving]、service_time が今日または空、service_name/price/nickname/avatar を返す。

**完了記録**: status ∈ [serving, completed]、service_end_at 降順、ページネーションレスポンスに meta を含む。

**サービス開始/完了**: 行ロック+ステートマシン検証、冪等操作。開始で service_start_at を書き込み；完了で service_end_at を書き込み、站内通知を送信。エラーコード: 本人以外 403、状態エラー 422、不正 hashid 422。

---

### 4. 注文インターフェース（JWT認証必要）

| メソッド | パス | 説明 |
|------|------|------|
| POST | `/api/order` | 注文作成 (order_type/items/store_id/technician_id/service_time/coupon_id/user_coupon_id/promotion_id/remark) |
| GET | `/api/order/list` | 注文リスト (?status=&page=1) |
| GET | `/api/order/detail/{id}` | 注文詳細 |
| POST | `/api/order/cancel/{id}` | 注文キャンセル (reason) |
| POST | `/api/order/pay/{id}` | 支払い開始 (pay_channel: wechat/balance, use_points: 任意のポイント充当) |
| POST | `/api/order/refund/{id}` | 返金申請 |
| POST | `/api/order/verify/{id}` | 核销 (code: QRコード値) |
| POST | `/api/order/reschedule/{id}` | 予約改期 (new_service_time 必須/reason 任意) |
| GET | `/api/order/logistics/{id}` | 物流追跡（第19ラウンド、product 注文） |
| POST | `/api/order/review/{order_id}` | 評価送信 (rating 1-5/content/images)（第19ラウンドで登録補完） |
| POST | `/api/order/review/{order_id}/append` | 評価追記 (content/images カンマ区切り)（第19ラウンド） |

**注文状態**: pending(待支払い) → paid(支払い済み) → confirmed(確認済み) → serving(サービス中) → completed(完了)

**注文作成時**: Redis SETNX でスタッフを3分間ロック、ページ離脱またはタイムアウトで解放。

**価格改ざん防止（2026-08-26）**: 注文項目の金額はすべてデータベース記録に従う（target_type=service は erik_service を照会、product は erik_product を照会）、クライアント送信価格は計算に参加しない；不明な target_type 422；target_id は hashid エンコード値を渡す必要あり（raw id を渡すと 0 にデコードされ 422「商品不存在或已下架」）；拼团/秒殺価格も同様に DB 基準。

**返金ルール**: 注文から15分以内または開始まで>6hは100%返金 / ≤6hは90% / 開始済みは80% / 開始確認後は返金なし。

**クーポン充当**: 注文作成時に任意で user_coupon_id（hashid）を渡す。エラーコード: 他人のクーポン 404、利用条件不足/期限切れ/下架済み/使用済み 422、不正 hashid 422。充当は2段階：注文時に PriceCalculator.applyCoupon が読み取り検証し充当額を計算して discount_amount に書き込み；支払い成功後に consume がクーポンを used に設定；返金時に restoreCouponAndCard が冪等に返還。

**残高支払いと返金**: 支払いリクエストボディに `pay_channel: "balance"` を渡すとウォレット残高を使用；微信返金と残高返金の両方で金額をウォレット残高に回充。

**ポイント充当**: 支払いリクエストボディに任意で `use_points`（整数）を渡す。SUM 集計でポイント残高を検証（erik_user_points の balance 列は単回増分スナップショットのため、直接残高として使えない）、充当額 = floor(use_points / config('app.points_rate', 100)) 元、実払い金額 = 元の支払額 - 充当額（下限 0.01、支払額超過分は支払額いっぱいまで充当しポイントを無駄にしない）。成功時は type=consume/source=points_offset 消費流水を書き込み（冪等、リトライで二重引き落としなし）。残高不足 422。

**ポイント返還**: キャンセル/返金時に points_offset で消費したポイントを返還（type=earn/source=points_refund）：キャンセルは全額、返金は比率に応じて、5 つの接続ポイントで冪等（refundOffsetPoints）。

**拼团注文（第16ラウンド）**: 注文作成時に任意で `promotion_id`（hashid）を渡す。検証：group_buy タイプのみ、キャンペーン有効期間内、呼び出し元が参加者、未満員（成团済みは 422）、注文サービスとキャンペーンの一致；拼团価格 = 原価 × discount_percent/100、クーポン/回数券/ポイントの併用不可（いずれかを渡すと 422）。注文に promotion_id/participant_id を保存；支払いは完全に `POST /api/order/pay/{id}` を再利用、pay 時にキャンペーン終了（期限到来で未成团）を遅延判定 → 注文を自動キャンセルしスタッフロックを解放。

**秒殺注文（第18ラウンド、廃止）**: ~~注文作成時に `promotion_id`（flash_sale タイプ）を渡す~~ —— 2026-08 より旧プロモーション FLASH_SALE チャネルは削除、store() のプロモーション分岐は拼团 GROUP_BUY のみ（非拼团 promotion 422）；秒殺は統一して第24ラウンドの `/api/seckill` チャネルへ（seckill_id を store トランザクション内の行ロックで在庫引き落とし）、PromotionController::index は flash_sale をフィルタ、show/join は 400 を返す、`Promotion::TYPE_FLASH_SALE` 定数は履歴データ互換のため保持。

**予約改期（第17ラウンド）**: `POST /api/order/reschedule/{id}` に new_service_time（必須）+ reason（任意）を渡し、同じスタッフで時間変更。ルール：本人の注文のみ（本人以外 404）、appointment タイプかつ状態 pending/paid/confirmed のみ変更可（その他 422）、元のサービス開始まで ≥ 6 時間（全額返金ウィンドウと一致）でのみ改期可。並行防御：B1 order_lock（pay/cancel/refund と同一の相互排他ファミリー）→ 新時間帯のスタッフロック Redis SETNX EX 180（並行改期での過剰販売防止）→ トランザクション内の行ロック再読み取り + B2 排班衝突の DB 検証（本注文を除外）→ service_time 更新 + erik_order_reschedule 記録 → 元時間帯ロックを解放、新時間帯ロックは本注文が保持 → SCENE_RESCHEDULE 購読メッセージ（未設定時は站内通知にフォールバック）。失敗時はトランザクションロールバックと同時に新時間帯ロックを解放。

**物流追跡（第19ラウンド）**: `GET /api/order/logistics/{id}` — 本人の product 注文のみ照会可（本人以外/非商品/未発送は統一 404）。order.remark JSON を読み取り（shipping_company/tracking_no/shipped_at、admin MallOrderController::ship() が発送時に書き込み）、parseShippingInfo/parseReceiver の二重パースで旧形式を救済；受取人の携帯番号はマスキング 138****5678。

**評価（第19ラウンド）**: `POST /api/order/review/{order_id}` で評価送信（rating 必須 1-5、content/images 任意）：本人以外 404、非 completed 422、重複評価 400。`POST /api/order/review/{order_id}/append` で追記（content 必須、images カンマ区切り）：評価が存在しない/本人以外は統一 404、非 completed 422、重複追記 422、空内容 422；成功時は append_content/append_images(JSON)/append_at を書き込み、スタッフへ站内通知 type='review_append'、レスポンスに append フィールドを透過。

### 4.1 アフターサービスインターフェース（JWT認証必要）

| メソッド | パス | 説明 |
|------|------|------|
| POST | `/api/aftersales` | アフターサービス申請 (order_id hashid/type: refund|exchange/reason)、本人注文検証 404、状態 paid+completed のみ申請可 422、同一注文の進行中アフターサービス重複 422 |
| GET | `/api/aftersales` | 自分のアフターサービスリスト (?status=&page=1&limit=) |
| GET | `/api/aftersales/{id}` | アフターサービス詳細（帰属検証 404） |

**アフターサービス状態**: pending(審査待ち) → approved(承認) / rejected(却下)。approved は状態遷移のみで、返金アクションは `POST /api/order/refund/{id}` を沿用。

---

### 4.2 拼团/プロモーションインターフェース（JWT認証必要；FLASH_SALE は廃止）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/promotions` | キャンペーンリスト (?type=group_buy；flash_sale はフィルタされ返らない) |
| GET | `/api/promotions/{id}` | キャンペーン詳細（参加人数/成团可否を含む；flash_sale タイプは 400） |
| GET | `/api/promotions/{id}/participants` | 参加リスト |
| POST | `/api/promotions/join/{id}` | キャンペーン参加（第15ラウンドで拡充：レスポンスに discount_percent/original_price/group_price；flash_sale タイプは 400） |

**参加ルール**: group_buy 満員（≥min_people）でロック、成团後の新規参加 422；期限到来で未満員は遅延クローズ（show/join 時に status を 0 に）。join 後の拼团価格での注文は「拼团注文（第16ラウンド）」参照。秒殺は本チャネルを使わず、「24. 秒殺インターフェース」参照。

---

### 5. マーケティングインターフェース（JWT認証必要）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/marketing/coupons` | クーポンリスト (?status=available/used/expired) |
| POST | `/api/marketing/coupons/receive` | クーポン受け取り (coupon_id) |
| GET | `/api/marketing/cards` | 会員カードリスト |
| POST | `/api/marketing/cards/buy` | 会員カード購入 (card_id) |
| GET | `/api/marketing/cards/my` | 自分の回数券リスト |
| POST | `/api/marketing/cards/use` | 回数券核销 (user_card_id/service_id/remark?) |
| GET | `/api/marketing/gift-cards` | ギフトカードリスト |
| GET | `/api/marketing/gift-cards/my` | 自分のギフトカード (redeem記録) |
| POST | `/api/marketing/gift-cards/redeem` | ギフトカード交換 (cashタイプは交換後にウォレット残高へ入金) |
| GET | `/api/marketing/points` | ポイント流水 (?type=earn/use/expire&source=order/referral/gift_card/check_in/admin) |
| GET | `/api/marketing/points-exchange` | ポイント交換商品リスト（上架中 + リアルタイム残り在庫 + 交換済み数） |
| POST | `/api/marketing/points-exchange/{id}` | 交換 (type=coupon はクーポン発行 / wallet は入帳 / gift_card はカードシークレット返却) |
| POST | `/api/marketing/coupons/transfer` | 転贈コード生成 (user_coupon_id: 8桁ユニークコード/7日有効) |
| POST | `/api/marketing/coupons/claim` | 転贈クーポン受け取り (code) |
| GET | `/api/marketing/coupons/transfers` | 転贈記録 (発行 pending/claimed/expired + 受領 claimed) |

**回数券**: cards/my は card_id/name/type/services/total_times/used_times/remaining_times/start_at/end_at/status を返す（リアルタイム計算）。核销成功時は {order_id, usage_id, remaining_times} を返す；エラーコード: 不正 hashid 422、回数不足 422、期限切れ 400、本人以外 404、Redis 重複防止 400。

**ギフトカード**: gift-cards/my は redeem 記録を返す (type/amount/gift_name/status/used_at)。

**ポイントルール**: 明細はページネーション、type フィルタ (earn/use/expire)、source フィルタ (order/referral/gift_card/check_in/admin)。チェックインでポイント付与 (CheckIn, type=earn)；消費で floor(paid_amount×1) ポイント付与、核销時に発行かつ冪等；返金時は比率に応じてポイントを回収。

**ポイント失効（第17ラウンド）**: erik_user_points.expires_at 列（設定 points.expiry_days、デフォルト 365 日、≤0 は失効なし）、すべての earn は保存時に有効期限を記入；PointsExpiryTimer タイマープロセスが 60 秒ごとにカーソルスキャンで失効 earn 行を検出し、type=expire 負値の引き落とし行を書き込み（source=expiry + order_id で元流水をトレース、三重冪等）+ 集約して站内通知「您有 X 积分已过期」；利用可能残高の SUM 口径は expire 負値行を含み、失効ポイントは以後の充当/交換に使えない。

**クーポン転贈（第17ラウンド）**: transfer はクーポンが本人所有/available/クーポン定義が未失効/転贈済みでないことを検証し、8桁の紛らわしい文字を除いたユニーク転贈コードを生成（uk_code ユニークインデックスで兜底）、7日有効。claim は悪用防止：Redis NX ロック（coupon_transfer_claim:{code} 30s）+ 行ロック再検証で二重利用防止、uk_user_coupon ユニークインデックスで同一クーポンの転贈を1回のみに制限、転贈されたクーポンは再転贈不可（新クーポンは転贈記録がないため自然に阻止）、自分の転贈したクーポンの受領不可 422、受取人は元所有者以外；遅延判定で期限切れを expired にして元クーポンを available に復元。claim トランザクション内で元クーポンを used に + 受取人に紐付く新 UserCoupon を生成（coupon_id 不変＝有効期限不変）+ 記録を claimed に。

---

### 6. 通知インターフェース（JWT認証必要）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/notification` | 通知リスト (?type=order/system&page=1) |
| PUT | `/api/notification/read/{id}` | 既読マーク |
| PUT | `/api/notification/read-all` | 全件既読 |

---

### 7. ウォレットインターフェース（JWT認証必要）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/wallet` | ウォレット残高 + 流水のページネーション |
| POST | `/api/wallet/recharge` | チャージオーダー作成 (amount: 元) |
| POST | `/api/wallet/recharge/{id}/pay` | チャージオーダーの支払い開始 (微信) |
| POST | `/api/wallet/transfer` | 残高振込 (to_user_id hashid/amount/remark 任意/client_token 任意)（第19ラウンド） |
| GET | `/api/wallet/transfers` | 振込記録 (?direction=out/in&page=1)（第19ラウンド） |
| GET | `/api/wallet/transfers/{id}` | 振込詳細（双方のみ閲覧可、他人 404）（第19ラウンド） |

**流水**: wallet_txn タイプ: recharge / consume / refund / gift_card / referral_reward(分销返金) / referral_level2(2級返金) / points_exchange(ポイント交換入帳)、ページネーションで返却。

**チャージ**: `POST /api/wallet/recharge` に amount（元）を渡してチャージオーダーを作成、チャージオーダーの hashid を返す。`POST /api/wallet/recharge/{id}/pay` で微信支払いを開始、レスポンスに sign_params を含む（注文支払いモードと同じ）；支払いコールバックは R プレフィックスの out_trade_no でチャージオーダーと注文を区別。

**残高支払い**: 注文支払いリクエストボディに `pay_channel: "balance"` を渡すとウォレット残高を使用；微信返金と残高返金の両方で金額をウォレット残高に回充。

**残高振込（第19ラウンド）**: `POST /api/wallet/transfer` — 受取人の hashid デコード+存在性 404、自分への振込 422、金額 0.01-1000/回 422（DECIMAL 比較で float 禁止）、残高不足 422、単日累計 5000 元 422。並行/冪等：Redis NX ロック wallet_transfer:{from} 30s で転出側を直列化 → トランザクション内で双方のウォレット行を user_id 昇順で lockForUpdate（固定順序でデッドロック防止）→ 転出側を引き落とし + 受取側を増額 + WalletTxn 二重流水（transfer_out/transfer_in は balance_after スナップショット含む）+ 振込記録 completed + 受取側へ站内通知 type='balance_received'（失敗時はログ記録のみ）。client_token 任意：成功後に SETNX 24h で重複送信防止（失敗リクエストは token を記録しないためリトライ可）。

---

### 8. 店長ワークベンチインターフェース（JWT認証必要）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/store-manager/overview` | 今日の概要 (今日の注文数/今日の売上/進行中/スタッフ数/核销数) |
| GET | `/api/store-manager/orders` | 店舗注文リスト (?status=&page=&limit=) |
| GET | `/api/store-manager/technicians` | スタッフリスト（今日の排班含む） |
| GET | `/api/store-manager/revenue` | 直近 7 日の売上集計 |

**store_id 分離**: requireStoreId() が現在のユーザーに店舗紐付け（erik_user.store_id）を強制、店舗なし 403；すべてのクエリは store_id でフィルタ。

---

### 9. 成長レベルインターフェース（JWT認証必要、第20ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/growth` | 現在の成長概要（balance/レベル/次段階までの差額/レベル名） |
| GET | `/api/growth/records` | 成長値流水のページネーション (?page=&limit=) |
| GET | `/api/growth/levels` | 段階リスト（公開、ログイン不要） |

**成長値入帳**: チェックイン +10；評価送信 +20（追記は入帳なし）；消費 floor(paid) で 1 元ごとに 1 ポイント（支払いコールバック内で状態再検証の冪等を再利用、重複コールバックで重複入帳なし）。

### 10. インボイスインターフェース（JWT認証必要、第20ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| POST | `/api/invoices` | インボイス申請 (order_id hashid/order_type: service=サービス/points_exchange=ポイント交換/order_type デフォルト service；金額と宛名はサーバー側で導出、改ざん不可) |
| GET | `/api/invoices` | インボイスリスト (?status=&page=) |
| GET | `/api/invoices/{id}` | インボイス詳細（本人のみ） |

**重複防止**: uk_order_type(order_id, order_type) ユニークキー、同一注文の同一タイプで重複申請 422（MySQL 1062 キャッチの兜底含む）。

### 11. カスタマーサポートチケットインターフェース（JWT認証必要、第20ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| POST | `/api/tickets` | チケット送信 (title/content 必須) |
| GET | `/api/tickets` | チケットリスト (?status=open/closed&page=) |
| GET | `/api/tickets/{id}` | チケット詳細（本人のみ、他人 404） |
| POST | `/api/tickets/{id}/close` | チケットクローズ（本人のみ/open のみ；任意の rating 1-5 満足度、範囲外/非整数 422、未指定は NULL で互換） |

### 12. 予約月カレンダーインターフェース（JWT認証必要、第20ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/calendar/technician/{id}` | 月ビュー (?month=YYYY-MM)：排班 time_slots を時間スロットに展開 + 予約済みを除外 |
| GET | `/api/calendar/technician/{id}/day` | 日ビュー (?date=YYYY-MM-DD)：当日の予約可/予約済み/予約不可スロット明細 |

### 13. インボイス宛名インターフェース（JWT認証必要、第21ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| POST | `/api/invoice-titles` | 宛名保存 (title_type: personal/company；company は tax_no 必須；同一ユーザーの同一宛名重複 422；先頭は自動でデフォルト) |
| GET | `/api/invoice-titles` | 宛名リスト（デフォルトを先頭に） |
| PUT | `/api/invoice-titles/{id}` | 宛名編集（本人のみ） |
| DELETE | `/api/invoice-titles/{id}` | 宛名削除（本人のみ；デフォルト削除後は最古の1件を自動指定） |
| POST | `/api/invoice-titles/{id}/default` | デフォルト設定（トランザクションで同一ユーザーの他行をクリア） |

**申請連携**: POST /api/invoices は任意の title_id をサポート —— 宛名を解決して invoice_title/tax_no/title_type を自動で持ち込み、title_id なしの場合は従来の手入力パスを維持。

### 14. 閲覧履歴インターフェース（JWT認証必要、第21ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/browse-history` | 最近閲覧したサービス（サービス名/表紙/価格/原価を join、viewed_at 降順、per_page デフォルト 15 上限 50） |
| DELETE | `/api/browse-history/{item_id}` | 単件削除（本人のみ、不正/他人 404） |
| DELETE | `/api/browse-history` | 履歴クリア（本人のみ） |

**記録タイミング**: サービス詳細インターフェースのアクセス成功後に自動記録（未ログインはスキップ；重複閲覧は viewed_at を更新するのみで重複挿入しない）。

### 15. 満減キャンペーンインターフェース（第22ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/full-reduction-activities` | 有効な満減キャンペーンリスト（status=1 かつ時間が有効期間内、割引額降順；公開インターフェース） |

**注文時の併用ルール**: 満減は標準注文のみ有効（拼团/秒殺はスキップ）、クーポン/回数券充当後の支払額で敷居（threshold）を判定、併用順は **クーポン/回数券 → 満減 → レベル割引**；割引額最大のキャンペーンを採用；割引額は discount_amount に統合、備考に「满减：满X减Y」を追記；満減後の実払い下限 0.01 元。

### 16. 予約 ICS エクスポート（JWT認証必要、第22ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/order/ics` | 90 日以内の有効注文（pending/paid/confirmed/serving）を iCal（RFC5545）でエクスポート |

**出力**: `Content-Type: text/calendar; charset=utf-8` + `Content-Disposition: attachment; filename="my-appointments.ics"`。VEVENT：UID=注文ID、TZID=Asia/Shanghai、サマリー「预约：サービス名」（欠落時は「预约」にフォールバック）、説明（スタッフ/店舗/住所、欠落はスキップ）、LOCATION 店舗名；テキストは RFC5545 に従ってエスケープ（\, \; \\ \n）+ 75 バイト行折り返し。注文なしは有効な空カレンダーを返す；本人の注文のみエクスポート。

### 17. スタッフ勤怠インターフェース（JWT認証必要、第22ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| POST | `/api/technician/attendance/check-in` | 出勤打刻（当日重複 422、ユニークインデックスで並行を兜底；>10:00 は遅刻マーク） |
| POST | `/api/technician/attendance/check-out` | 退勤打刻（未出勤/既退勤 422、行ロックで並行処理） |
| GET | `/api/technician/attendance` | 当月勤怠リスト + 出勤日数/総労働時間/平均労働時間の集計（?month=YYYY-MM、不正 422） |

### 18. プライバシーコンプライアンスインターフェース（JWT認証必要、第22ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/privacy/data` | データエクスポート（personal/orders/points/wallet_txns/reviews/addresses/invoices をグループ化した JSON；サーバーログはマスキング済み携帯番号+件数のみ記録） |
| POST | `/api/privacy/close-request` | アカウント削除申請（残高非 0 / 未完了注文 / 進行中チケット 422；close_status=1 + close_requested_at を設定） |
| POST | `/api/privacy/close-cancel` | 削除申請のキャンセル（close_status 1→0） |
| POST | `/api/privacy/close-confirm` | 削除確定（72h 経過後のみ可；close_status=2 + close_at + phone/nickname を user{id} に匿名化 + status=0） |

**ログイン遮断**: close_status=2 のアカウントはログイン時に 403「账号已注销」を返す。

### 19. ユーザー健康カルテインターフェース（JWT認証必要、第23ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/health-profile` | 自分の健康カルテ照会（カルテなしは空オブジェクト） |
| PUT | `/api/health-profile` | 作成/更新（upsert、一人一件；allergies/health_notes 上限 500 字、preferred_technician_id の存在性検証；渡されたフィールドのみ更新、レスポンスは hashid エンコード） |
| DELETE | `/api/health-profile` | 自分のカルテ削除（本人のみ） |

フィールド: allergies（アレルギー歴）/health_notes（健康メモ）/preferred_technician_id（希望スタッフ、null 可）。

### 20. ウォレット支払いパスワードインターフェース（JWT認証必要、第23ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| POST | `/api/wallet/pay-password/set` | 支払いパスワード設定（6桁数字 `\d{6}`；設定済みの場合は旧パスワード必須 422 で遮断） |
| POST | `/api/wallet/pay-password/verify` | 支払いパスワード検証（正/誤のブール値を返す、保存しない） |
| POST | `/api/wallet/pay-password/check` | 設定済みかどうかの照会（set: true/false） |

保存: password_hash() ハッシュ + pay_password_set_at、平文は絶対に保存しない。

### 21. 注文状態タイムラインインターフェース（JWT認証必要、第23ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/order/{id}/timeline` | 注文状態変更タイムライン（降順；本人のみ、他人の注文は 404 で存在性を漏らさない） |

埋め込みポイント: 送信/支払い（微信コールバック markOrderPaid の単一消費ポイント）/キャンセル/スタッフ確認/返金申請/返金承認/サービス開始/サービス完了/タイムアウト自動キャンセル/バックエンド操作（operator=admin）の計 8 種の変更。

### 22. ポイントラッキールーレットインターフェース（JWT認証必要、第23ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/wheel/prizes` | ルーレット賞品リスト（weight/stock の機微フィールドを非表示） |
| POST | `/api/wheel/spin` | 抽選 1 回（Redis NX + 行ロックで並行防止；random_int 加重抽選；ポイント→earn 流水に失効時間含む、残高→lockForUpdate で入帳、クーポン→pending で手動発行、無賞→lose；client_token 冪等） |
| GET | `/api/wheel/records` | 自分の抽選記録（ページネーション） |

### 23. ゲストモードインターフェース（第24ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/guest/home` | ホーム集約（カルーセル画像/お知らせ/サービス分類/人気サービス、Redis キャッシュ svc:guest:home 300s） |
| GET | `/api/guest/services` | サービスリスト（?category_id=hashid&sort=newest|sales|price&page/per_page≤50） |
| GET | `/api/guest/services/{id}` | サービス詳細（存在しない 404） |
| GET | `/api/guest/stores` | 店舗リスト |
| GET | `/api/guest/technicians` | スタッフリスト（審査通過のみ；?service_id=hashid フィルタ；評価降順） |

認証不要（ApiVersion ミドルウェアのみ）の未ログイン閲覧エントリ。

### 24. 秒殺インターフェース（JWT認証必要、第24ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/seckill` | 秒殺キャンペーンリスト（status=1 かつ時間窓内；販売済み量 = erik_order.seckill_id の注文数、残り在庫を含む） |
| GET | `/api/seckill/{id}` | キャンペーン詳細（state=not_started/ongoing/ended） |
| POST | `/api/seckill/{id}/buy` | 秒殺注文（client_token 冪等 + Redis NX 30s 並行防止 + キャンペーン検証；在庫は事前引き落とししない） |

**注文ルール（2026-08-26 以降）**: 在庫は `/api/order store()` トランザクション内の行ロックで一律引き落とし、buy は入口検証/冪等のみ；秒殺価格 = seckill_price（DB 基準）、クーポン/ポイント/会員カードと併用不可；注文キャンセルで在庫は回補しない；`/api/order` に直接 seckill_id を渡して注文しても同様に在庫を引き落とす。

### 25. APP バージョンチェックインターフェース（第24ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/api/app/version?platform=android|ios` | 最新バージョンチェック（platform 不正 422；バージョンなしは空オブジェクト；公開インターフェース） |

レスポンス: id/platform/version_code/version_name/force_update（1=強制）/changelog/download_url。

---

## 二、管理バックエンドAPI (admin/ :8787)

リクエストヘッダー: `Authorization: Bearer <admin_token>`, `API-Version: v1`

### ダッシュボード

**`GET /admin/dashboard`** — ダッシュボードデータ

レスポンス: user_count / order_count / technician_count / today_revenue + グラフデータ(注文量/金額/新規ユーザー/アクティビティ)

### ユーザー管理

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/user` | ユーザーリスト (?keyword/status/page/per_page) |
| POST | `/admin/user` | ユーザー追加 |
| GET | `/admin/user/{id}` | ユーザー詳細 |
| PUT | `/admin/user/{id}` | ユーザー編集 |
| DELETE | `/admin/user/{id}` | ユーザー削除 |
| POST | `/admin/user/batch/destroy` | 一括削除 |
| POST | `/admin/user/batch/status` | 一括有効/無効 |

### 会員カード管理

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/member-cards` | カードリスト (?keyword/status/page/per_page) |
| GET | `/admin/member-cards/{id}` | カード詳細 |
| POST | `/admin/member-cards` | カード追加 (services JSON検証) |
| PUT | `/admin/member-cards/{id}` | カード更新/上架下架 |
| DELETE | `/admin/member-cards/{id}` | カード削除 (ユーザー保有中は拒否) |

権限ID: 365-369。

### 店舗ワークベンチ（第15ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/stores/workbench-overview` | 店舗ワークベンチ概要 (?store_id=hashid：今日の注文数/今日の売上/進行中/スタッフ数/今日の核销、口径は service 側と一致) |
| GET | `/admin/orders` | 注文リストに store_id フィルタを追加 (hashid デコード) |

権限ID: 372。

### ポイント交換商品（第16ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/points-exchange-goods` | 商品リスト (?keyword/status/page/per_page) |
| POST | `/admin/points-exchange-goods` | 商品追加 (type=coupon/gift_card/wallet；coupon は hashid を渡し、wallet/gift_card は金額を元で渡す) |
| PUT | `/admin/points-exchange-goods/{id}` | 商品更新 |
| DELETE | `/admin/points-exchange-goods/{id}` | 商品削除 |
| POST | `/admin/points-exchange-goods/{id}/toggle-status` | 上架/下架切替 |
| GET | `/admin/points-exchange-goods/{id}/exchanges` | 交換記録リスト（ユーザー携帯番号 + result スナップショット含む） |

権限ID: 373-378。

### 返金記録（第16ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/referral-rewards` | 返金記録 (?keyword=&page=&limit=、発行済み記録のみ、紹介人/被紹介者のニックネームまたは携帯番号でフィルタ、hashid エンコード) |

権限ID: 379。

### スタッフレベル（第17ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/technician-tiers/logs` | レベル変更ログ（スタッフ名と新旧レベル名を join、hashid エンコード、ページネーション） |

権限ID: 380。

**自動評価**: TierRatingService::evaluate がリアルタイム集計（erik_order completed 注文数 + 評価平均点、四捨五入 1 桁）で profile.order_count/rating を書き戻し、erik_technician_tier_config（min_orders/min_rating）に従って高→低でマッチング、マッチなしは最低レベルに分類。昇格のみで降格なし（降格は歩合率と価格係数に影響するため、バックエンドで人為的にフォローアップ；allowDowngrade=true で人為的再評価をサポート）；冪等（レベル一致時は統計のみ同期）；変更は erik_technician_tier_log に記録 + 站内通知。トリガーポイント：WorkController::complete / ReviewController の評価書き込み / ProfileController のカルテ閲覧時遅延判定。

### 評価返信の閲覧（第18ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/reviews/{id}/reply` | 評価返信詳細（decodeId → find → 404 → decorate 出力；未返信は reply=''、reply/replied_at は toArray で透過；静的ルートが resource より優先） |

権限ID: 381（slug 'get.admin/reviews/{id}/reply'）。

### インボイス管理（第20ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/invoices` | インボイスリスト（?status=pending/issued/rejected&page=） |
| POST | `/admin/invoices/{id}/issue` | 発行 (invoice_no 必須、status→issued + issued_at；冪等：発行済み 422) |
| POST | `/admin/invoices/{id}/reject` | 却下 (reject_reason 必須、status→rejected；pending のみ却下可) |

権限ID: 382 リスト / 383 発行 / 384 却下。

### チケット管理（第20ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/tickets` | チケットリスト（?status=&page=、静的ルートが resource より優先で shadow を回避） |
| POST | `/admin/tickets/{id}/reply` | チケット返信 (content 必須、reply_content/replied_at を書き込み、チケットは open に戻る) |
| GET | `/admin/tickets/satisfaction` | 満足度集計（第21ラウンド）：total/rated_count/unrated_count/average 1桁/1-5星 distribution 欠星は 0 補完；静的ルートが resource より優先 |

権限ID: 385 チケット返信 / 387 チケットリスト閲覧 / 388 チケット満足度統計。

### 評価画像審査（第21ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/review-audit` | 画像付き評価リスト（JSON_LENGTH(images)>0、?status=visible/hidden&page=、ユーザーニックネームとスタッフ名を join、ID は hashid エンコード） |
| POST | `/admin/review-audit/{id}/hide` | 評価を非表示（visible のみ非表示可、それ以外 422；非表示後はユーザー端のスタッフ評価リストで自動的に非表示） |
| POST | `/admin/review-audit/{id}/restore` | 評価を復元（hidden のみ復元可、それ以外 422） |

権限ID: 389 リスト / 390 非表示 / 391 復元。

### 2級返金記録（第20ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/referral-level2` | 2級返金記録（1級紹介人と2級紹介人のニックネームを join、ページネーション） |

権限ID: 386。発行ルール：注文支払い後に1級紹介人の紹介人へ paid×level2_rate（システム設定 referral.level2_rate デフォルト 0.02）を発行、uk_order_referred 冪等で重複防止。

### 勤怠管理（第22ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/attendance` | 勤怠記録（?date=YYYY-MM&name=スタッフ名&page=；real_name を join、ID は hashid エンコード） |
| GET | `/admin/attendance/stats` | スタッフ別のグループ統計（打刻日数/総労働時間/平均労働時間；?date=YYYY-MM、不正 422） |

権限ID: 392 リスト / 393 統計。

### 満減キャンペーン管理（第22ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/full-reduction-activities` | キャンペーンリスト（ページネーション） |
| POST | `/admin/full-reduction-activities` | 追加（threshold/reduction/title/status/start_at/end_at） |
| PUT | `/admin/full-reduction-activities/{id}` | 編集 |
| POST | `/admin/full-reduction-activities/{id}/toggle-status` | 上架/下架 |
| DELETE | `/admin/full-reduction-activities/{id}` | 削除（confirmPassword 必須） |

権限ID: 396 リスト / 397 追加 / 398 編集 / 399 上架下架 / 400 削除（1つの権限レコードが 1 つの method.path slug に対応するため、5 ルート 5 レコード）。

### 分账記録（第22ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/profit-sharing` | 分账記録（注文番号/スタッフニックネームを leftJoin、?status&order_no&technician_name&page=、hashid エンコード） |

権限ID: 394。サーバー側ロジック：erik_system_config group=profit_sharing（enabled/receiver_ratio）；未有効化は disabled でログのみにフォールバック；有効化後は支払い成功で自動的に分账をリクエスト（金額=実払い×receiver_ratio デフォルト 0.7、同一注文の pending/success は冪等でスキップ）；資格情報なしでは HTTP を実行せず、リクエスト構造をログに記録。

### ポイントルーレット管理（第23ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/lucky-wheel` | ルーレット賞品リスト（weight/stock 含む、ページネーション） |
| POST | `/admin/lucky-wheel` | 賞品追加（名称/タイプ points/balance/coupon/none/重み/在庫/画像） |
| GET/PUT | `/admin/lucky-wheel/{id}` | 詳細 / 編集 |
| DELETE | `/admin/lucky-wheel/{id}` | 削除 |
| POST | `/admin/lucky-wheel/{id}/toggle-status` | 上架/下架 |
| GET | `/admin/lucky-wheel/records` | 抽選記録（?status&page=、ユーザーニックネーム/賞品名含む） |

権限ID: 401-406。静的ルート `/lucky-wheel/records` と `/lucky-wheel/{id}/toggle-status` は resource より前に登録し {id} shadow を回避。

### リピーター報酬管理（第24ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/return-customer/config` | 設定閲覧（enabled スイッチ / ratio 比率） |
| PUT | `/admin/return-customer/config` | 設定更新（enabled in:0,1；ratio between:0.01,1） |
| GET | `/admin/return-customer/rewards` | 報酬記録リスト（?keyword スタッフ名/注文番号/ユーザーニックネーム、type=return_customer ページネーション） |

権限ID: 412-414。報酬ルール：ユーザーが同じスタッフに対して 30 日以内の 2 回目の消費（注文完了）でボーナス発行 = 実払い × ratio（デフォルト 0.05）、erik_technician_earnings に記録（type=return_customer、status=pending）し歩合精算チェーンで一括精算；同一注文は冪等で重複発行なし。

### 秒殺キャンペーン管理（第24ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/seckill` | キャンペーンリスト（ページネーション） |
| POST | `/admin/seckill` | キャンペーン追加（name/service_id/seckill_price/original_price/stock/start_at/end_at） |
| GET | `/admin/seckill/{id}` | キャンペーン詳細 |
| PUT | `/admin/seckill/{id}` | 編集 |
| DELETE | `/admin/seckill/{id}` | 削除 |
| POST | `/admin/seckill/{id}/toggle-status` | 上架/下架 |
| GET | `/admin/seckill/{id}/orders` | 秒殺注文リスト |

権限ID: 407-411、420。販売済み量 = erik_order.seckill_id の注文数；在庫は行ロックで引き落とし、売り切れは遮断。

### APP バージョン管理（第24ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/versions` | バージョンリスト |
| POST | `/admin/versions` | バージョン追加（platform/version_code/version_name/force_update/changelog/download_url/status） |
| PUT | `/admin/versions/{id}` | 編集 |
| DELETE | `/admin/versions/{id}` | 削除 |

権限ID: 416-419。更新チェックインターフェース /api/app/version は status=1 のうち最新（updated_at/id 最大）バージョンを取得。

### 排班エクスポート（第24ラウンド）

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/technician-schedule/export` | 排班 CSV エクスポート（UTF-8 BOM、Excel で直接開ける；start_date/end_date 必須かつ期間≤31日；technician_id は任意 hashid） |

権限ID: 415。列：スタッフID/スタッフ名/日付/時間帯明細（time_slots JSON を "09:00-12:00, 14:00-18:00" にパース）。

### ロール権限

| メソッド | パス | 説明 |
|------|------|------|
| GET/POST/PUT/DELETE | `/admin/role` | ロールCRUD |
| GET/POST/PUT/DELETE | `/admin/permission` | 権限CRUD（ツリー型構造）|

### システム設定

| メソッド | パス | 説明 |
|------|------|------|
| GET | `/admin/config` | 設定リスト |
| POST | `/admin/config` | 設定追加 (group/key/value/type/description) |
| PUT | `/admin/config/{id}` | 設定編集 |
| DELETE | `/admin/config/{id}` | 設定削除 |

### 操作ログ

**`GET /admin/log`** — ログ照会

パラメータ: `?user_id/action/source/start_date/end_date/page`

`souce` フィールド: web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### エクスポート

| メソッド | パス | 説明 |
|------|------|------|
| POST | `/admin/export/excel` | Excelエクスポート (type: users/technicians/orders/finance)。機密フィールドは自動マスキング |
| POST | `/admin/export/pdf` | PDFパネルエクスポート (type: dashboard) |

### ファイルアップロード

**`POST /admin/upload`** — ファイルアップロード (multipart/form-data)

### マイページ

| メソッド | パス | 説明 |
|------|------|------|
| PUT | `/admin/profile` | 個人プロフィール変更 |
| PUT | `/admin/profile/password` | パスワード変更 |
| POST | `/admin/profile/logout` | ログアウト |

### インポート

**`POST /admin/import/users`** — ユーザー一括インポート (Excel)

### 監視

| メソッド | パス | 認証 | 説明 |
|------|------|------|------|
| GET | `/health` | なし | ヘルスチェック |
| GET | `/metrics` | なし | Prometheus指標 |
| GET | `/.well-known/security.txt` | なし | セキュリティ連絡先(RFC 9116) |
| GET | `/api/docs` | なし | APIドキュメント |

---

## 三、共通説明

### エラーコード

| code | 説明 |
|------|------|
| 0 | 成功 |
| 401 | 未ログインまたはToken期限切れ |
| 403 | 権限なし |
| 404 | リソースが存在しない |
| 422 | パラメータ検証失敗 |
| 429 | リクエストが多すぎる |

### IDエンコード

- すべてのAPIレスポンスの `id` と `*_id` フィールドは hashids でエンコード
- リクエストに含める `id` パラメータも hashids エンコード形式を使用
- フロントはエンコード文字列を直接使用し、手動デコード不要

### 携帯番号マスキング

レスポンスの携帯番号形式: `138****8000`。Excelエクスポートも同様に処理。

### データ暗号化

- API層: レスポンスの機密フィールドは `erikwang2013/encryption` で暗号化
- DB層: 携帯番号/身分証/微信IDなどは `erikwang2013/encryptable` で自動暗号化/復号

### 環境変数設定

| 変数 | 説明 |
|------|------|
| WECHAT_SUBSCRIBE_TEMPLATE_ID | 予約リマインダー購読メッセージテンプレートID |
| WECHAT_SUBSCRIBE_TEMPLATE_PAID | 支払い成功購読メッセージテンプレートID |
| WECHAT_SUBSCRIBE_TEMPLATE_REFUND | 返金購読メッセージテンプレートID |
| WECHAT_SUBSCRIBE_TEMPLATE_VERIFIED | 核销購読メッセージテンプレートID |
| WECHAT_SUBSCRIBE_TEMPLATE_REMINDER | サービス開始前リマインダー購読メッセージテンプレートID（第18ラウンド） |
| WECHAT_SUBSCRIBE_TEMPLATE_EXPIRY | 会員カード/クーポン期限切れリマインダー購読メッセージテンプレートID（第18ラウンド） |

購読メッセージテンプレート未設定時は自動的に站内通知へフォールバック。

**購読メッセージシナリオ**: SCENE_PAY(支払い成功) / SCENE_REFUND(返金到着) / SCENE_VERIFIED(核销成功) / SCENE_RESCHEDULE(改期成功) / SCENE_REMINDER(サービス開始前リマインダー、第18ラウンド) / SCENE_EXPIRY(期限切れリマインダー、第18ラウンド)。プッシュ成功時のみ push_sent_at を書き込み、失敗時は次回にリトライ。

**チャージ到着通知（第18ラウンド）**: 微信チャージコールバック（R プレフィックス単号）のトランザクション内で站内通知 type='wallet_recharge'「您已成功充值 ¥X.XX」を書き込み；コールバックの冪等を再利用（初回のみ pending→paid でトリガー）、状態変更と同一トランザクションで原子的にコミット、書き込み失敗はメインフローをブロックしない。
