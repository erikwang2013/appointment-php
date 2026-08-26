# وثيقة شرح API
> **Languages**: [中文](../API.md) · [English](../en/API.md) · [한국어](../ko/API.md) · [Русский](../ru/API.md) · [Deutsch](../de/API.md) · [Français](../fr/API.md) · [Español](../es/API.md) · [Português](../pt/API.md) · [हिन्दी](../hi/API.md) · [বাংলা](../bn/API.md) · [Bahasa Indonesia](../id/API.md) · [日本語](../ja/API.md)

## نظرة عامة

- **واجهات الأعمال** (service/): `http://localhost:8787` — توفر واجهات الأعمال لبرنامج WeChat الصغير والتطبيق
- **واجهات لوحة الإدارة** (admin/): `http://localhost:8787` — توفر الواجهات لـ Flutter Web الخاص بلوحة الإدارة
- **طريقة المصادقة**: Bearer Token (JWT)، ترويسة الطلب `Authorization: Bearer <token>`
- **التحكم بالإصدارات**: عبر ترويسة الطلب `API-Version: v1`، ولا تظهر في URL. الافتراضي v1
- **ترميز المعرّفات**: جميع حقول المعرّفات في الطلبات/الاستجابات مشفرة بـ hashids، لإخفاء معرّفات قاعدة البيانات الحقيقية خارجيًا
- **وثائق OpenAPI**: تُولَّد عبر `hg/apidoc`، منفصلة للوحة الإدارة والعميل

| الطرف | عنوان وثائق OpenAPI | الوصف |
|------|------|------|
| لوحة الإدارة | `GET http://localhost:8787/api/docs` | المواصفات الكاملة لواجهات لوحة الإدارة (OpenAPI 3.0 JSON) |
| العميل | `GET http://localhost:8787/api/docs` | المواصفات الكاملة لواجهات الأعمال (OpenAPI 3.0 JSON) |

يمكن استيراد العنوانين أعلاه عبر أدوات مثل Swagger UI لعرض وثائق تفاعلية.

- **صيغة الاستجابة العامة**:

```json
{
  "code": 0,
  "message": "操作成功",
  "data": {}
}
```

استجابة الترقيم:
```json
{
  "code": 0,
  "message": "success",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

---

## 一、واجهات الأعمال (service/ :8787)

### 1. الواجهات العامة (بدون مصادقة)

#### 1.1 رمز التحقق

**`POST /api/captcha/send`** — إرسال رمز تحقق بالرسائل النصية

الطلب:
```json
{
  "phone": "13800138000"
}
```
الاستجابة: `{"code":0,"message":"验证码已发送","data":null}`

القيود: مرة واحدة فقط كل 60 ثانية، والرمز صالح لمدة 5 دقائق.

---

#### 1.2 المصادقة

**`POST /api/auth/register`** — التسجيل برقم الهاتف

الطلب:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "abc123",
  "confirm_password": "abc123",
  "referral_code": "A1B2C3D4"
}
```
الاستجابة:
```json
{
  "code": 0,
  "message": "注册成功",
  "data": {
    "token": "eyJhbGciOi...",
    "user": {
      "id": "aB3xK9mQ",
      "phone": "138****8000",
      "nickname": "用户138****8000",
      "user_type": "customer",
      "active_role": "customer",
      "referral_code": "E5F6G7H8"
    }
  }
}
```

---

**`POST /api/auth/login`** — تسجيل الدخول بكلمة المرور

الطلب:
```json
{
  "phone": "13800138000",
  "password": "abc123"
}
```
الاستجابة: نفس استجابة التسجيل، تتضمن token ومعلومات المستخدم.

---

**`POST /api/auth/login-by-code`** — تسجيل الدخول برمز التحقق

الطلب:
```json
{
  "phone": "13800138000",
  "code": "123456"
}
```
الاستجابة: مثل تسجيل الدخول. يُنشأ الحساب تلقائيًا للمستخدم غير المسجل.

---

**`POST /api/auth/forget-password`** — نسيت كلمة المرور

الطلب:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "newpass123",
  "confirm_password": "newpass123"
}
```

---

**`POST /api/auth/refresh`** — تحديث Token

ترويسة الطلب: `Authorization: Bearer <旧token>`
الاستجابة: `{"code":0,"data":{"token":"eyJhbGciOi..."}}`

---

#### 1.3 WeChat

**`POST /api/wechat/mini-login`** — تسجيل دخول برنامج WeChat الصغير

الطلب: `{"code":"微信登录code"}`
الوصف: عند أول تسجيل دخول يلزم لاحقًا استدعاء `/api/wechat/phone` لربط رقم الهاتف.

---

**`POST /api/wechat/phone`** — ربط رقم الهاتف

الطلب: `{"code":"微信手机号组件code"}`

---

**`POST /api/wechat/oa-login`** — تسجيل دخول الحساب الرسمي

الطلب: `{"code":"公众号授权code"}`

---

#### 1.4 الخدمات العامة

**`GET /api/common/config`** — الإعدادات العامة

الاستجابة: تتضمن نصوص الاتفاقيات (اتفاقية المستخدم/اتفاقية الخصوصية/اتفاقية الخدمة) ومعلومات «من نحن» ورقم الإصدار.

---

**`GET /api/common/area`** — قائمة مناطق المدن

---

#### 1.5 الاستعلام عن الخدمات

**`GET /api/service/categories`** — قائمة التصنيفات

المعلمات: `?parent_id=0`

---

**`GET /api/service/items`** — قائمة مشاريع الخدمة

المعلمات: `?category_id=&page=1&per_page=10&sort=sales`

---

**`GET /api/service/detail/{id}`** — تفاصيل الخدمة

تتضمن الاستجابة: الصور/الاسم/السعر/المواصفات/المدة/المبيعات/قائمة التقييمات.

---

**`GET /api/service/products`** — قائمة المنتجات

**`GET /api/service/stores`** — قائمة المتاجر

المعلمات: `?lat=&lng=&city=`

---

#### 1.6 الاستعلام عن الفنيين

**`GET /api/technician/list`** — قائمة الفنيين

المعلمات: `?lat=&lng=&service_id=&page=1`
مرتبة حسب المسافة من الأقرب إلى الأبعد، وتُرجع: الصورة الرمزية/الاسم/التقييم/عدد الطلبات/عدد المفضلة/المسافة/أقرب وقت حجز/قابلية الخدمة.

---

**`GET /api/technician/detail/{id}`** — تفاصيل الفني

تتضمن الاستجابة: الصور/الاسم/التعريف/التقييم/المسافة/قائمة المشاريع القابلة للخدمة/التقييمات.

---

**`GET /api/technician/schedule/{id}`** — جدول مواعيد الفني

المعلمات: `?date=2026-05-26`
تُرجع الفترات الزمنية القابلة للحجز في ذلك التاريخ وحالة توفرها.

---

#### 1.7 المحتوى

**`GET /api/content/banners`** — الشرائح الدوارة

المعلمات: `?position=home`

**`GET /api/content/articles`** — قائمة الإعلانات/المقالات

المعلمات: `?type=announcement&page=1`

**`GET /api/content/article/{id}`** — تفاصيل المقالة

---

#### 1.8 LBS

**`GET /api/lbs/nearby-stores`** — المتاجر القريبة

المعلمات: `?lat=&lng=&radius=5000`

**`GET /api/lbs/geocode`** — الترميز الجغرافي العكسي

المعلمات: `?lat=&lng=`

---

### 2. واجهات المستخدم (تتطلب مصادقة JWT)

جميع الواجهات تحمل ترويسة الطلب `Authorization: Bearer <token>`

#### 2.1 الملف الشخصي

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/user/profile` | الحصول على المعلومات الشخصية |
| PUT | `/api/user/profile` | تحديث اللقب/الصورة الرمزية/الجنس |
| POST | `/api/user/change-password` | تغيير كلمة المرور (old_password/new_password/confirm_password) |
| POST | `/api/user/change-phone` | تغيير رقم الهاتف المرتبط (old_code/new_phone/new_code) |
| POST | `/api/user/cancel-account` | إلغاء الحساب (يتطلب التحقق من كلمة المرور) |
| POST | `/api/user/logout` | تسجيل الخروج (يُضاف token إلى القائمة السوداء) |
| POST | `/api/user/switch-role` | تبديل الهوية (role: customer/technician) |

التبديل إلى technician يتطلب وجود ملف فني بحالة approved.

