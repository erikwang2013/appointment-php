# API दस्तावेज़
> **Languages**: [中文](../API.md) · [English](../en/API.md) · [한국어](../ko/API.md) · [Русский](../ru/API.md) · [Deutsch](../de/API.md) · [Français](../fr/API.md) · [Español](../es/API.md) · [Português](../pt/API.md) · [العربية](../ar/API.md) · [বাংলা](../bn/API.md) · [Bahasa Indonesia](../id/API.md) · [日本語](../ja/API.md)

## अवलोकन

- **बिज़नेस API** (service/): `http://localhost:8787` — मिनी प्रोग्राम/APP के लिए बिज़नेस इंटरफ़ेस
- **प्रबंधन बैकएंड API** (admin/): `http://localhost:8787` — प्रबंधन बैकएंड Flutter Web के लिए इंटरफ़ेस
- **प्रमाणीकरण विधि**: Bearer Token (JWT), अनुरोध हेडर `Authorization: Bearer <token>`
- **संस्करण नियंत्रण**: अनुरोध हेडर `API-Version: v1` द्वारा API संस्करण नियंत्रित, URL में नहीं। डिफ़ॉल्ट v1
- **ID एन्कोडिंग**: सभी अनुरोध/प्रतिक्रिया में ID फ़ील्ड hashids एन्कोडिंग उपयोग करती हैं, वास्तविक डेटाबेस ID बाहरी रूप से छिपाई जाती है
- **OpenAPI दस्तावेज़**: `hg/apidoc` द्वारा उत्पन्न, प्रबंधन-पक्ष और क्लाइंट-पक्ष अलग

| एंड | OpenAPI दस्तावेज़ पता | विवरण |
|------|------|------|
| प्रबंधन-पक्ष | `GET http://localhost:8787/api/docs` | प्रबंधन बैकएंड API पूर्ण विनिर्देश (OpenAPI 3.0 JSON) |
| क्लाइंट-पक्ष | `GET http://localhost:8787/api/docs` | बिज़नेस API पूर्ण विनिर्देश (OpenAPI 3.0 JSON) |

Swagger UI  जैसे टूल में उपरोक्त पता आयात कर इंटरैक्टिव दस्तावेज़ देखा जा सकता है।

- **सामान्य प्रतिक्रिया प्रारूप**:

```json
{
  "code": 0,
  "message": "संचालन सफल",
  "data": {}
}
```

पेजिंग प्रतिक्रिया:
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

## 一、बिज़नेस API (service/ :8787)

### 1. सार्वजनिक इंटरफ़ेस (प्रमाणीकरण की आवश्यकता नहीं)

#### 1.1 वेरिफिकेशन कोड

**`POST /api/captcha/send`** — SMS वेरिफिकेशन कोड भेजें

अनुरोध:
```json
{
  "phone": "13800138000"
}
```
प्रतिक्रिया: `{"code":0,"message":"वेरिफिकेशन कोड भेजा गया","data":null}`

सीमा: हर 60 सेकंड में केवल 1 बार भेजा जा सकता है, वेरिफिकेशन कोड 5 मिनट वैध है।

---

#### 1.2 प्रमाणीकरण

**`POST /api/auth/register`** — मोबाइल नंबर से पंजीकरण

अनुरोध:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "abc123",
  "confirm_password": "abc123",
  "referral_code": "A1B2C3D4"
}
```
प्रतिक्रिया:
```json
{
  "code": 0,
  "message": "पंजीकरण सफल",
  "data": {
    "token": "eyJhbGciOi...",
    "user": {
      "id": "aB3xK9mQ",
      "phone": "138****8000",
      "nickname": "उपयोगकर्ता138****8000",
      "user_type": "customer",
      "active_role": "customer",
      "referral_code": "E5F6G7H8"
    }
  }
}
```

---

**`POST /api/auth/login`** — पासवर्ड से लॉगिन

अनुरोध:
```json
{
  "phone": "13800138000",
  "password": "abc123"
}
```
प्रतिक्रिया: पंजीकरण प्रतिक्रिया के समान, token और user जानकारी सहित।

---

**`POST /api/auth/login-by-code`** — वेरिफिकेशन कोड से लॉगिन

अनुरोध:
```json
{
  "phone": "13800138000",
  "code": "123456"
}
```
प्रतिक्रिया: लॉगिन के समान। बिना पंजीकृत उपयोगकर्ता के लिए खाता स्वतः बनाया जाता है।

---

**`POST /api/auth/forget-password`** — पासवर्ड भूलना

अनुरोध:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "newpass123",
  "confirm_password": "newpass123"
}
```

---

**`POST /api/auth/refresh`** — Token रिफ्रेश करें

अनुरोध हेडर: `Authorization: Bearer <पुराना token>`
प्रतिक्रिया: `{"code":0,"data":{"token":"eyJhbGciOi..."}}`

---

#### 1.3 वीचैट

**`POST /api/wechat/mini-login`** — मिनी प्रोग्राम लॉगिन

अनुरोध: `{"code":"वीचैट लॉगिन code"}`
विवरण: पहली बार लॉगिन के बाद `/api/wechat/phone` कॉल कर मोबाइल नंबर बाइंड करना आवश्यक है।

---

**`POST /api/wechat/phone`** — मोबाइल नंबर बाइंड करें

अनुरोध: `{"code":"वीचैट मोबाइल नंबर घटक code"}`

---

**`POST /api/wechat/oa-login`** — ऑफिशियल अकाउंट लॉगिन

अनुरोध: `{"code":"ऑफिशियल अकाउंट ऑथराइज़ेशन code"}`

---

#### 1.4 सार्वजनिक सेवाएँ

**`GET /api/common/config`** — सार्वजनिक कॉन्फ़िगरेशन

प्रतिक्रिया: समझौता टेक्स्ट (उपयोगकर्ता समझौता/गोपनीयता समझौता/सेवा समझौता), हमारे बारे में जानकारी, संस्करण संख्या शामिल है।

---

**`GET /api/common/area`** — शहर/क्षेत्र सूची

---

#### 1.5 सेवा खोज

**`GET /api/service/categories`** — श्रेणी सूची

पैरामीटर: `?parent_id=0`

---

**`GET /api/service/items`** — सेवा आइटम सूची

पैरामीटर: `?category_id=&page=1&per_page=10&sort=sales`

---

**`GET /api/service/detail/{id}`** — सेवा विवरण

प्रतिक्रिया में शामिल: छवियाँ/नाम/कीमत/विनिर्देश/अवधि/बिक्री/समीक्षा सूची।

---

**`GET /api/service/products`** — उत्पाद सूची

**`GET /api/service/stores`** — स्टोर सूची

पैरामीटर: `?lat=&lng=&city=`

---

#### 1.6 तकनीशियन खोज

**`GET /api/technician/list`** — तकनीशियन सूची

पैरामीटर: `?lat=&lng=&service_id=&page=1`
दूरी के अनुसार निकट से दूर तक क्रमबद्ध, लौटाता है: अवतार/नाम/रेटिंग/ऑर्डर संख्या/पसंदीदा संख्या/दूरी/सबसे पहला उपलब्ध समय/सेवा दे सकता है या नहीं।

---

**`GET /api/technician/detail/{id}`** — तकनीशियन विवरण

प्रतिक्रिया में शामिल: छवियाँ/नाम/परिचय/रेटिंग/दूरी/सेवा आइटम सूची/समीक्षाएँ।

---

**`GET /api/technician/schedule/{id}`** — तकनीशियन शेड्यूल

पैरामीटर: `?date=2026-05-26`
उस तिथि के बुक करने योग्य समय स्लॉट और उपलब्धता स्थिति लौटाता है।

---

#### 1.7 सामग्री

**`GET /api/content/banners`** — कैरोसेल

पैरामीटर: `?position=home`

**`GET /api/content/articles`** — घोषणा/लेख सूची

पैरामीटर: `?type=announcement&page=1`

**`GET /api/content/article/{id}`** — लेख विवरण

---

#### 1.8 LBS

**`GET /api/lbs/nearby-stores`** — आस-पास के स्टोर

पैरामीटर: `?lat=&lng=&radius=5000`

**`GET /api/lbs/geocode`** — रिवर्स जियोकोडिंग

पैरामीटर: `?lat=&lng=`

---

### 2. उपयोगकर्ता इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक)

सभी इंटरफ़ेस अनुरोध हेडर में `Authorization: Bearer <token>` रखते हैं

#### 2.1 व्यक्तिगत प्रोफ़ाइल

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/user/profile` | व्यक्तिगत जानकारी प्राप्त करें |
| PUT | `/api/user/profile` | निकनेम/अवतार/लिंग अपडेट करें |
| POST | `/api/user/change-password` | पासवर्ड बदलें (old_password/new_password/confirm_password) |
| POST | `/api/user/change-phone` | मोबाइल बदलें (old_code/new_phone/new_code) |
| POST | `/api/user/cancel-account` | खाता हटाएँ (पासवर्ड सत्यापन आवश्यक) |
| POST | `/api/user/logout` | लॉगआउट (token ब्लैकलिस्ट में जाता है) |
| POST | `/api/user/switch-role` | भूमिका स्विच करें (role: customer/technician) |

technician पर स्विच करने के लिए approved स्थिति वाली तकनीशियन प्रोफ़ाइल आवश्यक है।

#### 2.2 पता प्रबंधन

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/user/addresses` | पता सूची |
| POST | `/api/user/addresses` | नया पता (contact_name/contact_phone/province/city/district/detail/lat/lng/is_default) |
| GET | `/api/user/addresses/{id}` | पता विवरण |
| PUT | `/api/user/addresses/{id}` | पता अपडेट करें |
| DELETE | `/api/user/addresses/{id}` | पता हटाएँ |

