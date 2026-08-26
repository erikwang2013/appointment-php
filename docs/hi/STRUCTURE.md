# अपॉइंटमेंट सेवा प्रणाली — परियोजना संरचना
> **Languages**: [中文](../STRUCTURE.md) · [English](../en/STRUCTURE.md) · [한국어](../ko/STRUCTURE.md) · [Русский](../ru/STRUCTURE.md) · [Deutsch](../de/STRUCTURE.md) · [Français](../fr/STRUCTURE.md) · [Español](../es/STRUCTURE.md) · [Português](../pt/STRUCTURE.md) · [العربية](../ar/STRUCTURE.md) · [বাংলা](../bn/STRUCTURE.md) · [Bahasa Indonesia](../id/STRUCTURE.md) · [日本語](../ja/STRUCTURE.md)

## रिपॉज़िटरी अवलोकन

```
appointment-php/
├── admin/              # प्रबंधन बैकएंड (webman v2 + Flutter Web)
├── service/            # बिज़नेस API सेवा (webman v2)
├── apps/               # उपयोगकर्ता-पक्ष फ्रंटएंड ऐप्स
│   ├── wechat/         #   वीचैट मिनी प्रोग्राम (नेटिव)
│   ├── flutter/        #   Flutter APP (iOS + Android)
│   └── harmonyos/      #   HarmonyOS APP (हारमनी ओएस नेटिव)
├── docs/               # परियोजना दस्तावेज़
└── .claude/            # Claude Code कॉन्फ़िगरेशन
```

## परियोजना संबंध

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

## admin/ — प्रबंधन बैकएंड