#### 2.2 إدارة العناوين

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/user/addresses` | قائمة العناوين |
| POST | `/api/user/addresses` | إضافة عنوان (contact_name/contact_phone/province/city/district/detail/lat/lng/is_default) |
| GET | `/api/user/addresses/{id}` | تفاصيل العنوان |
| PUT | `/api/user/addresses/{id}` | تحديث العنوان |
| DELETE | `/api/user/addresses/{id}` | حذف العنوان |

عند التعيين كافتراضي تُلغى العناوين الافتراضية الأخرى تلقائيًا.

#### 2.3 المفضلة

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/user/favorites` | قائمة المفضلة (?type=service/technician) |
| POST | `/api/user/favorites` | إضافة إلى المفضلة (target_type/target_id) |
| DELETE | `/api/user/favorites/{id}` | إلغاء التفضيل |

#### 2.4 الملاحظات

`POST /api/user/feedback` — إرسال ملاحظات (content + مصفوفة images)

#### 2.5 الترويج والإحالة

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/user/referral` | معلومات الترويج (رمز الإحالة/عدد المدعوين/عدد أصحاب الطلبات الأولى/النقاط المكتسبة) |
| GET | `/api/user/referral/qrcode` | رمز الترويج (رمز الإحالة + رابط الدعوة) |
| GET | `/api/user/referral/referred-users` | قائمة المستخدمين المُحالين |
| GET | `/api/user/referral/earnings` | تفاصيل عمولات التوزيع (ترقيم: لقب المُحال/الصورة الرمزية/رقم الطلب/المبلغ/وقت الصرف) |

**عمولة التوزيع**: تُصرف بعد اكتمال (completed) أول طلب للمُحال، المبلغ = paid_amount × reward_rate (erik_system_config referral.reward_rate، الافتراضي 0.05، والقيمة غير القانونية تعود إلى الثابت). قفل صف + فحص rewarded_at فارغ + إعادة فحص أول طلب (ثلاثي منع التكرار)؛ التسجيل في WalletTxn بنوع referral_reward.

#### 2.6 إهداء النقاط (الجولة 19)

| الطريقة | المسار | الوصف |
|------|------|------|
| POST | `/api/user/points/transfer` | إهداء النقاط (to_user_id hashid/points) |
| GET | `/api/user/points/transfers` | سجلات الإهداء (?direction=sent/received&page=1) |

**إهداء النقاط**: فك hashid للمستلم + التحقق من الوجود 404، إهداء النفس 422، النقاط 1-10000 422، عدم كفاية الرصيد عبر SUM 422، حد يومي تراكمي 10000 422. حماية التزامن: قفل Redis NX points_transfer:{user} لمدة 30 ثانية → داخل المعاملة lockForUpdate على آخر سطرَي العمليات للطرفين (بترتيب تصاعدي user_id لمنع الجمود المتبادل) → إعادة فحص الرصيد/الحد/المستلم داخل القفل. مواصفات العمليات: المرسِل type=consume/source=points_transfer بقيمة سالبة (balance=لقطة السطر السابق-هذه الدفعة)، والمستلم type=earn/source=points_transfer بقيمة موجبة تتضمن expires_at (يمكن لـ PointsExpiryTimer إبطالها بشكل طبيعي)؛ بعد commit إشعار داخلي للمستلم type='points_received' (الفشل فقط warn).

#### 2.7 إعدادات تفضيلات الإشعارات (الجولة 19)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/user/notify-settings` | الاستعلام عن مفاتيح الإشعارات (5 فئات كاملة) |
| PUT | `/api/user/notify-settings` | تحديث جماعي للمفاتيح (types: {service_reminder: 0/1, ...}) |

**مفاتيح الإشعارات**: جدول erik_user_notify_setting (مفتاح مركب فريد user_id+type، السطر المفقود=مفتوح افتراضيًا). 5 فئات: service_reminder تذكير الخدمة / card_expiry تذكير الانتهاء (موحدة للبطاقات والكوبونات) / points_expiry انتهاء النقاط / marketing تسويقي (محجوز) / system نظامي (لا يُغلق، PUT يفرضه 1). التحكم: notifySettingEnabled مربوط بعمليات 3 مؤقتات ServiceReminderTimer/ExpiryReminderTimer/PointsExpiryTimer + رسم خرائط سيناريوهات أحداث الاشتراك (PAY/REFUND/VERIFIED/RESCHEDULE→system يُرسل دائمًا، REMINDER→service_reminder، EXPIRY→card_expiry)؛ عند إغلاق الفئة يُتخطى الإشعار الداخلي ورسالة الاشتراك معًا.

---

### 3. واجهات الفني (تتطلب JWT + هوية فني)

#### 3.1 ملف الفني

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/technician/profile` | الحصول على ملف الفني |
| PUT | `/api/technician/profile` | تحديث الملف (avatar/intro/real_name/gender/id_card/id_card_front/id_card_back) |

أول تعبئة كاملة تُعد طلب انضمام، والحالة status=pending في انتظار المراجعة.

#### 3.2 المواعيد

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/technician/schedule` | استعلام المواعيد (?start_date=&end_date=) |
| PUT | `/api/technician/schedule` | ضبط المواعيد (date/time_slots/status)، تداخل الفترات 422 «تعارض مع المواعيد القائمة» |
| POST | `/api/technician/schedule/batch` | مواعيد جماعية (الجولة 23): نطاق الأيام ≤7 أيام + فلترة weekdays، تُتخطى الأيام ذات المواعيد القائمة، الاستجابة created/skipped |

#### 3.3 طلبات الفني

`GET /api/technician/orders` — قائمة الطلبات (?status=&page=1)

#### 3.4 الأرباح

`GET /api/technician/earnings` — نظرة على الأرباح (today_income/pending_settlement/balance + قائمة العمليات)

#### 3.5 السحب

`POST /api/technician/withdraw` — تقديم طلب سحب (amount)
القواعد: السحب يوم 20 من كل شهر، وصول T+1، الحد الأدنى للمبلغ/تقريب المئات بإعدادات لوحة الإدارة.

**الاحتياطي العالق (2026-08-26)**: عند التقديم يُخصم الرصيد فورًا كاحتياطي عالق (pending/approved)؛ وقبل الموافقة على التحويل تُعاد الفحوصات settled − withdrawn − العالق ≥ مبلغ السحب؛ الموافقات المتزامنة لن تدفع مرتين.

#### 3.6 الرد على التقييمات (الجولة 18)

`POST /api/technician/review/reply/{order_id}` — رد الفني على التقييم (reply). التقييم غير موجود/ليس للمستخدم نفسه 404 موحد (لا يكشف الوجود)؛ رد موجود 422 (رفض قطعي بدون استبدال)؛ رد فارغ 422. بعد نجاح الرد إشعار داخلي للمستخدم (type='review_reply').

