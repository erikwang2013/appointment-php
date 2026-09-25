# অ্যাপয়েন্টমেন্ট সার্ভিস সিস্টেম — ডকুমেন্টেশন সূচি
> **Languages**: [中文](../README.md) · [English](../en/DOCS.md) · [한국어](../ko/DOCS.md) · [Русский](../ru/DOCS.md) · [Deutsch](../de/DOCS.md) · [Français](../fr/DOCS.md) · [Español](../es/DOCS.md) · [Português](../pt/DOCS.md) · [हिन्दी](../hi/DOCS.md) · [العربية](../ar/DOCS.md) · [Bahasa Indonesia](../id/DOCS.md) · [日本語](../ja/DOCS.md)

> বাংলা অনুবাদ · মূল: [中文](../README.md)
>
> **প্রজেক্ট অবস্থা**: সম্পূর্ণ ✅ | ১৪৩ কন্ট্রোলার (service ৬৯ / admin ৭৪) | ৮৭ মডেল | ৭৫৭ টেস্ট (service ৫৭৯ / admin ১৭৮) | ৯৫ ডেটা টেবিল | ৪৭৯ রাউট (service ২২১ / admin ২৫৮)

## মূল ডকুমেন্টেশন

| ডকুমেন্ট | বিবরণ |
|------|------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | আর্কিটেকচার ব্যাখ্যা: সিস্টেম ওভারভিউ, প্রজেক্ট কম্পোনেন্ট, মূল কম্পোনেন্ট, মিডলওয়্যার চেইন, ডেটা ফ্লো |
| [FEATURES.md](FEATURES.md) | ফিচার ব্যাখ্যা: ব্যবহারকারী পাশ + টেকনিশিয়ান ওয়ার্কবেঞ্চ + অ্যাডমিন ব্যাকএন্ড সম্পূর্ণ ফিচার লিস্ট |
| [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) | আর্কিটেকচার ডিজাইন: লেয়ারড আর্কিটেকচার, মিডলওয়্যার ডিজাইন, ডেটাবেস ডিজাইন, সিকিউরিটি ডিজাইন, ES ইন্টিগ্রেশন |
| [FEATURE-DESIGN.md](FEATURE-DESIGN.md) | ফিচার ডিজাইন: কেনার ফ্লো, অর্ডার স্টেট মেশিন, রিফান্ড রুল, মেম্বার কার্ড ডিজাইন, পরিচয় স্যুইচ |
| [STRUCTURE.md](STRUCTURE.md) | প্রজেক্ট কাঠামো: চার প্রান্তের সম্পূর্ণ ডিরেক্টরি লেআউট, মিডলওয়্যার এক্সিকিউশন চেইন, ডেটাবেস টেবিল লিস্ট |
| [INSTALL.md](INSTALL.md) | ইনস্টল নির্দেশনা: Web ইনস্টল উইজার্ড, ম্যানুয়াল ইনস্টল, Docker ডিপ্লয়মেন্ট, এনভায়রনমেন্ট ভেরিয়েবল, FAQ |
| [USAGE.md](USAGE.md) | ব্যবহার নির্দেশনা: অ্যাডমিন ব্যাকএন্ড / ব্যবহারকারী পাশ / টেকনিশিয়ান পাশ অপারেশন (API ইন্টারফেস দেখুন [API.md](API.md)) |
| [API.md](API.md) | API ডকুমেন্টেশন: বিজনেস API + অ্যাডমিন ব্যাকএন্ড API, রিকোয়েস্ট/রেসপন্স উদাহরণ + OpenAPI এন্ডপয়েন্ট সহ |

## চিত্র (SVG)

সব চিত্র [diagrams/](diagrams/)-এ রয়েছে, চীনা `cn-*` / ইংরেজি `en-*` দুই সংস্করণে, এবং ১২টি ভাষার নিজস্ব অনুলিপি `docs/<lang>/diagrams/`-এ:

