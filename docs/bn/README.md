# অ্যাপয়েন্টমেন্ট সার্ভিস সিস্টেম
> **Languages**: [中文](../README.md) · [English](../en/README.md) · [한국어](../ko/README.md) · [Русский](../ru/README.md) · [Deutsch](../de/README.md) · [Français](../fr/README.md) · [Español](../es/README.md) · [Português](../pt/README.md) · [हिन्दी](../hi/README.md) · [العربية](../ar/README.md) · [Bahasa Indonesia](../id/README.md) · [日本語](../ja/README.md)

> বাংলা অনুবাদ · মূল: [中文](../../README.md)

চার প্রান্তের অ্যাপয়েন্টমেন্ট সার্ভিস ম্যানেজমেন্ট প্ল্যাটফর্ম: ব্যবহারকারী পাশের WeChat মিনি-প্রোগ্রাম + Flutter APP + HarmonyOS APP (একই অ্যাকাউন্টে পরিচয় স্যুইচ), PC অ্যাডমিন ব্যাকএন্ড।

> **প্রজেক্ট অবস্থা**: সম্পূর্ণ ✅ | ১৪৩ কন্ট্রোলার (service ৬৯ / admin ৭৪) | ৮৭ মডেল | ৭২২ টেস্ট (service ৫৫৮ / admin ১৬৪) | ৯৫ ডেটা টেবিল | ৩৮৮ রাউট (service ২২৭ / admin ১৬১)

## প্রজেক্ট পরিচিতি

<img src="docs/bn/diagrams/mascot.svg" alt="预约服务系统吉祥物——预约小兔（SVG 动画）" width="200" align="right">

**অ্যাপয়েন্টমেন্ট সার্ভিস সিস্টেম** হল জীবনধর্মী সার্ভিস শিল্পের জন্য একটি চার প্রান্তের অ্যাপয়েন্টমেন্ট ম্যানেজমেন্ট প্ল্যাটফর্ম: ব্যবহারকারী পাশ **WeChat মিনি-প্রোগ্রাম, Flutter APP, HarmonyOS APP** তিন প্রান্ত কভার করে, একই অ্যাকাউন্টে প্রান্ত-পার ভাবে স্বাধীনভাবে স্যুইচ করা যায়, সাথে **PC অ্যাডমিন ব্যাকএন্ড**, "ব্যবহারকারীর অ্যাপয়েন্টমেন্ট → টেকনিশিয়ানের অর্ডার গ্রহণ → ব্যাকএন্ড অপারেশন" সম্পূর্ণ প্রক্রিয়ার ডিজিটাল বন্ধনী অর্জন করে। শাখা অ্যাপয়েন্টমেন্ট, টেকনিশিয়ান সার্ভিস, মেম্বার মার্কেটিং বা ফাইন্যান্সিয়াল সেটেলমেন্ট — একটি সিস্টেমেই সবকিছু।

**এক-স্টপ অ্যাপয়েন্টমেন্ট অভিজ্ঞতা**

ব্যবহারকারীর তিন প্রান্তের অভিজ্ঞতা অভিন্ন: ক্যালেন্ডারে স্বজ্ঞাতভাবে সময় নির্বাচন করে অ্যাপয়েন্টমেন্ট, কুপন/টাইম কার্ড/পয়েন্ট ছাড়, ফ্ল্যাশ সেল ও গ্রুপ বাই ছাড়, WeChat/ব্যালেন্স পেমেন্ট, অর্ডার অবস্থা সর্বত্র ট্র্যাকযোগ্য — তারিখ পরিবর্তন, বাতিল, রিফান্ড, বিক্রয়োত্তর সেবা, ইলেকট্রনিক ইনভয়েস পুরো প্রক্রিয়া অনলাইনে সম্পন্ন; টেকনিশিয়ান পাশে ওয়ার্কবেঞ্চ, উপস্থিতি চেক-ইন, ব্যাচ শিডিউলিং, সার্ভিস ভেরিফিকেশন ও উত্তোলন অনুমোদন, অপারেশন দক্ষতা এক নজরে।

**সম্পূর্ণ-চেইন মার্কেটিং গ্রোথ**

অন্তর্নির্মিত ফুল রিডাকশন অ্যাক্টিভিটি, ফ্ল্যাশ সেল, গ্রুপ বাই, কুপন ট্রান্সফার, পয়েন্ট মল ও লাকি হুইল, মেম্বার কার্ড/গ্রোথ লেভেল সুবিধা, দুই-স্তর ডিস্ট্রিবিউশন রিবেট, রিটার্ন কাস্টমার রিওয়ার্ড সহ দশাধিক মার্কেটিং টুল, সাথে সাবস্ক্রিপশন মেসেজ পুশ ও APP পুশ, ব্যবসায়ীদের ধারাবাহিকভাবে নতুন কাস্টমার আনা, ধরে রাখা ও পুনরায় কেনা করতে সাহায্য করে।

**এন্টারপ্রাইজ-লেভেল নিরাপত্তা ও কমপ্লায়েন্স**

নিজস্ব তৈরি সিকিউরিটি কম্পোনেন্ট ব্যবহার করে: JWT অথেনটিকেশন, ID অবফাস্কেশন, ৩১ ধরনের আক্রমণ ডিটেকশন, সংবেদনশীল ডেটা দ্বৈত-স্তর এনক্রিপশন, সার্ভিস-সাইড মূল্য যাচাই, পেমেন্ট কলব্যাক কঠোর তুলনা ও আইডেম্পোটেন্সি প্রতিরোধ, সাথে WeChat অফিসিয়াল প্রফিট শেয়ারিং, প্রাইভেসি ডেটা এক্সপোর্ট ও অ্যাকাউন্ট ডিলিটেশন সাপোর্ট, কমপ্লায়েন্স প্রয়োজনীয়তা পূরণ করে।

