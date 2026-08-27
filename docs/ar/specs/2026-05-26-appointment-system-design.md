# مواصفات تصميم نظام خدمات الحجز
> **Languages**: [中文](../../superpowers/specs/2026-05-26-appointment-system-design.md) · [English](../../en/specs/2026-05-26-appointment-system-design.md) · [한국어](../../ko/specs/2026-05-26-appointment-system-design.md) · [Русский](../../ru/specs/2026-05-26-appointment-system-design.md) · [Deutsch](../../de/specs/2026-05-26-appointment-system-design.md) · [Français](../../fr/specs/2026-05-26-appointment-system-design.md) · [Español](../../es/specs/2026-05-26-appointment-system-design.md) · [Português](../../pt/specs/2026-05-26-appointment-system-design.md) · [हिन्दी](../../hi/specs/2026-05-26-appointment-system-design.md) · [বাংলা](../../bn/specs/2026-05-26-appointment-system-design.md) · [Bahasa Indonesia](../../id/specs/2026-05-26-appointment-system-design.md) · [日本語](../../ja/specs/2026-05-26-appointment-system-design.md)

## نظرة عامة

نظام خدمات حجز ثلاثي الواجهات: واجهة المستخدم (برنامج WeChat الصغير + تطبيق Flutter) + لوحة عمل الفني (تبديل هوية داخل نفس التطبيق) + لوحة الإدارة (PC Web).

## القرارات المعمارية

| القرار | الحل |
|------|------|
| بنية الخلفية | `admin/` (واجهات لوحة الإدارة) + `service/` (واجهات الأعمال)، خدمتان تشاركان MySQL/Redis |
| برنامج المستخدم الصغير | برنامج WeChat الصغير الأصلي `apps/wechat/` |
| تطبيق المستخدم | Flutter `apps/flutter/` (iOS + Android) |
| هوية المستخدم | حساب موحد، قابل للتبديل بين العميل/الفني |
| علاقة البرنامج الصغير بالتطبيق | الوظائف متطابقة تمامًا، الفرق منصة فقط |
| واجهة لوحة الإدارة | توسيع Flutter Web القائم (`admin/apps/flutter/`) |
| خلفية لوحة الإدارة | توسيع وحدات الأعمال في webman v2 القائم (`admin/`) |
| الخدمات الخارجية | تسجيل دخول/دفع/رسائل/خرائط WeChat — حلول ربط محجوزة |

## مخطط بنية النظام

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

## الجداول الأساسية لقاعدة البيانات

جميع الجداول تستخدم البادئة `appointment_`، مفاتيح أساسية BIGINT غير تلقائية (يولّدها Snowflake). الحقول الحساسة تشفَّر وتفك عبر trait encryptable.

### مجال المستخدم والهوية

| اسم الجدول | الوصف | الحقول الأساسية |
|------|------|----------|
| `appointment_user` | جدول المستخدمين الموحد | phone, password, wx_openid, wx_unionid, avatar, nickname, user_type(customer/technician), status. مستخدمو technician يملكون وظائف العميل أيضًا، ويمكنهم تبديل الهوية النشطة الحالية بحرية |
| `appointment_user_address` | عناوين المستخدم | user_id, contact_name, contact_phone, province, city, district, detail, is_default |
| `appointment_technician_profile` | ملف الفني | user_id, real_name, gender, id_card, id_card_front, id_card_back, avatar, rating, order_count, status(pending/approved/rejected), intro |
| `appointment_technician_schedule` | مواعيد الفني | technician_id, date, time_slots(JSON), status |
| `appointment_technician_service` | مشاريع خدمة الفني | technician_id, service_id |
| `appointment_technician_earnings` | عمليات أرباح الفني | technician_id, order_id, type(commission/bonus/penalty), amount, status |
| `appointment_technician_withdrawal` | سجلات سحب الفني | technician_id, amount, actual_amount, commission_fee, account_info, status, reviewed_at |
| `appointment_technician_attendance` | حضور الفني | technician_id, date, check_in_at, check_out_at, clean_photo |
| `appointment_technician_member_note` | ملف العضو | technician_id, user_id, content, written_at |