डिफ़ॉल्ट सेट करने पर अन्य डिफ़ॉल्ट पते स्वतः रद्द हो जाते हैं।

#### 2.3 पसंदीदा

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/user/favorites` | पसंदीदा सूची (?type=service/technician) |
| POST | `/api/user/favorites` | पसंदीदा जोड़ें (target_type/target_id) |
| DELETE | `/api/user/favorites/{id}` | पसंदीदा रद्द करें |

#### 2.4 फ़ीडबैक

`POST /api/user/feedback` — फ़ीडबैक सबमिट करें (content + images ऐरे)

#### 2.5 प्रमोशन/रेफरल

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/user/referral` | प्रमोशन जानकारी (रेफरल कोड/रेफरल संख्या/पहले ऑर्डर वाले संख्या/प्राप्त पॉइंट्स) |
| GET | `/api/user/referral/qrcode` | प्रमोशन QR कोड (रेफरल कोड + आमंत्रण लिंक) |
| GET | `/api/user/referral/referred-users` | रेफर किए गए उपयोगकर्ताओं की सूची |
| GET | `/api/user/referral/earnings` | डिस्ट्रीब्यूशन कमीशन विवरण (पेजिंग: रेफर किए गए व्यक्ति का निकनेम/अवतार/ऑर्डर नंबर/राशि/जारी करने का समय) |

**डिस्ट्रीब्यूशन कमीशन**: रेफर किए गए व्यक्ति के पहले ऑर्डर के completed होने पर जारी, राशि = paid_amount × reward_rate (erik_system_config referral.reward_rate, डिफ़ॉल्ट 0.05, अवैध मान पर कॉन्स्टेंट पर वापसी)। रो-लॉक + rewarded_at रिक्त जाँच + पहले ऑर्डर की पुनर्जाँच तीन-स्तरीय आइडेम्पोटेंसी; WalletTxn type=referral_reward में जमा।

#### 2.6 पॉइंट्स ट्रांसफर (राउंड 19)

| विधि | पथ | विवरण |
|------|------|------|
| POST | `/api/user/points/transfer` | पॉइंट्स ट्रांसफर (to_user_id hashid/points) |
| GET | `/api/user/points/transfers` | ट्रांसफर रिकॉर्ड (?direction=sent/received&page=1) |

**पॉइंट्स ट्रांसफर**: प्राप्तकर्ता hashid डिकोड + अस्तित्व जाँच 404, स्वयं को ट्रांसफर 422, पॉइंट्स 1-10000 422, बैलेंस SUM एग्रीगेशन अपर्याप्त 422, दैनिक संचय 10000 सीमा 422। समवर्ती सुरक्षा: Redis NX लॉक points_transfer:{user} 30s → ट्रांज़ैक्शन में दोनों पक्षों की अंतिम लेन-देन lockForUpdate (user_id आरोही क्रम, आपसी ट्रांसफर डेडलॉक रोकता है) → लॉक के भीतर बैलेंस/सीमा/प्राप्तकर्ता पुनर्जाँच। लेन-देन मानक: भेजने वाला type=consume/source=points_transfer नकारात्मक मान (balance=पिछली स्नैपशॉट-वर्तमान), प्राप्तकर्ता type=earn/source=points_transfer सकारात्मक मान expires_at सहित (PointsExpiryTimer सामान्य रूप से समाप्त कर सकता है); commit के बाद प्राप्तकर्ता को इन-ऐप नोटिफिकेशन type='points_received' (विफलता पर केवल warn)।

#### 2.7 मैसेज प्रेफरेंस सेटिंग (राउंड 19)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/user/notify-settings` | नोटिफिकेशन स्विच क्वेरी (5 प्रकार पूर्ण) |
| PUT | `/api/user/notify-settings` | बैच स्विच अपडेट (types: {service_reminder: 0/1, ...}) |

**नोटिफिकेशन स्विच**: erik_user_notify_setting टेबल (user_id+type कम्पोज़िट यूनिक कुंजी, डिफ़ॉल्ट पंक्ति=डिफ़ॉल्ट चालू)। 5 प्रकार: service_reminder सेवा रिमाइंडर / card_expiry एक्सपायरी रिमाइंडर (कार्ड+कूपन एकीकृत छाता)/ points_expiry पॉइंट्स एक्सपायरी / marketing मार्केटिंग (रिज़र्व)/ system सिस्टम (बंद नहीं किया जा सकता, PUT पर 1 में बाध्य)। गेटिंग: notifySettingEnabled ServiceReminderTimer/ExpiryReminderTimer/PointsExpiryTimer 3 टाइमर प्रोसेस + सब्सक्रिप्शन इवेंट सीन मैपिंग से जुड़ा (PAY/REFUND/VERIFIED/RESCHEDULE→system हमेशा भेजा जाता है, REMINDER→service_reminder, EXPIRY→card_expiry); प्रकार बंद होने पर इन-ऐप नोटिफिकेशन और सब्सक्रिप्शन मैसेज दोनों छोड़ दिए जाते हैं।

---

### 3. तकनीशियन इंटरफ़ेस (JWT + तकनीशियन पहचान आवश्यक)

#### 3.1 तकनीशियन प्रोफ़ाइल

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/technician/profile` | तकनीशियन प्रोफ़ाइल प्राप्त करें |
| PUT | `/api/technician/profile` | प्रोफ़ाइल अपडेट करें (avatar/intro/real_name/gender/id_card/id_card_front/id_card_back) |

पहली बार पूर्ण रूप से भरना आवेदन माना जाता है, status=pending ऑडिट की प्रतीक्षा।

#### 3.2 शेड्यूल

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/technician/schedule` | शेड्यूल क्वेरी (?start_date=&end_date=) |
| PUT | `/api/technician/schedule` | शेड्यूल सेट करें (date/time_slots/status), समय स्लॉट ओवरलैप 422「मौजूदा शेड्यूल समय से टकराव」 |
| POST | `/api/technician/schedule/batch` | बैच शेड्यूल (राउंड 23): दिनांक सीमा ≤7 दिन + weekdays फ़िल्टर, मौजूदा शेड्यूल वाले दिन छोड़े जाते हैं, प्रतिक्रिया created/skipped |

#### 3.3 तकनीशियन ऑर्डर

`GET /api/technician/orders` — ऑर्डर सूची (?status=&page=1)

#### 3.4 आय

`GET /api/technician/earnings` — आय अवलोकन (today_income/pending_settlement/balance + लेन-देन सूची)

#### 3.5 विड्रॉल

`POST /api/technician/withdraw` — विड्रॉल आवेदन (amount)
नियम: हर महीने 20 तारीख को विड्रॉल, T+1 में जमा, न्यूनतम राशि/पूर्ण-सौ की सीमा बैकएंड कॉन्फ़िगरेशन द्वारा।

**इन-फ्लाइट रिज़र्वेशन (2026-08-26)**: आवेदन पर बैलेंस से इन-फ्लाइट (pending/approved) राशि तुरंत कटती है; अनुमोदन ट्रांसफर से पहले पुनर्जाँच settled − withdrawn − इन-फ्लाइट ≥ विड्रॉल राशि; समवर्ती अनुमोदन से डबल-पेमेंट नहीं होता।

#### 3.6 समीक्षा उत्तर (राउंड 18)

`POST /api/technician/review/reply/{order_id}` — तकनीशियन समीक्षा उत्तर (reply)। समीक्षा मौजूद नहीं/स्वयं की नहीं → एकीकृत 404 (अस्तित्व लीक नहीं होता); मौजूदा उत्तर 422 (आइडेम्पोटेंट अस्वीकृति, ओवरराइट नहीं); खाली उत्तर 422। उत्तर सफल होने पर उपयोगकर्ता को इन-ऐप नोटिफिकेशन (type='review_reply')।

