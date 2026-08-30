# System Function Diagram
> **Languages**: [中文](../../diagrams/FUNCTION-DIAGRAM.md) · [한국어](../../ko/diagrams/FUNCTION-DIAGRAM.md) · [Русский](../../ru/diagrams/FUNCTION-DIAGRAM.md) · [Deutsch](../../de/diagrams/FUNCTION-DIAGRAM.md) · [Français](../../fr/diagrams/FUNCTION-DIAGRAM.md) · [Español](../../es/diagrams/FUNCTION-DIAGRAM.md) · [Português](../../pt/diagrams/FUNCTION-DIAGRAM.md) · [हिन्दी](../../hi/diagrams/FUNCTION-DIAGRAM.md) · [العربية](../../ar/diagrams/FUNCTION-DIAGRAM.md) · [বাংলা](../../bn/diagrams/FUNCTION-DIAGRAM.md) · [Bahasa Indonesia](../../id/diagrams/FUNCTION-DIAGRAM.md) · [日本語](../../ja/diagrams/FUNCTION-DIAGRAM.md)

```mermaid
mindmap
  root((Appointment Service System))
    User side
      Authentication
        Phone registration/login
        Captcha login
        WeChat authorized login
        Guest mode
        Forgot password
        User/privacy agreements
      Homepage
        LBS location & city switching
        Banners/announcements
        Service category entries
        New user coupon
      Service booking
        Store selection incl. navigation
        Technician selection incl. rating
        Service time selection
        Off-peak 10% off / early booking 5% off
        Coupon usage
        Remarks & service agreement
      Product mall
        Product search & filter
        Product details & favorites
        Cart management
        Buy now
      Order management
        All orders Tab view
        Pending payment/to-ship/to-receive
        Cancel/urge shipment/confirm receipt
        Refund application
        After-sales application  return/exchange status tracking
        Points cash-off  deduction at payment
        Group-buy ordering  order at group price after joining
        Seckill ordering  order at seckill price, sold-out interception
        Rescheduling  change time with same technician ≥6h before start
        Appointment calendar  schedule month/day view, booked excluded
        Pre-service reminder  1h ahead subscribe message + in-app
        Text + image reviews
        Review append  extra content/images once
        Logistics tracking  shipment status/masked recipient
        E-invoice  apply/list/detail dedupe
        ICS calendar export  export 90-day appointments as iCal
        Order timeline  status change records/own only
        Invoice titles  common title library/default
        Notification preferences  toggles/timer gating
      Technician module
        Technician list  distance sort
        Technician details & favorites
        Onboarding application
        Batch scheduling  date range ≤7 days/overlap conflict detection
      Marketing center
        Coupons  claim/order deduction
        Coupon transfer  8-char code/anti-double-spend/7-day validity
        Member cards  monthly/VIP/session
        Session card verification  my/use
        Points earning & exchange/purchase rebate
        Points expiry  365-day validity/scheduled deduction
        Points exchange mall  exchange coupons/balance/gift cards
        Group buy/seckill  join/full lock/order after forming
        Card expiry reminders  notify within 3 days of expiry
        Gift cards  cash/physical/exchange credit
        Points transfer  user-to-user/daily cap/dual transactions
        Level-2 commission  level-2 referrer 2% commission
        Full-reduction activities  spend X save Y/auto stack on order
        Points wheel  weighted draw/points balance coupons/lose
      Wallet
        Balance query
        Top-up  in-app arrival notification
        Balance payment
        Refund credit
        Balance transfer  user-to-user/dual row locks/transfer records
        Pay password  6-digit set/verify/change
      Personal center
        Avatar/nickname/phone
        Identity switching  customer↔technician
        Notifications
        My favorites
        Browse history  recently viewed services
        Health profile  allergies/preferred technician
        Follow official account
        User referral  QR poster/commission details
        Growth levels  check-in/review/purchase 5 tiers
        Tier benefits  order discount/points multiplier
        Customer service tickets  submit/list/detail/close
        Ticket satisfaction  close rating/admin summary
        Feedback
      Settings
        Change password
        Rebind phone
        View agreements
        Check for updates
        Privacy compliance  data export/72h closure loop
        Account closure

    Technician workbench
      Attendance check-in
        Check in  late marked
        Check out
      Workbench loop
        today  today's orders
        records  service records
        start  start service
        complete  complete verification
      Today's overview
        Today's order count
        Income overview
      Schedule management
        Set time slots by day
        Publish bookable times
      Order handling
        Booked-not-verified list
        Completed list
        QR scan verification
      Member management
        Served members
        Session usage data
        Session card records
        Member profile editing
      Review interaction
        Reply to user reviews  404/duplicate 422/in-app notification
      Earnings management
        Today's income
        Amount in settlement
        Wallet balance
        In-transit funds  auto-confirm after 3 days
      Withdrawal
        Apply on the 20th of each month
        T+1 to WeChat balance
        Minimum/reserved/whole-hundred limits
      Return-customer rewards
        Second purchase bonus within 30 days
      Professional training
        Video courses
        Text+image courses

    Admin dashboard
      Dashboard
        7 stat cards  total users/new today/active users/operation logs/appointments today/pending withdrawals/pending technicians
        30-day trend charts  order volume/amount/new users/activity
        User-status distribution pie  enabled/disabled
        Recent operation logs 10
        Quick navigation
        In-app messages
      Technician management
        Technician list & search
        Add/export
        Review onboarding applications
        Schedule/service item settings
        Course progress tracking
        Auto tier assessment  order count + avg rating/upgrade-only/change log
        Attendance stats  by month/technician/late
      User management
        Member list & search
        Details/level settings
        Modify superior/password/phone
      Store management
        Store CRUD
        Enable/disable control
        Map coordinates config
        Store workbench  overview/order filter
      Services & products
        Service item CRUD
        Product CRUD
        Category tree management
        Card design  item+product combos
      Mall management
        Mall orders/shipment/logistics
        After-sales order review
        Review management
        Review image audit  hide/restore permissions 389-391
        Payment transactions
        Sales stats
      Appointment orders
        Multi-condition search
        Platform cancel/confirm complete
        Detail view
      Coupon activities
        Coupon CRUD
        On-off shelf control
        Claim stats
      Full-reduction activities
        Spend X save Y CRUD
        On-off shelf control
      Points wheel
        Prize CRUD
        On-off shelf control
        Spin record viewing
      Seckill activities
        Activity CRUD
        On-off shelf control
        Seckill order viewing
      Points exchange
        Exchange goods CRUD
        On-off shelf control
        Exchange record viewing
      Member card management
        Member card definition CRUD
        Session/monthly/VIP
      After-sales management
        After-sales list  status/user/order filter
        Review  approve/reject remarks
      Reviews & reports
        Service review management
        Data reports  order stats/technician TOP10/channel distribution 7-30-day range Redis 300s
        Sales stats  date-range order summary/store/service type
      Finance management
        Order profit sharing
        Technician withdrawal review
        Commission settings & rewards/penalties
        Income/expense transactions
        Finance stats  revenue/refunds/withdrawals/commissions range summary
        Withdrawal account/limit config
        Two-level refund approval
        Distribution commission records
        Level-2 commission records  permission 386
        Profit-sharing records  WeChat profit sharing/status filter
        Invoice review  issue/reject permissions 382-384
        Return-customer rewards  toggle/ratio/reward records permissions 412-414
      Content management
        Banner CRUD
        Announcement CRUD & publish
        Agreement editing
        FAQ CRUD
        Feedback handling
        Ticket replies  permissions 385/387
        Ticket satisfaction stats  permission 388
        Moments moderation
        About Us settings
      System settings
        Platform agreement management
        Unified technician commission
        System message templates
        APP push  config-driven/5 events wired
        Subscribe messages  3 order-event scenarios
        APP version management  version CRUD/forced update
        Sub-account permissions  RBAC
      Extended features
        System monitor  CPU/memory/Redis/MySQL
        IP blacklist management
        Database backup/restore
        Customer profiles  360 view
        Batch message push
        Scheduled task management
        Dual-channel SMS config
        Storage config  local/OSS/COS
        Schedule Excel export
        Store manager accounts  store_id isolation
```
