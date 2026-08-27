# अपॉइंटमेंट सेवा प्रणाली — इंस्टॉलेशन गाइड
> **Languages**: [中文](../INSTALL.md) · [English](../en/INSTALL.md) · [한국어](../ko/INSTALL.md) · [Русский](../ru/INSTALL.md) · [Deutsch](../de/INSTALL.md) · [Français](../fr/INSTALL.md) · [Español](../es/INSTALL.md) · [Português](../pt/INSTALL.md) · [العربية](../ar/INSTALL.md) · [বাংলা](../bn/INSTALL.md) · [Bahasa Indonesia](../id/INSTALL.md) · [日本語](../ja/INSTALL.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## पर्यावरण आवश्यकताएँ

| घटक | न्यूनतम संस्करण | विवरण |
|------|----------|------|
| PHP | 8.3+ | एक्सटेंशन: bcmath, curl, gd, mbstring, pdo, pdo_mysql, pcntl, redis |
| MySQL | 8.0+ | टेबल उपसर्ग `appointment_`, कैरेक्टर सेट utf8mb4 |
| Redis | 6.0+ | कैश / रेट-लिमिट / Session / वेरिफिकेशन कोड संग्रह |
| Composer | 2.x | PHP निर्भरता प्रबंधन |
| Elasticsearch | 8.x (वैकल्पिक) | फुल-टेक्स्ट खोज, इंस्टॉल न करने पर मुख्य फ़ीचर प्रभावित नहीं |

---

## 一、वेब इंस्टॉल विज़ार्ड (अनुशंसित)

प्रबंधन बैकएंड शुरू करने के बाद, ब्राउज़र में `/install` खोलकर वन-क्लिक इंस्टॉल विज़ार्ड में प्रवेश करें:

```bash
# 1. निर्भरताएँ स्थापित करें और शुरू करें
cd admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
php start.php start -d     # डिफ़ॉल्ट पोर्ट 8787
```

ब्राउज़र में `http://localhost:8787/install` खोलें, 4 चरणों में पूरा करें:

1. **पर्यावरण जाँच** — स्वचालित रूप से PHP संस्करण, आवश्यक एक्सटेंशन, फ़ाइल अनुमतियाँ जाँचता है
2. **डेटाबेस कॉन्फ़िगरेशन** — MySQL कनेक्शन जानकारी भरें, कनेक्शन परीक्षण पर क्लिक करें
3. **व्यवस्थापक खाता** — एप्लिकेशन नाम, व्यवस्थापक उपयोगकर्ता नाम और पासवर्ड सेट करें
4. **इंस्टॉलेशन निष्पादित करें** — स्वतः SQL इम्पोर्ट → व्यवस्थापक बनाना → .env कॉन्फ़िग लिखना

इंस्टॉलेशन के बाद सेट किए गए उपयोगकर्ता नाम/पासवर्ड से लॉगिन करें। सफल इंस्टॉलेशन पर `.install.lock` फ़ाइल लिखी जाती है, `/install` इंटरफ़ेस दोहरा सत्यापन (फ़ाइल लॉक + isInstalled) से पुनः-इंस्टॉल रोकता है; `.install.lock` `.gitignore` में जोड़ा गया है। प्रोडक्शन में `admin/config/route.php` से `/install` रूट हटाने की सिफारिश की जाती है।

---

## 二、मैन्युअल इंस्टॉलेशन

### 2.1 परियोजना क्लोन करें

```bash
git clone <repo-url> appointment-php
cd appointment-php
```

### 1.2 PHP निर्भरताएँ स्थापित करें

```bash
# बिज़नेस API सेवा
cd service/
cp .env.example .env
composer install --no-dev --optimize-autoloader

# प्रबंधन बैकएंड
cd ../admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
```

### 1.3 पर्यावरण चर कॉन्फ़िगर करें

`service/.env` (बिज़नेस API) और `admin/.env` (प्रबंधन बैकएंड) संपादित करें, निम्न मुख्य कॉन्फ़िग बदलें:

```bash
# डेटाबेस कनेक्शन
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=appointment          # service में appointment, admin में open_admin
DB_USERNAME=root
DB_PASSWORD=your-password

# Redis कनेक्शन
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# JWT कुंजी — प्रोडक्शन में 64-वर्ण यादृच्छिक स्ट्रिंग अवश्य बदलें
JWT_SECRET_KEY=your-64-char-random-string

# एन्क्रिप्शन कुंजी — प्रोडक्शन में अवश्य बदलें
ENCRYPTION_KEY=your-32-byte-key
ENCRYPTABLE_KEY=your-32-byte-key

# Hashids सॉल्ट — प्रोडक्शन में अवश्य बदलें
HASHIDS_SALT=your-random-salt

# डिबग मोड — प्रोडक्शन में false होना अनिवार्य
APP_DEBUG=false
```

> पूर्ण चर विवरण `service/.env.example` और `admin/.env.example` में।

### 1.4 डेटाबेस बनाएँ और इम्पोर्ट करें

```bash
# डेटाबेस बनाएँ (service और admin एक ही डेटाबेस उपयोग कर सकते हैं, अलग भी कर सकते हैं)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS appointment DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS open_admin DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# एकीकृत इंस्टॉल स्क्रिप्ट इम्पोर्ट करें (सभी 54+ टेबल + अनुमति डेटा + डेमो डेटा सहित)
mysql -u root -p appointment < docs/install.sql
mysql -u root -p open_admin < docs/install.sql
```

> `docs/install.sql` सभी माइग्रेशन फ़ाइलों के संयोजन से बना है, कुल 2723 पंक्तियाँ, जिसमें प्रबंधन बैकएंड और बिज़नेस सेवा की सभी टेबल संरचनाएँ और सीड डेटा शामिल हैं। नई स्थापना पर एक बार निष्पादित करें; मौजूदा डेटाबेस पर दोबारा निष्पादन प्राथमिक कुंजी/कॉलम संघर्ष से बाधित होगा, अपग्रेड परिदृश्य में पहले बैकअप लें या संघर्ष मैन्युअल रूप से संभालें।

### 1.5 सेवा शुरू करें

```bash
# बिज़नेस API सेवा शुरू करें (डिफ़ॉल्ट पोर्ट 8787)
cd service/
php start.php start -d

# प्रबंधन बैकएंड शुरू करें (डिफ़ॉल्ट पोर्ट 8787)
cd ../admin/
php start.php start -d
```

### 1.6 इंस्टॉलेशन सत्यापित करें

```bash
# बिज़नेस API
curl http://localhost:8787/api/common/config

# प्रबंधन बैकएंड हेल्थ चेक
curl http://localhost:8787/health

# प्रबंधन बैकएंड लॉगिन (डिफ़ॉल्ट खाता पासवर्ड नीचे)
curl -X POST http://localhost:8787/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### 1.7 डिफ़ॉल्ट खाता

| भूमिका | उपयोगकर्ता नाम | पासवर्ड | विवरण |
|------|--------|------|------|
| सुपर एडमिन | `admin` | `admin123` | सभी अनुमतियाँ |

> पहले लॉगिन के तुरंत बाद पासवर्ड बदलें।

---

## 三、Docker डिप्लॉयमेंट

### 2.1 बिज़नेस API सेवा

```bash
cd service/
cp .env.docker .env
# .env संपादित करें, कुंजियाँ और पासवर्ड बदलें
docker-compose up -d
```

ऑर्केस्ट्रेशन: nginx (80/443) + app (8787) + mysql (3306) + redis (6379) + elasticsearch (9200)

### 2.2 प्रबंधन बैकएंड

```bash
cd admin/
cp .env.docker .env
docker-compose up -d
```

### 2.3 Docker पर्यावरण में डेटाबेस इम्पोर्ट

```bash
# install.sql को कंटेनर में कॉपी कर निष्पादित करें
docker cp docs/install.sql appointment-svc-mysql:/tmp/
docker exec -it appointment-svc-mysql mysql -u root -p appointment < /tmp/install.sql
```

---

## 四、डेटाबेस संरचना अवलोकन

| डोमेन | टेबल संख्या | मुख्य टेबल |
|----|------|--------|
| प्रबंधन बैकएंड | 8 | `appointment_admin_user`, `appointment_admin_role`, `appointment_admin_permission`, `appointment_operation_log` |
| उपयोगकर्ता डोमेन | 4 | `appointment_user`, `appointment_user_address`, `appointment_user_favorite`, `appointment_user_device` |
| तकनीशियन डोमेन | 8 | `appointment_technician_profile`, `appointment_technician_schedule`, `appointment_technician_earning`, `appointment_technician_withdrawal`, `appointment_technician_tier_config` |
| सेवा डोमेन | 4 | `appointment_service_category`, `appointment_service`, `appointment_service_package`, `appointment_service_record` |
| ऑर्डर डोमेन | 5 | `appointment_order`, `appointment_order_item`, `appointment_order_payment`, `appointment_order_refund`, `appointment_order_review` |
| मार्केटिंग डोमेन | 8 | `appointment_coupon`, `appointment_member_card`, `appointment_gift_card`, `appointment_user_points`, `appointment_promotion` |
| कतार | 1 | `appointment_queue_number` |
| सामग्री डोमेन | 5 | `appointment_banner`, `appointment_announcement`, `appointment_faq`, `appointment_feedback`, `appointment_platform_agreement` |
| समुदाय डोमेन | 3 | `appointment_post`, `appointment_comment`, `appointment_moment` |
| स्टोर | 1 | `appointment_store` |
| प्रशिक्षण | 2 | `appointment_training_course`, `appointment_training_progress` |
| परीक्षा | 3 | `appointment_exam`, `appointment_exam_question`, `appointment_exam_attempt` |
| सिस्टम | 3 | `appointment_system_config`, `appointment_notification`, `appointment_signature` |
| **कुल** | **55** | |

सभी टेबल `appointment_` उपसर्ग उपयोग करती हैं, प्राथमिक कुंजी `id` BIGINT गैर-ऑटो-इंक्रीमेंट (snowflake-php एप्लिकेशन स्तर पर उत्पन्न)।

---

## 五、परीक्षण चलाना

```bash
# बिज़नेस API परीक्षण (21 tests)
cd service/
php vendor/bin/phpunit

# प्रबंधन बैकएंड परीक्षण (59 tests)
cd admin/
php vendor/bin/phpunit

# स्थैतिक विश्लेषण
php vendor/bin/phpstan analyse --level=5 app/

# कोड शैली जाँच
php vendor/bin/php-cs-fixer fix --dry-run --diff
```

---

## 六、तृतीय-पक्ष सेवा कॉन्फ़िगरेशन

प्रबंधन बैकएंड के "सिस्टम कॉन्फ़िगरेशन" में निम्न कॉन्फ़िग समूह भरें:

| कॉन्फ़िग समूह | उपयोग | अनिवार्य |
|--------|------|------|
| `wechat_pay` | वीचैट भुगतान मर्चेंट नंबर / API कुंजी / प्रमाणपत्र | भुगतान फ़ीचर के लिए आवश्यक |
| `wechat_app` | वीचैट मिनी प्रोग्राम AppID / AppSecret | वीचैट लॉगिन के लिए आवश्यक |
| `sms` | SMS सेवा प्रदाता (aliyun/tencent) + हस्ताक्षर/टेम्पलेट | SMS वेरिफिकेशन कोड के लिए आवश्यक |
| `map_service` | मानचित्र सेवा (amap/tencent) + API Key | LBS फ़ीचर के लिए आवश्यक |
| `storage` | ऑब्जेक्ट स्टोरेज (oss/cos) + AccessKey/Endpoint | फ़ाइल अपलोड के लिए आवश्यक |

---

## 七、सामान्य समस्याएँ

**Q: स्टार्टअप त्रुटि `Class 'support\Model' not found`**
A: `composer dump-autoload` चलाएँ।

**Q: डेटाबेस कनेक्शन विफल `SQLSTATE[HY000] [2002]`**
A: `.env` में `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD` कॉन्फ़िग जाँचें।

**Q: SQL इम्पोर्ट में एन्कोडिंग त्रुटि**
A: `mysql -u root -p --default-character-set=utf8mb4 < docs/install.sql` उपयोग करें

**Q: Redis कनेक्शन विफल**
A: Redis शुरू हुआ है पुष्टि करें, `REDIS_HOST`/`REDIS_PORT` कॉन्फ़िग जाँचें।

**Q: पोर्ट कब्जा**
A: `config/server.php` में `listen` पोर्ट बदलें।

**Q: वेरिफिकेशन कोड प्रदर्शित नहीं होता**
A: GD एक्सटेंशन स्थापित है पुष्टि करें, `POSTER_CAPTCHA_STORAGE` कॉन्फ़िग सही है (स्थानीय `file`, प्रोडक्शन `redis`)।

**Q: Elasticsearch काम नहीं करता**
A: ES वैकल्पिक घटक है, `SCOUT_HOSTS` कॉन्फ़िग सही और ES सेवा शुरू होना पुष्टि करें।

---

## 八、निर्देशिका संरचना

```
appointment-php/
├── admin/                    # प्रबंधन बैकएंड (webman v2)
│   ├── app/                  # नियंत्रक / मॉडल / मिडलवेयर
│   ├── config/               # रूट / डेटाबेस / मिडलवेयर कॉन्फ़िगरेशन
│   ├── database/             # बैकअप स्क्रिप्ट (टेबल संरचना और सीड डेटा एकीकृत: docs/install.sql)
│   ├── tests/                # PHPUnit परीक्षण (59 tests)
│   ├── .env.example          # पर्यावरण चर टेम्पलेट
│   ├── .env.docker           # Docker पर्यावरण चर
│   ├── Dockerfile            # Docker बिल्ड फ़ाइल
│   └── docker-compose.yml    # Docker ऑर्केस्ट्रेशन
├── service/                  # बिज़नेस API सेवा (webman v2)
│   ├── app/                  # नियंत्रक / मॉडल / मिडलवेयर
│   ├── config/               # सुरक्षा / रूट / डेटाबेस कॉन्फ़िगरेशन
│   ├── seed.php              # डेमो डेटा सीड रनर (docs/install.sql डेमो डेटा खंड पढ़ता है)
│   ├── tests/                # PHPUnit परीक्षण (21 tests)
│   ├── .env.example          # पर्यावरण चर टेम्पलेट
│   ├── .env.docker           # Docker पर्यावरण चर
│   ├── Dockerfile            # Docker बिल्ड फ़ाइल
│   └── docker-compose.yml    # Docker ऑर्केस्ट्रेशन
├── docs/                     # दस्तावेज़
│   ├── INSTALL.md            # यह इंस्टॉलेशन गाइड
│   ├── install.sql           # एकीकृत डेटाबेस इंस्टॉल स्क्रिप्ट (2723 पंक्तियाँ)
│   ├── ARCHITECTURE.md       # आर्किटेक्चर डिज़ाइन दस्तावेज़
│   ├── API.md                # API संदर्भ दस्तावेज़
│   └── AUDIT-REPORT.md       # ऑडिट रिपोर्ट
└── .github/workflows/        # CI/CD पाइपलाइन
    └── ci.yml
```