### مجال الخدمة والمنتجات

| اسم الجدول | الوصف | الحقول الأساسية |
|------|------|----------|
| `appointment_service_category` | تصنيفات الخدمة | name, icon, parent_id, sort, status |
| `appointment_service` | مشاريع الخدمة | category_id, name, description, cover_image, images(JSON), price, duration, sales_volume, specs(JSON), status |
| `appointment_product` | المنتجات | category_id, name, cover_image, price, stock, sales_volume, type, status |
| `appointment_store` | المتاجر | name, address, lat, lng, phone, business_hours(JSON), images, status |

### مجال الطلبات

| اسم الجدول | الوصف | الحقول الأساسية |
|------|------|----------|
| `appointment_order` | الجدول الرئيسي للطلبات | order_no, user_id, technician_id, store_id, total_amount, discount_amount, paid_amount, status, service_time, cancel_reason, remark |
| `appointment_order_item` | بنود الطلب | order_id, service_id, product_id, type, name, price, quantity, spec_info |
| `appointment_order_payment` | سجلات الدفع | order_id, pay_type(wechat), transaction_id, amount, status, paid_at |
| `appointment_order_refund` | سجلات الاسترداد | order_id, payment_id, refund_no, amount, ratio, reason, status |
| `appointment_order_review` | تقييمات الخدمة | order_id, user_id, technician_id, rating, content, images |
| `appointment_order_verification` | سجلات التحقق | order_id, code, verified_at, verified_by, location |

### مجال التسويق

| اسم الجدول | الوصف | الحقول الأساسية |
|------|------|----------|
| `appointment_coupon` | تعريف الكوبونات | name, type, amount, min_amount, total_qty, remain_qty, start_at, end_at, status |
| `appointment_user_coupon` | كوبونات المستخدم | user_id, coupon_id, status(available/used/expired), used_at |
| `appointment_member_card` | تعريف بطاقات العضوية | name, type(month/vip/times), price, duration_days, total_times, services(JSON) |
| `appointment_user_member_card` | بطاقات عضوية المستخدم | user_id, card_id, start_at, end_at, total_times, used_times, status |
| `appointment_member_card_usage` | سجلات استخدام بطاقات المرات | user_card_id, order_id, service_id, used_at |
| `appointment_user_points` | عمليات النقاط | user_id, type(earn/use), points, source, order_id |
| `appointment_gift_card` | بطاقات الهدايا | code, type, amount_or_gift, status, used_by, used_at |
| `appointment_user_referral` | ترويج المستخدم | referrer_id, referred_user_id, reward_type, reward_amount, registered_at, first_order_at |

### مجال المحتوى والإشعارات

| اسم الجدول | الوصف | الحقول الأساسية |
|------|------|----------|
| `appointment_banner` | الشرائح الدوارة | position, image, jump_type(url/detail/none), jump_value, sort, status |
| `appointment_announcement` | الإعلانات | content, status, published_at |
| `appointment_platform_agreement` | اتفاقيات المنصة | type(user_agreement/privacy_policy/service_agreement), title, content, version |
| `appointment_faq` | الأسئلة الشائعة | title, content, sort |
| `appointment_feedback` | الملاحظات | user_id, content, images, handler_reply, status(pending/handled) |
| `appointment_moment` | منشورات دائرة الأصدقاء | content, images, published_at |
| `appointment_notification` | إشعارات الرسائل | user_id, type(order/system), title, content, is_read, created_at |

### مجال المالية (جانب admin)

| اسم الجدول | الوصف | الحقول الأساسية |
|------|------|----------|
| `appointment_finance_transaction` | عمليات الدخل والإنفاق | user_id, order_id, type, direction(income/expense), amount, actual_amount, commission, status |
| `appointment_technician_commission_config` | إعداد العمولات | technician_id, commission_rate, settlement_cycle |
| `appointment_withdrawal_account` | حسابات السحب | user_id, type(wechat), account_name, account_no |
| `appointment_withdrawal_config` | إعداد قيود السحب | min_amount, reserve_amount, round_to_hundred |

## وحدات Service API

