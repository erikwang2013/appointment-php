# अपॉइंटमेंट सेवा प्रणाली — दस्तावेज़ सूचकांक
> **Languages**: [中文](../README.md) · [English](../en/DOCS.md) · [한국어](../ko/DOCS.md) · [Русский](../ru/DOCS.md) · [Deutsch](../de/DOCS.md) · [Français](../fr/DOCS.md) · [Español](../es/DOCS.md) · [Português](../pt/DOCS.md) · [العربية](../ar/DOCS.md) · [বাংলা](../bn/DOCS.md) · [Bahasa Indonesia](../id/DOCS.md) · [日本語](../ja/DOCS.md)

> **परियोजना स्थिति**: सभी कार्य पूर्ण ✅ | 143 नियंत्रक (service 69 / admin 74) | 87 मॉडल | 757 परीक्षण (service 579 / admin 178) | 95 डेटा तालिकाएँ | 479 रूट (service 221 / admin 258)

## मुख्य दस्तावेज़

| दस्तावेज़ | विवरण |
|------|------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | आर्किटेक्चर विवरण: सिस्टम अवलोकन, परियोजना घटक, मुख्य घटक, मिडलवेयर श्रृंखला, डेटा प्रवाह |
| [FEATURES.md](FEATURES.md) | फ़ीचर विवरण: उपयोगकर्ता पक्ष + तकनीशियन वर्कबेंच + प्रबंधन बैकएंड पूर्ण फ़ीचर सूची |
| [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) | आर्किटेक्चर डिज़ाइन: लेयर्ड आर्किटेक्चर, मिडलवेयर डिज़ाइन, डेटाबेस डिज़ाइन, सुरक्षा डिज़ाइन, ES एकीकरण |
| [FEATURE-DESIGN.md](FEATURE-DESIGN.md) | फ़ीचर डिज़ाइन: खरीद प्रवाह, ऑर्डर स्टेट मशीन, रिफंड नियम, सदस्यता कार्ड डिज़ाइन, पहचान स्विच |
| [STRUCTURE.md](STRUCTURE.md) | परियोजना संरचना: चार पक्षों की पूर्ण निर्देशिका लेआउट, मिडलवेयर निष्पादन श्रृंखला, डेटाबेस टेबल सूची |
| [INSTALL.md](INSTALL.md) | इंस्टॉलेशन विवरण: वेब इंस्टॉल विज़ार्ड, मैन्युअल इंस्टॉलेशन, Docker डिप्लॉयमेंट, पर्यावरण चर, FAQ |
| [USAGE.md](USAGE.md) | उपयोग विवरण: प्रबंधन बैकएंड / उपयोगकर्ता पक्ष / तकनीशियन पक्ष संचालन (API इंटरफ़ेस के लिए [API.md](API.md) देखें) |
| [API.md](API.md) | API दस्तावेज़: बिज़नेस API + प्रबंधन बैकएंड API, अनुरोध प्रतिक्रिया उदाहरण + OpenAPI एंडपॉइंट सहित |

## आरेख (SVG)

सभी आरेख [diagrams/](diagrams/) में हैं, चीनी `cn-*` / अंग्रेज़ी `en-*` दोनों संस्करण, और 12 भाषाओं के अपने-अपने प्रतिबिंब `docs/<lang>/diagrams/` में:

| आरेख | विवरण | Mermaid स्रोत |
|------|------|-----------|
| [hi-architecture.svg](diagrams/hi-architecture.svg) | सिस्टम आर्किटेक्चर: चार पक्षों की स्तरित टोपोलॉजी + मिडलवेयर + डेटा परत + तृतीय-पक्ष सेवाएँ | [ARCHITECTURE-DIAGRAM.md](diagrams/ARCHITECTURE-DIAGRAM.md) |
| [hi-architecture-design.svg](diagrams/hi-architecture-design.svg) | आर्किटेक्चर डिज़ाइन: 7-परत लेयरिंग + मिडलवेयर निष्पादन श्रृंखला + दर-सीमा + डेटाबेस डिज़ाइन सिद्धांत + सुरक्षा डिज़ाइन | [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) |
| [hi-feature-design.svg](diagrams/hi-feature-design.svg) | फ़ीचर डिज़ाइन: तीन प्रमुख फ़ंक्शन क्षेत्र + खरीद प्रवाह + लेन-देन नियम + संपत्ति और अधिकार + तकनीशियन निपटान + पहचान स्विच + भुगतान | [FEATURE-DESIGN.md](FEATURE-DESIGN.md) |
| [hi-project-structure.svg](diagrams/hi-project-structure.svg) | परियोजना संरचना: चार पक्षों का निर्देशिका ट्री + मॉड्यूल विवरण | [STRUCTURE.md](STRUCTURE.md) |
| [hi-appointment-flow.svg](diagrams/hi-appointment-flow.svg) | सेवा अपॉइंटमेंट प्रवाह | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [hi-payment-refund.svg](diagrams/hi-payment-refund.svg) | भुगतान और रिफंड प्रवाह | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [hi-order-lifecycle.svg](diagrams/hi-order-lifecycle.svg) | ऑर्डर जीवनचक्र स्टेट मशीन | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [hi-lifecycle-overview.svg](diagrams/hi-lifecycle-overview.svg) | सभी जीवनचक्रों का अवलोकन (17, चार श्रेणियों में) | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [hi-security-defense.svg](diagrams/hi-security-defense.svg) | सात-परत गहराई-रक्षा प्रणाली | [SECURITY-ARCHITECTURE.md](diagrams/SECURITY-ARCHITECTURE.md) |
| [mascot.svg](diagrams/mascot.svg) | परियोजना शुभंकर "यूए" — कैलेंडर स्प्राइट (SMIL एनीमेशन, कोई बाह्य निर्भरता नहीं) | — |

## परीक्षण और सुरक्षा

| दस्तावेज़ | विवरण |
|------|------|
| [TEST-REPORT.md](TEST-REPORT.md) | परीक्षण रिपोर्ट: पूर्ण 558 मामले / 2508 एसर्शन कवरेज ऑडिट + HTTP स्मोक रिकॉर्ड |
| [AUDIT-REPORT.md](AUDIT-REPORT.md) | ऑडिट रिपोर्ट: परीक्षण परिणाम, पारिस्थितिकी कॉन्फ़िग स्कोर, समस्या सुधार रिकॉर्ड, कोड आर्किटेक्चर विश्लेषण |
| [SECURITY-AUDIT-REPORT.md](SECURITY-AUDIT-REPORT.md) | सुरक्षा ऑडिट रिपोर्ट |

## डेटाबेस और संचालन

| दस्तावेज़ | विवरण |
|------|------|
| [install.sql](../install.sql) | एकीकृत इंस्टॉल स्क्रिप्ट: 67 माइग्रेशन संयुक्त, 2723 पंक्तियाँ, 95 टेबल / 285 अनुमतियाँ / 38 कॉन्फ़िग + डेमो डेटा |

## विनिर्देश और योजना

| दस्तावेज़ | विवरण |
|------|------|
| [superpowers/specs/2026-05-26-appointment-system-design.md](specs/2026-05-26-appointment-system-design.md) | सिस्टम डिज़ाइन विनिर्देश |
| [superpowers/plans/2026-05-26-appointment-system-plan.md](plans/2026-05-26-appointment-system-plan.md) | कार्यान्वयन योजना |

## प्रबंधन बैकएंड दस्तावेज़

`admin/` अपने दस्तावेज़: ARCHITECTURE.md, DESIGN.md, SECURITY.md, API.md, nginx-security.conf।
