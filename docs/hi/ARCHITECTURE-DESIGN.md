# आर्किटेक्चर डिज़ाइन
> **Languages**: [中文](../ARCHITECTURE-DESIGN.md) · [English](../en/ARCHITECTURE-DESIGN.md) · [한국어](../ko/ARCHITECTURE-DESIGN.md) · [Русский](../ru/ARCHITECTURE-DESIGN.md) · [Deutsch](../de/ARCHITECTURE-DESIGN.md) · [Français](../fr/ARCHITECTURE-DESIGN.md) · [Español](../es/ARCHITECTURE-DESIGN.md) · [Português](../pt/ARCHITECTURE-DESIGN.md) · [العربية](../ar/ARCHITECTURE-DESIGN.md) · [বাংলা](../bn/ARCHITECTURE-DESIGN.md) · [Bahasa Indonesia](../id/ARCHITECTURE-DESIGN.md) · [日本語](../ja/ARCHITECTURE-DESIGN.md)

## लेयर्ड आर्किटेक्चर

```
┌─────────────────────────────────────────┐
│              表现层 (Presentation)        │
│  微信小程序 / Flutter APP / Flutter Web   │
├─────────────────────────────────────────┤
│              路由层 (Route)               │
│  config/route.php — 路由分组 + 中间件绑定  │
├─────────────────────────────────────────┤
│            中间件层 (Middleware)           │
│  Cors → Security → RateLimit → Auth      │
│  → TechnicianAuth → OperationLog         │
├─────────────────────────────────────────┤
│            控制器层 (Controller)           │
│  BaseController → 各业务Controller        │
├─────────────────────────────────────────┤
│             服务层 (Service)              │
│  common/ — Snowflake/Hashids/Encryption  │
├─────────────────────────────────────────┤
│             模型层 (Model)                │
│  Eloquent ORM + Encryptable + Scout      │
├─────────────────────────────────────────┤
│              数据层 (Data)                │
│  MySQL / Redis / Elasticsearch           │
└─────────────────────────────────────────┘
```

## मिडलवेयर डिज़ाइन

### निष्पादन श्रृंखला

```
Cors → Security(31种攻击检测) → RateLimit → Auth(JWT+用户状态)
    → [TechnicianAuth(技师身份)] → [AdminPermission(RBAC)] → [OperationLog(8端来源)]
    → Controller
```

### मिडलवेयर ज़िम्मेदारियाँ

| मिडलवेयर | दायरा | कार्य |
|--------|--------|------|
| Cors | वैश्विक | OPTIONS प्रीफ़्लाइट + CORS प्रतिक्रिया हेडर |
| Security | वैश्विक | erikwang2013/security-php, 31 प्रकार की आक्रमण पहचान |
| RateLimit | वैश्विक | Redis स्लाइडिंग विंडो + Lua परमाणुकरण |
| Auth | रूट समूह | JWT पार्सिंग + उपयोगकर्ता अस्तित्व/स्थिति सत्यापन |
| TechnicianAuth | रूट समूह | तकनीशियन प्रोफ़ाइल क्वेरी + approved स्थिति सत्यापन |
| AdminAuth | रूट समूह | Admin पक्ष JWT प्रमाणीकरण + ब्लैकलिस्ट |
| AdminPermission | रूट समूह | RBAC अनुमति सत्यापन, Redis 60s कैश |
| OperationLog | रूट समूह | ऑपरेशन लॉग + 8 पक्ष स्रोत स्वचालित पहचान |

### रेट-लिमिट नीति

| इंटरफ़ेस | सीमा |
|------|------|
| डिफ़ॉल्ट | 60 बार/मिनट/IP |
| लॉगिन | 10 बार/मिनट |
| पंजीकरण | 5 बार/मिनट |
| वेरिफिकेशन कोड | 1 बार/60 सेकंड/मोबाइल नंबर |

## डेटाबेस डिज़ाइन सिद्धांत

### प्राथमिक कुंजी नीति

- सभी प्राथमिक कुंजियाँ: BIGINT UNSIGNED NOT NULL, गैर-ऑटो-इंक्रीमेंट
- `erikwang2013/snowflake-php` से एप्लिकेशन स्तर पर उत्पन्न
- Model: `$incrementing = false`, `$keyType = 'string'`