```
admin/
├── app/
│   ├── admin/controller/       # प्रबंधन-पक्ष नियंत्रक
│   │   ├── BaseController          # आधार नियंत्रक
│   │   ├── DashboardController     # डैशबोर्ड
│   │   ├── UserController          # उपयोगकर्ता प्रबंधन
│   │   ├── RoleController          # भूमिका प्रबंधन
│   │   ├── PermissionController    # अनुमति प्रबंधन
│   │   ├── ConfigController        # सिस्टम कॉन्फ़िगरेशन
│   │   ├── LogController           # संचालन लॉग
│   │   ├── ProfileController       # व्यक्तिगत केंद्र
│   │   ├── ExportController        # निर्यात
│   │   ├── ImportController        # आयात
│   │   ├── UploadController        # फ़ाइल अपलोड
│   │   ├── HealthController        # स्वास्थ्य जाँच
│   │   ├── DocsController          # API दस्तावेज़
│   │   ├── MetricsController       # Prometheus मेट्रिक्स
│   │   │                            # ✅ लागू बिज़नेस मॉड्यूल:
│   │   ├── TechnicianController    #   तकनीशियन प्रबंधन (सूची/ऑडिट/शेड्यूल/निर्यात)
│   │   ├── MemberController        #   सदस्य प्रबंधन (स्तर/उपभोग)
│   │   ├── StoreController         #   स्टोर CRUD
│   │   ├── ServiceController       #   सेवा आइटम CRUD
│   │   ├── ServiceCategoryController # सेवा श्रेणी CRUD (ट्री)
│   │   ├── ProductController       #   उत्पाद CRUD
│   │   ├── MallOrderController     #   मॉल ऑर्डर/डिलीवरी/आफ्टर-सेल
│   │   ├── SalesStatsController    #   बिक्री आँकड़े (Redis कैश)
│   │   ├── AppointmentOrderController  # अपॉइंटमेंट ऑर्डर (रद्द/पूर्ण)
│   │   ├── MemberCardController    #   सदस्यता कार्ड परिभाषा CRUD
│   │   ├── ReviewController        #   सेवा समीक्षा प्रबंधन
│   │   ├── ReportController        #   डेटा रिपोर्ट आँकड़े
│   │   ├── CouponController        #   कूपन CRUD
│   │   ├── FinanceController       #   वित्तीय लेन-देन/आँकड़े
│   │   ├── WithdrawalController    #   विड्रॉल ऑडिट (मंज़ूर/अस्वीकृत/पूर्ण)
│   │   ├── CommissionController    #   कमीशन सेटिंग/इनाम-दंड
│   │   ├── WithdrawalAccountController # विड्रॉल खाता प्रबंधन
│   │   ├── WithdrawalConfigController  # विड्रॉल सीमा कॉन्फ़िगरेशन
│   │   ├── BannerController        #   कैरोसेल CRUD
│   │   ├── AnnouncementController  #   घोषणा CRUD/प्रकाशन
│   │   ├── FaqController           #   सामान्य प्रश्न CRUD
│   │   ├── FeedbackController      #   फ़ीडबैक/उत्तर
│   │   ├── MomentController        #   मोमेंट्स ऑडिट
│   │   ├── AgreementController     #   समझौता संपादन/प्रकाशन
│   │   ├── AboutController         #   हमारे बारे में सेटिंग
│   │   └── SystemMessageController #   सिस्टम मैसेज टेम्पलेट/भेजना
│   │   │                            # ✅ विस्तार मॉड्यूल:
│   │   ├── ServiceCardController    #   कार्ड आइटम डिज़ाइन
│   │   ├── SystemMonitorController  #   सिस्टम मॉनिटरिंग
│   │   ├── IpBlacklistController    #   IP ब्लैकलिस्ट प्रबंधन
│   │   ├── DbBackupController       #   डेटाबेस बैकअप
│   │   ├── SmsConfigController      #   SMS कॉन्फ़िगरेशन
│   │   ├── StorageConfigController  #   स्टोरेज कॉन्फ़िगरेशन
│   │   ├── StoreManagerController   #   स्टोर मैनेजर खाता
│   │   ├── TrainingController       #   तकनीशियन प्रशिक्षण
│   │   ├── ScheduledTaskController  #   शेड्यूल कार्य
│   │   ├── CustomerProfileController #  ग्राहक प्रोफ़ाइल
│   │   ├── BatchMessageController   #   बैच पुश
│   │   ├── RefundWorkflowController #   रिफंड ऑडिट
│   │   ├── TechnicianTierController #   तकनीशियन स्तर
│   │   │                            # ✅ राउंड 22-25 में नए जोड़े गए:
│   │   ├── FullReductionController  #   फुल-रिडक्शन गतिविधि
│   │   ├── AttendanceController     #   तकनीशियन उपस्थिति
│   │   ├── ProfitSharingController  #   वीचैट प्रॉफिट शेयरिंग
│   │   ├── LuckyWheelController     #   पॉइंट्स व्हील
│   │   ├── PointsExchangeGoodsController # पॉइंट्स एक्सचेंज उत्पाद
│   │   ├── ReviewAuditController    #   समीक्षा छवि ऑडिट
│   │   ├── InvoiceController        #   ई-इनवॉइस
│   │   ├── TicketController         #   ग्राहक सेवा टिकट
│   │   ├── ReferralRewardController #   पहले-स्तर रेफरल कमीशन रिकॉर्ड
│   │   ├── ReferralLevel2Controller #   दूसरे-स्तर रेफरल कमीशन रिकॉर्ड
│   │   ├── ReturnCustomerController #   लौटने वाले ग्राहक इनाम
│   │   ├── SeckillController        #   सेकिल गतिविधि
│   │   ├── VersionController        #   APP संस्करण प्रबंधन
│   │   ├── TechnicianScheduleController # शेड्यूल प्रबंधन/CSV निर्यात
│   │   ├── AftersaleController      #   आफ्टर-सेल प्रोसेसिंग
│   │   ├── OrderVerificationController # वेरिफिकेशन रिकॉर्ड
│   │   ├── CommunityModerationController # कम्युनिटी ऑडिट
│   │   ├── VideoAuditController     #   वीडियो ऑडिट
│   │   └── InstallController        #   इंस्टॉलेशन विज़ार्ड
│   ├── api/v1/controller/      # सार्वजनिक API v1
│   │   ├── AuthController
│   │   └── CaptchaController
│   ├── common/                 # सार्वजनिक उपकरण
│   │   ├── HashidsService
│   │   ├── SnowflakeService
│   │   ├── EncryptionService
│   │   ├── TechnicianWithdrawalService
│   │   └── WechatPayService
│   ├── middleware/             # मिडलवेयर
│   │   ├── Cors
│   │   ├── RateLimit
│   │   ├── ApiVersion
│   │   ├── AdminAuth
│   │   ├── AdminPermission
│   │   └── OperationLog
│   ├── model/                  # डेटा मॉडल (केवल 6 विशिष्ट मॉडल: AdminPermission/AdminRole/AdminUser/OperationLog/OperationLogDetail/SystemConfig; बाकी psr-4 साझा service संस्करण)
│   ├── queue/                  # क्यू कार्य
│   └── process/                # प्रोसेस
├── apps/
│   ├── flutter/                # Flutter Web प्रबंधन बैकएंड फ्रंटएंड
│   │   └── lib/app/
│   │       ├── pages/           #   पेज (20)
│   │       │   ├── dashboard/   #   डैशबोर्ड
│   │       │   ├── login/       #   लॉगिन
│   │       │   ├── user/        #   उपयोगकर्ता प्रबंधन
│   │       │   ├── member/      #   सदस्य प्रबंधन
│   │       │   ├── role/        #   भूमिका/अनुमति
│   │       │   ├── config/      #   सिस्टम कॉन्फ़िगरेशन
│   │       │   ├── log/         #   संचालन लॉग
│   │       │   ├── profile/     #   व्यक्तिगत केंद्र
│   │       │   ├── technician/  #   तकनीशियन प्रबंधन
│   │       │   ├── schedule/    #   शेड्यूल
│   │       │   ├── service/     #   सेवा/उत्पाद प्रबंधन
│   │       │   ├── service_card/#   कार्ड आइटम डिज़ाइन
│   │       │   ├── order/       #   ऑर्डर प्रबंधन
│   │       │   ├── verification/#   वेरिफिकेशन रिकॉर्ड
│   │       │   ├── coupon/      #   कूपन
│   │       │   ├── withdrawal/  #   विड्रॉल ऑडिट
│   │       │   ├── report/      #   रिपोर्ट आँकड़े
│   │       │   ├── review/      #   समीक्षा प्रबंधन
│   │       │   ├── announcement/#   घोषणा
│   │       │   └── faq/         #   सामान्य प्रश्न
│   │       ├── services/        #   API सेवा परत
│   │       ├── layouts/         #   लेआउट
│   │       └── theme/           #   थीम
│   ├── harmonyos/               # HarmonyOS प्रबंधन-पक्ष (ArkTS)
│   └── weixin/                  # वीचैट प्रबंधन-पक्ष
├── config/                     # कॉन्फ़िगरेशन फ़ाइलें
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
│   └── backup/                 # बैकअप स्क्रिप्ट (टेबल संरचना और सीड डेटा एकीकृत: docs/install.sql)
├── docs/                       # प्रबंधन बैकएंड दस्तावेज़
├── public/                     # एंट्री फ़ाइल
├── runtime/                    # रनटाइम
├── tests/                      # टेस्ट
├── vendor/                     # निर्भरताएँ
├── CLAUDE.md
├── composer.json
├── Dockerfile
└── docker-compose.yml
```