#### 3.6 لوحة العمل

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/technician/work/today` | قائمة مهام اليوم |
| GET | `/api/technician/work/records` | ترقيم سجلات الإنجاز |
| POST | `/api/technician/work/{id}/start` | بدء الخدمة |
| POST | `/api/technician/work/{id}/complete` | إكمال الخدمة |

**مهام اليوم**: status ∈ [confirmed, serving]، وservice_time في اليوم أو فارغ، تُرجع service_name/price/nickname/avatar.

**سجلات الإنجاز**: status ∈ [serving, completed]، مرتبة تنازليًا حسب service_end_at، الاستجابة المرقمة تتضمن meta.

**بدء/إكمال الخدمة**: قفل صف + فحص آلة الحالة، عمليات قطعية. بدء الخدمة يكتب service_start_at؛ إكمال الخدمة يكتب service_end_at ويُرسل إشعارًا داخليًا. رموز الخطأ: ليس لنفسه 403، حالة خاطئة 422، hashid غير صالح 422.

---

### 4. واجهات الطلبات (تتطلب مصادقة JWT)

| الطريقة | المسار | الوصف |
|------|------|------|
| POST | `/api/order` | إنشاء طلب (order_type/items/store_id/technician_id/service_time/coupon_id/user_coupon_id/promotion_id/remark) |
| GET | `/api/order/list` | قائمة الطلبات (?status=&page=1) |
| GET | `/api/order/detail/{id}` | تفاصيل الطلب |
| POST | `/api/order/cancel/{id}` | إلغاء الطلب (reason) |
| POST | `/api/order/pay/{id}` | إطلاق الدفع (pay_channel: wechat/balance، use_points: خصم النقاط اختياري) |
| POST | `/api/order/refund/{id}` | تقديم طلب استرداد |
| POST | `/api/order/verify/{id}` | التحقق (code: قيمة رمز QR) |
| POST | `/api/order/reschedule/{id}` | تغيير موعد الحجز (new_service_time إلزامي/reason اختياري) |
| GET | `/api/order/logistics/{id}` | تتبع اللوجستيات (الجولة 19، طلبات المنتجات) |
| POST | `/api/order/review/{order_id}` | إرسال تقييم (rating 1-5/content/images) (الجولة 19 إضافة) |
| POST | `/api/order/review/{order_id}/append` | إضافة تقييم تكميلية (content/images مفصولة بفواصل) (الجولة 19) |

**حالة الطلب**: pending(في انتظار الدفع) → paid(مدفوع) → confirmed(مؤكد) → serving(في الخدمة) → completed(مكتمل)

**عند إنشاء الطلب**: قفل Redis SETNX للفني لمدة 3 دقائق، يُحرَّر عند الخروج من الصفحة أو انتهاء المهلة.

**الحماية من العبث بالسعر (2026-08-26)**: مبالغ بنود الطلب دائمًا حسب سجلات قاعدة البيانات (target_type=service يستعلم erik_service، product يستعلم erik_product)، والأسعار المرسلة من العميل لا تدخل في الحساب؛ target_type غير معروف 422؛ target_id يجب أن يكون قيمة hashid (تمرير raw id يُفك إلى 0 → 422 «المنتج غير موجود أو غير متاح»)؛ أسعار المجموعة المشتركة/البيع المفاجئ كذلك حسب DB.

**قواعد الاسترداد**: خلال 15 دقيقة من الطلب أو >6 ساعات من البدء استرداد 100% / ≤6 ساعات 90% / بدأت الخدمة 80% / بعد تأكيد البدء لا استرداد.

**خصم الكوبونات**: عند إنشاء الطلب يمكن تمرير user_coupon_id (hashid). رموز الخطأ: كوبون الآخرين 404، عدم استيفاء العتبة/الانتهاء/الإيقاف/الاستخدام 422، hashid غير صالح 422. الخصم على مرحلتين: عند الطلب يقوم PriceCalculator.applyCoupon بفحص للقراءة فقط وحساب مبلغ الخصم وكتابته في discount_amount؛ بعد نجاح الدفع يُحول consume الكوبون إلى used؛ عند الاسترداد تعيد restoreCouponAndCard الكوبون بقطعية.

**الدفع بالرصيد والاسترداد**: تمرير `pay_channel: "balance"` في جسم الدفع يستخدم رصيد المحفظة؛ استرداد WeChat واسترداد الرصيد يعيدان المبلغ إلى رصيد المحفظة.

**خصم النقاط نقدًا**: جسم الدفع يدعم اختياريًا `use_points` (عدد صحيح). التحقق من رصيد النقاط عبر SUM (عمود balance في erik_user_points لقطة زيادة مفردة، لا يصح التعامل معه كرصيد)، مبلغ الخصم = floor(use_points / config('app.points_rate', 100)) يوان، المبلغ الفعلي = المبلغ المستحق - الخصم (الحد الأدنى 0.01، وما زاد عن المستحق يُكتفى بالمستحق ولا تضيع النقاط). عند النجاح تُكتب عملية استهلاك type=consume/source=points_offset (قطعية، إعادة المحاولة لا تخصم مرتين). الرصيد غير كافٍ 422.

**إعادة النقاط**: عند الإلغاء/الاسترداد تُعاد النقاط المستهلكة عبر points_offset (type=earn/source=points_refund): الإلغاء كاملًا والاسترداد نسبيًا، عبر 5 نقاط ربط قطعية (refundOffsetPoints).

**الطلب الجماعي (الجولة 16)**: عند إنشاء الطلب يمكن تمرير `promotion_id` (hashid). التحقق: فقط نوع group_buy، ضمن فترة صلاحية النشاط، والطالب مشارك، ولم يكتمل العدد (اكتمل التشكيل 422)، ومطابقة خدمة الطلب للنشاط؛ سعر المجموعة = السعر الأصلي × discount_percent/100، ويُحظر تراكب الكوبونات/بطاقات المرات/النقاط (تمرير أي منها 422). يُخزن الطلب promotion_id/participant_id؛ الدفع يعيد استخدام `POST /api/order/pay/{id}` بالكامل، وعند الدفع يُفحص بتكاسل إغلاق النشاط (انتهت المهلة دون اكتمال العدد) → يُلغى الطلب تلقائيًا ويُحرَّر قفل الفني.

**الطلب السريع (الجولة 18، أوقف)**: ~~تمرير `promotion_id` (نوع flash_sale) عند إنشاء الطلب~~ — اعتبارًا من 2026-08 حُذفت قناة الترويج القديمة FLASH_SALE، وفرع الترويج في store() لم يبقَ سوى المجموعة المشتركة GROUP_BUY (promotion غير المجموعة 422)؛ البيع المفاجئ يسلك قناة الجولة 24 `/api/seckill` (يُحقن seckill_id في معاملة store مع قفل صف لخصم المخزون)، وPromotionController::index يفلتر flash_sale، وshow/join يُرجعان لها 400، والثابت `Promotion::TYPE_FLASH_SALE` محفوظ لتوافق البيانات التاريخية.

**تغيير موعد الحجز (الجولة 17)**: `POST /api/order/reschedule/{id}` يمرر new_service_time (إلزامي) + reason (اختياري)، تغيير الوقت مع نفس الفني. القواعد: فقط طلبات المستخدم نفسه (غير نفسه 404)، فقط نوع appointment وحالات pending/paid/confirmed القابلة للتغيير (غيرها 422)، مسافة ≥ 6 ساعات من موعد بدء الخدمة الأصلي (متطابقة مع نافذة الاسترداد الكامل) لإمكانية التغيير. حماية التزامن: B1 order_lock (نفس عائلة التبادل مع pay/cancel/refund) → قفل الفني للفترة الجديدة Redis SETNX EX 180 (منع البيع الزائد في التغييرات المتزامنة) → داخل المعاملة إعادة قراءة بقفل صف + B2 فحص تعارض المواعيد في DB (باستبعاد هذا الطلب) → تحديث service_time + كتابة سجل erik_order_reschedule → تحرير قفل الفترة الأصلية، والفترة الجديدة يحملها هذا الطلب → رسالة اشتراك SCENE_RESCHEDULE (عند عدم التهيئة تنخفض إلى إشعار داخلي). في مسار الفشل تُتراجع المعاملة ويُحرَّر قفل الفترة الجديدة معًا.

**تتبع اللوجستيات (الجولة 19)**: `GET /api/order/logistics/{id}` — فقط طلبات المنتجات للمستخدم نفسه (غير نفسه/ليس منتجًا/لم يُشحن 404 موحد). يقرأ JSON حقل order.remark (shipping_company/tracking_no/shipped_at، يكتبها admin MallOrderController::ship() عند الشحن)، وتحليلان احتياطيان parseShippingInfo/parseReceiver للصيغ القديمة؛ إخفاء رقم هاتف المستلم 138****5678.

**التقييم (الجولة 19)**: `POST /api/order/review/{order_id}` يرسل تقييمًا (rating إلزامي 1-5، content/images اختياريان): غير نفسه 404، غير completed 422، تقييم مكرر 400. `POST /api/order/review/{order_id}/append` تقييم تكميلي (content إلزامي، images مفصولة بفواصل): التقييم غير موجود/ليس لنفسه 404 موحد، غير completed 422، تكميلة مكررة 422، محتوى فارغ 422؛ عند النجاح يُكتب append_content/append_images(JSON)/append_at ويُرسل إشعار داخلي للفني type='review_append'، وتُظهر الاستجابة حقل append.

### 4.1 واجهات ما بعد البيع (تتطلب مصادقة JWT)

| الطريقة | المسار | الوصف |
|------|------|------|
| POST | `/api/aftersales` | تقديم طلب ما بعد البيع (order_id hashid/type: refund|exchange/reason)، التحقق من طلب المستخدم نفسه 404، فقط الحالات paid+completed القابلة للتقديم 422، تكرار ما بعد البيع الجاري لنفس الطلب 422 |
| GET | `/api/aftersales` | قائمة طلبات ما بعد البيع الخاصة بي (?status=&page=1&limit=) |
| GET | `/api/aftersales/{id}` | تفاصيل ما بعد البيع (تحقق الملكية 404) |

**حالة ما بعد البيع**: pending(في انتظار المراجعة) → approved(موافق) / rejected(مرفوض). approved انتقال حالة فقط، وإجراء الاسترداد يستخدم `POST /api/order/refund/{id}`.

---

### 4.2 واجهات المجموعة/الترويج (تتطلب مصادقة JWT؛ FLASH_SALE أوقف)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/promotions` | قائمة النشاطات (?type=group_buy؛ flash_sale مفلتر ولا يُرجع) |
| GET | `/api/promotions/{id}` | تفاصيل النشاط (تشمل عدد المشاركين/اكتمال التشكيل؛ نوع flash_sale 400) |
| GET | `/api/promotions/{id}/participants` | قائمة المشاركين |
| POST | `/api/promotions/join/{id}` | المشاركة في النشاط (الجولة 15 تحسين: الاستجابة تتضمن discount_percent/original_price/group_price؛ نوع flash_sale 400) |

