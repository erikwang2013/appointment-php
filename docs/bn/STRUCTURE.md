# অ্যাপয়েন্টমেন্ট সার্ভিস সিস্টেম — প্রজেক্ট স্ট্রাকচার
> **Languages**: [中文](../STRUCTURE.md) · [English](../en/STRUCTURE.md) · [한국어](../ko/STRUCTURE.md) · [Русский](../ru/STRUCTURE.md) · [Deutsch](../de/STRUCTURE.md) · [Français](../fr/STRUCTURE.md) · [Español](../es/STRUCTURE.md) · [Português](../pt/STRUCTURE.md) · [हिन्दी](../hi/STRUCTURE.md) · [العربية](../ar/STRUCTURE.md) · [Bahasa Indonesia](../id/STRUCTURE.md) · [日本語](../ja/STRUCTURE.md)

## রিপোজিটরি ওভারভিউ

```
appointment-php/
├── admin/              # 管理后台 (webman v2 + Flutter Web)
├── service/            # 业务API服务 (webman v2)
├── apps/               # 用户端前端应用
│   ├── wechat/         #   微信小程序（原生）
│   ├── flutter/        #   Flutter APP（iOS + Android）
│   └── harmonyos/      #   HarmonyOS APP（鸿蒙原生）
├── docs/               # 项目文档
└── .claude/            # Claude Code 配置
```

## প্রজেক্ট সম্পর্ক

```
┌──────────────────────────────────────────────┐
│                   apps/                       │
│  ┌─────────────┐  ┌──────────┐  ┌─────────┐  │
│  │ wechat/      │  │ flutter/  │  │harmonyos/│  │
│  │ 微信小程序    │  │iOS/Android│  │ 鸿蒙 APP │  │
│  └──────┬──────┘  └────┬─────┘  └────┬────┘  │
│         │     功能完全相同      │            │
│         └──────────┬─────────┘            │
│                    │ HTTP API                 │
├────────────────────┼─────────────────────────┤
│              service/                         │
│         业务API (webman v2)                    │
│             端口: 8787                         │
│                    │                          │
│                    │ 共享 MySQL/Redis/ES       │
│                    │                          │
│              admin/                           │
│         管理后台API (webman v2)                 │
│             端口: 8787                         │
│                    │                          │
│         ┌──────────┴──────────┐               │
│         │                     │               │
│    admin/apps/flutter/    Flutter Web         │
│    管理后台前端 (PC)                           │
└──────────────────────────────────────────────┘
```

## admin/ — ম্যানেজমেন্ট ব্যাকএন্ড

