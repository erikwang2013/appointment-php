# अपॉइंटमेंट सेवा प्रणाली डिज़ाइन विनिर्देश
> **Languages**: [中文](../../superpowers/specs/2026-05-26-appointment-system-design.md) · [English](../../en/specs/2026-05-26-appointment-system-design.md) · [한국어](../../ko/specs/2026-05-26-appointment-system-design.md) · [Русский](../../ru/specs/2026-05-26-appointment-system-design.md) · [Deutsch](../../de/specs/2026-05-26-appointment-system-design.md) · [Français](../../fr/specs/2026-05-26-appointment-system-design.md) · [Español](../../es/specs/2026-05-26-appointment-system-design.md) · [Português](../../pt/specs/2026-05-26-appointment-system-design.md) · [العربية](../../ar/specs/2026-05-26-appointment-system-design.md) · [বাংলা](../../bn/specs/2026-05-26-appointment-system-design.md) · [Bahasa Indonesia](../../id/specs/2026-05-26-appointment-system-design.md) · [日本語](../../ja/specs/2026-05-26-appointment-system-design.md)

## अवलोकन

तीन-पक्षीय अपॉइंटमेंट सेवा प्रणाली: उपयोगकर्ता-पक्ष (वीचैट मिनी प्रोग्राम + Flutter APP) + तकनीशियन वर्कबेंच (उसी APP में पहचान स्विच) + प्रबंधन बैकएंड (PC Web)।

## आर्किटेक्चर निर्णय

| निर्णय | समाधान |
|------|------|
| बैकएंड आर्किटेक्चर | `admin/` (प्रबंधन बैकएंड API) + `service/` (बिज़नेस API), दो सेवाएँ MySQL/Redis साझा करती हैं |
| उपयोगकर्ता-पक्ष मिनी प्रोग्राम | नेटिव वीचैट मिनी प्रोग्राम `apps/wechat/` |
| उपयोगकर्ता-पक्ष APP | Flutter `apps/flutter/` (iOS + Android) |
| उपयोगकर्ता पहचान | एकीकृत खाता, ग्राहक/तकनीशियन पहचान स्विच करने योग्य |
| मिनी प्रोग्राम और APP संबंध | कार्यक्षमता पूर्णतः समान, केवल प्लेटफ़ॉर्म भिन्नता |
| प्रबंधन बैकएंड फ्रंटएंड | मौजूदा Flutter Web (`admin/apps/flutter/`) का विस्तार |
| प्रबंधन बैकएंड बैकएंड | मौजूदा webman v2 (`admin/`) का बिज़नेस मॉड्यूल विस्तार |
| तृतीय-पक्ष सेवाएँ | वीचैट लॉगिन/भुगतान/SMS/मानचित्र — एकीकरण योजना रिज़र्व |

## सिस्टम आर्किटेक्चर आरेख

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

## डेटाबेस मुख्य टेबल

सभी टेबल `erik_` उपसर्ग उपयोग करती हैं, प्राथमिक कुंजी BIGINT गैर-ऑटो-इंक्रीमेंट (Snowflake द्वारा उत्पन्न)। संवेदनशील फ़ील्ड encryptable trait से एन्क्रिप्ट-डिक्रिप्ट।

### उपयोगकर्ता और पहचान डोमेन

