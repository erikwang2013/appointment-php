> الترجمة العربية · الأصل: [中文](../../README.md)

# نظام خدمات الحجز
> **Languages**: [中文](../README.md) · [English](../en/README.md) · [한국어](../ko/README.md) · [Русский](../ru/README.md) · [Deutsch](../de/README.md) · [Français](../fr/README.md) · [Español](../es/README.md) · [Português](../pt/README.md) · [हिन्दी](../hi/README.md) · [বাংলা](../bn/README.md) · [Bahasa Indonesia](../id/README.md) · [日本語](../ja/README.md)

منصة إدارة الحجوزات بأربع واجهات: برنامج WeChat الصغير + تطبيق Flutter + تطبيق HarmonyOS (تبديل الهوية بين الحسابات) + لوحة إدارة الكمبيوتر.

> **حالة المشروع**: مكتمل بالكامل ✅ | 143 وحدة تحكم (service 69 / admin 74) | 87 نموذجًا | 722 اختبارًا (service 558 / admin 164) | 95 جدول بيانات | 388 مسارًا (service 227 / admin 161)

## مقدمة المشروع

<img src="../diagrams/mascot.svg" alt="تميمة نظام خدمات الحجز — الأرنب الصغير (رسوم SVG متحركة)" width="200" align="right">

**نظام خدمات الحجز** هو منصة إدارة حجوزات بأربع واجهات موجهة لقطاع الخدمات اليومية: تغطي واجهات المستخدم **برنامج WeChat الصغير وتطبيق Flutter وتطبيق HarmonyOS** بثلاث واجهات، مع تبديل حر بين الحسابات عبر الواجهات، إلى جانب **لوحة إدارة الكمبيوتر**، لتحقيق إغلاق رقمي كامل لدورة "حجز المستخدم ← قبول الفني ← تشغيل الخلفية". سواء كانت حجوزات المتاجر أو خدمات الفنيين أو تسويق العضويات أو التسوية المالية، نظام واحد ينجز كل شيء.

**تجربة حجز متكاملة في مكان واحد**

تجربة موحّدة عبر واجهات المستخدم الثلاث: اختيار الوقت مباشرة عبر التقويم، خصومات الكوبونات/بطاقات المرات/النقاط، عروض الفلاش والشراء الجماعي، الدفع عبر WeChat/الرصيد، وتتبع كامل لحالة الطلب — التغيير والإلغاء والاسترداد وخدمة ما بعد البيع والفواتير الإلكترونية تُنجز بالكامل عبر الإنترنت؛ يوفر جانب الفني لوحة عمل، وحضور الانصراف، وجدولة جماعية، والتحقق من الخدمة وموافقات السحب، بكفاءة تشغيلية واضحة.

**نمو تسويقي كامل السلسلة**

أكثر من عشرة أدوات تسويقية مدمجة: أنشطة تخفيض المبلغ، الفلاش، الشراء الجماعي، إهداء الكوبونات، متجر النقاط وعجلة الحظ، مزايا بطاقات العضوية/مستويات النمو، عمولة إحالة بمستويين، مكافآت العملاء العائدين، إلى جانب رسائل الاشتراك ودفع التطبيق، لمساعدة التجار على جذب عملاء جدد والاحتفاظ بهم وزيادة إعادة الشراء.

**أمان وامتثال بمستوى المؤسسات**

مكونات أمان مبنية داخليًا: مصادقة JWT، إخفاء المعرّفات، اكتشاف 31 نوع هجوم، تشفير مزدوج للبيانات الحساسة، تحقق من الأسعار في الخادم، مقارنة صارمة لاستدعاءات الدفع والحماية من التكرار، مع دعم تقسيم الأرباح الرسمي من WeChat، وتصدير البيانات الخاصة وحذف الحساب، تلبيةً لمتطلبات الامتثال.

**أساس تقني ناضج**

مبني على PHP 8.3 + إطار webman عالي الأداء المقيم، مدعوم بـ MySQL 8.0 + Redis + Elasticsearch؛ 95 جدول بيانات، 388 واجهة، 285 نقطة صلاحية دقيقة، 722 اختبارًا آليًا ناجحًا جميعها، مع وثائق معمارية كاملة باللغتين وبرنامج تثبيت بخطوة واحدة، جاهز للاستخدام الفوري وسهل التطوير الثانوي.

سواء كان حجز متجر واحد أو سلسلة فروع متعددة، يقدم نظام خدمات الحجز حلاً متكاملًا ومستقرًا وآمنًا وقابلًا للتوسع.

## هيكل المشروع

