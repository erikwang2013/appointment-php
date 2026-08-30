# Appointment Service System

A four-platform appointment service management platform: WeChat Mini Program + Flutter App + HarmonyOS App (same-account role switching) + PC Admin Dashboard.

> **Status**: All complete | 143 Controllers (service 69 / admin 74) | 87 Models | 722 tests (service 558 / admin 164) | 95 Tables | 388 Routes (service 227 / admin 161)

## Project Structure

```
appointment-php/
├── admin/              # Admin dashboard (webman v2 + Flutter Web)
├── service/            # Business API service (webman v2)
├── apps/               # Client-side frontend apps
│   ├── wechat/         #   WeChat Mini Program (native)
│   ├── flutter/        #   Flutter App (iOS + Android)
│   └── harmonyos/      #   HarmonyOS App
└── docs/               # Documentation
```

## Quick Start

### Requirements

- PHP 8.3+
- MySQL 8.0+
- Redis
- Composer

### Web Installer (Recommended)

```bash
cd admin/
cp .env.example .env
composer install
php start.php start -d
```

Open `http://localhost:8787/install` in browser and follow the wizard to configure database and admin account.

### Manual Installation

```bash
# 1. Install dependencies
cd service/ && cp .env.example .env && composer install
cd ../admin/ && cp .env.example .env && composer install

# 2. Import unified database script (95 tables + permissions/config seeds + demo data)
mysql -u root -p < docs/install.sql

# 3. Start services
cd service/ && php start.php start -d   # Business API → :8787
cd ../admin/ && php start.php start -d  # Admin API → :8787
```

### Docker Deployment

```bash
cd admin/ && cp .env.docker .env && docker-compose up -d
cd ../service/ && cp .env.docker .env && docker-compose up -d
```

## Tech Stack

| Layer | Technology | Description |
|-------|------------|-------------|
| Backend Framework | webman v2 (PHP 8.3+) | High-performance persistent-memory HTTP service |
| Database | MySQL 8.0 | Table prefix `appointment_` |
| Cache | Redis | Cache / Rate limiting / Session / Queue |
| Search | Elasticsearch | Full-text search (via webman-scout) |
| Admin Frontend | Flutter Web | PC admin dashboard style |
| Client App | Flutter | iOS + Android |
| Client Mini Program | Native WeChat Mini Program | WXML / WXSS / JS |
| Client HarmonyOS App | HarmonyOS ArkTS | Native @ohos.net.http |
| ID Generation | erikwang2013/snowflake-php | BIGINT non-auto-increment primary keys |
| API ID Encode/Decode | erikwang2013/hashids | Hide real IDs from external exposure |
| JWT Authentication | erikwang2013/jwt-webman | Bearer Token |
| Sensitive Data Encryption | erikwang2013/encryption + encryptable | Dual-layer API + DB encryption |
| Security Protection | erikwang2013/security-php | 31 attack detectors |
| Action Verification | erikwang2013/poster-php | Random verification for sensitive actions |
| Country Flags | erikwang2013/season | Flag icons |
| ES Sync | erikwang2013/webman-scout | Automatic model synchronization |

## Architecture

<img src="docs/diagrams/en-architecture.svg" alt="en-architecture.svg" width="100%">

## Core Flows

### Appointment Booking

<img src="docs/diagrams/en-appointment-flow.svg" alt="en-appointment-flow.svg" width="100%">

### Payment & Refund

<img src="docs/diagrams/en-payment-refund.svg" alt="en-payment-refund.svg" width="100%">

## Order Lifecycle

<img src="docs/diagrams/en-order-lifecycle.svg" alt="en-order-lifecycle.svg" width="100%">

## Security Architecture

### Defense-in-Depth (7 Layers)

<img src="docs/diagrams/en-security-defense.svg" alt="en-security-defense.svg" width="100%">

> More diagrams: [Flowcharts](docs/diagrams/FLOWCHART.md) (withdrawal/role switch) | [Function Map](docs/diagrams/FUNCTION-DIAGRAM.md) | [All Lifecycles](docs/diagrams/LIFECYCLE-DIAGRAM.md) | [Full Security Architecture](docs/diagrams/SECURITY-ARCHITECTURE.md)

