# API ডকুমেন্টেশন

## ওভারভিউ

- **বিজনেস API** (service/): `http://localhost:8787` — মিনি-প্রোগ্রাম/APP-কে বিজনেস ইন্টারফেস সরবরাহ করে
- **ম্যানেজমেন্ট ব্যাকএন্ড API** (admin/): `http://localhost:8787` — ম্যানেজমেন্ট ব্যাকএন্ড Flutter Web-কে ইন্টারফেস সরবরাহ করে
- **অথেনটিকেশন পদ্ধতি**: Bearer Token (JWT), রিকোয়েস্ট হেডার `Authorization: Bearer <token>`
- **ভার্সন কন্ট্রোল**: রিকোয়েস্ট হেডার `API-Version: v1` দিয়ে API ভার্সন নিয়ন্ত্রণ করা হয়, URL-এ দেখানো হয় না। ডিফল্ট v1
- **ID এনকোডিং**: সব রিকোয়েস্ট/রেসপন্সের ID ফিল্ড hashids এনকোডিং ব্যবহার করে, বাস্তব ডেটাবেস ID বাইরে থেকে লুকানো থাকে
- **OpenAPI ডকুমেন্টেশন**: `hg/apidoc` দিয়ে জেনারেট করা হয়, ম্যানেজমেন্ট এন্ড ও ক্লায়েন্ট এন্ড আলাদা

| এন্ড | OpenAPI ডকুমেন্ট ঠিকানা | বিবরণ |
|------|------|------|
| ম্যানেজমেন্ট এন্ড | `GET http://localhost:8787/api/docs` | ম্যানেজমেন্ট ব্যাকএন্ড API সম্পূর্ণ স্পেসিফিকেশন (OpenAPI 3.0 JSON) |
| ক্লায়েন্ট এন্ড | `GET http://localhost:8787/api/docs` | বিজনেস API সম্পূর্ণ স্পেসিফিকেশন (OpenAPI 3.0 JSON) |

Swagger UI ইত্যাদি টুল দিয়ে উপরের ঠিকানা ইমপোর্ট করে ইন্টারঅ্যাক্টিভ ডকুমেন্টেশন দেখা যায়।

- **সাধারণ রেসপন্স ফরম্যাট**:

```json
{
  "code": 0,
  "message": "操作成功",
  "data": {}
}
```

পেজিনেশন রেসপন্স:
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

## 一、বিজনেস API (service/ :8787)

### 1. পাবলিক ইন্টারফেস (অথেনটিকেশন প্রয়োজন নেই)

#### 1.1 ভেরিফিকেশন কোড

**`POST /api/captcha/send`** — SMS ভেরিফিকেশন কোড পাঠানো

রিকোয়েস্ট:
```json
{
  "phone": "13800138000"
}
```
রেসপন্স: `{"code":0,"message":"验证码已发送","data":null}`

সীমা: প্রতি ৬০ সেকেন্ডে মাত্র ১ বার পাঠানো যায়, ভেরিফিকেশন কোড ৫ মিনিট বৈধ।

---

#### 1.2 অথেনটিকেশন

**`POST /api/auth/register`** — ফোন নম্বর দিয়ে রেজিস্ট্রেশন

রিকোয়েস্ট:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "abc123",
  "confirm_password": "abc123",
  "referral_code": "A1B2C3D4"
}
```
রেসপন্স:
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

**`POST /api/auth/login`** — পাসওয়ার্ড লগইন

রিকোয়েস্ট:
```json
{
  "phone": "13800138000",
  "password": "abc123"
}
```
রেসপন্স: রেজিস্ট্রেশন রেসপন্সের মতোই, token ও user তথ্য থাকে।

---

**`POST /api/auth/login-by-code`** — ভেরিফিকেশন কোড লগইন

রিকোয়েস্ট:
```json
{
  "phone": "13800138000",
  "code": "123456"
}
```
রেসপন্স: লগইনের মতোই। রেজিস্টার না করা ব্যবহারকারীর জন্য স্বয়ংক্রিয়ভাবে অ্যাকাউন্ট তৈরি হয়।

---

**`POST /api/auth/forget-password`** — পাসওয়ার্ড ভুলে গেলে

রিকোয়েস্ট:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "newpass123",
  "confirm_password": "newpass123"
}
```

---

**`POST /api/auth/refresh`** — Token রিফ্রেশ

রিকোয়েস্ট হেডার: `Authorization: Bearer <旧token>`
রেসপন্স: `{"code":0,"data":{"token":"eyJhbGciOi..."}}`

---

#### 1.3 উইচ্যাট

**`POST /api/wechat/mini-login`** — মিনি-প্রোগ্রাম লগইন

রিকোয়েস্ট: `{"code":"微信登录code"}`
বিবরণ: প্রথম লগইনের পর ফোন নম্বর বাঁধতে `/api/wechat/phone` কল করতে হবে।

---

**`POST /api/wechat/phone`** — ফোন নম্বর বাঁধা

রিকোয়েস্ট: `{"code":"微信手机号组件code"}`

---

**`POST /api/wechat/oa-login`** — অফিসিয়াল অ্যাকাউন্ট লগইন

রিকোয়েস্ট: `{"code":"公众号授权code"}`

---

#### 1.4 পাবলিক সার্ভিস

**`GET /api/common/config`** — পাবলিক কনফিগ

রেসপন্স: চুক্তি টেক্সট (ব্যবহারকারী চুক্তি/প্রাইভেসি চুক্তি/সার্ভিস চুক্তি), আমাদের সম্পর্কে তথ্য, ভার্সন নম্বর থাকে।

---

**`GET /api/common/area`** — শহর/এলাকা তালিকা

---

#### 1.5 সার্ভিস কোয়েরি

**`GET /api/service/categories`** — ক্যাটাগরি তালিকা

প্যারামিটার: `?parent_id=0`

---

**`GET /api/service/items`** — সার্ভিস আইটেম তালিকা

প্যারামিটার: `?category_id=&page=1&per_page=10&sort=sales`

---

**`GET /api/service/detail/{id}`** — সার্ভিস ডিটেইল

রেসপন্সে থাকে: ছবি/নাম/দাম/স্পেক/সময়কাল/বিক্রয় সংখ্যা/রিভিউ তালিকা।

---

**`GET /api/service/products`** — পণ্য তালিকা

**`GET /api/service/stores`** — স্টোর তালিকা

প্যারামিটার: `?lat=&lng=&city=`

---

#### 1.6 টেকনিশিয়ান কোয়েরি

**`GET /api/technician/list`** — টেকনিশিয়ান তালিকা

প্যারামিটার: `?lat=&lng=&service_id=&page=1`
দূরত্ব কাছাকাছি থেকে দূরে সাজানো, রিটার্ন: প্রোফাইল ছবি/নাম/স্কোর/অর্ডার সংখ্যা/ফেভারিট সংখ্যা/দূরত্ব/সবচেয়ে আগে বুকযোগ্য সময়/সার্ভিস দেওয়া যায় কি না।

---

**`GET /api/technician/detail/{id}`** — টেকনিশিয়ান ডিটেইল

রেসপন্সে থাকে: ছবি/নাম/ইন্ট্রো/স্কোর/দূরত্ব/সার্ভিসযোগ্য আইটেম তালিকা/রিভিউ।

---

**`GET /api/technician/schedule/{id}`** — টেকনিশিয়ান শিডিউল

প্যারামিটার: `?date=2026-05-26`
সেই তারিখের বুকযোগ্য সময় স্লট ও উপলব্ধ স্ট্যাটাস রিটার্ন করে।

---

#### 1.7 কনটেন্ট

**`GET /api/content/banners`** — ক্যারোসেল

প্যারামিটার: `?position=home`

**`GET /api/content/articles`** — নোটিশ/আর্টিকেল তালিকা

প্যারামিটার: `?type=announcement&page=1`

**`GET /api/content/article/{id}`** — আর্টিকেল ডিটেইল

---

#### 1.8 LBS

**`GET /api/lbs/nearby-stores`** — কাছের স্টোর

প্যারামিটার: `?lat=&lng=&radius=5000`

**`GET /api/lbs/geocode`** — রিভার্স জিওকোডিং

প্যারামিটার: `?lat=&lng=`

---

### 2. ইউজার ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন)

সব ইন্টারফেসের রিকোয়েস্ট হেডারে `Authorization: Bearer <token>` থাকে

#### 2.1 পার্সোনাল প্রোফাইল

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/user/profile` | ব্যক্তিগত তথ্য পাওয়া |
| PUT | `/api/user/profile` | নিকনেম/অবতার/লিঙ্গ আপডেট |
| POST | `/api/user/change-password` | পাসওয়ার্ড পরিবর্তন (old_password/new_password/confirm_password) |
| POST | `/api/user/change-phone` | ফোন নম্বর পরিবর্তন (old_code/new_phone/new_code) |
| POST | `/api/user/cancel-account` | অ্যাকাউন্ট বাতিল (পাসওয়ার্ড ভেরিফাই করতে হবে) |
| POST | `/api/user/logout` | লগআউট (token ব্ল্যাকলিস্টে) |
| POST | `/api/user/switch-role` | পরিচয় স্যুইচ (role: customer/technician) |

technician-এ স্যুইচ করতে হলে আগে থেকে approved স্ট্যাটাসের টেকনিশিয়ান প্রোফাইল থাকতে হবে।

#### 2.2 ঠিকানা ম্যানেজমেন্ট

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/user/addresses` | ঠিকানা তালিকা |
| POST | `/api/user/addresses` | নতুন ঠিকানা (contact_name/contact_phone/province/city/district/detail/lat/lng/is_default) |
| GET | `/api/user/addresses/{id}` | ঠিকানা ডিটেইল |
| PUT | `/api/user/addresses/{id}` | ঠিকানা আপডেট |
| DELETE | `/api/user/addresses/{id}` | ঠিকানা ডিলিট |

ডিফল্ট সেট করলে অন্য ডিফল্ট ঠিকানা স্বয়ংক্রিয়ভাবে বাতিল হয়।

