# Lifecycle Diagrams
> **Languages**: [中文](../../diagrams/LIFECYCLE-DIAGRAM.md)

## 1. Order Lifecycle (State Machine)

```mermaid
stateDiagram-v2
    [*] --> pending: user submits order

    pending --> paid: payment success<br/>(WeChat/balance/free three channels)

    pending --> cancelled: timeout cancel (15min)<br/>user-initiated cancel

    paid --> confirmed: technician confirms order<br/>callback atomic consumption<br/>coupon deducted/session card used
    paid --> cancelled: user cancels<br/>(per refund rules)
    paid --> refunding: user applies for refund
    paid --> aftersale: after-sales application<br/>(refund/exchange)

    confirmed --> serving: service starts

    serving --> completed: service completed + verified<br/>session card usage deducted

    serving --> refunding: abnormal refund<br/>(80%)

    completed --> reviewed: user review
    completed --> aftersale: after-sales application<br/>(refund/exchange)

    refunding --> refunded: approved<br/>original route refund/balance credit<br/>coupon returned + points clawed back
    refunding --> paid: rejected

    aftersale --> refunded: approved-refund<br/>reuses order refund endpoint
    aftersale --> paid: rejected
    aftersale --> [*]: approved-exchange<br/>status flow complete

    reviewed --> [*]
    cancelled --> [*]
    refunded --> [*]

    note right of pending: technician locked for 3 minutes
    note right of refunding: store manager → finance two-level approval
```

## 2. Member Card Lifecycle

```mermaid
stateDiagram-v2
    [*] --> active: user purchases member card

    active --> used_up: session card times exhausted

    active --> expired: expiry (monthly/VIP)

    active --> frozen: violation freeze (admin operation)

    frozen --> active: unfreeze

    used_up --> [*]
    expired --> [*]
```

## 3. Technician Onboarding Lifecycle

```mermaid
stateDiagram-v2
    [*] --> applied: submit onboarding application

    applied --> approved: admin review approved
    applied --> rejected: review rejected

    rejected --> applied: modify and resubmit

    approved --> active: first login to technician side

    active --> suspended: violation suspension
    suspended --> active: restored
    active --> banned: permanent ban

    banned --> [*]
```

## 4. Coupon Lifecycle

```mermaid
stateDiagram-v2
    [*] --> draft: created by admin

    draft --> published: published on shelf

    published --> claimed: claimed by user

    claimed --> used: used on order
    claimed --> expired: past validity

    published --> ended: stock exhausted/expired off shelf

    used --> [*]
    expired --> [*]
    ended --> [*]
```

## 5. Technician Withdrawal Lifecycle

```mermaid
stateDiagram-v2
    [*] --> pending: submit withdrawal application

    pending --> approved: store manager approves
    pending --> rejected: review rejected

    rejected --> [*]: returned

    approved --> processing: finance confirms

    processing --> completed: WeChat balance arrival (T+1)

    completed --> [*]
```

## 6. Token Authentication Lifecycle

```mermaid
stateDiagram-v2
    [*] --> issued: user logs in successfully

    issued --> active: requests API with token

    active --> refreshed: near expiry, refresh token

    refreshed --> active: continue with new token

    active --> blacklisted: manual logout<br/>password change<br/>concurrent limit exceeded (>3)

    active --> expired: unused for 7 days

    blacklisted --> [*]
    expired --> [*]

    note right of blacklisted: added to JWT blacklist<br/>invalidated immediately
```

## 7. Group-Buy Activity Lifecycle

```mermaid
stateDiagram-v2
    [*] --> ongoing: created and published by admin

    ongoing --> full: participants ≥ min_people<br/>(full lock, new joins rejected)

    ongoing --> closed: expired without forming<br/>(lazy check: closed on show/join)

    full --> closed: expiry

    ongoing --> joined: user joins<br/>(Redis NX against overselling, duplicate join 422)

    joined --> group_paid: order and pay at group price<br/>(group price = original × discount_percent)

    joined --> cancelled: activity closed without forming<br/>(order auto-cancelled, technician lock released)

    group_paid --> [*]: normal order lifecycle
    cancelled --> [*]
    closed --> [*]

    note right of joined: group-buy orders disable coupon/session card/points stacking
    note right of closed: joined users prompted "not formed"
```

## 8. Coupon Transfer Lifecycle

```mermaid
stateDiagram-v2
    [*] --> available: claimed by user/issued by system

    available --> transferred: transfer code generated<br/>(8-char unique code, 7-day validity)

    transferred --> claimed: recipient claims<br/>(Redis NX lock + row lock against double-spend<br/>original coupon set used, new coupon bound to recipient)

    transferred --> expired: unclaimed after 7 days<br/>(lazy check, original coupon restored to available)

    claimed --> used: recipient uses on order
    claimed --> expired2: recipient fails to use in time

    used --> [*]
    expired --> [*]
    expired2 --> [*]

    note right of transferred: a coupon can be transferred only once<br/>(uk_user_coupon unique index)
    note right of claimed: transferred coupons cannot be re-transferred
```

## 9. Points Expiry Lifecycle

```mermaid
stateDiagram-v2
    [*] --> earned: check-in/purchase rebate/refund credit<br/>(expires_at = now + 365 days)

    earned --> used: cash-off/exchange consumption

    earned --> expired: unused by expiry<br/>(PointsExpiryTimer scans every 60s<br/>writes type=expire negative deduction rows)

    expired --> [*]: in-app notification "points expired"
    used --> [*]

    note right of expired: three-layer idempotency: original-row row-lock re-check<br/>+ id cursor pagination + notification only on deduction rounds
```

## 14. Account Closure Lifecycle (Round 22)

```mermaid
stateDiagram-v2
    [*] --> active: normal use

    active --> requested: closure requested<br/>(balance/unfinished orders blocked 422)

    requested --> active: cancel application (close-cancel)

    requested --> closing: confirm closure<br/>(after 72h close-confirm)

    closing --> [*]: anonymize phone/nickname<br/>+ status=0 disabled

    note right of requested: login unaffected
    note right of closing: close_status=2 login blocked 403
```

## 17. Return-Customer Reward Lifecycle (Round 24)

```mermaid
stateDiagram-v2
    [*] --> completed: order completed<br/>(WorkController::complete row-lock transaction)

    completed --> checked: second purchase with same technician within 30 days

    checked --> none: first purchase / toggle off<br/>(enabled=0)

    checked --> pending: second purchase<br/>(bonus = paid × ratio<br/>same order_id+type idempotent)

    pending --> settled: settled via commission chain<br/>(appointment_technician_earnings<br/>type=return_customer)

    settled --> [*]
    none --> [*]

    note right of pending: status=pending<br/>auto-included in technician earnings summary
```