#### 3.7 वर्कबेंच

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/technician/work/today` | आज के कार्य सूची |
| GET | `/api/technician/work/records` | पूर्ण रिकॉर्ड पेजिंग |
| POST | `/api/technician/work/{id}/start` | सेवा शुरू करें |
| POST | `/api/technician/work/{id}/complete` | सेवा पूर्ण करें |

**आज के कार्य**: status ∈ [confirmed, serving], service_time आज का या खाली, लौटाता है service_name/price/nickname/avatar।

**पूर्ण रिकॉर्ड**: status ∈ [serving, completed], service_end_at के अनुसार उल्टे क्रम, पेजिंग प्रतिक्रिया meta सहित।

**सेवा शुरू/पूर्ण करें**: रो-लॉक + स्टेट मशीन जाँच, आइडेम्पोटेंट संचालन। सेवा शुरू करने पर service_start_at लिखा जाता है; सेवा पूर्ण करने पर service_end_at लिखा जाता है और इन-ऐप नोटिफिकेशन भेजा जाता है। त्रुटि कोड: स्वयं का नहीं 403, स्थिति गलत 422, अवैध hashid 422।

---

### 4. ऑर्डर इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक)

| विधि | पथ | विवरण |
|------|------|------|
| POST | `/api/order` | ऑर्डर बनाएँ (order_type/items/store_id/technician_id/service_time/coupon_id/user_coupon_id/promotion_id/remark) |
| GET | `/api/order/list` | ऑर्डर सूची (?status=&page=1) |
| GET | `/api/order/detail/{id}` | ऑर्डर विवरण |
| POST | `/api/order/cancel/{id}` | ऑर्डर रद्द करें (reason) |
| POST | `/api/order/pay/{id}` | भुगतान शुरू करें (pay_channel: wechat/balance, use_points: वैकल्पिक पॉइंट्स कैश) |
| POST | `/api/order/refund/{id}` | रिफंड आवेदन |
| POST | `/api/order/verify/{id}` | वेरिफिकेशन (code: QR कोड मान) |
| POST | `/api/order/reschedule/{id}` | अपॉइंटमेंट रीशेड्यूल (new_service_time अनिवार्य/reason वैकल्पिक) |
| GET | `/api/order/logistics/{id}` | लॉजिस्टिक्स ट्रैकिंग (राउंड 19, product ऑर्डर) |
| POST | `/api/order/review/{order_id}` | समीक्षा सबमिट करें (rating 1-5/content/images) (राउंड 19 पंजीकरण) |
| POST | `/api/order/review/{order_id}/append` | समीक्षा फॉलो-अप (content/images अल्पविराम से अलग) (राउंड 19) |

**ऑर्डर स्थिति**: pending(भुगतान शेष) → paid(भुगतान किया) → confirmed(पुष्टि) → serving(सेवा में) → completed(पूर्ण)

**ऑर्डर बनाते समय**: Redis SETNX तकनीशियन को 3 मिनट लॉक करता है, पेज छोड़ने या टाइमआउट पर रिलीज़।

**कीमत टैम्पर-रोधी (2026-08-26)**: ऑर्डर आइटम राशि हमेशा डेटाबेस रिकॉर्ड के अनुसार (target_type=service erik_service में, product erik_product में), क्लाइंट द्वारा भेजी गई कीमत गणना में भाग नहीं लेती; अज्ञात target_type 422; target_id hashid एन्कोडेड मान होना चाहिए (raw id भेजने पर डिकोड 0 → 422「उत्पाद मौजूद नहीं या अनलिस्टेड」); ग्रुप बाय/सेकिल कीमत भी DB पर आधारित।

**रिफंड नियम**: ऑर्डर के 15 मिनट के भीतर या शुरुआत से >6h पहले 100% / ≤6h 90% / शुरू हो चुका 80% / शुरुआत पुष्टि के बाद कोई रिफंड नहीं।

**कूपन कटौती**: ऑर्डर बनाते समय वैकल्पिक user_coupon_id (hashid) भेजें। त्रुटि कोड: दूसरे का कूपन 404, न्यूनतम-सीमा अपर्याप्त/एक्सपायर्ड/अनलिस्टेड/उपयोग किया गया 422, अवैध hashid 422। दो-चरणीय कटौती: ऑर्डर बनाते समय PriceCalculator.applyCoupon केवल-पठन जाँच कर discount_amount में कटौती राशि लिखती है; भुगतान सफल होने पर consume कूपन को used करता है; रिफंड पर restoreCouponAndCard आइडेम्पोटेंट रूप से लौटाता है।

**बैलेंस भुगतान और रिफंड**: भुगतान अनुरोध बॉडी में `pay_channel: "balance"` भेजने पर वॉलेट बैलेंस उपयोग होता है; वीचैट रिफंड और बैलेंस रिफंड दोनों राशि वॉलेट बैलेंस में वापस जमा करते हैं।

**पॉइंट्स कैश**: भुगतान अनुरोध बॉडी में वैकल्पिक `use_points` (पूर्णांक) भेजें। SUM एग्रीगेशन से पॉइंट्स बैलेंस जाँच (erik_user_points का balance कॉलम एकल-इंक्रीमेंट स्नैपशॉट है, सीधे बैलेंस के रूप में उपयोग नहीं किया जा सकता), कटौती = floor(use_points / config('app.points_rate', 100)) युआन, वास्तविक भुगतान = मूल देय − कटौती (निचली सीमा 0.01, देय से अधिक पर देय अनुसार पूर्ण कटौती, पॉइंट्स बर्बाद नहीं)। सफल होने पर type=consume/source=points_offset उपभोग लेन-देन लिखा जाता है (आइडेम्पोटेंट, रीट्राई पर दोहरा कटौती नहीं)। बैलेंस अपर्याप्त 422।

**पॉइंट्स वापसी**: रद्द/रिफंड पर points_offset द्वारा उपभोग किए गए पॉइंट्स वापस (type=earn/source=points_refund): रद्द पूर्ण, रिफंड अनुपात के अनुसार, 5 जोड़ बिंदु आइडेम्पोटेंट (refundOffsetPoints)।

**ग्रुप बाय ऑर्डर (राउंड 16)**: ऑर्डर बनाते समय वैकल्पिक `promotion_id` (hashid) भेजें। जाँच: केवल group_buy प्रकार, गतिविधि वैध अवधि में, कॉलर भागीदार है, पूर्ण नहीं (पूर्ण हुआ लॉक 422), ऑर्डर सेवा गतिविधि से मेल खाती है; ग्रुप बाय कीमत = मूल कीमत × discount_percent/100, कूपन/सेशन कार्ड/पॉइंट्स स्टैकिंग निषिद्ध (कोई भी भेजने पर 422)। ऑर्डर में promotion_id/participant_id लिखा जाता है; भुगतान पूरी तरह `POST /api/order/pay/{id}` दोहराता है, pay पर लेज़ी निर्धारण कि गतिविधि बंद हो गई (समय समाप्त पर पूर्ण नहीं) → ऑर्डर स्वतः रद्द और तकनीशियन लॉक रिलीज़।

**सेकिल ऑर्डर (राउंड 18, बंद)**: ~~ऑर्डर बनाते समय `promotion_id` (flash_sale प्रकार) भेजें~~ — 2026-08 से पुराना प्रमोशन FLASH_SALE चैनल हटा दिया गया, store() प्रमोशन शाखा में केवल ग्रुप बाय GROUP_BUY बचा (गैर-ग्रुप बाय promotion 422); सेकिल एकीकृत रूप से राउंड 24 के `/api/seckill` चैनल से होता है (seckill_id store ट्रांज़ैक्शन में रो-लॉक इन्वेंट्री कटौती में इंजेक्ट होता है), PromotionController::index flash_sale फ़िल्टर करता है, show/join उस पर 400 लौटाता है, `Promotion::TYPE_FLASH_SALE` कॉन्स्टेंट ऐतिहासिक डेटा संगतता के लिए रिटेन किया गया है।

**अपॉइंटमेंट रीशेड्यूल (राउंड 17)**: `POST /api/order/reschedule/{id}` पर new_service_time (अनिवार्य) + reason (वैकल्पिक) भेजें, उसी तकनीशियन के साथ समय बदलें। नियम: केवल स्वयं का ऑर्डर (स्वयं का नहीं 404), केवल appointment प्रकार और स्थिति pending/paid/confirmed में बदला जा सकता है (बाकी 422), मूल सेवा शुरुआत से ≥ 6 घंटे (पूर्ण रिफंड विंडो के समान) पर ही बदला जा सकता है। समवर्ती सुरक्षा: B1 order_lock (pay/cancel/refund के साथ एक ही म्यूटेक्स परिवार) → नए समय स्लॉट के लिए तकनीशियन लॉक Redis SETNX EX 180 (समवर्ती रीशेड्यूल पर ओवरसेलिंग रोकता है) → ट्रांज़ैक्शन में रो-लॉक पुनर्पठन + B2 शेड्यूल टकराव DB जाँच (यह ऑर्डर छोड़कर) → service_time अपडेट + erik_order_reschedule रिकॉर्ड → मूल स्लॉट लॉक रिलीज़, नया स्लॉट लॉक इस ऑर्डर के पास → SCENE_RESCHEDULE सब्सक्रिप्शन मैसेज (कॉन्फ़िगर न होने पर इन-ऐप नोटिफिकेशन डिग्रेड)। विफल पथ पर ट्रांज़ैक्शन रोलबैक के साथ नया स्लॉट लॉक भी रिलीज़।

**लॉजिस्टिक्स ट्रैकिंग (राउंड 19)**: `GET /api/order/logistics/{id}` — केवल स्वयं का product ऑर्डर देख सकता है (स्वयं का नहीं/उत्पाद नहीं/शिप नहीं हुआ एकीकृत 404)। order.remark JSON पढ़ता है (shipping_company/tracking_no/shipped_at, admin MallOrderController::ship() शिप करते समय लिखता है), parseShippingInfo/parseReceiver दोहरी पार्सिंग पुराने फ़ॉर्मैट के लिए फॉलबैक; प्राप्तकर्ता मोबाइल नंबर मास्क 138****5678।

**समीक्षा (राउंड 19)**: `POST /api/order/review/{order_id}` समीक्षा सबमिट (rating अनिवार्य 1-5, content/images वैकल्पिक): स्वयं का नहीं 404, non-completed 422, दोहरी समीक्षा 400। `POST /api/order/review/{order_id}/append` फॉलो-अप (content अनिवार्य, images अल्पविराम से अलग): समीक्षा मौजूद नहीं/स्वयं की नहीं एकीकृत 404, non-completed 422, दोहरा फॉलो-अप 422, खाली सामग्री 422; सफल होने पर append_content/append_images(JSON)/append_at लिखता है और तकनीशियन को इन-ऐप नोटिफिकेशन type='review_append', प्रतिक्रिया में append फ़ील्ड दिखती है।

### 4.1 आफ्टर-सेल इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक)

| विधि | पथ | विवरण |
|------|------|------|
| POST | `/api/aftersales` | आफ्टर-सेल आवेदन (order_id hashid/type: refund|exchange/reason), स्वयं के ऑर्डर की जाँच 404, स्थिति paid+completed पर ही आवेदन 422, उसी ऑर्डर की चालू आफ्टर-सेल डेडुप 422 |
| GET | `/api/aftersales` | मेरे आफ्टर-सेल सूची (?status=&page=1&limit=) |
| GET | `/api/aftersales/{id}` | आफ्टर-सेल विवरण (स्वामित्व जाँच 404) |

**आफ्टर-सेल स्थिति**: pending(ऑडिट शेष) → approved(पास) / rejected(अस्वीकृत)। approved केवल स्थिति परिवर्तन है, रिफंड क्रिया `POST /api/order/refund/{id}` उपयोग करती है।

---

### 4.2 ग्रुप बाय/प्रमोशन इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक; FLASH_SALE बंद)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/promotions` | गतिविधि सूची (?type=group_buy; flash_sale फ़िल्टर होकर वापस नहीं आती) |
| GET | `/api/promotions/{id}` | गतिविधि विवरण (भागीदारी संख्या/पूर्ण हुई या नहीं सहित; flash_sale प्रकार 400) |
| GET | `/api/promotions/{id}/participants` | भागीदार सूची |
| POST | `/api/promotions/join/{id}` | गतिविधि में भाग लें (राउंड 15 पूर्णता: प्रतिक्रिया में discount_percent/original_price/group_price; flash_sale प्रकार 400) |

