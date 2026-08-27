# API Documentation
> **Languages**: [中文](../API.md) · [한국어](../ko/API.md) · [Русский](../ru/API.md) · [Deutsch](../de/API.md) · [Français](../fr/API.md) · [Español](../es/API.md) · [Português](../pt/API.md) · [हिन्दी](../hi/API.md) · [العربية](../ar/API.md) · [বাংলা](../bn/API.md) · [Bahasa Indonesia](../id/API.md) · [日本語](../ja/API.md)

## Overview

- **Business API** (service/): `http://localhost:8787` — provides business endpoints for the Mini Program/APP
- **Admin API** (admin/): `http://localhost:8787` — provides endpoints for the admin Flutter Web dashboard
- **Authentication**: Bearer Token (JWT), request header `Authorization: Bearer <token>`
- **Versioning**: API version controlled via the `API-Version: v1` request header, not in the URL. Default v1
- **ID encoding**: All ID fields in requests/responses are hashids-encoded, hiding the real database IDs externally
- **OpenAPI docs**: generated with `hg/apidoc`, separate for admin and client

| Side | OpenAPI doc URL | Description |
|------|-----------------|-------------|
| Admin | `GET http://localhost:8787/api/docs` | Full admin API spec (OpenAPI 3.0 JSON) |
| Client | `GET http://localhost:8787/api/docs` | Full business API spec (OpenAPI 3.0 JSON) |

Import the URLs above in tools like Swagger UI for interactive docs.

- **Generic response format**:

```json
{
  "code": 0,
  "message": "操作成功",
  "data": {}
}
```

Paginated response:
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

## 1. Business API (service/ :8787)

### 1. Public Endpoints (No Authentication)

#### 1.1 Captcha

**`POST /api/captcha/send`** — send SMS captcha

Request:
```json
{
  "phone": "13800138000"
}
```
Response: `{"code":0,"message":"验证码已发送","data":null}`

Limits: 1 send per 60 seconds; captcha valid for 5 minutes.

---

#### 1.2 Authentication

**`POST /api/auth/register`** — register by phone number

Request:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "abc123",
  "confirm_password": "abc123",
  "referral_code": "A1B2C3D4"
}
```
Response:
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

**`POST /api/auth/login`** — password login

Request:
```json
{
  "phone": "13800138000",
  "password": "abc123"
}
```
Response: same as register response, includes token and user info.

---

**`POST /api/auth/login-by-code`** — captcha login

Request:
```json
{
  "phone": "13800138000",
  "code": "123456"
}
```
Response: same as login. Unregistered users get an account created automatically.

---

**`POST /api/auth/forget-password`** — forgot password

Request:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "newpass123",
  "confirm_password": "newpass123"
}
```

---

**`POST /api/auth/refresh`** — refresh Token

Request header: `Authorization: Bearer <old token>`
Response: `{"code":0,"data":{"token":"eyJhbGciOi..."}}`

---

#### 1.3 WeChat

**`POST /api/wechat/mini-login`** — Mini Program login

Request: `{"code":"微信登录code"}`
Notes: on first login, `/api/wechat/phone` must be called afterwards to bind the phone number.

---

**`POST /api/wechat/phone`** — bind phone number

Request: `{"code":"微信手机号组件code"}`

---

**`POST /api/wechat/oa-login`** — Official Account login

Request: `{"code":"公众号授权code"}`

---

#### 1.4 Common Services

**`GET /api/common/config`** — public config

Response: includes agreement texts (user agreement/privacy policy/service agreement), About Us info, version number.

---

**`GET /api/common/area`** — city/area list

---

#### 1.5 Service Queries

**`GET /api/service/categories`** — category list

Params: `?parent_id=0`

---

**`GET /api/service/items`** — service item list

Params: `?category_id=&page=1&per_page=10&sort=sales`

---

**`GET /api/service/detail/{id}`** — service details

Response includes: images/name/price/specs/duration/sales/review list.

---

**`GET /api/service/products`** — product list

**`GET /api/service/stores`** — store list

Params: `?lat=&lng=&city=`

---

#### 1.6 Technician Queries

**`GET /api/technician/list`** — technician list

Params: `?lat=&lng=&service_id=&page=1`
Sorted by distance ascending; returns: avatar/name/rating/order count/favorite count/distance/earliest available time/whether serviceable.

---

**`GET /api/technician/detail/{id}`** — technician details

Response includes: images/name/intro/rating/distance/serviceable item list/reviews.

---

**`GET /api/technician/schedule/{id}`** — technician schedule

Params: `?date=2026-05-26`
Returns bookable time slots and availability for that date.

---

#### 1.7 Content

**`GET /api/content/banners`** — banners

Params: `?position=home`

**`GET /api/content/articles`** — announcement/article list

Params: `?type=announcement&page=1`

**`GET /api/content/article/{id}`** — article details

---

#### 1.8 LBS

**`GET /api/lbs/nearby-stores`** — nearby stores

Params: `?lat=&lng=&radius=5000`

**`GET /api/lbs/geocode`** — reverse geocoding

Params: `?lat=&lng=`

---

### 2. User Endpoints (JWT Required)

All endpoints carry the `Authorization: Bearer <token>` request header.

#### 2.1 Profile

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/user/profile` | Get profile |
| PUT | `/api/user/profile` | Update nickname/avatar/gender |
| POST | `/api/user/change-password` | Change password (old_password/new_password/confirm_password) |
| POST | `/api/user/change-phone` | Rebind phone (old_code/new_phone/new_code) |
| POST | `/api/user/cancel-account` | Cancel account (password verification required) |
| POST | `/api/user/logout` | Log out (token added to blacklist) |
| POST | `/api/user/switch-role` | Switch role (role: customer/technician) |

Switching to technician requires an approved technician profile.

#### 2.2 Address Management

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/user/addresses` | Address list |
| POST | `/api/user/addresses` | Add address (contact_name/contact_phone/province/city/district/detail/lat/lng/is_default) |
| GET | `/api/user/addresses/{id}` | Address details |
| PUT | `/api/user/addresses/{id}` | Update address |
| DELETE | `/api/user/addresses/{id}` | Delete address |

