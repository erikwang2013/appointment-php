# सुरक्षा ऑडिट रिपोर्ट — अपॉइंटमेंट प्रणाली (appointment-php)
> **Languages**: [中文](../SECURITY-AUDIT-REPORT.md) · [English](../en/SECURITY-AUDIT-REPORT.md) · [한국어](../ko/SECURITY-AUDIT-REPORT.md) · [Русский](../ru/SECURITY-AUDIT-REPORT.md) · [Deutsch](../de/SECURITY-AUDIT-REPORT.md) · [Français](../fr/SECURITY-AUDIT-REPORT.md) · [Español](../es/SECURITY-AUDIT-REPORT.md) · [Português](../pt/SECURITY-AUDIT-REPORT.md) · [العربية](../ar/SECURITY-AUDIT-REPORT.md) · [বাংলা](../bn/SECURITY-AUDIT-REPORT.md) · [Bahasa Indonesia](../id/SECURITY-AUDIT-REPORT.md) · [日本語](../ja/SECURITY-AUDIT-REPORT.md)

**दिनांक**: 2026-08-04
**ऑडिट दायरा**: service (अपॉइंटमेंट सेवा प्रणाली), admin (खुला प्रबंधन बैकएंड)
**PHP संस्करण**: 8.3.7
**फ्रेमवर्क**: webman v2

---

## 一、परीक्षण परिणाम

| परीक्षण आइटम | Service | Admin |
|--------|---------|-------|
| PHP सिंटैक्स जाँच (पूर्ण) | पास | पास |
| PHPUnit यूनिट परीक्षण | 59 tests / 165 assertions PASS | 59 tests / 165 assertions PASS |
| PHPStan स्थैतिक विश्लेषण | स्थापित नहीं (dev निर्भरता डाउनलोड टाइमआउट) | स्थापित नहीं (dev निर्भरता डाउनलोड टाइमआउट) |

---

## 二、सुरक्षा सुरक्षा स्तर अवलोकन

```
请求 → Nginx (安全头+敏感文件保护) → Cors (CORS+安全头) → SecurityMiddleware (31种攻击检测) → RateLimit (Redis滑动窗口) → Auth (JWT) → Controller
                                                                                                   ↓
                                                                                    IP黑名单 (5次攻击/60s → 封禁15min)
                                                                                    账号锁定 (5次失败/15min → 锁定15min)
```

---

## 三、सुधारी गई समस्याएँ

### 3.1 Service CORS में सुरक्षा प्रतिक्रिया हेडर अनुपलब्ध → सुधारा गया
**फ़ाइल**: `service/app/middleware/Cors.php`
- 6 सुरक्षा हेडर जोड़े: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, CSP, X-Permitted-Cross-Domain-Policies
- अब admin सुरक्षा हेडर कॉन्फ़िग के अनुरूप

### 3.2 Service में लॉगिन विफलता लॉक अनुपलब्ध → सुधारा गया
**फ़ाइल**: `service/app/api/v1/controller/AuthController.php`
- `login()` और `loginByCode()` विधियों में Redis विफलता काउंट जोड़ा
- 5 विफलताएँ/15 मिनट लॉक → HTTP 429
- Redis विफलता पर ग्रेसफुल डिग्रेड

### 3.3 CORS Origin हार्डकोड `*` → सुधारा गया
**फ़ाइल**: `service/app/middleware/Cors.php`, `admin/app/middleware/Cors.php`
- `CORS_ALLOW_ORIGIN` पर्यावरण चर से कॉन्फ़िगर करने के लिए बदला
- खाली छोड़ने पर डिफ़ॉल्ट `*` (पिछड़ी संगतता)

### 3.4 Service में security-php निर्भरता अनुपलब्ध → सुधारा गया
**संचालन**:
- composer.json में `allow-plugins.erikwang2013/security-php` जोड़ा
- `composer install --no-dev` चलाकर निर्भरता स्थापित
- कॉन्फ़िग फ़ाइल `config/plugin/erikwang2013/security-php/app.php` पर प्रकाशित
- CSRF Origin डिटेक्टर (`csrf_origin`) सक्षम (block मोड)

### 3.5 Service Nginx में Permissions-Policy अनुपलब्ध → सुधारा गया
**फ़ाइल**: `service/docs/nginx.conf`
- `add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;` जोड़ा

### 3.6 पारिस्थितिकी कॉन्फ़िग पूरा करना → सुधारा गया
- `service/.env.example` और `admin/.env.example` में `CORS_ALLOW_ORIGIN` जोड़ा
- `service/.env.docker` और `admin/.env.docker` में `CORS_ALLOW_ORIGIN` जोड़ा