### الواجهات العامة (بدون مصادقة)
- **AuthController** — تسجيل الدخول/التسجيل/نسيان كلمة المرور/وضع الزائر/تبديل الهوية
- **CaptchaController** — رمز التحقق بالرسائل النصية
- **WechatController** — تفويض WeChat/تسجيل الدخول/استدعاء الدفع
- **CommonController** — نصوص الاتفاقيات/من نحن/معلومات الإصدار

### وحدة المستخدم `user/` (تتطلب مصادقة)
- **ProfileController** — المعلومات الشخصية/تغيير كلمة المرور/تغيير الهاتف/إلغاء الحساب
- **AddressController** — CRUD عناوين الاستلام
- **FavoriteController** — المفضلة
- **FeedbackController** — الملاحظات
- **ReferralController** — الترويج/قائمة المستخدمين المُحالين

### وحدة الفني `technician/` (تتطلب هوية فني + وسيط TechnicianAuth)
- **ProfileController** — ملف الفني/طلب الانضمام
- **ScheduleController** — إعداد المواعيد
- **OrderController** — المحجوز غير المُتحقق منه/المكتمل/التحقق بالرمز
- **MemberController** — أعضائي/ملفات الأعضاء
- **EarningsController** — الأرباح/الأموال العالقة
- **WithdrawalController** — السحب
- **AttendanceController** — الحضور/صور النظافة

### وحدة الخدمة `service/`
- **CategoryController** — تصنيفات الخدمة
- **ItemController** — قائمة وتفاصيل الخدمات/المنتجات
- **SearchController** — البحث
- **StoreController** — قائمة/تفاصيل المتاجر

### وحدة الطلبات `order/` (تتطلب مصادقة)
- **CartController** — سلة التسوق
- **OrderController** — الطلب/قائمة الطلبات/التفاصيل/الإلغاء
- **PaymentController** — الدفع/الاسترداد
- **VerificationController** — التحقق برمز QR
- **ReviewController** — التقييمات

### وحدة التسويق `marketing/` (تتطلب مصادقة)
- **CouponController** — قائمة/استلام/استخدام الكوبونات
- **MemberCardController** — بطاقات العضوية/بطاقات المرات
- **PointsController** — النقاط
- **GiftCardController** — بطاقات الهدايا

### وحدة المحتوى `content/`
- **BannerController** — الشرائح الدوارة
- **AnnouncementController** — الإعلانات
- **NotificationController** — إشعارات الرسائل

### وحدة LBS
- **LocationController** — تحديد الموقع/تبديل المدينة/المتاجر القريبة

### القدرات العامة `common/`
- SnowflakeService — توليد المعرّفات
- HashidsService — تشفير/فك تشفير المعرّفات
- EncryptionService — تشفير/فك تشفير البيانات الحساسة
- WechatPayService — دفع WeChat (محجوز)
- WechatAuthService — تسجيل دخول WeChat (محجوز)
- SmsService — خدمة الرسائل (محجوزة)
- MapService — خدمة الخرائط (محجوزة)

### الوسائط
- Auth — مصادقة JWT (تشارك حزمة erikwang2013/jwt-webman مع admin)
- TechnicianAuth — التحقق من هوية الفني
- RateLimit — تحديد المعدل (مشتركة مع admin)

## توسعة لوحة إدارة Admin

إضافة متحكمات جديدة على الإطار القائم:

### إدارة الفنيين
- **TechnicianController** — قائمة/بحث/تصدير/مراجعة الفنيين/إدارة المواعيد/إعداد مشاريع الخدمة/تتبع تقدم الدورات

### توسعة إدارة المستخدمين
- **MemberController** — قائمة الأعضاء/إعداد المستويات/إحصائيات الاستهلاك

### إدارة المتاجر
- **StoreController** — CRUD المتاجر/التفعيل والتعطيل

### إدارة الخدمات
- **ServiceController** — قائمة/CRUD الخدمات/تصميم البطاقات
- **ServiceCategoryController** — إدارة التصنيفات
- **ProductController** — قائمة/CRUD المنتجات

### إدارة المتجر الإلكتروني
- **MallOrderController** — طلبات المتجر/الشحن/ما بعد البيع/التقييمات
- **SalesStatsController** — إحصائيات المبيعات