```
admin/
├── app/
│   ├── admin/controller/       # ম্যানেজমেন্ট এন্ড কন্ট্রোলার
│   │   ├── BaseController          # বেস কন্ট্রোলার
│   │   ├── DashboardController     # ড্যাশবোর্ড
│   │   ├── UserController          # ইউজার ম্যানেজমেন্ট
│   │   ├── RoleController          # রোল ম্যানেজমেন্ট
│   │   ├── PermissionController    # পারমিশন ম্যানেজমেন্ট
│   │   ├── ConfigController        # সিস্টেম কনফিগ
│   │   ├── LogController           # অপারেশন লগ
│   │   ├── ProfileController       # পার্সোনাল সেন্টার
│   │   ├── ExportController        # এক্সপোর্ট
│   │   ├── ImportController        # ইমপোর্ট
│   │   ├── UploadController        # ফাইল আপলোড
│   │   ├── HealthController        # হেলথ চেক
│   │   ├── DocsController          # API ডকুমেন্টেশন
│   │   ├── MetricsController       # Prometheus মেট্রিক্স
│   │   │                            # ✅ বাস্তবায়িত বিজনেস মডিউল:
│   │   ├── TechnicianController    #   টেকনিশিয়ান ম্যানেজমেন্ট (তালিকা/অডিট/শিডিউল/এক্সপোর্ট)
│   │   ├── MemberController        #   মেম্বার ম্যানেজমেন্ট (লেভেল/কনজিউম)
│   │   ├── StoreController         #   স্টোর CRUD
│   │   ├── ServiceController       #   সার্ভিস আইটেম CRUD
│   │   ├── ServiceCategoryController # সার্ভিস ক্যাটাগরি CRUD (ট্রি)
│   │   ├── ProductController       #   পণ্য CRUD
│   │   ├── MallOrderController     #   মল অর্ডার/শিপমেন্ট/আফটার-সেলস
│   │   ├── SalesStatsController    #   সেলস স্ট্যাটস (Redis ক্যাশ)
│   │   ├── AppointmentOrderController  # অ্যাপয়েন্টমেন্ট অর্ডার (বাতিল/সম্পন্ন)
│   │   ├── MemberCardController    #   মেম্বার কার্ড ডেফিনিশন CRUD
│   │   ├── ReviewController        #   সার্ভিস রিভিউ ম্যানেজমেন্ট
│   │   ├── ReportController        #   ডেটা রিপোর্ট পরিসংখ্যান
│   │   ├── CouponController        #   কুপন CRUD
│   │   ├── FinanceController       #   ফাইন্যান্স লেনদেন/পরিসংখ্যান
│   │   ├── WithdrawalController    #   উত্তোলন অডিট (অনুমোদন/রিজেক্ট/সম্পন্ন)
│   │   ├── CommissionController    #   কমিশন সেটিং/পুরস্কার-জরিমানা
│   │   ├── WithdrawalAccountController # উত্তোলন অ্যাকাউন্ট ম্যানেজমেন্ট
│   │   ├── WithdrawalConfigController  # উত্তোলন সীমা কনফিগ
│   │   ├── BannerController        #   ক্যারোসেল CRUD
│   │   ├── AnnouncementController  #   নোটিশ CRUD/প্রকাশ
│   │   ├── FaqController           #   সাধারণ প্রশ্ন CRUD
│   │   ├── FeedbackController      #   মতামত ফিডব্যাক/রিপ্লাই
│   │   ├── MomentController        #   মোমেন্ট অডিট
│   │   ├── AgreementController     #   চুক্তি এডিট/প্রকাশ
│   │   ├── AboutController         #   আমাদের সম্পর্কে সেটিং
│   │   └── SystemMessageController #   সিস্টেম মেসেজ টেমপ্লেট/পাঠানো
│   │   │                            # ✅ এক্সটেনশন মডিউল:
│   │   ├── ServiceCardController    #   কার্ড আইটেম ডিজাইন
│   │   ├── SystemMonitorController  #   সিস্টেম মনিটরিং
│   │   ├── IpBlacklistController    #   IP ব্ল্যাকলিস্ট ম্যানেজমেন্ট
│   │   ├── DbBackupController       #   ডেটাবেস ব্যাকআপ
│   │   ├── SmsConfigController      #   SMS কনফিগ
│   │   ├── StorageConfigController  #   স্টোরেজ কনফিগ
│   │   ├── StoreManagerController   #   স্টোর ম্যানেজার অ্যাকাউন্ট
│   │   ├── TrainingController       #   টেকনিশিয়ান ট্রেনিং
│   │   ├── ScheduledTaskController  #   শিডিউলড টাস্ক
│   │   ├── CustomerProfileController #  কাস্টমার প্রোফাইল
│   │   ├── BatchMessageController   #   ব্যাচ পুশ
│   │   ├── RefundWorkflowController #   রিফান্ড অডিট
│   │   ├── TechnicianTierController #   টেকনিশিয়ান লেভেল
│   │   │                            # ✅ রাউন্ড ২২-২৫-এ নতুন:
│   │   ├── FullReductionController  #   ফুল-রিডাকশন অ্যাক্টিভিটি
│   │   ├── AttendanceController     #   টেকনিশিয়ান অ্যাটেন্ডেন্স
│   │   ├── ProfitSharingController  #   উইচ্যাট প্রফিট শেয়ারিং
│   │   ├── LuckyWheelController     #   পয়েন্ট লাকি হুইল
│   │   ├── PointsExchangeGoodsController # পয়েন্ট এক্সচেঞ্জ পণ্য
│   │   ├── ReviewAuditController    #   রিভিউ ইমেজ অডিট
│   │   ├── InvoiceController        #   ইলেকট্রনিক ইনভয়েস
│   │   ├── TicketController         #   কাস্টমার সার্ভিস টিকিট
│   │   ├── ReferralRewardController #   প্রথম-স্তর কমিশন রেকর্ড
│   │   ├── ReferralLevel2Controller #   দ্বিতীয়-স্তর কমিশন রেকর্ড
│   │   ├── ReturnCustomerController #   রিটার্ন কাস্টমার রিওয়ার্ড
│   │   ├── SeckillController        #   সেকিল অ্যাক্টিভিটি
│   │   ├── VersionController        #   APP ভার্সন ম্যানেজমেন্ট
│   │   ├── TechnicianScheduleController # শিডিউল ম্যানেজমেন্ট/CSV এক্সপোর্ট
│   │   ├── AftersaleController      #   আফটার-সেলস প্রসেসিং
│   │   ├── OrderVerificationController # ভেরিফিকেশন রেকর্ড
│   │   ├── CommunityModerationController # কমিউনিটি অডিট
│   │   ├── VideoAuditController     #   ভিডিও অডিট
│   │   └── InstallController        #   ইনস্টল উইজার্ড
│   ├── api/v1/controller/      # পাবলিক API v1
│   │   ├── AuthController
│   │   └── CaptchaController
│   ├── common/                 # পাবলিক ইউটিলিটি
│   │   ├── HashidsService
│   │   ├── SnowflakeService
│   │   ├── EncryptionService
│   │   ├── TechnicianWithdrawalService
│   │   └── WechatPayService
│   ├── middleware/             # মিডলওয়্যার
│   │   ├── Cors
│   │   ├── RateLimit
│   │   ├── AdminAuth
│   │   ├── AdminPermission
│   │   └── OperationLog
│   ├── model/                  # ডেটা মডেল (শুধু ৬টি স্বতন্ত্র মডেল: AdminPermission/AdminRole/AdminUser/OperationLog/OperationLogDetail/SystemConfig; বাকি psr-4 শেয়ারড service ভার্সন)
│   ├── queue/                  # কিউ টাস্ক
│   └── process/                # প্রসেস
├── apps/
│   ├── flutter/                # Flutter Web ম্যানেজমেন্ট ব্যাকএন্ড ফ্রন্টএন্ড
│   │   └── lib/app/
│   │       ├── pages/           #   পেজ (২০টি)
│   │       │   ├── dashboard/   #   ড্যাশবোর্ড
│   │       │   ├── login/       #   লগইন
│   │       │   ├── user/        #   ইউজার ম্যানেজমেন্ট
│   │       │   ├── member/      #   মেম্বার ম্যানেজমেন্ট
│   │       │   ├── role/        #   রোল পারমিশন
│   │       │   ├── config/      #   সিস্টেম কনফিগ
│   │       │   ├── log/         #   অপারেশন লগ
│   │       │   ├── profile/     #   পার্সোনাল সেন্টার
│   │       │   ├── technician/  #   টেকনিশিয়ান ম্যানেজমেন্ট
│   │       │   ├── schedule/    #   শিডিউল
│   │       │   ├── service/     #   সার্ভিস/পণ্য ম্যানেজমেন্ট
│   │       │   ├── service_card/#   কার্ড আইটেম ডিজাইন
│   │       │   ├── order/       #   অর্ডার ম্যানেজমেন্ট
│   │       │   ├── verification/#   ভেরিফিকেশন রেকর্ড
│   │       │   ├── coupon/      #   কুপন
│   │       │   ├── withdrawal/  #   উত্তোলন অডিট
│   │       │   ├── report/      #   রিপোর্ট পরিসংখ্যান
│   │       │   ├── review/      #   রিভিউ ম্যানেজমেন্ট
│   │       │   ├── announcement/#   নোটিশ
│   │       │   └── faq/         #   সাধারণ প্রশ্ন
│   │       ├── services/        #   API সার্ভিস লেয়ার
│   │       ├── layouts/         #   লেআউট
│   │       └── theme/           #   থিম
│   ├── harmonyos/               # HarmonyOS ম্যানেজমেন্ট এন্ড (ArkTS)
│   └── weixin/                  # উইচ্যাট ম্যানেজমেন্ট এন্ড
├── config/                     # কনফিগ ফাইল
│   ├── route.php
│   ├── middleware.php
│   ├── database.php
│   ├── jwt.php
│   ├── snowflake.php
│   ├── hashids.php
│   ├── encryption.php
│   ├── encryptable.php
│   └── ...
├── database/
│   └── backup/                 # ব্যাকআপ স্ক্রিপ্ট (টেবিল স্ট্রাকচার ও সিড ডেটা ইউনিফাইড দেখুন docs/install.sql)
├── docs/                       # ম্যানেজমেন্ট ব্যাকএন্ড ডকুমেন্টেশন
├── public/                     # এন্ট্রি ফাইল
├── runtime/                    # রানটাইম
├── tests/                      # টেস্ট
├── vendor/                     # ডিপেন্ডেন্সি
├── CLAUDE.md
├── composer.json
├── Dockerfile
└── docker-compose.yml
```