Setting one as default automatically un-defaults the others.

#### 2.3 Favorites

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/user/favorites` | Favorite list (?type=service/technician) |
| POST | `/api/user/favorites` | Add favorite (target_type/target_id) |
| DELETE | `/api/user/favorites/{id}` | Remove favorite |

#### 2.4 Feedback

`POST /api/user/feedback` — submit feedback (content + images array)

#### 2.5 Referral

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/user/referral` | Referral info (referral code/referred count/first-order count/points earned) |
| GET | `/api/user/referral/qrcode` | Referral QR code (referral code + invite link) |
| GET | `/api/user/referral/referred-users` | Referred user list |
| GET | `/api/user/referral/earnings` | Distribution commission details (paginated: referee nickname/avatar/order no/amount/disbursement time) |

**Distribution commission**: paid out after the referee's first order reaches completed; amount = paid_amount × reward_rate (appointment_system_config referral.reward_rate, default 0.05, falls back to constant on invalid value). Triple idempotency: row lock + rewarded_at null check + first-order re-check; credited as WalletTxn type=referral_reward.

#### 2.6 Points Transfer (Round 19)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/user/points/transfer` | Transfer points (to_user_id hashid/points) |
| GET | `/api/user/points/transfers` | Transfer records (?direction=sent/received&page=1) |

**Points transfer**: recipient hashid decode + existence 404, self-transfer 422, points 1-10000 422, insufficient balance via SUM aggregate 422, daily cumulative 10000 cap 422. Concurrency protection: Redis NX lock points_transfer:{user} 30s → both parties' last transaction rows lockForUpdate inside a transaction (user_id ascending to prevent mutual-transfer deadlock) → re-verify balance/cap/recipient inside the lock. Transaction records: sender type=consume/source=points_transfer negative value (balance=previous snapshot−this transfer), recipient type=earn/source=points_transfer positive value with expires_at (PointsExpiryTimer can expire normally); after commit, in-app notification to recipient type='points_received' (failure only warns).

#### 2.7 Notification Preferences (Round 19)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/user/notify-settings` | Query notification toggles (all 5 types) |
| PUT | `/api/user/notify-settings` | Batch update toggles (types: {service_reminder: 0/1, ...}) |

**Notification toggles**: appointment_user_notify_setting table (user_id+type composite unique key; missing row = default on). 5 types: service_reminder service reminders / card_expiry expiry reminders (umbrella for cards + coupons) / points_expiry points expiry / marketing (reserved) / system (cannot be off; PUT forces it to 1). Gating: notifySettingEnabled wired into the 3 timer processes ServiceReminderTimer/ExpiryReminderTimer/PointsExpiryTimer + subscribe event scenario mapping (PAY/REFUND/VERIFIED/RESCHEDULE→system always sent, REMINDER→service_reminder, EXPIRY→card_expiry); when a type is off, both in-app notifications and subscribe messages are skipped.

---

### 3. Technician Endpoints (JWT + Technician Identity Required)

#### 3.1 Technician Profile

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/technician/profile` | Get technician profile |
| PUT | `/api/technician/profile` | Update profile (avatar/intro/real_name/gender/id_card/id_card_front/id_card_back) |

First complete fill-in counts as an onboarding application, status=pending awaiting review.

#### 3.2 Schedule

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/technician/schedule` | Schedule query (?start_date=&end_date=) |
| PUT | `/api/technician/schedule` | Set schedule (date/time_slots/status); overlapping slots 422 "与已有排班时间冲突" |
| POST | `/api/technician/schedule/batch` | Batch schedule (Round 23): date range ≤ 7 days + weekdays filter, days with existing schedules skipped, response created/skipped |

#### 3.3 Technician Orders

`GET /api/technician/orders` — order list (?status=&page=1)

#### 3.4 Earnings

`GET /api/technician/earnings` — earnings overview (today_income/pending_settlement/balance + transaction list)

#### 3.5 Withdrawal

`POST /api/technician/withdraw` — apply for withdrawal (amount)
Rules: withdrawable on the 20th of each month, T+1 arrival, minimum amount/whole-hundred limits configured in admin.

**In-transit reservation (2026-08-26)**: on application, balance is immediately reserved in-transit (pending/approved); before approval transfer, re-check settled − withdrawn − in-transit ≥ withdrawal amount; concurrent approvals cannot cause double payouts.

#### 3.6 Review Reply (Round 18)

`POST /api/technician/review/reply/{order_id}` — technician replies to review (reply). Review not found/not own → unified 404 (no existence leak); existing reply 422 (idempotent rejection, no overwrite); empty reply 422. On success, in-app notification to user (type='review_reply').

#### 3.7 Workbench

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/technician/work/today` | Today's task list |
| GET | `/api/technician/work/records` | Completed records, paginated |
| POST | `/api/technician/work/{id}/start` | Start service |
| POST | `/api/technician/work/{id}/complete` | Complete service |

**Today's tasks**: status ∈ [confirmed, serving], service_time is today or empty; returns service_name/price/nickname/avatar.

**Completed records**: status ∈ [serving, completed], sorted by service_end_at descending, paginated response includes meta.

**Start/complete service**: row lock + state machine validation, idempotent. Start writes service_start_at; complete writes service_end_at and sends in-app notification. Error codes: not own 403, wrong status 422, invalid hashid 422.

---

### 4. Order Endpoints (JWT Required)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/order` | Create order (order_type/items/store_id/technician_id/service_time/coupon_id/user_coupon_id/promotion_id/remark) |
| GET | `/api/order/list` | Order list (?status=&page=1) |
| GET | `/api/order/detail/{id}` | Order details |
| POST | `/api/order/cancel/{id}` | Cancel order (reason) |
| POST | `/api/order/pay/{id}` | Initiate payment (pay_channel: wechat/balance, use_points: optional points cash-off) |
| POST | `/api/order/refund/{id}` | Apply for refund |
| POST | `/api/order/verify/{id}` | Verify (code: QR code value) |
| POST | `/api/order/reschedule/{id}` | Reschedule (new_service_time required/reason optional) |
| GET | `/api/order/logistics/{id}` | Logistics tracking (Round 19, product orders) |
| POST | `/api/order/review/{order_id}` | Submit review (rating 1-5/content/images) (registered in Round 19) |
| POST | `/api/order/review/{order_id}/append` | Append review (content/images comma-separated) (Round 19) |