### टेबल उपसर्ग

एकीकृत `appointment_` उपसर्ग, `config/database.php` में कॉन्फ़िगर किया जाता है। Model मूल टेबल नाम लिखता है, ORM स्वचालित रूप से उपसर्ग जोड़ता है।

### संवेदनशील फ़ील्ड एन्क्रिप्शन

`erikwang2013/encryptable` trait का उपयोग:

```php
use Erikwang2013\Encryptable\Encryptable;

class User extends Model
{
    use Encryptable;
    protected array $encryptable = [
        'phone', 'wx_openid', 'wx_unionid', 'real_name',
    ];
}
```

एन्क्रिप्टेड फ़ील्ड की VARCHAR लंबाई 500 (एन्क्रिप्शन डेटा विस्तार)।

### सॉफ्ट डिलीट और टाइमस्टैम्प

- Eloquent SoftDeletes: `deleted_at` DATETIME DEFAULT NULL
- सभी टेबल में `created_at` + `updated_at`

## API ID एन्क्रिप्शन/डिक्रिप्शन तंत्र

### अनुरोध: decodeIds()

फ्रंटएंड hashids-एन्कोडेड ID भेजता है → नियंत्रक डिकोड करने के लिए `$this->decodeIds($request->all())` कॉल करता है।

### प्रतिक्रिया: encodeIds()

DB क्वेरी परिणाम की ID → `BaseController::success()` स्वचालित रूप से `encodeIds()` कॉल कर एन्कोड करता है → hashids स्ट्रिंग लौटाता है।

### नियम

सरणी में `id` कुंजी नाम या `_id` से समाप्त होने वाले फ़ील्ड को रिकर्सिव रूप से संसाधित करता है।

## सुरक्षा डिज़ाइन

### गहराई-रक्षा

```
WAF → Cors → Security(31种检测) → RateLimit → Auth(JWT+状态)
    → [身份校验] → [RBAC] → Controller(Model加密) → 响应
```

### प्रमाणीकरण सुरक्षा

- पासवर्ड: bcrypt हैश
- JWT: 7 दिन वैधता + रिफ्रेश + ब्लैकलिस्ट
- लॉक: 5 विफलताएँ → 15 मिनट
- समवर्ती: अधिकतम 3 Token

### डेटा सुरक्षा

- API स्तर: erikwang2013/encryption
- DB स्तर: erikwang2013/encryptable trait
- लॉग: संवेदनशील डेटा लॉग में नहीं

### ऑपरेशन सुरक्षा

- erikwang2013/poster-php: हटाने/ऑडिट/विड्रॉल से पहले सत्यापन
- Security मिडलवेयर: XSS/SQL इंजेक्शन/CSRF/पाथ ट्रैवर्सल पहचान

## Elasticsearch एकीकरण

`erikwang2013/webman-scout` मॉडल को ES में स्वचालित रूप से सिंक करता है:

```php
use Erikwang2013\WebmanScout\Searchable;

class Service extends Model
{
    use Searchable;
    public function searchableAs(): string { return 'appointment_services'; }
}
```

## Excel/PDF निर्यात

- Excel: PhpSpreadsheet, संवेदनशील फ़ील्ड स्वचालित मास्किंग
- PDF: Dashboard पैनल विज़ुअलाइज़ेशन निर्यात

## 8 पक्ष स्रोत पहचान

OperationLog User-Agent पार्सिंग द्वारा:

```
iPad → iPadOS / Mac → macOS / Windows → Windows
Linux → Linux / iPhone → ios / Android → android
HarmonyOS → harmonyOS / 其他 → web
```


## TDD परीक्षण

| आइटम | परीक्षण संख्या | स्थिति |
|------|--------|------|
| admin/ | 60 | ✅ पास |
| service/ | 21 | ✅ पास |
| कुल | 81 | ✅ |

परीक्षण कवरेज: रिफंड नियम / ऑर्डर स्थिति / Hashids / कतार प्रणाली / एन्क्रिप्शन / वेरिफिकेशन कोड