| टेबल नाम | विवरण | मुख्य फ़ील्ड |
|------|------|----------|
| `erik_user` | एकीकृत उपयोगकर्ता टेबल | phone, password, wx_openid, wx_unionid, avatar, nickname, user_type(customer/technician), status। technician उपयोगकर्ता के पास साथ ही ग्राहक कार्यक्षमताएँ हैं, वर्तमान सक्रिय पहचान स्वतंत्र रूप से स्विच कर सकता है |
| `erik_user_address` | उपयोगकर्ता पता | user_id, contact_name, contact_phone, province, city, district, detail, is_default |
| `erik_technician_profile` | तकनीशियन प्रोफ़ाइल | user_id, real_name, gender, id_card, id_card_front, id_card_back, avatar, rating, order_count, status(pending/approved/rejected), intro |
| `erik_technician_schedule` | तकनीशियन शेड्यूल | technician_id, date, time_slots(JSON), status |
| `erik_technician_service` | तकनीशियन की सेवा आइटम | technician_id, service_id |
| `erik_technician_earnings` | तकनीशियन आय लेन-देन | technician_id, order_id, type(commission/bonus/penalty), amount, status |
| `erik_technician_withdrawal` | तकनीशियन विड्रॉल रिकॉर्ड | technician_id, amount, actual_amount, commission_fee, account_info, status, reviewed_at |
| `erik_technician_attendance` | तकनीशियन उपस्थिति | technician_id, date, check_in_at, check_out_at, clean_photo |
| `erik_technician_member_note` | सदस्य प्रोफ़ाइल | technician_id, user_id, content, written_at |

### सेवा और उत्पाद डोमेन

| टेबल नाम | विवरण | मुख्य फ़ील्ड |
|------|------|----------|
| `erik_service_category` | सेवा श्रेणी | name, icon, parent_id, sort, status |
| `erik_service` | सेवा आइटम | category_id, name, description, cover_image, images(JSON), price, duration, sales_volume, specs(JSON), status |
| `erik_product` | उत्पाद | category_id, name, cover_image, price, stock, sales_volume, type, status |
| `erik_store` | स्टोर | name, address, lat, lng, phone, business_hours(JSON), images, status |

### ऑर्डर डोमेन

| टेबल नाम | विवरण | मुख्य फ़ील्ड |
|------|------|----------|
| `erik_order` | ऑर्डर मुख्य टेबल | order_no, user_id, technician_id, store_id, total_amount, discount_amount, paid_amount, status, service_time, cancel_reason, remark |
| `erik_order_item` | ऑर्डर विवरण | order_id, service_id, product_id, type, name, price, quantity, spec_info |
| `erik_order_payment` | भुगतान रिकॉर्ड | order_id, pay_type(wechat), transaction_id, amount, status, paid_at |
| `erik_order_refund` | रिफंड रिकॉर्ड | order_id, payment_id, refund_no, amount, ratio, reason, status |
| `erik_order_review` | सेवा समीक्षा | order_id, user_id, technician_id, rating, content, images |
| `erik_order_verification` | वेरिफिकेशन रिकॉर्ड | order_id, code, verified_at, verified_by, location |

### मार्केटिंग डोमेन

| टेबल नाम | विवरण | मुख्य फ़ील्ड |
|------|------|----------|
| `erik_coupon` | कूपन परिभाषा | name, type, amount, min_amount, total_qty, remain_qty, start_at, end_at, status |
| `erik_user_coupon` | उपयोगकर्ता कूपन | user_id, coupon_id, status(available/used/expired), used_at |
| `erik_member_card` | सदस्यता कार्ड परिभाषा | name, type(month/vip/times), price, duration_days, total_times, services(JSON) |
| `erik_user_member_card` | उपयोगकर्ता सदस्यता कार्ड | user_id, card_id, start_at, end_at, total_times, used_times, status |
| `erik_member_card_usage` | सेशन कार्ड उपयोग रिकॉर्ड | user_card_id, order_id, service_id, used_at |
| `erik_user_points` | पॉइंट्स लेन-देन | user_id, type(earn/use), points, source, order_id |
| `erik_gift_card` | गिफ्ट कार्ड | code, type, amount_or_gift, status, used_by, used_at |
| `erik_user_referral` | उपयोगकर्ता प्रमोशन | referrer_id, referred_user_id, reward_type, reward_amount, registered_at, first_order_at |

### सामग्री और नोटिफिकेशन डोमेन