## service/ — বিজনেস API

```
service/
├── app/
│   ├── api/v1/controller/       # পাবলিক API v1 (২৬টি কন্ট্রোলার)
│   │   ├── AuthController          # লগইন/রেজিস্ট্রেশন/পাসওয়ার্ড ভুলে গেলে/রিফ্রেশ/পরিচয় স্যুইচ
│   │   ├── CaptchaController       # SMS ভেরিফিকেশন কোড (Redis রেট লিমিট)
│   │   ├── CommonController        # পাবলিক কনফিগ/চুক্তি/এলাকা
│   │   ├── ContentController       # ক্যারোসেল/নোটিশ/আর্টিকেল
│   │   ├── DocsController          # OpenAPI ডকুমেন্টেশন (hg/apidoc)
│   │   ├── LbsController           # কাছের স্টোর (Haversine)/রিভার্স জিওকোডিং
│   │   ├── GuestController         # গেস্ট মোড (লগইন ছাড়া শুধু রিড ব্রাউজিং, Redis ক্যাশ)
│   │   ├── SeckillController       # সেকিল অ্যাক্টিভিটি/সেকিল কেনা (আলাদা চ্যানেল)
│   │   ├── PromotionController     # গ্রুপ বাই (পুরনো flash_sale চ্যানেল অফলাইন)
│   │   ├── ServiceController       # সার্ভিস ক্যাটাগরি/আইটেম/পণ্য/স্টোর
│   │   ├── ServicePackageController # সার্ভিস প্যাকেজ
│   │   ├── StoreManagerController  # স্টোর ম্যানেজার ওয়ার্কবেঞ্চ (overview/orders/technicians/revenue)
│   │   ├── TechnicianController    # টেকনিশিয়ান পাবলিক তথ্য
│   │   ├── BrowseHistoryController # ব্রাউজ হিস্ট্রি
│   │   ├── CalendarController      # অ্যাপয়েন্টমেন্ট ক্যালেন্ডার (মাস/দিন ভিউ)
│   │   ├── CommunityController     # কমিউনিটি ফিড
│   │   ├── CommunityCommentController # কমিউনিটি কমেন্ট
│   │   ├── FullReductionController # ফুল-রিডাকশন অ্যাক্টিভিটি
│   │   ├── PaymentNotifyController # পেমেন্ট কলব্যাক (উইচ্যাট/Alipay)
│   │   ├── PrintController         # প্রিন্টিং
│   │   ├── PrivacyController       # প্রাইভেসি কমপ্লায়েন্স (ডেটা এক্সপোর্ট/অ্যাকাউন্ট বন্ধ)
│   │   ├── QueueController         # কিউ কলিং
│   │   ├── VersionController       # APP ভার্সন ম্যানেজমেন্ট/আপডেট চেক
│   │   ├── VideoController         # ভিডিও
│   │   ├── WechatController        # উইচ্যাট সম্পর্কিত
│   │   └── WheelController         # পয়েন্ট লাকি হুইল
│   ├── user/v1/controller/      # ইউজার মডিউল v1 (১৪টি কন্ট্রোলার)
│   │   ├── ProfileController       # ব্যক্তিগত তথ্য/পাসওয়ার্ড/ফোন/অ্যাকাউন্ট বন্ধ/লগআউট
│   │   ├── AddressController       # ঠিকানা CRUD (ডিফল্ট ঠিকানা ম্যানেজমেন্ট)
│   │   ├── FavoriteController      # ফেভারিট (সার্ভিস/টেকনিশিয়ান)
│   │   ├── FeedbackController      # মতামত ফিডব্যাক (টেক্সট+ছবি)
│   │   ├── ReferralController      # প্রমোশন/QR কোড/রেফারড ইউজার
│   │   ├── CheckInController       # চেক-ইন
│   │   ├── DeviceController        # ইউজার ডিভাইস ম্যানেজমেন্ট
│   │   ├── GrowthController        # গ্রোথ লেভেল (overview/records/levels)
│   │   ├── HealthProfileController # হেলথ প্রোফাইল
│   │   ├── InvoiceController       # ইলেকট্রনিক ইনভয়েস আবেদন/তালিকা/ডিটেইল
│   │   ├── InvoiceTitleController  # ইনভয়েস টাইটেল লাইব্রেরি
│   │   ├── NotifySettingController # মেসেজ পছন্দ সেটিংস
│   │   ├── PointsTransferController# পয়েন্ট ট্রান্সফার
│   │   └── TicketController        # কাস্টমার সার্ভিস টিকিট
│   ├── technician/v1/controller/ # টেকনিশিয়ান মডিউল v1 (১০টি কন্ট্রোলার)
│   │   ├── ProfileController       # টেকনিশিয়ান প্রোফাইল/ইন্টারনশিপ আবেদন
│   │   ├── ScheduleController      # শিডিউল কোয়েরি/সেটিং
│   │   ├── OrderController         # টেকনিশিয়ান অর্ডার তালিকা
│   │   ├── WorkController          # ওয়ার্কবেঞ্চ (today/records/start/complete)
│   │   ├── EarningController       # আয় ওভারভিউ + লেনদেন
│   │   ├── WithdrawController      # উত্তোলন আবেদন (প্রতি মাস config('withdraw.gate_day') তারিখে, কনফিগযোগ্য)
│   │   ├── ServiceRecordController # সার্ভিস রেকর্ড
│   │   ├── ExamController          # অনলাইন অ্যাসেসমেন্ট
│   │   ├── AttendanceController    # চেক-ইন/চেক-আউট অ্যাটেন্ডেন্স
│   │   └── ReviewController        # টেকনিশিয়ান রিভিউ রিপ্লাই
│   ├── order/v1/controller/     # অর্ডার মডিউল v1 (৮টি কন্ট্রোলার + ৯টি trait)
│   │   ├── OrderController         # অর্ডার (টেকনিশিয়ান লক)/তালিকা/ডিটেইল/বাতিল/পেমেন্ট/রিফান্ড/ভেরিফিকেশন (অ্যাগ্রিগেট এন্ট্রি, 38 লাইন, সব মেথড trait থেকে)
│   │   ├── OrderCreateTrait        # অর্ডার তৈরি store/প্রাইসিং সহায়ক (475 লাইন)
│   │   ├── OrderQueryTrait         # অর্ডার কোয়েরি তালিকা/ডিটেইল/লজিস্টিকস (205 লাইন)
│   │   ├── OrderPayTrait           # পেমেন্ট pay/ব্যালেন্স পেমেন্ট/পয়েন্ট কাটতি (415 লাইন)
│   │   ├── OrderCancelTrait        # অর্ডার বাতিল (272 লাইন)
│   │   ├── OrderRefundTrait        # রিফান্ড আবেদন (379 লাইন)
│   │   ├── OrderCompensateTrait    # রিফান্ড ক্ষতিপূরণ স্ক্যান + কুপন/পয়েন্ট ফেরত (345 লাইন)
│   │   ├── OrderVerifyTrait        # ভেরিফিকেশন কমিশন/পয়েন্ট ফেরত (256 লাইন)
│   │   ├── OrderRescheduleTrait    # অ্যাপয়েন্টমেন্ট পুনঃনির্ধারণ (181 লাইন)
│   │   ├── OrderNotifyTrait        # নোটিফিকেশন সাবস্ক্রিপশন/টেমপ্লেট/স্টেশন-ইন/WebSocket (195 লাইন)
│   │   └── OrderLockTrait          # ডিস্ট্রিবিউটেড লক ইউটিলিটি (80 লাইন)
│   │   ├── AftersaleController     # আফটার-সেলস
│   │   ├── CartController          # কার্ট
│   │   ├── IcsController           # ICS ক্যালেন্ডার এক্সপোর্ট
│   │   ├── ReviewController        # রিভিউ/ফলো-আপ রিভিউ
│   │   ├── SignatureController     # সিগনেচার
│   │   ├── TimelineController      # অর্ডার স্ট্যাটাস টাইমলাইন
│   │   └── WaitlistController      # ওয়েটলিস্ট
│   ├── wallet/v1/controller/    # ওয়ালেট মডিউল v1 (২টি কন্ট্রোলার)
│   │   ├── WalletController        # ব্যালেন্স/রিচার্জ/ট্রানজেকশন/ব্যালেন্স পেমেন্ট
│   │   └── WalletTransferController# ইউজারদের মধ্যে ট্রান্সফার
│   ├── marketing/v1/controller/ # মার্কেটিং মডিউল v1 (৭টি কন্ট্রোলার)
│   │   ├── CouponController        # কুপন তালিকা/গ্রহণ/অর্ডার কাটতি
│   │   ├── CardController          # মেম্বার কার্ড তালিকা/কেনা/টাইম কার্ড my/use
│   │   ├── PointController         # পয়েন্ট লেনদেন/কনজিউম ক্যাশব্যাক
│   │   ├── GiftCardController      # গিফট কার্ড/রিডিম
│   │   ├── MemberBenefitController # মেম্বার বেনিফিট
│   │   ├── MemberCardController    # মেম্বার কার্ড ডেফিনিশন
│   │   └── PointsExchangeController# পয়েন্ট এক্সচেঞ্জ মল
│   ├── notification/v1/controller/ # নোটিফিকেশন মডিউল v1 (১টি কন্ট্রোলার)
│   │   └── NotificationController  # নোটিফিকেশন তালিকা/পড়া হয়েছে চিহ্নিত
│   ├── common/                  # পাবলিক ক্ষমতা (BaseController ইত্যাদি)
│   ├── middleware/              # মিডলওয়্যার
│   │   ├── Auth                    # JWT অথেনটিকেশন + ইউজার স্ট্যাটাস ভ্যালিডেশন
│   │   ├── Cors                    # ক্রস-অরিজিন প্রসেসিং
│   │   ├── Security                # সিকিউরিটি ডিটেকশন (security-php)
│   │   └── TechnicianAuth          # টেকনিশিয়ান পরিচয় ভ্যালিডেশন
│   └── model/                   # ডেটা মডেল (৮১টি)
│       ├── User.php → appointment_user
│       ├── TechnicianProfile.php → appointment_technician_profile
│       ├── Service.php → appointment_service (ES: appointment_services)
│       ├── Product.php → appointment_product (ES: appointment_products)
│       ├── Store.php → appointment_store
│       ├── Order.php → appointment_order (রিফান্ড নিয়ম/স্টেট মেশিন সহ)
│       ├── Coupon.php → appointment_coupon
│       ├── MemberCard.php → appointment_member_card
│       ├── Notification.php → appointment_notification
│       └── ... (মোট ৮১টি মডেল ফাইল; admin-এ আরও ৬টি স্বতন্ত্র মডেল, সর্বমোট ৮৭)
├── config/                     # কনফিগ ফাইল
├── public/                     # এন্ট্রি
├── runtime/                    # রানটাইম
├── vendor/                     # ডিপেন্ডেন্সি
├── start.php
├── composer.json
└── Dockerfile
```

