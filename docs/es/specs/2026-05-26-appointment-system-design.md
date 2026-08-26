# Especificación de diseño del sistema de servicios de reservas
> **Languages**: [中文](../../superpowers/specs/2026-05-26-appointment-system-design.md) · [English](../../en/specs/2026-05-26-appointment-system-design.md) · [한국어](../../ko/specs/2026-05-26-appointment-system-design.md) · [Русский](../../ru/specs/2026-05-26-appointment-system-design.md) · [Deutsch](../../de/specs/2026-05-26-appointment-system-design.md) · [Français](../../fr/specs/2026-05-26-appointment-system-design.md) · [Português](../../pt/specs/2026-05-26-appointment-system-design.md) · [हिन्दी](../../hi/specs/2026-05-26-appointment-system-design.md) · [العربية](../../ar/specs/2026-05-26-appointment-system-design.md) · [বাংলা](../../bn/specs/2026-05-26-appointment-system-design.md) · [Bahasa Indonesia](../../id/specs/2026-05-26-appointment-system-design.md) · [日本語](../../ja/specs/2026-05-26-appointment-system-design.md)

## Resumen

Sistema de servicios de reservas de tres extremos: extremo de usuario (miniprograma de WeChat + APP Flutter) + puesto de trabajo de técnico (cambio de identidad dentro de la misma APP) + panel de administración (Web PC).

## Decisiones de arquitectura

| Decisión | Solución |
|------|------|
| Arquitectura backend | `admin/` (API del panel de administración) + `service/` (API de negocio), dos servicios que comparten MySQL/Redis |
| Miniprograma de usuario | Miniprograma nativo de WeChat `apps/wechat/` |
| APP de usuario | Flutter `apps/flutter/` (iOS + Android) |
| Identidad de usuario | Cuenta unificada, identidad de cliente/técnico conmutables |
| Relación miniprograma-APP | Funcionalidad idéntica, solo difiere la plataforma |
| Frontend del panel de administración | Extensión del Flutter Web existente (`admin/apps/flutter/`) |
| Backend del panel de administración | Extensión de los módulos de negocio del webman v2 existente (`admin/`) |
| Servicios de terceros | Inicio de sesión/pago de WeChat, SMS, mapas — solución de integración reservada |

## Diagrama de arquitectura del sistema

```
┌──────────────────────────────────────────────────────────┐
│                      Capa de terminal de usuario           │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ Miniprograma     │  │ Flutter APP       │              │
│  │ WeChat           │  │ apps/flutter/     │              │
│  │ apps/wechat/     │  │ (iOS + Android)   │              │
│  │ (WXML/WXSS nativo)│  └────────┬─────────┘              │
│  └────────┬─────────┘           │                         │
│           │          Funcionalidad idéntica  │             │
│           └──────────┬──────────┘                        │
│                      │ Cambio de identidad cliente/técnico │
├──────────────────────┼──────────────────────────────────┤
│              Puerta de enlace API de negocio               │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ API service/      │  │ API admin/        │              │
│  │ (webman v2)       │  │ (webman v2)       │              │
│  │ Usuario/pedido/   │  │ Interfaces del    │              │
│  │ pago/técnico/     │  │ panel (existentes │              │
│  │ tienda/marketing…  │  │ + extensión)     │              │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │                      │                        │
│           └──────────┬───────────┘                        │
│                      │                                    │
├──────────────────────┼──────────────────────────────────┤
│                   Capa de datos                            │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────────────┐    │
│  │ MySQL  │ │ Redis  │ │  ES    │ │ Servicios de   │    │
│  │ 8.0    │ │ Caché/ │ │ Búsqueda│ │ terceros       │    │
│  │        │ │ límite │ │        │ │ WeChat/SMS/    │    │
│  │        │ │ Session│ │        │ │ mapas (integración reservada) │
│  └────────┘ └────────┘ └────────┘ └────────────────┘    │
└──────────────────────────────────────────────────────────┘
```

## Tablas principales de la base de datos