---

## 四、वर्तमान सुरक्षा सुरक्षा पूर्ण सूची

### 4.1 WAF स्तर — 31 प्रकार के आक्रमण डिटेक्टर

| मोड | डिटेक्टर | संख्या |
|------|--------|------|
| **block** (403 इंटरसेप्ट) | XSS, SQL इंजेक्शन, कमांड इंजेक्शन, पाथ ट्रैवर्सल, फ़ाइल अपलोड, SSRF, XXE, डिसीरियलाइज़ेशन, LDAP इंजेक्शन, ईमेल हेडर इंजेक्शन, Open Redirect, JWT आक्रमण, Host हेडर आक्रमण, Request Smuggling, GraphQL इंजेक्शन, XPATH इंजेक्शन, JNDI/Log4Shell, SSI इंजेक्शन, CSV इंजेक्शन, डेटा लीक, Prototype Pollution, WebSocket अपहरण, CORS बायपास, DNS Rebinding, HTTP विधि सत्यापन, अनुरोध निकाय आकार (10MB), Content-Type व्हाइटलिस्ट, CSRF Origin | 28 |
| **log** (केवल रिकॉर्ड) | प्रतिक्रिया हेडर इंजेक्शन, SSTI, NoSQL इंजेक्शन | 3 |

### 4.2 प्रमाणीकरण और प्राधिकरण

| तंत्र | Service | Admin |
|------|---------|-------|
| JWT प्रमाणीकरण | Auth मिडलवेयर | AdminAuth मिडलवेयर |
| JWT ब्लैकलिस्ट | लॉगआउट पर जोड़ा | लॉगआउट + सत्र सीमा से अधिक पर जोड़ा |
| RBAC अनुमतियाँ | — | method.path प्रारूप, Redis 60s कैश |
| खाता लॉक | 5 बार/15 मिनट (Redis) | 5 बार/15 मिनट (Redis) |
| समवर्ती सत्र सीमा | — | अधिकतम 3 Token |
| पासवर्ड हैश | bcrypt | bcrypt |

### 4.3 रेट-लिमिट

| रूट | Service | Admin |
|------|---------|-------|
| डिफ़ॉल्ट | 60 बार/मिनट/IP | 60 बार/मिनट/IP |
| लॉगिन | 10 बार/मिनट | — |
| पंजीकरण | 5 बार/मिनट | — |
| SMS/पासवर्ड भूलना | 5 बार/मिनट | — |

### 4.4 डेटा सुरक्षा

| उपाय | Service | Admin |
|------|---------|-------|
| डेटाबेस फ़ील्ड एन्क्रिप्शन | AES-256-CBC (6 मॉडल) | AES-256-CBC |
| API ट्रांसफर एन्क्रिप्शन | AES-256-CBC | AES-256-CBC |
| ID अस्पष्टीकरण (Hashids) | सभी बाहरी ID | सभी बाहरी ID |
| Snowflake ID | गैर-ऑटो-इंक्रीमेंट BIGINT | गैर-ऑटो-इंक्रीमेंट BIGINT |
| संवेदनशील फ़ील्ड मास्किंग | मोबाइल नंबर मास्किंग | निर्यात डेटा मास्किंग |

---

## 五、लंबित सुझाव

### 5.1 सुझाव: security-php संग्रहण Redis में बदलें (प्रोडक्शन)
**वर्तमान**: दोनों सेवाएँ `file` प्रकार संग्रहण उपयोग करती हैं (स्थानीय JSON फ़ाइल)
**जोखिम**: बहु-इंस्टेंस डिप्लॉयमेंट में IP ब्लैकलिस्ट साझा नहीं होती, आक्रमणकारी इंस्टेंस बदलकर बायपास कर सकता है
**सुझाव**: प्रोडक्शन में `storage.type` को `redis` में बदलें

### 5.2 सुझाव: Session Cookie सुरक्षा विशेषताएँ
**वर्तमान**: `secure: false`, `same_site: ''`
**जोखिम**: Cookie HTTP से स्थानांतरित हो सकती है, CSRF सुरक्षा कमज़ोर
**सुझाव**: प्रोडक्शन में `secure: true`, `same_site: 'Lax'` सेट करें

### 5.3 सुझाव: PHPStan dev निर्भरता स्थापित करें
**वर्तमान**: `composer install --dev` नेटवर्क टाइमआउट से विफल
**संचालन**: `composer install --dev` या `composer require --dev phpstan/phpstan`