**भागीदारी नियम**: group_buy पूर्ण (≥min_people) लॉक, पूर्ण हुई गतिविधि में नया भागीदारी 422; समय समाप्त पर अपूर्ण लेज़ी बंद (show/join पर status 0)। join के बाद ग्रुप बाय कीमत पर ऑर्डर देखें「ग्रुप बाय ऑर्डर (राउंड 16)」。सेकिल अब इस चैनल से नहीं जाता, देखें「24. सेकिल इंटरफ़ेस」。

---

### 5. मार्केटिंग इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/marketing/coupons` | कूपन सूची (?status=available/used/expired) |
| POST | `/api/marketing/coupons/receive` | कूपन प्राप्त करें (coupon_id) |
| GET | `/api/marketing/cards` | सदस्यता कार्ड सूची |
| POST | `/api/marketing/cards/buy` | सदस्यता कार्ड खरीदें (card_id) |
| GET | `/api/marketing/cards/my` | मेरे सेशन कार्ड सूची |
| POST | `/api/marketing/cards/use` | सेशन कार्ड वेरिफाई करें (user_card_id/service_id/remark?) |
| GET | `/api/marketing/gift-cards` | गिफ्ट कार्ड सूची |
| GET | `/api/marketing/gift-cards/my` | मेरे गिफ्ट कार्ड (redeem रिकॉर्ड) |
| POST | `/api/marketing/gift-cards/redeem` | गिफ्ट कार्ड रिडीम करें (cash प्रकार रिडीम पर वॉलेट बैलेंस में जमा) |
| GET | `/api/marketing/points` | पॉइंट्स लेन-देन (?type=earn/use/expire&source=order/referral/gift_card/check_in/admin) |
| GET | `/api/marketing/points-exchange` | पॉइंट्स एक्सचेंज उत्पाद सूची (लिस्टेड + वास्तविक समय शेष स्टॉक + एक्सचेंज की गई संख्या) |
| POST | `/api/marketing/points-exchange/{id}` | एक्सचेंज (type=coupon कूपन जारी / wallet जमा / gift_card कार्ड-पिन वापसी) |
| POST | `/api/marketing/coupons/transfer` | ट्रांसफर कोड बनाएँ (user_coupon_id: 8-अंकीय यूनिक कोड/7 दिन वैध) |
| POST | `/api/marketing/coupons/claim` | ट्रांसफर कूपन प्राप्त करें (code) |
| GET | `/api/marketing/coupons/transfers` | ट्रांसफर रिकॉर्ड (भेजा गया pending/claimed/expired + प्राप्त claimed) |

**सेशन कार्ड**: cards/my लौटाता है card_id/name/type/services/total_times/used_times/remaining_times/start_at/end_at/status (वास्तविक समय गणना)। वेरिफिकेशन सफल {order_id, usage_id, remaining_times}; त्रुटि कोड: अवैध hashid 422, बार-संख्या अपर्याप्त 422, एक्सपायर्ड 400, स्वयं का नहीं 404, Redis डेडुप 400।

**गिफ्ट कार्ड**: gift-cards/my लौटाता है redeem रिकॉर्ड (type/amount/gift_name/status/used_at)।

**पॉइंट्स नियम**: विवरण पेजिंग, type फ़िल्टर (earn/use/expire), source फ़िल्टर (order/referral/gift_card/check_in/admin)। चेक-इन पॉइंट्स (CheckIn, type=earn); उपभोग पॉइंट्स floor(paid_amount×1), वेरिफिकेशन पर जारी और आइडेम्पोटेंट; रिफंड अनुपात के अनुसार पॉइंट्स वापस।

**पॉइंट्स एक्सपायरी (राउंड 17)**: erik_user_points.expires_at कॉलम (कॉन्फ़िग points.expiry_days, डिफ़ॉल्ट 365 दिन, ≤0 कभी एक्सपायर नहीं), सभी earn लिखते समय वैधता अवधि भरें; PointsExpiryTimer टाइमर प्रोसेस हर 60s कर्सर स्कैन एक्सपायर्ड earn पंक्तियाँ, type=expire नकारात्मक मान कटौती पंक्ति लिखता है (source=expiry + order_id मूल लेन-देन ट्रेस, तीन-स्तरीय आइडेम्पोटेंसी) + एग्रीगेट इन-ऐप नोटिफिकेशन「आपके X पॉइंट्स एक्सपायर हुए」; उपलब्ध बैलेंस SUM गणना में expire नकारात्मक पंक्तियाँ शामिल हैं, एक्सपायर्ड पॉइंट्स कैश/एक्सचेंज में उपयोग नहीं हो सकते।

**कूपन ट्रांसफर (राउंड 17)**: transfer जाँच करता है कि कूपन स्वयं का है/available/कूपन परिभाषा एक्सपायर्ड नहीं/पहले ट्रांसफर नहीं हुआ, 8-अंकीय डिऑबफस्केटेड यूनिक ट्रांसफर कोड बनाता है (uk_code यूनिक इंडेक्स फॉलबैक), 7 दिन वैध। claim दुरुपयोग-रोधी: Redis NX लॉक (coupon_transfer_claim:{code} 30s) + रो-लॉक पुनर्जाँच डबल-स्पेंड रोकता है, uk_user_coupon यूनिक इंडेक्स एक ही कूपन केवल एक बार ट्रांसफर की सीमा देता है, ट्रांसफर किया गया कूपन दोबारा ट्रांसफर नहीं हो सकता (नया कूपन ट्रांसफर रिकॉर्ड न होने से स्वतः अवरुद्ध), अपना ट्रांसफर कूपन प्राप्त नहीं कर सकता 422, प्राप्तकर्ता मूल धारक नहीं; लेज़ी एक्सपायरी निर्धारण पर expired किया जाता है और मूल कूपन available बहाल; claim ट्रांज़ैक्शन में मूल कूपन used + नया UserCoupon प्राप्तकर्ता से बंधा (coupon_id अपरिवर्तित अर्थात वैधता अपरिवर्तित) + रिकॉर्ड claimed।

---

### 6. नोटिफिकेशन इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/notification` | नोटिफिकेशन सूची (?type=order/system&page=1) |
| PUT | `/api/notification/read/{id}` | पढ़ा हुआ चिह्नित करें |
| PUT | `/api/notification/read-all` | सभी पढ़े हुए |

---

### 7. वॉलेट इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/wallet` | वॉलेट बैलेंस + लेन-देन पेजिंग |
| POST | `/api/wallet/recharge` | रिचार्ज ऑर्डर बनाएँ (amount: युआन) |
| POST | `/api/wallet/recharge/{id}/pay` | रिचार्ज ऑर्डर भुगतान शुरू करें (वीचैट) |
| POST | `/api/wallet/transfer` | बैलेंस ट्रांसफर (to_user_id hashid/amount/remark वैकल्पिक/client_token वैकल्पिक) (राउंड 19) |
| GET | `/api/wallet/transfers` | ट्रांसफर रिकॉर्ड (?direction=out/in&page=1) (राउंड 19) |
| GET | `/api/wallet/transfers/{id}` | ट्रांसफर विवरण (केवल दोनों पक्ष देख सकते हैं, अन्य 404) (राउंड 19) |

**लेन-देन**: wallet_txn प्रकार: recharge / consume / refund / gift_card / referral_reward(डिस्ट्रीब्यूशन कमीशन) / referral_level2(दूसरे-स्तर कमीशन) / points_exchange(पॉइंट्स एक्सचेंज जमा), पेजिंग वापसी।

**रिचार्ज**: `POST /api/wallet/recharge` पर amount (युआन) भेजकर रिचार्ज ऑर्डर बनाएँ, रिचार्ज ऑर्डर hashid लौटाता है। `POST /api/wallet/recharge/{id}/pay` वीचैट भुगतान शुरू करता है, प्रतिक्रिया में sign_params (ऑर्डर भुगतान मोड के समान); भुगतान कॉलबैक R उपसर्ग वाले out_trade_no से रिचार्ज ऑर्डर और ऑर्डर अलग करता है।