**Order status**: pending → paid → confirmed → serving → completed

**On order creation**: Redis SETNX locks the technician for 3 minutes, released on page exit or timeout.

**Price tamper prevention (2026-08-26)**: order item amounts always come from database records (target_type=service → appointment_service, product → appointment_product); client-sent prices never participate in calculation; unknown target_type 422; target_id must be a hashid-encoded value (raw id decodes to 0 → 422 "商品不存在或已下架"); group-buy/seckill prices likewise DB-based.

**Refund rules**: within 15 min of ordering or > 6h before start → 100% / ≤ 6h → 90% / started → 80% / after confirmed start → no refund.

**Coupon deduction**: optionally pass user_coupon_id (hashid) when creating an order. Error codes: someone else's coupon 404, threshold not met/expired/offline/used 422, invalid hashid 422. Two-stage deduction: at order time PriceCalculator.applyCoupon only validates read-only and writes the computed deduction to discount_amount; after successful payment, consume marks the coupon used; on refund, restoreCouponAndCard returns it idempotently.

**Balance payment and refund**: pass `pay_channel: "balance"` in the pay request body to use wallet balance; both WeChat refunds and balance refunds credit the amount back to the wallet balance.

**Points cash-off**: optionally pass `use_points` (integer) in the pay request body. SUM aggregate validates the points balance (the balance column of appointment_user_points is a per-transaction delta snapshot, not usable directly as balance); deduction = floor(use_points / config('app.points_rate', 100)) yuan, actual payable = original payable − deduction (floor 0.01; if exceeding payable, capped at payable to avoid wasting points). On success writes a type=consume/source=points_offset consumption record (idempotent, retries don't double-deduct). Insufficient balance 422.

**Points refund**: on cancel/refund, points consumed via points_offset are returned (type=earn/source=points_refund): full on cancel, pro-rata on refund, idempotent at 5 hook points (refundOffsetPoints).

**Group-buy order (Round 16)**: optionally pass `promotion_id` (hashid) when creating an order. Validation: group_buy type only, activity within validity window, caller is a participant, not full (locked after formed 422), order service matches activity; group price = original price × discount_percent/100, coupons/session cards/points stacking disabled (passing any → 422). Order stores promotion_id/participant_id; payment fully reuses `POST /api/order/pay/{id}`, with lazy detection at pay time if the activity closed (expired without forming) → order auto-cancelled and technician lock released.

**Seckill order (Round 18, retired)**: ~~create order with `promotion_id` (flash_sale type)~~ — since 2026-08 the legacy FLASH_SALE channel has been removed; the store() promotion branch only handles group-buy GROUP_BUY (non-group-buy promotion 422); seckill uniformly uses the Round-24 `/api/seckill` channel (seckill_id injects stock deduction with row locks inside the store transaction), PromotionController::index filters out flash_sale, show/join return 400 for it, and the `Promotion::TYPE_FLASH_SALE` constant is kept for historical data compatibility.

**Reschedule (Round 17)**: `POST /api/order/reschedule/{id}` with new_service_time (required) + reason (optional), changing time with the same technician. Rules: own order only (not own 404), appointment type only with status pending/paid/confirmed (others 422), ≥ 6 hours before original start (aligned with the full-refund window). Concurrency protection: B1 order_lock (same mutual-exclusion family as pay/cancel/refund) → new slot technician lock Redis SETNX EX 180 (prevents overselling on concurrent reschedules) → row-lock re-read inside transaction + B2 schedule-conflict DB check (excluding this order) → update service_time + write appointment_order_reschedule record → release original slot lock, new slot lock held by this order → SCENE_RESCHEDULE subscribe message (falls back to in-app notification when unconfigured). Failed paths roll back the transaction and release the new slot lock.

**Logistics tracking (Round 19)**: `GET /api/order/logistics/{id}` — only the owner can query product orders (not own/not product/not shipped → unified 404). Reads order.remark JSON (shipping_company/tracking_no/shipped_at, written by admin MallOrderController::ship() on shipment), parseShippingInfo/parseReceiver dual parsing to fall back on old formats; recipient phone masked 138****5678.

**Reviews (Round 19)**: `POST /api/order/review/{order_id}` submits a review (rating required 1-5, content/images optional): not own 404, not completed 422, duplicate review 400. `POST /api/order/review/{order_id}/append` appends (content required, images comma-separated): review not found/not own → unified 404, not completed 422, duplicate append 422, empty content 422; on success writes append_content/append_images(JSON)/append_at and notifies the technician in-app type='review_append', response exposes the append field.

### 4.1 After-Sales Endpoints (JWT Required)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/aftersales` | Apply for after-sales (order_id hashid/type: refund\|exchange/reason); own-order check 404, only status paid+completed eligible 422, in-progress after-sales dedupe 422 |
| GET | `/api/aftersales` | My after-sales list (?status=&page=1&limit=) |
| GET | `/api/aftersales/{id}` | After-sales details (ownership check 404) |

**After-sales status**: pending → approved / rejected. approved is status flow only; the actual refund action reuses `POST /api/order/refund/{id}`.

---

### 4.2 Group-Buy/Promotion Endpoints (JWT Required; FLASH_SALE retired)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/promotions` | Activity list (?type=group_buy; flash_sale filtered out) |
| GET | `/api/promotions/{id}` | Activity details (incl. participant count/whether formed; flash_sale type 400) |
| GET | `/api/promotions/{id}/participants` | Participant list |
| POST | `/api/promotions/join/{id}` | Join activity (Round-15 polish: response includes discount_percent/original_price/group_price; flash_sale type 400) |

**Join rules**: group_buy locks when full (≥ min_people), new joins after formed 422; expired-without-forming lazily closes (status set to 0 on show/join). After joining, order at group price per "Group-Buy Order (Round 16)". Seckill no longer uses this channel, see "24. Seckill Endpoints".

---

### 5. Marketing Endpoints (JWT Required)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/marketing/coupons` | Coupon list (?status=available/used/expired) |
| POST | `/api/marketing/coupons/receive` | Claim coupon (coupon_id) |
| GET | `/api/marketing/cards` | Member card list |
| POST | `/api/marketing/cards/buy` | Buy member card (card_id) |
| GET | `/api/marketing/cards/my` | My session cards |
| POST | `/api/marketing/cards/use` | Verify session card (user_card_id/service_id/remark?) |
| GET | `/api/marketing/gift-cards` | Gift card list |
| GET | `/api/marketing/gift-cards/my` | My gift cards (redeem records) |
| POST | `/api/marketing/gift-cards/redeem` | Redeem gift card (cash type credits wallet balance after redemption) |
| GET | `/api/marketing/points` | Points transactions (?type=earn/use/expire&source=order/referral/gift_card/check_in/admin) |
| GET | `/api/marketing/points-exchange` | Points exchange goods list (on-shelf + real-time remaining stock + redeemed count) |
| POST | `/api/marketing/points-exchange/{id}` | Redeem (type=coupon issues coupon / wallet credits / gift_card returns card secret) |
| POST | `/api/marketing/coupons/transfer` | Generate transfer code (user_coupon_id: 8-char unique code/7-day validity) |
| POST | `/api/marketing/coupons/claim` | Claim transferred coupon (code) |
| GET | `/api/marketing/coupons/transfers` | Transfer records (sent pending/claimed/expired + received claimed) |

**Session cards**: cards/my returns card_id/name/type/services/total_times/used_times/remaining_times/start_at/end_at/status (computed in real time). Successful verification returns {order_id, usage_id, remaining_times}; error codes: invalid hashid 422, insufficient times 422, expired 400, not own 404, Redis dedupe 400.

**Gift cards**: gift-cards/my returns redeem records (type/amount/gift_name/status/used_at).

**Points rules**: paginated transactions, type filter (earn/use/expire), source filter (order/referral/gift_card/check_in/admin). Check-in rewards points (CheckIn, type=earn); purchases reward floor(paid_amount×1) points, issued at verification and idempotent; refunds claw back points pro-rata.

**Points expiry (Round 17)**: appointment_user_points.expires_at column (config points.expiry_days, default 365 days, ≤0 never expires); every earn row gets an expiry written. PointsExpiryTimer timer process cursor-scans expired earn rows every 60s, writes type=expire negative deduction rows (source=expiry + order_id tracing to the original transaction, three-layer idempotency) + aggregated in-app notification "您有 X 积分已过期"; the available balance SUM includes expire negative rows; expired points cannot be used for cash-off/exchange.

**Coupon transfer (Round 17)**: transfer validates the coupon belongs to self/available/definition not expired/not previously transferred, generates an 8-char de-ambiguous unique transfer code (uk_code unique index as fallback), 7-day validity. claim anti-abuse: Redis NX lock (coupon_transfer_claim:{code} 30s) + row-lock re-verification against double-spend, uk_user_coupon unique index limiting one transfer per coupon, transferred coupons cannot be re-transferred (new coupons have no transfer record so naturally blocked), cannot claim your own transferred coupon 422, recipient must not be the original holder; lazy expiry sets expired and restores the original coupon to available. Inside the claim transaction: original coupon set used + new UserCoupon generated bound to the recipient (coupon_id unchanged so validity unchanged) + record set claimed.

---

### 6. Notification Endpoints (JWT Required)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/notification` | Notification list (?type=order/system&page=1) |
| PUT | `/api/notification/read/{id}` | Mark read |
| PUT | `/api/notification/read-all` | Mark all read |

---

### 7. Wallet Endpoints (JWT Required)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/wallet` | Wallet balance + paginated transactions |
| POST | `/api/wallet/recharge` | Create top-up order (amount: yuan) |
| POST | `/api/wallet/recharge/{id}/pay` | Initiate top-up payment (WeChat) |
| POST | `/api/wallet/transfer` | Balance transfer (to_user_id hashid/amount/remark optional/client_token optional) (Round 19) |
| GET | `/api/wallet/transfers` | Transfer records (?direction=out/in&page=1) (Round 19) |
| GET | `/api/wallet/transfers/{id}` | Transfer details (visible to both parties only, others 404) (Round 19) |

**Transactions**: wallet_txn types: recharge / consume / refund / gift_card / referral_reward (distribution commission) / referral_level2 (level-2 commission) / points_exchange (points exchange credit), paginated.

**Top-up**: `POST /api/wallet/recharge` with amount (yuan) creates a top-up order and returns its hashid. `POST /api/wallet/recharge/{id}/pay` initiates WeChat Pay; the response includes sign_params (same as the order payment mode); payment callbacks distinguish top-up orders from orders by the R-prefixed out_trade_no.

**Balance payment**: pass `pay_channel: "balance"` in the order pay request body to use wallet balance; both WeChat refunds and balance refunds credit the amount back to the wallet balance.

**Balance transfer (Round 19)**: `POST /api/wallet/transfer` — recipient hashid decode + existence 404, self-transfer 422, amount 0.01-1000/transaction 422 (DECIMAL comparison, no floats), insufficient balance 422, daily cumulative 5000 yuan 422. Concurrency/idempotency: Redis NX lock wallet_transfer:{from} 30s serializes the sender → inside a transaction both wallet rows lockForUpdate in user_id ascending order (fixed order prevents deadlocks) → debit sender + credit recipient + dual WalletTxn records (transfer_out/transfer_in with balance_after snapshots) + transfer record completed + in-app notification to recipient type='balance_received' (failure only logged). client_token optional: on success SETNX 24h prevents duplicate submission (failed requests don't record the token, so retries work).

---

### 8. Store Manager Workbench Endpoints (JWT Required)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/store-manager/overview` | Today's overview (today's orders/today's revenue/in-progress/technician count/verification count) |
| GET | `/api/store-manager/orders` | Store order list (?status=&page=&limit=) |
| GET | `/api/store-manager/technicians` | Technician list (incl. today's schedule) |
| GET | `/api/store-manager/revenue` | Revenue aggregation for the last 7 days |

**store_id isolation**: requireStoreId() forces the current user to be bound to a store (appointment_user.store_id), no store → 403; all queries filter by store_id.

---

### 9. Growth Level Endpoints (JWT Required, Round 20)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/growth` | Current growth overview (balance/level/next-tier gap/level name) |
| GET | `/api/growth/records` | Growth transactions, paginated (?page=&limit=) |
| GET | `/api/growth/levels` | Tier list (public, no login required) |

**Growth crediting**: check-in +10; review submitted +20 (appends don't credit); purchases floor(paid) 1 point per yuan (reuses status re-verification inside the payment callback for idempotency; duplicate callbacks don't double-credit).

### 10. Invoice Endpoints (JWT Required, Round 20)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/invoices` | Apply for invoice (order_id hashid/order_type: service=service/points_exchange=points exchange; order_type defaults to service; amount and title server-side, not tamperable) |
| GET | `/api/invoices` | Invoice list (?status=&page=) |
| GET | `/api/invoices/{id}` | Invoice details (own only) |

**Dedupe**: uk_order_type(order_id, order_type) unique key; duplicate application for the same order+type 422 (incl. MySQL 1062 catch as fallback).

### 11. Customer Service Ticket Endpoints (JWT Required, Round 20)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/tickets` | Submit ticket (title/content required) |
| GET | `/api/tickets` | Ticket list (?status=open/closed&page=) |
| GET | `/api/tickets/{id}` | Ticket details (own only, others 404) |
| POST | `/api/tickets/{id}/close` | Close ticket (own/open only; optional rating 1-5 satisfaction score, out-of-range/non-integer 422, absent → NULL for compatibility) |

### 12. Appointment Calendar Endpoints (JWT Required, Round 20)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/calendar/technician/{id}` | Month view (?month=YYYY-MM): schedule time_slots expanded to hour slots + booked excluded |
| GET | `/api/calendar/technician/{id}/day` | Day view (?date=YYYY-MM-DD): bookable/booked/unbookable slot details for the day |

### 13. Invoice Title Endpoints (JWT Required, Round 21)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/invoice-titles` | Save title (title_type: personal/company; company requires tax_no; same user + same title duplicate 422; first one auto-default) |
| GET | `/api/invoice-titles` | Title list (default first) |
| PUT | `/api/invoice-titles/{id}` | Edit title (own only) |
| DELETE | `/api/invoice-titles/{id}` | Delete title (own only; deleting the default auto-assigns the earliest one) |
| POST | `/api/invoice-titles/{id}/default` | Set default (transaction clears other rows of the same user) |

**Application integration**: POST /api/invoices supports optional title_id — resolves the title and auto-fills invoice_title/tax_no/title_type; without title_id the original manual-entry path is kept.

### 14. Browse History Endpoints (JWT Required, Round 21)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/browse-history` | Recently viewed services (join service name/cover/price/original price, viewed_at descending, per_page default 15 cap 50) |
| DELETE | `/api/browse-history/{item_id}` | Delete one entry (own only, invalid/others 404) |
| DELETE | `/api/browse-history` | Clear history (own only) |

**Recording**: automatically recorded after a successful service-detail visit (skipped when not logged in; repeat views only refresh viewed_at, no duplicate inserts).

### 15. Full-Reduction Activity Endpoints (Round 22)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/full-reduction-activities` | Active full-reduction activities (status=1 and within validity window, sorted by reduction descending; public endpoint) |

**Order stacking rules**: full-reduction applies only to standard orders (group-buy/seckill skipped), threshold judged on the payable after coupon/session-card deduction, stacking order **coupon/session card → full reduction → tier discount**; picks the activity with the largest reduction; the discount merges into discount_amount, note appends "满减：满X减Y"; payable floor 0.01 yuan after reduction.

### 16. My Appointments ICS Export (JWT Required, Round 22)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/order/ics` | Export valid orders within 90 days (pending/paid/confirmed/serving) as iCal (RFC5545) |

**Output**: `Content-Type: text/calendar; charset=utf-8` + `Content-Disposition: attachment; filename="my-appointments.ics"`. VEVENT: UID=order ID, TZID=Asia/Shanghai, summary "预约：服务名" (falls back to "预约" when missing), description (technician/store/address, skipped when missing), LOCATION store name; text escaped per RFC5545 (\, \; \\ \n) + 75-byte line folding. No orders → valid empty calendar; exports own orders only.

### 17. Technician Attendance Endpoints (JWT Required, Round 22)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/technician/attendance/check-in` | Check in (same-day duplicate 422, unique index guards concurrency; after 10:00 marked late) |
| POST | `/api/technician/attendance/check-out` | Check out (not checked in/already checked out 422, row-lock concurrency) |
| GET | `/api/technician/attendance` | Current month attendance list + work days/total hours/average hours summary (?month=YYYY-MM, invalid 422) |

### 18. Privacy Compliance Endpoints (JWT Required, Round 22)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/privacy/data` | Data export (grouped JSON: personal/orders/points/wallet_txns/reviews/addresses/invoices; server logs only record masked phone + row count) |
| POST | `/api/privacy/close-request` | Apply for account closure (balance non-zero / unfinished orders / open tickets 422; sets close_status=1 + close_requested_at) |
| POST | `/api/privacy/close-cancel` | Cancel closure application (close_status 1→0) |
| POST | `/api/privacy/close-confirm` | Confirm closure (only after 72h; close_status=2 + close_at + phone/nickname anonymized to user{id} + status=0) |

**Login interception**: accounts with close_status=2 get 403 "账号已注销" on login.

### 19. User Health Profile Endpoints (JWT Required, Round 23)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/health-profile` | Get my health profile (empty object when none) |
| PUT | `/api/health-profile` | Create/update (upsert, one per user; allergies/health_notes max 500 chars, preferred_technician_id existence-validated; only updates provided fields, response hashid-encoded) |
| DELETE | `/api/health-profile` | Delete my profile (own only) |

Fields: allergies / health_notes / preferred_technician_id (nullable).

### 20. Wallet Pay Password Endpoints (JWT Required, Round 23)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/wallet/pay-password/set` | Set pay password (6 digits `\d{6}`; when already set, old password required or 422 blocks) |
| POST | `/api/wallet/pay-password/verify` | Verify pay password (returns boolean, nothing stored) |
| POST | `/api/wallet/pay-password/check` | Query whether set (set: true/false) |

Storage: password_hash() hash + pay_password_set_at; plaintext never stored.

### 21. Order Status Timeline Endpoints (JWT Required, Round 23)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/order/{id}/timeline` | Order status change timeline (descending; own only, others' orders 404 without leaking existence) |

Tracked events: submit/pay (WeChat callback markOrderPaid single consumption point)/cancel/technician confirm/refund application/refund approved/service start/service complete/timeout auto-cancel/admin operation (operator=admin), 8 change types in total.

### 22. Points Lucky Wheel Endpoints (JWT Required, Round 23)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/wheel/prizes` | Wheel prize list (hides sensitive weight/stock fields) |
| POST | `/api/wheel/spin` | Spin once (Redis NX + row lock against concurrency; random_int weighted draw; points→earn transaction with expiry, balance→lockForUpdate credit, coupon→pending manual issuance, no-prize→lose; client_token idempotent) |
| GET | `/api/wheel/records` | My spin records (paginated) |

### 23. Guest Mode Endpoints (Round 24)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/guest/home` | Homepage aggregation (banners/announcements/service categories/hot services, Redis cache svc:guest:home 300s) |
| GET | `/api/guest/services` | Service list (?category_id=hashid&sort=newest\|sales\|price&page/per_page≤50) |
| GET | `/api/guest/services/{id}` | Service details (not found 404) |
| GET | `/api/guest/stores` | Store list |
| GET | `/api/guest/technicians` | Technician list (approved only; ?service_id=hashid filter; rating descending) |

Not-logged-in browsing entry requiring no authentication (ApiVersion middleware only).

### 24. Seckill Endpoints (JWT Required, Round 24)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/seckill` | Seckill activity list (status=1 and within time window; includes sold count = appointment_order.seckill_id order count, remaining stock) |
| GET | `/api/seckill/{id}` | Activity details (state=not_started/ongoing/ended) |
| POST | `/api/seckill/{id}/buy` | Seckill order (client_token idempotent + Redis NX 30s against concurrency + activity validation; no longer pre-deducts stock) |

**Ordering rules (from 2026-08-26)**: stock uniformly deducted via row locks inside the `/api/order store()` transaction; buy only does entry validation/idempotency; seckill price = seckill_price (DB-based), no coupon/points/member-card stacking; order cancellation does not restore stock; calling `/api/order` directly with seckill_id also deducts stock.

### 25. APP Version Check Endpoints (Round 24)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/app/version?platform=android|ios` | Latest version check (invalid platform 422; no version → empty object; public endpoint) |

Response: id/platform/version_code/version_name/force_update (1=forced)/changelog/download_url.

---

## 2. Admin API (admin/ :8787)

Request headers: `Authorization: Bearer <admin_token>`, `API-Version: v1`

### Dashboard

**`GET /admin/dashboard`** — dashboard data

Response: user_count / order_count / technician_count / today_revenue + chart data (order volume/amount/new users/activity)

### User Management

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/user` | User list (?keyword/status/page/per_page) |
| POST | `/admin/user` | Add user |
| GET | `/admin/user/{id}` | User details |
| PUT | `/admin/user/{id}` | Edit user |
| DELETE | `/admin/user/{id}` | Delete user |
| POST | `/admin/user/batch/destroy` | Batch delete |
| POST | `/admin/user/batch/status` | Batch enable/disable |

### Member Card Management

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/member-cards` | Card list (?keyword/status/page/per_page) |
| GET | `/admin/member-cards/{id}` | Card details |
| POST | `/admin/member-cards` | Add card (services JSON validated) |
| PUT | `/admin/member-cards/{id}` | Update card / on-off shelf |
| DELETE | `/admin/member-cards/{id}` | Delete card (rejected when users hold it) |

Permission IDs: 365-369.

### Store Workbench (Round 15)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/stores/workbench-overview` | Store workbench overview (?store_id=hashid: today's orders/today's revenue/in-progress/technician count/today's verifications, same metrics as the service side) |
| GET | `/admin/orders` | Order list with store_id filter added (hashid decoded) |

Permission IDs: 372.

### Points Exchange Goods (Round 16)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/points-exchange-goods` | Goods list (?keyword/status/page/per_page) |
| POST | `/admin/points-exchange-goods` | Add goods (type=coupon/gift_card/wallet; coupon passes hashid, wallet/gift_card passes amount in yuan) |
| PUT | `/admin/points-exchange-goods/{id}` | Update goods |
| DELETE | `/admin/points-exchange-goods/{id}` | Delete goods |
| POST | `/admin/points-exchange-goods/{id}/toggle-status` | On-off shelf toggle |
| GET | `/admin/points-exchange-goods/{id}/exchanges` | Exchange records list (incl. user phone + result snapshot) |

Permission IDs: 373-378.

### Referral Commission Records (Round 16)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/referral-rewards` | Commission records (?keyword=&page=&limit=, disbursed records only, filter by referrer/referee nickname or phone, hashid-encoded) |

Permission IDs: 379.

### Technician Tiers (Round 17)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/technician-tiers/logs` | Tier change log (join technician name and old/new tier names, hashid-encoded, paginated) |

Permission IDs: 380.

**Auto assessment**: TierRatingService::evaluate computes real-time stats (appointment_order completed order count + average review rating, rounded to 1 decimal) and writes back profile.order_count/rating, matching appointment_technician_tier_config (min_orders/min_rating) from high to low; no match → lowest tier. Upgrade-only, no downgrades (downgrades affect commission rates and price coefficients, handled manually by admin as fallback; allowDowngrade=true for manual re-assessment); idempotent (same tier only syncs stats); changes write appointment_technician_tier_log + in-app notification. Trigger points: WorkController::complete / ReviewController review writes / ProfileController profile view lazy evaluation.

### Review Reply Viewing (Round 18)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/reviews/{id}/reply` | Review reply details (decodeId → find → 404 → decorate output; unreplied reply='', reply/replied_at exposed via toArray; static route precedes resource) |

Permission IDs: 381 (slug 'get.admin/reviews/{id}/reply').

### Invoice Management (Round 20)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/invoices` | Invoice list (?status=pending/issued/rejected&page=) |
| POST | `/admin/invoices/{id}/issue` | Issue invoice (invoice_no required, status→issued + issued_at; idempotent: already issued 422) |
| POST | `/admin/invoices/{id}/reject` | Reject (reject_reason required, status→rejected; only pending can be rejected) |

Permission IDs: 382 list / 383 issue / 384 reject.

### Ticket Management (Round 20)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/tickets` | Ticket list (?status=&page=, static route precedes resource to avoid shadowing) |
| POST | `/admin/tickets/{id}/reply` | Reply to ticket (content required, writes reply_content/replied_at, ticket back to open) |
| GET | `/admin/tickets/satisfaction` | Satisfaction summary (Round 21): total/rated_count/unrated_count/average 1 decimal/1-5 star distribution with missing stars zero-filled; static route precedes resource |

Permission IDs: 385 ticket reply / 387 ticket list view / 388 ticket satisfaction stats.

### Review Image Audit (Round 21)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/review-audit` | Reviews with images list (JSON_LENGTH(images)>0, ?status=visible/hidden&page=, join user nickname and technician name, IDs hashid-encoded) |
| POST | `/admin/review-audit/{id}/hide` | Hide review (visible only, otherwise 422; once hidden the review is automatically invisible in the user-side technician review list) |
| POST | `/admin/review-audit/{id}/restore` | Restore review (hidden only, otherwise 422) |

Permission IDs: 389 list / 390 hide / 391 restore.

### Level-2 Referral Records (Round 20)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/referral-level2` | Level-2 referral records (join level-1 referrer and level-2 referrer nicknames, paginated) |

Permission IDs: 386. Disbursement rule: after order payment, the level-1 referrer's referrer gets paid×level2_rate (system config referral.level2_rate default 0.02), uk_order_referred idempotency against duplicates.

### Attendance Management (Round 22)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/attendance` | Attendance records (?date=YYYY-MM&name=technician name&page=; join real_name, IDs hashid-encoded) |
| GET | `/admin/attendance/stats` | Per-technician grouped stats (check-in days/total hours/average hours; ?date=YYYY-MM, invalid 422) |

Permission IDs: 392 list / 393 stats.

### Full-Reduction Activity Management (Round 22)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/full-reduction-activities` | Activity list (paginated) |
| POST | `/admin/full-reduction-activities` | Add (threshold/reduction/title/status/start_at/end_at) |
| PUT | `/admin/full-reduction-activities/{id}` | Edit |
| POST | `/admin/full-reduction-activities/{id}/toggle-status` | On-off shelf |
| DELETE | `/admin/full-reduction-activities/{id}` | Delete (with confirmPassword) |

Permission IDs: 396 list / 397 add / 398 edit / 399 on-off shelf / 400 delete (one permission record per method.path slug, so 5 routes 5 records).

### Profit-Sharing Records (Round 22)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/profit-sharing` | Profit-sharing records (leftJoin order no/technician nickname, ?status&order_no&technician_name&page=, hashid-encoded) |

Permission IDs: 394. Server logic: appointment_system_config group=profit_sharing (enabled/receiver_ratio); disabled degrades to log-only; when enabled, payment success auto-requests profit sharing (amount=actual paid×receiver_ratio default 0.7, same-order pending/success idempotent skip); no HTTP execution without credentials, request structure logged.

### Points Lucky Wheel Management (Round 23)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/lucky-wheel` | Wheel prize list (incl. weight/stock, paginated) |
| POST | `/admin/lucky-wheel` | Add prize (name/type points/balance/coupon/none/weight/stock/image) |
| GET/PUT | `/admin/lucky-wheel/{id}` | Details / edit |
| DELETE | `/admin/lucky-wheel/{id}` | Delete |
| POST | `/admin/lucky-wheel/{id}/toggle-status` | On-off shelf |
| GET | `/admin/lucky-wheel/records` | Spin records (?status&page=, incl. user nickname/prize name) |

Permission IDs: 401-406. Static routes `/lucky-wheel/records` and `/lucky-wheel/{id}/toggle-status` registered before the resource to avoid {id} shadowing.

### Return-Customer Reward Management (Round 24)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/return-customer/config` | Config view (enabled toggle / ratio) |
| PUT | `/admin/return-customer/config` | Config update (enabled in:0,1; ratio between:0.01,1) |
| GET | `/admin/return-customer/rewards` | Reward records list (?keyword technician name/order no/user nickname, type=return_customer paginated) |

Permission IDs: 412-414. Reward rule: a user's 2nd purchase with the same technician within 30 days (order completed) pays a bonus = actual paid × ratio (default 0.05), recorded in appointment_technician_earnings (type=return_customer, status=pending) settling together with the commission settlement chain; same-order idempotent, no duplicates.

### Seckill Activity Management (Round 24)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/seckill` | Activity list (paginated) |
| POST | `/admin/seckill` | Add activity (name/service_id/seckill_price/original_price/stock/start_at/end_at) |
| GET | `/admin/seckill/{id}` | Activity details |
| PUT | `/admin/seckill/{id}` | Edit |
| DELETE | `/admin/seckill/{id}` | Delete |
| POST | `/admin/seckill/{id}/toggle-status` | On-off shelf |
| GET | `/admin/seckill/{id}/orders` | Seckill order list |

Permission IDs: 407-411, 420. Sold count = appointment_order.seckill_id order count; stock deducted with row locks, sold-out interception.

### APP Version Management (Round 24)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/versions` | Version list |
| POST | `/admin/versions` | Add version (platform/version_code/version_name/force_update/changelog/download_url/status) |
| PUT | `/admin/versions/{id}` | Edit |
| DELETE | `/admin/versions/{id}` | Delete |

Permission IDs: 416-419. The version check endpoint /api/app/version takes the latest (max updated_at/id) among status=1 versions.

### Schedule Export (Round 24)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/technician-schedule/export` | Schedule CSV export (UTF-8 BOM, opens directly in Excel; start_date/end_date required with span ≤ 31 days; technician_id optional hashid) |

Permission IDs: 415. Columns: technician ID/technician name/date/time slot details (time_slots JSON parsed to "09:00-12:00, 14:00-18:00").

### Roles & Permissions

| Method | Path | Description |
|--------|------|-------------|
| GET/POST/PUT/DELETE | `/admin/role` | Role CRUD |
| GET/POST/PUT/DELETE | `/admin/permission` | Permission CRUD (tree structure) |

### System Config

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/config` | Config list |
| POST | `/admin/config` | Add config (group/key/value/type/description) |
| PUT | `/admin/config/{id}` | Edit config |
| DELETE | `/admin/config/{id}` | Delete config |

### Operation Logs

**`GET /admin/log`** — log query

Params: `?user_id/action/source/start_date/end_date/page`

`source` field: web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### Export

| Method | Path | Description |
|--------|------|-------------|
| POST | `/admin/export/excel` | Excel export (type: users/technicians/orders/finance). Sensitive fields auto-masked |
| POST | `/admin/export/pdf` | PDF panel export (type: dashboard) |

### File Upload

**`POST /admin/upload`** — file upload (multipart/form-data)

### Profile

| Method | Path | Description |
|--------|------|-------------|
| PUT | `/admin/profile` | Update profile |
| PUT | `/admin/profile/password` | Change password |
| POST | `/admin/profile/logout` | Log out |

### Import

**`POST /admin/import/users`** — batch import users (Excel)

### Monitoring

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/health` | None | Health check |
| GET | `/metrics` | None | Prometheus metrics |
| GET | `/.well-known/security.txt` | None | Security contact (RFC 9116) |
| GET | `/api/docs` | None | API docs |

---

## 3. General Notes

### Error Codes

| code | Description |
|------|-------------|
| 0 | Success |
| 401 | Not logged in or token expired |
| 403 | No permission |
| 404 | Resource not found |
| 422 | Parameter validation failed |
| 429 | Too many requests |

### ID Encoding

- All `id` and `*_id` fields in API responses are hashids-encoded
- `id` parameters carried in requests should also use the hashids-encoded format
- The frontend uses the encoded strings directly, no manual decoding needed

### Phone Number Masking

Phone numbers in responses are formatted: `138****8000`. Same treatment for Excel exports.

### Data Encryption

- API layer: sensitive fields in responses encrypted via `erikwang2013/encryption`
- DB layer: phone/ID card/WeChat ID etc. auto-encrypted/decrypted via `erikwang2013/encryptable`

### Environment Variable Config

| Variable | Description |
|----------|-------------|
| WECHAT_SUBSCRIBE_TEMPLATE_ID | Appointment reminder subscribe-message template ID |
| WECHAT_SUBSCRIBE_TEMPLATE_PAID | Payment success subscribe-message template ID |
| WECHAT_SUBSCRIBE_TEMPLATE_REFUND | Refund subscribe-message template ID |
| WECHAT_SUBSCRIBE_TEMPLATE_VERIFIED | Verification subscribe-message template ID |
| WECHAT_SUBSCRIBE_TEMPLATE_REMINDER | Pre-service reminder subscribe-message template ID (Round 18) |
| WECHAT_SUBSCRIBE_TEMPLATE_EXPIRY | Member card/coupon expiry reminder subscribe-message template ID (Round 18) |

When subscribe-message templates are unconfigured, it degrades to in-app notifications automatically.

**Subscribe message scenarios**: SCENE_PAY (payment success) / SCENE_REFUND (refund received) / SCENE_VERIFIED (verification success) / SCENE_RESCHEDULE (reschedule success) / SCENE_REMINDER (pre-service reminder, Round 18) / SCENE_EXPIRY (expiry reminder, Round 18). push_sent_at is written only on successful push; failures retry next round.

**Top-up arrival notification (Round 18)**: the WeChat top-up callback (R-prefixed order no) writes an in-app notification type='wallet_recharge' "您已成功充值 ¥X.XX" inside the transaction; reuses callback idempotency (only the first pending→paid triggers it), atomically committed with the status change in the same transaction; write failure doesn't block the main flow.
