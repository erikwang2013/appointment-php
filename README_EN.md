# Appointment Service System

A three-platform appointment service management platform: WeChat Mini Program + Flutter App (same-account role switching) + PC Admin Dashboard.

> **Status**: All complete | 113 Controllers | 110 Models | 415 tests (service 309 / admin 106) | 77 Tables | 291 Routes

## Project Structure

```
appointment-php/
├── admin/              # Admin dashboard (webman v2 + Flutter Web)
├── service/            # Business API service (webman v2)
├── apps/               # Client-side frontend apps
│   ├── wechat/         #   WeChat Mini Program (native)
│   └── flutter/        #   Flutter App (iOS + Android)
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

# 2. Import unified database script (55 tables + demo data)
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
| Database | MySQL 8.0 | Table prefix `erik_` |
| Cache | Redis | Cache / Rate limiting / Session / Queue |
| Search | Elasticsearch | Full-text search (via webman-scout) |
| Admin Frontend | Flutter Web | PC admin dashboard style |
| Client App | Flutter | iOS + Android |
| Client Mini Program | Native WeChat Mini Program | WXML / WXSS / JS |
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

## Key Features (Rounds 6-10)

| Feature | Description |
|---------|-------------|
| Wallet & Prepaid | user_wallet / wallet_recharge / wallet_txn tables; balance + transactions, WeChat recharge (callback with R-prefixed order no.), balance order payment (pay_channel=balance), auto top-up on WeChat/balance refunds |
| Admin UI Complete | Flutter Web, 20 pages: dashboard/users/roles/config/logs/verify/schedule/services/technicians/orders/coupons/members/visit-cards/announcements/FAQ/withdrawals/reviews/reports/profile |
| Mini Program Subscribe Messages | 3 order scenarios (payment success / refund arrival / verified); push_sent_at idempotency; automatic fallback to in-app notification when template unconfigured |
| Technician Withdrawal | Admin review; two-level approval (store → finance) for amounts ≥500; state machine pending→approved→completed (rejected/failed) |
| Visit Card Redemption | My cards with real-time used_up/expired; Redis NX idempotency + row lock, directly creates completed order + OrderItem + OrderPayment(pay_type='card') |
| Technician Workbench | Today tasks / records / start·complete (row lock + state-machine guard + idempotent, writes in-app notification); mini program tech-work 3 tabs |
| Coupon Deduction | PriceCalculator: applyCoupon readonly calc / consume on payment / restoreCouponAndCard idempotent restore on refund; fixed/percent + min_amount threshold |
| Gift Cards | redeem: cash type tops up wallet (row-lock anti-double-credit, WalletTxn type='gift_card'), gift type mark-only |
| Points System | Check-in points; consumption points floor(paid×1) on verification (order_id idempotent, balance snapshot); pro-rata clawback on refund; paged details with type/source filters |
| Membership Management | erik_user.member_level column (migration 000008); admin member-card full CRUD (permissions 365-369) |
| Mini Program Ordering Flow | Service detail → confirm order (coupon pick / threshold grey-out / client-side estimate) → POST /order → WeChat/balance payment; 20 mini program pages |
| Group-buy / Flash-sale | join with Redis NX anti-oversell + duplicate 422 + full-group lock + lazy expiry close; group-buy order via store with promotion_id at discounted price (discount_percent), stacking with coupons/cards/points disabled, auto-cancel + technician lock release when group fails |
| Store Manager Workbench | service /api/store-manager 4 endpoints (overview/orders/technicians/revenue) with mandatory store_id isolation (403 without store); admin workbench overview + order store_id filter + Flutter page + permission 372 |
| Referral Reward | first completed order of referred user pays referrer paid_amount × reward_rate (system config, default 0.05) into wallet (WalletTxn referral_reward); row-lock + null-check + first-order recheck triple idempotency; earnings list + admin records view (permission 379) |
| Points Exchange Mall | goods/exchange-record tables; exchange with Redis NX + row-lock anti-oversell + uk_user_goods once-per-user; coupon grant / wallet credit / gift-card code results; admin CRUD + on-off + records (permissions 373-378) |
| Appointment Reschedule | POST /api/order/reschedule/{id} same-technician time change; only pending/paid/confirmed and ≥6h before original start; order_lock + new-slot technician lock SETNX(180s) anti-oversell + schedule-conflict DB check; erik_order_reschedule record + SCENE_RESCHEDULE subscribe message |
| Coupon Transfer | 8-char unique transfer code (uk_code fallback, 7-day validity); claim anti-abuse: Redis NX lock + row-lock recheck anti-double-spend, uk_user_coupon one-transfer-per-coupon, transferred coupons non-re-transferable, no self-claim; lazy expiry restores original coupon |
| Points Expiry | expires_at (default 365d, config points.expiry_days); PointsExpiryTimer 60s cursor sweep writes type=expire negative rows (triple idempotency) + aggregated in-app notification; expired points cannot offset/exchange |
| Technician Auto Tier | TierRatingService real-time stats (order count + review avg) backfill profile, match tier_config high-to-low; upgrade-only by default (allowDowngrade for manual re-eval); erik_technician_tier_log + in-app notification; admin logs view (permission 380) |

> Round-8 maintenance fixes: removed 12 latent Poster::verify fatals; DashboardController stats switched to Capsule Manager queries.
>
> Round-14 additions: subscribe-message authorization (mini program requestSubscribeMessage, template IDs in erik_system_config.wechat_app.template_ids); points cash-offset at payment (use_points, 100 points = 1 yuan, idempotent consume ledger); after-sale refund/exchange flow (erik_order_aftersale + /api/aftersales + admin review, permissions 370/371).
>
> Round-15 additions: points refund/compensation on cancel/refund (refundOffsetPoints, 5 idempotent hooks); PromotionParticipant status switched to integer constants (fixes join 1366 breakage under strict mode).
>
> Round-16 additions: points exchange (PointsExchangeController, type=consume/source=exchange); group-buy ordering (erik_order promotion_id/participant_id columns); referral rewards (ReferralRewardService hooked into WorkController::complete).
>
> Round-17 additions: reschedule (erik_order_reschedule + reschedule endpoint); coupon transfer (erik_user_coupon_transfer + transfer/claim/transfers); points expiry (expires_at + PointsExpiryTimer process); technician auto tier (TierRatingService + erik_technician_tier_log, permission 380).
>
> Round-17 fix: AutoCancelTimer notification insert now uses \support\Model::generateId() (previously called non-existent Snowflake::generate(), silently failing auto-cancel notifications).

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
| [Design Spec](docs/superpowers/specs/2026-05-26-appointment-system-design.md) | System design specification |
| [Implementation Plan](docs/superpowers/plans/2026-05-26-appointment-system-plan.md) | Phased implementation plan |

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

## License

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