## apps/ — ইউজার এন্ড ফ্রন্টএন্ড

### apps/wechat/ — উইচ্যাট মিনি-প্রোগ্রাম

```
apps/wechat/
├── app.js                      # অ্যাপ এন্ট্রি
├── app.json                    # গ্লোবাল কনফিগ
├── app.wxss                    # গ্লোবাল স্টাইল
├── pages/
│   ├── auth/                   # অথেনটিকেশন
│   │   ├── login               #   লগইন
│   │   ├── register            #   রেজিস্ট্রেশন
│   │   ├── forget-password     #   পাসওয়ার্ড ভুলে গেলে
│   │   └── agreement           #   চুক্তি ভিউ
│   ├── home/                   # হোমপেজ (ক্যারোসেল/নোটিশ/ক্যাটাগরি/সার্চ)
│   ├── service/                # সার্ভিস
│   │   ├── list                #   সার্ভিস তালিকা
│   │   └── detail              #   সার্ভিস ডিটেইল
│   ├── order/                  # অর্ডার
│   │   ├── list                #   অর্ডার তালিকা
│   │   ├── detail              #   অর্ডার ডিটেইল
│   │   └── confirm             #   অর্ডার কনফার্ম
│   ├── cart/                   # কার্ট
│   ├── cards/                  # মেম্বার কার্ড (কেনা/আমার/টাইম কার্ড ব্যবহার my/use)
│   ├── gift-cards/             # গিফট কার্ড (রিডিম/জমা)
│   ├── points/                 # পয়েন্ট (লেনদেন/এক্সচেঞ্জ)
│   ├── marketing/              # মার্কেটিং (কুপন ইত্যাদি)
│   ├── favorite/               # ফেভারিট
│   ├── feedback/               # মতামত ফিডব্যাক
│   ├── referral/               # প্রমোশন
│   ├── message/                # মেসেজ
│   │   ├── list                #   মেসেজ তালিকা
│   │   └── detail              #   মেসেজ ডিটেইল
│   ├── tech-work/              # টেকনিশিয়ান ওয়ার্কবেঞ্চ
│   │   ├── index               #   ওয়ার্কবেঞ্চ হোম (today/records/start/complete)
│   │   ├── schedule            #   শিডিউল
│   │   ├── order-list          #   অর্ডার
│   │   ├── scan-verify         #   QR স্ক্যান ভেরিফিকেশন
│   │   ├── member-list         #   মেম্বার তালিকা
│   │   ├── member-detail       #   মেম্বার ডিটেইল
│   │   ├── earnings            #   আয়
│   │   ├── withdrawal          #   উত্তোলন
│   │   ├── transaction-list    #   ট্রানজেকশন বিবরণ
│   │   └── training            #   ট্রেনিং
│   ├── user/                   # পার্সোনাল সেন্টার
│   │   ├── index               #   ব্যক্তিগত তথ্য
│   │   ├── settings            #   সেটিংস
│   │   └── switch-role         #   পরিচয় স্যুইচ
│   └── wallet/                 # ওয়ালেট (ব্যালেন্স/রিচার্জ/ট্রানজেকশন)
├── components/                 # পাবলিক কম্পোনেন্ট
│   ├── navbar
│   ├── tabbar
│   ├── service-card
│   ├── technician-card
│   ├── coupon-popup
│   └── lbs-selector
├── utils/                      # ইউটিলিটি
│   ├── api.js                  #   HTTP রিকোয়েস্ট
│   ├── auth.js                 #   অথেনটিকেশন ম্যানেজমেন্ট
│   ├── location.js             #   LBS লোকেশন
│   └── constants.js            #   কনস্ট্যান্ট
├── styles/                     # পাবলিক স্টাইল
└── images/                     # ইমেজ রিসোর্স
```