## service/ — बिज़नेस API

```
service/
├── app/
│   ├── api/v1/controller/       # सार्वजनिक API v1 (26 नियंत्रक)
│   │   ├── AuthController          # लॉगिन/पंजीकरण/पासवर्ड भूलना/रिफ्रेश/भूमिका स्विच
│   │   ├── CaptchaController       # SMS वेरिफिकेशन कोड (Redis रेट-लिमिट)
│   │   ├── CommonController        # सार्वजनिक कॉन्फ़िग/समझौते/क्षेत्र
│   │   ├── ContentController       # कैरोसेल/घोषणा/लेख
│   │   ├── DocsController          # OpenAPI दस्तावेज़ (hg/apidoc)
│   │   ├── LbsController           # आस-पास के स्टोर (Haversine)/रिवर्स जियोकोड
│   │   ├── GuestController         # गेस्ट मोड (बिना लॉगिन के केवल-पठन ब्राउज़िंग, Redis कैश)
│   │   ├── SeckillController       # सेकिल गतिविधि/खरीद (अलग चैनल)
│   │   ├── PromotionController     # ग्रुप बाय (पुराना flash_sale चैनल बंद)
│   │   ├── ServiceController       # सेवा श्रेणी/आइटम/उत्पाद/स्टोर
│   │   ├── ServicePackageController # सेवा पैकेज
│   │   ├── StoreManagerController  # स्टोर मैनेजर वर्कबेंच (overview/orders/technicians/revenue)
│   │   ├── TechnicianController    # तकनीशियन सार्वजनिक जानकारी
│   │   ├── BrowseHistoryController # ब्राउज़िंग हिस्ट्री
│   │   ├── CalendarController      # अपॉइंटमेंट कैलेंडर (महीना/दिन दृश्य)
│   │   ├── CommunityController     # कम्युनिटी फ़ीड
│   │   ├── CommunityCommentController # कम्युनिटी टिप्पणियाँ
│   │   ├── FullReductionController # फुल-रिडक्शन गतिविधि
│   │   ├── PaymentNotifyController # भुगतान कॉलबैक (वीचैट/अलीपे)
│   │   ├── PrintController         # प्रिंटिंग
│   │   ├── PrivacyController       # गोपनीयता अनुपालन (डेटा निर्यात/खाता हटाना)
│   │   ├── QueueController         # कतार/कॉलिंग
│   │   ├── VersionController       # APP संस्करण प्रबंधन/अपडेट जाँच
│   │   ├── VideoController         # वीडियो
│   │   ├── WechatController        # वीचैट से संबंधित
│   │   └── WheelController         # पॉइंट्स लकी व्हील
│   ├── user/v1/controller/      # उपयोगकर्ता मॉड्यूल v1 (14 नियंत्रक)
│   │   ├── ProfileController       # व्यक्तिगत जानकारी/पासवर्ड/फ़ोन/खाता हटाना/लॉगआउट
│   │   ├── AddressController       # पता CRUD (डिफ़ॉल्ट पता प्रबंधन)
│   │   ├── FavoriteController      # पसंदीदा (सेवा/तकनीशियन)
│   │   ├── FeedbackController      # फ़ीडबैक (टेक्स्ट + छवि)
│   │   ├── ReferralController      # प्रमोशन/QR कोड/रेफर किए गए उपयोगकर्ता
│   │   ├── CheckInController       # चेक-इन/अटेंडेंस
│   │   ├── DeviceController        # उपयोगकर्ता डिवाइस प्रबंधन
│   │   ├── GrowthController        # ग्रोथ लेवल (overview/records/levels)
│   │   ├── HealthProfileController # स्वास्थ्य प्रोफ़ाइल
│   │   ├── InvoiceController       # ई-इनवॉइस आवेदन/सूची/विवरण
│   │   ├── InvoiceTitleController  # इनवॉइस शीर्षक लाइब्रेरी
│   │   ├── NotifySettingController # मैसेज प्रेफरेंस सेटिंग
│   │   ├── PointsTransferController# पॉइंट्स ट्रांसफर
│   │   └── TicketController        # ग्राहक सेवा टिकट
│   ├── technician/v1/controller/ # तकनीशियन मॉड्यूल v1 (10 नियंत्रक)
│   │   ├── ProfileController       # तकनीशियन प्रोफ़ाइल/आवेदन
│   │   ├── ScheduleController      # शेड्यूल क्वेरी/सेटिंग
│   │   ├── OrderController         # तकनीशियन ऑर्डर सूची
│   │   ├── WorkController          # वर्कबेंच (today/records/start/complete)
│   │   ├── EarningController       # आय अवलोकन + लेन-देन
│   │   ├── WithdrawController      # विड्रॉल आवेदन (हर महीने config('withdraw.gate_day') तारीख, कॉन्फ़िगर करने योग्य)
│   │   ├── ServiceRecordController # सेवा रिकॉर्ड
│   │   ├── ExamController          # ऑनलाइन परीक्षा
│   │   ├── AttendanceController    # चेक-इन/चेक-आउट उपस्थिति
│   │   └── ReviewController        # तकनीशियन समीक्षा उत्तर
│   ├── order/v1/controller/     # ऑर्डर मॉड्यूल v1 (8 नियंत्रक + 9 trait)
│   │   ├── OrderController         # ऑर्डर (तकनीशियन लॉक)/सूची/विवरण/रद्द/भुगतान/रिफंड/वेरिफिकेशन (एग्रीगेट एंट्री, 38 पंक्तियाँ, सभी विधियाँ trait से)
│   │   ├── OrderCreateTrait        # ऑर्डर निर्माण store/मूल्य-निर्धारण सहायक (475 पंक्तियाँ)
│   │   ├── OrderQueryTrait         # ऑर्डर क्वेरी सूची/विवरण/लॉजिस्टिक्स (205 पंक्तियाँ)
│   │   ├── OrderPayTrait           # भुगतान pay/बैलेंस भुगतान/पॉइंट्स कटौती (415 पंक्तियाँ)
│   │   ├── OrderCancelTrait        # ऑर्डर रद्द (272 पंक्तियाँ)
│   │   ├── OrderRefundTrait        # रिफंड आवेदन (379 पंक्तियाँ)
│   │   ├── OrderCompensateTrait    # रिफंड मुआवज़ा स्कैन + कूपन/पॉइंट्स वापसी (345 पंक्तियाँ)
│   │   ├── OrderVerifyTrait        # वेरिफिकेशन कमीशन/पॉइंट्स रिटर्न (256 पंक्तियाँ)
│   │   ├── OrderRescheduleTrait    # अपॉइंटमेंट रीशेड्यूल (181 पंक्तियाँ)
│   │   ├── OrderNotifyTrait        # नोटिफिकेशन सब्सक्रिप्शन/टेम्पलेट/इन-ऐप/WebSocket (195 पंक्तियाँ)
│   │   └── OrderLockTrait          # डिस्ट्रिब्यूटेड लॉक टूल (80 पंक्तियाँ)
│   │   ├── AftersaleController     # आफ्टर-सेल
│   │   ├── CartController          # कार्ट
│   │   ├── IcsController           # ICS कैलेंडर निर्यात
│   │   ├── ReviewController        # समीक्षा/फॉलो-अप समीक्षा
│   │   ├── SignatureController     # सिग्नेचर
│   │   ├── TimelineController      # ऑर्डर स्टेटस टाइमलाइन
│   │   └── WaitlistController      # वेटलिस्ट
│   ├── wallet/v1/controller/    # वॉलेट मॉड्यूल v1 (2 नियंत्रक)
│   │   ├── WalletController        # बैलेंस/रिचार्ज/लेन-देन लॉग/बैलेंस भुगतान
│   │   └── WalletTransferController# उपयोगकर्ताओं के बीच ट्रांसफर
│   ├── marketing/v1/controller/ # मार्केटिंग मॉड्यूल v1 (7 नियंत्रक)
│   │   ├── CouponController        # कूपन सूची/प्राप्त करें/ऑर्डर कटौती
│   │   ├── CardController          # सदस्यता कार्ड सूची/खरीद/सेशन कार्ड my/use
│   │   ├── PointController         # पॉइंट्स लेन-देन/उपभोग कैशबैक
│   │   ├── GiftCardController      # गिफ्ट कार्ड/रिडीम redeem
│   │   ├── MemberBenefitController # सदस्य लाभ
│   │   ├── MemberCardController    # सदस्यता कार्ड परिभाषा
│   │   └── PointsExchangeController# पॉइंट्स एक्सचेंज मॉल
│   ├── notification/v1/controller/ # नोटिफिकेशन मॉड्यूल v1 (1 नियंत्रक)
│   │   └── NotificationController  # नोटिफिकेशन सूची/पढ़ा हुआ चिह्नित करें
│   ├── common/                  # सामान्य क्षमताएँ (BaseController आदि)
│   ├── middleware/              # मिडलवेयर
│   │   ├── ApiVersion              # API संस्करण नियंत्रण (API-Version हेडर)
│   │   ├── Auth                    # JWT प्रमाणीकरण + उपयोगकर्ता स्थिति जाँच
│   │   ├── Cors                    # क्रॉस-ओरिजिन हैंडलिंग
│   │   ├── Security                # सुरक्षा जाँच (security-php)
│   │   └── TechnicianAuth          # तकनीशियन पहचान जाँच
│   └── model/                   # डेटा मॉडल (81)
│       ├── User.php → erik_user
│       ├── TechnicianProfile.php → erik_technician_profile
│       ├── Service.php → erik_service (ES: erik_services)
│       ├── Product.php → erik_product (ES: erik_products)
│       ├── Store.php → erik_store
│       ├── Order.php → erik_order (रिफंड नियम/स्टेट मशीन सहित)
│       ├── Coupon.php → erik_coupon
│       ├── MemberCard.php → erik_member_card
│       ├── Notification.php → erik_notification
│       └── ... (कुल 81 मॉडल फ़ाइलें; admin में 6 विशिष्ट मॉडल अतिरिक्त, कुल 87)
├── config/                     # कॉन्फ़िगरेशन फ़ाइलें
├── public/                     # एंट्री
├── runtime/                    # रनटाइम
├── vendor/                     # निर्भरताएँ
├── start.php
├── composer.json
└── Dockerfile
```