**পরিণত প্রযুক্তি ভিত্তি**

PHP 8.3 + webman উচ্চ-পারফরম্যান্স রেসিডেন্ট ফ্রেমওয়ার্কের উপর ভিত্তি করে, MySQL 8.0 + Redis + Elasticsearch সাপোর্ট করে; ৯৫ ডেটা টেবিল, ৩৮৮টি API, ২৮৫টি ফাইন-গ্রেইন্ড পারমিশন পয়েন্ট, ৭২২টি অটোমেশন টেস্ট সব পাস, সাথে সম্পূর্ণ চাইনিজ/ইংরেজি আর্কিটেকচার ডকুমেন্টেশন ও ওয়ান-ক্লিক ইনস্টল স্ক্রিপ্ট, বক্সের বাইরে ব্যবহারযোগ্য, সহজে সেকেন্ডারি ডেভেলপমেন্ট করা যায়।

একক শাখার অ্যাপয়েন্টমেন্ট বা বহু-শাখা চেইন, অ্যাপয়েন্টমেন্ট সার্ভিস সিস্টেম আপনার জন্য স্থিতিশীল, নিরাপদ, এক্সটেনসিবল ইন্টিগ্রেটেড সমাধান দিতে পারে।

## প্রজেক্ট কাঠামো

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

## দ্রুত শুরু

### পরিবেশ প্রয়োজনীয়তা

- PHP 8.3+
- MySQL 8.0+
- Redis
- Composer

### Web ইনস্টল উইজার্ড (প্রস্তাবিত)

```bash
cd admin/
cp .env.example .env
composer install
php start.php start -d
```

ব্রাউজারে `http://localhost:8787/install` খুলুন, নির্দেশনা অনুযায়ী ডেটাবেস ও অ্যাডমিন অ্যাকাউন্ট পূরণ করলেই ইনস্টল সম্পন্ন।

### ম্যানুয়াল ইনস্টল

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

### Docker ডিপ্লয়মেন্ট

```bash
cd admin/ && cp .env.docker .env && docker-compose up -d
cd ../service/ && cp .env.docker .env && docker-compose up -d
```

## টেকনোলজি স্ট্যাক

| স্তর | টেকনোলজি | বিবরণ |
|------|------|------|
| ব্যাকএন্ড ফ্রেমওয়ার্ক | webman v2 (PHP 8.3+) | উচ্চ-পারফরম্যান্স রেসিডেন্ট মেমরি HTTP সার্ভিস |
| ডেটাবেস | MySQL 8.0 | টেবিল প্রিফিক্স `erik_` |
| ক্যাশ | Redis | ক্যাশ/রেট লিমিট/Session/কিউ |
| সার্চ | Elasticsearch | ফুল-টেক্সট সার্চ (via webman-scout) |
| অ্যাডমিন ব্যাকএন্ড ফ্রন্টএন্ড | Flutter Web | PC অ্যাডমিন ব্যাকএন্ড স্টাইল |
| ব্যবহারকারী APP | Flutter | iOS + Android |
| ব্যবহারকারী মিনি-প্রোগ্রাম | নেটিভ WeChat মিনি-প্রোগ্রাম | WXML/WXSS/JS |
| ব্যবহারকারী HarmonyOS APP | HarmonyOS ArkTS | নেটিভ @ohos.net.http |
| ID জেনারেশন | erikwang2013/snowflake-php | BIGINT নন-অটোইনক্রিমেন্ট প্রাইমারি কী |
| API ID এনক্রিপ্ট/ডিক্রিপ্ট | erikwang2013/hashids | বাইরে আসল ID লুকানো |
| JWT অথেনটিকেশন | erikwang2013/jwt-webman | Bearer Token |
| সংবেদনশীল ডেটা এনক্রিপশন | erikwang2013/encryption + encryptable | API + DB দ্বৈত-স্তর এনক্রিপশন |
| নিরাপত্তা সুরক্ষা | erikwang2013/security-php | ৩১ ধরনের আক্রমণ ডিটেকশন |
| অপারেশন ভেরিফিকেশন | erikwang2013/poster-php | সংবেদনশীল অপারেশন র্যান্ডম ভেরিফিকেশন |
| দেশের পতাকা | erikwang2013/season | জাতীয় পতাকা আইকন |
| ES সিঙ্ক | erikwang2013/webman-scout | মডেল স্বয়ংক্রিয় সিঙ্ক |

## সিস্টেম আর্কিটেকচার

<img src="docs/bn/diagrams/bn-architecture.svg" alt="bn-architecture.svg" width="100%">

## মূল ফ্লো

### সার্ভিস অ্যাপয়েন্টমেন্ট ফ্লো

<img src="docs/bn/diagrams/bn-appointment-flow.svg" alt="bn-appointment-flow.svg" width="100%">

### পেমেন্ট ও রিফান্ড ফ্লো

<img src="docs/bn/diagrams/bn-payment-refund.svg" alt="bn-payment-refund.svg" width="100%">

## অর্ডার লাইফসাইকেল

<img src="docs/bn/diagrams/bn-order-lifecycle.svg" alt="bn-order-lifecycle.svg" width="100%">

## সিকিউরিটি আর্কিটেকচার

### গভীর প্রতিরক্ষা সাত স্তরের সিস্টেম

<img src="docs/bn/diagrams/bn-security-defense.svg" alt="bn-security-defense.svg" width="100%">

> আরও বিস্তারিত চিত্র: [ফ্লোচার্ট](diagrams/FLOWCHART.md) (টেকনিশিয়ান উত্তোলন/পরিচয় স্যুইচ সহ) | [ফাংশন ব্রেন ম্যাপ](diagrams/FUNCTION-DIAGRAM.md) | [সম্পূর্ণ লাইফসাইকেল](diagrams/LIFECYCLE-DIAGRAM.md) | [সম্পূর্ণ সিকিউরিটি আর্কিটেকচার](diagrams/SECURITY-ARCHITECTURE.md)