**बैलेंस भुगतान**: ऑर्डर भुगतान अनुरोध बॉडी में `pay_channel: "balance"` भेजने पर वॉलेट बैलेंस उपयोग होता है; वीचैट रिफंड और बैलेंस रिफंड दोनों राशि वॉलेट बैलेंस में वापस जमा करते हैं।

**बैलेंस ट्रांसफर (राउंड 19)**: `POST /api/wallet/transfer` — प्राप्तकर्ता hashid डिकोड + अस्तित्व जाँच 404, स्वयं को ट्रांसफर 422, राशि 0.01-1000/लेन-देन 422 (DECIMAL तुलना, float निषिद्ध), बैलेंस अपर्याप्त 422, दैनिक संचय 5000 युआन 422। समवर्ती/आइडेम्पोटेंसी: Redis NX लॉक wallet_transfer:{from} 30s भेजने वाले को सीरियलाइज़ करता है → ट्रांज़ैक्शन में दोनों पक्षों के वॉलेट पंक्तियाँ user_id आरोही क्रम में lockForUpdate (निश्चित क्रम डेडलॉक रोकता है) → भेजने वाले से कटौती + प्राप्तकर्ता को जोड़ + WalletTxn डबल लेन-देन (transfer_out/transfer_in balance_after स्नैपशॉट सहित) + ट्रांसफर रिकॉर्ड completed + प्राप्तकर्ता को इन-ऐप नोटिफिकेशन type='balance_received' (विफलता पर केवल लॉग)। client_token वैकल्पिक: सफलता के बाद SETNX 24h दोहरा सबमिशन रोकता है (विफल अनुरोध token नहीं लिखता, रीट्राई किया जा सकता है)।

---

### 8. स्टोर मैनेजर वर्कबेंच इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/store-manager/overview` | आज का अवलोकन (आज के ऑर्डर संख्या/आज की आय/चालू/तकनीशियन संख्या/वेरिफिकेशन संख्या) |
| GET | `/api/store-manager/orders` | स्टोर ऑर्डर सूची (?status=&page=&limit=) |
| GET | `/api/store-manager/technicians` | तकनीशियन सूची (आज का शेड्यूल सहित) |
| GET | `/api/store-manager/revenue` | हाल के 7 दिनों की आय एग्रीगेशन |

**store_id अलगाव**: requireStoreId() वर्तमान उपयोगकर्ता को स्टोर से बंधने के लिए बाध्य करता है (erik_user.store_id), स्टोर नहीं 403; सभी क्वेरी store_id से फ़िल्टर होती हैं।

---

### 9. ग्रोथ लेवल इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक, राउंड 20)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/growth` | वर्तमान ग्रोथ अवलोकन (balance/स्तर/अगली स्लॉट अंतर/स्तर नाम) |
| GET | `/api/growth/records` | ग्रोथ वैल्यू लेन-देन पेजिंग (?page=&limit=) |
| GET | `/api/growth/levels` | स्लॉट सूची (सार्वजनिक, लॉगिन आवश्यक नहीं) |

**ग्रोथ वैल्यू जमा**: चेक-इन +10; समीक्षा सबमिट +20 (फॉलो-अप जमा नहीं); उपभोग floor(paid) हर 1 युआन पर 1 पॉइंट (भुगतान कॉलबैक में स्थिति पुनर्जाँच आइडेम्पोटेंट, दोहरा कॉलबैक दोहरी जमा नहीं)।

### 10. इनवॉइस इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक, राउंड 20)

| विधि | पथ | विवरण |
|------|------|------|
| POST | `/api/invoices` | इनवॉइस आवेदन (order_id hashid/order_type: service=सेवा/points_exchange=पॉइंट्स एक्सचेंज/order_type डिफ़ॉल्ट service; राशि और शीर्षक सर्वर-पक्ष से, टैम्पर नहीं किया जा सकता) |
| GET | `/api/invoices` | इनवॉइस सूची (?status=&page=) |
| GET | `/api/invoices/{id}` | इनवॉइस विवरण (केवल स्वयं) |

**डेडुप**: uk_order_type(order_id, order_type) यूनिक कुंजी, एक ही ऑर्डर एक ही प्रकार का दोहरा आवेदन 422 (MySQL 1062 कैच फॉलबैक सहित)।

### 11. ग्राहक सेवा टिकट इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक, राउंड 20)

| विधि | पथ | विवरण |
|------|------|------|
| POST | `/api/tickets` | टिकट सबमिट करें (title/content अनिवार्य) |
| GET | `/api/tickets` | टिकट सूची (?status=open/closed&page=) |
| GET | `/api/tickets/{id}` | टिकट विवरण (केवल स्वयं, अन्य 404) |
| POST | `/api/tickets/{id}/close` | टिकट बंद करें (केवल स्वयं/केवल open; वैकल्पिक rating 1-5 संतुष्टि स्कोर, सीमा से बाहर/गैर-पूर्णांक 422, न दिए जाने पर NULL संगत) |

### 12. अपॉइंटमेंट कैलेंडर इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक, राउंड 20)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/calendar/technician/{id}` | माह दृश्य (?month=YYYY-MM): शेड्यूल time_slots घंटे स्लॉट में विस्तार + बुक किए गए बाहर |
| GET | `/api/calendar/technician/{id}/day` | दिन दृश्य (?date=YYYY-MM-DD): उस दिन बुक करने योग्य/बुक किया/बुक नहीं कर सकने वाले स्लॉट विवरण |

### 13. इनवॉइस शीर्षक इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक, राउंड 21)

| विधि | पथ | विवरण |
|------|------|------|
| POST | `/api/invoice-titles` | शीर्षक सहेजें (title_type: personal/company; company पर tax_no अनिवार्य; एक ही उपयोगकर्ता एक ही शीर्षक दोहरा 422; पहला स्वतः डिफ़ॉल्ट) |
| GET | `/api/invoice-titles` | शीर्षक सूची (डिफ़ॉल्ट शीर्ष पर) |
| PUT | `/api/invoice-titles/{id}` | शीर्षक संपादित करें (केवल स्वयं) |
| DELETE | `/api/invoice-titles/{id}` | शीर्षक हटाएँ (केवल स्वयं; डिफ़ॉल्ट हटाने पर सबसे पुराना स्वतः चुना जाता है) |
| POST | `/api/invoice-titles/{id}/default` | डिफ़ॉल्ट सेट करें (ट्रांज़ैक्शन में उसी उपयोगकर्ता की अन्य पंक्तियाँ शून्य) |

**आवेदन एकीकरण**: POST /api/invoices पर वैकल्पिक title_id — शीर्षक पार्स कर invoice_title/tax_no/title_type स्वतः लाता है, title_id न होने पर मूल मैनुअल-भरण पथ बना रहता है।

### 14. ब्राउज़िंग हिस्ट्री इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक, राउंड 21)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/browse-history` | हाल की ब्राउज़ की गई सेवाएँ (join सेवा नाम/कवर/कीमत/मूल कीमत, viewed_at उल्टे क्रम, per_page डिफ़ॉल्ट 15 अधिकतम 50) |
| DELETE | `/api/browse-history/{item_id}` | एक पंक्ति हटाएँ (केवल स्वयं, अवैध/अन्य 404) |
| DELETE | `/api/browse-history` | हिस्ट्री खाली करें (केवल स्वयं) |

**रिकॉर्ड समय**: सेवा विवरण इंटरफ़ेस सफल एक्सेस के बाद स्वतः रिकॉर्ड (बिना लॉगिन छोड़ें; दोहरा ब्राउज़िंग केवल viewed_at रिफ्रेश, दोहरी इंसर्ट नहीं)।

### 15. फुल-रिडक्शन गतिविधि इंटरफ़ेस (राउंड 22)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/full-reduction-activities` | चालू फुल-रिडक्शन गतिविधि सूची (status=1 और समय वैध अवधि में, रिडक्शन राशि के अनुसार उल्टे क्रम; सार्वजनिक इंटरफ़ेस) |

**ऑर्डर स्टैकिंग नियम**: फुल-रिडक्शन केवल मानक ऑर्डर पर लागू (ग्रुप बाय/सेकिल छोड़ें), कूपन/सेशन कार्ड कटौती के बाद देय राशि से थ्रेशोल्ड तय (threshold), स्टैकिंग क्रम **कूपन/सेशन कार्ड → फुल-रिडक्शन → लेवल डिस्काउंट**; अधिकतम रिडक्शन वाली गतिविधि लें; छूट राशि discount_amount में जुड़ती है, टिप्पणी में「फुल-रिडक्शन: X पर Y कट」जुड़ता है; रिडक्शन के बाद वास्तविक भुगतान निचली सीमा 0.01 युआन।

### 16. मेरे अपॉइंटमेंट ICS निर्यात (JWT प्रमाणीकरण आवश्यक, राउंड 22)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/order/ics` | 90 दिनों के भीतर के वैध ऑर्डर (pending/paid/confirmed/serving) को iCal (RFC5545) में निर्यात करें |

**आउटपुट**: `Content-Type: text/calendar; charset=utf-8` + `Content-Disposition: attachment; filename="my-appointments.ics"`। VEVENT: UID=ऑर्डर ID, TZID=Asia/Shanghai, सारांश「अपॉइंटमेंट: सेवा नाम」(अनुपलब्ध पर「अपॉइंटमेंट」डिग्रेड), विवरण (तकनीशियन/स्टोर/पता, अनुपलब्ध छोड़ें), LOCATION स्टोर नाम; टेक्स्ट RFC5545 के अनुसार एस्केप (\, \; \\ \n) + 75-बाइट पंक्ति फोल्डिंग। कोई ऑर्डर न होने पर वैध खाली कैलेंडर लौटाता है; केवल स्वयं के ऑर्डर निर्यात।