## apps/ — उपयोगकर्ता-पक्ष फ्रंटएंड

### apps/wechat/ — वीचैट मिनी प्रोग्राम

```
apps/wechat/
├── app.js                      # एप्लिकेशन एंट्री
├── app.json                    # ग्लोबल कॉन्फ़िगरेशन
├── app.wxss                    # ग्लोबल स्टाइल
├── pages/
│   ├── auth/                   # प्रमाणीकरण
│   │   ├── login               #   लॉगिन
│   │   ├── register            #   पंजीकरण
│   │   ├── forget-password     #   पासवर्ड भूलना
│   │   └── agreement           #   समझौता देखना
│   ├── home/                   # होमपेज (कैरोसेल/घोषणा/श्रेणियाँ/खोज)
│   ├── service/                # सेवाएँ
│   │   ├── list                #   सेवा सूची
│   │   └── detail              #   सेवा विवरण
│   ├── order/                  # ऑर्डर
│   │   ├── list                #   ऑर्डर सूची
│   │   ├── detail              #   ऑर्डर विवरण
│   │   └── confirm             #   ऑर्डर कन्फर्म
│   ├── cart/                   # कार्ट
│   ├── cards/                  # सदस्यता कार्ड (खरीद/मेरे कार्ड/सेशन कार्ड उपयोग my/use)
│   ├── gift-cards/             # गिफ्ट कार्ड (रिडीम redeem/जमा)
│   ├── points/                 # पॉइंट्स (लेन-देन/एक्सचेंज)
│   ├── marketing/              # मार्केटिंग (कूपन आदि)
│   ├── favorite/               # पसंदीदा
│   ├── feedback/               # फ़ीडबैक
│   ├── referral/               # प्रमोशन
│   ├── message/                # मैसेज
│   │   ├── list                #   मैसेज सूची
│   │   └── detail              #   मैसेज विवरण
│   ├── tech-work/              # तकनीशियन वर्कबेंच
│   │   ├── index               #   वर्कबेंच होम (today/records/start/complete)
│   │   ├── schedule            #   शेड्यूल
│   │   ├── order-list          #   ऑर्डर
│   │   ├── scan-verify         #   स्कैन वेरिफिकेशन
│   │   ├── member-list         #   सदस्य सूची
│   │   ├── member-detail       #   सदस्य विवरण
│   │   ├── earnings            #   आय
│   │   ├── withdrawal          #   विड्रॉल
│   │   ├── transaction-list    #   लेन-देन विवरण
│   │   └── training            #   प्रशिक्षण
│   ├── user/                   # व्यक्तिगत केंद्र
│   │   ├── index               #   व्यक्तिगत जानकारी
│   │   ├── settings            #   सेटिंग
│   │   └── switch-role         #   भूमिका स्विच
│   └── wallet/                 # वॉलेट (बैलेंस/रिचार्ज/लेन-देन लॉग)
├── components/                 # सामान्य घटक
│   ├── navbar
│   ├── tabbar
│   ├── service-card
│   ├── technician-card
│   ├── coupon-popup
│   └── lbs-selector
├── utils/                      # उपकरण
│   ├── api.js                  #   HTTP अनुरोध
│   ├── auth.js                 #   प्रमाणीकरण प्रबंधन
│   ├── location.js             #   LBS लोकेशन
│   └── constants.js            #   कॉन्स्टेंट
├── styles/                     # सामान्य स्टाइल
└── images/                     # इमेज संसाधन
```