| टेबल नाम | विवरण | मुख्य फ़ील्ड |
|------|------|----------|
| `erik_banner` | कैरोसेल | position, image, jump_type(url/detail/none), jump_value, sort, status |
| `erik_announcement` | घोषणा | content, status, published_at |
| `erik_platform_agreement` | प्लेटफ़ॉर्म समझौता | type(user_agreement/privacy_policy/service_agreement), title, content, version |
| `erik_faq` | सामान्य प्रश्न | title, content, sort |
| `erik_feedback` | फ़ीडबैक | user_id, content, images, handler_reply, status(pending/handled) |
| `erik_moment` | मोमेंट्स फ़ीड | content, images, published_at |
| `erik_notification` | मैसेज नोटिफिकेशन | user_id, type(order/system), title, content, is_read, created_at |

### वित्त डोमेन (admin पक्ष)

| टेबल नाम | विवरण | मुख्य फ़ील्ड |
|------|------|----------|
| `erik_finance_transaction` | आय-व्यय लेन-देन | user_id, order_id, type, direction(income/expense), amount, actual_amount, commission, status |
| `erik_technician_commission_config` | कमीशन कॉन्फ़िगरेशन | technician_id, commission_rate, settlement_cycle |
| `erik_withdrawal_account` | विड्रॉल खाता | user_id, type(wechat), account_name, account_no |
| `erik_withdrawal_config` | विड्रॉल सीमा कॉन्फ़िगरेशन | min_amount, reserve_amount, round_to_hundred |

## Service API मॉड्यूल

### सार्वजनिक API (प्रमाणीकरण आवश्यक नहीं)
- **AuthController** — लॉगिन/पंजीकरण/पासवर्ड भूलना/गेस्ट मोड/पहचान स्विच
- **CaptchaController** — SMS वेरिफिकेशन कोड
- **WechatController** — वीचैट ऑथराइज़ेशन/लॉगिन/भुगतान कॉलबैक
- **CommonController** — समझौता टेक्स्ट/हमारे बारे में/संस्करण जानकारी

### उपयोगकर्ता मॉड्यूल `user/` (प्रमाणीकरण आवश्यक)
- **ProfileController** — व्यक्तिगत जानकारी/पासवर्ड बदलना/मोबाइल बदलना/खाता हटाना
- **AddressController** — डिलीवरी पता CRUD
- **FavoriteController** — पसंदीदा
- **FeedbackController** — फ़ीडबैक
- **ReferralController** — प्रमोशन/रेफर किए गए उपयोगकर्ता सूची

### तकनीशियन मॉड्यूल `technician/` (तकनीशियन पहचान + TechnicianAuth मिडलवेयर आवश्यक)
- **ProfileController** — तकनीशियन प्रोफ़ाइल/आवेदन
- **ScheduleController** — शेड्यूल सेटिंग
- **OrderController** — बुक किया गया-बिना वेरिफिकेशन/पूर्ण/स्कैन वेरिफिकेशन
- **MemberController** — मेरे सदस्य/सदस्य प्रोफ़ाइल
- **EarningsController** — आय/इन-फ्लाइट फंड
- **WithdrawalController** — विड्रॉल
- **AttendanceController** — उपस्थिति/स्वच्छता फ़ोटो

### सेवा मॉड्यूल `service/`
- **CategoryController** — सेवा श्रेणी
- **ItemController** — सेवा/उत्पाद सूची और विवरण
- **SearchController** — खोज
- **StoreController** — स्टोर सूची/विवरण

### ऑर्डर मॉड्यूल `order/` (प्रमाणीकरण आवश्यक)
- **CartController** — कार्ट
- **OrderController** — ऑर्डर/ऑर्डर सूची/विवरण/रद्द
- **PaymentController** — भुगतान/रिफंड
- **VerificationController** — QR कोड वेरिफिकेशन
- **ReviewController** — समीक्षा

### मार्केटिंग मॉड्यूल `marketing/` (प्रमाणीकरण आवश्यक)
- **CouponController** — कूपन सूची/प्राप्त करना/उपयोग
- **MemberCardController** — सदस्यता कार्ड/सेशन कार्ड
- **PointsController** — पॉइंट्स
- **GiftCardController** — गिफ्ट कार्ड

### सामग्री मॉड्यूल `content/`
- **BannerController** — कैरोसेल
- **AnnouncementController** — घोषणा
- **NotificationController** — मैसेज नोटिफिकेशन

