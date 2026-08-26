# Feature Design
> **Languages**: [中文](../FEATURE-DESIGN.md) · [한국어](../ko/FEATURE-DESIGN.md) · [Русский](../ru/FEATURE-DESIGN.md) · [Deutsch](../de/FEATURE-DESIGN.md) · [Français](../fr/FEATURE-DESIGN.md) · [Español](../es/FEATURE-DESIGN.md) · [Português](../pt/FEATURE-DESIGN.md) · [हिन्दी](../hi/FEATURE-DESIGN.md) · [العربية](../ar/FEATURE-DESIGN.md) · [বাংলা](../bn/FEATURE-DESIGN.md) · [Bahasa Indonesia](../id/FEATURE-DESIGN.md) · [日本語](../ja/FEATURE-DESIGN.md)

## Purchase Flows

### Service Appointment Flow (Direct Order)

```
Service details → Confirm order (store/technician/time/coupon/note) → Read service agreement
    → Submit order → Redis locks technician for 3 minutes → WeChat Pay → Payment success
    → Notify user + technician → Service time arrives → Technician confirms start
    → Service completed → QR code verification → User review → Order completed
```

### Product Purchase Flow (Cart Mode)

```
Product list → Add to cart → Cart confirmation (change quantity/delete)
    → Submit order → Pay → Ship → Receive → Complete
```

## Order State Machine

```
pending (pending payment) → paid (paid) → confirmed (confirmed)
    → serving (in service) → completed (completed) → reviewed (reviewed)

pending → cancelled (cancelled)
paid → cancelled
paid → refunding (refunding) → refunded (refunded)
```

## Technician Lock Mechanism

User enters the confirm-order page → Redis SETNX locks for 3 minutes. Released on exit/timeout.

```
SETNX lock:tech:123:2026-05-26-14:00 user_456 EX 180
 → Success: continue to order
 → Failure: technician already locked
```

## Refund Rules

| Condition | Refund Ratio |
|-----------|--------------|
| Within 15 minutes of ordering OR > 6 hours before start | 100% |
| ≤ 6 hours before start | 90% |
| Started but service not confirmed | 80% |
| After service confirmed started | 0% (no refund) |

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
