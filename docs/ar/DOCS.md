# نظام خدمات الحجز — فهرس الوثائق
> **Languages**: [中文](../README.md) · [English](../en/DOCS.md) · [한국어](../ko/DOCS.md) · [Русский](../ru/DOCS.md) · [Deutsch](../de/DOCS.md) · [Français](../fr/DOCS.md) · [Español](../es/DOCS.md) · [Português](../pt/DOCS.md) · [हिन्दी](../hi/DOCS.md) · [বাংলা](../bn/DOCS.md) · [Bahasa Indonesia](../id/DOCS.md) · [日本語](../ja/DOCS.md)

> **حالة المشروع**: مكتمل بالكامل ✅ | 143 وحدة تحكم (service 69 / admin 74) | 87 نموذجًا | 757 اختبارًا (service 579 / admin 178) | 95 جدول بيانات | 479 مسارًا (service 221 / admin 258)

## الوثائق الأساسية

| الوثيقة | الوصف |
|------|------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | شرح البنية: نظرة عامة على النظام، مكونات المشروع، المكونات الأساسية، سلسلة الوسائط، تدفقات البيانات |
| [FEATURES.md](FEATURES.md) | شرح الميزات: قائمة الميزات الكاملة لواجهة المستخدم + لوحة عمل الفني + لوحة الإدارة |
| [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) | تصميم البنية: بنية الطبقات، تصميم الوسائط، تصميم قاعدة البيانات، التصميم الأمني، تكامل ES |
| [FEATURE-DESIGN.md](FEATURE-DESIGN.md) | تصميم الميزات: عملية الشراء، آلة حالة الطلب، قواعد الاسترداد، تصميم بطاقات العضوية، تبديل الهوية |
| [STRUCTURE.md](STRUCTURE.md) | هيكل المشروع: تخطيط الدليل الكامل للواجهات الأربع، سلسلة تنفيذ الوسائط، قائمة جداول قاعدة البيانات |
| [INSTALL.md](INSTALL.md) | تعليمات التثبيت: معالج التثبيت عبر الويب، التثبيت اليدوي، نشر Docker، متغيرات البيئة، الأسئلة الشائعة |
| [USAGE.md](USAGE.md) | تعليمات الاستخدام: عمليات لوحة الإدارة / واجهة المستخدم / واجهة الفني (واجهات API في [API.md](API.md)) |
| [API.md](API.md) | وثائق API: واجهات الأعمال + واجهات لوحة الإدارة، مع أمثلة طلب/استجابة + نقاط OpenAPI |

## الرسوم (SVG)

جميع الرسوم في [diagrams/](diagrams/)، بنسختين: الصينية `cn-*` والإنجليزية `en-*`، ولكل من اللغات الـ 12 نسختها المطابقة في `docs/<lang>/diagrams/`:

| الرسم | الوصف | مصدر Mermaid |
|------|------|-----------|
| [ar-architecture.svg](diagrams/ar-architecture.svg) | بنية النظام: الطوبولوجيا الطبقية للواجهات الأربع + الوسائط + طبقة البيانات + خدمات خارجية | [ARCHITECTURE-DIAGRAM.md](diagrams/ARCHITECTURE-DIAGRAM.md) |
| [ar-architecture-design.svg](diagrams/ar-architecture-design.svg) | تصميم البنية: 7 طبقات + سلسلة تنفيذ الوسائط + تحديد المعدل + مبادئ تصميم قاعدة البيانات + التصميم الأمني | [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) |
| [ar-feature-design.svg](diagrams/ar-feature-design.svg) | تصميم المزايا: المجالات الوظيفية الثلاثة + مسارات الشراء + قواعد المعاملات + الأصول والحقوق + تسوية الفنيين + تبديل الهوية + الدفع | [FEATURE-DESIGN.md](FEATURE-DESIGN.md) |
| [ar-project-structure.svg](diagrams/ar-project-structure.svg) | هيكل المشروع: شجرة دليل الواجهات الأربع + تفاصيل الوحدات | [STRUCTURE.md](STRUCTURE.md) |
| [ar-appointment-flow.svg](diagrams/ar-appointment-flow.svg) | عملية حجز الخدمة | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [ar-payment-refund.svg](diagrams/ar-payment-refund.svg) | عملية الدفع والاسترداد | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [ar-order-lifecycle.svg](diagrams/ar-order-lifecycle.svg) | آلة حالة دورة حياة الطلب | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [ar-lifecycle-overview.svg](diagrams/ar-lifecycle-overview.svg) | نظرة شاملة على جميع دورات الحياة (17 دورة، مصنفة إلى أربع فئات) | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [ar-security-defense.svg](diagrams/ar-security-defense.svg) | نظام الدفاع العميق من سبع طبقات | [SECURITY-ARCHITECTURE.md](diagrams/SECURITY-ARCHITECTURE.md) |
| [mascot.svg](diagrams/mascot.svg) | تميمة المشروع «يُوِه» — روح التقويم (رسوم SMIL متحركة، دون أي تبعيات خارجية) | — |

## الاختبارات والأمان

| الوثيقة | الوصف |
|------|------|
| [TEST-REPORT.md](TEST-REPORT.md) | تقرير الاختبار: تدقيق تغطية 558 حالة / 2508 تأكيدًا + سجل اختبار HTTP |
| [AUDIT-REPORT.md](AUDIT-REPORT.md) | تقرير المراجعة: نتائج الاختبار، تقييم تكوين النظام البيئي، سجل إصلاح المشكلات، تحليل بنية الكود |
| [SECURITY-AUDIT-REPORT.md](SECURITY-AUDIT-REPORT.md) | تقرير تدقيق الأمان |

## قاعدة البيانات والعمليات

| الوثيقة | الوصف |
|------|------|
| [install.sql](../install.sql) | نص التثبيت الموحد: دمج 67 ترحيلًا، 2723 سطرًا، 95 جدولًا / 285 صلاحية / 38 إعدادًا + بيانات تجريبية |

## المواصفات والخطط

| الوثيقة | الوصف |
|------|------|
| [superpowers/specs/2026-05-26-appointment-system-design.md](specs/2026-05-26-appointment-system-design.md) | مواصفات تصميم النظام |
| [superpowers/plans/2026-05-26-appointment-system-plan.md](plans/2026-05-26-appointment-system-plan.md) | خطة التنفيذ |

## وثائق لوحة الإدارة

وثائق `admin/` الخاصة: ARCHITECTURE.md、DESIGN.md、SECURITY.md、API.md、nginx-security.conf。