**قواعد المشاركة**: اكتمال عدد group_buy (≥min_people) يُقفل، والمشاركة بعد التشكيل 422؛ عدم اكتمال العدد عند الانتهاء يُغلق بتكاسل (عند show/join تُضبط status إلى 0). الطلب بعد join بسعر المجموعة راجع «الطلب الجماعي (الجولة 16)». البيع المفاجئ لم يعد يسلك هذه القناة، راجع «24. واجهات البيع المفاجئ».

---

### 5. واجهات التسويق (تتطلب مصادقة JWT)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/marketing/coupons` | قائمة الكوبونات (?status=available/used/expired) |
| POST | `/api/marketing/coupons/receive` | الحصول على كوبون (coupon_id) |
| GET | `/api/marketing/cards` | قائمة بطاقات العضوية |
| POST | `/api/marketing/cards/buy` | شراء بطاقة عضوية (card_id) |
| GET | `/api/marketing/cards/my` | قائمة بطاقات المرات الخاصة بي |
| POST | `/api/marketing/cards/use` | التحقق من بطاقة مرات (user_card_id/service_id/remark?) |
| GET | `/api/marketing/gift-cards` | قائمة بطاقات الهدايا |
| GET | `/api/marketing/gift-cards/my` | بطاقات الهدايا الخاصة بي (سجلات redeem) |
| POST | `/api/marketing/gift-cards/redeem` | استبدال بطاقة هدايا (نوع cash بعد الاستبدال يُشحن رصيد المحفظة) |
| GET | `/api/marketing/points` | عمليات النقاط (?type=earn/use/expire&source=order/referral/gift_card/check_in/admin) |
| GET | `/api/marketing/points-exchange` | قائمة منتجات استبدال النقاط (متاحة + المخزون المتبقي الفوري + العدد المتبدل) |
| POST | `/api/marketing/points-exchange/{id}` | الاستبدال (type=coupon إصدار كوبون / wallet إيداع / gift_card إرجاع كلمة البطاقة) |
| POST | `/api/marketing/coupons/transfer` | توليد رمز الإهداء (user_coupon_id: رمز فريد 8 أحرف/صالح 7 أيام) |
| POST | `/api/marketing/coupons/claim` | استلام الكوبون المُهدى (code) |
| GET | `/api/marketing/coupons/transfers` | سجلات الإهداء (المرسل: pending/claimed/expired + المستقبِل: claimed) |

**بطاقة المرات**: cards/my تُرجع card_id/name/type/services/total_times/used_times/remaining_times/start_at/end_at/status (محسوبة فوريًا). التحقق الناجح يُرجع {order_id, usage_id, remaining_times}؛ رموز الخطأ: hashid غير صالح 422، مرات غير كافية 422، منتهية 400، ليست لنفسه 404، منع تكرار Redis 400.

**بطاقة الهدايا**: gift-cards/my تُرجع سجلات redeem (type/amount/gift_name/status/used_at).

**قواعد النقاط**: ترقيم التفاصيل، فلترة type (earn/use/expire)، فلترة source (order/referral/gift_card/check_in/admin). تسجيل الحضور يُرجع نقاطًا (CheckIn, type=earn)؛ الاستهلاك يُرجع نقاطًا floor(paid_amount×1)، تُصرف عند التحقق وبقطعية؛ الاسترداد يخصم النقاط نسبيًا.

**انتهاء النقاط (الجولة 17)**: عمود erik_user_points.expires_at (إعداد points.expiry_days، الافتراضي 365 يومًا، ≤0 لا ينتهي أبدًا)، كل earn عند الكتابة تُملأ بمدة الصلاحية؛ مؤقت PointsExpiryTimer كل 60 ثانية يمسح صفوف earn المنتهية بمؤشر، ويكتب صفوف خصم type=expire بقيمة سالبة (source=expiry + order_id لتعقب العملية الأصلية، ثلاثي منع التكرار) + إشعار داخلي مُجمَّع «لديك X نقطة انتهت صلاحيتها»؛ معيار الرصيد المتاح SUM يتضمن صفوف expire السالبة، والنقاط المنتهية لا يمكن استخدامها للخصم/الاستبدال.

**إهداء الكوبونات (الجولة 17)**: transfer يتحقق أن الكوبون لنفسه/available/تعريف الكوبون غير منتهٍ/لم يُهدَ من قبل، ويولّد رمز إهداء فريد 8 أحرف خالية من الالتباس (uk_code بمؤشر فريد كضمانة)، صالح 7 أيام. claim ضد سوء الاستخدام: قفل Redis NX (coupon_transfer_claim:{code} 30s) + إعادة فحص بقفل صف لمنع الصرف المزدوج، مؤشر فريد uk_user_coupon يحد من إهداء نفس الكوبون مرة واحدة فقط، الكوبون المُهدى لا يُهدى مرة أخرى (الكوبون الجديد بلا سجل إهداء يُمنع طبيعيًا)، لا يمكن استلام كوبون أهداه المستخدم نفسه 422، والمستلم غير الحامل الأصلي؛ الفحص البطيء للانتهاء يضبط expired ويعيد الكوبون الأصلي إلى available. داخل معاملة claim يُضبط الكوبون الأصلي used + يُنشأ UserCoupon جديد مربوط بالمستلم (coupon_id ثابت أي مدة الصلاحية ثابتة) + يُضبط السجل claimed.

---

### 6. واجهات الإشعارات (تتطلب مصادقة JWT)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/notification` | قائمة الإشعارات (?type=order/system&page=1) |
| PUT | `/api/notification/read/{id}` | تعليم كمقروء |
| PUT | `/api/notification/read-all` | تعليم الكل كمقروء |

---

### 7. واجهات المحفظة (تتطلب مصادقة JWT)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/wallet` | رصيد المحفظة + ترقيم العمليات |
| POST | `/api/wallet/recharge` | إنشاء طلب شحن (amount: يوان) |
| POST | `/api/wallet/recharge/{id}/pay` | إطلاق الدفع لطلب الشحن (WeChat) |
| POST | `/api/wallet/transfer` | تحويل الرصيد (to_user_id hashid/amount/remark اختياري/client_token اختياري) (الجولة 19) |
| GET | `/api/wallet/transfers` | سجلات التحويل (?direction=out/in&page=1) (الجولة 19) |
| GET | `/api/wallet/transfers/{id}` | تفاصيل التحويل (مرئي للطرفين فقط، الآخرين 404) (الجولة 19) |

**العمليات**: أنواع wallet_txn: recharge / consume / refund / gift_card / referral_reward(عمولة التوزيع) / referral_level2(عمولة المستوى الثاني) / points_exchange(إيداع استبدال النقاط)، تُرجع مرقمة.

**الشحن**: `POST /api/wallet/recharge` يمرر amount (يوان) لإنشاء طلب الشحن، ويُرجع hashid طلب الشحن. `POST /api/wallet/recharge/{id}/pay` يُطلق دفع WeChat، والاستجابة تتضمن sign_params (مثل نمط دفع الطلبات)؛ استدعاء الدفع يميز طلبات الشحن عن الطلبات عبر out_trade_no بادئته R.

**الدفع بالرصيد**: تمرير `pay_channel: "balance"` في جسم دفع الطلب يستخدم رصيد المحفظة؛ استرداد WeChat واسترداد الرصيد يعيدان المبلغ إلى رصيد المحفظة.