### 17. तकनीशियन उपस्थिति इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक, राउंड 22)

| विधि | पथ | विवरण |
|------|------|------|
| POST | `/api/technician/attendance/check-in` | काम पर चेक-इन (उसी दिन दोहरा 422, यूनिक इंडेक्स समवर्ती फॉलबैक; >10:00 पर देर से चिह्नित) |
| POST | `/api/technician/attendance/check-out` | काम से चेक-आउट (बिना चेक-इन/पहले चेक-आउट 422, रो-लॉक समवर्ती) |
| GET | `/api/technician/attendance` | उस माह की उपस्थिति सूची + उपस्थिति दिन/कुल घंटे/औसत घंटे सारांश (?month=YYYY-MM, अवैध 422) |

### 18. गोपनीयता अनुपालन इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक, राउंड 22)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/privacy/data` | डेटा निर्यात (personal/orders/points/wallet_txns/reviews/addresses/invoices समूह JSON; सर्वर लॉग केवल मास्क मोबाइल + गिनती) |
| POST | `/api/privacy/close-request` | खाता हटाने का आवेदन (बैलेंस 0 नहीं / अधूरे ऑर्डर / चालू टिकट 422; close_status=1 + close_requested_at सेट) |
| POST | `/api/privacy/close-cancel` | हटाने का आवेदन रद्द करें (close_status 1→0) |
| POST | `/api/privacy/close-confirm` | हटाने की पुष्टि (72h पूर्ण होने पर ही; close_status=2 + close_at + phone/nickname user{id} में अनाम + status=0) |

**लॉगिन रोक**: close_status=2 वाले खाते का लॉगिन 403「खाता हटा दिया गया」लौटाता है।

### 19. उपयोगकर्ता स्वास्थ्य प्रोफ़ाइल इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक, राउंड 23)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/health-profile` | मेरी स्वास्थ्य प्रोफ़ाइल क्वेरी (कोई प्रोफ़ाइल न होने पर खाली ऑब्जेक्ट) |
| PUT | `/api/health-profile` | बनाएँ/अपडेट करें (upsert, एक व्यक्ति एक प्रोफ़ाइल; allergies/health_notes अधिकतम 500 अक्षर, preferred_technician_id अस्तित्व जाँच; केवल दिए गए फ़ील्ड अपडेट, प्रतिक्रिया hashid एन्कोडेड) |
| DELETE | `/api/health-profile` | मेरी प्रोफ़ाइल हटाएँ (केवल स्वयं) |

फ़ील्ड: allergies (एलर्जी इतिहास)/health_notes (स्वास्थ्य टिप्पणी)/preferred_technician_id (पसंदीदा तकनीशियन, खाली हो सकता है)।

### 20. वॉलेट भुगतान पासवर्ड इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक, राउंड 23)

| विधि | पथ | विवरण |
|------|------|------|
| POST | `/api/wallet/pay-password/set` | भुगतान पासवर्ड सेट करें (6-अंकीय `\d{6}`; पहले से सेट होने पर पुराना पासवर्ड आवश्यक 422 रोक) |
| POST | `/api/wallet/pay-password/verify` | भुगतान पासवर्ड जाँचें (सही/गलत बूलियन लौटाता है, सहेजा नहीं जाता) |
| POST | `/api/wallet/pay-password/check` | सेट हुआ या नहीं क्वेरी (set: true/false) |

संग्रहण: password_hash() हैश + pay_password_set_at, प्लेनटेक्स्ट कभी नहीं संग्रहीत।

### 21. ऑर्डर स्टेटस टाइमलाइन इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक, राउंड 23)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/order/{id}/timeline` | ऑर्डर स्टेटस परिवर्तन टाइमलाइन (उल्टे क्रम; केवल स्वयं, अन्य का ऑर्डर 404 अस्तित्व लीक नहीं) |

ट्रैकिंग: सबमिट/भुगतान (वीचैट कॉलबैक markOrderPaid एकल उपभोग बिंदु)/रद्द/तकनीशियन पुष्टि/रिफंड आवेदन/रिफंड पास/सेवा शुरू/सेवा पूर्ण/टाइमआउट स्वतः रद्द/बैकएंड संचालन (operator=admin) कुल 8 प्रकार के परिवर्तन।

### 22. पॉइंट्स लकी व्हील इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक, राउंड 23)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/wheel/prizes` | व्हील पुरस्कार सूची (weight/stock संवेदनशील फ़ील्ड छिपे) |
| POST | `/api/wheel/spin` | एक बार ड्रॉ (Redis NX + रो-लॉक समवर्ती रोक; random_int वेटेड ड्रॉ; पॉइंट्स→earn लेन-देन एक्सपायरी समय सहित, बैलेंस→lockForUpdate जमा, कूपन→pending मैनुअल जारी, कोई पुरस्कार नहीं→lose; client_token आइडेम्पोटेंट) |
| GET | `/api/wheel/records` | मेरे ड्रॉ रिकॉर्ड (पेजिंग) |

### 23. गेस्ट मोड इंटरफ़ेस (राउंड 24)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/guest/home` | होमपेज एग्रीगेशन (कैरोसेल/घोषणा/सेवा श्रेणियाँ/लोकप्रिय सेवाएँ, Redis कैश svc:guest:home 300s) |
| GET | `/api/guest/services` | सेवा सूची (?category_id=hashid&sort=newest|sales|price&page/per_page≤50) |
| GET | `/api/guest/services/{id}` | सेवा विवरण (मौजूद नहीं 404) |
| GET | `/api/guest/stores` | स्टोर सूची |
| GET | `/api/guest/technicians` | तकनीशियन सूची (केवल ऑडिट पास; ?service_id=hashid फ़िल्टर; रेटिंग उल्टे क्रम) |

बिना प्रमाणीकरण (केवल ApiVersion मिडलवेयर) के लॉगिन-रहित ब्राउज़िंग प्रवेश द्वार।

### 24. सेकिल इंटरफ़ेस (JWT प्रमाणीकरण आवश्यक, राउंड 24)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/seckill` | सेकिल गतिविधि सूची (status=1 और समय विंडो में; बिकी मात्रा = erik_order.seckill_id ऑर्डर संख्या, शेष स्टॉक सहित) |
| GET | `/api/seckill/{id}` | गतिविधि विवरण (state=not_started/ongoing/ended) |
| POST | `/api/seckill/{id}/buy` | सेकिल ऑर्डर (client_token आइडेम्पोटेंट + Redis NX 30s समवर्ती रोक + गतिविधि जाँच; अब स्टॉक पूर्व-कटौती नहीं) |

**ऑर्डर नियम (2026-08-26 से)**: स्टॉक एकीकृत रूप से `/api/order store()` ट्रांज़ैक्शन में रो-लॉक कटौती, buy केवल प्रवेश जाँच/आइडेम्पोटेंसी करता है; सेकिल कीमत = seckill_price (DB के अनुसार), कूपन/पॉइंट्स/सदस्यता कार्ड स्टैक नहीं; ऑर्डर रद्द पर स्टॉक वापस नहीं; सीधे `/api/order` पर seckill_id भेजने पर भी स्टॉक कटता है।

### 25. APP संस्करण जाँच इंटरफ़ेस (राउंड 24)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/api/app/version?platform=android|ios` | नवीनतम संस्करण जाँच (platform अवैध 422; कोई संस्करण न होने पर खाली ऑब्जेक्ट; सार्वजनिक इंटरफ़ेस) |

प्रतिक्रिया: id/platform/version_code/version_name/force_update (1=अनिवार्य)/changelog/download_url।

---

## 二、प्रबंधन बैकएंड API (admin/ :8787)

अनुरोध हेडर: `Authorization: Bearer <admin_token>`, `API-Version: v1`

### डैशबोर्ड

**`GET /admin/dashboard`** — डैशबोर्ड डेटा

प्रतिक्रिया: user_count / order_count / technician_count / today_revenue + चार्ट डेटा (ऑर्डर मात्रा/राशि/नए उपयोगकर्ता/गतिविधि)

### उपयोगकर्ता प्रबंधन

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/user` | उपयोगकर्ता सूची (?keyword/status/page/per_page) |
| POST | `/admin/user` | नया उपयोगकर्ता |
| GET | `/admin/user/{id}` | उपयोगकर्ता विवरण |
| PUT | `/admin/user/{id}` | उपयोगकर्ता संपादित करें |
| DELETE | `/admin/user/{id}` | उपयोगकर्ता हटाएँ |
| POST | `/admin/user/batch/destroy` | बैच हटाएँ |
| POST | `/admin/user/batch/status` | बैच सक्षम/अक्षम |

### सदस्यता कार्ड प्रबंधन

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/member-cards` | कार्ड सूची (?keyword/status/page/per_page) |
| GET | `/admin/member-cards/{id}` | कार्ड विवरण |
| POST | `/admin/member-cards` | नया कार्ड (services JSON जाँच) |
| PUT | `/admin/member-cards/{id}` | कार्ड अपडेट/ऑन-ऑफ-शेल्फ़ |
| DELETE | `/admin/member-cards/{id}` | कार्ड हटाएँ (उपयोगकर्ता के पास कार्ड होने पर अस्वीकृत) |

अनुमति ID: 365-369।

