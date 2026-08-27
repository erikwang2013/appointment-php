# Appointment Service System Design Specification
> **Languages**: [中文](../../superpowers/specs/2026-05-26-appointment-system-design.md) · [한국어](../../ko/specs/2026-05-26-appointment-system-design.md) · [Русский](../../ru/specs/2026-05-26-appointment-system-design.md) · [Deutsch](../../de/specs/2026-05-26-appointment-system-design.md) · [Français](../../fr/specs/2026-05-26-appointment-system-design.md) · [Español](../../es/specs/2026-05-26-appointment-system-design.md) · [Português](../../pt/specs/2026-05-26-appointment-system-design.md) · [हिन्दी](../../hi/specs/2026-05-26-appointment-system-design.md) · [العربية](../../ar/specs/2026-05-26-appointment-system-design.md) · [বাংলা](../../bn/specs/2026-05-26-appointment-system-design.md) · [Bahasa Indonesia](../../id/specs/2026-05-26-appointment-system-design.md) · [日本語](../../ja/specs/2026-05-26-appointment-system-design.md)

## Overview

Three-client appointment service system: user side (WeChat Mini Program + Flutter APP) + technician workbench (role switch within the same APP) + admin dashboard (PC Web).

## Architecture Decisions

| Decision | Approach |
|----------|----------|
| Backend architecture | `admin/` (admin API) + `service/` (business API), dual services sharing MySQL/Redis |
| User Mini Program | Native WeChat Mini Program `apps/wechat/` |
| User APP | Flutter `apps/flutter/` (iOS + Android) |
| User identity | Unified account, customer/technician identities switchable |
| Mini Program vs APP relationship | Functionally identical, platform differences only |
| Admin frontend | Extend existing Flutter Web (`admin/apps/flutter/`) |
| Admin backend | Extend business modules on existing webman v2 (`admin/`) |
| Third-party services | WeChat login/payment/SMS/map — integration approach reserved |

## System Architecture Diagram

```
┌──────────────────────────────────────────────────────────┐
│                      用户终端层                            │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ 微信小程序        │  │ Flutter APP       │              │
│  │ apps/wechat/      │  │ apps/flutter/     │              │
│  │ (原生WXML/WXSS)   │  │ (iOS + Android)   │              │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │         功能完全相同  │                        │
│           └──────────┬──────────┘                        │
│                      │ 客户身份 / 技师身份切换              │
├──────────────────────┼──────────────────────────────────┤
│              业务API网关                                   │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ service/ API      │  │ admin/ API        │              │
│  │ (webman v2)       │  │ (webman v2)       │              │
│  │ 用户/订单/支付/    │  │ 管理后台接口       │              │
│  │ 技师/门店/营销...   │  │ (已建 + 扩展)     │              │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │                      │                        │
│           └──────────┬───────────┘                        │
│                      │                                    │
├──────────────────────┼──────────────────────────────────┤
│                  数据层                                    │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────────────┐    │
│  │ MySQL  │ │ Redis  │ │  ES    │ │ 第三方服务      │    │
│  │ 8.0    │ │ 缓存/   │ │ 搜索   │ │ 微信/短信/地图  │    │
│  │        │ │ 限流/   │ │        │ │ (预留对接)     │    │
│  │        │ │ Session │ │        │ │                │    │
│  └────────┘ └────────┘ └────────┘ └────────────────┘    │
└──────────────────────────────────────────────────────────┘
```

## Core Database Tables

All tables use the `appointment_` prefix with BIGINT non-auto-increment primary keys (Snowflake-generated). Sensitive fields use the encryptable trait for encryption/decryption.

### User & Identity Domain

