# Feature Description
> **Languages**: [中文](../FEATURES.md) · [한국어](../ko/FEATURES.md) · [Русский](../ru/FEATURES.md) · [Deutsch](../de/FEATURES.md) · [Français](../fr/FEATURES.md) · [Español](../es/FEATURES.md) · [Português](../pt/FEATURES.md) · [हिन्दी](../hi/FEATURES.md) · [العربية](../ar/FEATURES.md) · [বাংলা](../bn/FEATURES.md) · [Bahasa Indonesia](../id/FEATURES.md) · [日本語](../ja/FEATURES.md)

> **Project status**: All complete ✅ | 109 controllers | 103 models | 344 tests (service 240 / admin 104) | WebSocket | payment callbacks | queue calling | assessment | community

## I. User Side (WeChat Mini Program + Flutter APP)

The Mini Program and APP have identical features. A unified account supports customer/technician identity switching.

### 1. Authentication

| Feature | Description |
|------|------|
| Phone registration | Phone + captcha + password + confirm password, supports referral code |
| Password login | Registered phone + password |
| Captcha login | Registered phone + captcha |
| WeChat login | WeChat authorized login, phone binding required on first login |
| Guest mode | Browse without logging in but cannot place orders; registration required to order |
| Forgot password | Change password via captcha |
| User/privacy agreement | Editable in admin dashboard, shown during registration |

### 2. Home

| Feature | Description |
|------|------|
| LBS location | Locates the current region, shows services in that region, supports city switching |
| Banners | Auto-rotating, jump target configured in admin (webpage/detail/no action) |
| Announcements | Scrolling ticker, tap to view the list, added from admin |
| Service categories | Image/name/price/sales, tap to enter detail |
| New-user coupon | Auto-issued upon registration |

### 3. Service Items

| Feature | Description |
|------|------|
| Basic info | Image/name/price/sales/specs/service duration/item details |
| User reviews | Review content shown, can view more |
| Book appointment | Enters the confirm-order page |
| Store selection | In-store service store address (navigation)/business hours/contact phone |
| Technician selection | Technician name/avatar/rating |
| Service time | Select an appointment time slot |
| Off-peak 10% off | 10-12 / 17-18 / after 21:00 |
| Early booking 5% off | 30+ minutes in advance, cannot stack with coupons |
| Coupons | Shows usable amount, use/not use |
| Remarks | Service requirement notes (character-limited) |
| Service agreement | Read and confirm before submitting |

### 4. Product Search & Cart

| Feature | Description |
|------|------|
| Product search | Search by name |
| Category filter | Search by category |
| Product detail | Purchasable quantity/favorite/share/add to cart/buy now |
| Cart | Select/delete/change quantity |

### 5. Orders

| Feature | Description |
|------|------|
| All orders | View by status tab |
| Pending payment | View/pay |
| Pending shipment/self-pickup | Urge shipment/cancel order/view |
| Pending receipt | Logistics info/confirm receipt |
| Pending review | Order detail/text+image review |
| Completed | View order info |
| Refund rules | Within 15min of ordering or >6h before start 100% / <6h before start 90% / after start 80% / after confirmation none |

### 6. Technicians (customer view)

| Feature | Description |
|------|------|
| Technician list | Nearest-first/avatar/name/order count/rating/favorite/distance/available times/book now |
| Technician detail | Images/name/distance/orders/reviews/favorites/available service items |
| Technician onboarding | Fill in info to apply to become a technician, download the technician APP |

### 7. Technician Workbench (after identity switch)

| Feature | Description |
|------|------|
| Today's overview | Today's orders/income overview |
| Schedule settings | Set bookable time slots per day |
| My orders | Booked-not-verified / completed |
| QR verification | Scan user QR code to verify sessions |
| Member management | List of served members/class-consumption data/session cards/profile editing |
| Earnings management | Today's income/settling/wallet balance |
| In-transit funds | Verified but unsettled, auto-confirmed after 3 days |
| Withdrawals | On the 20th of each month, T+1 to WeChat change; admin review, two-level approval for amounts ≥500 (store manager → finance); in-transit reservation at application, recheck before approval transfer, concurrent approvals cannot double-pay (2026-08-26 hardening) |
| Attendance | Clock-in/clock-out/cleanliness photo upload |
| Return-customer rewards | Bonus recorded for second purchase within 30 days |
| Professional training | Video courses/text courses |
| Today's tasks | WorkController today: real-time fetch of today's to-dos |
| Completion records | WorkController records: historical completion records |
| Start/complete service | WorkController start/complete: row lock + state-machine guard + idempotency, auto in-app notification on completion |
| Mini Program technician workbench | tech-work 3 tabs: QR verification/today's tasks/completion records |

### 8. Personal Center

| Feature | Description |
|------|------|
| Personal info | Avatar/nickname/phone |
| Identity switching | Customer ↔ technician |
| Notifications | In-app notifications (appointment_notification); message center page: paging/pull-to-refresh/unread highlight/mark read/mark all read |
| My member cards | Monthly/VIP yearly/session cards (expiry/times/used/remaining) |
| My points | Earned records/available points/usage records (1:100 exchange for gift cards); check-in/consumption returns points, refunds claw back proportionally, paged details + type/source filters |
| My gift cards | Cash cards/physical gifts; cash type redemption tops up the wallet directly |
| Coupons | Claimed available/used/expired |
| My favorites | Favorited service items |
| Follow official account | QR popup, long-press to save |
| User referral | Referral info/QR poster/referred user list/points rewards |
| Feedback | Text+image submission, replied within 24h |
| About us | LOGO/intro/service phone/website/email |

### 9. Settings

| Feature | Description |
|------|------|
| Change password | Current password + new password + confirm new password |
| Change phone | Current phone captcha + new phone captcha |
| User agreement | Text display, editable in admin |
| Privacy agreement | Text display, editable in admin |
| Check for updates | Version number + update |
| Account deletion | Deletion notice + confirmation |
| Log out | Clears login state |