### स्टोर वर्कबेंच (राउंड 15)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/stores/workbench-overview` | स्टोर वर्कबेंच अवलोकन (?store_id=hashid: आज के ऑर्डर संख्या/आज की आय/चालू/तकनीशियन संख्या/आज के वेरिफिकेशन, गणना सेवा-पक्ष के अनुरूप) |
| GET | `/admin/orders` | ऑर्डर सूची में store_id फ़िल्टर जोड़ा गया (hashid डिकोड) |

अनुमति ID: 372।

### पॉइंट्स एक्सचेंज उत्पाद (राउंड 16)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/points-exchange-goods` | उत्पाद सूची (?keyword/status/page/per_page) |
| POST | `/admin/points-exchange-goods` | नया उत्पाद (type=coupon/gift_card/wallet; coupon पर hashid भेजें, wallet/gift_card पर राशि युआन में) |
| PUT | `/admin/points-exchange-goods/{id}` | उत्पाद अपडेट |
| DELETE | `/admin/points-exchange-goods/{id}` | उत्पाद हटाएँ |
| POST | `/admin/points-exchange-goods/{id}/toggle-status` | ऑन-ऑफ-शेल्फ़ टॉगल |
| GET | `/admin/points-exchange-goods/{id}/exchanges` | एक्सचेंज रिकॉर्ड सूची (उपयोगकर्ता मोबाइल + result स्नैपशॉट सहित) |

अनुमति ID: 373-378।

### कमीशन रिकॉर्ड (राउंड 16)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/referral-rewards` | कमीशन रिकॉर्ड (?keyword=&page=&limit=, केवल जारी किए गए रिकॉर्ड, रेफरर/रेफर किए गए व्यक्ति का निकनेम या मोबाइल फ़िल्टर, hashid एन्कोडेड) |

अनुमति ID: 379।

### तकनीशियन स्तर (राउंड 17)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/technician-tiers/logs` | स्तर परिवर्तन लॉग (join तकनीशियन नाम और पुराना/नया स्तर नाम, hashid एन्कोडेड, पेजिंग) |

अनुमति ID: 380।

**स्वचालित मूल्यांकन**: TierRatingService::evaluate वास्तविक समय आँकड़े (erik_order completed ऑर्डर संख्या + समीक्षा औसत, 1 दशमलव स्थान राउंड) profile.order_count/rating में वापस लिखता है, erik_technician_tier_config (min_orders/min_rating) के अनुसार उच्च से निम्न मिलान, कोई मिलान न होने पर निम्नतम स्तर। केवल अपग्रेड, डाउनग्रेड नहीं (डाउनग्रेड कमीशन दर और कीमत गुणांक को प्रभावित करता है, बैकएंड मैनुअल फॉलबैक; allowDowngrade=true मैनुअल पुनर्मूल्यांकन के लिए); आइडेम्पोटेंट (स्तर समान होने पर केवल आँकड़े सिंक); परिवर्तन erik_technician_tier_log + इन-ऐप नोटिफिकेशन। ट्रिगर बिंदु: WorkController::complete / ReviewController समीक्षा लिखना / ProfileController प्रोफ़ाइल देखते समय लेज़ी निर्धारण।

### समीक्षा उत्तर देखना (राउंड 18)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/reviews/{id}/reply` | समीक्षा उत्तर विवरण (decodeId → find → 404 → decorate आउटपुट; उत्तर न होने पर reply='', reply/replied_at toArray से दिखता है; स्टैटिक रूट resource से पहले) |

अनुमति ID: 381 (slug 'get.admin/reviews/{id}/reply')।

### इनवॉइस प्रबंधन (राउंड 20)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/invoices` | इनवॉइस सूची (?status=pending/issued/rejected&page=) |
| POST | `/admin/invoices/{id}/issue` | इनवॉइस जारी करें (invoice_no अनिवार्य, status→issued + issued_at; आइडेम्पोटेंट: पहले से जारी 422) |
| POST | `/admin/invoices/{id}/reject` | अस्वीकृत करें (reject_reason अनिवार्य, status→rejected; केवल pending अस्वीकृत हो सकता है) |

अनुमति ID: 382 सूची / 383 जारी / 384 अस्वीकृत।

### टिकट प्रबंधन (राउंड 20)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/tickets` | टिकट सूची (?status=&page=, स्टैटिक रूट resource से पहले shadow रोकने के लिए) |
| POST | `/admin/tickets/{id}/reply` | टिकट उत्तर (content अनिवार्य, reply_content/replied_at लिखता है, टिकट open पर लौटता है) |
| GET | `/admin/tickets/satisfaction` | संतुष्टि सारांश (राउंड 21): total/rated_count/unrated_count/average 1 दशमलव/1-5 स्टार distribution गायब स्टार 0; स्टैटिक रूट resource से पहले |

अनुमति ID: 385 टिकट उत्तर / 387 टिकट सूची देखना / 388 टिकट संतुष्टि आँकड़े।

### समीक्षा छवि ऑडिट (राउंड 21)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/review-audit` | छवि वाली समीक्षा सूची (JSON_LENGTH(images)>0, ?status=visible/hidden&page=, join उपयोगकर्ता निकनेम और तकनीशियन नाम, ID hashid एन्कोडेड) |
| POST | `/admin/review-audit/{id}/hide` | समीक्षा छिपाएँ (केवल visible छिप सकता है, अन्यथा 422; छिपने के बाद उपयोगकर्ता-पक्ष तकनीशियन समीक्षा सूची स्वतः अदृश्य) |
| POST | `/admin/review-audit/{id}/restore` | समीक्षा बहाल करें (केवल hidden बहाल हो सकता है, अन्यथा 422) |

अनुमति ID: 389 सूची / 390 छिपाना / 391 बहाल।

### दूसरे-स्तर कमीशन रिकॉर्ड (राउंड 20)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/referral-level2` | दूसरे-स्तर कमीशन रिकॉर्ड (join पहले-स्तर रेफरर और दूसरे-स्तर रेफरर निकनेम, पेजिंग) |

अनुमति ID: 386। जारी नियम: ऑर्डर भुगतान के बाद पहले-स्तर रेफरर के रेफरर को paid×level2_rate (सिस्टम कॉन्फ़िग referral.level2_rate डिफ़ॉल्ट 0.02), uk_order_referred आइडेम्पोटेंट डेडुप।

### उपस्थिति प्रबंधन (राउंड 22)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/attendance` | उपस्थिति रिकॉर्ड (?date=YYYY-MM&name=तकनीशियन नाम&page=; join real_name, ID hashid एन्कोडेड) |
| GET | `/admin/attendance/stats` | तकनीशियन के अनुसार समूह आँकड़े (चेक-इन दिन/कुल घंटे/औसत घंटे; ?date=YYYY-MM, अवैध 422) |

अनुमति ID: 392 सूची / 393 आँकड़े।

### फुल-रिडक्शन गतिविधि प्रबंधन (राउंड 22)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/full-reduction-activities` | गतिविधि सूची (पेजिंग) |
| POST | `/admin/full-reduction-activities` | नई गतिविधि (threshold/reduction/title/status/start_at/end_at) |
| PUT | `/admin/full-reduction-activities/{id}` | संपादित करें |
| POST | `/admin/full-reduction-activities/{id}/toggle-status` | ऑन-ऑफ-शेल्फ़ |
| DELETE | `/admin/full-reduction-activities/{id}` | हटाएँ (confirmPassword सहित) |

अनुमति ID: 396 सूची / 397 नया / 398 संपादन / 399 ऑन-ऑफ-शेल्फ़ / 400 हटाना (एक अनुमति रिकॉर्ड एक method.path slug से मेल, इसलिए 5 रूट 5 रिकॉर्ड)।

### प्रॉफिट शेयरिंग रिकॉर्ड (राउंड 22)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/profit-sharing` | प्रॉफिट शेयरिंग रिकॉर्ड (leftJoin ऑर्डर नंबर/तकनीशियन निकनेम, ?status&order_no&technician_name&page=, hashid एन्कोडेड) |

अनुमति ID: 394। सर्वर-पक्ष लॉजिक: erik_system_config group=profit_sharing (enabled/receiver_ratio); अक्षम होने पर disabled डिग्रेड केवल लॉग; सक्षम होने पर भुगतान सफलता पर स्वतः प्रॉफिट शेयरिंग अनुरोध (राशि=वास्तविक भुगतान×receiver_ratio डिफ़ॉल्ट 0.7, एक ही ऑर्डर pending/success आइडेम्पोटेंट स्किप); क्रेडेंशियल न होने पर HTTP निष्पादित नहीं, अनुरोध संरचना लॉग।

### पॉइंट्स व्हील प्रबंधन (राउंड 23)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/lucky-wheel` | व्हील पुरस्कार सूची (weight/stock सहित, पेजिंग) |
| POST | `/admin/lucky-wheel` | नया पुरस्कार (नाम/प्रकार points/balance/coupon/none/वेट/स्टॉक/छवि) |
| GET/PUT | `/admin/lucky-wheel/{id}` | विवरण / संपादन |
| DELETE | `/admin/lucky-wheel/{id}` | हटाएँ |
| POST | `/admin/lucky-wheel/{id}/toggle-status` | ऑन-ऑफ-शेल्फ़ |
| GET | `/admin/lucky-wheel/records` | ड्रॉ रिकॉर्ड (?status&page=, उपयोगकर्ता निकनेम/पुरस्कार नाम सहित) |

अनुमति ID: 401-406। स्टैटिक रूट `/lucky-wheel/records` और `/lucky-wheel/{id}/toggle-status` resource से पहले पंजीकृत हैं, {id} shadow रोकने के लिए।