Todas las tablas usan el prefijo `erik_` y claves primarias BIGINT no autoincrementales (generadas por Snowflake). Los campos sensibles se cifran/descifran con el trait encryptable.

### Dominio de usuario e identidad

| Nombre de tabla | Descripción | Campos principales |
|------|------|----------|
| `erik_user` | Tabla de usuario unificada | phone, password, wx_openid, wx_unionid, avatar, nickname, user_type(customer/technician), status. Los usuarios técnicos también tienen funcionalidad de cliente y pueden conmutar libremente la identidad activa |
| `erik_user_address` | Direcciones del usuario | user_id, contact_name, contact_phone, province, city, district, detail, is_default |
| `erik_technician_profile` | Expediente del técnico | user_id, real_name, gender, id_card, id_card_front, id_card_back, avatar, rating, order_count, status(pending/approved/rejected), intro |
| `erik_technician_schedule` | Horarios del técnico | technician_id, date, time_slots(JSON), status |
| `erik_technician_service` | Servicios que puede prestar el técnico | technician_id, service_id |
| `erik_technician_earnings` | Historial de ingresos del técnico | technician_id, order_id, type(commission/bonus/penalty), amount, status |
| `erik_technician_withdrawal` | Registros de retiro del técnico | technician_id, amount, actual_amount, commission_fee, account_info, status, reviewed_at |
| `erik_technician_attendance` | Asistencia del técnico | technician_id, date, check_in_at, check_out_at, clean_photo |
| `erik_technician_member_note` | Expediente de miembros | technician_id, user_id, content, written_at |

### Dominio de servicios y productos

| Nombre de tabla | Descripción | Campos principales |
|------|------|----------|
| `erik_service_category` | Categorías de servicio | name, icon, parent_id, sort, status |
| `erik_service` | Servicios | category_id, name, description, cover_image, images(JSON), price, duration, sales_volume, specs(JSON), status |
| `erik_product` | Productos | category_id, name, cover_image, price, stock, sales_volume, type, status |
| `erik_store` | Tiendas | name, address, lat, lng, phone, business_hours(JSON), images, status |

### Dominio de pedidos

| Nombre de tabla | Descripción | Campos principales |
|------|------|----------|
| `erik_order` | Tabla principal de pedidos | order_no, user_id, technician_id, store_id, total_amount, discount_amount, paid_amount, status, service_time, cancel_reason, remark |
| `erik_order_item` | Detalles del pedido | order_id, service_id, product_id, type, name, price, quantity, spec_info |
| `erik_order_payment` | Registros de pago | order_id, pay_type(wechat), transaction_id, amount, status, paid_at |
| `erik_order_refund` | Registros de reembolso | order_id, payment_id, refund_no, amount, ratio, reason, status |
| `erik_order_review` | Evaluaciones del servicio | order_id, user_id, technician_id, rating, content, images |
| `erik_order_verification` | Registros de verificación | order_id, code, verified_at, verified_by, location |

### Dominio de marketing

| Nombre de tabla | Descripción | Campos principales |
|------|------|----------|
| `erik_coupon` | Definición de cupones | name, type, amount, min_amount, total_qty, remain_qty, start_at, end_at, status |
| `erik_user_coupon` | Cupones del usuario | user_id, coupon_id, status(available/used/expired), used_at |
| `erik_member_card` | Definición de tarjetas de membresía | name, type(month/vip/times), price, duration_days, total_times, services(JSON) |
| `erik_user_member_card` | Tarjetas de membresía del usuario | user_id, card_id, start_at, end_at, total_times, used_times, status |
| `erik_member_card_usage` | Registros de uso de tarjetas por uso | user_card_id, order_id, service_id, used_at |
| `erik_user_points` | Historial de puntos | user_id, type(earn/use), points, source, order_id |
| `erik_gift_card` | Tarjetas regalo | code, type, amount_or_gift, status, used_by, used_at |
| `erik_user_referral` | Promoción de usuarios | referrer_id, referred_user_id, reward_type, reward_amount, registered_at, first_order_at |

### Dominio de contenido y notificaciones