| Table | Description | Core fields |
|-------|-------------|-------------|
| `appointment_user` | Unified user table | phone, password, wx_openid, wx_unionid, avatar, nickname, user_type(customer/technician), status. Technician users also have customer features and can freely switch their currently active identity |
| `appointment_user_address` | User address | user_id, contact_name, contact_phone, province, city, district, detail, is_default |
| `appointment_technician_profile` | Technician profile | user_id, real_name, gender, id_card, id_card_front, id_card_back, avatar, rating, order_count, status(pending/approved/rejected), intro |
| `appointment_technician_schedule` | Technician schedule | technician_id, date, time_slots(JSON), status |
| `appointment_technician_service` | Services a technician can provide | technician_id, service_id |
| `appointment_technician_earnings` | Technician earnings ledger | technician_id, order_id, type(commission/bonus/penalty), amount, status |
| `appointment_technician_withdrawal` | Technician withdrawal records | technician_id, amount, actual_amount, commission_fee, account_info, status, reviewed_at |
| `appointment_technician_attendance` | Technician attendance | technician_id, date, check_in_at, check_out_at, clean_photo |
| `appointment_technician_member_note` | Member profile notes | technician_id, user_id, content, written_at |

### Service & Product Domain

| Table | Description | Core fields |
|-------|-------------|-------------|
| `appointment_service_category` | Service category | name, icon, parent_id, sort, status |
| `appointment_service` | Service item | category_id, name, description, cover_image, images(JSON), price, duration, sales_volume, specs(JSON), status |
| `appointment_product` | Product | category_id, name, cover_image, price, stock, sales_volume, type, status |
| `appointment_store` | Store | name, address, lat, lng, phone, business_hours(JSON), images, status |

### Order Domain

| Table | Description | Core fields |
|-------|-------------|-------------|
| `appointment_order` | Order master table | order_no, user_id, technician_id, store_id, total_amount, discount_amount, paid_amount, status, service_time, cancel_reason, remark |
| `appointment_order_item` | Order line items | order_id, service_id, product_id, type, name, price, quantity, spec_info |
| `appointment_order_payment` | Payment records | order_id, pay_type(wechat), transaction_id, amount, status, paid_at |
| `appointment_order_refund` | Refund records | order_id, payment_id, refund_no, amount, ratio, reason, status |
| `appointment_order_review` | Service reviews | order_id, user_id, technician_id, rating, content, images |
| `appointment_order_verification` | Verification records | order_id, code, verified_at, verified_by, location |

### Marketing Domain

| Table | Description | Core fields |
|-------|-------------|-------------|
| `appointment_coupon` | Coupon definition | name, type, amount, min_amount, total_qty, remain_qty, start_at, end_at, status |
| `appointment_user_coupon` | User coupon | user_id, coupon_id, status(available/used/expired), used_at |
| `appointment_member_card` | Member card definition | name, type(month/vip/times), price, duration_days, total_times, services(JSON) |
| `appointment_user_member_card` | User member card | user_id, card_id, start_at, end_at, total_times, used_times, status |
| `appointment_member_card_usage` | Session card usage records | user_card_id, order_id, service_id, used_at |
| `appointment_user_points` | Points ledger | user_id, type(earn/use), points, source, order_id |
| `appointment_gift_card` | Gift card | code, type, amount_or_gift, status, used_by, used_at |
| `appointment_user_referral` | User referral | referrer_id, referred_user_id, reward_type, reward_amount, registered_at, first_order_at |

### Content & Notification Domain

| Table | Description | Core fields |
|-------|-------------|-------------|
| `appointment_banner` | Carousel banners | position, image, jump_type(url/detail/none), jump_value, sort, status |
| `appointment_announcement` | Announcements | content, status, published_at |
| `appointment_platform_agreement` | Platform agreements | type(user_agreement/privacy_policy/service_agreement), title, content, version |
| `appointment_faq` | FAQ | title, content, sort |
| `appointment_feedback` | Feedback | user_id, content, images, handler_reply, status(pending/handled) |
| `appointment_moment` | Moments | content, images, published_at |
| `appointment_notification` | Notifications | user_id, type(order/system), title, content, is_read, created_at |

### Finance Domain (admin side)

| Table | Description | Core fields |
|-------|-------------|-------------|
| `appointment_finance_transaction` | Income/expense ledger | user_id, order_id, type, direction(income/expense), amount, actual_amount, commission, status |
| `appointment_technician_commission_config` | Commission config | technician_id, commission_rate, settlement_cycle |
| `appointment_withdrawal_account` | Withdrawal account | user_id, type(wechat), account_name, account_no |
| `appointment_withdrawal_config` | Withdrawal limit config | min_amount, reserve_amount, round_to_hundred |