### LBS मॉड्यूल
- **LocationController** — लोकेशन/शहर स्विच/आस-पास के स्टोर

### सामान्य क्षमताएँ `common/`
- SnowflakeService — ID जनरेशन
- HashidsService — ID एन्क्रिप्ट-डिक्रिप्ट
- EncryptionService — संवेदनशील डेटा एन्क्रिप्ट-डिक्रिप्ट
- WechatPayService — वीचैट भुगतान (रिज़र्व)
- WechatAuthService — वीचैट लॉगिन (रिज़र्व)
- SmsService — SMS सेवा (रिज़र्व)
- MapService — मानचित्र सेवा (रिज़र्व)

### मिडलवेयर
- Auth — JWT प्रमाणीकरण (admin के साथ erikwang2013/jwt-webman पैकेज साझा)
- TechnicianAuth — तकनीशियन पहचान जाँच
- RateLimit — रेट-लिमिट (admin के साथ साझा)

## Admin प्रबंधन बैकएंड विस्तार

मौजूदा फ्रेमवर्क के आधार पर नए नियंत्रक:

### तकनीशियन प्रबंधन
- **TechnicianController** — तकनीशियन सूची/खोज/निर्यात/ऑडिट/शेड्यूल प्रबंधन/तकनीकी सेवा आइटम सेटिंग/पाठ्यक्रम सीखने की प्रगति

### उपयोगकर्ता प्रबंधन विस्तार
- **MemberController** — सदस्य सूची/स्तर सेटिंग/उपभोग आँकड़े

### स्टोर प्रबंधन
- **StoreController** — स्टोर CRUD/सक्षम-अक्षम

### सेवा प्रबंधन
- **ServiceController** — सेवा सूची/CRUD/कार्ड आइटम डिज़ाइन
- **ServiceCategoryController** — श्रेणी प्रबंधन
- **ProductController** — उत्पाद सूची/CRUD

### मॉल प्रबंधन
- **MallOrderController** — मॉल ऑर्डर/शिपमेंट/आफ्टर-सेल/समीक्षा
- **SalesStatsController** — बिक्री आँकड़े

### ऑर्डर प्रबंधन
- **AppointmentOrderController** — उपयोग शेष ऑर्डर/रद्द/पूर्ण पुष्टि

### कूपन गतिविधि
- **CouponController** — कूपन CRUD/जारी करना

### वित्त प्रबंधन
- **FinanceController** — ऑर्डर प्रॉफिट शेयरिंग/आय-व्यय लेन-देन
- **WithdrawalController** — तकनीशियन विड्रॉल ऑडिट/पूर्ण
- **CommissionController** — कमीशन सेटिंग/इनाम-दंड/बैलेंस क्वेरी
- **WithdrawalAccountController** — विड्रॉल खाता प्रबंधन
- **WithdrawalConfigController** — विड्रॉल सीमा कॉन्फ़िगरेशन

### सामग्री प्रबंधन
- **BannerController** — कैरोसेल CRUD
- **AnnouncementController** — घोषणा CRUD
- **FaqController** — FAQ CRUD
- **FeedbackController** — फ़ीडबैक प्रोसेसिंग
- **MomentController** — मोमेंट्स फ़ीड ऑडिट
- **AgreementController** — समझौता संपादन (उपयोगकर्ता समझौता/गोपनीयता समझौता/सेवा समझौता)
- **AboutController** — हमारे बारे में सेटिंग

### सेटिंग
- **SystemMessageController** — सिस्टम मैसेज सेटिंग
- **AdminUserController** — उप-खाता प्रबंधन (मौजूदा RBAC पर आधारित)

### Dashboard विस्तार
- वास्तविक समय आँकड़े कार्ड: उपयोगकर्ता संख्या/कुल ऑर्डर/तकनीशियन संख्या/सेवा ऑर्डर संख्या
- लाइन चार्ट: ऑर्डर मात्रा/राशि/दैनिक नए उपयोगकर्ता/गतिविधि
- त्वरित नेविगेशन: प्रतीक्षा मॉड्यूल बटन
- इन-ऐप मैसेज: नए ऑर्डर नोटिफिकेशन/रिफंड नोटिफिकेशन