#### 2.3 ফেভারিট

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/user/favorites` | ফেভারিট তালিকা (?type=service/technician) |
| POST | `/api/user/favorites` | ফেভারিট যোগ (target_type/target_id) |
| DELETE | `/api/user/favorites/{id}` | ফেভারিট বাতিল |

#### 2.4 মতামত ও ফিডব্যাক

`POST /api/user/feedback` — ফিডব্যাক জমা (content + images অ্যারে)

#### 2.5 প্রমোশন ও রেফারেল

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/user/referral` | প্রমোশন তথ্য (রেফারেল কোড/রেফার করা সংখ্যা/প্রথম অর্ডার সংখ্যা/পাওয়া পয়েন্ট) |
| GET | `/api/user/referral/qrcode` | প্রমোশন QR কোড (রেফারেল কোড + ইনভাইট লিংক) |
| GET | `/api/user/referral/referred-users` | রেফার করা ইউজার তালিকা |
| GET | `/api/user/referral/earnings` | ডিস্ট্রিবিউশন কমিশন বিবরণ (পেজিনেশন: রেফার করা ব্যক্তির নিকনেম/অবতার/অর্ডার নম্বর/পরিমাণ/দেওয়ার সময়) |

**ডিস্ট্রিবিউশন কমিশন**: রেফার করা ব্যক্তির প্রথম অর্ডার completed হওয়ার পর দেওয়া হয়, পরিমাণ = paid_amount × reward_rate (erik_system_config referral.reward_rate, ডিফল্ট 0.05, অবৈধ মান হলে কনস্ট্যান্টে ফিরে যায়)। রো লক + rewarded_at নাল চেক + প্রথম অর্ডার পুনঃপরীক্ষা — তিন স্তরের আইডেমপোটেন্সি; ওয়ালেটে WalletTxn type=referral_reward হিসেবে জমা হয়।

#### 2.6 পয়েন্ট ট্রান্সফার (রাউন্ড ১৯)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| POST | `/api/user/points/transfer` | পয়েন্ট ট্রান্সফার (to_user_id hashid/points) |
| GET | `/api/user/points/transfers` | ট্রান্সফার রেকর্ড (?direction=sent/received&page=1) |

**পয়েন্ট ট্রান্সফার**: রিসিভারের hashid ডিকোড + অস্তিত্ব ৪০৪, নিজেকে ট্রান্সফার ৪২২, পয়েন্ট ১-১০০০০ এর বাইরে ৪২২, ব্যালেন্স SUM অ্যাগ্রিগেশন কম ৪২২, একদিনে মোট ১০০০০ সীমা ৪২২। কনকারেন্সি সুরক্ষা: Redis NX লক `points_transfer:{user}` ৩০s → ট্রানজেকশনে উভয় পক্ষের শেষ লেনদেন lockForUpdate (user_id ক্রমবর্ধমান, পারস্পরিক ট্রান্সফার ডেডলক রোধ) → লকের ভেতরে ব্যালেন্স/সীমা/রিসিভার পুনঃপরীক্ষা। লেনদেন নিয়ম: পাঠানোর পক্ষ type=consume/source=points_transfer নেগেটিভ মান (balance=আগের স্ন্যাপশট-এইবার), গ্রহণের পক্ষ type=earn/source=points_transfer পজিটিভ মান expires_at সহ (PointsExpiryTimer স্বাভাবিকভাবে এক্সপায়ার করতে পারে); commit-এর পর গ্রহণকারীকে স্টেশন-ইন নোটিফিকেশন type='points_received' (ব্যর্থ হলে শুধু warn)।

#### 2.7 নোটিফিকেশন পছন্দ সেটিংস (রাউন্ড ১৯)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/user/notify-settings` | নোটিফিকেশন সুইচ কোয়েরি (৫ টাইপ ফুল) |
| PUT | `/api/user/notify-settings` | সুইচ ব্যাচ আপডেট (types: {service_reminder: 0/1, ...}) |

**নোটিফিকেশন সুইচ**: erik_user_notify_setting টেবিল (user_id+type কম্পোজিট ইউনিক কী, ডিফল্ট সারি=ডিফল্ট চালু)। ৫ টাইপ: service_reminder সার্ভিস রিমাইন্ডার / card_expiry এক্সপায়ারি রিমাইন্ডার (কার্ড+কুপন ইউনিফাইড ছাতা) / points_expiry পয়েন্ট এক্সপায়ার / marketing মার্কেটিং (রিজার্ভ) / system সিস্টেম (বন্ধ করা যাবে না, PUT-এ জোর করে 1)। গেট: notifySettingEnabled ৩টি টাইমার প্রসেসে সংযুক্ত (ServiceReminderTimer/ExpiryReminderTimer/PointsExpiryTimer) + সাবস্ক্রিপশন ইভেন্ট সিন ম্যাপিং (PAY/REFUND/VERIFIED/RESCHEDULE→system সবসময় পাঠায়, REMINDER→service_reminder, EXPIRY→card_expiry); টাইপ বন্ধ থাকলে স্টেশন-ইন নোটিফিকেশন ও সাবস্ক্রিপশন মেসেজ দুটোই স্কিপ হয়।

---

### 3. টেকনিশিয়ান ইন্টারফেস (JWT + টেকনিশিয়ান পরিচয় প্রয়োজন)

#### 3.1 টেকনিশিয়ান প্রোফাইল

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/technician/profile` | টেকনিশিয়ান প্রোফাইল পাওয়া |
| PUT | `/api/technician/profile` | প্রোফাইল আপডেট (avatar/intro/real_name/gender/id_card/id_card_front/id_card_back) |

প্রথমবার সম্পূর্ণভাবে পূরণ করলে তা ইন্টারনশিপ আবেদন হিসেবে ধরা হয়, status=pending রিভিউ অপেক্ষায়।

#### 3.2 শিডিউল

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/technician/schedule` | শিডিউল কোয়েরি (?start_date=&end_date=) |
| PUT | `/api/technician/schedule` | শিডিউল সেট (date/time_slots/status), সময় স্লট ওভারল্যাপ ৪২২「与已有排班时间冲突」 |
| POST | `/api/technician/schedule/batch` | ব্যাচ শিডিউল (রাউন্ড ২৩): তারিখ পরিসর ≤৭ দিন + weekdays ফিল্টার, ইতিমধ্যে শিডিউল করা দিন স্কিপ, রেসপন্সে created/skipped |

#### 3.3 টেকনিশিয়ান অর্ডার

`GET /api/technician/orders` — অর্ডার তালিকা (?status=&page=1)

#### 3.4 আয়

`GET /api/technician/earnings` — আয় ওভারভিউ (today_income/pending_settlement/balance + লেনদেন তালিকা)

#### 3.5 উত্তোলন

`POST /api/technician/withdraw` — উত্তোলন আবেদন (amount)
নিয়ম: প্রতি মাস ২০ তারিখে উত্তোলন করা যায়, T+1 এ টাকা পৌঁছায়, সর্বনিম্ন পরিমাণ/শতক সীমা ব্যাকএন্ড কনফিগ।

**ইন-ফ্লাইট রিজার্ভ (২০২৬-০৮-২৬)**: আবেদনের সময় ব্যালেন্স থেকে ইন-ফ্লাইট (pending/approved) রিজার্ভ কেটে রাখা হয়; অনুমোদনের ট্রান্সফারের আগে পুনঃপরীক্ষা settled − withdrawn − ইন-ফ্লাইট ≥ উত্তোলন পরিমাণ; কনকারেন্ট অনুমোদনে ডাবল পেমেন্ট হয় না।

#### 3.6 রিভিউ রিপ্লাই (রাউন্ড ১৮)

`POST /api/technician/review/reply/{order_id}` — টেকনিশিয়ান রিভিউ রিপ্লাই (reply)। রিভিউ না থাকলে/নিজের না হলে ইউনিফাইড ৪০৪ (অস্তিত্ব লিক হয় না); আগে থেকে রিপ্লাই থাকলে ৪২২ (আইডেমপোটেন্ট রিজেক্ট, ওভাররাইট হয় না); খালি রিপ্লাই ৪২২। রিপ্লাই সফল হলে ইউজারকে স্টেশন-ইন নোটিফিকেশন (type='review_reply')।

#### 3.7 ওয়ার্কবেঞ্চ

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/technician/work/today` | আজকের টাস্ক তালিকা |
| GET | `/api/technician/work/records` | সম্পন্ন রেকর্ড পেজিনেশন |
| POST | `/api/technician/work/{id}/start` | সার্ভিস শুরু |
| POST | `/api/technician/work/{id}/complete` | সার্ভিস সম্পন্ন |

**আজকের টাস্ক**: status ∈ [confirmed, serving], service_time আজ বা খালি, রিটার্ন service_name/price/nickname/avatar।

**সম্পন্ন রেকর্ড**: status ∈ [serving, completed], service_end_at উল্টো ক্রমে, পেজিনেশন রেসপন্সে meta থাকে।

**সার্ভিস শুরু/সম্পন্ন**: রো লক + স্টেট মেশিন ভ্যালিডেশন, আইডেমপোটেন্ট অপারেশন। শুরু করলে service_start_at লেখা হয়; সম্পন্ন করলে service_end_at লেখা হয় এবং স্টেশন-ইন নোটিফিকেশন পাঠায়। এরর কোড: নিজের নয় ৪০৩, স্টেট ভুল ৪২২, অবৈধ hashid ৪২২।

---

### 4. অর্ডার ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| POST | `/api/order` | অর্ডার তৈরি (order_type/items/store_id/technician_id/service_time/coupon_id/user_coupon_id/promotion_id/remark) |
| GET | `/api/order/list` | অর্ডার তালিকা (?status=&page=1) |
| GET | `/api/order/detail/{id}` | অর্ডার ডিটেইল |
| POST | `/api/order/cancel/{id}` | অর্ডার বাতিল (reason) |
| POST | `/api/order/pay/{id}` | পেমেন্ট শুরু (pay_channel: wechat/balance, use_points: ঐচ্ছিক পয়েন্ট ক্যাশব্যাক) |
| POST | `/api/order/refund/{id}` | রিফান্ড আবেদন |
| POST | `/api/order/verify/{id}` | ভেরিফিকেশন (code: QR কোড মান) |
| POST | `/api/order/reschedule/{id}` | অ্যাপয়েন্টমেন্ট পুনঃনির্ধারণ (new_service_time বাধ্যতামূলক/reason ঐচ্ছিক) |
| GET | `/api/order/logistics/{id}` | লজিস্টিকস ট্র্যাকিং (রাউন্ড ১৯, product অর্ডার) |
| POST | `/api/order/review/{order_id}` | রিভিউ জমা (rating 1-5/content/images)（রাউন্ড ১৯补注册） |
| POST | `/api/order/review/{order_id}/append` | রিভিউ ফলো-আপ (content/images কমা-বিভক্ত)（রাউন্ড ১৯） |

**অর্ডার স্ট্যাটাস**: pending(待支付) → paid(已支付) → confirmed(已确认) → serving(服务中) → completed(已完成)

**অর্ডার তৈরি করার সময়**: Redis SETNX দিয়ে টেকনিশিয়ান ৩ মিনিট লক, পেজ থেকে বের হলে বা টাইমআউট হলে রিলিজ।

**দাম টেম্পারিং সুরক্ষা (২০২৬-০৮-২৬)**: অর্ডার আইটেমের পরিমাণ সবসময় ডেটাবেস রেকর্ড অনুযায়ী (target_type=service হলে erik_service, product হলে erik_product), ক্লায়েন্ট পাঠানো দাম হিসাবে ধরা হয় না; অজানা target_type ৪২২; target_id অবশ্যই hashid এনকোড করা মান দিতে হবে (raw id দিলে ডিকোড 0 → ৪২২「商品不存在或已下架」); গ্রুপ বাই/সেকিল দামও DB অনুযায়ী।

**রিফান্ড নিয়ম**: অর্ডারের ১৫ মিনিটের মধ্যে বা শুরুর >৬ ঘণ্টা আগে ১০০% / ≤৬ ঘণ্টা ৯০% / শুরু হয়ে গেলে ৮০% / শুরু কনফার্ম হওয়ার পর রিফান্ড নেই।

**কুপন কাটতি**: অর্ডার তৈরিতে ঐচ্ছিক user_coupon_id (hashid) পাঠানো যায়। এরর কোড: অন্যের কুপন ৪০৪, থ্রেশহোল্ড কম/এক্সপায়ার্ড/অফ-শেল্ফ/ব্যবহৃত ৪২২, অবৈধ hashid ৪২২। দুই ধাপের কাটতি: অর্ডার তৈরির সময় PriceCalculator.applyCoupon শুধু রিড-চেক করে কাটতি পরিমাণ হিসাব করে discount_amount-এ লেখে; পেমেন্ট সফলের পর consume কুপনটি used করে; রিফান্ডে restoreCouponAndCard আইডেমপোটেন্ট ফেরত দেয়।

**ব্যালেন্স পেমেন্ট ও রিফান্ড**: পেমেন্ট রিকোয়েস্ট বডিতে `pay_channel: "balance"` দিলে ওয়ালেট ব্যালেন্স ব্যবহার হয়; উইচ্যাট রিফান্ড ও ব্যালেন্স রিফান্ড দুটোই পরিমাণ ওয়ালেট ব্যালেন্সে ফেরত দেয়।

**পয়েন্ট ক্যাশব্যাক**: পেমেন্ট রিকোয়েস্ট বডিতে ঐচ্ছিক `use_points` (পূর্ণসংখ্যা)। SUM অ্যাগ্রিগেশন দিয়ে পয়েন্ট ব্যালেন্স ভেরিফাই করা হয় (erik_user_points এর balance কলাম এককালীন ইনক্রিমেন্ট স্ন্যাপশট, সরাসরি ব্যালেন্স হিসেবে ধরা যায় না), কাটতি = floor(use_points / config('app.points_rate', 100)) ইউয়ান, প্রকৃত পরিমাণ = মূল পরিশোধযোগ্য − কাটতি (নিম্নসীমা 0.01, পরিশোধযোগ্যের বেশি হলে পরিশোধযোগ্য অনুযায়ী পয়েন্ট নষ্ট হয় না)। সফল হলে type=consume/source=points_offset কনজিউম লেনদেন লেখা হয় (আইডেমপোটেন্ট, রিট্রাইতে ডাবল কাটা হয় না)। ব্যালেন্স কম ৪২২।

**পয়েন্ট রিফান্ড**: বাতিল/রিফান্ডে points_offset এ খরচ করা পয়েন্ট ফেরত (type=earn/source=points_refund): বাতিলে পূর্ণ, রিফান্ডে অনুপাত অনুযায়ী, ৫টি হুক পয়েন্টে আইডেমপোটেন্ট (refundOffsetPoints)।

**গ্রুপ বাই অর্ডার (রাউন্ড ১৬)**: অর্ডার তৈরিতে ঐচ্ছিক `promotion_id` (hashid)। ভ্যালিডেশন: শুধু group_buy টাইপ, অ্যাক্টিভিটি বৈধ সময়ে, কলকারী অংশগ্রহণকারী, পূর্ণ হয়নি (গ্রুপ লক ৪২২), অর্ডার সার্ভিস অ্যাক্টিভিটির সাথে ম্যাচ; গ্রুপ বাই দাম = মূল দাম × discount_percent/100, কুপন/টাইম কার্ড/পয়েন্ট স্ট্যাকিং নিষিদ্ধ (একটিও দিলে ৪২২)। অর্ডারে promotion_id/participant_id লেখা হয়; পেমেন্ট সম্পূর্ণভাবে `POST /api/order/pay/{id}` রিইউজ করে, pay-এর সময় লেজি চেক অ্যাক্টিভিটি বন্ধ হয়েছে (সময় শেষে গ্রুপ হয়নি) → অর্ডার অটো বাতিল + টেকনিশিয়ান লক রিলিজ।

**সেকিল অর্ডার (রাউন্ড ১৮, অফলাইন)**: ~~অর্ডার তৈরিতে `promotion_id` (flash_sale টাইপ)~~ —— ২০২৬-০৮ থেকে পুরনো প্রমোশন FLASH_SALE চ্যানেল ডিলিট, store() প্রমোশন ব্রাঞ্চে শুধু গ্রুপ বাই GROUP_BUY আছে (অ-গ্রুপ বাই প্রমোশন ৪২২); সেকিল ইউনিফাইডভাবে রাউন্ড ২৪-এর `/api/seckill` চ্যানেল দিয়ে যায় (seckill_id store ট্রানজেকশনে ইনজেক্ট হয়ে রো লক দিয়ে স্টক কাটে), PromotionController::index এ flash_sale ফিল্টার, show/join-এ তার জন্য ৪০০, `Promotion::TYPE_FLASH_SALE` কনস্ট্যান্ট ঐতিহাসিক ডেটা কম্প্যাটিবিলিটির জন্য রেখে দেওয়া।

**অ্যাপয়েন্টমেন্ট পুনঃনির্ধারণ (রাউন্ড ১৭)**: `POST /api/order/reschedule/{id}` এ new_service_time (বাধ্যতামূলক) + reason (ঐচ্ছিক) পাঠান, একই টেকনিশিয়ানে সময় বদল। নিয়ম: শুধু নিজের অর্ডার (অন্যের ৪০৪), শুধু appointment টাইপ এবং status pending/paid/confirmed বদলানো যায় (বাকি ৪২২), মূল সার্ভিস শুরু হওয়ার ≥ ৬ ঘণ্টা আগে (পূর্ণ রিফান্ড উইন্ডোর সাথে মিল) বদলানো যায়। কনকারেন্সি সুরক্ষা: B1 order_lock (pay/cancel/refund-এর সাথে একই মিউটেক্স ফ্যামিলি) → নতুন স্লটের টেকনিশিয়ান লক Redis SETNX EX 180 (কনকারেন্ট বদল ওভারসেল রোধ) → ট্রানজেকশনে রো লক রিরিড + B2 শিডিউল কনফ্লিক্ট DB ভ্যালিডেশন (এই অর্ডার বাদ) → service_time আপডেট + erik_order_reschedule রেকর্ড → মূল স্লট লক রিলিজ, নতুন স্লট লক এই অর্ডার ধরে রাখে → SCENE_RESCHEDULE সাবস্ক্রিপশন মেসেজ (কনফিগ না থাকলে স্টেশন-ইন নোটিফিকেশনে ডিগ্রেড)। ব্যর্থ পথে ট্রানজেকশন রোলব্যাকের সাথে নতুন স্লট লকও রিলিজ।

**লজিস্টিকস ট্র্যাকিং (রাউন্ড ১৯)**: `GET /api/order/logistics/{id}` — শুধু নিজের product অর্ডার দেখা যায় (নিজের নয়/প্রোডাক্ট নয়/শিপ হয়নি — সব ইউনিফাইড ৪০৪)। order.remark JSON পড়ে (shipping_company/tracking_no/shipped_at, admin MallOrderController::ship() শিপ করার সময় লেখে), parseShippingInfo/parseReceiver ডাবল পার্সিং পুরনো ফরম্যাট কভার করে; প্রাপকের ফোন নম্বর মাস্ক ১৩৮****৫৬৭৮।

**রিভিউ (রাউন্ড ১৯)**: `POST /api/order/review/{order_id}` রিভিউ জমা (rating বাধ্যতামূলক ১-৫, content/images ঐচ্ছিক): নিজের নয় ৪০৪, non-completed ৪২২, ডুপ্লিকেট রিভিউ ৪০০। `POST /api/order/review/{order_id}/append` ফলো-আপ (content বাধ্যতামূলক, images কমা-বিভক্ত): রিভিউ নেই/নিজের নয় ইউনিফাইড ৪০৪, non-completed ৪২২, ডুপ্লিকেট ফলো-আপ ৪২২, খালি কনটেন্ট ৪২২; সফল হলে append_content/append_images(JSON)/append_at লেখে ও টেকনিশিয়ানকে স্টেশন-ইন নোটিফিকেশন type='review_append', রেসপন্সে append ফিল্ড দেখা যায়।

### 4.1 আফটার-সেলস ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| POST | `/api/aftersales` | আফটার-সেলস আবেদন (order_id hashid/type: refund\|exchange/reason), নিজের অর্ডার ভেরিফাই ৪০৪, status paid+completed হলে তবেই আবেদন ৪২২, একই অর্ডারে চলমান আফটার-সেলস ডিডুপ ৪২২ |
| GET | `/api/aftersales` | আমার আফটার-সেলস তালিকা (?status=&page=1&limit=) |
| GET | `/api/aftersales/{id}` | আফটার-সেলস ডিটেইল (অধিকারী ভেরিফাই ৪০৪) |

**আফটার-সেলস স্ট্যাটাস**: pending(待审核) → approved(通过) / rejected(拒绝)। approved শুধু স্ট্যাটাস পরিবর্তন, রিফান্ড অ্যাকশন `POST /api/order/refund/{id}` রিইউজ করে।

---

### 4.2 গ্রুপ বাই/প্রমোশন ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন; FLASH_SALE অফলাইন)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/promotions` | অ্যাক্টিভিটি তালিকা (?type=group_buy；flash_sale ফিল্টার হয়ে রিটার্ন হয় না) |
| GET | `/api/promotions/{id}` | অ্যাক্টিভিটি ডিটেইল (অংশগ্রহণকারী সংখ্যা/গ্রুপ হয়েছে কি না সহ; flash_sale টাইপ ৪০০) |
| GET | `/api/promotions/{id}/participants` | অংশগ্রহণকারী তালিকা |
| POST | `/api/promotions/join/{id}` | অ্যাক্টিভিটিতে অংশগ্রহণ (রাউন্ড ১৫ পরিপূর্ণ: রেসপন্সে discount_percent/original_price/group_price; flash_sale টাইপ ৪০০) |

**অংশগ্রহণ নিয়ম**: group_buy পূর্ণ (≥min_people) হলে লক, গ্রুপ হওয়ার পর নতুন অংশগ্রহণ ৪২২; সময় শেষে পূর্ণ না হলে লেজি ক্লোজ (show/join-এ status 0)। join-এর পর গ্রুপ বাই দামে অর্ডার করতে দেখুন「গ্রুপ বাই অর্ডার（রাউন্ড ১৬）」। সেকিল আর এই চ্যানেলে যায় না, দেখুন「24. সেকিল ইন্টারফেস」。

---

### 5. মার্কেটিং ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/marketing/coupons` | কুপন তালিকা (?status=available/used/expired) |
| POST | `/api/marketing/coupons/receive` | কুপন গ্রহণ (coupon_id) |
| GET | `/api/marketing/cards` | মেম্বার কার্ড তালিকা |
| POST | `/api/marketing/cards/buy` | মেম্বার কার্ড কেনা (card_id) |
| GET | `/api/marketing/cards/my` | আমার টাইম কার্ড তালিকা |
| POST | `/api/marketing/cards/use` | টাইম কার্ড ভেরিফাই (user_card_id/service_id/remark?) |
| GET | `/api/marketing/gift-cards` | গিফট কার্ড তালিকা |
| GET | `/api/marketing/gift-cards/my` | আমার গিফট কার্ড (redeem রেকর্ড) |
| POST | `/api/marketing/gift-cards/redeem` | গিফট কার্ড রিডিম (cash টাইপ রিডিমের পর ওয়ালেট ব্যালেন্স রিচার্জ) |
| GET | `/api/marketing/points` | পয়েন্ট লেনদেন (?type=earn/use/expire&source=order/referral/gift_card/check_in/admin) |
| GET | `/api/marketing/points-exchange` | পয়েন্ট এক্সচেঞ্জ পণ্য তালিকা (আপ + রিয়েল-টাইম অবশিষ্ট স্টক + এক্সচেঞ্জ সংখ্যা) |
| POST | `/api/marketing/points-exchange/{id}` | এক্সচেঞ্জ (type=coupon কুপন দেওয়া / wallet জমা / gift_card কার্ড কোড রিটার্ন) |
| POST | `/api/marketing/coupons/transfer` | ট্রান্সফার কোড জেনারেট (user_coupon_id: ৮-অক্ষরের ইউনিক কোড/৭ দিন বৈধ) |
| POST | `/api/marketing/coupons/claim` | ট্রান্সফারড কুপন গ্রহণ (code) |
| GET | `/api/marketing/coupons/transfers` | ট্রান্সফার রেকর্ড (পাঠানো pending/claimed/expired + গ্রহণ claimed) |

**টাইম কার্ড**: cards/my রিটার্ন card_id/name/type/services/total_times/used_times/remaining_times/start_at/end_at/status (রিয়েল-টাইম হিসাব)। ভেরিফাই সফল হলে {order_id, usage_id, remaining_times} রিটার্ন; এরর কোড: অবৈধ hashid ৪২২, বার কম ৪২২, এক্সপায়ার্ড ৪০০, নিজের নয় ৪০৪, Redis ডুপ্লিকেট সুরক্ষা ৪০০।

**গিফট কার্ড**: gift-cards/my রিটার্ন redeem রেকর্ড (type/amount/gift_name/status/used_at)।

**পয়েন্ট নিয়ম**: বিবরণ পেজিনেশন, type ফিল্টার (earn/use/expire), source ফিল্টার (order/referral/gift_card/check_in/admin)। চেক-ইন পয়েন্ট (CheckIn, type=earn); কনজিউমে পয়েন্ট floor(paid_amount×1), ভেরিফাইয়ের সময় দেওয়া হয় ও আইডেমপোটেন্ট; রিফান্ডে অনুপাত অনুযায়ী পয়েন্ট কাটা।

**পয়েন্ট এক্সপায়ার (রাউন্ড ১৭)**: erik_user_points.expires_at কলাম (কনফিগ points.expiry_days, ডিফল্ট ৩৬৫ দিন, ≤০ হলে কখনো এক্সপায়ার না); সব earn এক্সপায়ারি তারিখ সহ লেখা হয়; PointsExpiryTimer টাইমার প্রসেস প্রতি ৬০ সেকেন্ডে কার্সর স্ক্যান এক্সপায়ারড earn সারি, type=expire নেগেটিভ মান কাটা লেনদেন লেখে (source=expiry + order_id মূল লেনদেনের ট্রেস, তিন স্তর আইডেমপোটেন্সি) + অ্যাগ্রিগেট স্টেশন-ইন নোটিফিকেশন「您有 X 积分已过期」; উপলব্ধ ব্যালেন্স SUM হিসাবে expire নেগেটিভ সারি অন্তর্ভুক্ত, এক্সপায়ারড পয়েন্ট আর ক্যাশব্যাক/এক্সচেঞ্জ করা যায় না।

**কুপন ট্রান্সফার (রাউন্ড ১৭)**: transfer ভ্যালিডেশন: কুপন নিজের/available/কুপন ডেফিনিশন এক্সপায়ার না/আগে ট্রান্সফার হয়নি, ৮-অক্ষরের ডি-অবফাসকেটেড ইউনিক ট্রান্সফার কোড জেনারেট (uk_code ইউনিক ইনডেক্স ব্যাকআপ), ৭ দিন বৈধ। claim অ্যান্টি-অ্যাবিউজ: Redis NX লক (coupon_transfer_claim:{code} ৩০s) + রো লক রি-ভেরিফাই ডাবল স্পেন্ড রোধ, uk_user_coupon ইউনিক ইনডেক্স এক কুপন শুধু একবার ট্রান্সফার, ট্রান্সফারড কুপন আবার ট্রান্সফার করা যায় না (নতুন কুপনে ট্রান্সফার রেকর্ড নেই স্বাভাবিকভাবে ব্লক), নিজের ট্রান্সফারড কুপন নিজে নেওয়া যায় না ৪২২, গ্রহণকারী মূল মালিক নয়; লেজি চেক এক্সপায়ার হলে expired করে মূল কুপন available ফিরিয়ে দেয়। claim ট্রানজেকশনে মূল কুপন used + নতুন UserCoupon তৈরি করে গ্রহণকারীর সাথে বাঁধে (coupon_id অপরিবর্তিত অর্থাৎ বৈধতা অপরিবর্তিত) + রেকর্ড claimed।

---

### 6. নোটিফিকেশন ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/notification` | নোটিফিকেশন তালিকা (?type=order/system&page=1) |
| PUT | `/api/notification/read/{id}` | পড়া হয়েছে চিহ্নিত |
| PUT | `/api/notification/read-all` | সব পড়া হয়েছে |

---

### 7. ওয়ালেট ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/wallet` | ওয়ালেট ব্যালেন্স + লেনদেন পেজিনেশন |
| POST | `/api/wallet/recharge` | রিচার্জ অর্ডার তৈরি (amount: ইউয়ান) |
| POST | `/api/wallet/recharge/{id}/pay` | রিচার্জ অর্ডার পেমেন্ট (উইচ্যাট) |
| POST | `/api/wallet/transfer` | ব্যালেন্স ট্রান্সফার (to_user_id hashid/amount/remark ঐচ্ছিক/client_token ঐচ্ছিক)（রাউন্ড ১৯） |
| GET | `/api/wallet/transfers` | ট্রান্সফার রেকর্ড (?direction=out/in&page=1)（রাউন্ড ১৯） |
| GET | `/api/wallet/transfers/{id}` | ট্রান্সফার ডিটেইল (শুধু দুই পক্ষ দেখতে পারে, অন্যের ৪০৪)（রাউন্ড ১৯） |

**লেনদেন**: wallet_txn টাইপ: recharge / consume / refund / gift_card / referral_reward(分销返佣) / referral_level2(二级返佣) / points_exchange(积分兑换入账), পেজিনেশন রিটার্ন।

**রিচার্জ**: `POST /api/wallet/recharge` এ amount (ইউয়ান) দিয়ে রিচার্জ অর্ডার তৈরি হয়, রিচার্জ অর্ডার hashid রিটার্ন। `POST /api/wallet/recharge/{id}/pay` উইচ্যাট পেমেন্ট শুরু করে, রেসপন্সে sign_params থাকে (অর্ডার পেমেন্ট মোডের মতো); পেমেন্ট কলব্যাকে R প্রিফিক্সের out_trade_no দিয়ে রিচার্জ অর্ডার ও অর্ডার আলাদা করা হয়।

**ব্যালেন্স পেমেন্ট**: অর্ডার পেমেন্ট রিকোয়েস্ট বডিতে `pay_channel: "balance"` দিলে ওয়ালেট ব্যালেন্স ব্যবহার হয়; উইচ্যাট রিফান্ড ও ব্যালেন্স রিফান্ড দুটোই পরিমাণ ওয়ালেট ব্যালেন্সে ফেরত দেয়।

**ব্যালেন্স ট্রান্সফার (রাউন্ড ১৯)**: `POST /api/wallet/transfer` — গ্রহণকারীর hashid ডিকোড + অস্তিত্ব ৪০৪, নিজেকে ৪২২, পরিমাণ ০.০১-১০০০/বার ৪২২ (DECIMAL তুলনা, float নিষিদ্ধ), ব্যালেন্স কম ৪২২, একদিনে মোট ৫০০০ ইউয়ান ৪২২। কনকারেন্সি/আইডেমপোটেন্সি: Redis NX লক wallet_transfer:{from} ৩০s পাঠানোর পক্ষ সিরিয়ালাইজ → ট্রানজেকশনে দুই পক্ষের ওয়ালেট সারি user_id ক্রমে lockForUpdate (স্থির ক্রম ডেডলক রোধ) → পাঠানোর পক্ষ কাটা + গ্রহণের পক্ষ বাড়ানো + WalletTxn ডাবল লেনদেন (transfer_out/transfer_in balance_after স্ন্যাপশট সহ) + ট্রান্সফার রেকর্ড completed + গ্রহণকারীকে স্টেশন-ইন নোটিফিকেশন type='balance_received' (ব্যর্থ হলে শুধু লগ)। client_token ঐচ্ছিক: সফলের পর SETNX ২৪ ঘণ্টা ডুপ্লিকেট সাবমিশন রোধ (ব্যর্থ রিকোয়েস্টে token লেখা হয় না, রিট্রাই করা যায়)।

---

### 8. স্টোর ম্যানেজার ওয়ার্কবেঞ্চ ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/store-manager/overview` | আজকের ওভারভিউ (আজকের অর্ডার সংখ্যা/আজকের আয়/চলমান/টেকনিশিয়ান সংখ্যা/ভেরিফাই সংখ্যা) |
| GET | `/api/store-manager/orders` | স্টোর অর্ডার তালিকা (?status=&page=&limit=) |
| GET | `/api/store-manager/technicians` | টেকনিশিয়ান তালিকা (আজকের শিডিউল সহ) |
| GET | `/api/store-manager/revenue` | সাম্প্রতিক ৭ দিনের আয় অ্যাগ্রিগেশন |

**store_id আইসোলেশন**: requireStoreId() বর্তমান ইউজারকে স্টোর বাঁধতে বাধ্য করে (erik_user.store_id), স্টোর না থাকলে ৪০৩; সব কোয়েরি store_id দিয়ে ফিল্টার।

---

### 9. গ্রোথ লেভেল ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন, রাউন্ড ২০)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/growth` | বর্তমান গ্রোথ ওভারভিউ (balance/লেভেল/পরের ধাপের পার্থক্য/লেভেল নাম) |
| GET | `/api/growth/records` | গ্রোথ ভ্যালু লেনদেন পেজিনেশন (?page=&limit=) |
| GET | `/api/growth/levels` | ধাপ তালিকা (পাবলিক, লগইন লাগে না) |

**গ্রোথ ভ্যালু জমা**: চেক-ইন +১০; রিভিউ জমা +২০ (ফলো-আপ জমা হয় না); কনজিউম floor(paid) প্রতি ১ ইউয়ানে ১ পয়েন্ট (পেমেন্ট কলব্যাকে স্টেট রি-ভেরিফাই আইডেমপোটেন্সি, ডুপ্লিকেট কলব্যাকে ডাবল জমা হয় না)।

### 10. ইনভয়েস ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন, রাউন্ড ২০)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| POST | `/api/invoices` | ইনভয়েস আবেদন (order_id hashid/order_type: service=服务/points_exchange=积分兑换/order_type ডিফল্ট service; পরিমাণ ও টাইটেল সার্ভার সাইড থেকে আনা হয়, টেম্পার করা যায় না) |
| GET | `/api/invoices` | ইনভয়েস তালিকা (?status=&page=) |
| GET | `/api/invoices/{id}` | ইনভয়েস ডিটেইল (শুধু নিজের) |

**ডুপ্লিকেট রোধ**: uk_order_type(order_id, order_type) ইউনিক কী, একই অর্ডারে একই টাইপ পুনরায় আবেদন ৪২২ (MySQL 1062 ক্যাচ ব্যাকআপ সহ)।

### 11. কাস্টমার সার্ভিস টিকিট ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন, রাউন্ড ২০)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| POST | `/api/tickets` | টিকিট জমা (title/content বাধ্যতামূলক) |
| GET | `/api/tickets` | টিকিট তালিকা (?status=open/closed&page=) |
| GET | `/api/tickets/{id}` | টিকিট ডিটেইল (শুধু নিজের, অন্যের ৪০৪) |
| POST | `/api/tickets/{id}/close` | টিকিট বন্ধ (শুধু নিজের/শুধু open; ঐচ্ছিক rating ১-৫ সন্তুষ্টি স্কোর, সীমার বাইরে/অ-পূর্ণসংখ্যা ৪২২, না দিলে NULL) |

### 12. অ্যাপয়েন্টমেন্ট ক্যালেন্ডার ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন, রাউন্ড ২০)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/calendar/technician/{id}` | মাস ভিউ (?month=YYYY-MM): শিডিউল time_slots ঘণ্টা স্লটে বিস্তার + বুক করা বাদ |
| GET | `/api/calendar/technician/{id}/day` | দিন ভিউ (?date=YYYY-MM-DD): সেদিনের বুকযোগ্য/বুক করা/বুক করা যাবে না স্লট বিবরণ |

### 13. ইনভয়েস টাইটেল ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন, রাউন্ড ২১)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| POST | `/api/invoice-titles` | টাইটেল সেভ (title_type: personal/company; company হলে tax_no বাধ্যতামূলক; একই ইউজারের একই টাইটেল ডুপ্লিকেট ৪২২; প্রথমটি অটো ডিফল্ট) |
| GET | `/api/invoice-titles` | টাইটেল তালিকা (ডিফল্ট প্রথমে) |
| PUT | `/api/invoice-titles/{id}` | টাইটেল এডিট (শুধু নিজের) |
| DELETE | `/api/invoice-titles/{id}` | টাইটেল ডিলিট (শুধু নিজের; ডিফল্ট ডিলিট করলে অটো সবচেয়ে পুরনোটি ডিফল্ট) |
| POST | `/api/invoice-titles/{id}/default` | ডিফল্ট সেট (ট্রানজেকশনে একই ইউজারের অন্য সারি শূন্য) |

**আবেদন লিংকেজ**: POST /api/invoices ঐচ্ছিক title_id সমর্থন করে — টাইটেল পার্স করে অটো invoice_title/tax_no/title_type পূরণ, title_id না দিলে আগের ম্যানুয়াল পথ।

### 14. ব্রাউজ হিস্ট্রি ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন, রাউন্ড ২১)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/browse-history` | সাম্প্রতিক দেখা সার্ভিস (সার্ভিস নাম/কভার/দাম/মূল দাম join, viewed_at উল্টো ক্রমে, per_page ডিফল্ট ১৫ সর্বোচ্চ ৫০) |
| DELETE | `/api/browse-history/{item_id}` | একটি ডিলিট (শুধু নিজের, অবৈধ/অন্যের ৪০৪) |
| DELETE | `/api/browse-history` | হিস্ট্রি ক্লিয়ার (শুধু নিজের) |

**রেকর্ড টাইমিং**: সার্ভিস ডিটেইল ইন্টারফেস অ্যাক্সেস সফল হলে অটো রেকর্ড (লগইন না থাকলে স্কিপ; বারবার দেখলে শুধু viewed_at রিফ্রেশ, ডুপ্লিকেট ইনসার্ট নয়)।

### 15. ফুল-রিডাকশন অ্যাক্টিভিটি ইন্টারফেস (রাউন্ড ২২)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/full-reduction-activities` | চলমান ফুল-রিডাকশন অ্যাক্টিভিটি তালিকা (status=1 এবং সময় বৈধ পরিসরে, কাটতি পরিমাণ উল্টো ক্রমে; পাবলিক ইন্টারফেস) |

**অর্ডার স্ট্যাকিং নিয়ম**: ফুল-রিডাকশন শুধু স্ট্যান্ডার্ড অর্ডারে কার্যকর (গ্রুপ বাই/সেকিল স্কিপ), কুপন/টাইম কার্ড কাটার পরের পরিশোধযোগ্য পরিমাণ দিয়ে থ্রেশহোল্ড (threshold) বিচার, স্ট্যাকিং ক্রম **কুপন/টাইম কার্ড → ফুল-রিডাকশন → লেভেল ডিসকাউন্ট**; সর্বোচ্চ কাটতি পরিমাণের অ্যাক্টিভিটি নেয়া হয়; কাটতি discount_amount-এ যোগ, রিমার্কে「满减：满X减Y」অ্যাপেন্ড; কাটার পরের প্রকৃত পরিমাণের নিম্নসীমা ০.০১ ইউয়ান।

### 16. আমার অ্যাপয়েন্টমেন্ট ICS এক্সপোর্ট (JWT অথেনটিকেশন প্রয়োজন, রাউন্ড ২২)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/order/ics` | ৯০ দিনের মধ্যে বৈধ অর্ডার (pending/paid/confirmed/serving) iCal (RFC5545) হিসেবে এক্সপোর্ট |

**আউটপুট**: `Content-Type: text/calendar; charset=utf-8` + `Content-Disposition: attachment; filename="my-appointments.ics"`। VEVENT: UID=অর্ডার ID, TZID=Asia/Shanghai, সামারি「预约：服务名」(না থাকলে「预约」ডিগ্রেড), বিবরণ (টেকনিশিয়ান/স্টোর/ঠিকানা, না থাকলে স্কিপ), LOCATION স্টোর নাম; টেক্সট RFC5545 অনুযায়ী এস্কেপ (\, \; \\ \n) + ৭৫ বাইট লাইন ফোল্ডিং। অর্ডার না থাকলে বৈধ খালি ক্যালেন্ডার; শুধু নিজের অর্ডার এক্সপোর্ট।

### 17. টেকনিশিয়ান অ্যাটেন্ডেন্স ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন, রাউন্ড ২২)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| POST | `/api/technician/attendance/check-in` | কাজে ঢোকার চেক-ইন (সেদিন ডুপ্লিকেট ৪২২, ইউনিক ইনডেক্স কনকারেন্সি ব্যাকআপ; >১০:০০ হলে লেট চিহ্নিত) |
| POST | `/api/technician/attendance/check-out` | কাজ শেষের চেক-আউট (চেক-ইন না/চেক-আউট হয়েছে ৪২২, রো লক কনকারেন্সি) |
| GET | `/api/technician/attendance` | চলতি মাসের অ্যাটেন্ডেন্স তালিকা + উপস্থিত দিন/মোট ঘণ্টা/গড় ঘণ্টা সামারি (?month=YYYY-MM, অবৈধ ৪২২) |

### 18. প্রাইভেসি কমপ্লায়েন্স ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন, রাউন্ড ২২)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/privacy/data` | ডেটা এক্সপোর্ট (personal/orders/points/wallet_txns/reviews/addresses/invoices গ্রুপ JSON; সার্ভার লগে শুধু মাস্কড ফোন নম্বর + সংখ্যা) |
| POST | `/api/privacy/close-request` | অ্যাকাউন্ট বন্ধের আবেদন (ব্যালেন্স ০ নয় / অসম্পূর্ণ অর্ডার / চলমান টিকিট ৪২২; close_status=1 + close_requested_at সেট) |
| POST | `/api/privacy/close-cancel` | বন্ধের আবেদন বাতিল (close_status ১→০) |
| POST | `/api/privacy/close-confirm` | বন্ধ কনফার্ম (৭২ ঘণ্টা পূর্ণ হলে; close_status=2 + close_at + phone/nickname user{id} তে অ্যানোনিমাইজ + status=0) |

**লগইন ব্লক**: close_status=2 অ্যাকাউন্টের লগইনে ৪০৩「账号已注销」।

### 19. ইউজার হেলথ প্রোফাইল ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন, রাউন্ড ২৩)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/health-profile` | আমার হেলথ প্রোফাইল কোয়েরি (প্রোফাইল না থাকলে খালি অবজেক্ট) |
| PUT | `/api/health-profile` | তৈরি/আপডেট (upsert, একজনের একটি; allergies/health_notes সর্বোচ্চ ৫০০ অক্ষর, preferred_technician_id অস্তিত্ব ভেরিফাই; শুধু দেওয়া ফিল্ড আপডেট, রেসপন্স hashid এনকোড) |
| DELETE | `/api/health-profile` | আমার প্রোফাইল ডিলিট (শুধু নিজের) |

ফিল্ড: allergies(过敏史)/health_notes(健康备注)/preferred_technician_id(偏好技师, খালি রাখা যায়)।

### 20. ওয়ালেট পেমেন্ট পাসওয়ার্ড ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন, রাউন্ড ২৩)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| POST | `/api/wallet/pay-password/set` | পেমেন্ট পাসওয়ার্ড সেট (৬ অঙ্কের `\d{6}`; সেট করা থাকলে পুরনো পাসওয়ার্ড চাওয়া হয় ৪২২) |
| POST | `/api/wallet/pay-password/verify` | পেমেন্ট পাসওয়ার্ড ভেরিফাই (সঠিক/ভুল বুলিয়ান রিটার্ন, সংরক্ষণ হয় না) |
| POST | `/api/wallet/pay-password/check` | সেট করা আছে কি না কোয়েরি (set: true/false) |

স্টোরেজ: password_hash() হ্যাশ + pay_password_set_at, প্লেইনটেক্সট কখনো সংরক্ষিত হয় না।

### 21. অর্ডার স্ট্যাটাস টাইমলাইন ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন, রাউন্ড ২৩)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/order/{id}/timeline` | অর্ডার স্ট্যাটাস পরিবর্তনের টাইমলাইন (উল্টো ক্রমে; শুধু নিজের, অন্যের অর্ডার ৪০৪ অস্তিত্ব লিক হয় না) |

ইভেন্ট ট্র্যাকিং: সাবমিট/পেমেন্ট (উইচ্যাট কলব্যাক markOrderPaid একক কনজিউম পয়েন্ট)/বাতিল/টেকনিশিয়ান কনফার্ম/রিফান্ড আবেদন/রিফান্ড অনুমোদন/সার্ভিস শুরু/সার্ভিস সম্পন্ন/টাইমআউট অটো বাতিল/ব্যাকএন্ড অপারেশন (operator=admin) — মোট ৮ টাইপ পরিবর্তন।

### 22. পয়েন্ট লাকি হুইল ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন, রাউন্ড ২৩)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/wheel/prizes` | হুইল প্রাইজ তালিকা (weight/stock সেনসিটিভ ফিল্ড লুকানো) |
| POST | `/api/wheel/spin` | একবার ড্র (Redis NX + রো লক কনকারেন্সি রোধ; random_int ওয়েট ড্র; পয়েন্ট→earn লেনদেন এক্সপায়ারি টাইম সহ, ব্যালেন্স→lockForUpdate জমা, কুপন→pending ম্যানুয়াল বিতরণ, কিছু না→lose; client_token আইডেমপোটেন্সি) |
| GET | `/api/wheel/records` | আমার ড্র রেকর্ড (পেজিনেশন) |

### 23. গেস্ট মোড ইন্টারফেস (রাউন্ড ২৪)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/guest/home` | হোমপেজ অ্যাগ্রিগেশন (ক্যারোসেল/নোটিশ/সার্ভিস ক্যাটাগরি/হট সার্ভিস, Redis ক্যাশ svc:guest:home ৩০০s) |
| GET | `/api/guest/services` | সার্ভিস তালিকা (?category_id=hashid&sort=newest\|sales\|price&page/per_page≤50) |
| GET | `/api/guest/services/{id}` | সার্ভিস ডিটেইল (না থাকলে ৪০৪) |
| GET | `/api/guest/stores` | স্টোর তালিকা |
| GET | `/api/guest/technicians` | টেকনিশিয়ান তালিকা (শুধু অনুমোদিত; ?service_id=hashid ফিল্টার; স্কোর উল্টো ক্রমে) |

অথেনটিকেশন ছাড়া (শুধু ApiVersion মিডলওয়্যার) লগইন না করা ব্রাউজিং এন্ট্রি।

### 24. সেকিল ইন্টারফেস (JWT অথেনটিকেশন প্রয়োজন, রাউন্ড ২৪)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/seckill` | সেকিল অ্যাক্টিভিটি তালিকা (status=1 এবং টাইম উইন্ডোর মধ্যে; বিক্রি হওয়া সংখ্যা = erik_order.seckill_id অর্ডার সংখ্যা, অবশিষ্ট স্টক সহ) |
| GET | `/api/seckill/{id}` | অ্যাক্টিভিটি ডিটেইল (state=not_started/ongoing/ended) |
| POST | `/api/seckill/{id}/buy` | সেকিল অর্ডার (client_token আইডেমপোটেন্সি + Redis NX ৩০s কনকারেন্সি রোধ + অ্যাক্টিভিটি ভ্যালিডেশন; স্টক আগে থেকে কাটা হয় না) |

**অর্ডার নিয়ম (২০২৬-০৮-২৬ থেকে)**: স্টক ইউনিফাইডভাবে `/api/order store()` ট্রানজেকশনে রো লক দিয়ে কাটা হয়, buy শুধু এন্ট্রি ভ্যালিডেশন/আইডেমপোটেন্সি; সেকিল দাম = seckill_price (DB অনুযায়ী), কুপন/পয়েন্ট/মেম্বার কার্ড স্ট্যাক হয় না; অর্ডার বাতিলে স্টক ফেরত নয়; সরাসরি `/api/order` এ seckill_id দিলেও স্টক কাটে।

### 25. APP ভার্সন চেক ইন্টারফেস (রাউন্ড ২৪)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/api/app/version?platform=android\|ios` | সর্বশেষ ভার্সন চেক (platform অবৈধ ৪২২; ভার্সন না থাকলে খালি অবজেক্ট; পাবলিক ইন্টারফেস) |

রেসপন্স: id/platform/version_code/version_name/force_update（1=强制）/changelog/download_url।

---

## 二、ম্যানেজমেন্ট ব্যাকএন্ড API (admin/ :8787)

রিকোয়েস্ট হেডার: `Authorization: Bearer <admin_token>`, `API-Version: v1`

### ড্যাশবোর্ড

**`GET /admin/dashboard`** — ড্যাশবোর্ড ডেটা

রেসপন্স: user_count / order_count / technician_count / today_revenue + চার্ট ডেটা (অর্ডার সংখ্যা/পরিমাণ/নতুন ইউজার/অ্যাক্টিভিটি)

### ইউজার ম্যানেজমেন্ট

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/user` | ইউজার তালিকা (?keyword/status/page/per_page) |
| POST | `/admin/user` | নতুন ইউজার |
| GET | `/admin/user/{id}` | ইউজার ডিটেইল |
| PUT | `/admin/user/{id}` | ইউজার এডিট |
| DELETE | `/admin/user/{id}` | ইউজার ডিলিট |
| POST | `/admin/user/batch/destroy` | ব্যাচ ডিলিট |
| POST | `/admin/user/batch/status` | ব্যাচ সক্ষম/নিষ্ক্রিয় |

### মেম্বার কার্ড ম্যানেজমেন্ট

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/member-cards` | কার্ড তালিকা (?keyword/status/page/per_page) |
| GET | `/admin/member-cards/{id}` | কার্ড ডিটেইল |
| POST | `/admin/member-cards` | নতুন কার্ড (services JSON ভ্যালিডেশন) |
| PUT | `/admin/member-cards/{id}` | কার্ড আপডেট/আপ-ডাউন |
| DELETE | `/admin/member-cards/{id}` | কার্ড ডিলিট (ইউজার কার্ড ধরে থাকলে রিজেক্ট) |

পারমিশন ID: 365-369।

### স্টোর ওয়ার্কবেঞ্চ (রাউন্ড ১৫)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/stores/workbench-overview` | স্টোর ওয়ার্কবেঞ্চ ওভারভিউ (?store_id=hashid: আজকের অর্ডার সংখ্যা/আজকের আয়/চলমান/টেকনিশিয়ান সংখ্যা/আজকের ভেরিফাই, ক্যালকুলেশন service এন্ডের সাথে মিলে) |
| GET | `/admin/orders` | অর্ডার তালিকায় নতুন store_id ফিল্টার (hashid ডিকোড) |

পারমিশন ID: 372।

### পয়েন্ট এক্সচেঞ্জ পণ্য (রাউন্ড ১৬)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/points-exchange-goods` | পণ্য তালিকা (?keyword/status/page/per_page) |
| POST | `/admin/points-exchange-goods` | নতুন পণ্য (type=coupon/gift_card/wallet; coupon-এ hashid, wallet/gift_card-এ পরিমাণ ইউয়ান) |
| PUT | `/admin/points-exchange-goods/{id}` | পণ্য আপডেট |
| DELETE | `/admin/points-exchange-goods/{id}` | পণ্য ডিলিট |
| POST | `/admin/points-exchange-goods/{id}/toggle-status` | আপ-ডাউন টগল |
| GET | `/admin/points-exchange-goods/{id}/exchanges` | এক্সচেঞ্জ রেকর্ড তালিকা (ইউজার ফোন নম্বর + result স্ন্যাপশট সহ) |

পারমিশন ID: 373-378।

### কমিশন রেকর্ড (রাউন্ড ১৬)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/referral-rewards` | কমিশন রেকর্ড (?keyword=&page=&limit=, শুধু বিতরণ করা রেকর্ড, রেফারার/রেফারড ব্যক্তির নিকনেম বা ফোন নম্বর ফিল্টার, hashid এনকোড) |

পারমিশন ID: 379।

### টেকনিশিয়ান লেভেল (রাউন্ড ১৭)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/technician-tiers/logs` | লেভেল পরিবর্তনের লগ (টেকনিশিয়ান নাম ও পুরনো/নতুন লেভেল নাম join, hashid এনকোড, পেজিনেশন) |

পারমিশন ID: 380।

**অটো অ্যাসেসমেন্ট**: TierRatingService::evaluate রিয়েল-টাইম হিসাব (erik_order completed অর্ডার সংখ্যা + রিভিউ গড় স্কোর, দশমিক ১ ঘর রাউন্ড) profile.order_count/rating এ ফিরে লেখে, erik_technician_tier_config (min_orders/min_rating) অনুযায়ী উঁচু থেকে নিচু ম্যাচ, ম্যাচ না হলে সর্বনিম্ন লেভেল। শুধু আপগ্রেড, ডাউনগ্রেড নয় (ডাউনগ্রেড কমিশন রেট ও প্রাইস কোএফিসিয়েন্টে প্রভাব ফেলে, ব্যাকএন্ড ম্যানুয়াল ব্যাকআপ; allowDowngrade=true ম্যানুয়াল রি-অ্যাসেসমেন্টের জন্য); আইডেমপোটেন্ট (লেভেল এক হলে শুধু পরিসংখ্যান সিঙ্ক); পরিবর্তন erik_technician_tier_log + স্টেশন-ইন নোটিফিকেশন। ট্রিগার পয়েন্ট: WorkController::complete / ReviewController রিভিউ লেখা / ProfileController প্রোফাইল দেখার লেজি চেক।

### রিভিউ রিপ্লাই ভিউ (রাউন্ড ১৮)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/reviews/{id}/reply` | রিভিউ রিপ্লাই ডিটেইল (decodeId → find → 404 → decorate আউটপুট; রিপ্লাই না হলে reply='', reply/replied_at toArray দিয়ে বেরিয়ে আসে; স্ট্যাটিক রুট resource-এর আগে) |

পারমিশন ID: 381 (slug 'get.admin/reviews/{id}/reply')।

### ইনভয়েস ম্যানেজমেন্ট (রাউন্ড ২০)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/invoices` | ইনভয়েস তালিকা (?status=pending/issued/rejected&page=) |
| POST | `/admin/invoices/{id}/issue` | ইনভয়েস ইস্যু (invoice_no বাধ্যতামূলক, status→issued + issued_at; আইডেমপোটেন্ট: ইস্যু করা হয়েছে ৪২২) |
| POST | `/admin/invoices/{id}/reject` | রিজেক্ট (reject_reason বাধ্যতামূলক, status→rejected; শুধু pending রিজেক্ট করা যায়) |

পারমিশন ID: 382 তালিকা / 383 ইস্যু / 384 রিজেক্ট।

### টিকিট ম্যানেজমেন্ট (রাউন্ড ২০)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/tickets` | টিকিট তালিকা (?status=&page=, স্ট্যাটিক রুট resource-এর আগে shadow এড়াতে) |
| POST | `/admin/tickets/{id}/reply` | টিকিট রিপ্লাই (content বাধ্যতামূলক, reply_content/replied_at লেখা, টিকিট open-এ ফেরত) |
| GET | `/admin/tickets/satisfaction` | সন্তুষ্টি সামারি (রাউন্ড ২১): total/rated_count/unrated_count/average দশমিক ১ ঘর/১-৫ তারা distribution কম হলে ০ পূরণ; স্ট্যাটিক রুট resource-এর আগে |

পারমিশন ID: 385 টিকিট রিপ্লাই / 387 টিকিট তালিকা ভিউ / 388 টিকিট সন্তুষ্টি পরিসংখ্যান।

### রিভিউ ইমেজ অডিট (রাউন্ড ২১)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/review-audit` | ছবিসহ রিভিউ তালিকা (JSON_LENGTH(images)>0, ?status=visible/hidden&page=, ইউজার নিকনেম ও টেকনিশিয়ান নাম join, ID hashid এনকোড) |
| POST | `/admin/review-audit/{id}/hide` | রিভিউ হাইড (শুধু visible হাইড করা যায়, না হলে ৪২২; হাইড করলে ইউজার এন্ডে টেকনিশিয়ান রিভিউ তালিকায় অটো অদৃশ্য) |
| POST | `/admin/review-audit/{id}/restore` | রিভিউ রিস্টোর (শুধু hidden রিস্টোর করা যায়, না হলে ৪২২) |

পারমিশন ID: 389 তালিকা / 390 হাইড / 391 রিস্টোর।

### দ্বিতীয়-স্তর কমিশন রেকর্ড (রাউন্ড ২০)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/referral-level2` | দ্বিতীয়-স্তর কমিশন রেকর্ড (প্রথম-স্তর রেফারার ও দ্বিতীয়-স্তর রেফারারের নিকনেম join, পেজিনেশন) |

পারমিশন ID: 386। বিতরণ নিয়ম: অর্ডার পেমেন্টের পর প্রথম-স্তর রেফারারের রেফারারকে paid×level2_rate (সিস্টেম কনফিগ referral.level2_rate ডিফল্ট 0.02), uk_order_referred আইডেমপোটেন্সি ডুপ্লিকেট রোধ।

### অ্যাটেন্ডেন্স ম্যানেজমেন্ট (রাউন্ড ২২)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/attendance` | অ্যাটেন্ডেন্স রেকর্ড (?date=YYYY-MM&name=技师名&page=; real_name join, ID hashid এনকোড) |
| GET | `/admin/attendance/stats` | টেকনিশিয়ান অনুযায়ী গ্রুপ পরিসংখ্যান (চেক-ইন দিন/মোট ঘণ্টা/গড় ঘণ্টা; ?date=YYYY-MM, অবৈধ ৪২২) |

পারমিশন ID: 392 তালিকা / 393 পরিসংখ্যান।

### ফুল-রিডাকশন অ্যাক্টিভিটি ম্যানেজমেন্ট (রাউন্ড ২২)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/full-reduction-activities` | অ্যাক্টিভিটি তালিকা (পেজিনেশন) |
| POST | `/admin/full-reduction-activities` | নতুন (threshold/reduction/title/status/start_at/end_at) |
| PUT | `/admin/full-reduction-activities/{id}` | এডিট |
| POST | `/admin/full-reduction-activities/{id}/toggle-status` | আপ-ডাউন |
| DELETE | `/admin/full-reduction-activities/{id}` | ডিলিট (confirmPassword সহ) |

পারমিশন ID: 396 তালিকা / 397 নতুন / 398 এডিট / 399 আপ-ডাউন / 400 ডিলিট (একটি পারমিশন রেকর্ড একটি method.path slug-এর সাথে মিলে, তাই ৫ রুটে ৫টি)।

### প্রফিট শেয়ারিং রেকর্ড (রাউন্ড ২২)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/profit-sharing` | প্রফিট শেয়ারিং রেকর্ড (leftJoin অর্ডার নম্বর/টেকনিশিয়ান নিকনেম, ?status&order_no&technician_name&page=, hashid এনকোড) |

পারমিশন ID: 394। সার্ভার সাইড লজিক: erik_system_config group=profit_sharing (enabled/receiver_ratio); নিষ্ক্রিয় হলে disabled ডিগ্রেড শুধু লগ; সক্ষম হলে পেমেন্ট সফলের পর অটো প্রফিট শেয়ারিং রিকোয়েস্ট (পরিমাণ=প্রকৃত পরিশোধ×receiver_ratio ডিফল্ট 0.7, একই অর্ডারে pending/success আইডেমপোটেন্ট স্কিপ); ক্রেডেনশিয়াল না থাকলে HTTP এক্সিকিউট হয় না, রিকোয়েস্ট স্ট্রাকচার লগ করা হয়।

### লাকি হুইল ম্যানেজমেন্ট (রাউন্ড ২৩)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/lucky-wheel` | হুইল প্রাইজ তালিকা (weight/stock সহ, পেজিনেশন) |
| POST | `/admin/lucky-wheel` | নতুন প্রাইজ (নাম/টাইপ points/balance/coupon/none/ওয়েট/স্টক/ছবি) |
| GET/PUT | `/admin/lucky-wheel/{id}` | ডিটেইল / এডিট |
| DELETE | `/admin/lucky-wheel/{id}` | ডিলিট |
| POST | `/admin/lucky-wheel/{id}/toggle-status` | আপ-ডাউন |
| GET | `/admin/lucky-wheel/records` | ড্র রেকর্ড (?status&page=, ইউজার নিকনেম/প্রাইজ নাম সহ) |

পারমিশন ID: 401-406। স্ট্যাটিক রুট `/lucky-wheel/records` ও `/lucky-wheel/{id}/toggle-status` resource-এর আগে রেজিস্টার করা হয় {id} shadow এড়াতে।

### রিটার্ন কাস্টমার রিওয়ার্ড ম্যানেজমেন্ট (রাউন্ড ২৪)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/return-customer/config` | কনফিগ ভিউ (enabled সুইচ / ratio অনুপাত) |
| PUT | `/admin/return-customer/config` | কনফিগ আপডেট (enabled in:0,1; ratio between:0.01,1) |
| GET | `/admin/return-customer/rewards` | রিওয়ার্ড রেকর্ড তালিকা (?keyword টেকনিশিয়ান নাম/অর্ডার নম্বর/ইউজার নিকনেম, type=return_customer পেজিনেশন) |

পারমিশন ID: 412-414। রিওয়ার্ড নিয়ম: ইউজার একই টেকনিশিয়ানে ৩০ দিনের মধ্যে ২য় বার খরচ (অর্ডার সম্পন্ন) হলে বোনাস = প্রকৃত পরিশোধ × ratio (ডিফল্ট 0.05), erik_technician_earnings-এ (type=return_customer, status=pending) কমিশন সেটেলমেন্ট চেইনের সাথে ইউনিফাইড সেটেল; একই অর্ডার আইডেমপোটেন্ট ডুপ্লিকেট বিতরণ হয় না।

### সেকিল অ্যাক্টিভিটি ম্যানেজমেন্ট (রাউন্ড ২৪)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/seckill` | অ্যাক্টিভিটি তালিকা (পেজিনেশন) |
| POST | `/admin/seckill` | নতুন অ্যাক্টিভিটি (name/service_id/seckill_price/original_price/stock/start_at/end_at) |
| GET | `/admin/seckill/{id}` | অ্যাক্টিভিটি ডিটেইল |
| PUT | `/admin/seckill/{id}` | এডিট |
| DELETE | `/admin/seckill/{id}` | ডিলিট |
| POST | `/admin/seckill/{id}/toggle-status` | আপ-ডাউন |
| GET | `/admin/seckill/{id}/orders` | সেকিল অর্ডার তালিকা |

পারমিশন ID: 407-411、420। বিক্রি হওয়া সংখ্যা = erik_order.seckill_id অর্ডার সংখ্যা; স্টক রো লক কাটা, সোল্ড আউট ব্লক।

### APP ভার্সন ম্যানেজমেন্ট (রাউন্ড ২৪)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/versions` | ভার্সন তালিকা |
| POST | `/admin/versions` | নতুন ভার্সন (platform/version_code/version_name/force_update/changelog/download_url/status) |
| PUT | `/admin/versions/{id}` | এডিট |
| DELETE | `/admin/versions/{id}` | ডিলিট |

পারমিশন ID: 416-419। আপডেট চেক ইন্টারফেস /api/app/version status=1-এর মধ্যে সর্বশেষ (updated_at/id সর্বোচ্চ) ভার্সন নেয়।

### শিডিউল এক্সপোর্ট (রাউন্ড ২৪)

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/technician-schedule/export` | শিডিউল CSV এক্সপোর্ট (UTF-8 BOM, Excel সরাসরি খোলে; start_date/end_date বাধ্যতামূলক এবং স্প্যান ≤৩১ দিন; technician_id ঐচ্ছিক hashid) |

পারমিশন ID: 415। কলাম: টেকনিশিয়ান ID/টেকনিশিয়ান নাম/তারিখ/সময় স্লট বিবরণ (time_slots JSON পার্স করে "09:00-12:00, 14:00-18:00")।

### রোল পারমিশন

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET/POST/PUT/DELETE | `/admin/role` | রোল CRUD |
| GET/POST/PUT/DELETE | `/admin/permission` | পারমিশন CRUD (ট্রি স্ট্রাকচার) |

### সিস্টেম কনফিগ

| মেথড | পাথ | বিবরণ |
|------|------|------|
| GET | `/admin/config` | কনফিগ তালিকা |
| POST | `/admin/config` | নতুন কনফিগ (group/key/value/type/description) |
| PUT | `/admin/config/{id}` | কনফিগ এডিট |
| DELETE | `/admin/config/{id}` | কনফিগ ডিলিট |

### অপারেশন লগ

**`GET /admin/log`** — লগ কোয়েরি

প্যারামিটার: `?user_id/action/source/start_date/end_date/page`

`souce` ফিল্ড: web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### এক্সপোর্ট

| মেথড | পাথ | বিবরণ |
|------|------|------|
| POST | `/admin/export/excel` | Excel এক্সপোর্ট (type: users/technicians/orders/finance)। সেনসিটিভ ফিল্ড অটো মাস্ক |
| POST | `/admin/export/pdf` | PDF প্যানেল এক্সপোর্ট (type: dashboard) |

### ফাইল আপলোড

**`POST /admin/upload`** — ফাইল আপলোড (multipart/form-data)

### পার্সোনাল সেন্টার

| মেথড | পাথ | বিবরণ |
|------|------|------|
| PUT | `/admin/profile` | ব্যক্তিগত তথ্য পরিবর্তন |
| PUT | `/admin/profile/password` | পাসওয়ার্ড পরিবর্তন |
| POST | `/admin/profile/logout` | লগআউট |

### ইমপোর্ট

**`POST /admin/import/users`** — ইউজার ব্যাচ ইমপোর্ট (Excel)

### মনিটরিং

| মেথড | পাথ | অথেনটিকেশন | বিবরণ |
|------|------|------|------|
| GET | `/health` | নেই | হেলথ চেক |
| GET | `/metrics` | নেই | Prometheus মেট্রিক্স |
| GET | `/.well-known/security.txt` | নেই | সিকিউরিটি কন্টাক্ট (RFC 9116) |
| GET | `/api/docs` | নেই | API ডকুমেন্টেশন |

---

## 三、সাধারণ বিবরণ

### এরর কোড

| code | বিবরণ |
|------|------|
| 0 | সফল |
| 401 | লগইন না বা Token এক্সপায়ার্ড |
| 403 | অনুমতি নেই |
| 404 | রিসোর্স নেই |
| 422 | প্যারামিটার ভ্যালিডেশন ব্যর্থ |
| 429 | রিকোয়েস্ট খুব ঘন ঘন |

### ID এনকোডিং

- সব API রেসপন্সের `id` ও `*_id` ফিল্ড hashids দিয়ে এনকোড করা হয়
- রিকোয়েস্টে পাঠানো `id` প্যারামিটারও hashids এনকোড ফরম্যাটে হওয়া উচিত
- ফ্রন্টএন্ড সরাসরি এনকোডেড স্ট্রিং ব্যবহার করে, ম্যানুয়াল ডিকোড লাগে না

### ফোন নম্বর মাস্কিং

রেসপন্সে ফোন নম্বর ফরম্যাট: `138****8000`। Excel এক্সপোর্টেও একইভাবে।

### ডেটা এনক্রিপশন

- API স্তর: রেসপন্সের সেনসিটিভ ফিল্ড `erikwang2013/encryption` দিয়ে এনক্রিপ্ট
- DB স্তর: ফোন নম্বর/আইডি কার্ড/উইচ্যাট ID ইত্যাদি `erikwang2013/encryptable` দিয়ে অটো এনক্রিপ্ট/ডিক্রিপ্ট

### এনভায়রনমেন্ট ভেরিয়েবল কনফিগ

| ভেরিয়েবল | বিবরণ |
|------|------|
| WECHAT_SUBSCRIBE_TEMPLATE_ID | অ্যাপয়েন্টমেন্ট রিমাইন্ডার সাবস্ক্রিপশন মেসেজ টেমপ্লেট ID |
| WECHAT_SUBSCRIBE_TEMPLATE_PAID | পেমেন্ট সফল সাবস্ক্রিপশন মেসেজ টেমপ্লেট ID |
| WECHAT_SUBSCRIBE_TEMPLATE_REFUND | রিফান্ড সাবস্ক্রিপশন মেসেজ টেমপ্লেট ID |
| WECHAT_SUBSCRIBE_TEMPLATE_VERIFIED | ভেরিফিকেশন সাবস্ক্রিপশন মেসেজ টেমপ্লেট ID |
| WECHAT_SUBSCRIBE_TEMPLATE_REMINDER | সার্ভিস শুরু হওয়ার আগে রিমাইন্ডার সাবস্ক্রিপশন মেসেজ টেমপ্লেট ID (রাউন্ড ১৮) |
| WECHAT_SUBSCRIBE_TEMPLATE_EXPIRY | মেম্বার কার্ড/কুপন এক্সপায়ারি রিমাইন্ডার সাবস্ক্রিপশন মেসেজ টেমপ্লেট ID (রাউন্ড ১৮) |

সাবস্ক্রিপশন মেসেজ টেমপ্লেট কনফিগ না থাকলে অটো স্টেশন-ইন নোটিফিকেশনে ডিগ্রেড।

**সাবস্ক্রিপশন মেসেজ সিন**: SCENE_PAY(支付成功) / SCENE_REFUND(退款到账) / SCENE_VERIFIED(核销成功) / SCENE_RESCHEDULE(改期成功) / SCENE_REMINDER(服务开始前提醒，第18轮) / SCENE_EXPIRY(到期提醒，第18轮)। পুশ সফল হলেই push_sent_at লেখা হয়, ব্যর্থ হলে পরের রাউন্ডে রিট্রাই।

**রিচার্জ সফল নোটিফিকেশন (রাউন্ড ১৮)**: উইচ্যাট রিচার্জ কলব্যাক (R প্রিফিক্স অর্ডার নম্বর) ট্রানজেকশনে স্টেশন-ইন নোটিফিকেশন type='wallet_recharge'「您已成功充值 ¥X.XX」; কলব্যাক আইডেমপোটেন্সি রিইউজ (শুধু প্রথমবার pending→paid ট্রিগার), স্ট্যাটাস পরিবর্তনের সাথে একই ট্রানজেকশনে অ্যাটমিক কমিট, লেখা ব্যর্থ হলে মূল ফ্লো ব্লক হয় না।