## Service API Modules

### Public API (no authentication required)
- **AuthController** — login/register/forgot password/guest mode/identity switch
- **CaptchaController** — SMS verification code
- **WechatController** — WeChat authorization/login/payment callback
- **CommonController** — agreement text/about us/version info

### User module `user/` (authentication required)
- **ProfileController** — profile/change password/rebind phone/account cancellation
- **AddressController** — shipping address CRUD
- **FavoriteController** — favorites
- **FeedbackController** — feedback
- **ReferralController** — referral/referred user list

### Technician module `technician/` (technician identity + TechnicianAuth middleware required)
- **ProfileController** — technician profile/onboarding application
- **ScheduleController** — schedule settings
- **OrderController** — booked-not-verified/completed/QR scan verification
- **MemberController** — my members/member profile
- **EarningsController** — earnings/in-transit funds
- **WithdrawalController** — withdrawals
- **AttendanceController** — attendance/cleanliness photos

### Service module `service/`
- **CategoryController** — service categories
- **ItemController** — service/product list and details
- **SearchController** — search
- **StoreController** — store list/details

### Order module `order/` (authentication required)
- **CartController** — shopping cart
- **OrderController** — place order/order list/details/cancel
- **PaymentController** — payment/refund
- **VerificationController** — QR code verification
- **ReviewController** — reviews

### Marketing module `marketing/` (authentication required)
- **CouponController** — coupon list/receive/use
- **MemberCardController** — member cards/session cards
- **PointsController** — points
- **GiftCardController** — gift cards

### Content module `content/`
- **BannerController** — carousel banners
- **AnnouncementController** — announcements
- **NotificationController** — notifications

### LBS module
- **LocationController** — location/city switch/nearby stores

### Shared capabilities `common/`
- SnowflakeService — ID generation
- HashidsService — ID encryption/decryption
- EncryptionService — sensitive data encryption/decryption
- WechatPayService — WeChat Pay (reserved)
- WechatAuthService — WeChat login (reserved)
- SmsService — SMS service (reserved)
- MapService — map service (reserved)

### Middleware
- Auth — JWT authentication (shares erikwang2013/jwt-webman package with admin)
- TechnicianAuth — technician identity validation
- RateLimit — rate limiting (shared with admin)

## Admin Dashboard Extensions

New controllers added on top of the existing framework:

### Technician management
- **TechnicianController** — technician list/search/export/review/schedule management/service item settings/course learning progress

### User management extensions
- **MemberController** — member list/level settings/consumption statistics

### Store management
- **StoreController** — store CRUD/enable-disable

### Service management
- **ServiceController** — service list/CRUD/card item design
- **ServiceCategoryController** — category management
- **ProductController** — product list/CRUD

### Mall management
- **MallOrderController** — mall orders/shipping/after-sales/reviews
- **SalesStatsController** — sales statistics

### Order management
- **AppointmentOrderController** — pending-use orders/cancel/confirm complete

### Coupon activities
- **CouponController** — coupon CRUD/issuance

### Finance management
- **FinanceController** — order profit sharing/income-expense ledger
- **WithdrawalController** — technician withdrawal review/complete
- **CommissionController** — commission settings/rewards-penalties/balance query
- **WithdrawalAccountController** — withdrawal account management
- **WithdrawalConfigController** — withdrawal limit config

### Content management
- **BannerController** — banner CRUD
- **AnnouncementController** — announcement CRUD
- **FaqController** — FAQ CRUD
- **FeedbackController** — feedback handling
- **MomentController** — moments moderation
- **AgreementController** — agreement editing (user agreement/privacy policy/service agreement)
- **AboutController** — about us settings

### Settings
- **SystemMessageController** — system message settings
- **AdminUserController** — sub-account management (based on existing RBAC)

### Dashboard extensions
- Real-time stat cards: user count/order total/technician count/service order count
- Line charts: order volume/amount/daily new users/activity
- Quick navigation: pending-module buttons
- In-app messages: new order notifications/refund notifications

## User-Side Page Structure