## उपयोगकर्ता-पक्ष पेज संरचना

वीचैट मिनी प्रोग्राम और Flutter APP कार्यक्षमता पूर्णतः समान।

### auth/ — प्रमाणीकरण
- login — लॉगिन (मोबाइल नंबर/वेरिफिकेशन कोड/वीचैट/गेस्ट प्रवेश)
- register — पंजीकरण (मोबाइल नंबर+वेरिफिकेशन कोड+पासवर्ड+रेफरल कोड)
- forget-password — पासवर्ड भूलना
- agreement — समझौता देखना

### home/ — होमपेज
- index — होमपेज (कैरोसेल+घोषणा+सेवा श्रेणियाँ+सिफारिश)
- search — खोज पेज

### service/ — सेवाएँ
- list — सेवा सूची (श्रेणी फ़िल्टर)
- detail — सेवा विवरण (मूल जानकारी+समीक्षा+तुरंत अपॉइंटमेंट)
- product-list — उत्पाद सूची

### order/ — ऑर्डर
- confirm — ऑर्डर कन्फर्म (स्टोर/तकनीशियन/समय/कूपन/टिप्पणी/समझौता)
- payment — भुगतान पेज
- payment-success — भुगतान सफल
- list — सभी ऑर्डर (स्थिति Tab फ़िल्टर)
- detail — ऑर्डर विवरण
- review — सेवा समीक्षा
- verification — QR कोड वेरिफिकेशन

### cart/ — कार्ट
- index — कार्ट सूची

### technician/ — तकनीशियन (ग्राहक दृष्टिकोण)
- list — तकनीशियन सूची (दूरी निकट से दूर क्रम)
- detail — तकनीशियन विवरण (समीक्षाएँ/सेवा आइटम/तुरंत अपॉइंटमेंट)
- apply — तकनीशियन आवेदन

### tech-work/ — तकनीशियन वर्कबेंच (तकनीशियन पहचान)
- index — वर्कबेंच होम (आज के ऑर्डर/आय अवलोकन)
- schedule — शेड्यूल सेटिंग
- order-list — मेरे ऑर्डर (बुक किया-बिना वेरिफिकेशन/पूर्ण)
- scan-verify — स्कैन वेरिफिकेशन
- member-list — मेरे सदस्य
- member-detail — सदस्य विवरण/प्रोफ़ाइल संपादन
- earnings — मेरी आय
- withdrawal — विड्रॉल
- transaction-list — लेन-देन विवरण
- attendance — उपस्थिति/स्वच्छता फ़ोटो अपलोड
- training — पेशेवर प्रशिक्षण

### user/ — व्यक्तिगत केंद्र
- index — व्यक्तिगत जानकारी (अवतार/निकनेम/सदस्यता कार्ड/पसंदीदा/कूपन प्रवेश)
- settings — सेटिंग (पासवर्ड बदलना/मोबाइल बदलना/समझौता/अपडेट/खाता हटाना/लॉगआउट)
- switch-role — पहचान स्विच (ग्राहक ↔ तकनीशियन)

### marketing/ — मार्केटिंग
- coupon-list — कूपन सूची
- member-card — मेरे सदस्यता कार्ड
- points — मेरे पॉइंट्स
- gift-card — मेरे गिफ्ट कार्ड
- referral — प्रमोशन (विवरण+QR कोड पोस्टर+रेफर किए गए उपयोगकर्ता सूची)

### अन्य पेज
- message/ — मैसेज सूची/विवरण
- store/list, store/detail — स्टोर सूची (LBS क्रम)/विवरण (नेविगेशन)
- other/about — हमारे बारे में
- other/feedback — फ़ीडबैक
- other/official-account — ऑफिशियल अकाउंट फॉलो करें