| চিত্র | বিবরণ | Mermaid সোর্স |
|------|------|-----------|
| [bn-architecture.svg](diagrams/bn-architecture.svg) | সিস্টেম আর্কিটেকচার: চার প্রান্তের স্তরভিত্তিক টোপোলজি + মিডলওয়্যার + ডেটা স্তর + তৃতীয় পক্ষের সার্ভিস | [ARCHITECTURE-DIAGRAM.md](diagrams/ARCHITECTURE-DIAGRAM.md) |
| [bn-architecture-design.svg](diagrams/bn-architecture-design.svg) | আর্কিটেকচার ডিজাইন: ৭-স্তরের লেয়ারিং + মিডলওয়্যার এক্সিকিউশন চেইন + রেট-লিমিট + ডেটাবেস ডিজাইন নিয়ম + সিকিউরিটি ডিজাইন | [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) |
| [bn-feature-design.svg](diagrams/bn-feature-design.svg) | ফিচার ডিজাইন: তিনটি প্রধান ফিচার ডোমেইন + ক্রয় প্রবাহ + লেনদেনের নিয়ম + সম্পদ ও অধিকার + টেকনিশিয়ান নিষ্পত্তি + ভূমিকা পরিবর্তন + পেমেন্ট | [FEATURE-DESIGN.md](FEATURE-DESIGN.md) |
| [bn-project-structure.svg](diagrams/bn-project-structure.svg) | প্রজেক্ট কাঠামো: চার প্রান্তের ডিরেক্টরি ট্রি + মডিউল বিবরণ | [STRUCTURE.md](STRUCTURE.md) |
| [bn-appointment-flow.svg](diagrams/bn-appointment-flow.svg) | সার্ভিস অ্যাপয়েন্টমেন্ট ফ্লো | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [bn-payment-refund.svg](diagrams/bn-payment-refund.svg) | পেমেন্ট ও রিফান্ড ফ্লো | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [bn-order-lifecycle.svg](diagrams/bn-order-lifecycle.svg) | অর্ডার লাইফসাইকেল স্টেট মেশিন | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [bn-lifecycle-overview.svg](diagrams/bn-lifecycle-overview.svg) | সব লাইফসাইকেলের সামগ্রিক চিত্র (১৭টি, চার শ্রেণিতে বিভক্ত) | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [bn-security-defense.svg](diagrams/bn-security-defense.svg) | গভীর প্রতিরক্ষার সাত স্তরের সিস্টেম | [SECURITY-ARCHITECTURE.md](diagrams/SECURITY-ARCHITECTURE.md) |
| [mascot.svg](diagrams/mascot.svg) | প্রজেক্ট মাসকট "ইউয়ে" — ক্যালেন্ডার স্প্রিট (SMIL অ্যানিমেশন, কোনো বাহ্যিক নির্ভরতা নেই) | — |

## টেস্ট ও নিরাপত্তা

| ডকুমেন্ট | বিবরণ |
|------|------|
| [TEST-REPORT.md](TEST-REPORT.md) | টেস্ট রিপোর্ট: ফুল ৫৭৯ কেস / ২৫০৮ অ্যাসারশন কভারেজ অডিট + HTTP স্মোক রেকর্ড |
| [AUDIT-REPORT.md](AUDIT-REPORT.md) | অডিট রিপোর্ট: টেস্ট ফলাফল, ইকোসিস্টেম কনফিগ স্কোর, সমস্যা ফিক্স রেকর্ড, কোড আর্কিটেকচার বিশ্লেষণ |
| [SECURITY-AUDIT-REPORT.md](SECURITY-AUDIT-REPORT.md) | সিকিউরিটি অডিট রিপোর্ট |

## ডেটাবেস ও অপারেশন

| ডকুমেন্ট | বিবরণ |
|------|------|
| [install.sql](../install.sql) | ইউনিফাইড ইনস্টল স্ক্রিপ্ট: ৬৭টি মাইগ্রেশন মার্জড, ২৭২৩ লাইন, ৯৫ টেবিল / ২৮৫ পারমিশন / ৩৮ কনফিগ + ডেমো ডেটা |

## স্পেক ও প্ল্যান

| ডকুমেন্ট | বিবরণ |
|------|------|
| [superpowers/specs/2026-05-26-appointment-system-design.md](specs/2026-05-26-appointment-system-design.md) | সিস্টেম ডিজাইন স্পেসিফিকেশন |
| [superpowers/plans/2026-05-26-appointment-system-plan.md](plans/2026-05-26-appointment-system-plan.md) | ইমপ্লিমেন্টেশন প্ল্যান |

## অ্যাডমিন ব্যাকএন্ড ডকুমেন্টেশন

`admin/` নিজস্ব ডকুমেন্টেশন: ARCHITECTURE.md、DESIGN.md、SECURITY.md、API.md、nginx-security.conf।