```
appointment-php/
├── admin/                     # 管理后台 (webman v2 + Flutter Web，独立部署 :8787)
│   ├── app/                   #   admin(后台控制器)/api/model/middleware/process/view
│   ├── apps/                  #   Flutter Web 后台 / HarmonyOS / 微信管理端
│   ├── config/                #   路由/数据库/进程/插件配置
│   ├── database/              #   备份脚本（表结构与种子数据统一见 docs/install.sql）
│   ├── tests/                 #   PHPUnit（#[\Test] 属性风格）
│   └── start.php
├── service/                   # 业务API服务 (webman v2，独立部署 :8787)
│   ├── app/                   #   api/user/technician/order/wallet/marketing/notification 等模块
│   ├── config/                #   路由/数据库/进程/支付等配置
│   ├── support/               #   Model 基类（generateId）/Request/Response
│   ├── tests/                 #   PHPUnit
│   └── start.php
├── apps/                      # 用户端前端应用
│   ├── wechat/                #   微信小程序（原生）
│   ├── flutter/               #   Flutter APP（iOS + Android）
│   └── harmonyos/             #   HarmonyOS APP（鸿蒙原生）
└── docs/                      # 项目文档
    ├── API.md / FEATURES.md / STRUCTURE.md / install.sql / README.md ...
    └── diagrams/              #   架构/流程图（SVG + mermaid）
```

## البدء السريع

### المتطلبات البيئية

- PHP 8.3+
- MySQL 8.0+
- Redis
- Composer

### معالج التثبيت عبر الويب (موصى به)

```bash
cd admin/
cp .env.example .env
composer install
php start.php start -d
```

افتح المتصفح على `http://localhost:8787/install` واتبع الإرشادات لإدخال قاعدة البيانات وحساب المدير لإتمام التثبيت.

### التثبيت اليدوي

```bash
# 1. 安装依赖
cd service/ && cp .env.example .env && composer install
cd ../admin/ && cp .env.example .env && composer install

# 2. 一键导入数据库（含全部 95 张表 + 权限/配置种子）
mysql -u root -p < docs/install.sql

# 3. 启动服务
cd service/ && php start.php start -d   # 业务API → :8787
cd ../admin/ && php start.php start -d  # 管理后台 → :8787
```

### النشر عبر Docker

```bash
cd admin/ && cp .env.docker .env && docker-compose up -d
cd ../service/ && cp .env.docker .env && docker-compose up -d
```

## الحزمة التقنية

| الطبقة | التقنية | الوصف |
|------|------|------|
| إطار الواجهة الخلفية | webman v2 (PHP 8.3+) | خدمة HTTP عالية الأداء مقيمة في الذاكرة |
| قاعدة البيانات | MySQL 8.0 | بادئة الجداول `appointment_` |
| التخزين المؤقت | Redis | تخزين مؤقت/تحديد معدل/Session/قوائم انتظار |
| البحث | Elasticsearch | بحث النص الكامل (عبر webman-scout) |
| واجهة لوحة الإدارة | Flutter Web | نمط لوحة إدارة الكمبيوتر |
| تطبيق المستخدم | Flutter | iOS + Android |
| برنامج المستخدم الصغير | برنامج WeChat الصغير الأصلي | WXML/WXSS/JS |
| تطبيق HarmonyOS | HarmonyOS ArkTS | أصلي @ohos.net.http |
| توليد المعرّفات | erikwang2013/snowflake-php | مفاتيح أساسية BIGINT غير تلقائية |
| تشفير معرّفات API | erikwang2013/hashids | إخفاء المعرّف الحقيقي خارجيًا |
| مصادقة JWT | erikwang2013/jwt-webman | Bearer Token |
| تشفير البيانات الحساسة | erikwang2013/encryption + encryptable | تشفير مزدوج API + قاعدة البيانات |
| الحماية الأمنية | erikwang2013/security-php | اكتشاف 31 نوع هجوم |
| التحقق من العمليات | erikwang2013/poster-php | تحقق عشوائي للعمليات الحساسة |
| أعلام الدول | erikwang2013/season | أيقونات الأعلام |
| مزامنة ES | erikwang2013/webman-scout | مزامنة تلقائية للنماذج |

## بنية النظام

<img src="../diagrams/ar-architecture.svg" alt="ar-architecture.svg" width="100%">

## العمليات الأساسية

### عملية حجز الخدمة

<img src="../diagrams/ar-appointment-flow.svg" alt="ar-appointment-flow.svg" width="100%">

### عملية الدفع والاسترداد

<img src="../diagrams/ar-payment-refund.svg" alt="ar-payment-refund.svg" width="100%">

## دورة حياة الطلب

<img src="../diagrams/ar-order-lifecycle.svg" alt="ar-order-lifecycle.svg" width="100%">

## البنية الأمنية

### نظام الدفاع العميق من سبع طبقات

<img src="../diagrams/ar-security-defense.svg" alt="ar-security-defense.svg" width="100%">

> للمزيد من الرسوم التفصيلية: [المخططات الانسيابية](diagrams/FLOWCHART.md) (تتضمن سحب الفني/تبديل الهوية) | [خريطة الوظائف](diagrams/FUNCTION-DIAGRAM.md) | [دورة الحياة الكاملة](diagrams/LIFECYCLE-DIAGRAM.md) | [البنية الأمنية الكاملة](diagrams/SECURITY-ARCHITECTURE.md)