### 5.4 चेतावनी: प्रोडक्शन डिप्लॉयमेंट से पहले सभी कुंजियाँ बदलें
`.env.docker` में प्लेसहोल्डर कुंजियाँ प्रोडक्शन डिप्लॉयमेंट से पहले यादृच्छिक मानों से बदलनी अनिवार्य:
- `JWT_SECRET_KEY`
- `HASHIDS_SALT`, `HASHIDS_ALT_SALT`
- `ENCRYPTION_KEY`, `ENCRYPTABLE_KEY`
- `DB_PASSWORD`

---

## 六、दस्तावेज़ उत्पादन

| दस्तावेज़ | पथ |
|------|------|
| Service सुरक्षा आर्किटेक्चर | `service/docs/SECURITY.md` |
| Admin सुरक्षा आर्किटेक्चर | `admin/docs/SECURITY.md` |
| यह ऑडिट रिपोर्ट | `docs/SECURITY-AUDIT-REPORT.md` |

---

## 七、ऑडिट निष्कर्ष

**सुरक्षा सुरक्षा समग्र रेटिंग: अच्छा**

- गहराई-रक्षा स्तर पूर्ण (Nginx → WAF → Rate Limit → Auth → RBAC)
- 31 प्रकार के आक्रमण डिटेक्टर वैश्विक कवरेज, 28 प्रकार इंटरसेप्ट मोड
- JWT + ब्लैकलिस्ट + खाता लॉक + IP ब्लैकलिस्ट बहु-स्तरीय प्रमाणीकरण सुरक्षा
- डेटा स्तर AES-256-CBC एन्क्रिप्शन + Hashids अस्पष्टीकरण
- service पक्ष के तीन मुख्य मुद्दे सुधारे: सुरक्षा प्रतिक्रिया हेडर अनुपलब्ध, लॉगिन लॉक अनुपलब्ध, WAF पैकेज अनुपलब्ध
- सुझाव आइटम प्रोडक्शन कॉन्फ़िग अनुकूलन हैं, सुरक्षा कमज़ोरियाँ नहीं

---

## 八、2026-08-26 सुधार दौर (सुरक्षा सुदृढ़ीकरण)

| आइटम | सुधार सामग्री |
|----|---------|
| ऑर्डर टैम्पर-रोधी | OrderController::store() में ऑर्डर आइटम कीमतें हमेशा डेटाबेस रिकॉर्ड के अनुसार (service→appointment_service, product→appointment_product), क्लाइंट कीमत गणना में भाग नहीं लेती; अज्ञात target_type 422; target_id अनिवार्य hashid (raw id डिकोड होकर 0 → 422 "उत्पाद मौजूद नहीं या अनलिस्टेड"); समूह खरीद/सेकिल कीमत भी DB आधारित |
| सेकिल स्टॉक घटाव एकीकृत | स्टॉक एकीकृत रूप से /api/order store() ट्रांज़ैक्शन के भीतर रो-लॉक घटाव; SeckillController::buy अब स्टॉक पूर्व-घटाव नहीं करता (Redis गतिविधि लॉक + client_token आइडेम्पोटेंसी बरकरार); सीधे /api/order को seckill_id के साथ कॉल करने पर भी स्टॉक घटता है |
| तकनीशियन विड्रॉल | आवेदन पर बैलेंस से इन-फ्लाइट (pending/approved) आरक्षण; अनुमोदन ट्रांसफर से पहले पुनर्जाँच settled−withdrawn−इन-फ्लाइट ≥ विड्रॉल राशि; समवर्ती अनुमोदन से डबल-पेमेंट नहीं |
| भुगतान कॉलबैक | वीचैट कॉलबैक total_fee ऑर्डर देय राशि से कड़ी तुलना, बेमेल होने पर अस्वीकार; अलीपे कॉलबैक लॉग मास्क (buyer_id/seller_id आदि शामिल नहीं) |
| /install सुरक्षा | सफल इंस्टॉलेशन पर .install.lock लिखा जाता है, install इंटरफ़ेस दोहरा सत्यापन (फ़ाइल लॉक + isInstalled); .gitignore में .install.lock अनदेखा |
| निर्भरता समेकन | webman-scout एकीकृत 2.0.5 (service/admin); opensearch-project/opensearch-php ^2.6 जोड़ा; dompdf/security-php/webman-database सटीक संस्करण लॉक ("*" वाइल्डकार्ड हटाया) |
| इंजीनियरिंग | service/app/common/StorageService.php हटाया (डेड कोड); admin/app/common/ में TechnicianWithdrawalService/WechatPayService जोड़ा (admin स्वतंत्र डिप्लॉयमेंट service कोड पर निर्भर नहीं); दोनों ऐप्स के phpstan.neon सुधार चालनीय (php -d memory_limit=2G) |
