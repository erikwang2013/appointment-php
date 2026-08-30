# Usage Guide
> **Languages**: [中文](../USAGE.md) · [한국어](../ko/USAGE.md) · [Русский](../ru/USAGE.md) · [Deutsch](../de/USAGE.md) · [Français](../fr/USAGE.md) · [Español](../es/USAGE.md) · [Português](../pt/USAGE.md) · [हिन्दी](../hi/USAGE.md) · [العربية](../ar/USAGE.md) · [বাংলা](../bn/USAGE.md) · [Bahasa Indonesia](../id/USAGE.md) · [日本語](../ja/USAGE.md)

## Admin Dashboard Login

Default admin: `admin` / `admin123` | Address: `http://localhost:8787`

> Change the password immediately after first login

---

## System Configuration Flow

### 1. Basic Settings
System Config → fill in platform name/LOGO → About Us → customer service phone/website/email → Platform Agreement → edit User Agreement/Privacy Policy

### 2. Stores & Services
Store Management → add store (name/address/coordinates/phone/hours) → Service Categories → create category → Service Items → add service (name/price/duration/specs) → Product Management → add products/cards

### 3. Technician Onboarding
Technician applies via APP → reviewed in admin "Technician Management" → after approval, technician sets schedule → can receive appointments

### 4. Operations Config
Banner → upload + set redirect | Announcements → publish scrolling notices | Coupons → create new-user coupons/discount coupons | Member Cards → monthly/VIP/session cards | Commission → set technician commission rates

---

## Admin Dashboard Operations

### Dashboard
After login, the home page shows 7 dynamically rendered stat cards (total users / new today / active users / operation logs / appointments today / pending withdrawals / pending technicians), 30-day trend charts (order volume / amount / new users / activity), a user-status distribution pie (enabled/disabled) and the latest 10 operation logs (Redis `svc:dashboard` cache 300s); quick navigation goes straight to pending modules, and in-app messages deliver new-order/refund notifications.

### Data Reports
The Reports page offers 3 report types (7/30-day range, backed by `GET /admin/reports/orders|technicians|distribution`, Redis cache 300s):
- **Order Statistics** — summary (order count/paid amount/refunds/net revenue) + daily trend
- **Technician Performance** — technician TOP10 (order count/revenue/rating, masked names, sortable by count or revenue)
- **Channel Distribution** — payment channel distribution (WeChat/Alipay/balance) + order status distribution

Sales stats (`svc:sales_stats`: range order summary/store/service-type dimensions) and finance stats (`svc:finance_stats`: revenue/refunds/withdrawals/commissions range summary) are also available.

---

## User-Side Flows

### Register & Login
Search WeChat/scan QR → register with phone + captcha (referral code optional) → or one-tap WeChat login → new users automatically get a coupon

### Booking Services
Browse categories on homepage → tap a service for details → view price/reviews → Book Now → select store/technician/time/coupon → confirm order → WeChat Pay → payment success

### Order Management
Pending payment: complete payment | Paid: awaiting service | Completed: review (star rating + text + photos) | Refund: refund ratio calculated automatically

### Personal Center
Orders/coupons/member cards/points/favorites | Promotion Center: get promotion QR code to earn points | Feedback: text + photos

---

## Technician-Side Operations

### Switching Identity
APP "My" → Switch to Technician → Workbench

### Daily Work
- **Schedule Settings**: set bookable time slots by day
- **View Appointments**: list of today's booked orders
- **QR Verification**: scan user QR code to verify sessions
- **Member Profiles**: fill in customer profile within 24h per order (no commission if late)
- **Attendance Check-in**: check in/out + hygiene photos

### Earnings
View today's income/funds in transit/balance → withdraw on the 20th of each month → T+1 to WeChat balance

### Growth
Take training courses → attend assessments → pass to upgrade technician level (affects commission rate)

---

## API Endpoints

API documentation is maintained separately, see [API.md](API.md) (business API + admin API, with request/response examples and OpenAPI endpoints).

---

## WebSocket

```
ws://localhost:8282
```

Auth: `{"type":"auth","token":"<JWT>"}`

Events: `order_update` / `technician_online` / `system_notice`

---

## Push Configuration

iOS (APNs): configure apns_key_id/team_id/bundle_id/.p8 file  
Android (FCM): configure fcm_server_key

APP device registration: `POST /api/user/device/register {"platform":"ios","device_token":"..."}`

---

## Scheduled Tasks

| Task | Frequency | Description |
|------|-----------|-------------|
| Auto-cancel orders | 30 seconds | Pending payment over 30 minutes |
| Auto-settle earnings | 3 days | Settle commissions for completed orders |
| Coupon expiry | Daily | Mark expired |
| Member card expiry | Daily | Mark expired |

---

## Refund Rules

| Condition | Ratio |
|-----------|-------|
| Within 15 min of ordering or > 6h before start | 100% |
| ≤ 6h before start | 90% |
| Started but not confirmed | 80% |
| After confirmed start | 0% |

---

## Monitoring

```bash
GET /health          # Health check
GET /metrics         # Prometheus metrics
GET /.well-known/security.txt  # Security contact
```

## Tests

```bash
admin/ && phpunit --bootstrap tests/bootstrap.php     # 60 tests
service/ && phpunit --configuration phpunit.xml        # 21 tests
```
