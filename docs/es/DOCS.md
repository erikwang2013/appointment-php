# Sistema de Reservas de Servicios — Índice de documentación
> **Languages**: [中文](../README.md) · [English](../en/DOCS.md) · [한국어](../ko/DOCS.md) · [Русский](../ru/DOCS.md) · [Deutsch](../de/DOCS.md) · [Français](../fr/DOCS.md) · [Português](../pt/DOCS.md) · [हिन्दी](../hi/DOCS.md) · [العربية](../ar/DOCS.md) · [বাংলা](../bn/DOCS.md) · [Bahasa Indonesia](../id/DOCS.md) · [日本語](../ja/DOCS.md)

> **Estado del proyecto**: todo completado ✅ | 143 controladores (service 69 / admin 74) | 87 modelos | 722 pruebas (service 558 / admin 164) | 95 tablas de datos | 388 rutas (service 227 / admin 161)

## Documentación principal

| Documento | Descripción |
|------|------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Descripción de la arquitectura: visión general del sistema, composición del proyecto, componentes principales, cadena de middleware, flujos de datos |
| [FEATURES.md](FEATURES.md) | Descripción de funciones: lista completa de funciones del extremo de usuario + panel de trabajo del técnico + panel de administración |
| [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) | Diseño de arquitectura: arquitectura en capas, diseño de middleware, diseño de base de datos, diseño de seguridad, integración ES |
| [FEATURE-DESIGN.md](FEATURE-DESIGN.md) | Diseño de funciones: flujo de compra, máquina de estados de pedidos, reglas de reembolso, diseño de tarjetas de membresía, cambio de identidad |
| [STRUCTURE.md](STRUCTURE.md) | Estructura del proyecto: diseño completo de directorios de los cuatro extremos, cadena de ejecución de middleware, lista de tablas de base de datos |
| [INSTALL.md](INSTALL.md) | Instrucciones de instalación: asistente de instalación web, instalación manual, despliegue Docker, variables de entorno, FAQ |
| [USAGE.md](USAGE.md) | Instrucciones de uso: operaciones del panel de administración / extremo de usuario / extremo de técnico (las interfaces API en [API.md](API.md)) |
| [API.md](API.md) | Documentación de API: API de negocio + API del panel de administración, con ejemplos de solicitud/respuesta + extremo OpenAPI |

## Pruebas y seguridad

| Documento | Descripción |
|------|------|
| [TEST-REPORT.md](TEST-REPORT.md) | Informe de pruebas: auditoría de cobertura completa de 558 casos / 2508 aserciones + registro de pruebas HTTP de humo |
| [AUDIT-REPORT.md](AUDIT-REPORT.md) | Informe de revisión: resultados de pruebas, evaluación de configuración del ecosistema, registro de correcciones de problemas, análisis de arquitectura de código |
| [SECURITY-AUDIT-REPORT.md](SECURITY-AUDIT-REPORT.md) | Informe de auditoría de seguridad |

## Base de datos y operaciones

| Documento | Descripción |
|------|------|
| [install.sql](../install.sql) | Script de instalación unificado: 67 migraciones combinadas, 2723 líneas, 95 tablas / 285 permisos / 38 configuraciones + datos de demostración |

## Especificaciones y planes

| Documento | Descripción |
|------|------|
| [superpowers/specs/2026-05-26-appointment-system-design.md](specs/2026-05-26-appointment-system-design.md) | Especificación de diseño del sistema |
| [superpowers/plans/2026-05-26-appointment-system-plan.md](plans/2026-05-26-appointment-system-plan.md) | Plan de implementación |

## Documentación del panel de administración

Documentación propia de `admin/`: ARCHITECTURE.md, DESIGN.md, SECURITY.md, API.md, nginx-security.conf.