**تحويل الرصيد (الجولة 19)**: `POST /api/wallet/transfer` — فك hashid للمستلم + التحقق من الوجود 404، تحويل النفس 422، المبلغ 0.01-1000/عملية 422 (مقارنة DECIMAL وليس float)، الرصيد غير كافٍ 422، حد يومي تراكمي 5000 يوان 422. التزامن/القطع: قفل Redis NX wallet_transfer:{from} 30s لتسلسل المرسِل → داخل المعاملة lockForUpdate لصفَي محفظة الطرفين بترتيب تصاعدي user_id (ترتيب ثابت لمنع الجمود) → خصم المرسِل + إيداع المستلم + عمليتان wallet_txn (transfer_out/transfer_in تتضمن لقطة balance_after) + سجل تحويل completed + إشعار داخلي للمستلم type='balance_received' (الفشل يسجَّل فقط). client_token اختياري: بعد النجاح SETNX 24 ساعة لمنع الإرسال المكرر (الطلبات الفاشلة لا تُسجل token ويمكن إعادة المحاولة).

---

### 8. واجهات لوحة عمل مدير المتجر (تتطلب مصادقة JWT)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/store-manager/overview` | نظرة اليوم (طلبات اليوم/إيراد اليوم/الجاري/عدد الفنيين/عدد التحققات) |
| GET | `/api/store-manager/orders` | قائمة طلبات المتجر (?status=&page=&limit=) |
| GET | `/api/store-manager/technicians` | قائمة الفنيين (تشمل مواعيد اليوم) |
| GET | `/api/store-manager/revenue` | تجميع إيراد آخر 7 أيام |

**عزل store_id**: requireStoreId() يفرض ربط المستخدم الحالي بمتجر (erik_user.store_id)، بلا متجر 403؛ جميع الاستعلامات مفلترة حسب store_id.

---

### 9. واجهات مستوى النمو (تتطلب مصادقة JWT، الجولة 20)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/growth` | نظرة النمو الحالية (balance/المستوى/فرق الشريحة التالية/اسم المستوى) |
| GET | `/api/growth/records` | ترقيم عمليات قيمة النمو (?page=&limit=) |
| GET | `/api/growth/levels` | قائمة الشرائح (عامة، بدون تسجيل دخول) |

**إدخالات قيمة النمو**: تسجيل الحضور +10؛ إرسال تقييم +20 (التكميلة لا تُدخل)؛ الاستهلاك floor(paid) كل 1 يوان نقطة واحدة (داخل استدعاء الدفع عبر إعادة فحص القطع، التكرار لا يُدخل مرتين).

### 10. واجهات الفواتير (تتطلب مصادقة JWT، الجولة 20)

| الطريقة | المسار | الوصف |
|------|------|------|
| POST | `/api/invoices` | طلب فاتورة (order_id hashid/order_type: service=خدمة/points_exchange=استبدال نقاط؛ order_type الافتراضي service؛ المبلغ والرأس يُخرجان من الخادم ولا يمكن العبث بهما) |
| GET | `/api/invoices` | قائمة الفواتير (?status=&page=) |
| GET | `/api/invoices/{id}` | تفاصيل الفاتورة (لنفسه فقط) |

**منع التكرار**: مفتاح فريد uk_order_type(order_id, order_type)، طلب مكرر لنفس النوع لنفس الطلب 422 (تشمل التقاط MySQL 1062 كضمانة).

### 11. واجهات تذاكر خدمة العملاء (تتطلب مصادقة JWT، الجولة 20)

| الطريقة | المسار | الوصف |
|------|------|------|
| POST | `/api/tickets` | إرسال تذكرة (title/content إلزاميان) |
| GET | `/api/tickets` | قائمة التذاكر (?status=open/closed&page=) |
| GET | `/api/tickets/{id}` | تفاصيل التذكرة (لنفسه فقط، الآخرين 404) |
| POST | `/api/tickets/{id}/close` | إغلاق التذكرة (لنفسه فقط/فقط open؛ rating اختياري 1-5 لتقييم الرضا، خارج الحدود/غير صحيح 422، عند عدم التقديم NULL متوافق) |

### 12. واجهات تقويم الحجوزات (تتطلب مصادقة JWT، الجولة 20)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/calendar/technician/{id}` | عرض الشهر (?month=YYYY-MM): توسيع time_slots للمواعيد إلى فتحات ساعة + استبعاد المحجوز |
| GET | `/api/calendar/technician/{id}/day` | عرض اليوم (?date=YYYY-MM-DD): تفاصيل فتحات اليوم المتاحة/المحجوزة/غير المتاحة |

### 13. واجهات رأس الفاتورة (تتطلب مصادقة JWT، الجولة 21)

| الطريقة | المسار | الوصف |
|------|------|------|
| POST | `/api/invoice-titles` | حفظ رأس (title_type: personal/company؛ company يجب أن يحمل tax_no؛ تكرار نفس المستخدم لنفس الرأس 422؛ أول سطر يُجعل افتراضيًا تلقائيًا) |
| GET | `/api/invoice-titles` | قائمة الرؤوس (الافتراضي في الأعلى) |
| PUT | `/api/invoice-titles/{id}` | تعديل الرأس (لنفسه فقط) |
| DELETE | `/api/invoice-titles/{id}` | حذف الرأس (لنفسه فقط؛ بعد حذف الافتراضي يُعيَّن أقرب سطر تلقائيًا) |
| POST | `/api/invoice-titles/{id}/default` | تعيين كافتراضي (داخل معاملة تصفير بقية أسطر المستخدم) |

**الربط عند التقديم**: POST /api/invoices يدعم title_id اختياريًا — تحليل الرأس يُدخل تلقائيًا invoice_title/tax_no/title_type، وعند غياب title_id يحتفظ بالمسار اليدوي القديم.

### 14. واجهات سجل التصفح (تتطلب مصادقة JWT، الجولة 21)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/browse-history` | آخر الخدمات المُتصفحة (join اسم الخدمة/الغلاف/السعر/السعر الأصلي، تنازليًا viewed_at، per_page الافتراضي 15 والحد 50) |
| DELETE | `/api/browse-history/{item_id}` | حذف سطر واحد (لنفسه فقط، غير قانوني/الآخرين 404) |
| DELETE | `/api/browse-history` | مسح السجل (لنفسه فقط) |

**وقت التسجيل**: بعد نجاح الوصول لواجهة تفاصيل الخدمة يُسجَّل تلقائيًا (غير المسجل يُتخطى؛ التصفح المكرر يحدّث viewed_at فقط ولا يُدرج سطرًا جديدًا).

### 15. واجهات نشاطات الخصم الكامل (الجولة 22)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/full-reduction-activities` | قائمة نشاطات الخصم الكامل السارية (status=1 والوقت ضمن الصلاحية، تنازليًا حسب مبلغ الخصم؛ واجهة عامة) |

**قواعد التراكب عند الطلب**: الخصم الكامل يسري على الطلبات القياسية فقط (المجموعة/البيع المفاجئ يُتخطيان)، وتُحكم العتبة (threshold) بالمبلغ المستحق بعد خصم الكوبونات/بطاقات المرات، وترتيب التراكب **كوبون/بطاقة مرات → خصم كامل → خصم المستوى**؛ يُؤخذ النشاط بأكبر مبلغ خصم؛ يُدمج مبلغ الخصم في discount_amount وتُضاف ملاحظة «خصم كامل: خصم X عند Y»؛ الحد الأدنى للمبلغ الفعلي بعد الخصم 0.01 يوان.

### 16. تصدير مواعيدي ICS (تتطلب مصادقة JWT، الجولة 22)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/order/ics` | تصدير الطلبات السارية خلال 90 يومًا (pending/paid/confirmed/serving) بصيغة iCal (RFC5545) |

**الناتج**: `Content-Type: text/calendar; charset=utf-8` + `Content-Disposition: attachment; filename="my-appointments.ics"`. VEVENT: UID=معرّف الطلب، TZID=Asia/Shanghai، الملخص «حجز: اسم الخدمة» (عند الغياب ينخفض إلى «حجز»)، الوصف (الفني/المتجر/العنوان، المفقود يُتخطى)، LOCATION اسم المتجر؛ النصوص وفق هروب RFC5545 (\, \; \\ \n) + طي الأسطر عند 75 بايت. بلا طلبات تُرجع تقويمًا فارغًا قانونيًا؛ تُصدَّر طلبات المستخدم نفسه فقط.