## মূল ফিচার হাইলাইট (রাউন্ড ৬-২৪)

| ফিচার | বিবরণ |
|------|------|
| স্টোরেজ ওয়ালেট | user_wallet / wallet_recharge / wallet_txn টেবিল; ব্যালেন্স+লেনদেন, WeChat পেমেন্ট রিচার্জ (কলব্যাক R প্রিফিক্স অর্ডার নম্বর), অর্ডার ব্যালেন্স পেমেন্ট (pay_channel=balance), WeChat/ব্যালেন্স রিফান্ড স্বয়ংক্রিয়ভাবে ব্যালেন্সে ফেরত |
| অ্যাডমিন ব্যাকএন্ড UI সম্পূর্ণ | Flutter Web ২০ পেজ: dashboard/ব্যবহারকারী/রোল/কনফিগ/লগ/ভেরিফিকেশন/শিডিউল/সার্ভিস/টেকনিশিয়ান/অর্ডার/কুপন/মেম্বার/টাইম কার্ড/বিজ্ঞপ্তি/FAQ/উত্তোলন/রিভিউ/রিপোর্ট/পার্সোনাল সেন্টার |
| মিনি-প্রোগ্রাম সাবস্ক্রিপশন মেসেজ | অর্ডার ৩টি দৃশ্যে সাবস্ক্রিপশন পুশ (পেমেন্ট সফল/রিফান্ড পৌঁছানো/ভেরিফিকেশন সফল); push_sent_at আইডেম্পোটেন্ট; টেমপ্লেট কনফিগ না থাকলে সাইট-ইন নোটিফিকেশনে ডাউনগ্রেড |
| টেকনিশিয়ান উত্তোলন | অ্যাডমিন পাশে অনুমোদন; পরিমাণ ≥৫০০ হলে দুই-স্তর অনুমোদন (স্টোর ম্যানেজার→ফাইন্যান্স); স্টেট মেশিন pending→approved→completed (rejected/failed) |
| টাইম কার্ড ভেরিফিকেশন বন্ধনী | আমার টাইম কার্ডে রিয়েল-টাইম used_up/expired হিসাব; ভেরিফিকেশন Redis NX আইডেম্পোটেন্ট + সারি লক কাটা, সরাসরি completed অর্ডার + OrderItem + OrderPayment(pay_type='card') |
| টেকনিশিয়ান ওয়ার্কবেঞ্চ | আজকের কাজ/সম্পন্ন রেকর্ড/শুরু·সম্পন্ন (সারি লক+স্টেট মেশিন গার্ড+আইডেম্পোটেন্সি, সম্পন্ন হলে সাইট-ইন নোটিফিকেশন); মিনি-প্রোগ্রাম tech-work তিন Tab |
| কুপন ছাড় | PriceCalculator: applyCoupon শুধু-পড়া হিসাব / consume পেমেন্টে used সেট / restoreCouponAndCard রিফান্ড আইডেম্পোটেন্ট ফেরত; fixed/percent + min_amount শর্ত |
| গিফট কার্ড | redeem করলে cash টাইপ ওয়ালেটে রিচার্জ (সারি লক দ্বৈত-এন্ট্রি প্রতিরোধ, WalletTxn type='gift_card'), gift টাইপ শুধু মার্ক করে |
| পয়েন্ট সিস্টেম | চেক-ইনে পয়েন্ট; ভেরিফিকেশনে খরচে পয়েন্ট floor(paid×1) (order_id আইডেম্পোটেন্ট, balance স্ন্যাপশট); রিফান্ডে অনুপাতে ফেরত; লেনদেন পেজিনেশন + type/source ফিল্টার |
| মেম্বার ম্যানেজমেন্ট | erik_user.member_level কলাম (মাইগ্রেশন 000008); অ্যাডমিন পাশে মেম্বার কার্ড সম্পূর্ণ CRUD (পারমিশন ৩৬৫-৩৬৯) |
| মিনি-প্রোগ্রাম অর্ডার চেইন | সার্ভিস ডিটেইল → অর্ডার কনফার্ম (কুপন নির্বাচন/শর্ত গ্রে-আউট/ক্লায়েন্ট আনুমানিক পরিমাণ) → POST /order → WeChat/ব্যালেন্স পেমেন্ট; মিনি-প্রোগ্রামে মোট ২০ পেজ |
| গ্রুপ বাই বন্ধনী | join পুনরাবৃত্তি ৪২২ + পূর্ণ হলে লক + মেয়াদ শেষে লেজি ক্লোজ; গ্রুপ গঠনে store তে promotion_id পাস করে গ্রুপ প্রাইসে (discount_percent) অর্ডার, কুপন/টাইম কার্ড/পয়েন্ট স্ট্যাকিং নিষিদ্ধ, গ্রুপ না হলে স্বয়ংক্রিয় অর্ডার বাতিল ও টেকনিশিয়ান লক মুক্ত (পুরনো FLASH_SALE প্রোমো চ্যানেল বন্ধ, সেকিল আলাদা চ্যানেলে) |
| স্টোর ম্যানেজার ওয়ার্কবেঞ্চ | service /api/store-manager ৪টি API (overview/orders/technicians/revenue) store_id বাধ্যতামূলক আইসোলেশন (শাখা নেই ৪০৩); admin শাখা ওয়ার্কবেঞ্চ ওভারভিউ + অর্ডার store_id ফিল্টার + Flutter পেজ + পারমিশন ৩৭২ |
| ডিস্ট্রিবিউশন রিবেট | রেফার করা ব্যক্তির প্রথম অর্ডার completed হলে paid_amount × reward_rate (সিস্টেম কনফিগ, ডিফল্ট 0.05) অনুযায়ী রেফারারকে ওয়ালেটে রিবেট (WalletTxn referral_reward); সারি লক+খালি চেক+প্রথম অর্ডার পুনরায় চেক ট্রিপল আইডেম্পোটেন্সি; earnings ডিটেইল + admin রেকর্ড দেখা (পারমিশন ৩৭৯) |
| পয়েন্ট রিডিম মল | রিডিম পণ্য/রিডিম রেকর্ড দুই টেবিল; রিডিম API Redis NX + সারি লক অতিরিক্ত রিডিম প্রতিরোধ + uk_user_goods একই ব্যবহারকারী একবার; coupon কুপন / wallet এন্ট্রি / gift_card কার্ড-সিক্রেট তিন ফলাফল; admin CRUD + প্রকাশ/আড়াল + রেকর্ড (পারমিশন ৩৭৩-৩৭৮) |
| অ্যাপয়েন্টমেন্ট রিশিডিউল | POST /api/order/reschedule/{id} একই টেকনিশিয়ানে সময় পরিবর্তন; শুধু pending/paid/confirmed এবং মূল সার্ভিস শুরু থেকে ≥৬ ঘণ্টা আগে; order_lock + নতুন স্লট টেকনিশিয়ান লক SETNX(180s) কনকারেন্সি ওভারসেল প্রতিরোধ + B2 শিডিউল সংঘর্ষ যাচাই; erik_order_reschedule + SCENE_RESCHEDULE সাবস্ক্রিপশন মেসেজ |
| কুপন ট্রান্সফার | ৮ সংখ্যার ইউনিক ট্রান্সফার কোড (uk_code ফলব্যাক, ৭ দিন বৈধ); claim অপব্যবহার প্রতিরোধ: Redis NX লক + সারি লক পুনঃযাচাই দ্বৈত-স্পেন্ড প্রতিরোধ, uk_user_coupon একবার ট্রান্সফার সীমা, ট্রান্সফার করা কুপন আর ট্রান্সফার করা যাবে না, নিজে ক্লেইম করা যাবে না; লেজি এক্সপায়ারে মূল কুপন ফেরত |
| পয়েন্ট এক্সপায়ার | expires_at (ডিফল্ট ৩৬৫ দিন, কনফিগ points.expiry_days); PointsExpiryTimer ৬০ সেকেন্ড কার্সর স্ক্যানে type=expire নেতিবাচক কাটা (তিন স্তর আইডেম্পোটেন্সি) + অ্যাগ্রিগেট সাইট-ইন নোটিফিকেশন; এক্সপায়ারড পয়েন্ট ক্যাশ/রিডিম করা যায় না |
| টেকনিশিয়ান লেভেল অটো অ্যাসেসমেন্ট | TierRatingService রিয়েল-টাইম অর্ডার কাউন্ট+গড় স্কোর profile-এ ফেরত লেখা, tier_config অনুযায়ী উঁচু থেকে নিচু ম্যাচ; শুধু আপগ্রেড (allowDowngrade ম্যানুয়াল পুনঃমূল্যায়নের জন্য); পরিবর্তন erik_technician_tier_log + সাইট-ইন নোটিফিকেশন; admin লগ দেখা (পারমিশন ৩৮০) |
| সেকিল অর্ডার বন্ধনী | /api/seckill অ্যাক্টিভিটি + buy আইডেম্পোটেন্ট/কনকারেন্সি প্রতিরোধ, অর্ডারে seckill_id ইনজেক্ট করে store() পুনরায় ব্যবহার, স্টক ট্রানজেকশনের ভেতরে সারি লকে কাটা (সেকিল প্রাইস = seckill_price DB-ই প্রামাণ্য), সোল্ড আউট ৪২২ "সব শেষ", বাতিলে স্টক ফেরত নেই; পুরনো promotion flash_sale চ্যানেল বন্ধ |
| সার্ভিস শুরুর আগে রিমাইন্ডার | ServiceReminderTimer ৬০ সেকেন্ডে ১ ঘণ্টার মধ্যে শুরু হওয়া confirmed/serving অর্ডার স্ক্যান → SCENE_REMINDER সাবস্ক্রিপশন মেসেজ+সাইট-ইন নোটিফিকেশন (order_id+type ডুপ প্রতিরোধ, তিন স্তর আইডেম্পোটেন্সি); টেমপ্লেট কনফিগ না হলে সাইট-ইন নোটিফিকেশনে ডাউনগ্রেড |
| এক্সপায়ারি রিমাইন্ডার | ExpiryReminderTimer ৬ ঘণ্টায় ৩ দিনের মধ্যে এক্সপায়ার হওয়া মেম্বার কার্ড/কুপন স্ক্যান → type=card_expiry/coupon_expiry + SCENE_EXPIRY সাবস্ক্রিপশন মেসেজ (order_id দিয়ে উৎস রেকর্ড ডুপ প্রতিরোধ) |
| টেকনিশিয়ান রিভিউ রিপ্লাই | POST /api/technician/review/reply/{order_id}: অ-নিজস্ব ৪০৪, পুনরাবৃত্তি রিপ্লাই ৪২২, রিপ্লাই সফলে ব্যবহারকারীকে সাইট-ইন নোটিফিকেশন; erik_order_review-এ replied_at; admin রিপ্লাই ডিটেইল (পারমিশন ৩৮১) |
| রিচার্জ পৌঁছানোর নোটিফিকেশন | WeChat রিচার্জ কলব্যাক ট্রানজেকশনের ভেতরে সাইট-ইন নোটিফিকেশন type='wallet_recharge' (কলব্যাক আইডেম্পোটেন্সি পুনরায় ব্যবহার, একই ট্রানজেকশন অ্যাটমিক, ব্যর্থ হলে মূল ফ্লো ব্লক হয় না) |
| ব্যালেন্স ট্রান্সফার | POST /api/wallet/transfer ব্যবহারকারী-মধ্যবর্তী ট্রান্সফার: পরিমাণ 0.01-1000/ট্রানজেকশন + দৈনিক ৫০০০ সীমা; Redis NX লক + দুই পক্ষের ওয়ালেট সারি লক (user_id অ্যাসেন্ডিং ডেডলক প্রতিরোধ) + client_token ২৪ ঘণ্টা আইডেম্পোটেন্ট; WalletTxn transfer_out/transfer_in দ্বৈত লেনদেন balance_after স্ন্যাপশট সহ; প্রাপকের সাইট-ইন নোটিফিকেশন type='balance_received' |
| পয়েন্ট ট্রান্সফার | POST /api/user/points/transfer ব্যবহারকারী-মধ্যবর্তী ট্রান্সফার: ১-১০০০০ পয়েন্ট + দৈনিক মোট ১০০০০ সীমা; Redis NX লক + দুই পক্ষের শেষ লেনদেন lockForUpdate (অ্যাসেন্ডিং ডেডলক প্রতিরোধ) + লকের ভেতরে পুনঃযাচাই; প্রেরকের consume/প্রাপকের earn দ্বৈত লেনদেন (প্রাপকে expires_at সহ স্বাভাবিক এক্সপায়ার); প্রাপকের সাইট-ইন নোটিফিকেশন type='points_received' |
| রিভিউ অ্যাপেন্ড | POST /api/order/review/{order_id}/append: অ-নিজস্ব ৪০৪/পুনরাবৃত্তি ৪২২/খালি কনটেন্ট ৪২২/অ-কমপ্লিটেড ৪২২, সফলে টেকনিশিয়ানকে সাইট-ইন নোটিফিকেশন type='review_append'; erik_order_review-এ append_content/append_images(JSON)/append_at; সাথে রেজিস্টার্ড ব্যবহারকারীর রিভিউ রুট (আগে store রুট ছিল না) এবং এর লুকানো TypeError ফিক্স |
| ব্যবহারকারী লজিস্টিক ট্র্যাকিং | GET /api/order/logistics/{id}: শুধু নিজস্ব product অর্ডার (৪০৪ অ-নিজস্ব/অ-পণ্য/অ-শিপড); order.remark JSON পড়ে (shipping_company/tracking_no/shipped_at, admin শিপমেন্টে লেখে); প্রাপকের ফোন মাস্কড 138****5678 |
| মেসেজ পছন্দ সেটিং | erik_user_notify_setting টেবিল (uk_user_type ইউনিক কী, ডিফল্ট সারি = সব চালু); GET/PUT /api/user/notify-settings; ৫ ধরনের সুইচ service_reminder/card_expiry/points_expiry/marketing/system (system চিরকাল চালু বন্ধ করা যায় না); notifySettingEnabled গেটে ৩টি টাইমার + সাবস্ক্রিপশন ইভেন্ট, বন্ধ থাকলে সাইট-ইন নোটিফিকেশন ও সাবস্ক্রিপশন মেসেজ দুটোই বাদ |
| অ্যাপয়েন্টমেন্ট ক্যালেন্ডার | GET /api/calendar/technician/{id} (মাস ভিউ) + /day (দিন ভিউ): time_slots JSON ঘণ্টা স্লটে বিস্তৃত, erik_order-এ আগে থেকে বুক করা স্লট বাদ; শাখা শিডিউল ভিজুয়ালাইজড সময় নির্বাচন |
| ব্যবহারকারী গ্রোথ লেভেল | erik_user_growth + erik_growth_level (ব্রোঞ্জ ০/সিলভার ১০০/গোল্ড ৫০০/প্লাটিনাম ২০০০/ডায়মন্ড ৫০০০); চেক-ইন +১০, রিভিউ +২০, খরচ প্রতি ১ ইউয়ানে ১ পয়েন্ট (বিদ্যমান স্টেট পুনঃযাচাই প্রাকৃতিক আইডেম্পোটেন্ট); GET /api/growth (ওভারভিউ/records/levels পাবলিক লেভেল) |
| ইলেকট্রনিক ইনভয়েস | POST/GET /api/invoices (আবেদন/লিস্ট/ডিটেইল): uk_order_type(order_id,order_type) পুনরাবৃত্তি আবেদন প্রতিরোধ, পরিমাণ সার্ভার-সাইড; admin ইনভয়েস/প্রত্যাখ্যান (পারমিশন ৩৮২-৩৮৪) |
| কাস্টমার টিকেট | POST/GET /api/tickets + /{id}/close: ব্যবহারকারী জমা/লিস্ট/ডিটেইল/ক্লোজ; admin রিপ্লাই (পারমিশন ৩৮৫/৩৮৭) |
| মাল্টি-লেভেল ডিস্ট্রিবিউশন - লেভেল ২ রিবেট | অর্ডার পেমেন্টের পর লেভেল ১ রেফারারের রেফারারকে paid×level2_rate (কনফিগ 0.02): ট্রানজেকশন সারি লক + uk_order_referred আইডেম্পোটেন্সি ডুপলিকেট ইস্যু প্রতিরোধ; WalletTxn TYPE_REFERRAL_LEVEL2; admin রেকর্ড দেখা (পারমিশন ৩৮৬) |
| গ্রোথ লেভেল সুবিধা | GrowthLevel.benefits শেল বাস্তবায়ন: অর্ডারে লেভেল অনুযায়ী discount_rate ছাড় (শুধু স্ট্যান্ডার্ড অর্ডার, কুপন/টাইম কার্ড→লেভেল ছাড় স্ট্যাকিং, ছাড়ের পরিমাণ discount_amount + মন্তব্যে ট্রেসযোগ্য, নিম্ন সীমা সুরক্ষায় ০ এ কাটা); পেমেন্ট কলব্যাকে গ্রোথ পয়েন্ট floor(paid×points_multiplier) গুণে এন্ট্রি (পেমেন্ট সময়ের লেভেল, আপগ্রেড নয়) |
| ইনভয়েস হেডার ম্যানেজমেন্ট | erik_invoice_title সাধারণ হেডার লাইব্রেরি: সেভ/এডিট/ডিলিট/ডিফল্ট (প্রথমটি স্বয়ংক্রিয় ডিফল্ট, ডিফল্ট ডিলিটে স্বয়ংক্রিয় স্থানান্তর, ডিফল্ট সেট ট্রানজেকশন শূন্য); ইনভয়েস আবেদনে title_id অপশনাল, ম্যানুয়াল ফিল কম্প্যাটিবল |
| টিকেট স্যাটিসফ্যাকশন | টিকেট ক্লোজে ১-৫ রেটিং (বাউন্ডারির বাইরে ৪২২, না দিলে NULL কম্প্যাটিবল); admin স্যাটিসফ্যাকশন সামারি: গড় স্কোর/১-৫ স্টার ডিস্ট্রিবিউশন/রেটেড-অনরেটেড কাউন্ট (পারমিশন ৩৮৮) |
| রিভিউ ইমেজ অডিট | admin ReviewAuditController: ছবিসহ রিভিউ লিস্ট (JSON_LENGTH ফিল্টার + join ব্যবহারকারী/টেকনিশিয়ান নাম), হাইড/রিস্টোর (hide শুধু visible, restore শুধু hidden, ৪২২ দ্বিমুখী যাচাই); হাইড করলে টেকনিশিয়ান রিভিউ লিস্টে স্বয়ংক্রিয় অদৃশ্য (পারমিশন ৩৮৯-৩৯১) |
| ব্রাউজ হিস্ট্রি | erik_browse_history (uk_user_item পুনরাবৃত্তি ব্রাউজে শুধু viewed_at আপডেট): সার্ভিস ডিটেইলে হুক (try/catch মূল ফ্লো ব্লক করে না, লগইন না করলে স্কিপ); লিস্ট join সার্ভিস তথ্য + hashid; একক ডিলিট/ক্লিয়ার শুধু নিজস্ব |