### 10. Stored-Value Wallet (Round 6)

| Feature | Description |
|------|------|
| Wallet balance | GET /api/wallet balance + transactions (user_wallet/wallet_recharge/wallet_txn tables) |
| Top-up | POST /api/wallet/recharge creates a top-up order; POST /api/wallet/recharge/{id}/pay WeChat Pay top-up, callback uses R-prefixed order number |
| Balance payment | Order payment channel pay_channel=balance |
| Refund top-up | WeChat/balance refunds automatically top up the wallet (refundToBalance / creditRefundToWallet) |

### 11. Subscribe Messages (Rounds 6+8)

| Feature | Description |
|------|------|
| Subscribe scenarios | Order events, 3 scenarios: payment success / refund received / verification success |
| Idempotency | push_sent_at marker prevents duplicate pushes |
| Fallback | Auto fallback to in-app notification when subscribe template not configured |

### 12. Session-Card Verification Loop (Round 8)

| Feature | Description |
|------|------|
| My session cards | GET /api/marketing/cards/my real-time used_up/expired calculation |
| Verify & deduct | POST /api/marketing/cards/use: Redis NX idempotency + lockForUpdate row lock, directly creates completed order + OrderItem + OrderPayment(pay_type='card') |

### 13. Coupon Deduction (Round 9)

| Feature | Description |
|------|------|
| Coupon on ordering | Optionally pass user_coupon_id, PriceCalculator.applyCoupon read-only validation + amount calc |
| Coupon types | fixed fixed amount / percent percentage, min_amount threshold |
| Consumption & return | consume sets used on payment success; refund restoreCouponAndCard idempotent return |

### 14. Gift Cards (Round 9)

| Feature | Description |
|------|------|
| Redeem | redeem: cash type tops up the wallet (row lock prevents double crediting, WalletTxn type='gift_card'), gift type only marked |
| My gift cards | GET /api/marketing/gift-cards/my |

### 15. Points System (Rounds 9+10)

| Feature | Description |
|------|------|
| Check-in points | CheckIn daily check-in |
| Consumption points | floor(paid×1) on verification, order_id idempotent, balance snapshot |
| Refund clawback | clawbackOrderPoints proportional clawback (3 hook points) |
| Points for cash | Pass use_points at payment, 100 points = 1 CNY (config app.points_rate), SUM aggregate balance check, consumption record source=points_offset idempotent |
| Points restitution (Round 15) | Cancel/refund returns points_offset points: refundOffsetPoints 5 hook points (doCancel 3 paths/doRefund WeChat transaction/creditRefundToWallet/completeOneRefundCompensation), source=points_refund idempotent |
| Points details | GET /api/marketing/points paged + type/source filters, type unified as earn |

### 16. Mini Program Order Flow (Round 10)

| Feature | Description |
|------|------|
| Service detail page | service/detail |
| Confirm-order page | order/confirm: coupon selection/threshold gray-out/client-side estimated amount → POST /order → WeChat/balance payment |
| Page scale | Mini Program now has 20 pages |

### 17. User-Side Three Entries (Round 10)

| Feature | Description |
|------|------|
| Favorites | favorite page (user page entry) |
| Referral | referral: invite code/link copy/referred user list |
| Feedback | feedback form |

### 18. Subscribe Message Authorization (Round 14)

| Feature | Description |
|------|------|
| Subscribe authorization | utils/subscribe.js centrally manages template IDs (keys aligned with server appointment_system_config.wechat_app.template_ids) |
| Trigger scenarios | wx.requestSubscribeMessage inside gesture callbacks after booking/payment success; silent when template ID not configured or user rejects |
| Server chain | WechatTemplateMessageService sends + NotificationReminderService 2h~1h pre-appointment reminder + AutoCancelTimer process scan |

### 19. After-Sales Returns/Exchanges (Round 14)

| Feature | Description |
|------|------|
| Apply for after-sales | POST /api/aftersales: type=refund/exchange, validates own order/paid+completed/dedup per order |
| My after-sales | GET /api/aftersales paged list + GET /api/aftersales/{id} detail |
| Review flow | Admin approve/reject (rejected requires remark); approved only transitions status, refund reuses the order refund endpoint |

### 20. Group Buy / Flash Sale (Round 15)

> As of 2026-08 the FLASH_SALE channel is retired: PromotionController::index filters out flash_sale, show/join return 400 for it, flash sales uniformly use the "43. Flash Sale (Round 24)" channel; the `Promotion::TYPE_FLASH_SALE` constant remains for historical data compatibility. This section and "27. Flash Sale Ordering" are historical records.

| Feature | Description |
|------|------|
| Activity list/detail | GET /api/promotions + /api/promotions/{id}, type filter group_buy/flash_sale |
| Join | POST /api/promotions/join/{id}: Redis NX lock prevents oversell (flash_sale uses max_people as stock cap), duplicate join 422, group_buy full-team lock, lazy close on expiry without full team (status set to 0 on show/join) |
| Participant list | GET /api/promotions/{id}/participants |
| Status fix | PromotionParticipant status switched to integer constants 0/1/2/3 (fixes strict-mode join 1366 corruption) |

### 21. Group-Buy Ordering (Round 16)

| Feature | Description |
|------|------|
| Group price | join response returns discount_percent/original_price/group_price |
| Group order | POST /api/order passes promotion_id: validates only group_buy/activity valid/caller is participant/not full/service match; group price = original × discount_percent/100, disables coupon/session-card/points stacking (422) |
| Order marking | appointment_order new promotion_id/participant_id columns + index |
| Failed group handling | Expiry without full team → activity closed + batch cancel of that activity's pending orders (idempotent); pay() lazy-checks closure and auto-cancels the order, releasing the technician lock |