### सामान्य घटक
- navbar, tabbar, service-card, technician-card
- coupon-popup, lbs-selector, empty-state, loading

### पहचान स्विच लॉजिक
- ग्राहक पहचान बॉटम नेविगेशन: होमपेज / सेवाएँ / कार्ट / ऑर्डर / मेरा
- तकनीशियन पहचान बॉटम नेविगेशन: वर्कबेंच / ऑर्डर / सदस्य / आय / मेरा
-「मेरा」पेज पहचान स्विच प्रवेश प्रदान करता है
- अभी तक तकनीशियन नहीं बने उपयोगकर्ता तकनीशियन पहचान पर स्विच करते समय आवेदन पेज पर निर्देशित होते हैं

## खरीद प्रवाह विवरण

सिस्टम में दो अलग खरीद प्रवाह हैं:

### सेवा अपॉइंटमेंट प्रवाह (सीधे ऑर्डर, कोई कार्ट नहीं)
- सेवा आइटम विवरण पेज → ऑर्डर कन्फर्म (स्टोर/तकनीशियन/समय चुनें) → भुगतान → वेरिफिकेशन
- तकनीशियन संसाधन एकाधिकार: ऑर्डर कन्फर्म पेज में प्रवेश करते समय तकनीशियन 3 मिनट लॉक
- मालिश, ब्यूटी जैसी ऑफ़लाइन सेवा आइटम के लिए

### उत्पाद खरीद प्रवाह (कार्ट मोड)
- उत्पाद सूची → कार्ट में जोड़ें → कार्ट कन्फर्म → ऑर्डर सबमिट → भुगतान → शिपमेंट/रिसीव
- मात्रा बदलना, उत्पाद हटाना सपोर्ट
- भौतिक वस्तुओं या कार्ड कूपन बिक्री के लिए

## मुख्य बिज़नेस नियम

### तकनीशियन लॉक तंत्र
- एक ही समय में कई लोग एक तकनीशियन को बुक नहीं कर सकते
- उपयोगकर्ता ऑर्डर कन्फर्म पेज में प्रवेश करते समय, Redis SETNX से तकनीशियन 3 मिनट लॉक
- अपॉइंटमेंट पेज छोड़ने या टाइमआउट पर स्वतः लॉक रिलीज़

### रिफंड नियम
| शर्त | रिफंड प्रतिशत |
|------|----------|
| ऑर्डर के 15 मिनट के भीतर या शुरुआत से >6 घंटे | 100% |
| शुरुआत से ≤6 घंटे | 90% |
| शुरू हो चुका लेकिन सेवा पुष्टि नहीं | 80% |
| सेवा शुरुआत पुष्टि के बाद | 0% (रिफंड नहीं) |

### डिस्काउंट नियम
- कम-पीक अवधि (10-12 बजे/17-18 बजे/21:00 के बाद) 9% छूट (90%)
- 30 मिनट पहले अपॉइंटमेंट 95% (कूपन के साथ स्टैक नहीं)

### तकनीशियन विड्रॉल
- हर महीने 20 तारीख को विड्रॉल, T+1 कार्यदिवस में जमा
- वीचैट वॉलेट में विड्रॉल सपोर्ट
- वेरिफाई किए गए-सेटल नहीं ऑर्डर, 3 दिनों में सिस्टम स्वतः पुष्टि
- 24 घंटे के भीतर सदस्य प्रोफ़ाइल पूरी करनी होगी, नहीं तो कमीशन नहीं

### लौटने वाले ग्राहक इनाम
- 30 दिनों में एक ही तकनीशियन के साथ दूसरी बार उपभोग → बोनस रिकॉर्ड
- सेवा के बाद स्वच्छता फ़ोटो अपलोड

### पॉइंट्स नियम
- 1:100 गिफ्ट कार्ड एक्सचेंज (बैकएंड कॉन्फ़िगर करने योग्य)
- रेफर किए गए उपयोगकर्ता के सफल पंजीकरण और ऑर्डर के बाद निर्धारित पॉइंट्स प्राप्त (बैकएंड सेटिंग)