### إدارة الطلبات
- **AppointmentOrderController** — الطلبات غير المستخدمة/الإلغاء/تأكيد الإكمال

### نشاطات الكوبونات
- **CouponController** — CRUD/إصدار الكوبونات

### الإدارة المالية
- **FinanceController** — تقسيم أرباح الطلبات/عمليات الدخل والإنفاق
- **WithdrawalController** — مراجعة/إكمال سحوبات الفنيين
- **CommissionController** — إعداد العمولات/المكافآت والغرامات/استعلام الرصيد
- **WithdrawalAccountController** — إدارة حسابات السحب
- **WithdrawalConfigController** — إعداد قيود السحب

### إدارة المحتوى
- **BannerController** — CRUD الشرائح الدوارة
- **AnnouncementController** — CRUD الإعلانات
- **FaqController** — CRUD الأسئلة الشائعة
- **FeedbackController** — معالجة الملاحظات
- **MomentController** — مراجعة منشورات دائرة الأصدقاء
- **AgreementController** — تعديل الاتفاقيات (اتفاقية المستخدم/الخصوصية/الخدمة)
- **AboutController** — إعداد «من نحن»

### الإعدادات
- **SystemMessageController** — إعداد رسائل النظام
- **AdminUserController** — إدارة الحسابات الفرعية (بناءً على RBAC القائم)

### توسعة لوحة القيادة
- بطاقات إحصائيات فورية: عدد المستخدمين/إجمالي الطلبات/عدد الفنيين/عدد طلبات الخدمات
- مخططات خطية: حجم الطلبات/المبالغ/المستخدمون الجدد يوميًا/النشاط
- تنقل سريع: أزرار الوحدات المعلقة
- رسائل داخلية: إشعارات الطلبات الجديدة/إشعارات الاسترداد

## بنية صفحات الواجهة

وظائف برنامج WeChat الصغير وتطبيق Flutter متطابقة تمامًا.

### auth/ — المصادقة
- login — تسجيل الدخول (الهاتف/رمز التحقق/WeChat/مدخل الزائر)
- register — التسجيل (هاتف+رمز تحقق+كلمة مرور+رمز إحالة)
- forget-password — نسيان كلمة المرور
- agreement — عرض الاتفاقية

### home/ — الصفحة الرئيسية
- index — الصفحة الرئيسية (شرائح+إعلانات+تصنيفات الخدمات+توصيات)
- search — صفحة البحث

### service/ — الخدمات
- list — قائمة الخدمات (تصفية حسب التصنيف)
- detail — تفاصيل الخدمة (المعلومات الأساسية+التقييمات+احجز الآن)
- product-list — قائمة المنتجات

### order/ — الطلبات
- confirm — تأكيد الطلب (المتجر/الفني/الوقت/الكوبون/الملاحظات/الاتفاقية)
- payment — صفحة الدفع
- payment-success — نجاح الدفع
- list — جميع الطلبات (تصفية حسب علامات الحالة)
- detail — تفاصيل الطلب
- review — تقييم الخدمة
- verification — التحقق برمز QR

### cart/ — سلة التسوق
- index — قائمة سلة التسوق

### technician/ — الفنيون (من منظور العميل)
- list — قائمة الفنيين (مرتبة من الأقرب إلى الأبعد)
- detail — تفاصيل الفني (التقييمات/المشاريع القابلة للخدمة/احجز الآن)
- apply — طلب انضمام الفني

### tech-work/ — لوحة عمل الفني (هوية الفني)
- index — الصفحة الرئيسية للوحة العمل (طلبات اليوم/نظرة الأرباح)
- schedule — إعداد المواعيد
- order-list — طلباتي (المحجوز غير المُتحقق منه/المكتمل)
- scan-verify — التحقق بالرمز
- member-list — أعضائي
- member-detail — تفاصيل العضو/تعديل الملف
- earnings — أرباحي
- withdrawal — السحب
- transaction-list — تفاصيل المعاملات
- attendance — الحضور/رفع صور النظافة
- training — التدريب المهني