### 22. Referral Commission (Round 16)

| Feature | Description |
|------|------|
| Payout rule | Paid after referred user's first order completes: amount = paid_amount×reward_rate (appointment_system_config referral.reward_rate default 0.05, invalid values fall back to constant), only paid when >0 |
| Hook point | ReferralRewardService::handleOrderCompleted hooked into WorkController::complete transaction (serving→completed sole entry; verify only reaches serving and does not trigger); failure rolls back entirely and can retry |
| Idempotency | appointment_user_referral row lock lockForUpdate + rewarded_at null check + in-lock first-order recheck (concurrent/duplicate calls pay once) |
| Crediting | Wallet row lock accumulation + WalletTxn type='referral_reward' (balance_after + order number remark); referral record writes reward_type/reward_amount/rewarded_at/first_order_at |
| Details | GET /api/user/referral/earnings paged (referred user nickname/avatar/order number/amount/time) |

### 23. Points Redemption Mall (Round 16)

| Feature | Description |
|------|------|
| Exchange goods | appointment_points_exchange_goods: type=coupon/gift_card/wallet, points_cost/value (DECIMAL(25,2) prevents avalanche ID precision loss)/stock/status |
| Goods list | GET /api/marketing/points-exchange: on-shelf goods + real-time remaining stock + redeemed count |
| Exchange | POST /api/marketing/points-exchange/{id}: Redis NX lock + goods row lock prevents over-redemption; points SUM check (insufficient 422) + UserPoints type='consume' source='exchange' deduction; coupon issue / wallet credit (WalletTxn points_exchange) / gift_card card-key returned |
| Idempotency | uk_user_goods unique index limits once per user per goods + in-lock recheck + 1062 fallback; exchange record snapshot appointment_user_points_exchange |

### 24. Appointment Rescheduling (Round 17)

| Feature | Description |
|------|------|
| Endpoint | POST /api/order/reschedule/{id}: new_service_time (required) + reason (optional), same-technician time change |
| Rules | Own orders only (not owner 404); appointment type only with status pending/paid/confirmed (else 422); ≥ 6 hours before original start (aligned with the full-refund window) |
| Concurrency protection | B1 order_lock (same mutex family as pay/cancel/refund) → new-slot technician lock Redis SETNX EX 180 (concurrent reschedules prevent oversell) → in-transaction row-lock re-read + B2 schedule conflict DB check (excluding this order) |
| Wrap-up | Update service_time + record appointment_order_reschedule (with reason) + release original/new slot locks held by this order; on failure the transaction rolls back and the new-slot lock is also released |
| Notification | SCENE_RESCHEDULE subscribe message (fallback to in-app notification "预约改期成功" when template not configured) + pushOrderUpdate |

### 25. Coupon Gifting (Round 17)

| Feature | Description |
|------|------|
| Endpoints | POST /api/marketing/coupons/transfer (user_coupon_id) generates an 8-character de-obfuscated unique gift code (uk_code fallback, valid 7 days); POST /api/marketing/coupons/claim (code) claims; GET /api/marketing/coupons/transfers sent (pending/claimed/expired) + received (claimed) paged |
| Validation | Coupon belongs to caller/available/coupon definition not expired/not previously gifted (422); cannot claim own gifted coupon, recipient must not be the original holder |
| Anti-abuse | Redis NX lock coupon_transfer_claim:{code} (30s) + in-transaction row-lock recheck prevents double-spend; uk_user_coupon unique index limits one gift per coupon; gifted coupons cannot be re-gifted (new coupons have no gift record, naturally blocked); lazy expiry sets expired + restores original coupon to available |
| Claiming | In-transaction: original coupon set used + new UserCoupon created bound to recipient (coupon_id unchanged so validity unchanged) + gift record set claimed |

### 26. Points Expiry (Round 17)

| Feature | Description |
|------|------|
| Validity | appointment_user_points.expires_at column; all earns (check-in/consumption/restitution) store expires_at = now + points.expiry_days (default 365, ≤0 never expires); consume/use records leave it null |
| Expiry execution | PointsExpiryTimer scheduled process scans every 60s with cursor (100/batch) earn rows where expires_at < now → writes type=expire negative deduction rows (source=expiry + order_id tracing original record) → aggregated per-user in-app notification "You have X points expired" |
| Idempotency | ① expire row order_id points to the original earn record, lockForUpdate + exists recheck on the original row inside the transaction (concurrent processes serialize on the row lock) ② id cursor paging ③ notifications only produced in actual deduction rounds |
| Accounting | Available balance SUM aggregate includes expire negative rows; expired points cannot be used for cash offset/exchange |

### 27. Flash Sale Ordering (Round 18, retired)

> Superseded by the Round-24 `/api/seckill` channel (store() promo branch now only handles group buying); see "43. Flash Sale".

| Feature | Description |
|------|------|
| Endpoint | POST /api/order passes promotion_id (flash_sale type): flash price = round(total × (100 − discount_percent)/100, 2), consistent with PromotionController's flash price |
| Validation | Type whitelist [group_buy, flash_sale] (else 422); activity ongoing; caller is participant; order service matches activity; sold out participants_count ≥ max_people 422 "已抢光"; coupon/session-card/points stacking disabled 422 |
| Expiry | pay() lazy-check isFlashSaleClosed (same pattern as isGroupBuyClosed): flash expired → activity set to 0 + batch cancel that activity's pending orders + this order auto-cancelled + technician lock released 422 |

### 28. Service Reminder + Expiry Reminder (Round 18)