| Nombre de tabla | Descripción | Campos principales |
|------|------|----------|
| `erik_banner` | Banners | position, image, jump_type(url/detail/none), jump_value, sort, status |
| `erik_announcement` | Avisos | content, status, published_at |
| `erik_platform_agreement` | Acuerdos de plataforma | type(user_agreement/privacy_policy/service_agreement), title, content, version |
| `erik_faq` | Preguntas frecuentes | title, content, sort |
| `erik_feedback` | Comentarios y sugerencias | user_id, content, images, handler_reply, status(pending/handled) |
| `erik_moment` | Momentos | content, images, published_at |
| `erik_notification` | Notificaciones de mensajes | user_id, type(order/system), title, content, is_read, created_at |

### Dominio financiero (lado admin)

| Nombre de tabla | Descripción | Campos principales |
|------|------|----------|
| `erik_finance_transaction` | Historial de ingresos y gastos | user_id, order_id, type, direction(income/expense), amount, actual_amount, commission, status |
| `erik_technician_commission_config` | Configuración de comisiones | technician_id, commission_rate, settlement_cycle |
| `erik_withdrawal_account` | Cuentas de retiro | user_id, type(wechat), account_name, account_no |
| `erik_withdrawal_config` | Configuración de límites de retiro | min_amount, reserve_amount, round_to_hundred |

## Módulos de API de Service

### API públicas (sin autenticación)
- **AuthController** — inicio de sesión/registro/olvido de contraseña/modo invitado/cambio de identidad
- **CaptchaController** — código de verificación SMS
- **WechatController** — autorización/inicio de sesión WeChat/devolución de llamada de pago
- **CommonController** — texto de acuerdos/sobre nosotros/información de versión

### Módulo de usuario `user/` (con autenticación)
- **ProfileController** — información personal/cambio de contraseña/cambio de teléfono/baja
- **AddressController** — CRUD de direcciones de entrega
- **FavoriteController** — favoritos
- **FeedbackController** — comentarios y sugerencias
- **ReferralController** — promoción/lista de usuarios recomendados

### Módulo de técnico `technician/` (requiere identidad de técnico + middleware TechnicianAuth)
- **ProfileController** — expediente del técnico/solicitud de incorporación
- **ScheduleController** — configuración de horarios
- **OrderController** — reservados sin verificar/completados/verificación con escaneo
- **MemberController** — mis miembros/expedientes de miembros
- **EarningsController** — ingresos/fondos en tránsito
- **WithdrawalController** — retiros
- **AttendanceController** — asistencia/fotos de higiene

### Módulo de servicios `service/`
- **CategoryController** — categorías de servicio
- **ItemController** — lista y detalle de servicios/productos
- **SearchController** — búsqueda
- **StoreController** — lista/detalle de tiendas

### Módulo de pedidos `order/` (con autenticación)
- **CartController** — carrito de compra
- **OrderController** — realizar pedido/lista de pedidos/detalle/cancelación
- **PaymentController** — pago/reembolso
- **VerificationController** — verificación con código QR
- **ReviewController** — evaluación

### Módulo de marketing `marketing/` (con autenticación)
- **CouponController** — lista/recogida/uso de cupones
- **MemberCardController** — tarjetas de membresía/tarjetas por uso
- **PointsController** — puntos
- **GiftCardController** — tarjetas regalo

### Módulo de contenido `content/`
- **BannerController** — banners
- **AnnouncementController** — avisos
- **NotificationController** — notificaciones de mensajes

### Módulo LBS
- **LocationController** — localización/cambio de ciudad/tiendas cercanas

### Capacidades comunes `common/`
- SnowflakeService — generación de ID
- HashidsService — cifrado/descifrado de ID
- EncryptionService — cifrado/descifrado de datos sensibles
- WechatPayService — pago WeChat (reservado)
- WechatAuthService — inicio de sesión WeChat (reservado)
- SmsService — servicio de SMS (reservado)
- MapService — servicio de mapas (reservado)

### Middleware
- Auth — autenticación JWT (comparte el paquete erikwang2013/jwt-webman con admin)
- TechnicianAuth — verificación de identidad de técnico
- RateLimit — limitación de tráfico (compartido con admin)