## Project Intro

<img src="docs/diagrams/mascot.svg" alt="Appointment service mascot — Booking Bunny (animated SVG)" width="200" align="right">

**Appointment Service System** is a four-platform appointment management platform for the life-services industry: WeChat Mini Program + Flutter App + HarmonyOS App (same-account role switching) + PC Admin Dashboard, covering the full loop of user booking, technician fulfillment and admin operations — appointments, membership, marketing and settlement in one system, stable, secure and easy to extend.

## Key Features (Rounds 6-24)

| Feature | Description |
|---------|-------------|
| Wallet & Prepaid | user_wallet / wallet_recharge / wallet_txn tables; balance + transactions, WeChat recharge (callback with R-prefixed order no.), balance order payment (pay_channel=balance), auto top-up on WeChat/balance refunds |
| Admin UI Complete | Flutter Web, 20 pages: dashboard/users/roles/config/logs/verify/schedule/services/technicians/orders/coupons/members/visit-cards/announcements/FAQ/withdrawals/reviews/reports/profile |
| Dashboard Real-time Stats | Admin home real-time stats (users / total orders / technicians / service orders) + trend charts (order volume / amount / new users / activity), Redis 5m cache |
| Data Reports | ReportController 3 endpoints: order statistics / technician performance / store distribution (GET /admin/reports/orders\|technicians\|distribution), Redis cache 300s |
| Mini Program Subscribe Messages | 3 order scenarios (payment success / refund arrival / verified); push_sent_at idempotency; automatic fallback to in-app notification when template unconfigured |
| Technician Withdrawal | Admin review; two-level approval (store → finance) for amounts ≥500; state machine pending→approved→completed (rejected/failed) |
| Visit Card Redemption | My cards with real-time used_up/expired; Redis NX idempotency + row lock, directly creates completed order + OrderItem + OrderPayment(pay_type='card') |
| Technician Workbench | Today tasks / records / start·complete (row lock + state-machine guard + idempotent, writes in-app notification); mini program tech-work 3 tabs |
| Coupon Deduction | PriceCalculator: applyCoupon readonly calc / consume on payment / restoreCouponAndCard idempotent restore on refund; fixed/percent + min_amount threshold |
| Gift Cards | redeem: cash type tops up wallet (row-lock anti-double-credit, WalletTxn type='gift_card'), gift type mark-only |
| Points System | Check-in points; consumption points floor(paid×1) on verification (order_id idempotent, balance snapshot); pro-rata clawback on refund; paged details with type/source filters |
| Membership Management | appointment_user.member_level column (migration 000008); admin member-card full CRUD (permissions 365-369) |
| Mini Program Ordering Flow | Service detail → confirm order (coupon pick / threshold grey-out / client-side estimate) → POST /order → WeChat/balance payment; 20 mini program pages |
| Group-buy | join with duplicate 422 + full-group lock + lazy expiry close; group-buy order via store with promotion_id at discounted price (discount_percent), stacking with coupons/cards/points disabled, auto-cancel + technician lock release when group fails (old promotion FLASH_SALE channel removed; flash sale now goes through the dedicated seckill channel) |
| Store Manager Workbench | service /api/store-manager 4 endpoints (overview/orders/technicians/revenue) with mandatory store_id isolation (403 without store); admin workbench overview + order store_id filter + Flutter page + permission 372 |
| Referral Reward | first completed order of referred user pays referrer paid_amount × reward_rate (system config, default 0.05) into wallet (WalletTxn referral_reward); row-lock + null-check + first-order recheck triple idempotency; earnings list + admin records view (permission 379) |
| Points Exchange Mall | goods/exchange-record tables; exchange with Redis NX + row-lock anti-oversell + uk_user_goods once-per-user; coupon grant / wallet credit / gift-card code results; admin CRUD + on-off + records (permissions 373-378) |
| Appointment Reschedule | POST /api/order/reschedule/{id} same-technician time change; only pending/paid/confirmed and ≥6h before original start; order_lock + new-slot technician lock SETNX(180s) anti-oversell + schedule-conflict DB check; appointment_order_reschedule record + SCENE_RESCHEDULE subscribe message |
| Coupon Transfer | 8-char unique transfer code (uk_code fallback, 7-day validity); claim anti-abuse: Redis NX lock + row-lock recheck anti-double-spend, uk_user_coupon one-transfer-per-coupon, transferred coupons non-re-transferable, no self-claim; lazy expiry restores original coupon |
| Points Expiry | expires_at (default 365d, config points.expiry_days); PointsExpiryTimer 60s cursor sweep writes type=expire negative rows (triple idempotency) + aggregated in-app notification; expired points cannot offset/exchange |
| Technician Auto Tier | TierRatingService real-time stats (order count + review avg) backfill profile, match tier_config high-to-low; upgrade-only by default (allowDowngrade for manual re-eval); appointment_technician_tier_log + in-app notification; admin logs view (permission 380) |
| Flash-sale Ordering | /api/seckill activities + buy idempotency/anti-concurrency; order injects seckill_id into store(), stock deducted by row lock inside the transaction (price = seckill_price from DB), sold-out 422, no stock restore on cancel; old promotion flash_sale channel removed |
| Service Start Reminder | ServiceReminderTimer 60s sweep of confirmed/serving orders starting within 1h → SCENE_REMINDER subscribe message + in-app notification (order_id+type dedup, triple idempotency); falls back to in-app when template unconfigured |
| Expiry Reminder | ExpiryReminderTimer 6h sweep of member cards/coupons expiring within 3 days → type=card_expiry/coupon_expiry + SCENE_EXPIRY subscribe message (order_id tracks source dedup) |
| Review Reply | POST /api/technician/review/reply/{order_id}: non-owner 404, duplicate 422, in-app notification on success; appointment_order_review replied_at column; admin reply detail (permission 381) |
| Recharge Arrival Notify | in-app notification type='wallet_recharge' inside WeChat recharge callback transaction (reuses callback idempotency, atomic with status change, non-blocking on failure) |
| Balance Transfer | POST /api/wallet/transfer user-to-user: amount 0.01-1000/once + daily 5000 cap; Redis NX lock + both-wallet row locks (ascending user_id anti-deadlock) + client_token 24h idempotency; WalletTxn transfer_out/transfer_in double ledger with balance_after snapshot; receiver in-app notification type='balance_received' |
| Points Transfer | POST /api/user/points/transfer user-to-user: 1-10000 points + daily 10000 cap; Redis NX lock + last-ledger-row lockForUpdate both sides (ascending anti-deadlock) + recheck inside lock; sender consume / receiver earn double ledger (receiver rows carry expires_at for normal expiry); receiver in-app notification type='points_received' |
| Review Append | POST /api/order/review/{order_id}/append: non-owner 404 / duplicate 422 / empty 422 / non-completed 422, technician in-app notification type='review_append' on success; appointment_order_review append_content/append_images(JSON)/append_at columns; also registered the previously-unreachable user review-submit route and fixed its latent TypeError |
| Logistics Tracking | GET /api/order/logistics/{id}: owner-only product orders (404 for non-owner/non-product/not-shipped); parses order.remark JSON (shipping_company/tracking_no/shipped_at written by admin ship()); receiver phone masked 138****5678 |
| Notification Preferences | appointment_user_notify_setting table (uk_user_type unique, absent row = enabled by default); GET/PUT /api/user/notify-settings; 5 types service_reminder/card_expiry/points_expiry/marketing/system (system always-on); notifySettingEnabled gates 3 timers + subscribe events, disabled type skips both in-app and subscribe messages |
| Booking Calendar | GET /api/calendar/technician/{id} (month view) + /day (day view): time_slots JSON expanded to hour slots, appointment_order booked slots excluded; visual schedule-based time picking |
| User Growth Levels | appointment_user_growth + appointment_growth_level (Bronze 0 / Silver 100 / Gold 500 / Platinum 2000 / Diamond 5000); check-in +10, review +20, spending 1 point per yuan (reuses existing status re-check for natural idempotency); GET /api/growth (overview/records, public levels) |
| E-Invoice | POST/GET /api/invoices (apply/list/detail): uk_order_type(order_id,order_type) dedup on duplicate apply, amount served server-side; admin issue/reject (permissions 382-384) |
| Customer Tickets | POST/GET /api/tickets + /{id}/close: user submit/list/detail/close; admin reply (permissions 385/387) |
| Multi-level Referral L2 | After order paid, pay first-level referrer's referrer paid×level2_rate (config 0.02): transaction row lock + uk_order_referred idempotency; WalletTxn TYPE_REFERRAL_LEVEL2; admin record view (permission 386) |
| Growth Level Benefits | GrowthLevel.benefits shell realized: order-time discount_rate discount (standard orders only, coupon/card → level discount order, discount amount into discount_amount + traceable remark, floor protection truncates to 0); payment growth points floor(paid×points_multiplier) (tier taken at payment time, no same-order promotion) |
| Invoice Titles | appointment_invoice_title frequently-used title library: save/edit/delete/default (first auto-default, deleted default auto-reassigns oldest, set-default transactional clear); invoice apply optional title_id, manual-entry compatible |
| Ticket Satisfaction | Close ticket may rate 1-5 (out-of-range 422, absent keeps NULL for old clients); admin satisfaction summary: average / 1-5 star distribution / rated vs unrated counts (permission 388) |
| Review Image Audit | admin ReviewAuditController: image reviews list (JSON_LENGTH filter + join user/technician names), hide/restore (hide only visible, restore only hidden, 422 both ways); hidden reviews automatically invisible in public lists (permissions 389-391) |
| Browse Footprint | appointment_browse_history (uk_user_item, re-browse refreshes viewed_at only): service detail hook records (try/catch non-blocking, anonymous skipped); list joins service info + hashid; delete single/clear owner-only |