### apps/flutter/ — Flutter APP

```
apps/flutter/
├── lib/
│   ├── main.dart               # एंट्री
│   ├── app.dart                # App कॉन्फ़िग/रूट/थीम
│   ├── pages/                  # पेज (मिनी प्रोग्राम संरचना के अनुरूप)
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
│   ├── widgets/                # सामान्य घटक
│   ├── services/               # API सेवाएँ
│   │   ├── api_service         #   HTTP (Dio)
│   │   ├── auth_service        #   प्रमाणीकरण
│   │   └── location_service    #   लोकेशन
│   ├── models/                 # डेटा मॉडल
│   ├── state/                  # स्टेट प्रबंधन
│   └── utils/                  # उपकरण
├── android/                    # Android प्रोजेक्ट
├── ios/                        # iOS प्रोजेक्ट
├── pubspec.yaml
└── ...
```

## मिडलवेयर निष्पादन श्रृंखला

### service/

```
सार्वजनिक API:  Cors → Security → RateLimit → Controller
उपयोगकर्ता API:  Cors → Security → RateLimit → Auth → Controller
तकनीशियन API:  Cors → Security → RateLimit → Auth → TechnicianAuth → Controller
भुगतान कॉलबैक: Cors → Security → Controller
```

### admin/

```
सार्वजनिक API:  Cors → Security → RateLimit → Controller
प्रबंधन API:  Cors → Security → RateLimit → AdminAuth → AdminPermission → OperationLog → Controller
स्वास्थ्य जाँच: Cors → Security → RateLimit → Controller
```