### apps/flutter/ — Flutter APP

```
apps/flutter/
├── lib/
│   ├── main.dart               # এন্ট্রি
│   ├── app.dart                # App কনফিগ/রাউট/থিম
│   ├── pages/                  # পেজ (মিনি-প্রোগ্রামের সাথে কাঠামো একই)
│   │   ├── auth/
│   │   ├── home/
│   │   ├── service/
│   │   ├── order/
│   │   ├── cart/
│   │   ├── technician/
│   │   ├── tech_work/
│   │   ├── user/
│   │   ├── marketing/
│   │   ├── message/
│   │   ├── store/
│   │   └── other/
│   ├── widgets/                # পাবলিক কম্পোনেন্ট
│   ├── services/               # API সার্ভিস
│   │   ├── api_service         #   HTTP (Dio)
│   │   ├── auth_service        #   অথেনটিকেশন
│   │   └── location_service    #   লোকেশন
│   ├── models/                 # ডেটা মডেল
│   ├── state/                  # স্টেট ম্যানেজমেন্ট
│   └── utils/                  # ইউটিলিটি
├── android/                    # Android প্রজেক্ট
├── ios/                        # iOS প্রজেক্ট
├── pubspec.yaml
└── ...
```

## মিডলওয়্যার এক্সিকিউশন চেইন