### 17. واجهات حضور الفني (تتطلب مصادقة JWT، الجولة 22)

| الطريقة | المسار | الوصف |
|------|------|------|
| POST | `/api/technician/attendance/check-in` | تسجيل دخول اليوم (تكرار نفس اليوم 422، مؤشر فريد كضمانة التزامن؛ بعد >10:00 يُعلَّم متأخرًا) |
| POST | `/api/technician/attendance/check-out` | تسجيل خروج اليوم (بدون دخول/خروج مكرر 422، قفل صف للتزامن) |
| GET | `/api/technician/attendance` | قائمة حضور الشهر + ملخص أيام الحضور/إجمالي الساعات/متوسط الساعات (?month=YYYY-MM، غير القانوني 422) |

### 18. واجهات امتثال الخصوصية (تتطلب مصادقة JWT، الجولة 22)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/privacy/data` | تصدير البيانات (مجموعات personal/orders/points/wallet_txns/reviews/addresses/invoices كـ JSON؛ سجلات الخادم تسجّل فقط الهاتف المُخفي + عدد العناصر) |
| POST | `/api/privacy/close-request` | طلب إلغاء الحساب (الرصيد غير 0 / طلبات غير مكتملة / تذاكر جارية 422؛ يُضبط close_status=1 + close_requested_at) |
| POST | `/api/privacy/close-cancel` | إلغاء طلب الإلغاء (close_status 1→0) |
| POST | `/api/privacy/close-confirm` | تأكيد الإلغاء (بعد اكتمال 72 ساعة؛ close_status=2 + close_at + إخفاء هوية phone/nickname إلى user{id} + status=0) |

**اعتراض تسجيل الدخول**: حساب close_status=2 يُرجع عند تسجيل الدخول 403 «الحساب مُلغى».

### 19. واجهات ملف الصحة (تتطلب مصادقة JWT، الجولة 23)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/health-profile` | الاستعلام عن ملف صحتي (بلا ملف يُرجع كائنًا فارغًا) |
| PUT | `/api/health-profile` | إنشاء/تحديث (upsert، ملف واحد لكل شخص؛ allergies/health_notes بحد 500 حرف، preferred_technician_id يُفحص وجوده؛ تحديث الحقول المقدمة فقط، الاستجابة بترميز hashid) |
| DELETE | `/api/health-profile` | حذف ملفي (لنفسه فقط) |

الحقول: allergies (تاريخ الحساسية)/health_notes (ملاحظات صحية)/preferred_technician_id (الفني المفضل، قابل للفارغ).

### 20. واجهات كلمة مرور الدفع للمحفظة (تتطلب مصادقة JWT، الجولة 23)

| الطريقة | المسار | الوصف |
|------|------|------|
| POST | `/api/wallet/pay-password/set` | تعيين كلمة مرور الدفع (6 أرقام `\d{6}`؛ عند التعيين المسبق يلزم تمرير الكلمة القديمة 422 اعتراض) |
| POST | `/api/wallet/pay-password/verify` | التحقق من كلمة مرور الدفع (تُرجع قيمة منطقية صحيحة/خاطئة، دون كتابة في DB) |
| POST | `/api/wallet/pay-password/check` | الاستعلام هل عُيّنت (set: true/false) |

التخزين: hash من password_hash() + pay_password_set_at، ولا يُخزن النص الصريح أبدًا.

### 21. واجهات الخط الزمني لحالات الطلب (تتطلب مصادقة JWT، الجولة 23)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/order/{id}/timeline` | الخط الزمني لتغييرات حالة الطلب (تنازليًا؛ لنفسه فقط، طلبات الآخرين 404 دون كشف الوجود) |

نقاط التتبع: التقديم/الدفع (نقطة استهلاك واحدة في استدعاء WeChat markOrderPaid)/الإلغاء/تأكيد الفني/طلب الاسترداد/قبول الاسترداد/بدء الخدمة/إكمال الخدمة/الإلغاء التلقائي بعد المهلة/عمليات لوحة الإدارة (operator=admin) بإجمالي 8 فئات.

### 22. واجهات عجلة النقاط المحظوظة (تتطلب مصادقة JWT، الجولة 23)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/wheel/prizes` | قائمة جوائز العجلة (تُخفي الحقول الحساسة weight/stock) |
| POST | `/api/wheel/spin` | سحب مرة واحدة (Redis NX + قفل صف ضد التزامن؛ random_int سحب بالأوزان؛ النقاط→عملية earn تتضمن مدة الصلاحية، الرصيد→إيداع lockForUpdate، الكوبون→صرف يدوي pending، بلا جائزة→lose؛ client_token قطعي) |
| GET | `/api/wheel/records` | سجلات سحوباتي (ترقيم) |

### 23. واجهات وضع الزائر (الجولة 24)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/guest/home` | تجميع الصفحة الرئيسية (الشرائح/الإعلانات/تصنيفات الخدمات/الخدمات الرائجة، تخزين Redis svc:guest:home 300s) |
| GET | `/api/guest/services` | قائمة الخدمات (?category_id=hashid&sort=newest|sales|price&page/per_page≤50) |
| GET | `/api/guest/services/{id}` | تفاصيل الخدمة (غير الموجودة 404) |
| GET | `/api/guest/stores` | قائمة المتاجر |
| GET | `/api/guest/technicians` | قائمة الفنيين (المقبولون فقط؛ ?service_id=hashid تصفية؛ تنازليًا بالتقييم) |

مدخل تصفح بدون تسجيل الدخول، بلا مصادقة (وسيط ApiVersion فقط).

### 24. واجهات البيع المفاجئ (تتطلب مصادقة JWT، الجولة 24)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/seckill` | قائمة نشاطات البيع المفاجئ (status=1 وداخل النافذة الزمنية؛ تتضمن المبيعات = عدد طلبات erik_order.seckill_id والمخزون المتبقي) |
| GET | `/api/seckill/{id}` | تفاصيل النشاط (state=not_started/ongoing/ended) |
| POST | `/api/seckill/{id}/buy` | طلب بيع مفاجئ (client_token قطعي + Redis NX 30s ضد التزامن + فحص النشاط؛ لا يخصم المخزون مسبقًا) |

**قواعد الطلب (من 2026-08-26)**: المخزون يُخصم داخل معاملة `/api/order store()` بقفل صف موحد، وbuy يقوم فقط بفحوصات الدخول/القطع؛ سعر البيع المفاجئ = seckill_price (حسب DB)، لا يتراكب مع الكوبونات/النقاط/بطاقات العضوية؛ إلغاء الطلب لا يعيد المخزون؛ استدعاء `/api/order` مباشرة مع seckill_id يخصم المخزون أيضًا.

### 25. واجهة فحص إصدار التطبيق (الجولة 24)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/api/app/version?platform=android|ios` | فحص أحدث إصدار (platform غير قانوني 422؛ بلا إصدار يُرجع كائنًا فارغًا؛ واجهة عامة) |

الاستجابة: id/platform/version_code/version_name/force_update (1=إجباري)/changelog/download_url.

---

## 二、واجهات لوحة الإدارة (admin/ :8787)

ترويسة الطلب: `Authorization: Bearer <admin_token>`, `API-Version: v1`

### لوحة القيادة

**`GET /admin/dashboard`** — بيانات لوحة القيادة

الاستجابة: user_count / order_count / technician_count / today_revenue + بيانات الرسوم البيانية (الطلبات/المبالغ/المستخدمون الجدد/النشاط)

### إدارة المستخدمين

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/user` | قائمة المستخدمين (?keyword/status/page/per_page) |
| POST | `/admin/user` | إضافة مستخدم |
| GET | `/admin/user/{id}` | تفاصيل المستخدم |
| PUT | `/admin/user/{id}` | تعديل المستخدم |
| DELETE | `/admin/user/{id}` | حذف المستخدم |
| POST | `/admin/user/batch/destroy` | حذف جماعي |
| POST | `/admin/user/batch/status` | تفعيل/تعطيل جماعي |

### إدارة بطاقات العضوية

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/member-cards` | قائمة البطاقات (?keyword/status/page/per_page) |
| GET | `/admin/member-cards/{id}` | تفاصيل البطاقة |
| POST | `/admin/member-cards` | إضافة بطاقة (services فحص JSON) |
| PUT | `/admin/member-cards/{id}` | تحديث البطاقة/إتاحتها |
| DELETE | `/admin/member-cards/{id}` | حذف البطاقة (يُرفض عند وجود مستخدمين يحملونها) |