## डेटाबेस टेबल सूची

सभी टेबल `erik_` उपसर्ग उपयोग करती हैं, प्राथमिक कुंजी BIGINT गैर-ऑटो-इंक्रीमेंट (Snowflake द्वारा उत्पन्न)।

| डोमेन | टेबल नाम | विवरण |
|----|------|------|
| उपयोगकर्ता | erik_user | एकीकृत उपयोगकर्ता टेबल |
| उपयोगकर्ता | erik_user_address | डिलीवरी पता |
| तकनीशियन | erik_technician_profile | तकनीशियन प्रोफ़ाइल |
| तकनीशियन | erik_technician_schedule | तकनीशियन शेड्यूल |
| तकनीशियन | erik_technician_service | तकनीशियन की सेवा आइटम |
| तकनीशियन | erik_technician_earnings | तकनीशियन आय लेन-देन |
| तकनीशियन | erik_technician_withdrawal | तकनीशियन विड्रॉल रिकॉर्ड |
| तकनीशियन | erik_technician_attendance | तकनीशियन उपस्थिति |
| तकनीशियन | erik_technician_member_note | सदस्य प्रोफ़ाइल |
| सेवा | erik_service_category | सेवा श्रेणी |
| सेवा | erik_service | सेवा आइटम |
| सेवा | erik_product | उत्पाद |
| सेवा | erik_store | स्टोर |
| ऑर्डर | erik_order | ऑर्डर मुख्य टेबल (सेकिल seckill_id संबंधित कॉलम, राउंड 24) |
| ऑर्डर | erik_order_item | ऑर्डर विवरण |
| ऑर्डर | erik_order_payment | भुगतान रिकॉर्ड |
| ऑर्डर | erik_order_refund | रिफंड रिकॉर्ड |
| ऑर्डर | erik_order_review | सेवा समीक्षा |
| ऑर्डर | erik_order_verification | वेरिफिकेशन रिकॉर्ड |
| ऑर्डर | erik_order_reschedule | अपॉइंटमेंट रीशेड्यूल रिकॉर्ड (राउंड 17) |
| मार्केटिंग | erik_coupon | कूपन परिभाषा |
| मार्केटिंग | erik_user_coupon | उपयोगकर्ता कूपन |
| मार्केटिंग | erik_user_coupon_transfer | कूपन ट्रांसफर रिकॉर्ड (राउंड 17) |
| मार्केटिंग | erik_user_points_transfer | पॉइंट्स ट्रांसफर रिकॉर्ड (राउंड 19) |
| मार्केटिंग | erik_technician_tier_log | तकनीशियन स्तर परिवर्तन लॉग (राउंड 17) |
| मार्केटिंग | erik_member_card | सदस्यता कार्ड परिभाषा |
| मार्केटिंग | erik_user_member_card | उपयोगकर्ता सदस्यता कार्ड |
| मार्केटिंग | erik_member_card_usage | सेशन कार्ड उपयोग रिकॉर्ड |
| मार्केटिंग | erik_user_points | पॉइंट्स लेन-देन |
| मार्केटिंग | erik_gift_card | गिफ्ट कार्ड |
| मार्केटिंग | erik_user_referral | उपयोगकर्ता प्रमोशन |
| मार्केटिंग | erik_user_favorite | उपयोगकर्ता पसंदीदा |
| वॉलेट | erik_user_wallet | उपयोगकर्ता वॉलेट बैलेंस |
| वॉलेट | erik_wallet_recharge | वॉलेट रिचार्ज रिकॉर्ड |
| वॉलेट | erik_wallet_txn | वॉलेट लेन-देन लॉग |
| वॉलेट | erik_wallet_transfer | उपयोगकर्ताओं के बीच ट्रांसफर रिकॉर्ड (राउंड 19) |
| उपयोगकर्ता | erik_user_notify_setting | मैसेज प्रेफरेंस सेटिंग (राउंड 19) |
| सामग्री | erik_banner | कैरोसेल |
| सामग्री | erik_announcement | घोषणा |
| सामग्री | erik_platform_agreement | प्लेटफ़ॉर्म समझौता |
| सामग्री | erik_faq | सामान्य प्रश्न |
| सामग्री | erik_feedback | फ़ीडबैक |
| सामग्री | erik_moment | मोमेंट्स फ़ीड |
| सामग्री | erik_notification | मैसेज नोटिफिकेशन |
| वित्त | erik_finance_transaction | आय-व्यय लेन-देन |
| वित्त | erik_technician_commission_config | कमीशन कॉन्फ़िगरेशन |
| वित्त | erik_withdrawal_account | विड्रॉल खाता |
| वित्त | erik_withdrawal_config | विड्रॉल सीमा कॉन्फ़िगरेशन |
| सिस्टम | erik_admin_user | प्रबंधन उपयोगकर्ता (बन चुका) |
| सिस्टम | erik_admin_role | भूमिका (बन चुका) |
| सिस्टम | erik_admin_permission | अनुमति (बन चुका) |
| सिस्टम | erik_admin_user_role | उपयोगकर्ता-भूमिका संबंध (बन चुका) |
| सिस्टम | erik_admin_role_permission | भूमिका-अनुमति संबंध (बन चुका) |
| सिस्टम | erik_system_config | सिस्टम कॉन्फ़िगरेशन (बन चुका) |
| सिस्टम | erik_operation_log | संचालन लॉग (बन चुका) |
| उपयोगकर्ता | erik_user_growth | ग्रोथ वैल्यू लेन-देन (राउंड 20) |
| उपयोगकर्ता | erik_growth_level | ग्रोथ लेवल स्लॉट (राउंड 20) |
| ऑर्डर | erik_invoice | ई-इनवॉइस (राउंड 20) |
| उपयोगकर्ता | erik_ticket | ग्राहक सेवा टिकट (राउंड 20) |
| मार्केटिंग | erik_referral_level2_reward | दूसरे-स्तर रेफरल कमीशन रिकॉर्ड (राउंड 20) |
| उपयोगकर्ता | erik_invoice_title | इनवॉइस शीर्षक लाइब्रेरी (राउंड 21) |
| उपयोगकर्ता | erik_browse_history | ब्राउज़िंग हिस्ट्री (राउंड 21) |
| मार्केटिंग | erik_full_reduction_activity | फुल-रिडक्शन गतिविधि (राउंड 22) |
| तकनीशियन | erik_technician_attendance | तकनीशियन उपस्थिति (राउंड 22) |
| सिस्टम | erik_push_log | APP पुश रिकॉर्ड (राउंड 22) |
| वित्त | erik_profit_sharing | वीचैट प्रॉफिट शेयरिंग रिकॉर्ड (राउंड 22) |
| ऑर्डर | erik_order_status_log | ऑर्डर स्टेटस टाइमलाइन (राउंड 23) |
| उपयोगकर्ता | erik_user_health_profile | उपयोगकर्ता स्वास्थ्य प्रोफ़ाइल (राउंड 23) |
| मार्केटिंग | erik_lucky_wheel | व्हील पुरस्कार परिभाषा (राउंड 23) |
| मार्केटिंग | erik_wheel_record | व्हील ड्रॉ रिकॉर्ड (राउंड 23) |
| मार्केटिंग | erik_seckill_activity | सेकिल गतिविधि (राउंड 24) |
| सिस्टम | erik_app_version | APP संस्करण (राउंड 24) |

