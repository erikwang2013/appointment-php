# सिस्टम आर्किटेक्चर आरेख
> **Languages**: [中文](../../diagrams/ARCHITECTURE-DIAGRAM.md) · [English](../../en/diagrams/ARCHITECTURE-DIAGRAM.md) · [한국어](../../ko/diagrams/ARCHITECTURE-DIAGRAM.md) · [Русский](../../ru/diagrams/ARCHITECTURE-DIAGRAM.md) · [Deutsch](../../de/diagrams/ARCHITECTURE-DIAGRAM.md) · [Français](../../fr/diagrams/ARCHITECTURE-DIAGRAM.md) · [Español](../../es/diagrams/ARCHITECTURE-DIAGRAM.md) · [Português](../../pt/diagrams/ARCHITECTURE-DIAGRAM.md) · [العربية](../../ar/diagrams/ARCHITECTURE-DIAGRAM.md) · [বাংলা](../../bn/diagrams/ARCHITECTURE-DIAGRAM.md) · [Bahasa Indonesia](../../id/diagrams/ARCHITECTURE-DIAGRAM.md) · [日本語](../../ja/diagrams/ARCHITECTURE-DIAGRAM.md)

```mermaid
graph TB
    subgraph user_terminal["उपयोगकर्ता टर्मिनल परत"]
        WX["वीचैट मिनी-प्रोग्राम<br/>apps/wechat/<br/>मूल WXML/WXSS/JS"]
        APP["Flutter APP<br/>apps/flutter/<br/>iOS + Android<br/>GetX + Dio"]
    end

    subgraph biz_service["व्यावसायिक सेवा परत :8787"]
        direction TB
        MW1["मिडलवेयर चेन<br/>Cors → Security → RateLimit"]
        subgraph api_module["API रूट मॉड्यूल"]
            PUB["सार्वजनिक API<br/>api/<br/>लॉगिन/पंजीकरण/सत्यापन कोड"]
            USER["उपयोगकर्ता मॉड्यूल<br/>user/<br/>प्रोफ़ाइल/पता/पसंदीदा"]
            TECH["तकनीशियन मॉड्यूल<br/>technician/<br/>शिड्यूल/वर्कबेंच/वेरिफिकेशन/आय/निकासी"]
            SVC["सेवा मॉड्यूल<br/>service/<br/>श्रेणियाँ/आइटम/खोज"]
            ORD["ऑर्डर मॉड्यूल<br/>order/<br/>कार्ट/ऑर्डर/भुगतान/रिफंड/वेरिफिकेशन"]
            MKT["मार्केटिंग मॉड्यूल<br/>marketing/<br/>कूपन/मेम्बर कार्ड(कोर्स कार्ड)/पॉइंट्स<br/>गिफ्ट कार्ड/मेम्बर लाभ"]
            WALLET["वॉलेट मॉड्यूल<br/>wallet/<br/>बैलेंस/टॉप-अप/लेन-देन हिस्ट्री<br/>बैलेंस भुगतान"]
            CTN["कंटेंट मॉड्यूल<br/>content/<br/>बैनर/घोषणाएँ/सूचनाएँ"]
            LBS["LBS मॉड्यूल<br/>lbs/<br/>शहर/आस-पास के स्टोर"]
            CACHE["Redis लिस्ट कैश<br/>svc:* प्रीफ़िक्स setex 300s<br/>श्रेणियाँ/आइटम/प्रोडक्ट/तकनीशियन/कंटेंट<br/>कार्ड/मार्केटिंग लिस्ट API<br/>admin राइट पाथ clearSvcCache() इनवैलिडेशन"]
            RES["रिस्पॉन्स कॉन्ट्रैक्ट<br/>success/paginate code=0<br/>गैर-शून्य एरर कोड<br/>मिनी-प्रोग्राम समझौते के अनुरूप"]
        end
    end

    subgraph admin_layer["प्रबंधन बैकएंड परत :8787"]
        MW2["मिडलवेयर चेन<br/>Cors → Security → RateLimit → AdminAuth → RBAC → OperationLog"]
        ADMIN_API["प्रबंधन API<br/>admin/controller/<br/>डैशबोर्ड/उपयोगकर्ता/तकनीशियन/स्टोर/सेवाएँ<br/>ऑर्डर/कूपन/मेम्बर कार्ड/निकासी/समीक्षाएँ<br/>रिपोर्ट/वित्त/कंटेंट/सेटिंग्स"]
        FLUTTER_WEB["Flutter Web फ्रंटएंड<br/>admin/apps/flutter/<br/>PC प्रबंधन बैकएंड इंटरफ़ेस"]
        MODEL["मॉडल शेयरिंग<br/>admin/app/model<br/>39 सिम्लिंक<br/>→ service/app/model समान इम्प्लीमेंटेशन"]
    end

    subgraph data_layer["डेटा परत"]
        MySQL[("MySQL 8.0<br/>55+ टेबल · appointment_ प्रीफ़िक्स<br/>BIGINT Snowflake प्राइमरी की")]
        Redis[("Redis<br/>कैश/रेट-लिमिट/Session<br/>क्यू/तकनीशियन लॉक<br/>svc:* लिस्ट कैश")]
        ES[("Elasticsearch<br/>फुल-टेक्स्ट खोज<br/>webman-scout ऑटो सिंक")]
    end

    subgraph ext_services["थर्ड-पार्टी सेवाएँ"]
        WXPAY["वीचैट पे<br/>यूनिफाइड ऑर्डर/रिफंड/निकासी"]
        SMS["SMS सेवा<br/>अलीयुन/टेंसेंट क्लाउड"]
        MAP["मैप सेवा<br/>गाओडे/टेंसेंट<br/>रिवर्स जियोकोडिंग/नेविगेशन"]
        OSS["ऑब्जेक्ट स्टोरेज<br/>लोकल/OSS/COS/CDN"]
        SUBMSG["वीचैट सब्सक्रिप्शन मैसेज<br/>WechatTemplateMessageService<br/>sendSubscribeMessage<br/>ऑर्डर इवेंट 3 सीन"]
    end

    subgraph sec_components["सुरक्षा कंपोनेंट परत"]
        SEC["Security-PHP<br/>31 प्रकार के अटैक डिटेक्टर"]
        JWT["JWT प्रमाणीकरण<br/>7 दिन वैधता + ब्लैकलिस्ट"]
        ENC["दोहरी एन्क्रिप्शन<br/>API परत + DB परत"]
        POSTER["ऑपरेशन वेरिफिकेशन<br/>संवेदनशील ऑपरेशन पर रैंडम वेरिफिकेशन"]
    end

    WX -->|"HTTP API<br/>फ़ंक्शन समतुल्य"| MW1
    APP -->|"HTTP API<br/>फ़ंक्शन समतुल्य"| MW1
    MW1 --> api_module

    FLUTTER_WEB -->|"HTTP API"| MW2
    MW2 --> ADMIN_API

    api_module --> MySQL
    api_module --> Redis
    api_module --> ES
    ADMIN_API --> MySQL
    ADMIN_API --> Redis
    ADMIN_API --> ES

    sec_components -.->|"सुरक्षा"| biz_service
    sec_components -.->|"सुरक्षा"| admin_layer

    api_module -.->|"कॉल"| ext_services
    ADMIN_API -.->|"कॉल"| ext_services

    classDef terminal fill:#e1f5fe,stroke:#0288d1,stroke-width:2px,color:#01579b
    classDef service fill:#fff3e0,stroke:#f57c00,stroke-width:2px,color:#e65100
    classDef admin fill:#e8f5e9,stroke:#388e3c,stroke-width:2px,color:#1b5e20
    classDef data fill:#fce4ec,stroke:#c62828,stroke-width:2px,color:#880e4f
    classDef external fill:#f3e5f5,stroke:#7b1fa2,stroke-width:2px,color:#4a148c
    classDef security fill:#fff8e1,stroke:#f9a825,stroke-width:2px,color:#f57f17

    class WX,APP terminal
    class MW1,api_module,PUB,USER,TECH,SVC,ORD,MKT,WALLET,CTN,LBS,CACHE,RES service
    class MW2,ADMIN_API,FLUTTER_WEB,MODEL admin
    class MySQL,Redis,ES data
    class WXPAY,SMS,MAP,OSS,SUBMSG external
    class SEC,JWT,ENC,POSTER security
```
