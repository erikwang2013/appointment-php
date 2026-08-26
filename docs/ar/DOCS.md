# نظام خدمات الحجز — فهرس الوثائق
> **Languages**: [中文](../README.md) · [English](../en/DOCS.md) · [한국어](../ko/DOCS.md) · [Русский](../ru/DOCS.md) · [Deutsch](../de/DOCS.md) · [Français](../fr/DOCS.md) · [Español](../es/DOCS.md) · [Português](../pt/DOCS.md) · [हिन्दी](../hi/DOCS.md) · [বাংলা](../bn/DOCS.md) · [Bahasa Indonesia](../id/DOCS.md) · [日本語](../ja/DOCS.md)

> **حالة المشروع**: مكتمل بالكامل ✅ | 143 وحدة تحكم (service 69 / admin 74) | 87 نموذجًا | 722 اختبارًا (service 558 / admin 164) | 95 جدول بيانات | 388 مسارًا (service 227 / admin 161)

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