> Round-8 maintenance fixes: removed 12 latent Poster::verify fatals; DashboardController stats switched to Capsule Manager queries.
>
> Round-14 additions: subscribe-message authorization (mini program requestSubscribeMessage, template IDs in appointment_system_config.wechat_app.template_ids); points cash-offset at payment (use_points, 100 points = 1 yuan, idempotent consume ledger); after-sale refund/exchange flow (appointment_order_aftersale + /api/aftersales + admin review, permissions 370/371).
>
> Round-15 additions: points refund/compensation on cancel/refund (refundOffsetPoints, 5 idempotent hooks); PromotionParticipant status switched to integer constants (fixes join 1366 breakage under strict mode).
>
> Round-16 additions: points exchange (PointsExchangeController, type=consume/source=exchange); group-buy ordering (appointment_order promotion_id/participant_id columns); referral rewards (ReferralRewardService hooked into WorkController::complete).
>
> Round-17 additions: reschedule (appointment_order_reschedule + reschedule endpoint); coupon transfer (appointment_user_coupon_transfer + transfer/claim/transfers); points expiry (expires_at + PointsExpiryTimer process); technician auto tier (TierRatingService + appointment_technician_tier_log, permission 380).
>
> Round-17 fix: AutoCancelTimer notification insert now uses \support\Model::generateId() (previously called non-existent Snowflake::generate(), silently failing auto-cancel notifications).
>
> Round-18 additions: flash-sale ordering (store() flash_sale price path); service start reminder (ServiceReminderTimer + SCENE_REMINDER); member-card/coupon expiry reminder (ExpiryReminderTimer + SCENE_EXPIRY); technician review reply (reply endpoint + replied_at column + permission 381); recharge arrival notification (type='wallet_recharge' inside callback transaction).
>
> Round-19 additions: balance transfer (appointment_wallet_transfer + WalletTransferController, dual row locks + client_token idempotency); points transfer (appointment_user_points_transfer + PointsTransferController, daily cap + double ledger); review append (appointment_order_review 3 append columns + append endpoint + store route registration); logistics tracking (logistics endpoint + remark JSON parsing + phone masking); notification preferences (appointment_user_notify_setting + NotifySettingController + 3 timer gates).
>
> Round-20 additions: booking calendar (CalendarController month/day views + booked-slot exclusion); user growth levels (appointment_user_growth + appointment_growth_level 5 tiers + check-in/review/spending hooks); e-invoice (appointment_invoice + uk_order_type dedup + admin issue/reject, permissions 382-384); customer tickets (appointment_ticket submit/list/detail/close + admin reply, permissions 385/387); multi-level referral L2 (payLevel2Reward transaction row lock + uk_order_referred idempotency, permission 386).
>
> Round-21 additions: growth level benefits realized (order discount_rate + payment points_multiplier, migration seeds 5-tier benefits); invoice titles (appointment_invoice_title library + apply title_id link); ticket satisfaction (close rating rating/rated_at + admin summary, permission 388); review image audit (ReviewAuditController hide/restore, permissions 389-391); browse footprint (appointment_browse_history + detail hook + list/delete/clear).
>
> Round-22 additions: full-reduction promotions (appointment_full_reduction auto discount + threshold check, permissions 396-400); ICS calendar export (RFC5545 my appointments); technician attendance check-in (appointment_technician_attendance clock-in/out + late marking + admin stats, permissions 392-393); APP push service (config-driven abstraction + 5 event hooks, appointment_push_log); WeChat official profit sharing (appointment_profit_sharing_log config-driven + graceful degradation, permission 394); privacy compliance (data export + account deletion 72h state machine close_status).
>
> Round-23 additions: user health profile (appointment_user_health_profile); wallet pay password (appointment_user_wallet pay_password set/verify); technician batch scheduling (batch import + overlap conflict detection); order status timeline (appointment_order_status_log 8-state tracing + user/admin display); points lucky wheel (appointment_lucky_wheel + appointment_wheel_record weighted draw, permissions 401-406); points validity (points.expiry_days config + new earn ledger rows carry expires_at).
>
> Round-24 additions: guest mode (/api/guest/* read-only browsing without login + Redis cache); seckill (appointment_seckill_activity + Redis NX row-lock purchase + appointment_order.seckill_id injected at order creation, permissions 407-411/420); APP version management & update check (appointment_app_version + /api/app/version, permissions 416-419); return-customer reward (second purchase within 30 days bonus type=return_customer, permissions 412-414); schedule CSV export (UTF-8 BOM + time-slot detail, permission 415).
>
> 2026-08-26 Security hardening: order item prices are taken from the database only (client-supplied prices are not trusted; unknown target_type → 422; target_id must be hashid-encoded), group-buy/flash-sale prices also from DB; flash-sale stock is deducted by row lock inside the /api/order store() transaction (SeckillController::buy no longer pre-deducts stock, Redis activity lock + client_token idempotency kept); technician withdrawal reserves in-transit balance at application time, re-checks before transfer, concurrent approvals cannot double-pay; WeChat payment callback total_fee strictly compared against order payable, Alipay callback logs desensitized; /install writes .install.lock on success with double-check against re-install; dependency versions pinned (webman-scout 2.0.5, opensearch-php ^2.6, exact pins for dompdf/security-php/webman-database); phpstan.neon fixed and runnable in both apps (php -d memory_limit=2G).

## Documentation

| Document | Description |
|----------|-------------|
| [Architecture](docs/ARCHITECTURE.md) | System architecture, platform relationships, technical components, data flow |
| [Features](docs/FEATURES.md) | Complete feature list: client / technician / admin dashboard |
| [Architecture Design](docs/ARCHITECTURE-DESIGN.md) | Layered design, middleware chain, database design, security design |
| [Feature Design](docs/FEATURE-DESIGN.md) | Core business flows, business rules, state machines, refund rules |
| [API Reference](docs/API.md) | Business API + Admin API with request/response examples + OpenAPI endpoint |
| [Installation](docs/INSTALL.md) | Requirements, Docker deployment, environment variables, third-party config, FAQ |
| [Usage Guide](docs/USAGE.md) | Admin configuration, client/technician operations, API examples, refund rules |
| [Project Structure](docs/STRUCTURE.md) | Full directory layout, middleware execution chain, database table list |
| [Test Report](docs/TEST-REPORT.md) | Full test coverage audit (558 cases / 2508 assertions) |
| [Design Spec](docs/superpowers/specs/2026-05-26-appointment-system-design.md) | System design specification |
| [Implementation Plan](docs/superpowers/plans/2026-05-26-appointment-system-plan.md) | Phased implementation plan |
| [Docs Index](docs/README.md) | Full documentation index (12 languages) |

## Languages

| 语言 | 入口 |
|------|------|
| 简体中文 | [README.md（本页）](README.md（本页）) |
| English | [docs/en/README.md](docs/en/README.md) |
| 한국어 | [docs/ko/README.md](docs/ko/README.md) |
| Русский | [docs/ru/README.md](docs/ru/README.md) |
| Deutsch | [docs/de/README.md](docs/de/README.md) |
| Français | [docs/fr/README.md](docs/fr/README.md) |
| Español | [docs/es/README.md](docs/es/README.md) |
| Português | [docs/pt/README.md](docs/pt/README.md) |
| हिन्दी | [docs/hi/README.md](docs/hi/README.md) |
| العربية | [docs/ar/README.md](docs/ar/README.md) |
| বাংলা | [docs/bn/README.md](docs/bn/README.md) |
| Bahasa Indonesia | [docs/id/README.md](docs/id/README.md) |
| 日本語 | [docs/ja/README.md](docs/ja/README.md) |

## Support

If this project helps you, your support is welcome and appreciated!

<table>
  <tr>
    <td align="center" width="50%">
      <img src="docs/weixinpay.png" alt="WeChat Pay" width="130" height="130"><br>
      <b>WeChat Pay</b>
    </td>
    <td align="center" width="50%">
      <img src="docs/alipay.png" alt="Alipay" width="130" height="130"><br>
      <b>Alipay</b>
    </td>
  </tr>
</table>

### Global Bank Transfer

Global bank transfer donations are welcome (HKD / CNY / USD / other currencies). Thank you for your generosity!

| Item | Details |
|------|---------|
| Beneficiary Name | WANG KEXUN |
| Account Number | 881015918251 |
| Bank | ZA Bank Limited (SWIFT Code: AABLHKHHXXX, Bank Code: 387) |
| Bank Address | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **Intermediary Bank (if required)**
> This is intermediary bank information, not the receiving bank. Please check with your remitting bank whether it is required.
>
> - For HKD / CNY / USD: **Citibank N.A. Hong Kong** — SWIFT Code: CITIHKHXXXX, Bank Code: 006, Branch: Hong Kong Branch, Branch Code: 391, Address: Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - For other currencies: **The Bank of New York Mellon** — SWIFT Code: IRVTUS3NXXX, Address: 240 Greenwich Street, New York, United States

### Crypto Donation

If this project helps you, donations are welcome. Thank you!

| Network | QR Code | Wallet Address |
|---|---|---|
| BNB Smart Chain (BEP20) | [<img src="docs/coin/1.jpg" width="150" alt="BNB Smart Chain (BEP20)">](docs/coin/1.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Tron (TRC20) | [<img src="docs/coin/2.jpg" width="150" alt="Tron (TRC20)">](docs/coin/2.jpg) | `TEdDHWLajt1XvqtPDWmQctdrJaC3pzZZzz` |
| Ethereum (ERC20) | [<img src="docs/coin/3.jpg" width="150" alt="Ethereum (ERC20)">](docs/coin/3.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Aptos | [<img src="docs/coin/4.jpg" width="150" alt="Aptos">](docs/coin/4.jpg) | `0x836e3780edfc3f7b2372b39e2a1a3a5d7adfaccd96c726f21cfde1b50dd68030` |
| Plasma | [<img src="docs/coin/5.jpg" width="150" alt="Plasma">](docs/coin/5.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Polygon POS | [<img src="docs/coin/6.jpg" width="150" alt="Polygon POS">](docs/coin/6.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Solana | [<img src="docs/coin/7.jpg" width="150" alt="Solana">](docs/coin/7.jpg) | `2hfhboHdmdrYsY25XfQSsEWxq5ip4EQsR7f4AzSRMUyr` |
| The Open Network (TON) | [<img src="docs/coin/8.jpg" width="150" alt="The Open Network (TON)">](docs/coin/8.jpg) | `UQB9kFQohzmXUir9QSSZq01iwl9aQZIDdBpNmDklljRtCoGK` |
| Arbitrum One | [<img src="docs/coin/9.jpg" width="150" alt="Arbitrum One">](docs/coin/9.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| AVAX C-Chain | [<img src="docs/coin/10.jpg" width="150" alt="AVAX C-Chain">](docs/coin/10.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |

## License

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