### service/

```
公开API:  Cors → Security → RateLimit → Controller
用户API:  Cors → Security → RateLimit → Auth → Controller
技师API:  Cors → Security → RateLimit → Auth → TechnicianAuth → Controller
支付回调: Cors → Security → Controller
```

### admin/

```
公开API:  Cors → Security → RateLimit → Controller
管理API:  Cors → Security → RateLimit → AdminAuth → AdminPermission → OperationLog → Controller
健康检查: Cors → Security → RateLimit → Controller
```

## ডেটাবেস টেবিল তালিকা

সব টেবিল `appointment_` প্রিফিক্স ব্যবহার করে, BIGINT নন-অটোইনক্রিমেন্ট প্রাইমারি কী (Snowflake জেনারেটেড)।

| ডোমেইন | টেবিল নাম | বিবরণ |
|----|------|------|
| ইউজার | appointment_user | ইউনিফাইড ইউজার টেবিল |
| ইউজার | appointment_user_address | প্রাপ্তির ঠিকানা |
| টেকনিশিয়ান | appointment_technician_profile | টেকনিশিয়ান প্রোফাইল |
| টেকনিশিয়ান | appointment_technician_schedule | টেকনিশিয়ান শিডিউল |
| টেকনিশিয়ান | appointment_technician_service | টেকনিশিয়ানের সার্ভিসযোগ্য আইটেম |
| টেকনিশিয়ান | appointment_technician_earnings | টেকনিশিয়ান আয় লেনদেন |
| টেকনিশিয়ান | appointment_technician_withdrawal | টেকনিশিয়ান উত্তোলন রেকর্ড |
| টেকনিশিয়ান | appointment_technician_attendance | টেকনিশিয়ান অ্যাটেন্ডেন্স |
| টেকনিশিয়ান | appointment_technician_member_note | মেম্বার প্রোফাইল |
| সার্ভিস | appointment_service_category | সার্ভিস ক্যাটাগরি |
| সার্ভিস | appointment_service | সার্ভিস আইটেম |
| সার্ভিস | appointment_product | পণ্য |
| সার্ভিস | appointment_store | স্টোর |
| অর্ডার | appointment_order | অর্ডার মূল টেবিল (সেকিল seckill_id অ্যাসোসিয়েটেড কলাম, রাউন্ড ২৪) |
| অর্ডার | appointment_order_item | অর্ডার ডিটেইল |
| অর্ডার | appointment_order_payment | পেমেন্ট রেকর্ড |
| অর্ডার | appointment_order_refund | রিফান্ড রেকর্ড |
| অর্ডার | appointment_order_review | সার্ভিস রিভিউ |
| অর্ডার | appointment_order_verification | ভেরিফিকেশন রেকর্ড |
| অর্ডার | appointment_order_reschedule | অ্যাপয়েন্টমেন্ট পুনঃনির্ধারণ রেকর্ড (রাউন্ড ১৭) |
| মার্কেটিং | appointment_coupon | কুপন ডেফিনিশন |
| মার্কেটিং | appointment_user_coupon | ইউজার কুপন |
| মার্কেটিং | appointment_user_coupon_transfer | কুপন ট্রান্সফার রেকর্ড (রাউন্ড ১৭) |
| মার্কেটিং | appointment_user_points_transfer | পয়েন্ট ট্রান্সফার রেকর্ড (রাউন্ড ১৯) |
| মার্কেটিং | appointment_technician_tier_log | টেকনিশিয়ান লেভেল পরিবর্তনের লগ (রাউন্ড ১৭) |
| মার্কেটিং | appointment_member_card | মেম্বার কার্ড ডেফিনিশন |
| মার্কেটিং | appointment_user_member_card | ইউজার মেম্বার কার্ড |
| মার্কেটিং | appointment_member_card_usage | টাইম কার্ড ব্যবহারের রেকর্ড |
| মার্কেটিং | appointment_user_points | পয়েন্ট লেনদেন |
| মার্কেটিং | appointment_gift_card | গিফট কার্ড |
| মার্কেটিং | appointment_user_referral | ইউজার প্রমোশন |
| মার্কেটিং | appointment_user_favorite | ইউজার ফেভারিট |
| ওয়ালেট | appointment_user_wallet | ইউজার ওয়ালেট ব্যালেন্স |
| ওয়ালেট | appointment_wallet_recharge | ওয়ালেট রিচার্জ রেকর্ড |
| ওয়ালেট | appointment_wallet_txn | ওয়ালেট ট্রানজেকশন |
| ওয়ালেট | appointment_wallet_transfer | ইউজারদের মধ্যে ট্রান্সফার রেকর্ড (রাউন্ড ১৯) |
| ইউজার | appointment_user_notify_setting | মেসেজ পছন্দ সেটিংস (রাউন্ড ১৯) |
| কনটেন্ট | appointment_banner | ক্যারোসেল |
| কনটেন্ট | appointment_announcement | নোটিশ |
| কনটেন্ট | appointment_platform_agreement | প্ল্যাটফর্ম চুক্তি |
| কনটেন্ট | appointment_faq | সাধারণ প্রশ্ন |
| কনটেন্ট | appointment_feedback | মতামত ফিডব্যাক |
| কনটেন্ট | appointment_moment | মোমেন্ট ফিড |
| কনটেন্ট | appointment_notification | মেসেজ নোটিফিকেশন |
| ফাইন্যান্স | appointment_finance_transaction | আয়-ব্যয় লেনদেন |
| ফাইন্যান্স | appointment_technician_commission_config | কমিশন কনফিগ |
| ফাইন্যান্স | appointment_withdrawal_account | উত্তোলন অ্যাকাউন্ট |
| ফাইন্যান্স | appointment_withdrawal_config | উত্তোলন সীমা কনফিগ |
| সিস্টেম | appointment_admin_user | ম্যানেজমেন্ট ইউজার (তৈরি করা হয়েছে) |
| সিস্টেম | appointment_admin_role | রোল (তৈরি করা হয়েছে) |
| সিস্টেম | appointment_admin_permission | পারমিশন (তৈরি করা হয়েছে) |
| সিস্টেম | appointment_admin_user_role | ইউজার রোল অ্যাসোসিয়েশন (তৈরি করা হয়েছে) |
| সিস্টেম | appointment_admin_role_permission | রোল পারমিশন অ্যাসোসিয়েশন (তৈরি করা হয়েছে) |
| সিস্টেম | appointment_system_config | সিস্টেম কনফিগ (তৈরি করা হয়েছে) |
| সিস্টেম | appointment_operation_log | অপারেশন লগ (তৈরি করা হয়েছে) |
| ইউজার | appointment_user_growth | গ্রোথ ভ্যালু লেনদেন (রাউন্ড ২০) |
| ইউজার | appointment_growth_level | গ্রোথ লেভেল ধাপ (রাউন্ড ২০) |
| অর্ডার | appointment_invoice | ইলেকট্রনিক ইনভয়েস (রাউন্ড ২০) |
| ইউজার | appointment_ticket | কাস্টমার সার্ভিস টিকিট (রাউন্ড ২০) |
| মার্কেটিং | appointment_referral_level2_reward | দ্বিতীয়-স্তর কমিশন রেকর্ড (রাউন্ড ২০) |
| ইউজার | appointment_invoice_title | ইনভয়েস টাইটেল লাইব্রেরি (রাউন্ড ২১) |
| ইউজার | appointment_browse_history | ব্রাউজ হিস্ট্রি (রাউন্ড ২১) |
| মার্কেটিং | appointment_full_reduction_activity | ফুল-রিডাকশন অ্যাক্টিভিটি (রাউন্ড ২২) |
| টেকনিশিয়ান | appointment_technician_attendance | টেকনিশিয়ান অ্যাটেন্ডেন্স (রাউন্ড ২২) |
| সিস্টেম | appointment_push_log | APP পুশ রেকর্ড (রাউন্ড ২২) |
| ফাইন্যান্স | appointment_profit_sharing | উইচ্যাট প্রফিট শেয়ারিং রেকর্ড (রাউন্ড ২২) |
| অর্ডার | appointment_order_status_log | অর্ডার স্ট্যাটাস টাইমলাইন (রাউন্ড ২৩) |
| ইউজার | appointment_user_health_profile | ইউজার হেলথ প্রোফাইল (রাউন্ড ২৩) |
| মার্কেটিং | appointment_lucky_wheel | হুইল প্রাইজ ডেফিনিশন (রাউন্ড ২৩) |
| মার্কেটিং | appointment_wheel_record | হুইল ড্র রেকর্ড (রাউন্ড ২৩) |
| মার্কেটিং | appointment_seckill_activity | সেকিল অ্যাক্টিভিটি (রাউন্ড ২৪) |
| সিস্টেম | appointment_app_version | APP ভার্সন (রাউন্ড ২৪) |