The WeChat Mini Program and Flutter APP are functionally identical.

### auth/ — Authentication
- login — login (phone/verification code/WeChat/guest entry)
- register — register (phone + verification code + password + referral code)
- forget-password — forgot password
- agreement — agreement viewing

### home/ — Homepage
- index — homepage (banners + announcements + service categories + recommendations)
- search — search page

### service/ — Services
- list — service list (filter by category)
- detail — service details (basic info + reviews + book now)
- product-list — product list

### order/ — Orders
- confirm — confirm order (store/technician/time/coupon/remarks/agreement)
- payment — payment page
- payment-success — payment success
- list — all orders (filter by status Tab)
- detail — order details
- review — service review
- verification — QR code verification

### cart/ — Shopping cart
- index — cart list

### technician/ — Technicians (customer view)
- list — technician list (sorted by distance, nearest first)
- detail — technician details (reviews/services offered/book now)
- apply — technician onboarding application

### tech-work/ — Technician workbench (technician identity)
- index — workbench homepage (today's orders/income overview)
- schedule — schedule settings
- order-list — my orders (booked-not-verified/completed)
- scan-verify — QR scan verification
- member-list — my members
- member-detail — member details/profile editing
- earnings — my earnings
- withdrawal — withdrawals
- transaction-list — transaction details
- attendance — attendance/cleanliness photo upload
- training — professional training

### user/ — Personal center
- index — personal info (avatar/nickname/member card/favorites/coupon entries)
- settings — settings (change password/rebind phone/agreements/update/cancel account/logout)
- switch-role — identity switch (customer ↔ technician)

### marketing/ — Marketing
- coupon-list — coupon list
- member-card — my member cards
- points — my points
- gift-card — my gift cards
- referral — referral (explanation + QR poster + referred user list)

### Other pages
- message/ — message list/details
- store/list, store/detail — store list (LBS sorted)/details (navigation)
- other/about — about us
- other/feedback — feedback
- other/official-account — follow official account

### Shared components
- navbar, tabbar, service-card, technician-card
- coupon-popup, lbs-selector, empty-state, loading

### Identity switch logic
- Customer identity bottom nav: Home / Services / Cart / Orders / Me
- Technician identity bottom nav: Workbench / Orders / Members / Earnings / Me
- The "Me" page provides the identity switch entry
- Users not yet technicians are guided to the onboarding application page when switching to technician identity

## Purchase Flow Description

The system has two distinct purchase flows:

### Service appointment flow (direct ordering, no cart)
- Service item detail page → confirm order (select store/technician/time) → pay → verify
- Technician resource exclusivity: technician locked for 3 minutes upon entering the confirm order page
- Used for offline service items such as massage, beauty care

### Product purchase flow (cart mode)
- Product list → add to cart → cart confirmation → submit order → pay → ship/receive
- Supports quantity changes and product removal
- Used for physical goods or card voucher sales

## Key Business Rules

### Technician lock mechanism
- Multiple users cannot book the same technician at the same time
- When a user enters the confirm order page, the technician is locked via Redis SETNX for 3 minutes
- The lock is automatically released when leaving the booking page or on timeout

### Refund rules
| Condition | Refund ratio |
|-----------|--------------|
| Within 15 minutes of ordering, or >6 hours before start | 100% |
| ≤6 hours before start | 90% |
| Started but service not confirmed | 80% |
| After service confirmed as started | 0% (no refund) |

### Discount rules
- Off-peak hours (10-12 / 17-18 / after 21:00) 10% off
- Booking 30 minutes in advance 5% off (cannot stack with coupons)

### Technician withdrawals
- Withdrawals available on the 20th of each month, arrival T+1 business day
- Supports withdrawal to WeChat balance
- Verified but unsettled orders are auto-confirmed by the system within 3 days
- Member profiles must be completed within 24 hours, otherwise no commission

### Return-customer rewards
- Second purchase with the same technician within 30 days → bonus recorded
- Cleanliness photos uploaded after service

### Points rules
- 1:100 exchange for gift cards (configurable in admin)
- Referred users receive specified points after successful registration and ordering (admin-set)
