# Feature Design
> **Languages**: [中文](../FEATURE-DESIGN.md)

## Purchase Flows

### Service Appointment Flow (Direct Order)

```
Service details → Confirm order (store/technician/time/coupon/note) → Read service agreement
    → Submit order → Redis locks technician for 3 minutes → WeChat Pay → paid
    → Unpaid orders auto-cancel after 30 minutes
    → Notify user + technician → Service time arrives → Technician scans/confirms start → serving
    → Service completed → completed → User review (separate record, does not change order state)
```

Pay/cancel/refund/payment callback/auto-cancel/verification/reschedule/technician start and complete all use the Redis lock `order_lock:{id}`.

### Product Purchase Flow (Cart Mode)

```
Product list → Add to cart → Cart confirmation (change quantity/delete)
    → Submit order → Pay → Ship → Receive → Complete
```

## Order State Machine

```
pending (pending payment) → paid (paid) → serving (in service) → completed (completed)
                                 └→ confirmed (compatibility state) → serving

pending → cancelled (cancelled)
paid → cancelled
paid → refunding (refunding) → refunded (refunded)
```

- Successful payment lands on `paid`; both `paid` and `confirmed` can enter `serving`.
- There is no `reviewed` status: reviews are a separate record and do not change order state.

## Technician Lock Mechanism

User enters the confirm-order page → Redis SETNX locks for 3 minutes. Released on exit/timeout.

```
SETNX lock:tech:123:2026-05-26-14:00 user_456 EX 180
 → Success: continue to order
 → Failure: technician already locked
```

The technician slot lock only prevents duplicate bookings for the same slot; order pay/cancel/refund state transitions are protected by `order_lock:{id}`.

## Refund Rules

| Condition | Refund Ratio |
|-----------|--------------|
| Within 15 minutes of ordering OR > 6 hours before service | 100% |
| ≤ 6 hours before service | 90% |
| Service started, service time passed, or status is confirmed/serving/completed | 0% (no refund) |
| Product orders or bookings without a service time | 100% |

## Discount Rules

| Type | Condition | Discount | Stacking |
|------|-----------|----------|----------|
| Off-peak discount | 10-12am / 5-6pm / after 21:00 | 10% off | Stackable with coupons |
| Early booking | 30+ minutes in advance | 5% off | Not stackable with coupons |

## Technician Withdrawal

- Withdrawal on the 20th of each month, T+1 to WeChat balance
- Verified but unsettled: auto-confirmed after 3 days
- Minimum amount / reserved amount / whole-hundred limits configured in admin

### Withdrawal Flow

```
Apply for withdrawal → poster-php verification → Admin review (approve/reject)
    → Withdrawal completed → WeChat balance credited → Financial record generated
```

### Earnings Types

| Type | Description |
|------|-------------|
| commission | Service commission |
| bonus | Bonus (repeat customers/attendance) |
| penalty | Fine (no profile written within 24h) |
| subsidy | Subsidy |
| attendance | Full-attendance reward |

### Repeat-Customer Reward

Second purchase with the same technician within 30 days → bonus recorded

### Member Profile

A profile must be written within 24h of each completed order, otherwise no commission

## Points Design

- Earned from purchases and referrals (admin configurable)
- 1:100 exchange for gift cards (admin configurable)
- Points transaction table records every change + balance

## Member Card Design

| Type | Billing | Description |
|------|---------|-------------|
| month | By day | Regular monthly card |
| vip | By day | VIP annual card |
| times | By count | Session card, freely combinable service items |

Session card: select a service combo at purchase (A×3+B×5), each use consumes 1 session of the corresponding item. Empty → used_up, expired → expired.

## Identity Switching

```
Customer → Switch to technician → Check if technician profile is approved
    → Yes: active_role=technician, page switches
    → No: guide to onboarding application

Technician → Switch to customer → active_role=customer, page switches
```

## New User Rewards

```
Register → Generate referral code → Has referrer → create promotion record
    → Auto-issue new user coupon (Phase 5)
    → Referrer earns points (after referee's first order)
```

## Payment Design (WeChat Pay Reserved)

```
POST /api/order/pay/{id}
    → Create payment record → Call WeChat unified order (WechatPayService reserved)
    → Return payment params → Frontend initiates payment
    → WeChat callback /api/wechat/notify → Verify signature → Update status to paid
    → Notify user + technician
```