### user/ — المركز الشخصي
- index — المعلومات الشخصية (الصورة الرمزية/اللقب/بطاقة العضوية/المفضلة/مدخل الكوبونات)
- settings — الإعدادات (تغيير كلمة المرور/تغيير الهاتف/الاتفاقية/التحديث/الإلغاء/الخروج)
- switch-role — تبديل الهوية (العميل ↔ الفني)

### marketing/ — التسويق
- coupon-list — قائمة الكوبونات
- member-card — بطاقة عضويتي
- points — نقاطي
- gift-card — بطاقة هداياي
- referral — الترويج (الشرح+ملصق رمز QR+قائمة المستخدمين المُحالين)

### صفحات أخرى
- message/ — قائمة/تفاصيل الرسائل
- store/list, store/detail — قائمة المتاجر (ترتيب LBS)/التفاصيل (الملاحة)
- other/about — من نحن
- other/feedback — الملاحظات
- other/official-account — متابعة الحساب الرسمي

### المكونات العامة
- navbar, tabbar, service-card, technician-card
- coupon-popup, lbs-selector, empty-state, loading

### منطق تبديل الهوية
- تنقل سفلي بهوية العميل: الصفحة الرئيسية / الخدمات / سلة التسوق / الطلبات / حسابي
- تنقل سفلي بهوية الفني: لوحة العمل / الطلبات / الأعضاء / الأرباح / حسابي
- توفر صفحة «حسابي» مدخل تبديل الهوية
- المستخدم الذي لم يصبح فنيًا بعد يُوجَّه إلى صفحة طلب الانضمام عند التبديل لهوية الفني

## شرح عمليتي الشراء

يوجد نظامان مختلفان لعمليات الشراء:

### عملية حجز الخدمة (طلب مباشر، بلا سلة تسوق)
- صفحة تفاصيل مشروع الخدمة → تأكيد الطلب (اختيار المتجر/الفني/الوقت) → الدفع → التحقق
- حصرية مورد الفني: عند دخول صفحة تأكيد الطلب يُقفل الفني لمدة 3 دقائق
- للخدمات الأرضية مثل التدليك والتجميل

### عملية شراء المنتجات (نمط سلة التسوق)
- قائمة المنتجات → أضف إلى السلة → تأكيد السلة → إرسال الطلب → الدفع → الشحن/الاستلام
- تدعم تعديل الكمية وحذف المنتجات
- لبيع المنتجات العينية أو الكوبونات

## القواعد التجارية الرئيسية

### آلية قفل الفني
- لا يمكن لعدة أشخاص حجز نفس الفني في نفس الوقت
- عند دخول المستخدم صفحة تأكيد الطلب، يُقفل الفني عبر Redis SETNX لمدة 3 دقائق
- الخروج من صفحة الحجز أو انتهاء المهلة يحرر القفل تلقائيًا

### قواعد الاسترداد
| الشرط | نسبة الاسترداد |
|------|----------|
| خلال 15 دقيقة من الطلب أو >6 ساعات من البدء | 100% |
| ≤6 ساعات من البدء | 90% |
| بدأت الخدمة دون تأكيدها | 80% |
| بعد تأكيد بدء الخدمة | 0% (لا استرداد) |

### قواعد الخصم
- فترة الذروة المنخفضة (10-12 / 17-18 / بعد 21:00) خصم 9%
- الحجز قبل 30 دقيقة خصم 95% (لا يتراكب مع الكوبونات)

### سحب الفني
- السحب يوم 20 من كل شهر، وصول T+1 يوم عمل
- يدعم السحب إلى رصيد WeChat
- الطلبات المُتحقق منها غير المسوَّاة تُؤكَّد تلقائيًا خلال 3 أيام
- يجب إكمال ملف العضو خلال 24 ساعة وإلا لا عمولة

### مكافأة العميل العائد
- استهلاك ثانٍ لنفس الفني خلال 30 يومًا → تسجيل مكافأة
- رفع صور النظافة بعد الخدمة

### قواعد النقاط
- 1:100 استبدال بطاقة الهدايا (قابلة للتكوين في الخلفية)
- المستخدم المُحال الذي يسجل بنجاح ويطلب يُحصل على نقاط محددة (إعداد بالخلفية)