> রাউন্ড ৮ অপারেশনাল ফিক্স: ১২টি Poster::verify লুকানো fatal সরানো; DashboardController স্ট্যাটিস্টিকস Capsule Manager কোয়েরিতে বদলানো।
>
> Round-15 সংযোজন: পয়েন্ট রিফান্ড (বাতিল/রিফান্ডে points_offset পয়েন্ট ফেরত, refundOffsetPoints ৫টি হুক পয়েন্ট আইডেম্পোটেন্ট); PromotionParticipant স্ট্যাটাস ইন্টিজার কনস্ট্যান্টে বদলানো (স্ট্রিক্ট মোডে join ১৩৬৬ ক্ষতি ফিক্স)।
>
> Round-16 সংযোজন: পয়েন্ট রিডিম (PointsExchangeController, টাইপ consume/source=exchange); গ্রুপ বাই অর্ডার (erik_order-এ promotion_id/participant_id কলাম); ডিস্ট্রিবিউশন রিবেট (ReferralRewardService হুক WorkController::complete)।
>
> Round-17 সংযোজন: অ্যাপয়েন্টমেন্ট রিশিডিউল (erik_order_reschedule + reschedule API); কুপন ট্রান্সফার (erik_user_coupon_transfer + transfer/claim/transfers); পয়েন্ট এক্সপায়ার (expires_at + PointsExpiryTimer প্রসেস); টেকনিশিয়ান লেভেল অটো অ্যাসেসমেন্ট (TierRatingService + erik_technician_tier_log, পারমিশন ৩৮০)。
>
> Round-17 ফিক্স: AutoCancelTimer নোটিফিকেশন ইনসার্ট \support\Model::generateId() ব্যবহার করে (আগে অস্তিত্বহীন Snowflake::generate() কল করত, অটো বাতিল নোটিফিকেশন নীরবে ব্যর্থ হতো)।
>
> Round-18 সংযোজন: সেকিল অর্ডার (store() flash_sale সেকিল প্রাইস সাপোর্ট); সার্ভিস শুরুর আগে রিমাইন্ডার (ServiceReminderTimer + SCENE_REMINDER); মেম্বার কার্ড/কুপন এক্সপায়ারি রিমাইন্ডার (ExpiryReminderTimer + SCENE_EXPIRY); টেকনিশিয়ান রিভিউ রিপ্লাই (review reply API + replied_at কলাম + পারমিশন ৩৮১); রিচার্জ পৌঁছানোর নোটিফিকেশন (কলব্যাক ট্রানজেকশনে type='wallet_recharge')।
>
> Round-19 সংযোজন: ব্যালেন্স ট্রান্সফার (erik_wallet_transfer + WalletTransferController, পারমিশনে দ্বৈত সারি লক + client_token আইডেম্পোটেন্সি); পয়েন্ট ট্রান্সফার (erik_user_points_transfer + PointsTransferController, দৈনিক সীমা + দ্বিমুখী লেনদেন); রিভিউ অ্যাপেন্ড (erik_order_review append তিন কলাম + append API + store রুট রেজিস্ট্রেশন); ব্যবহারকারী লজিস্টিক ট্র্যাকিং (logistics API + remark JSON পার্স + ফোন মাস্কিং); মেসেজ পছন্দ সেটিং (erik_user_notify_setting + NotifySettingController + ৩ টাইমার গেট)।
>
> Round-20 সংযোজন: অ্যাপয়েন্টমেন্ট ক্যালেন্ডার (CalendarController মাস/দিন ভিউ + বুকড বাদ); ব্যবহারকারী গ্রোথ লেভেল (erik_user_growth + erik_growth_level ৫ লেভেল + চেক-ইন/রিভিউ/খরচ হুক); ইলেকট্রনিক ইনভয়েস (erik_invoice + uk_order_type ডুপ প্রতিরোধ + ব্যাকএন্ড ইনভয়েস/প্রত্যাখ্যান, পারমিশন ৩৮২-৩৮৪); কাস্টমার টিকেট (erik_ticket জমা/লিস্ট/ডিটেইল/ক্লোজ + ব্যাকএন্ড রিপ্লাই, পারমিশন ৩৮৫/৩৮৭); মাল্টি-লেভেল ডিস্ট্রিবিউশন - লেভেল ২ রিবেট (payLevel2Reward ট্রানজেকশন সারি লক + uk_order_referred আইডেম্পোটেন্সি, পারমিশন ৩৮৬)。
>
> Round-21 সংযোজন: গ্রোথ লেভেল সুবিধা বাস্তবায়ন (অর্ডারে discount_rate ছাড় + পেমেন্টে points_multiplier পয়েন্ট গুণ, মাইগ্রেশন সিড ৫ লেভেল benefits); ইনভয়েস হেডার ম্যানেজমেন্ট (erik_invoice_title হেডার লাইব্রেরি + আবেদনে title_id লিংক); টিকেট স্যাটিসফ্যাকশন (ক্লোজে রেটিং rating/rated_at + admin সামারি স্ট্যাট, পারমিশন ৩৮৮); রিভিউ ইমেজ অডিট (ReviewAuditController হাইড/রিস্টোর, পারমিশন ৩৮৯-৩৯১); ব্যবহারকারী ব্রাউজ হিস্ট্রি (erik_browse_history + ডিটেইল হুক + লিস্ট/ডিলিট/ক্লিয়ার)。
>
> Round-22 সংযোজন: ফুল রিডাকশন অ্যাক্টিভিটি (erik_full_reduction অটো ছাড় + শর্ত যাচাই, পারমিশন ৩৯৬-৪০০); ICS ক্যালেন্ডার এক্সপোর্ট (RFC5545 আমার অ্যাপয়েন্টমেন্ট); টেকনিশিয়ান চেক-ইন (erik_technician_attendance চেক-ইন/আউট + লেট মার্ক + admin স্ট্যাট, পারমিশন ৩৯২-৩৯৩); APP পুশ সার্ভিস (কনফিগ-ড্রাইভেন অ্যাবস্ট্রাকশন + ৫টি ইভেন্ট ইন্টিগ্রেশন, erik_push_log); WeChat অফিসিয়াল প্রফিট শেয়ারিং (erik_profit_sharing_log কনফিগ-ড্রাইভেন + ডাউনগ্রেড, পারমিশন ৩৯৪); প্রাইভেসি কমপ্লায়েন্স (ডেটা এক্সপোর্ট + অ্যাকাউন্ট ডিলিটেশন ৭২ ঘণ্টা স্টেট মেশিন close_status)。
>
> Round-23 সংযোজন: ব্যবহারকারী হেলথ প্রোফাইল (erik_user_health_profile); ওয়ালেট পেমেন্ট পাসওয়ার্ড (erik_user_wallet pay_password সেট/যাচাই); টেকনিশিয়ান ব্যাচ শিডিউল (batch ইমপোর্ট + ওভারল্যাপ সংঘর্ষ ডিটেকশন); অর্ডার স্ট্যাটাস টাইমলাইন (erik_order_status_log ৮ স্ট্যাটাস ট্রেস + ব্যবহারকারী/ব্যাকএন্ড ডিসপ্লে); পয়েন্ট লাকি হুইল (erik_lucky_wheel + erik_wheel_record ওয়েটেড লটারি, পারমিশন ৪০১-৪০৬); পয়েন্ট ভ্যালিডিটি (points.expiry_days কনফিগ + নতুন earn লেনদেনে expires_at)।
>
> Round-24 সংযোজন: গেস্ট মোড (/api/guest/* লগইন ছাড়া রিড-অনলি ব্রাউজ + Redis ক্যাশ); সেকিল (erik_seckill_activity + Redis NX সারি লক কেনা + erik_order.seckill_id ইনজেক্ট অর্ডার, পারমিশন ৪০৭-৪১১/৪২০); APP ভার্সন ম্যানেজমেন্ট ও আপডেট ডিটেকশন (erik_app_version + /api/app/version, পারমিশন ৪১৬-৪১৯); রিটার্ন কাস্টমার রিওয়ার্ড (৩০ দিনে দ্বিতীয় খরচ বোনাস type=return_customer, পারমিশন ৪১২-৪১৪); শিডিউল CSV এক্সপোর্ট (UTF-8 BOM + টাইম স্লট ডিটেইল, পারমিশন ৪১৫)。
>
> 2026-08-26 নিরাপত্তা শক্তিশালীকরণ: অর্ডার API-তে অর্ডার আইটেমের দাম সবসময় ডেটাবেস রেকর্ড অনুযায়ী (ক্লায়েন্ট প্রাইস অবিশ্বস্ত, অজানা target_type ৪২২, target_id অবশ্যই hashid), গ্রুপ বাই/সেকিল প্রাইসও DB-ই প্রামাণ্য; সেকিল স্টক সবসময় /api/order store() ট্রানজেকশনের ভেতরে সারি লকে কাটা (SeckillController::buy আর প্রি-ডিডাক্ট করে না, Redis অ্যাক্টিভিটি লক + client_token আইডেম্পোটেন্সি রেখেছে); টেকনিশিয়ান উত্তোলন আবেদনে ইন-ফ্লাইট রিজার্ভ, অনুমোদন ট্রান্সফারের আগে পুনঃচেক, কনকারেন্ট অনুমোদনে ডাবল পেমেন্ট প্রতিরোধ; WeChat পেমেন্ট কলব্যাক total_fee অর্ডার পাওনাদির সাথে কঠোর তুলনা, Alipay কলব্যাক লগ মাস্কিং; /install ইনস্টল সফলে .install.lock ডাবল-চেক পুনঃইনস্টল প্রতিরোধ; ডিপেন্ডেন্সি ভার্সন কনভার্জেন্স (webman-scout 2.0.5 / opensearch-php ^2.6 / dompdf, security-php, webman-database সুনির্দিষ্ট লক); দুই অ্যাপের phpstan.neon ফিক্স করা চালুযোগ্য (php -d memory_limit=2G)。

## ডকুমেন্টেশন নেভিগেশন

| ডকুমেন্ট | বিবরণ |
|------|------|
| [আর্কিটেকচার ব্যাখ্যা](ARCHITECTURE.md) | সিস্টেম আর্কিটেকচার, তিন প্রান্তের সম্পর্ক, টেকনোলজি কম্পোনেন্ট, ডেটা ফ্লো |
| [ফিচার ব্যাখ্যা](FEATURES.md) | ব্যবহারকারী/টেকনিশিয়ান/অ্যাডমিন ব্যাকএন্ড সম্পূর্ণ ফিচার লিস্ট |
| [আর্কিটেকচার ডিজাইন](ARCHITECTURE-DESIGN.md) | লেয়ারড ডিজাইন, মিডলওয়্যার চেইন, ডেটাবেস ডিজাইন, সিকিউরিটি ডিজাইন |
| [ফিচার ডিজাইন](FEATURE-DESIGN.md) | মূল বিজনেস ফ্লো, বিজনেস রুল, স্টেট মেশিন, রিফান্ড রুল |
| [API ডকুমেন্টেশন](API.md) | বিজনেস API + অ্যাডমিন ব্যাকএন্ড API, রিকোয়েস্ট/রেসপন্স উদাহরণ + OpenAPI এন্ডপয়েন্ট সহ |
| [ইনস্টল নির্দেশনা](INSTALL.md) | পরিবেশ প্রয়োজনীয়তা, Docker ডিপ্লয়মেন্ট, এনভায়রনমেন্ট ভেরিয়েবল, থার্ড-পার্টি কনফিগ, সাধারণ সমস্যা |
| [ব্যবহার নির্দেশনা](USAGE.md) | অ্যাডমিন ব্যাকএন্ড কনফিগ, ব্যবহারকারী/টেকনিশিয়ান অপারেশন, রিফান্ড রুল (API ইন্টারফেস API.md-তে) |
| [প্রজেক্ট কাঠামো](STRUCTURE.md) | সম্পূর্ণ ডিরেক্টরি লেআউট, মিডলওয়্যার এক্সিকিউশন চেইন, ডেটাবেস টেবিল লিস্ট |
| [টেস্ট রিপোর্ট](TEST-REPORT.md) | ফুল টেস্ট কভারেজ অডিট (৫৫৮ কেস / ২৫০৮ অ্যাসারশন) |
| [ডিজাইন স্পেক](specs/2026-05-26-appointment-system-design.md) | সিস্টেম ডিজাইন স্পেসিফিকেশন |
| [ইমপ্লিমেন্টেশন প্ল্যান](plans/2026-05-26-appointment-system-plan.md) | পর্যায়ক্রমিক ইমপ্লিমেন্টেশন প্ল্যান |

## 支持项目 / Support

যদি এই প্রজেক্ট আপনার কাজে লাগে, সাপোর্ট করতে স্বাগতম! আপনার উৎসাহের জন্য ধন্যবাদ :heart:

If this project helps you, your support is welcome and appreciated!

<table>
  <tr>
    <td align="center" width="50%">
      <img src="docs/weixinpay.png" alt="微信支付 / WeChat Pay" width="130" height="130"><br>
      <b>WeChat Pay</b><br>WeChat Pay
    </td>
    <td align="center" width="50%">
      <img src="docs/alipay.png" alt="支付宝 / Alipay" width="130" height="130"><br>
      <b>Alipay</b><br>Alipay
    </td>
  </tr>
</table>

### 全球转账 / Global Bank Transfer

বিশ্বব্যাপী ব্যাংক ট্রান্সফার ডোনেশন স্বাগতম (HKD / CNY / USD / অন্যান্য মুদ্রা), আপনার উদারতার জন্য ধন্যবাদ :heart:

Global bank transfer donations are welcome (HKD / CNY / USD / other currencies). Thank you for your generosity!

| 项目 Item | 信息 Details |
|-----------|-------------|
| 收款人姓名 Beneficiary Name | WANG KEXUN |
| 收款账户号码 Account Number | 881015918251 |
| 收款银行 Bank | ZA Bank Limited（SWIFT Code：AABLHKHHXXX，银行编号 Bank Code：387） |
| 银行地址 Bank Address | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **跨境汇款代理银行（如需）/ Intermediary Bank (if required)**
> 此为跨境汇款代理银行（中转银行）信息，非收款银行信息，请向汇款银行查询是否需要提供。
> Note: this is intermediary bank information, not the receiving bank. Please check with your remitting bank whether it is required.
>
> - 汇入港元、人民币及美元（For HKD / CNY / USD）：**Citibank N.A. Hong Kong** — SWIFT Code：CITIHKHXXXX，银行编号 Bank Code：006，分行名称 Branch：Hong Kong Branch，分行编号 Branch Code：391，地址 Address：Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - 汇入其他币种（For other currencies）：**The Bank of New York Mellon** — SWIFT Code：IRVTUS3NXXX，地址 Address：240 Greenwich Street, New York, United States

## কপিরাইট

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