معرّفات الصلاحيات: 365-369.

### لوحة عمل المتجر (الجولة 15)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/stores/workbench-overview` | نظرة لوحة عمل المتجر (?store_id=hashid: طلبات اليوم/إيراد اليوم/الجاري/عدد الفنيين/تحققات اليوم، بمعيار مطابق لطرف service) |
| GET | `/admin/orders` | قائمة الطلبات مع فلترة store_id جديدة (فك hashid) |

معرّف الصلاحية: 372.

### منتجات استبدال النقاط (الجولة 16)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/points-exchange-goods` | قائمة المنتجات (?keyword/status/page/per_page) |
| POST | `/admin/points-exchange-goods` | إضافة منتج (type=coupon/gift_card/wallet؛ coupon يمرر hashid، wallet/gift_card يمرران المبلغ باليوان) |
| PUT | `/admin/points-exchange-goods/{id}` | تحديث المنتج |
| DELETE | `/admin/points-exchange-goods/{id}` | حذف المنتج |
| POST | `/admin/points-exchange-goods/{id}/toggle-status` | تبديل الإتاحة |
| GET | `/admin/points-exchange-goods/{id}/exchanges` | قائمة سجلات الاستبدال (تشمل رقم هاتف المستخدم + لقطة result) |

معرّفات الصلاحيات: 373-378.

### سجلات العمولة (الجولة 16)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/referral-rewards` | سجلات العمولة (?keyword=&page=&limit=، السجلات المدفوعة فقط، تصفية بلقب أو هاتف المُحيل/المُحال، ترميز hashid) |

معرّف الصلاحية: 379.

### مستويات الفني (الجولة 17)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/technician-tiers/logs` | سجلات تغيير المستوى (join اسم الفني وأسماء المستويات القديمة والجديدة، ترميز hashid، ترقيم) |

معرّف الصلاحية: 380.

**التقييم التلقائي**: TierRatingService::evaluate يحسب فوريًا (عدد طلبات erik_order completed + متوسط التقييمات، تقريب لخانة عشرية واحدة) ويكتب back إلى profile.order_count/rating، ويطابق erik_technician_tier_config (min_orders/min_rating) من الأعلى للأسفل، بلا تطابق ينزل إلى أدنى مستوى. ترقية فقط بلا تخفيض (التخفيض يؤثر على نسبة العمولة ومعامل السعر، والتدخل اليدوي من لوحة الإدارة هو الضمانة؛ allowDowngrade=true لإعادة التقييم اليدوي)؛ قطعي (المستوى متطابق يكتفي بمزامنة الإحصائيات)؛ التغيير يسجَّل erik_technician_tier_log + إشعار داخلي. نقاط الإطلاق: WorkController::complete / كتابة التقييمات في ReviewController / الفحص الكسول عند عرض الملف في ProfileController.

### عرض ردود التقييمات (الجولة 18)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/reviews/{id}/reply` | تفاصيل رد التقييم (decodeId → find → 404 → إخراج decorate؛ بلا رد reply=''، وreply/replied_at يُخرجان عبر toArray؛ المسار الساكن يسبق resource) |

معرّف الصلاحية: 381 (slug 'get.admin/reviews/{id}/reply').

### إدارة الفواتير (الجولة 20)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/invoices` | قائمة الفواتير (?status=pending/issued/rejected&page=) |
| POST | `/admin/invoices/{id}/issue` | إصدار الفاتورة (invoice_no إلزامي، status→issued + issued_at؛ قطعي: المُصدرة 422) |
| POST | `/admin/invoices/{id}/reject` | رفض (reject_reason إلزامي، status→rejected؛ فقط pending يُرفض) |

معرّفات الصلاحيات: 382 قائمة / 383 إصدار / 384 رفض.

### إدارة التذاكر (الجولة 20)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/tickets` | قائمة التذاكر (?status=&page=، المسار الساكن يسبق resource لمنع shadow) |
| POST | `/admin/tickets/{id}/reply` | الرد على تذكرة (content إلزامي، يكتب reply_content/replied_at، وتعود التذكرة إلى open) |
| GET | `/admin/tickets/satisfaction` | ملخص الرضا (الجولة 21): total/rated_count/unrated_count/average بخانة عشرية واحدة/توزيع 1-5 نجوم بتكميل الصفر للنجوم الناقصة؛ المسار الساكن يسبق resource |

معرّفات الصلاحيات: 385 رد التذاكر / 387 عرض قائمة التذاكر / 388 إحصاءات رضا التذاكر.

### مراجعة صور التقييمات (الجولة 21)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/review-audit` | قائمة التقييمات بالصور (JSON_LENGTH(images)>0، ?status=visible/hidden&page=، join لقب المستخدم واسم الفني، ترميز hashid للمعرّفات) |
| POST | `/admin/review-audit/{id}/hide` | إخفاء التقييم (فقط visible يُخفى، وإلا 422؛ بعد الإخفاء تختفي تلقائيًا من قائمة تقييمات الفني في طرف المستخدم) |
| POST | `/admin/review-audit/{id}/restore` | استعادة التقييم (فقط hidden يُستعاد، وإلا 422) |

معرّفات الصلاحيات: 389 قائمة / 390 إخفاء / 391 استعادة.

### سجلات العمولة الثانوية (الجولة 20)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/referral-level2` | سجلات العمولة الثانوية (join لقب المُحيل الأول والمُحيل الثاني، ترقيم) |

معرّف الصلاحية: 386. قاعدة الصرف: بعد دفع الطلب يُصرف لمُحيل المُحيل الأول paid×level2_rate (إعداد النظام referral.level2_rate الافتراضي 0.02)، ومفتاح uk_order_referred قطعي ضد التكرار.

### إدارة الحضور (الجولة 22)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/attendance` | سجلات الحضور (?date=YYYY-MM&name=اسم الفني&page=؛ join real_name، ترميز hashid للمعرّفات) |
| GET | `/admin/attendance/stats` | إحصاءات مجمعة حسب الفني (أيام الحضور/إجمالي الساعات/متوسط الساعات؛ ?date=YYYY-MM، غير القانوني 422) |

معرّفا الصلاحيات: 392 قائمة / 393 إحصاءات.

### إدارة نشاطات الخصم الكامل (الجولة 22)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/full-reduction-activities` | قائمة النشاطات (ترقيم) |
| POST | `/admin/full-reduction-activities` | إضافة (threshold/reduction/title/status/start_at/end_at) |
| PUT | `/admin/full-reduction-activities/{id}` | تعديل |
| POST | `/admin/full-reduction-activities/{id}/toggle-status` | إتاحة/إيقاف |
| DELETE | `/admin/full-reduction-activities/{id}` | حذف (مع confirmPassword) |

معرّفات الصلاحيات: 396 قائمة / 397 إضافة / 398 تعديل / 399 إتاحة / 400 حذف (سجل صلاحية واحد يقابل slug method.path واحد، لذا 5 مسارات بـ 5 سجلات).

### سجلات تقسيم الأرباح (الجولة 22)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/profit-sharing` | سجلات تقسيم الأرباح (leftJoin رقم الطلب/لقب الفني، ?status&order_no&technician_name&page=، ترميز hashid) |

معرّف الصلاحية: 394. منطق الخادم: erik_system_config group=profit_sharing (enabled/receiver_ratio)؛ غير المفعّل disabled يهبط إلى التسجيل فقط؛ بعد التفعيل يُطلب تقسيم الأرباح تلقائيًا عند نجاح الدفع (المبلغ=الفعلي×receiver_ratio الافتراضي 0.7، وpending/success لنفس الطلب قطعيًا يُتخطى)؛ بلا بيانات اعتماد لا يُنفَّذ HTTP وتُسجَّل بنية الطلب.

### إدارة عجلة النقاط المحظوظة (الجولة 23)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/lucky-wheel` | قائمة جوائز العجلة (تشمل weight/stock، ترقيم) |
| POST | `/admin/lucky-wheel` | إضافة جائزة (الاسم/النوع points/balance/coupon/none/الوزن/المخزون/الصورة) |
| GET/PUT | `/admin/lucky-wheel/{id}` | التفاصيل / التعديل |
| DELETE | `/admin/lucky-wheel/{id}` | الحذف |
| POST | `/admin/lucky-wheel/{id}/toggle-status` | إتاحة/إيقاف |
| GET | `/admin/lucky-wheel/records` | سجلات السحب (?status&page=، تشمل لقب المستخدم/اسم الجائزة) |