## أبرز المزايا الأساسية (الجولات 6-24)

| الميزة | الوصف |
|------|------|
| محفظة شحن | جداول user_wallet / wallet_recharge / wallet_txn؛ الرصيد + السجل، شحن عبر WeChat Pay (استدعاء بادئة R)، دفع الطلبات بالرصيد (pay_channel=balance)، استرداد WeChat/الرصيد يعيد الشحن تلقائيًا |
| إكمال واجهة لوحة الإدارة | Flutter Web 21 صفحة: لوحة القيادة/المستخدمون/الأدوار/الإعدادات/السجلات/التحقق/الجدولة/الخدمات/الفنيون/الطلبات/الكوبونات/العضويات/بطاقات المرات/الإعلانات/الأسئلة الشائعة/السحب/التقييمات/التقارير/ما بعد البيع/مدير المتجر/الملف الشخصي |
| إحصائيات لوحة القيادة لحظية | الصفحة الرئيسية للإدارة تعرض ديناميكيًا 7 بطاقات إحصائيات (إجمالي المستخدمين/جدد اليوم/نشطون/سجل العمليات/حجوزات اليوم/سحوبات قيد المراجعة/فنيون قيد المراجعة) + مخططات اتجاه 30 يومًا (حجم الطلبات/المبالغ/مستخدمون جدد/نشاط) + مخطط دائري لحالة المستخدمين + آخر سجلات العمليات، كاش Redis svc:dashboard 300s |
| تقارير البيانات | 3 نقاط نهاية ReportController: إحصائيات الطلبات / أفضل 10 فنيين / توزيع القنوات (GET /admin/reports/orders\|technicians\|distribution، نطاق 7/30 يوم، كاش Redis 300s) + إحصائيات المبيعات (svc:sales_stats) + إحصائيات مالية (svc:finance_stats إيرادات/استردادات/سحوبات/عمولات) |
| رسائل اشتراك البرنامج الصغير | إشعارات اشتراك 3 سيناريوهات للطلبات (نجاح الدفع/وصول الاسترداد/نجاح التحقق)؛ push_sent_at ذرّي؛ تنازل تلقائي إلى إشعارات داخلية عند عدم إعداد القالب |
| سحب الفني | مراجعة في لوحة الإدارة؛ مبالغ ≥500 بموافقة مستويين (مدير المتجر←المالية)؛ آلة الحالة pending→approved→completed (rejected/failed) |
| إغلاق حلقة بطاقة المرات | بطاقاتي تحسب used_up/expired في الوقت الفعلي؛ تحقق Redis NX ذرّي + قفل صفوف لخصم المرات، إنشاء مباشر لطلب completed + OrderItem + OrderPayment(pay_type='card') |
| لوحة عمل الفني | مهام اليوم/سجلات الإنجاز/بدء·إتمام (قفل صفوف + حارس آلة الحالة + ذرّية، إشعار داخلي بعد الإتمام)؛ ثلاثة تبويبات tech-work في البرنامج الصغير |
| خصم الكوبون | PriceCalculator: applyCoupon قراءة حساب / consume وضع used عند الدفع / restoreCouponAndCard إرجاع ذرّي عند الاسترداد؛ fixed/percent + حد min_amount |
| بطاقة الهدايا | عند redeem يشحن نوع cash إلى المحفظة (قفل صفوف يمنع الإيداع المزدوج، WalletTxn type='gift_card')، نوع gift يُعلّم فقط |
| نظام النقاط | نقاط الحضور؛ نقاط الاستهلاك بعد التحقق floor(paid×1) (ذرّية عبر order_id، لقطة balance)؛ استرداد نسبي عند الاسترداد؛ سجل مفصل + تصفية type/source |
| إدارة العضويات | عمود appointment_user.member_level (ترحيل 000008)؛ CRUD كامل لبطاقات العضوية في لوحة الإدارة (صلاحيات 365-369) |
| سلسلة طلب البرنامج الصغير | تفاصيل الخدمة ← تأكيد الطلب (اختيار الكوبون/تعطيل الحد/تقدير السعر في العميل) ← POST /order ← دفع WeChat/الرصيد؛ 20 صفحة في البرنامج الصغير |
| إغلاق حلقة الشراء الجماعي | join تكرار مشاركة 422 + قفل الامتلاء + إغلاق كسول عند الانتهاء؛ بعد تكوين المجموعة يُرسل الطلب عبر store مع promotion_id بسعر المجموعة (discount_percent)، تعطيل تراكب الكوبون/بطاقة المرات/النقاط، إلغاء تلقائي للطلبات غير المكتملة وتحرير قفل الفني (قناة FLASH_SALE القديمة أُلغيت، الفلاش بقناة مستقلة) |
| لوحة عمل مدير المتجر | service /api/store-manager 4 واجهات (overview/orders/technicians/revenue) بعزل إلزامي store_id (بدون متجر 403)؛ نظرة عامة على لوحة عمل المتجر + تصفية الطلبات store_id + صفحة Flutter + صلاحية 372 |
| عمولة الإحالة | بعد إتمام أول طلب للمُحال، يحصل المُحيل على paid_amount × reward_rate (إعداد النظام، افتراضي 0.05) في المحفظة (WalletTxn referral_reward)؛ ثلاثية ذرّية: قفل صفوف + فحص فارغ + إعادة فحص أول طلب؛ تفاصيل earnings + عرض السجل في admin (صلاحية 379) |
| متجر صرف النقاط | جدولا منتجات الصرف/سجلات الصرف؛ واجهة الصرف Redis NX + قفل صفوف ضد الإفراط + uk_user_goods حد مرة واحدة لنفس المستخدم؛ ثلاث نتائج: coupon إصدار كوبون / wallet إيداع / gift_card بطاقة كلمة المرور؛ CRUD + تعليق/إزالة + سجلات في admin (صلاحيات 373-378) |
| إعادة جدولة الحجز | POST /api/order/reschedule/{id} تغيير الوقت مع نفس الفني؛ فقط pending/paid/confirmed وقبل ≥6 ساعات من بدء الخدمة الأصلية؛ order_lock + قفل وقت جديد للفني SETNX(180s) ضد التزامن الزائد + فحص تعارض الجدولة B2؛ كتابة appointment_order_reschedule + رسالة اشتراك SCENE_RESCHEDULE |
| إهداء الكوبون | رمز إهداء فريد 8 خانات (uk_code احتياطي، صالح 7 أيام)؛ claim ضد سوء الاستخدام: قفل Redis NX + إعادة فحص بقفل صفوف ضد الإنفاق المزدوج، uk_user_coupon حد إهداء مرة واحدة، الكوبون المهدى لا يُهدى مجددًا، لا يمكن استلامه ذاتيًا؛ استعادة كسولة للكوبون الأصلي عند الانتهاء |
| انتهاء النقاط | expires_at (افتراضي 365 يومًا، إعداد points.expiry_days)؛ PointsExpiryTimer مسح مؤشر 60 ثانية لكتابة type=expire بقيمة سالبة (ثلاثية ذرّية) + إشعار داخلي مجمع؛ النقاط المنتهية لا تُستخدم للصرف/الإهداء |
| تقييم تلقائي لمستوى الفني | TierRatingService حساب فوري لكمية الطلبات + متوسط الدرجات للكتابة في profile، مطابقة من الأعلى للأسفل وفق tier_config؛ ترقية فقط بدون تنزيل (allowDowngrade لإعادة التقييم اليدوي)؛ كتابة appointment_technician_tier_log + إشعار داخلي؛ عرض السجل في admin (صلاحية 380) |
| إغلاق حلقة الفلاش | /api/seckill أنشطة + buy ذرّي/مضاد للتزامن، حقن seckill_id في الطلب لإعادة استخدام store()، خصم المخزون داخل المعاملة بقفل صفوف موحد (سعر الفلاش = seckill_price يتبع قاعدة البيانات)، نفاد 422 «بُيع بسرعة»، الإلغاء لا يعيد المخزون؛ قناة flash_sale القديمة أُلغيت |
| تذكير قبل بدء الخدمة | ServiceReminderTimer مسح 60 ثانية لطلبات confirmed/serving التي تبدأ خلال ساعة ← SCENE_REMINDER رسالة اشتراك + إشعار داخلي (منع تكرار عبر order_id+type، ثلاثية ذرّية)؛ تنازل تلقائي عند عدم إعداد القالب |
| تذكير الانتهاء | ExpiryReminderTimer مسح 6 ساعات للعضويات/الكوبونات المنتهية خلال 3 أيام ← type=card_expiry/coupon_expiry + رسالة اشتراك SCENE_EXPIRY (order_id يسجل المصدر لمنع التكرار) |
| رد الفني على التقييم | POST /api/technician/review/reply/{order_id}: ليس لصاحب الطلب 404، رد مكرر 422، بعد الرد إشعار داخلي للمستخدم؛ appointment_order_review يُضيف replied_at؛ تفاصيل الرد في admin (صلاحية 381) |
| إشعار وصول الشحن | استدعاء شحن WeChat يكتب إشعارًا داخليًا type='wallet_recharge' داخل المعاملة (يعيد استخدام ذرّية الاستدعاء، إرسال ذرّي في نفس المعاملة، الفشل لا يعيق العملية الرئيسية) |
| تحويل الرصيد | POST /api/wallet/transfer تحويل بين المستخدمين: 0.01-1000/عملية + حد يومي 5000؛ قفل Redis NX + قفل صفوف محفظتي الطرفين (ترتيب تصاعدي user_id ضد الجمود) + client_token ذرّية 24 ساعة؛ سجلان WalletTxn transfer_out/transfer_in مع لقطة balance_after؛ إشعار داخلي للمستلم type='balance_received' |
| إهداء النقاط | POST /api/user/points/transfer إهداء بين المستخدمين: 1-10000 نقطة + حد يومي تراكمي 10000؛ قفل Redis NX + آخر سجل لكل طرف lockForUpdate (ترتيب تصاعدي ضد الجمود) + إعادة فحص داخل القفل؛ سجلان consume للمرسل/earn للمستلم (المستلم مع expires_at يمكن انتهاؤه طبيعيًا)؛ إشعار داخلي للمستلم type='points_received' |
| إضافة تقييم | POST /api/order/review/{order_id}/append: ليس لصاحب الطلب 404/مكرر 422/محتوى فارغ 422/غير completed 422، بعد النجاح إشعار داخلي للفني type='review_append'؛ appointment_order_review يضيف append_content/append_images(JSON)/append_at؛ مع تسجيل مسار تقييم المستخدم وإصلاح خطأ TypeError كامن |
| تتبع الشحن في واجهة المستخدم | GET /api/order/logistics/{id}: فقط طلبات المنتجات الخاصة بالمستخدم (404 لغير المالك/غير المنتج/لم يُشحن)؛ قراءة order.remark JSON (shipping_company/tracking_no/shipped_at، كتابة عند الشحن من admin)؛ إخفاء رقم هاتف المستلم 138****5678 |
| إعدادات تفضيل الإشعارات | جدول appointment_user_notify_setting (مفتاح فريد uk_user_type، صف افتراضي = مفتوح)؛ GET/PUT /api/user/notify-settings؛ 5 مفاتيح service_reminder/card_expiry/points_expiry/marketing/system (system دائم مفتوح)؛ notifySettingEnabled يتحكم في 3 مؤقتات + أحداث الاشتراك، عند الإغلاق تُتخطى الإشعارات الداخلية ورسائل الاشتراك |
| تقويم الحجوزات | GET /api/calendar/technician/{id} (عرض شهري) + /day (عرض يومي): فتح time_slots JSON إلى فتحات ساعات، استبعاد الفترات المحجوزة من appointment_order؛ اختيار مرئي لوقت جدولة المتجر |
| مستوى نمو المستخدم | appointment_user_growth + appointment_growth_level (برونزي 0/فضي 100/ذهبي 500/بلاتيني 2000/ماسي 5000)؛ الحضور +10، التقييم +20، كل 1 يوان استهلاك نقطة واحدة (ذرّية طبيعية عبر إعادة فحص الحالة القائمة)؛ GET /api/growth (نظرة عامة/سجلات/مستويات عامة) |
| الفاتورة الإلكترونية | POST/GET /api/invoices (طلب/قائمة/تفاصيل): uk_order_type(order_id,order_type) ضد الطلب المكرر، المبلغ يُخرج من الخادم؛ إصدار/رفض في admin (صلاحيات 382-384) |
| تذاكر خدمة العملاء | POST/GET /api/tickets + /{id}/close: تقديم المستخدم/قائمة/تفاصيل/إغلاق؛ رد في admin (صلاحيات 385/387) |
| توزيع متعدد المستويات — عمولة المستوى الثاني | بعد دفع الطلب يُمنح مُحيل المُحيل المباشر paid×level2_rate (إعداد 0.02): قفل صفوف معاملة + uk_order_referred ذرّية ضد الإرسال المكرر؛ WalletTxn TYPE_REFERRAL_LEVEL2؛ عرض السجل في admin (صلاحية 386) |
| مزايا مستوى النمو | إطلاق نموذج GrowthLevel.benefits: خصم بنسبة discount_rate عند الطلب حسب المستوى (لطلبات قياسية فقط، تراكب كوبون/بطاقة مرات→خصم مستوى، مبلغ الخصم في discount_amount + ملاحظات قابلة للتتبع، حماية سفلية اقتطاع إلى 0)؛ نقطة نمو من استدعاء الدفع floor(paid×points_multiplier) (تُلتقط لحظة الدفع دون رفع المستوى) |
| إدارة ترويسة الفاتورة | appointment_invoice_title مكتبة ترويسات شائعة: حفظ/تعديل/حذف/افتراضي (أول صف تلقائي افتراضي، حذف الافتراضي ينقل تلقائيًا، تعيين الافتراضي تصفير معاملة)؛ عند الطلب يمكن اختيار title_id مع الاحتفاظ بالإدخال اليدوي |
| رضا التذاكر | عند إغلاق التذكرة يمكن التقييم 1-5 (خارج النطاق 422، عدم التقديم متوافق مع NULL)؛ ملخص الرضا في admin: متوسط الدرجات/توزيع 1-5 نجوم/عد المقيّم وغير المقيّم (صلاحية 388) |
| مراجعة صور التقييمات | admin ReviewAuditController: قائمة التقييمات بالصور (تصفية JSON_LENGTH + join أسماء المستخدم/الفني)، إخفاء/استعادة (hide فقط visible، restore فقط hidden، تحقق ثنائي 422)؛ بعد الإخفاء تختفي تلقائيًا من قائمة تقييمات الفني (صلاحيات 389-391) |
| سجل التصفح | appointment_browse_history (uk_user_item التصفح المكرر يحدّث viewed_at فقط): تسجيل عند دخول تفاصيل الخدمة (try/catch لا يعيق العملية الرئيسية، تخطي عند عدم تسجيل الدخول)؛ قائمة مع معلومات الخدمة + hashid؛ حذف فردي/مسح للمالك فقط |