| Feature | Description |
|------|------|
| Pre-service reminder | ServiceReminderTimer 60s scans service_time ∈ [now+1h, now+1h+60s), status confirmed/serving, appointment-type orders → in-app notification (type='service_reminder', with service/technician/store/time) + SCENE_REMINDER subscribe message |
| Expiry reminder | ExpiryReminderTimer 6h scans end_at ∈ (now, now+3d+6h]: active member cards (type='card_expiry') + available coupons (type='coupon_expiry', whereHas the linked coupon definition's end_at) + SCENE_EXPIRY subscribe message |
| Idempotency | Both use id cursor 100/batch + in-transaction row-lock recheck + notification dedup (order_id column records source id/order id as dedup key); push_sent_at only written after subscribe push succeeds, retried next round on failure |
| Fallback | Template not configured (WECHAT_SUBSCRIBE_TEMPLATE_REMINDER / _EXPIRY) auto-falls back to in-app notification only |

### 29. Technician Review Reply (Round 18)

| Feature | Description |
|------|------|
| Endpoint | POST /api/technician/review/reply/{order_id} (technician-identity middleware): review missing/not own unified 404; existing reply 422 (idempotent reject, no overwrite); empty reply 422 |
| After reply | In-app notification to user (type='review_reply', non-blocking try/catch + Log) |
| Data | appointment_order_review idempotently gains replied_at column (reply column existed at table creation); admin review list/show exposes reply/replied_at via decorate()->toArray() |

### 30. Recharge Arrival Notification (Round 18)

| Feature | Description |
|------|------|
| Endpoint | Inside the WeChat top-up callback (R-prefixed order number) handleRechargeNotify transaction: after WalletTxn, writes in-app notification type='wallet_recharge', "You have successfully topped up ¥X.XX" (amount in yuan, number_format 2 digits) |
| Idempotency | Reuses existing callback idempotency (top-up row lockForUpdate + status recheck, only first pending→paid reaches the notification); notification and status change committed atomically in the same transaction, no crash window; signature failure/missing order/amount mismatch writes no notification |
| Fault tolerance | Notification write in try/catch, failure only logs a warning and does not block the main flow |

### 31. Balance Transfer (Round 19)

| Feature | Description |
|------|------|
| Endpoint | POST /api/wallet/transfer: recipient hashid decode + existence 404, to self 422, amount 0.01-1000 per transfer 422 (DECIMAL comparison, no float), insufficient balance 422, 5000/day cumulative 422 |
| Concurrency/idempotency | Redis NX lock wallet_transfer:{from} 30s serializes the sender; in-transaction both wallet rows lockForUpdate by user_id ascending (fixed order prevents deadlock); client_token SETNX 24h after success prevents resubmission (failed requests don't store the token, can retry) |
| Crediting | Deduct sender + credit recipient + WalletTxn double records (transfer_out/transfer_in with balance_after snapshot) + transfer record completed + recipient in-app notification type='balance_received' (failure only logs) |
| Records | GET /api/wallet/transfers (direction=out/in paged) + GET /transfers/{id} (visible to both parties only 404) |

### 32. Points Transfer (Round 19)

| Feature | Description |
|------|------|
| Endpoint | POST /api/user/points/transfer: recipient existence 404, to self 422, points 1-10000 422, SUM aggregate balance insufficient 422, 10000/day limit 422 |
| Concurrency/idempotency | Redis NX lock points_transfer:{user} 30s; in-transaction both parties' last records lockForUpdate (user_id ascending prevents mutual-transfer deadlock) + in-lock recheck of balance/limit/recipient |
| Record conventions | Sender type=consume source=points_transfer negative (balance=previous snapshot − this amount, same convention as points_offset/exchange); recipient type=earn source=points_transfer positive with expires_at (PointsExpiryTimer can expire normally); transfer record written in-transaction, in-app notification to recipient type='points_received' after commit |
| Records | GET /api/user/points/transfers (direction=sent/received paged, with counterparty nickname) |

### 33. Review Follow-up + Submission Route Completion (Round 19)

| Feature | Description |
|------|------|
| Follow-up | POST /api/order/review/{order_id}/append: review missing/not own unified 404, non-completed 422, duplicate follow-up 422 (rejected if either append_content/append_at is non-null), empty content 422; on success writes append_content/append_images(JSON)/append_at + technician in-app notification type='review_append' |
| Submit review | Registered the missing POST /api/order/review/{order_id} (ReviewController::store previously had no route, unreachable); also fixed the latent TypeError: findByOrderId received int violating the string signature (aligned with the (string) cast in append), which would 500 the moment the route was registered |
| Data | appointment_order_review gains append_content TEXT/append_images JSON/append_at DATETIME columns (idempotent migration); response exposes append fields |

### 34. User-Side Logistics Tracking (Round 19)

| Feature | Description |
|------|------|
| Endpoint | GET /api/order/logistics/{id}: own product orders only (not owner/not product/not shipped unified 404) |
| Data | Reads order.remark JSON (shipping_company/tracking_no/shipped_at, written by admin MallOrderController::ship() on shipment); parseShippingInfo/parseReceiver double parsing covers legacy formats |
| Masking | Recipient phone maskPhone (138****5678) prevents leakage |

### 35. Notification Preferences (Round 19)

| Feature | Description |
|------|------|
| Data | appointment_user_notify_setting table (user_id+type composite unique key uk_user_type, missing row = default on); 5 types: service_reminder / card_expiry (unified umbrella for cards+coupons) / points_expiry / marketing (reserved) / system (cannot be turned off, PUT forces 1) |
| Endpoints | GET /api/user/notify-settings returns all 5 switches; PUT batch upsert produces no duplicate rows |
| Gating | NotificationReminderService::notifySettingEnabled hooks into 3 timer processes (ServiceReminderTimer/ExpiryReminderTimer cards+coupons/PointsExpiryTimer — timers insert directly into appointment_notification, bypassing the service write path, so each adds the same gate) + subscribe events (sendSubscribeForOrderEvent/Notification scenario mapping PAY/REFUND/VERIFIED/RESCHEDULE→system always sent, REMINDER→service_reminder, EXPIRY→card_expiry); when a type is off, both in-app notifications and subscribe messages are skipped |

---

## II. Admin Dashboard (PC Web)

Flutter Web single-page app with 21 pages: dashboard/users/roles/config/logs/verification/schedule/services/technicians/orders/coupons/members/session cards/announcements/FAQ/withdrawals/reviews/reports/after-sales/store workbench/profile.

### 1. Home Dashboard

- 7 dynamically rendered stat cards: total users/new today/active users/operation logs/appointments today/pending withdrawals/pending technicians
- 30-day trend charts: order volume/amount/new users/activity
- User-status distribution pie: enabled/disabled
- Latest 10 operation logs
- Quick navigation: pending-module buttons
- In-app messages: new order notifications/refund notifications

### 2. Technician Management

- Technician list: UID/phone/name/region/registration-time search
- List display: number/UID/phone/nickname/referrer/status/student count/performance/account status/registration time/last login/region
- Actions: export/change superior/view subordinates/change password or phone/schedule management/service item settings/course progress view
- Add: name/gender/phone/ID card/ID card photos
- Review onboarding applications

### 3. User Management

- Member list: name/phone/avatar/level/consumption amount
- Search: UID/phone/nickname/registration time
- Actions: detail/change superior/view subordinates/change password or phone/set member level

### 4. Store Management

- Store list: enable-disable/delete
- Add store: name/address/coordinates/phone/business hours/images

### 5. Service Management

- Service list: name/category search; number/name/type/discount/min price/sales/cover/sort/status/time
- Actions: add/edit/delete/card design
- Product list: type/name/discount/min price/sales/stock/cover/sort/status/time

### 6. Mall Management

- Mall orders: detail/ship/logistics/print
- After-sales orders: view/review/print
- Review management: view/review (show/hide)/delete (ReviewController index/show/audit/destroy)
- Payment transactions
- Sales statistics

### 7. Order Management

- Pending-use orders: multi-condition search
- Actions: detail/platform cancel/confirm completion

### 8. Coupon Activities

- List: sort/image/type/name/on-off shelf/total/remaining/admin/time/end date
- Actions: add/edit/delete

### 9. Finance

- Order profit sharing: search/detail
- Technician withdrawals: WithdrawalController review; two-level approval for amounts ≥500 (store manager store_approved_at → finance finance_approved_at); state machine pending→approved→completed (rejected/failed)
- Commission settings: change commission rate/settlement cycle/rewards & penalties/balance
- Income & expense records
- Withdrawal account management
- Withdrawal limit configuration

### 10. Content Management

- Banner CRUD
- About us settings
- Moments moderation
- FAQ CRUD
- Feedback handling
- Platform announcement CRUD

### 11. Settings

- Platform agreement editing (user/privacy/service agreements)
- Unified technician commission settings
- System message templates (incl. Mini Program subscribe message template config, auto fallback to in-app notification when not configured)
- Sub-account permission management (store managers can issue coupons + schedules)

### 12. Extended Features

- Card design: service+product combos/manual fee/commission settings
- System monitoring: CPU/memory/disk/Redis/MySQL/queue real-time dashboard
- IP blacklist: security-php attack records visualization + manual ban
- Database backup: Web UI backup/download/restore
- Customer profiles: 360° view/consumption preferences/segmented marketing
- Batch push: template messages/segmented broadcast
- Refund review flow: two-level approval (store manager → finance)
- Technician tiers: junior/senior/expert auto rating
- Scheduled tasks: auto cancel/settle/expiry handling
- SMS config: Aliyun/Tencent multi-channel management
- Storage config: local/OSS/COS/CDN
- Report enhancement: custom fields/scheduled email reports
- Schedule export: Excel export of appointment records/attendance lists
- Technician gender restriction: gender control for specific items
- Technician training: course management/learning progress tracking
- Store manager accounts: store_id data isolation + dedicated permissions

### 13. Data Reports (Round 7)

- ReportController 3 endpoints: order statistics (summary + daily trend) / technician TOP10 / channel distribution (payment channels + order status)
- Redis cache svc:admin_report:{type}:{start}:{end}, TTL 300

### 14. Member Card Management (Round 10)

- appointment_user.member_level member-level column (migration 000008)
- MemberCardController full CRUD (permissions 365-369): GET/POST/PUT/DELETE /admin/member-cards
- Flutter member card definition management page

### 15. After-Sales Management (Round 14)

- appointment_order_aftersale table (migration 000009): type=refund/exchange, status=pending/approved/rejected/completed
- AftersaleController: GET /admin/aftersales (paged + status/uid/order_no filters) + POST /admin/aftersales/{id}/review (approve/reject+remark)
- Flutter after-sales management page (list + review dialog, permissions 370/371), layout registered

### 16. Store Manager Workbench (Round 15)

- service /api/store-manager: overview (today's orders/revenue/in-progress/technician count/verification count) + orders (paged + status filter) + technicians (with today's schedule) + revenue (last 7 days aggregated), requireStoreId() enforces store_id isolation (403 without store)
- admin StoreController::workbenchOverview (GET /admin/stores/workbench-overview?store_id=, consistent with service) + AppointmentOrderController order list store_id filter (hashid decode)
- Flutter store workbench page: store dropdown + status filter + 5 overview cards + order DataTable + paging (permission 372)

### 17. Points Exchange Goods (Round 16)

- PointsExchangeGoodsController: GET/POST/PUT/DELETE /admin/points-exchange-goods + POST {id}/toggle-status (on/off shelf) + GET {id}/exchanges (exchange records, incl. phone + result JSON parse)
- Migrations 000012 (two tables) + 000013 (permissions 373-378) applied

### 18. Commission Records (Round 16)

- ReferralRewardController: GET /admin/referral-rewards (only records with rewarded_at non-null, paged + keyword filter by referrer/referred nickname or phone, hashid encoded, permission 379)

### 19. Technician Tier Auto-Rating (Round 17)

- TierRatingService::evaluate(technicianId, allowDowngrade=false): real-time stats of appointment_order completed count + appointment_order_review average rating (rounded to 1 decimal) written back to profile.order_count/rating, matched high-to-low by appointment_technician_tier_config (min_orders/min_rating), no match falls to the lowest tier
- Upgrade/downgrade rules: upgrade-only (tier binds commission rate and price coefficient; auto-downgrade affects technician income and easily causes disputes, decline handled manually by admin); downgrade only when allowDowngrade=true (manual re-evaluation scenario), downgrade also logs + notifies
- Idempotency: when the tier matches profile.tier_id, only stats are synced, no log or notification
- Logging: changes write appointment_technician_tier_log (id/technician_id/old_tier_id/new_tier_id/reason/created_at) + in-app notification (type='tier')
- Trigger points: WorkController::complete / ReviewController review writes / ProfileController profile view lazy check
- Admin: TechnicianTierController keeps manual config; GET /admin/technician-tiers/logs paged change log (join technician name and old/new tier names, ID hashid encoded, permission 380)

### 20. Review Reply Viewing (Round 18)

- ReviewController new reply(): GET /admin/reviews/{id}/reply reply detail (decodeId → find → 404 → decorate output, reply='' when unanswered, reply/replied_at exposed via toArray)
- Route is static (before audit, defined prior to resource); permission seed id 381 (slug 'get.admin/reviews/{id}/reply', type 3, superadmin role idempotent association)
- Permission: 381

### 21. Appointment Calendar (Round 20)

- CalendarController month/day views: GET /api/calendar/technician/{id} (month view) + /day (day view)
- Data source: technician_schedule.time_slots JSON expanded per weekday into hour slots, appointment_order booked slots for that day excluded (status ∈ pending/paid/confirmed/serving), remaining bookable slots output
- Purpose: visual time selection for store scheduling, frontend horizontal scroll by day + tap to select time slots

### 22. User Growth Levels (Round 20)

- appointment_user_growth (records) + appointment_growth_level (tier seeds 5 levels: Bronze 0/Silver 100/Gold 500/Platinum 2000/Diamond 5000)
- Growth point accrual: check-in +10 (CheckInController); submit review +20 (ReviewController::store, follow-ups don't accrue); consumption floor(paid) 1 point per 1 CNY (WechatPayService::markOrderPaid, reuses existing payment-state recheck, naturally idempotent, duplicate callbacks don't double-accrue)
- Endpoints: GET /api/growth (current tier overview: balance/level/next-tier gap); GET /api/growth/records (paged records); GET /api/growth/levels (public tier list, no login required)
- Failure policy: each accrual point try/catch logs, does not affect the main flow

### 23. E-Invoices (Round 20)

- appointment_invoice: uk_order_type(order_id,order_type) prevents duplicate applications for the same order (duplicate 422, incl. MySQL 1062 catch fallback); idx_user_created/idx_status
- User side: POST /api/invoices (apply, amount/title carried server-side from the order, non-tamperable); GET /api/invoices (list); GET /api/invoices/{id} (detail)
- Admin: InvoiceController issue (invoice: writes invoice_no + status=issued + issued_at) / reject (reject: status=rejected + reject_reason), permission 382 list/383 issue/384 reject
- State machine: pending → issued / rejected

### 24. Support Tickets (Round 20)

- appointment_ticket: user submits ticket (title/content), admin replies appended (reply_content/replied_at), user can close (closed_at)
- User side: POST /api/tickets (submit); GET /api/tickets (list); GET /api/tickets/{id} (detail, own only); POST /api/tickets/{id}/close (close)
- Admin: TicketController index (list)/reply (reply), static routes defined before resource to avoid {id} shadow; permission 385 ticket reply/387 ticket list view
- State machine: open → replied (returns to open after reply, can reply again) / closed

### 25. Multi-Level Distribution — Level-2 Commission (Round 20)

- ReferralRewardService::payLevel2Reward(paidAmount, orderId): after order payment succeeds, look up the level-1 referrer's referrer (level-2 referral relation), pay paid×level2_rate (system config referral.level2_rate, default 0.02)
- Idempotency: in-transaction row lock + uk_order_referred(order_id, level2_user_id) unique key, duplicate payment callbacks/concurrency don't double-pay; try/catch failure only logs, doesn't affect the payment main flow
- Crediting: WalletTxn type='referral_level2' (TYPE_REFERRAL_LEVEL2 constant) + wallet balance accumulation
- Admin: ReferralLevel2Controller index paged records (permission 386), joining both levels of user nicknames

### 26. Growth Level Benefits Implemented (Round 21)

- GrowthLevel.benefits JSON shell implemented: migration seeds 5 tiers (Bronze {"discount_rate":1.0,"points_multiplier":1.0}, Silver 0.98/1.1, Gold 0.95/1.2, Platinum 0.92/1.3, Diamond 0.9/1.5)
- Tier discount: OrderController::store applyGrowthDiscount() — standard orders only (promotion_id empty, group-buy/flash disabled); order: payable after coupon/session-card discount × discount_rate; discount amount merged into discount_amount, order remark appends "等级折扣：白银9.8折，优惠¥2.00" for traceability; floor protection: post-discount actual pay ≥0.01 CNY (≥100 in cents), otherwise discount truncated to 0
- Points multiplier: WechatPayService::markOrderPaid growth points changed from floor(paid) to floor(paid × points_multiplier), multiplier taken at payment-time tier (accrued before crediting, this order doesn't upgrade the tier); the R20 try/catch hook points fully retained
- Query reuse: GrowthLevel::levelForGrowth() resolves tier by cumulative growth points, reused by ordering/payment; GET /api/growth already returns benefits and next_gap (R20 implementation, no change needed)

### 27. Invoice Title Management (Round 21)

- appointment_invoice_title (uk_user_title(user_id, title_type, invoice_title) prevents duplicates + idx_user_default)
- Endpoints: POST /api/invoice-titles (save, company requires tax_no, duplicate 422); GET (list, default first); PUT /{id} (edit, own only); DELETE /{id} (delete, own only); POST /{id}/default (set default, transaction zeroes other rows of the same user)
- Default rules: first saved auto-default; deleting the default auto-assigns the earliest one
- Application link: InvoiceController::store optionally accepts title_id — resolves the title into invoice_title/tax_no/title_type, original manual path retained when no title_id; uk_order_type dedup logic unchanged

### 28. Ticket Satisfaction (Round 21)

- appointment_ticket gains rating TINYINT NULL + rated_at DATETIME NULL (migration 000303)
- Close rating: TicketController::close() supports optional rating 1-5 (filter_var integer validation, out-of-range/non-integer 422; provided → writes rating+rated_at, absent → stays NULL for old clients; open-only close rule retained)
- Admin stats: GET /admin/tickets/satisfaction (static route before resource to avoid {id} shadow) returns total/rated_count/unrated_count/average (1 decimal)/distribution (1-5 star counts, missing stars filled with 0); permission 388

### 29. Review Image Audit (Round 21)

- admin ReviewAuditController (new, existing ReviewController untouched): GET /admin/review-audit reviews with images (JSON_LENGTH(images)>0 filter + leftJoin user nickname and technician name + status filter + hashid encoding); POST /{id}/hide hide; POST /{id}/restore restore
- State machine: hide only from visible, restore only from hidden (422 both directions); OrderReview status is an integer system (STATUS_HIDDEN=0/STATUS_VISIBLE=1)
- Effect chain: user-side technician review lists already filter by status → hidden reviews auto-invisible
- Permissions: 389 list / 390 hide / 391 restore

### 30. User Browse History (Round 21)

- appointment_browse_history (uk_user_item(user_id, item_id) unique, re-browse only refreshes viewed_at, no duplicate insert; idx_user_viewed ordering)
- Recording hook: ServiceController::detail() records on success (try/catch + Log::warning doesn't affect the main flow; public route has no JWT, user_id null check skips anonymous)
- Endpoints: GET /api/browse-history (join appointment_service name/cover/price/original price, viewed_at descending, per_page default 15 max 50, item_id hashid); DELETE /{item_id} (own only, invalid/other's 404); DELETE / (clear own only)

### 31. Full-Reduction Promotions (Round 22)

- appointment_full_reduction_activity (threshold/reduction/title/status/start_at/end_at + idx_status_status_time)
- Order stacking: standard orders only (group-buy/flash skip), threshold judged on the amount after coupon/session-card deduction, order **coupon/session card → full reduction → tier discount**; picks the activity with the largest reduction; discount merged into discount_amount + remark "满减：满X减Y"; post-reduction actual pay floor 0.01 CNY (in cents)
- User side GET /api/full-reduction-activities (public, active ones sorted by reduction descending)
- admin FullReductionController: CRUD + toggle-status on/off shelf (destroy with confirmPassword)
- Permissions: 396 list / 397 create / 398 edit / 399 on-off shelf / 400 delete (one permission record corresponds to one method.path slug, 5 routes split into 5 records)

### 32. My Appointments ICS Export (Round 22)

- IcsController GET /api/order/ics: orders within 90 days with status pending/paid/confirmed/serving exported as iCal (RFC5545), own only
- VEVENT: UID=order ID, DTSTAMP(UTC), TZID=Asia/Shanghai, default duration 1h, summary "预约：服务名" (degenerates to "预约" when missing), description technician/store/address (skipped when missing), LOCATION; text escaping (\, \; \\ \n) + 75-byte line folding
- No orders returns a valid empty calendar (`BEGIN:VCALENDAR` skeleton)

### 33. Technician Attendance (Round 22)

- appointment_technician_attendance (date/check_in_at/check_out_at/status + uk_technician_date unique index prevents concurrent duplicate check-ins)
- Technician side (TechnicianAuth): check-in duplicate same day 422; check-out without check-in/already checked out 422 + row lock; >10:00 marks late; GET current month list + attendance days/total hours/average hours (?month=YYYY-MM invalid 422)
- admin: GET /admin/attendance (date+technician name filter, join real_name, hashid) + /stats (grouped per-technician statistics)
- Permissions: 392 list / 393 stats

### 34. APP Push Service (Round 22)

- AppPushService (config group=push: enabled default 0 / provider jpush/getui/placeholder): when disabled silently degrades to logging only; when enabled builds platform/title/content/payload structure, logs + writes appointment_push_log (status=sent); vendor SDK integration left as TODO (no credentials, nothing actually sent)
- 5 event hooks: payment success (WechatPayService::markOrderPaid), auto refund (autoRefundCancelledOrder), manual refund (doRefund/refundToBalance), refund compensation (completeOneRefundCompensation), pre-service reminder (ServiceReminderTimer); all try/catch, never blocking the main flow
- appointment_push_log (user_id/title/content/payload JSON/status/provider + idx_user)

### 35. WeChat Official Profit Sharing (Round 22)

- WechatProfitSharingService (config group=profit_sharing: enabled/receiver_ratio, credentials reuse wechat_pay): disabled → degraded to logging only, no DB writes; enabled → amount validation (>0 and ≤paid, actual paid×0.7 default) + idempotency (same order pending/success skipped) → writes pending record → builds "request single profit sharing" structure (no credentials, no HTTP executed, request content logged, record stays pending); private HTTP-isolated doRequest is testable
- WechatPayService::markOrderPaid hooks requestSharing after submission (try/catch, failure only logged)
- appointment_profit_sharing (uk_sharing_no unique + idx_order); admin GET /admin/profit-sharing list (join order number/technician nickname, status/order number/technician name filters)
- Permission: 394

### 36. Privacy Compliance (Round 22)

- GET /api/privacy/data: data export (personal/orders/points/wallet_txns/reviews/addresses/invoices grouped; logs only record masked phone + counts)
- Deletion loop: close-request (balance non-zero / unfinished orders / in-progress tickets 422 → close_status=1) → close-cancel (1→0) → close-confirm (after 72h → close_status=2 + close_at + phone/nickname anonymized to user{id} + status=0)
- appointment_user gains close_status/close_requested_at/close_at (idempotent ALTER migration); AuthController login/loginByCode return 403 "账号已注销" for close_status=2

### 37. User Health Profile (Round 23)

- GET/PUT/DELETE /api/health-profile: one per user (uk_user unique index), upsert only updates provided fields
- allergies/health_notes max 500 chars, preferred_technician_id existence validated, response hashid encoded
- Migration 000504_user_health_profile; HealthProfileTest 6 tests

### 38. Wallet Pay Password (Round 23)

- POST /api/wallet/pay-password/{set,verify,check}: 6-digit validation, password_hash storage + pay_password_set_at
- Changing when already set requires the old password 422; verify only validates, no storage; check returns whether set
- Migration 000502 (INFORMATION_SCHEMA idempotent ALTER two columns); WalletPayPasswordTest 7 tests

### 39. Technician Batch Scheduling (Round 23)

- POST /api/technician/schedule/batch: date range ≤7 days + weekdays filter, days with existing schedules skipped
- Single-entry settings also enable time-slot overlap detection (422 "与已有排班时间冲突：HH:MM-HH:MM")
- ScheduleConflictTest 5 tests

### 40. Order Status Timeline (Round 23)

- GET /api/order/{id}/timeline: own only (other's 404), descending; admin order detail merges the timeline array
- OrderStatusLog::record() static hooks for 8 change types: submit/pay/cancel/confirm/refund apply/refund approved/service start/service complete/timeout auto-cancel/admin operation (operator=admin)
- Payment callback markOrderPaid is the single consumption point; record() internal try/catch + Log::warning never blocks the main flow
- Migration 000501_order_status_log; OrderTimelineTest 4 tests

### 41. Points Lucky Wheel (Round 23)

- GET /api/wheel/prizes (hides weight/stock); POST /api/wheel/spin: Redis NX + row lock prevents concurrency, random_int weighted draw, client_token idempotent
- Prize crediting: points→earn record (with expiry time, can be expired normally by PointsExpiryTimer), balance→lockForUpdate, coupon→pending manual issue, no-prize→lose
- GET /api/wheel/records my records paged; admin /admin/lucky-wheel CRUD + on/off shelf + records (permissions 401-406)
- Migrations 000503 (appointment_lucky_wheel + appointment_wheel_record + w60/w40 demo seeds) + 000505 (permission seeds); LuckyWheelTest admin 3 + service 6 tests

### 42. Guest Mode (Round 24)

- GET /api/guest/{home,services,services/{id},stores,technicians}: unauthenticated browsing entry (only ApiVersion middleware)
- home aggregates banners/announcements/service categories/hot services, Redis cache svc:guest:home 300s; services supports category filter + newest/sales/price sorting (page/per_page≤50); technicians only approved, service_id filterable, rating descending
- Covered by GuestControllerTest

### 43. Flash Sale (Round 24)

- appointment_seckill_activity (name/service_id/seckill_price/original_price/stock/start_at/end_at/status); sold count = appointment_order.seckill_id order count
- GET /api/seckill (status=1 + time window), /{id} (state=not_started/ongoing/ended), POST /{id}/buy: client_token (8-64 chars, SETNX 24h) idempotency + Redis NX 30s concurrency prevention + activity validation (no stock pre-deduction since 2026-08-26)
- Order injection: seckill_id reuses OrderController::store; stock uniformly deducted by row lock inside store() transaction (calling /api/order directly with seckill_id also deducts stock), flash price = seckill_price (DB authoritative), no coupon/points/member-card stacking; order cancellation does not restore stock; old FLASH_SALE promotion channel removed (store() promo branch now only handles group buying, PromotionController index filters flash_sale, show/join 400), flash sales only go through this channel
- admin /admin/seckill CRUD + on/off shelf + order list (permissions 407-411, 420); migration 000606 permission seeds; SeckillTest service + admin

### 44. APP Version Management & Update Check (Round 24)

- appointment_app_version (platform/version_code/version_name/force_update/changelog/download_url/status)
- GET /api/app/version?platform=android|ios public update check (invalid platform 422; latest among status=1; empty object when none)
- admin /admin/versions CRUD (permissions 416-419); migration 000609 permission seeds; VersionTest service + admin

### 45. Return-Customer Rewards (Round 24)

- ReturnCustomerRewardService: when a user's 2nd consumption with the same technician within 30 days completes (order complete), pays the technician a bonus = actual paid_amount × ratio (system_config group=return_customer, ratio default 0.05, enabled switch, invalid values fall back to defaults)
- Written to appointment_technician_earnings (type=return_customer, status=pending) reusing the commission settlement chain, automatically included in technician earnings summaries; idempotent by order_id+type; called inside WorkController::complete row-lock transaction
- admin /admin/return-customer/config (GET/PUT) + /rewards (?keyword technician name/order number/user nickname) (permissions 412-414); migration 000607 permission seeds; ReturnCustomerRewardServiceTest

### 46. Schedule Export (Round 24)

- GET /admin/technician-schedule/export: CSV (UTF-8 BOM, opens directly in Excel), filename schedules_{YmdHis}.csv
- start_date/end_date required (YYYY-MM-DD, invalid 422) with span ≤31 days; technician_id optional (hashid, invalid 422)
- Columns: technician ID/technician name/date/time-slot detail (time_slots JSON parsed to "09:00-12:00, 14:00-18:00")
- Permission: 415; migration 000608 permission seeds; covered by ScheduleExportTest