## Extensión del panel de administración de Admin

Nuevos controladores sobre el framework existente:

### Gestión de técnicos
- **TechnicianController** — lista/búsqueda/exportación/auditoría de técnicos/gestión de horarios/configuración de servicios técnicos/progreso de aprendizaje de cursos

### Extensión de gestión de usuarios
- **MemberController** — lista de miembros/configuración de niveles/estadísticas de consumo

### Gestión de tiendas
- **StoreController** — CRUD de tiendas/habilitar-deshabilitar

### Gestión de servicios
- **ServiceController** — lista/CRUD de servicios/diseño de tarjetas
- **ServiceCategoryController** — gestión de categorías
- **ProductController** — lista/CRUD de productos

### Gestión de la tienda online
- **MallOrderController** — pedidos de la tienda/envío/posventa/evaluación
- **SalesStatsController** — estadísticas de ventas

### Gestión de pedidos
- **AppointmentOrderController** — pedidos pendientes de uso/cancelación/confirmación de finalización

### Actividades con cupones
- **CouponController** — CRUD/emisión de cupones

### Gestión financiera
- **FinanceController** — reparto de pedidos/historial de ingresos y gastos
- **WithdrawalController** — auditoría/finalización de retiros de técnicos
- **CommissionController** — configuración de comisiones/premios y multas/consulta de saldos
- **WithdrawalAccountController** — gestión de cuentas de retiro
- **WithdrawalConfigController** — configuración de límites de retiro

### Gestión de contenido
- **BannerController** — CRUD de banners
- **AnnouncementController** — CRUD de avisos
- **FaqController** — CRUD de FAQ
- **FeedbackController** — tratamiento de comentarios y sugerencias
- **MomentController** — auditoría de momentos
- **AgreementController** — edición de acuerdos (acuerdo de usuario/acuerdo de privacidad/acuerdo de servicio)
- **AboutController** — configuración de «sobre nosotros»

### Ajustes
- **SystemMessageController** — configuración de mensajes del sistema
- **AdminUserController** — gestión de subcuentas (basada en el RBAC existente)

### Extensión del Dashboard
- Tarjetas de estadísticas en tiempo real: número de usuarios/total de pedidos/número de técnicos/número de pedidos de servicios
- Gráfico de líneas: volumen de pedidos/importes/nuevos usuarios diarios/actividad
- Navegación rápida: botones de módulos pendientes de tratamiento
- Mensajes internos: notificaciones de nuevos pedidos/notificaciones de reembolso

## Estructura de páginas del extremo de usuario

El miniprograma de WeChat y la APP Flutter tienen funcionalidad idéntica.

### auth/ — autenticación
- login — inicio de sesión (teléfono/código de verificación/WeChat/entrada de invitado)
- register — registro (teléfono + código de verificación + contraseña + código de recomendación)
- forget-password — olvido de contraseña
- agreement — consulta de acuerdos

### home/ — inicio
- index — página de inicio (banners + avisos + categorías de servicio + recomendaciones)
- search — página de búsqueda

### service/ — servicios
- list — lista de servicios (filtro por categoría)
- detail — detalle del servicio (información básica + evaluaciones + reservar ahora)
- product-list — lista de productos

### order/ — pedidos
- confirm — confirmar pedido (tienda/técnico/hora/cupón/notas/acuerdo)
- payment — página de pago
- payment-success — pago correcto
- list — todos los pedidos (filtro por pestaña de estado)
- detail — detalle del pedido
- review — evaluación del servicio
- verification — verificación con código QR

### cart/ — carrito
- index — lista del carrito

### technician/ — técnicos (vista de cliente)
- list — lista de técnicos (ordenada de cerca a lejos)
- detail — detalle del técnico (evaluaciones/servicios disponibles/reservar ahora)
- apply — solicitud de incorporación de técnico