> إصلاحات الجولة الثامنة التشغيلية: إزالة 12 موقعًا من Poster::verify المخفية القاتلة؛ إحصاءات DashboardController تُستخدم الآن استعلامات Capsule Manager.
>
> استكمال الجولة 15: إرجاع النقاط (إلغاء/استرداد يعيد نقاط points_offset، 5 نقاط ربط refundOffsetPoints ذرّية)؛ حالة PromotionParticipant تحولت إلى ثوابت أعداد صحيحة (إصلاح تلف join 1366 في الوضع الصارم).
>
> استكمال الجولة 16: صرف النقاط (PointsExchangeController، نوع consume/source=exchange)؛ طلب الشراء الجماعي (appointment_order يضيف عمودي promotion_id/participant_id)؛ عمولة الإحالة (ReferralRewardService مرتبط بـ WorkController::complete).
>
> استكمال الجولة 17: إعادة جدولة الحجز (appointment_order_reschedule + واجهة reschedule)؛ إهداء الكوبونات (appointment_user_coupon_transfer + transfer/claim/transfers)؛ انتهاء النقاط (expires_at + عملية PointsExpiryTimer)؛ تقييم تلقائي لمستوى الفني (TierRatingService + appointment_technician_tier_log، صلاحية 380).
>
> إصلاح الجولة 17: إدراج إشعار AutoCancelTimer يستخدم الآن \support\Model::generateId() (كان يستدعي Snowflake::generate() غير الموجود، ففشل الإشعار التلقائي للإلغاء بصمت).
>
> استكمال الجولة 18: طلب الفلاش (store() يدعم سعر فلاش flash_sale)؛ تذكير قبل بدء الخدمة (ServiceReminderTimer + SCENE_REMINDER)؛ تذكير انتهاء العضويات/الكوبونات (ExpiryReminderTimer + SCENE_EXPIRY)؛ رد الفني على التقييم (واجهة review reply + عمود replied_at + صلاحية 381)؛ إشعار وصول الشحن (type='wallet_recharge' داخل معاملة الاستدعاء).
>
> استكمال الجولة 19: تحويل الرصيد (appointment_wallet_transfer + WalletTransferController، قفل صفوف مزدوج داخل الصلاحية + client_token ذرّية)؛ إهداء النقاط (appointment_user_points_transfer + PointsTransferController، حد يومي + سجلان متجهان)؛ إضافة تقييم (ثلاثة أعمدة appointment_order_review append + واجهة append + تسجيل مسار store)؛ تتبع الشحن في واجهة المستخدم (واجهة logistics + تحليل remark JSON + إخفاء رقم الهاتف)؛ إعدادات تفضيل الإشعارات (appointment_user_notify_setting + NotifySettingController + تحكم في 3 مؤقتات).
>
> استكمال الجولة 20: تقويم الحجوزات (CalendarController عرض شهري/يومي + استبعاد المحجوز)؛ مستوى نمو المستخدم (appointment_user_growth + appointment_growth_level 5 مستويات + ربط الحضور/التقييم/الاستهلاك)؛ الفاتورة الإلكترونية (appointment_invoice + uk_order_type ضد التكرار + إصدار/رفض في الخلفية، صلاحيات 382-384)؛ تذاكر خدمة العملاء (appointment_ticket تقديم/قائمة/تفاصيل/إغلاق + رد في الخلفية، صلاحيات 385/387)؛ توزيع متعدد المستويات — عمولة المستوى الثاني (payLevel2Reward قفل صفوف معاملة + uk_order_referred ذرّية، صلاحية 386).
>
> استكمال الجولة 21: إطلاق مزايا مستوى النمو (خصم discount_rate عند الطلب + مضاعف نقاط points_multiplier عند الدفع، ترحيل بذور 5 مستويات benefits)؛ إدارة ترويسة الفاتورة (appointment_invoice_title مكتبة الترويسات + ربط title_id عند الطلب)؛ رضا التذاكر (تقييم عند الإغلاق rating/rated_at + إحصاءات ملخصة في admin، صلاحية 388)؛ مراجعة صور التقييمات (ReviewAuditController إخفاء/استعادة، صلاحيات 389-391)؛ سجل تصفح المستخدم (appointment_browse_history + ربط التفاصيل + قائمة/حذف/مسح).
>
> استكمال الجولة 22: نشاط التخفيض (appointment_full_reduction خصم تلقائي + تحقق الحد، صلاحيات 396-400)؛ تصدير تقويم ICS (RFC5545 حجوزاتي)؛ حضور الفني بالبصمة (appointment_technician_attendance حضور/انصراف + علامة تأخير + إحصاءات admin، صلاحيات 392-393)؛ خدمة دفع APP (تجريد مدفوع بالتكوين + 5 نقاط ربط أحداث، appointment_push_log)؛ تقسيم الأرباح الرسمي من WeChat (appointment_profit_sharing_log مدفوع بالتكوين + تنازل، صلاحية 394)؛ خصوصية الامتثال (تصدير البيانات + حذف الحساب آلة حالة 72 ساعة close_status).
>
> استكمال الجولة 23: الملف الصحي للمستخدم (appointment_user_health_profile)؛ كلمة مرور دفع المحفظة (appointment_user_wallet pay_password إعداد/تحقق)؛ جدولة جماعية للفني (استيراد batch + فحص تعارض التداخل)؛ خط زمني لحالة الطلب (appointment_order_status_log 8 نقاط حالة + عرض في واجهة المستخدم/الخلفية)؛ عجلة حظ النقاط (appointment_lucky_wheel + appointment_wheel_record سحب موزون، صلاحيات 401-406)؛ فترة صلاحية النقاط (إعداد points.expiry_days + سجلات earn جديدة مع expires_at).
>
> استكمال الجولة 24: وضع الضيف (/api/guest/* تصفح للقراءة فقط بدون تسجيل دخول + تخزين Redis مؤقت)؛ الفلاش (appointment_seckill_activity + شراء Redis NX بقفل صفوف + حقن appointment_order.seckill_id، صلاحيات 407-411/420)؛ إدارة إصدارات APP والتحقق من التحديث (appointment_app_version + /api/app/version، صلاحيات 416-419)؛ مكافأة العميل العائد (جائزة استهلاك ثانية خلال 30 يومًا type=return_customer، صلاحيات 412-414)؛ تصدير جدولة CSV (UTF-8 BOM + تفاصيل الفترات، صلاحية 415).
>
> تقوية أمنية 2026-08-26: أسعار بنود طلبات واجهة الطلب تُؤخذ دائمًا من سجلات قاعدة البيانات (سعر العميل غير موثوق، target_type غير معروف 422، target_id يجب أن يكون hashid)، أسعار الشراء الجماعي/الفلاش تتبع قاعدة البيانات أيضًا؛ مخزون الفلاش يُخصم موحدًا بقفل صفوف داخل معاملة /api/order store() (SeckillController::buy لا يحجز مسبقًا، مع الاحتفاظ بقفل نشاط Redis + ذرّية client_token)؛ حجز مسبق في الطريق عند تقديم طلب سحب الفني، إعادة فحص قبل تحويل الموافقة، منع الدفع المزدوج في الموافقات المتزامنة؛ استدعاء دفع WeChat يقارن total_fee بصرامة مع مبلغ الطلب، إخفاء سجلات استدعاء Alipay؛ نجاح /install يكتب .install.lock تحقق مزدوج ضد إعادة التثبيت؛ تقارب إصدارات التبعيات (webman-scout 2.0.5 / opensearch-php ^2.6 / dompdf وsecurity-php وwebman-database مثبتة بدقة)؛ phpstan.neon للتطبيقين قابلة للتشغيل (php -d memory_limit=2G).

## التنقل في الوثائق

| الوثيقة | الوصف |
|------|------|
| [شرح البنية](ARCHITECTURE.md) | بنية النظام وعلاقات الواجهات والمكونات التقنية وتدفقات البيانات |
| [شرح الميزات](FEATURES.md) | قائمة الميزات الكاملة لواجهة المستخدم/الفني/لوحة الإدارة |
| [تصميم البنية](ARCHITECTURE-DESIGN.md) | تصميم الطبقات وسلسلة الوسائط وتصميم قاعدة البيانات والتصميم الأمني |
| [تصميم الميزات](FEATURE-DESIGN.md) | العمليات التجارية الأساسية والقواعد التجارية وآلة الحالة وقواعد الاسترداد |
| [وثائق API](API.md) | واجهات الأعمال + واجهات لوحة الإدارة، مع أمثلة طلب/استجابة + نقاط OpenAPI |
| [تعليمات التثبيت](INSTALL.md) | المتطلبات البيئية والنشر عبر Docker والمتغيرات البيئية والإعدادات الخارجية والأسئلة الشائعة |
| [تعليمات الاستخدام](USAGE.md) | إعداد لوحة الإدارة وعمليات المستخدم/الفني وقواعد الاسترداد (واجهات API في API.md) |
| [هيكل المشروع](STRUCTURE.md) | تخطيط الدليل الكامل وسلسلة تنفيذ الوسائط وقائمة جداول قاعدة البيانات |
| [تقرير الاختبار](TEST-REPORT.md) | تدقيق تغطية الاختبار الكاملة (558 حالة / 2508 تأكيدًا) |
| [مواصفات التصميم](specs/2026-05-26-appointment-system-design.md) | مواصفات تصميم النظام |
| [خطة التنفيذ](plans/2026-05-26-appointment-system-plan.md) | خطة تنفيذ على مراحل |

## دعم المشروع / Support

إذا كان هذا المشروع مفيدًا لك، فنرحب بدعمك! شكرًا لتشجيعك :heart:

If this project helps you, your support is welcome and appreciated!

<table>
  <tr>
    <td align="center" width="50%">
      <img src="../weixinpay.png" alt="微信支付 / WeChat Pay" width="130" height="130"><br>
      <b>微信支付</b><br>WeChat Pay
    </td>
    <td align="center" width="50%">
      <img src="../alipay.png" alt="支付宝 / Alipay" width="130" height="130"><br>
      <b>支付宝</b><br>Alipay
    </td>
  </tr>
</table>

### تحويل بنكي عالمي / Global Bank Transfer

نرحب بالتحويلات المصرفية العالمية الداعمة (دولار هونغ كونغ / يوان / دولار أمريكي / عملات أخرى)، شكرًا لكرمك :heart:

Global bank transfer donations are welcome (HKD / CNY / USD / other currencies). Thank you for your generosity!

| البند Item | المعلومات Details |
|-----------|-------------|
| اسم المستفيد Beneficiary Name | WANG KEXUN |
| رقم الحساب Account Number | 881015918251 |
| البنك المستلم Bank | ZA Bank Limited（رمز SWIFT：AABLHKHHXXX，رمز البنك Bank Code：387） |
| عنوان البنك Bank Address | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **البنك الوسيط للتحويلات العابرة (عند الحاجة) / Intermediary Bank (if required)**
> هذه معلومات البنك الوسيط (بنك العبور) للتحويلات العابرة، وليست معلومات البنك المستلم، يرجى الاستفسار من بنك التحويل عما إذا كان مطلوبًا تقديمها.
> Note: this is intermediary bank information, not the receiving bank. Please check with your remitting bank whether it is required.
>
> - للتحويل بدولار هونغ كونغ واليوان والدولار الأمريكي (For HKD / CNY / USD)：**Citibank N.A. Hong Kong** — رمز SWIFT：CITIHKHXXXX، رمز البنك Bank Code：006، اسم الفرع Branch：Hong Kong Branch، رمز الفرع Branch Code：391، العنوان Address：Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - للتحويل بعملات أخرى (For other currencies)：**The Bank of New York Mellon** — رمز SWIFT：IRVTUS3NXXX，العنوان Address：240 Greenwich Street, New York, United States

## حقوق النشر

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

### التبرع بالعملات الرقمية (Crypto Donation)

إذا كان هذا المشروع مفيدًا لك، فمرحبًا بمسح رمز الاستجابة السريعة للتبرع، شكرًا لك!

| الشبكة (Network) | رمز QR (QR Code) | عنوان المحفظة (Wallet Address) |
|---|---|---|
| BNB Smart Chain (BEP20) | [<img src="../coin/1.jpg" width="150" alt="BNB Smart Chain (BEP20)">](../coin/1.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Tron (TRC20) | [<img src="../coin/2.jpg" width="150" alt="Tron (TRC20)">](../coin/2.jpg) | `TEdDHWLajt1XvqtPDWmQctdrJaC3pzZZzz` |
| Ethereum (ERC20) | [<img src="../coin/3.jpg" width="150" alt="Ethereum (ERC20)">](../coin/3.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Aptos | [<img src="../coin/4.jpg" width="150" alt="Aptos">](../coin/4.jpg) | `0x836e3780edfc3f7b2372b39e2a1a3a5d7adfaccd96c726f21cfde1b50dd68030` |
| Plasma | [<img src="../coin/5.jpg" width="150" alt="Plasma">](../coin/5.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Polygon POS | [<img src="../coin/6.jpg" width="150" alt="Polygon POS">](../coin/6.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Solana | [<img src="../coin/7.jpg" width="150" alt="Solana">](../coin/7.jpg) | `2hfhboHdmdrYsY25XfQSsEWxq5ip4EQsR7f4AzSRMUyr` |
| The Open Network (TON) | [<img src="../coin/8.jpg" width="150" alt="The Open Network (TON)">](../coin/8.jpg) | `UQB9kFQohzmXUir9QSSZq01iwl9aQZIDdBpNmDklljRtCoGK` |
| Arbitrum One | [<img src="../coin/9.jpg" width="150" alt="Arbitrum One">](../coin/9.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| AVAX C-Chain | [<img src="../coin/10.jpg" width="150" alt="AVAX C-Chain">](../coin/10.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |

