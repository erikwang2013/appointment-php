# অ্যাপয়েন্টমেন্ট সার্ভিস সিস্টেম ডিজাইন স্পেসিফিকেশন
> **Languages**: [中文](../../superpowers/specs/2026-05-26-appointment-system-design.md) · [English](../../en/specs/2026-05-26-appointment-system-design.md) · [한국어](../../ko/specs/2026-05-26-appointment-system-design.md) · [Русский](../../ru/specs/2026-05-26-appointment-system-design.md) · [Deutsch](../../de/specs/2026-05-26-appointment-system-design.md) · [Français](../../fr/specs/2026-05-26-appointment-system-design.md) · [Español](../../es/specs/2026-05-26-appointment-system-design.md) · [Português](../../pt/specs/2026-05-26-appointment-system-design.md) · [हिन्दी](../../hi/specs/2026-05-26-appointment-system-design.md) · [العربية](../../ar/specs/2026-05-26-appointment-system-design.md) · [Bahasa Indonesia](../../id/specs/2026-05-26-appointment-system-design.md) · [日本語](../../ja/specs/2026-05-26-appointment-system-design.md)

> বাংলা অনুবাদ · মূল: [中文](../../superpowers/specs/2026-05-26-appointment-system-design.md)

## ওভারভিউ

তিন-প্রান্তের অ্যাপয়েন্টমেন্ট সার্ভিস সিস্টেম: ব্যবহারকারী-প্রান্ত (WeChat মিনি-প্রোগ্রাম + Flutter APP) + টেকনিশিয়ান ওয়ার্কবেঞ্চ (একই APP-এর ভেতরে আইডেন্টিটি সুইচ) + ম্যানেজমেন্ট ব্যাকএন্ড (PC Web)।

## আর্কিটেকচার সিদ্ধান্ত

| সিদ্ধান্ত | সমাধান |
|------|------|
| ব্যাকএন্ড আর্কিটেকচার | `admin/` (ম্যানেজমেন্ট ব্যাকএন্ড API) + `service/` (বিজনেস API), দুটি সার্ভিস শেয়ারড MySQL/Redis |
| ব্যবহারকারী-প্রান্ত মিনি-প্রোগ্রাম | নেটিভ WeChat মিনি-প্রোগ্রাম `apps/wechat/` |
| ব্যবহারকারী-প্রান্ত APP | Flutter `apps/flutter/` (iOS + Android) |
| ব্যবহারকারী আইডেন্টিটি | ইউনিফাইড অ্যাকাউন্ট, কাস্টমার/টেকনিশিয়ান আইডেন্টিটি পরিবর্তনযোগ্য |
| মিনি-প্রোগ্রাম ও APP-এর সম্পর্ক | ফাংশন সম্পূর্ণ একই, শুধু প্ল্যাটফর্ম ভিন্ন |
| ম্যানেজমেন্ট ব্যাকএন্ড ফ্রন্টএন্ড | বিদ্যমান Flutter Web (`admin/apps/flutter/`) এক্সটেনশন |
| ম্যানেজমেন্ট ব্যাকএন্ড ব্যাকএন্ড | বিদ্যমান webman v2 (`admin/`) এক্সটেনশন বিজনেস মডিউল |
| থার্ড-পার্টি সার্ভিস | WeChat লগইন/পেমেন্ট/SMS/ম্যাপ — সংযোগ সমাধান সংরক্ষিত |

## সিস্টেম আর্কিটেকচার ডায়াগ্রাম

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

## ডেটাবেস কোর টেবিল

সব টেবিল `erik_` প্রিফিক্স ব্যবহার করে, BIGINT নন-অটো-ইনক্রিমেন্ট প্রাইমারি কি (Snowflake জেনারেটেড)। সংবেদনশীল ফিল্ড encryptable trait-এর মাধ্যমে এনক্রিপশন/ডিক্রিপশন হয়।

### ইউজার ও আইডেন্টিটি ডোমেইন

| টেবিল নাম | বিবরণ | কোর ফিল্ড |
|------|------|----------|
| `erik_user` | ইউনিফাইড ইউজার টেবিল | phone, password, wx_openid, wx_unionid, avatar, nickname, user_type(customer/technician), status। technician ইউজারেরও কাস্টমার ফাংশন আছে, যেকোনো সময় সক্রিয় আইডেন্টিটি পরিবর্তন করা যায় |
| `erik_user_address` | ইউজার ঠিকানা | user_id, contact_name, contact_phone, province, city, district, detail, is_default |
| `erik_technician_profile` | টেকনিশিয়ান প্রোফাইল | user_id, real_name, gender, id_card, id_card_front, id_card_back, avatar, rating, order_count, status(pending/approved/rejected), intro |
| `erik_technician_schedule` | টেকনিশিয়ান শিডিউল | technician_id, date, time_slots(JSON), status |
| `erik_technician_service` | টেকনিশিয়ানের সার্ভিস আইটেম | technician_id, service_id |
| `erik_technician_earnings` | টেকনিশিয়ান আয় লেনদেন | technician_id, order_id, type(commission/bonus/penalty), amount, status |
| `erik_technician_withdrawal` | টেকনিশিয়ান উত্তোলন রেকর্ড | technician_id, amount, actual_amount, commission_fee, account_info, status, reviewed_at |
| `erik_technician_attendance` | টেকনিশিয়ান উপস্থিতি | technician_id, date, check_in_at, check_out_at, clean_photo |
| `erik_technician_member_note` | মেম্বার প্রোফাইল | technician_id, user_id, content, written_at |

### সার্ভিস ও প্রোডাক্ট ডোমেইন

| টেবিল নাম | বিবরণ | কোর ফিল্ড |
|------|------|----------|
| `erik_service_category` | সার্ভিস ক্যাটাগরি | name, icon, parent_id, sort, status |
| `erik_service` | সার্ভিস আইটেম | category_id, name, description, cover_image, images(JSON), price, duration, sales_volume, specs(JSON), status |
| `erik_product` | প্রোডাক্ট | category_id, name, cover_image, price, stock, sales_volume, type, status |
| `erik_store` | শাখা/স্টোর | name, address, lat, lng, phone, business_hours(JSON), images, status |

### অর্ডার ডোমেইন

| টেবিল নাম | বিবরণ | কোর ফিল্ড |
|------|------|----------|
| `erik_order` | অর্ডার মাস্টার টেবিল | order_no, user_id, technician_id, store_id, total_amount, discount_amount, paid_amount, status, service_time, cancel_reason, remark |
| `erik_order_item` | অর্ডার ডিটেইল | order_id, service_id, product_id, type, name, price, quantity, spec_info |
| `erik_order_payment` | পেমেন্ট রেকর্ড | order_id, pay_type(wechat), transaction_id, amount, status, paid_at |
| `erik_order_refund` | রিফান্ড রেকর্ড | order_id, payment_id, refund_no, amount, ratio, reason, status |
| `erik_order_review` | সার্ভিস রিভিউ | order_id, user_id, technician_id, rating, content, images |
| `erik_order_verification` | ভেরিফিকেশন রেকর্ড | order_id, code, verified_at, verified_by, location |

### মার্কেটিং ডোমেইন

| টেবিল নাম | বিবরণ | কোর ফিল্ড |
|------|------|----------|
| `erik_coupon` | কুপন সংজ্ঞা | name, type, amount, min_amount, total_qty, remain_qty, start_at, end_at, status |
| `erik_user_coupon` | ইউজার কুপন | user_id, coupon_id, status(available/used/expired), used_at |
| `erik_member_card` | মেম্বার কার্ড সংজ্ঞা | name, type(month/vip/times), price, duration_days, total_times, services(JSON) |
| `erik_user_member_card` | ইউজার মেম্বার কার্ড | user_id, card_id, start_at, end_at, total_times, used_times, status |
| `erik_member_card_usage` | টাইমস-কার্ড ব্যবহার রেকর্ড | user_card_id, order_id, service_id, used_at |
| `erik_user_points` | পয়েন্ট লেনদেন | user_id, type(earn/use), points, source, order_id |
| `erik_gift_card` | গিফট কার্ড | code, type, amount_or_gift, status, used_by, used_at |
| `erik_user_referral` | ইউজার রেফারেল | referrer_id, referred_user_id, reward_type, reward_amount, registered_at, first_order_at |

### কনটেন্ট ও নোটিফিকেশন ডোমেইন

| টেবিল নাম | বিবরণ | কোর ফিল্ড |
|------|------|----------|
| `erik_banner` | ক্যারোসেল ব্যানার | position, image, jump_type(url/detail/none), jump_value, sort, status |
| `erik_announcement` | ঘোষণা | content, status, published_at |
| `erik_platform_agreement` | প্ল্যাটফর্ম চুক্তি | type(user_agreement/privacy_policy/service_agreement), title, content, version |
| `erik_faq` | সাধারণ প্রশ্নাবলী | title, content, sort |
| `erik_feedback` | মতামত/ফিডব্যাক | user_id, content, images, handler_reply, status(pending/handled) |
| `erik_moment` | মোমেন্ট/ফিড পোস্ট | content, images, published_at |
| `erik_notification` | মেসেজ নোটিফিকেশন | user_id, type(order/system), title, content, is_read, created_at |

### ফাইন্যান্স ডোমেইন (admin-সাইড)

| টেবিল নাম | বিবরণ | কোর ফিল্ড |
|------|------|----------|
| `erik_finance_transaction` | আয়-ব্যয় লেনদেন | user_id, order_id, type, direction(income/expense), amount, actual_amount, commission, status |
| `erik_technician_commission_config` | কমিশন কনফিগ | technician_id, commission_rate, settlement_cycle |
| `erik_withdrawal_account` | উত্তোলন অ্যাকাউন্ট | user_id, type(wechat), account_name, account_no |
| `erik_withdrawal_config` | উত্তোলন সীমা কনফিগ | min_amount, reserve_amount, round_to_hundred |

## Service API মডিউল

### পাবলিক API (কোনো অথেনটিকেশন নেই)
- **AuthController** — লগইন/রেজিস্ট্রেশন/পাসওয়ার্ড ভুলে গেলে/গেস্ট মোড/আইডেন্টিটি সুইচ
- **CaptchaController** — SMS ভেরিফিকেশন কোড
- **WechatController** — WeChat অথরাইজেশন/লগইন/পেমেন্ট কলব্যাক
- **CommonController** — চুক্তি টেক্সট/আমাদের সম্পর্কে/ভার্সন তথ্য

### ইউজার মডিউল `user/` (অথেনটিকেশন প্রয়োজন)
- **ProfileController** — ব্যক্তিগত তথ্য/পাসওয়ার্ড পরিবর্তন/ফোন পরিবর্তন/অ্যাকাউন্ট বাতিল
- **AddressController** — ডেলিভারি ঠিকানা CRUD
- **FavoriteController** — ফেভারিট
- **FeedbackController** — মতামত/ফিডব্যাক
- **ReferralController** — প্রোমোশন/রেফারেল ইউজার তালিকা

### টেকনিশিয়ান মডিউল `technician/` (টেকনিশিয়ান আইডেন্টিটি + TechnicianAuth মিডলওয়্যার প্রয়োজন)
- **ProfileController** — টেকনিশিয়ান প্রোফাইল/এনরোলমেন্ট আবেদন
- **ScheduleController** — শিডিউল সেটিং
- **OrderController** — বুকড-অনভেরিফাইড/সম্পন্ন/স্ক্যান-ভেরিফিকেশন
- **MemberController** — আমার মেম্বার/মেম্বার প্রোফাইল
- **EarningsController** — আয়/পথে থাকা তহবিল
- **WithdrawalController** — উত্তোলন
- **AttendanceController** — উপস্থিতি/পরিচ্ছন্নতার ছবি

### সার্ভিস মডিউল `service/`
- **CategoryController** — সার্ভিস ক্যাটাগরি
- **ItemController** — সার্ভিস/প্রোডাক্ট তালিকা ও ডিটেইল
- **SearchController** — সার্চ
- **StoreController** — শাখা তালিকা/ডিটেইল

### অর্ডার মডিউল `order/` (অথেনটিকেশন প্রয়োজন)
- **CartController** — কার্ট
- **OrderController** — অর্ডার তৈরি/তালিকা/ডিটেইল/বাতিল
- **PaymentController** — পেমেন্ট/রিফান্ড
- **VerificationController** — QR কোড ভেরিফিকেশন
- **ReviewController** — রিভিউ

### মার্কেটিং মডিউল `marketing/` (অথেনটিকেশন প্রয়োজন)
- **CouponController** — কুপন তালিকা/গ্রহণ/ব্যবহার
- **MemberCardController** — মেম্বার কার্ড/টাইমস কার্ড
- **PointsController** — পয়েন্ট
- **GiftCardController** — গিফট কার্ড

### কনটেন্ট মডিউল `content/`
- **BannerController** — ক্যারোসেল ব্যানার
- **AnnouncementController** — ঘোষণা
- **NotificationController** — মেসেজ নোটিফিকেশন

### LBS মডিউল
- **LocationController** — লোকেশন/সিটি সুইচ/কাছের শাখা

### সাধারণ ক্ষমতা `common/`
- SnowflakeService — ID জেনারেশন
- HashidsService — ID এনক্রিপশন/ডিক্রিপশন
- EncryptionService — সংবেদনশীল ডেটা এনক্রিপশন/ডিক্রিপশন
- WechatPayService — WeChat পেমেন্ট (সংরক্ষিত)
- WechatAuthService — WeChat লগইন (সংরক্ষিত)
- SmsService — SMS সার্ভিস (সংরক্ষিত)
- MapService — ম্যাপ সার্ভিস (সংরক্ষিত)

### মিডলওয়্যার
- Auth — JWT অথেনটিকেশন (admin-এর সাথে শেয়ারড erikwang2013/jwt-webman প্যাকেজ)
- TechnicianAuth — টেকনিশিয়ান আইডেন্টিটি যাচাই
- RateLimit — রেট লিমিট (admin-এর সাথে শেয়ারড)

## Admin ম্যানেজমেন্ট ব্যাকএন্ড এক্সটেনশন

বিদ্যমান ফ্রেমওয়ার্কের ভিত্তিতে নতুন কন্ট্রোলার যোগ করা হয়েছে:

### টেকনিশিয়ান ম্যানেজমেন্ট
- **TechnicianController** — টেকনিশিয়ান তালিকা/সার্চ/এক্সপোর্ট/অডিট/শিডিউল ম্যানেজমেন্ট/টেকনিশিয়ান সার্ভিস আইটেম সেটিং/কোর্স লার্নিং প্রগ্রেস

### ইউজার ম্যানেজমেন্ট এক্সটেনশন
- **MemberController** — মেম্বার তালিকা/লেভেল সেটিং/কনজাম্পশন পরিসংখ্যান

### শাখা ম্যানেজমেন্ট
- **StoreController** — শাখা CRUD/সক্রিয়-নিষ্ক্রিয়

### সার্ভিস ম্যানেজমেন্ট
- **ServiceController** — সার্ভিস তালিকা/CRUD/কার্ড আইটেম ডিজাইন
- **ServiceCategoryController** — ক্যাটাগরি ম্যানেজমেন্ট
- **ProductController** — প্রোডাক্ট তালিকা/CRUD

### মল ম্যানেজমেন্ট
- **MallOrderController** — মল অর্ডার/ডেলিভারি/আফটার-সেল/রিভিউ
- **SalesStatsController** — বিক্রয় পরিসংখ্যান

### অর্ডার ম্যানেজমেন্ট
- **AppointmentOrderController** — অপেক্ষমাণ অর্ডার/বাতিল/সম্পন্ন নিশ্চিতকরণ

### কুপন অ্যাক্টিভিটি
- **CouponController** — কুপন CRUD/বিতরণ

### ফাইন্যান্স ম্যানেজমেন্ট
- **FinanceController** — অর্ডার সেটেলমেন্ট/আয়-ব্যয় লেনদেন
- **WithdrawalController** — টেকনিশিয়ান উত্তোলন অডিট/সম্পন্ন
- **CommissionController** — কমিশন সেটিং/পুরস্কার-জরিমানা/ব্যালেন্স কোয়েরি
- **WithdrawalAccountController** — উত্তোলন অ্যাকাউন্ট ম্যানেজমেন্ট
- **WithdrawalConfigController** — উত্তোলন সীমা কনফিগ

### কনটেন্ট ম্যানেজমেন্ট
- **BannerController** — ক্যারোসেল ব্যানার CRUD
- **AnnouncementController** — ঘোষণা CRUD
- **FaqController** — FAQ CRUD
- **FeedbackController** — মতামত প্রসেসিং
- **MomentController** — মোমেন্ট অডিট
- **AgreementController** — চুক্তি সম্পাদনা (ইউজার অ্যাগ্রিমেন্ট/প্রাইভেসি অ্যাগ্রিমেন্ট/সার্ভিস অ্যাগ্রিমেন্ট)
- **AboutController** — আমাদের সম্পর্কে সেটিং

### সেটিংস
- **SystemMessageController** — সিস্টেম মেসেজ সেটিং
- **AdminUserController** — সাব-অ্যাকাউন্ট ম্যানেজমেন্ট (বিদ্যমান RBAC-এর ভিত্তিতে)

### Dashboard এক্সটেনশন
- রিয়েল-টাইম পরিসংখ্যান কার্ড: ইউজার সংখ্যা/মোট অর্ডার/টেকনিশিয়ান সংখ্যা/সার্ভিস অর্ডার সংখ্যা
- লাইন চার্ট: অর্ডার ভলিউম/অ্যামাউন্ট/দৈনিক নতুন ইউজার/অ্যাক্টিভিটি
- দ্রুত নেভিগেশন: অপেক্ষমাণ মডিউল বাটন
- সাইট-মেসেজ: নতুন অর্ডার নোটিফিকেশন/রিফান্ড নোটিফিকেশন

## ব্যবহারকারী-প্রান্ত পেজ স্ট্রাকচার

WeChat মিনি-প্রোগ্রাম ও Flutter APP-এর ফাংশন সম্পূর্ণ একই।

### auth/ — অথেনটিকেশন
- login — লগইন (ফোন/ভেরিফিকেশন কোড/WeChat/গেস্ট এন্ট্রি)
- register — রেজিস্ট্রেশন (ফোন + ভেরিফিকেশন কোড + পাসওয়ার্ড + রেফারেল কোড)
- forget-password — পাসওয়ার্ড ভুলে গেলে
- agreement — চুক্তি দেখা

### home/ — হোমপেজ
- index — হোমপেজ (ক্যারোসেল ব্যানার + ঘোষণা + সার্ভিস ক্যাটাগরি + সুপারিশ)
- search — সার্চ পেজ

### service/ — সার্ভিস
- list — সার্ভিস তালিকা (ক্যাটাগরি অনুযায়ী ফিল্টার)
- detail — সার্ভিস ডিটেইল (বেসিক তথ্য + রিভিউ + এখনই বুক করুন)
- product-list — প্রোডাক্ট তালিকা

### order/ — অর্ডার
- confirm — অর্ডার নিশ্চিতকরণ (শাখা/টেকনিশিয়ান/সময়/কুপন/নোট/চুক্তি)
- payment — পেমেন্ট পেজ
- payment-success — পেমেন্ট সফল
- list — সব অর্ডার (স্ট্যাটাস Tab অনুযায়ী ফিল্টার)
- detail — অর্ডার ডিটেইল
- review — সার্ভিস রিভিউ
- verification — QR কোড ভেরিফিকেশন

### cart/ — কার্ট
- index — কার্ট তালিকা

### technician/ — টেকনিশিয়ান (কাস্টমার দৃষ্টিভঙ্গি)
- list — টেকনিশিয়ান তালিকা (দূরত্ব কাছাকাছি থেকে দূরে সাজানো)
- detail — টেকনিশিয়ান ডিটেইল (রিভিউ/সার্ভিস আইটেম/এখনই বুক করুন)
- apply — টেকনিশিয়ান এনরোলমেন্ট আবেদন

### tech-work/ — টেকনিশিয়ান ওয়ার্কবেঞ্চ (টেকনিশিয়ান আইডেন্টিটি)
- index — ওয়ার্কবেঞ্চ হোমপেজ (আজকের অর্ডার/আয় ওভারভিউ)
- schedule — শিডিউল সেটিং
- order-list — আমার অর্ডার (বুকড-অনভেরিফাইড/সম্পন্ন)
- scan-verify — স্ক্যান ভেরিফিকেশন
- member-list — আমার মেম্বার
- member-detail — মেম্বার ডিটেইল/প্রোফাইল সম্পাদনা
- earnings — আমার আয়
- withdrawal — উত্তোলন
- transaction-list — লেনদেন ডিটেইল
- attendance — উপস্থিতি/পরিচ্ছন্নতার ছবি আপলোড
- training — পেশাদার প্রশিক্ষণ

### user/ — পার্সোনাল সেন্টার
- index — ব্যক্তিগত তথ্য (অবতার/নিকনেম/মেম্বার কার্ড/ফেভারিট/কুপন এন্ট্রি)
- settings — সেটিংস (পাসওয়ার্ড পরিবর্তন/ফোন পরিবর্তন/চুক্তি/আপডেট/বাতিল/লগআউট)
- switch-role — আইডেন্টিটি সুইচ (কাস্টমার ↔ টেকনিশিয়ান)

### marketing/ — মার্কেটিং
- coupon-list — কুপন তালিকা
- member-card — আমার মেম্বার কার্ড
- points — আমার পয়েন্ট
- gift-card — আমার গিফট কার্ড
- referral — প্রোমোশন (ব্যাখ্যা + QR কোড পোস্টার + রেফারেল ইউজার তালিকা)

### অন্যান্য পেজ
- message/ — মেসেজ তালিকা/ডিটেইল
- store/list, store/detail — শাখা তালিকা (LBS সর্টিং)/ডিটেইল (নেভিগেশন)
- other/about — আমাদের সম্পর্কে
- other/feedback — মতামত/ফিডব্যাক
- other/official-account — অফিসিয়াল অ্যাকাউন্ট ফলো করা

### সাধারণ কম্পোনেন্ট
- navbar, tabbar, service-card, technician-card
- coupon-popup, lbs-selector, empty-state, loading

### আইডেন্টিটি সুইচ লজিক
- কাস্টমার আইডেন্টিটি বটম নেভিগেশন: হোম / সার্ভিস / কার্ট / অর্ডার / আমার
- টেকনিশিয়ান আইডেন্টিটি বটম নেভিগেশন: ওয়ার্কবেঞ্চ / অর্ডার / মেম্বার / আয় / আমার
- "আমার" পেজে আইডেন্টিটি সুইচ এন্ট্রি রয়েছে
- এখনও টেকনিশিয়ান নন এমন ইউজার টেকনিশিয়ান আইডেন্টিটিতে সুইচ করলে এনরোলমেন্ট আবেদন পেজে নিয়ে যাওয়া হয়

## ক্রয় প্রক্রিয়া ব্যাখ্যা

সিস্টেমে দুটি ভিন্ন ক্রয় প্রক্রিয়া রয়েছে:

### সার্ভিস বুকিং প্রক্রিয়া (সরাসরি অর্ডার, কার্ট নেই)
- সার্ভিস আইটেম ডিটেইল পেজ → অর্ডার নিশ্চিতকরণ (শাখা/টেকনিশিয়ান/সময় নির্বাচন) → পেমেন্ট → ভেরিফিকেশন
- টেকনিশিয়ান রিসোর্স এক্সক্লুসিভ: অর্ডার নিশ্চিতকরণ পেজে প্রবেশের সময় টেকনিশিয়ানকে 3 মিনিট লক করা হয়
- ম্যাসাজ/বিউটি প্রভৃতি অফলাইন সার্ভিস আইটেমের জন্য ব্যবহৃত হয়

### প্রোডাক্ট ক্রয় প্রক্রিয়া (কার্ট মোড)
- প্রোডাক্ট তালিকা → কার্টে যোগ → কার্ট নিশ্চিতকরণ → অর্ডার সাবমিট → পেমেন্ট → ডেলিভারি/রিসিভ
- পরিমাণ পরিবর্তন, পণ্য মুছে ফেলা সমর্থিত
- ফিজিক্যাল পণ্য বা কার্ড/কুপন বিক্রয়ের জন্য ব্যবহৃত হয়

## মূল বিজনেস নিয়ম

### টেকনিশিয়ান লক মেকানিজম
- একই সময়ে একাধিক ব্যক্তি একই টেকনিশিয়ানকে বুক করতে পারে না
- ইউজার অর্ডার নিশ্চিতকরণ পেজে প্রবেশ করলে Redis SETNX-এর মাধ্যমে টেকনিশিয়ানকে 3 মিনিট লক করা হয়
- বুকিং পেজ ছেড়ে গেলে বা টাইমআউট হলে স্বয়ংক্রিয়ভাবে লক রিলিজ হয়

### রিফান্ড নিয়ম
| শর্ত | রিফান্ড অনুপাত |
|------|----------|
| অর্ডারের 15 মিনিটের মধ্যে বা শুরু হওয়ার >6 ঘণ্টা আগে | 100% |
| শুরু হওয়ার ≤6 ঘণ্টা আগে | 90% |
| শুরু হয়েছে কিন্তু সার্ভিস নিশ্চিত হয়নি | 80% |
| সার্ভিস নিশ্চিতভাবে শুরু হওয়ার পরে | 0% (রিফান্ড নেই) |

### ডিসকাউন্ট নিয়ম
- কম-চাপ সময় (10-12টা/17-18টা/21:00-এর পরে) ১০% ছাড় (মূল্যের 90%)
- 30 মিনিট আগে বুক করলে ৫% ছাড় (মূল্যের 95%, কুপনের সাথে স্ট্যাক করা যাবে না)

### টেকনিশিয়ান উত্তোলন
- প্রতি মাসের 20 তারিখে উত্তোলন করা যায়, T+1 কার্যদিবসে অ্যাকাউন্টে আসে
- WeChat ওয়ালেটে উত্তোলন সমর্থিত
- ভেরিফাইড কিন্তু সেটেলমেন্ট হয়নি এমন অর্ডার, 3 দিনের মধ্যে সিস্টেম স্বয়ংক্রিয়ভাবে নিশ্চিত করে
- 24 ঘণ্টার মধ্যে মেম্বার প্রোফাইল সম্পন্ন করতে হবে, নইলে কমিশন নেই

### রিটার্নিং-কাস্টমার রিওয়ার্ড
- 30 দিনের মধ্যে একই টেকনিশিয়ানের কাছে দ্বিতীয়বার কনজাম্পশন → বোনাস রেকর্ড হয়
- সার্ভিসের পরে পরিচ্ছন্নতার ছবি আপলোড করা হয়

### পয়েন্ট নিয়ম
- 1:100 অনুপাতে গিফট কার্ড বিনিময় (ব্যাকএন্ডে কনফিগারযোগ্য)
- রেফারেল ইউজার রেজিস্ট্রেশন সফল ও অর্ডার করার পর নির্দিষ্ট পয়েন্ট পাওয়া যায় (ব্যাকএন্ডে সেট করা)