معرّفات الصلاحيات: 401-406. يُسجَّل المساران الساكنان `/lucky-wheel/records` و `/lucky-wheel/{id}/toggle-status` قبل resource لتجنب shadow {id}.

### إدارة مكافأة العميل العائد (الجولة 24)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/return-customer/config` | عرض الإعدادات (مفتاح enabled / نسبة ratio) |
| PUT | `/admin/return-customer/config` | تحديث الإعدادات (enabled in:0,1؛ ratio between:0.01,1) |
| GET | `/admin/return-customer/rewards` | قائمة سجلات المكافآت (?keyword اسم الفني/رقم الطلب/لقب المستخدم، type=return_customer ترقيم) |

معرّفات الصلاحيات: 412-414. قاعدة المكافأة: استهلاك المستخدم الثاني لنفس الفني خلال 30 يومًا (اكتمال الطلب) يُصرف مكافأة = الفعلي × ratio (الافتراضي 0.05)، تُسجَّل في erik_technician_earnings (type=return_customer، status=pending) وتُسوى عبر سلسلة تسوية العمولة الموحدة؛ قطعيًا لنفس الطلب لا تُصرف مرتين.

### إدارة البيع المفاجئ (الجولة 24)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/seckill` | قائمة النشاطات (ترقيم) |
| POST | `/admin/seckill` | إضافة نشاط (name/service_id/seckill_price/original_price/stock/start_at/end_at) |
| GET | `/admin/seckill/{id}` | تفاصيل النشاط |
| PUT | `/admin/seckill/{id}` | تعديل |
| DELETE | `/admin/seckill/{id}` | حذف |
| POST | `/admin/seckill/{id}/toggle-status` | إتاحة/إيقاف |
| GET | `/admin/seckill/{id}/orders` | قائمة طلبات البيع المفاجئ |

معرّفات الصلاحيات: 407-411 و420. المبيعات = عدد طلبات erik_order.seckill_id؛ خصم المخزون بقفل صف، واعتراض النفاد.

### إدارة إصدارات التطبيق (الجولة 24)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/versions` | قائمة الإصدارات |
| POST | `/admin/versions` | إضافة إصدار (platform/version_code/version_name/force_update/changelog/download_url/status) |
| PUT | `/admin/versions/{id}` | تعديل |
| DELETE | `/admin/versions/{id}` | حذف |

معرّفات الصلاحيات: 416-419. واجهة فحص التحديث /api/app/version تأخذ الأحدث (أكبر updated_at/id) من status=1.

### تصدير المواعيد (الجولة 24)

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/technician-schedule/export` | تصدير المواعيد CSV (UTF-8 BOM، يُفتح مباشرة في Excel؛ start_date/end_date إلزاميان والمدى ≤31 يومًا؛ technician_id اختياري hashid) |

معرّف الصلاحية: 415. الأعمدة: معرّف الفني/اسم الفني/التاريخ/تفاصيل الفترات (تحليل time_slots JSON إلى "09:00-12:00, 14:00-18:00").

### صلاحيات الأدوار

| الطريقة | المسار | الوصف |
|------|------|------|
| GET/POST/PUT/DELETE | `/admin/role` | CRUD الأدوار |
| GET/POST/PUT/DELETE | `/admin/permission` | CRUD الصلاحيات (بنية شجرية) |

### إعدادات النظام

| الطريقة | المسار | الوصف |
|------|------|------|
| GET | `/admin/config` | قائمة الإعدادات |
| POST | `/admin/config` | إضافة إعداد (group/key/value/type/description) |
| PUT | `/admin/config/{id}` | تعديل الإعداد |
| DELETE | `/admin/config/{id}` | حذف الإعداد |

### سجلات العمليات

**`GET /admin/log`** — استعلام السجلات

المعلمات: `?user_id/action/source/start_date/end_date/page`

حقل `source`: web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### التصدير

| الطريقة | المسار | الوصف |
|------|------|------|
| POST | `/admin/export/excel` | تصدير Excel (type: users/technicians/orders/finance). إخفاء تلقائي للحقول الحساسة |
| POST | `/admin/export/pdf` | تصدير لوحة PDF (type: dashboard) |

### رفع الملفات

**`POST /admin/upload`** — رفع الملفات (multipart/form-data)

### المركز الشخصي

| الطريقة | المسار | الوصف |
|------|------|------|
| PUT | `/admin/profile` | تعديل البيانات الشخصية |
| PUT | `/admin/profile/password` | تغيير كلمة المرور |
| POST | `/admin/profile/logout` | تسجيل الخروج |

### الاستيراد

**`POST /admin/import/users`** — استيراد المستخدمين جماعيًا (Excel)

### المراقبة

| الطريقة | المسار | المصادقة | الوصف |
|------|------|------|------|
| GET | `/health` | بلا | فحص الصحة |
| GET | `/metrics` | بلا | مؤشرات Prometheus |
| GET | `/.well-known/security.txt` | بلا | جهة الاتصال الأمنية (RFC 9116) |
| GET | `/api/docs` | بلا | وثائق API |

---

## 三、ملاحظات عامة

### رموز الخطأ

| code | الوصف |
|------|------|
| 0 | نجاح |
| 401 | غير مسجل الدخول أو انتهى Token |
| 403 | بلا صلاحية |
| 404 | المورد غير موجود |
| 422 | فشل التحقق من المعلمات |
| 429 | الطلبات متكررة جدًا |

### ترميز المعرّفات

- جميع حقول `id` و `*_id` في استجابات API مشفرة عبر hashids
- معلمات `id` المحمولة في الطلبات يجب أن تكون أيضًا بصيغة ترميز hashids
- يستخدم الطرف الأمامي السلاسل المشفرة مباشرة دون فك يدوي

### إخفاء أرقام الهواتف

صيغة أرقام الهواتف في الاستجابات: `138****8000`. تصدير Excel بنفس المعالجة.

### تشفير البيانات

- طبقة API: الحقول الحساسة في الاستجابات مشفرة عبر `erikwang2013/encryption`
- طبقة DB: رقم الهاتف/بطاقة الهوية/معرّف WeChat إلخ تُشفَّر وتُفك تلقائيًا عبر `erikwang2013/encryptable`

### إعدادات متغيرات البيئة

| المتغير | الوصف |
|------|------|
| WECHAT_SUBSCRIBE_TEMPLATE_ID | معرّف قالب رسالة الاشتراك لتذكير الحجز |
| WECHAT_SUBSCRIBE_TEMPLATE_PAID | معرّف قالب رسالة الاشتراك لنجاح الدفع |
| WECHAT_SUBSCRIBE_TEMPLATE_REFUND | معرّف قالب رسالة الاشتراك للاسترداد |
| WECHAT_SUBSCRIBE_TEMPLATE_VERIFIED | معرّف قالب رسالة الاشتراك للتحقق |
| WECHAT_SUBSCRIBE_TEMPLATE_REMINDER | معرّف قالب رسالة الاشتراك للتذكير قبل بدء الخدمة (الجولة 18) |
| WECHAT_SUBSCRIBE_TEMPLATE_EXPIRY | معرّف قالب رسالة الاشتراك لتذكير انتهاء بطاقات العضوية/الكوبونات (الجولة 18) |

عند عدم إعداد قوالب رسائل الاشتراك تنخفض تلقائيًا إلى الإشعارات الداخلية.

**سيناريوهات رسائل الاشتراك**: SCENE_PAY(نجاح الدفع) / SCENE_REFUND(وصول الاسترداد) / SCENE_VERIFIED(نجاح التحقق) / SCENE_RESCHEDULE(نجاح تغيير الموعد) / SCENE_REMINDER(تذكير قبل بدء الخدمة، الجولة 18) / SCENE_EXPIRY(تذكير الانتهاء، الجولة 18). يُكتب push_sent_at فقط عند نجاح الإرسال، والفشل يُعاد المحاولة في الجولة التالية.

**إشعار وصول الشحن (الجولة 18)**: داخل معاملة استدعاء شحن WeChat (رقم بادئته R) يُكتب إشعار داخلي type='wallet_recharge' «تم شحن ¥X.XX بنجاح»؛ إعادة استخدام قطعية للاستدعاء (فقط أول pending→paid يُطلق)، يُلتزم ذريًا مع تغيير الحالة في نفس المعاملة، وفشل الكتابة لا يعيق المسار الرئيسي.