### पूरक सूची (docs/install.sql 95 टेबल में ऊपर सूचीबद्ध नहीं किए गए भाग; पूर्ण आधिकारिक सूची install.sql अनुसार)

| डोमेन | टेबल नाम | विवरण |
|----|------|------|
| मार्केटिंग | erik_card_transfer | सेशन कार्ड ट्रांसफर |
| उपयोगकर्ता | erik_check_in | चेक-इन |
| सामग्री | erik_community_post | कम्युनिटी फ़ीड |
| सामग्री | erik_community_comment | कम्युनिटी टिप्पणियाँ |
| तकनीशियन | erik_exam | परीक्षा |
| तकनीशियन | erik_exam_question | परीक्षा प्रश्न |
| तकनीशियन | erik_exam_attempt | परीक्षा उत्तर पत्रक |
| सिस्टम | erik_operation_log_detail | संचालन लॉग विवरण |
| ऑर्डर | erik_order_aftersale | ऑर्डर आफ्टर-सेल |
| मार्केटिंग | erik_points_exchange_goods | पॉइंट्स एक्सचेंज उत्पाद |
| मार्केटिंग | erik_promotion | ग्रुप बाय गतिविधि |
| मार्केटिंग | erik_promotion_participant | ग्रुप बाय भागीदार |
| ऑर्डर | erik_queue_number | कतार/कॉलिंग |
| सेवा | erik_service_package | सेवा पैकेज |
| तकनीशियन | erik_service_record | सेवा रिकॉर्ड |
| सामग्री | erik_share | शेयर रिकॉर्ड |
| ऑर्डर | erik_signature | सिग्नेचर |
| तकनीशियन | erik_technician_tier_config | तकनीशियन स्तर कॉन्फ़िगरेशन |
| तकनीशियन | erik_training_course | प्रशिक्षण पाठ्यक्रम |
| तकनीशियन | erik_training_progress | प्रशिक्षण प्रगति |
| उपयोगकर्ता | erik_user_device | उपयोगकर्ता डिवाइस |
| मार्केटिंग | erik_user_points_exchange | पॉइंट्स एक्सचेंज रिकॉर्ड |
| सामग्री | erik_video_post | वीडियो फ़ीड |
| ऑर्डर | erik_waitlist | वेटलिस्ट |

## बाहरी सेवा रिज़र्वेशन

| सेवा | उपयोग | एकीकरण बिंदु |
|------|------|--------|
| वीचैट ओपन प्लेटफ़ॉर्म | वीचैट लॉगिन/UnionID | WechatAuthService |
| वीचैट पे | भुगतान/रिफंड/विड्रॉल | WechatPayService |
| SMS सेवा प्रदाता | वेरिफिकेशन कोड/नोटिफिकेशन | SmsService |
| मानचित्र सेवा | LBS लोकेशन/नेविगेशन/दूरी गणना | MapService |
