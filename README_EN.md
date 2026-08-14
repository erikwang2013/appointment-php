# Appointment Service System

A three-platform appointment service management platform: WeChat Mini Program + Flutter App (same-account role switching) + PC Admin Dashboard.

> **Status**: All complete | 104 Controllers | 58 Models | 323 tests (service 219 / admin 104) | 55+ Tables | 242 Routes

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

> Round-8 maintenance fixes: removed 12 latent Poster::verify fatals; DashboardController stats switched to Capsule Manager queries.

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