### tech-work/ — puesto de trabajo del técnico (identidad de técnico)
- index — página principal del puesto de trabajo (pedidos de hoy/resumen de ingresos)
- schedule — configuración de horarios
- order-list — mis pedidos (reservados sin verificar/completados)
- scan-verify — verificación con escaneo
- member-list — mis miembros
- member-detail — detalle del miembro/edición del expediente
- earnings — mis ingresos
- withdrawal — retiros
- transaction-list — detalle de transacciones
- attendance — asistencia/subida de fotos de higiene
- training — formación profesional

### user/ — centro personal
- index — información personal (avatar/apodo/tarjeta de membresía/favoritos/acceso a cupones)
- settings — ajustes (cambio de contraseña/cambio de teléfono/acuerdos/actualización/baja/cierre de sesión)
- switch-role — cambio de identidad (cliente ↔ técnico)

### marketing/ — marketing
- coupon-list — lista de cupones
- member-card — mis tarjetas de membresía
- points — mis puntos
- gift-card — mis tarjetas regalo
- referral — promoción (explicación + cartel con código QR + lista de usuarios recomendados)

### Otras páginas
- message/ — lista/detalle de mensajes
- store/list, store/detail — lista de tiendas (ordenación LBS)/detalle (navegación)
- other/about — sobre nosotros
- other/feedback — comentarios y sugerencias
- other/official-account — seguir la cuenta oficial

### Componentes comunes
- navbar, tabbar, service-card, technician-card
- coupon-popup, lbs-selector, empty-state, loading

### Lógica de cambio de identidad
- Navegación inferior del cliente: inicio / servicios / carrito / pedidos / mi
- Navegación inferior del técnico: puesto de trabajo / pedidos / miembros / ingresos / mi
- La página «Mi» ofrece la entrada al cambio de identidad
- Los usuarios que aún no son técnicos son dirigidos a la página de solicitud de incorporación al cambiar a identidad de técnico

## Explicación de los flujos de compra

El sistema tiene dos flujos de compra diferentes:

### Flujo de reserva de servicios (pedido directo, sin carrito)
- Página de detalle del servicio → confirmar pedido (elegir tienda/técnico/hora) → pago → verificación
- Recurso de técnico exclusivo: al entrar en la página de confirmación de pedido se bloquea al técnico durante 3 minutos
- Se usa para servicios presenciales como masajes, belleza, etc.

### Flujo de compra de productos (modo carrito)
- Lista de productos → añadir al carrito → confirmación del carrito → enviar pedido → pago → envío/recepción
- Permite modificar la cantidad y eliminar productos
- Se usa para venta de productos físicos o tarjetas/cupones

## Reglas de negocio clave

### Mecanismo de bloqueo del técnico
- Varias personas no pueden reservar al mismo técnico a la misma hora
- Al entrar en la página de confirmación de pedido, se bloquea al técnico 3 minutos con Redis SETNX
- El bloqueo se libera automáticamente al salir de la página de reserva o al expirar

### Reglas de reembolso
| Condición | Proporción de reembolso |
|------|----------|
| Dentro de los 15 minutos del pedido o a >6 horas del inicio | 100% |
| A ≤6 horas del inicio | 90% |
| Empezado pero sin confirmar el servicio | 80% |
| Después de confirmar el inicio del servicio | 0% (sin reembolso) |

### Reglas de descuento
- Horas valle (10-12 h/17-18 h/después de las 21:00): 9 % de descuento
- Reserva con 30 minutos de antelación: 95 % (no acumulable con cupones)

### Retiros de técnicos
- Retiro disponible el día 20 de cada mes; T+1 día hábil de llegada
- Admite retiro a la billetera de WeChat
- Los pedidos verificados sin liquidar se confirman automáticamente en 3 días
- El expediente de miembros debe completarse en 24 horas; si no, no hay comisión

### Recompensa de cliente habitual
- Segunda compra al mismo técnico dentro de 30 días → se registra un bono
- Subir foto de higiene tras el servicio

### Reglas de puntos
- Canje 1:100 por tarjeta regalo (configurable en el panel)
- Tras registrarse y hacer un pedido un usuario recomendado, se obtienen los puntos especificados (configurable en el panel)