### लौटने वाले ग्राहक इनाम प्रबंधन (राउंड 24)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/return-customer/config` | कॉन्फ़िग देखना (enabled स्विच / ratio अनुपात) |
| PUT | `/admin/return-customer/config` | कॉन्फ़िग अपडेट (enabled in:0,1; ratio between:0.01,1) |
| GET | `/admin/return-customer/rewards` | इनाम रिकॉर्ड सूची (?keyword तकनीशियन नाम/ऑर्डर नंबर/उपयोगकर्ता निकनेम, type=return_customer पेजिंग) |

अनुमति ID: 412-414। इनाम नियम: उपयोगकर्ता एक ही तकनीशियन के साथ 30 दिनों में दूसरी बार उपभोग (ऑर्डर पूर्ण) पर बोनस = वास्तविक भुगतान × ratio (डिफ़ॉल्ट 0.05), erik_technician_earnings में लिखा (type=return_customer, status=pending) कमीशन सेटलमेंट श्रृंखला के साथ एकीकृत सेटल; एक ही ऑर्डर आइडेम्पोटेंट, दोहरा जारी नहीं।

### सेकिल गतिविधि प्रबंधन (राउंड 24)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/seckill` | गतिविधि सूची (पेजिंग) |
| POST | `/admin/seckill` | नई गतिविधि (name/service_id/seckill_price/original_price/stock/start_at/end_at) |
| GET | `/admin/seckill/{id}` | गतिविधि विवरण |
| PUT | `/admin/seckill/{id}` | संपादित करें |
| DELETE | `/admin/seckill/{id}` | हटाएँ |
| POST | `/admin/seckill/{id}/toggle-status` | ऑन-ऑफ-शेल्फ़ |
| GET | `/admin/seckill/{id}/orders` | सेकिल ऑर्डर सूची |

अनुमति ID: 407-411、420। बिकी मात्रा = erik_order.seckill_id ऑर्डर संख्या; स्टॉक रो-लॉक कटौती, सोल्ड-आउट रोक।

### APP संस्करण प्रबंधन (राउंड 24)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/versions` | संस्करण सूची |
| POST | `/admin/versions` | नया संस्करण (platform/version_code/version_name/force_update/changelog/download_url/status) |
| PUT | `/admin/versions/{id}` | संपादित करें |
| DELETE | `/admin/versions/{id}` | हटाएँ |

अनुमति ID: 416-419। अपडेट जाँच इंटरफ़ेस /api/app/version status=1 में सबसे नया (updated_at/id सबसे बड़ा) संस्करण लेता है।

### शेड्यूल निर्यात (राउंड 24)

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/technician-schedule/export` | शेड्यूल CSV निर्यात (UTF-8 BOM, Excel सीधे खुलता है; start_date/end_date अनिवार्य और स्पैन≤31 दिन; technician_id वैकल्पिक hashid) |

अनुमति ID: 415। कॉलम: तकनीशियन ID/तकनीशियन नाम/तारीख/समय स्लॉट विवरण (time_slots JSON पार्स "09:00-12:00, 14:00-18:00" के रूप में)।

### भूमिका/अनुमति

| विधि | पथ | विवरण |
|------|------|------|
| GET/POST/PUT/DELETE | `/admin/role` | भूमिका CRUD |
| GET/POST/PUT/DELETE | `/admin/permission` | अनुमति CRUD (ट्री संरचना) |

### सिस्टम कॉन्फ़िगरेशन

| विधि | पथ | विवरण |
|------|------|------|
| GET | `/admin/config` | कॉन्फ़िग सूची |
| POST | `/admin/config` | नया कॉन्फ़िग (group/key/value/type/description) |
| PUT | `/admin/config/{id}` | कॉन्फ़िग संपादित करें |
| DELETE | `/admin/config/{id}` | कॉन्फ़िग हटाएँ |

### संचालन लॉग

**`GET /admin/log`** — लॉग क्वेरी

पैरामीटर: `?user_id/action/source/start_date/end_date/page`

`source` फ़ील्ड: web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### निर्यात

| विधि | पथ | विवरण |
|------|------|------|
| POST | `/admin/export/excel` | Excel निर्यात (type: users/technicians/orders/finance)। संवेदनशील फ़ील्ड स्वतः मास्क |
| POST | `/admin/export/pdf` | PDF पैनल निर्यात (type: dashboard) |

### फ़ाइल अपलोड

**`POST /admin/upload`** — फ़ाइल अपलोड (multipart/form-data)

### व्यक्तिगत केंद्र

| विधि | पथ | विवरण |
|------|------|------|
| PUT | `/admin/profile` | व्यक्तिगत जानकारी बदलें |
| PUT | `/admin/profile/password` | पासवर्ड बदलें |
| POST | `/admin/profile/logout` | लॉगआउट |

### आयात

**`POST /admin/import/users`** — बैच उपयोगकर्ता आयात (Excel)

### मॉनिटरिंग

| विधि | पथ | प्रमाणीकरण | विवरण |
|------|------|------|------|
| GET | `/health` | नहीं | स्वास्थ्य जाँच |
| GET | `/metrics` | नहीं | Prometheus मेट्रिक्स |
| GET | `/.well-known/security.txt` | नहीं | सुरक्षा संपर्क (RFC 9116) |
| GET | `/api/docs` | नहीं | API दस्तावेज़ |

---

## 三、सामान्य विवरण

### त्रुटि कोड

| code | विवरण |
|------|------|
| 0 | सफल |
| 401 | लॉगिन नहीं या Token एक्सपायर्ड |
| 403 | कोई अनुमति नहीं |
| 404 | संसाधन मौजूद नहीं |
| 422 | पैरामीटर सत्यापन विफल |
| 429 | बहुत अधिक अनुरोध |

### ID एन्कोडिंग

- सभी API प्रतिक्रियाओं में `id` और `*_id` फ़ील्ड hashids द्वारा एन्कोडेड हैं
- अनुरोध में भेजे गए `id` पैरामीटर भी hashids एन्कोडिंग प्रारूप में होने चाहिए
- फ्रंटएंड सीधे एन्कोडेड स्ट्रिंग उपयोग करता है, मैनुअल डिकोडिंग आवश्यक नहीं

### मोबाइल नंबर मास्किंग

प्रतिक्रिया में मोबाइल नंबर प्रारूप: `138****8000`। Excel निर्यात में भी इसी तरह।

### डेटा एन्क्रिप्शन

- API परत: प्रतिक्रिया में संवेदनशील फ़ील्ड `erikwang2013/encryption` द्वारा एन्क्रिप्टेड
- DB परत: मोबाइल नंबर/आईडी कार्ड/वीचैट ID आदि `erikwang2013/encryptable` द्वारा स्वतः एन्क्रिप्ट-डिक्रिप्ट

### पर्यावरण चर कॉन्फ़िगरेशन

| चर | विवरण |
|------|------|
| WECHAT_SUBSCRIBE_TEMPLATE_ID | अपॉइंटमेंट रिमाइंडर सब्सक्रिप्शन मैसेज टेम्पलेट ID |
| WECHAT_SUBSCRIBE_TEMPLATE_PAID | भुगतान सफल सब्सक्रिप्शन मैसेज टेम्पलेट ID |
| WECHAT_SUBSCRIBE_TEMPLATE_REFUND | रिफंड सब्सक्रिप्शन मैसेज टेम्पलेट ID |
| WECHAT_SUBSCRIBE_TEMPLATE_VERIFIED | वेरिफिकेशन सब्सक्रिप्शन मैसेज टेम्पलेट ID |
| WECHAT_SUBSCRIBE_TEMPLATE_REMINDER | सेवा शुरू होने से पहले रिमाइंडर सब्सक्रिप्शन मैसेज टेम्पलेट ID (राउंड 18) |
| WECHAT_SUBSCRIBE_TEMPLATE_EXPIRY | सदस्यता कार्ड/कूपन एक्सपायरी रिमाइंडर सब्सक्रिप्शन मैसेज टेम्पलेट ID (राउंड 18) |

सब्सक्रिप्शन मैसेज टेम्पलेट कॉन्फ़िगर न होने पर स्वतः इन-ऐप नोटिफिकेशन में डिग्रेड होता है।

**सब्सक्रिप्शन मैसेज सीन**: SCENE_PAY(भुगतान सफल) / SCENE_REFUND(रिफंड जमा) / SCENE_VERIFIED(वेरिफिकेशन सफल) / SCENE_RESCHEDULE(रीशेड्यूल सफल) / SCENE_REMINDER(सेवा शुरू होने से पहले रिमाइंडर, राउंड 18) / SCENE_EXPIRY(एक्सपायरी रिमाइंडर, राउंड 18)। पुश सफल होने पर ही push_sent_at लिखा जाता है, विफलता पर अगले राउंड में रीट्राई।

**रिचार्ज जमा नोटिफिकेशन (राउंड 18)**: वीचैट रिचार्ज कॉलबैक (R उपसर्ग नंबर) ट्रांज़ैक्शन में इन-ऐप नोटिफिकेशन type='wallet_recharge'「आपने सफलतापूर्वक ¥X.XX रिचार्ज किया」लिखता है; कॉलबैक आइडेम्पोटेंसी दोहराता है (केवल पहली बार pending→paid ट्रिगर), स्थिति परिवर्तन के साथ एक ही ट्रांज़ैक्शन में परमाणु कमिट, लिखने की विफलता मुख्य प्रवाह को ब्लॉक नहीं करती।