### সম্পূরক তালিকা (docs/install.sql-এর ৯৫টি টেবিলের মধ্যে উপরে তালিকাভুক্ত নয় এমন অংশ, সম্পূর্ণ কর্তৃত্বমূলক তালিকা install.sql অনুযায়ী)

| ডোমেইন | টেবিল নাম | বিবরণ |
|----|------|------|
| মার্কেটিং | appointment_card_transfer | টাইম কার্ড ট্রান্সফার |
| ইউজার | appointment_check_in | চেক-ইন |
| কনটেন্ট | appointment_community_post | কমিউনিটি ফিড |
| কনটেন্ট | appointment_community_comment | কমিউনিটি কমেন্ট |
| টেকনিশিয়ান | appointment_exam | অ্যাসেসমেন্ট |
| টেকনিশিয়ান | appointment_exam_question | অ্যাসেসমেন্ট প্রশ্ন |
| টেকনিশিয়ান | appointment_exam_attempt | অ্যাসেসমেন্ট উত্তরপত্র |
| সিস্টেম | appointment_operation_log_detail | অপারেশন লগ ডিটেইল |
| অর্ডার | appointment_order_aftersale | অর্ডার আফটার-সেলস |
| মার্কেটিং | appointment_points_exchange_goods | পয়েন্ট এক্সচেঞ্জ পণ্য |
| মার্কেটিং | appointment_promotion | গ্রুপ বাই অ্যাক্টিভিটি |
| মার্কেটিং | appointment_promotion_participant | গ্রুপ বাই অংশগ্রহণকারী |
| অর্ডার | appointment_queue_number | কিউ কলিং |
| সার্ভিস | appointment_service_package | সার্ভিস প্যাকেজ |
| টেকনিশিয়ান | appointment_service_record | সার্ভিস রেকর্ড |
| কনটেন্ট | appointment_share | শেয়ার রেকর্ড |
| অর্ডার | appointment_signature | সিগনেচার |
| টেকনিশিয়ান | appointment_technician_tier_config | টেকনিশিয়ান লেভেল কনফিগ |
| টেকনিশিয়ান | appointment_training_course | ট্রেনিং কোর্স |
| টেকনিশিয়ান | appointment_training_progress | ট্রেনিং প্রগ্রেস |
| ইউজার | appointment_user_device | ইউজার ডিভাইস |
| মার্কেটিং | appointment_user_points_exchange | পয়েন্ট এক্সচেঞ্জ রেকর্ড |
| কনটেন্ট | appointment_video_post | ভিডিও ফিড |
| অর্ডার | appointment_waitlist | ওয়েটলিস্ট |

## বাহ্যিক সার্ভিস রিজার্ভ

| সার্ভিস | ব্যবহার | ইন্টিগ্রেশন পয়েন্ট |
|------|------|--------|
| উইচ্যাট ওপেন প্ল্যাটফর্ম | উইচ্যাট লগইন/UnionID | WechatAuthService |
| উইচ্যাট পেমেন্ট | পেমেন্ট/রিফান্ড/উত্তোলন | WechatPayService |
| SMS প্রোভাইডার | ভেরিফিকেশন কোড/নোটিফিকেশন | SmsService |
| ম্যাপ সার্ভিস | LBS লোকেশন/নেভিগেশন/দূরত্ব হিসাব | MapService |
