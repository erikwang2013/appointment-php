# Sistema de Reservas de Servicios — Estructura del proyecto
> **Languages**: [中文](../STRUCTURE.md) · [English](../en/STRUCTURE.md) · [한국어](../ko/STRUCTURE.md) · [Русский](../ru/STRUCTURE.md) · [Deutsch](../de/STRUCTURE.md) · [Français](../fr/STRUCTURE.md) · [Português](../pt/STRUCTURE.md) · [हिन्दी](../hi/STRUCTURE.md) · [العربية](../ar/STRUCTURE.md) · [বাংলা](../bn/STRUCTURE.md) · [Bahasa Indonesia](../id/STRUCTURE.md) · [日本語](../ja/STRUCTURE.md)

## Visión general del repositorio

```
appointment-php/
├── admin/              # Panel de administración (webman v2 + Flutter Web)
├── service/            # Servicio de API de negocio (webman v2)
├── apps/               # Aplicaciones frontend del extremo de usuario
│   ├── wechat/         #   Mini programa WeChat (nativo)
│   ├── flutter/        #   APP Flutter (iOS + Android)
│   └── harmonyos/      #   APP HarmonyOS (nativo HarmonyOS)
├── docs/               # Documentación del proyecto
└── .claude/            # Configuración de Claude Code
```

## Relaciones del proyecto

```
┌──────────────────────────────────────────────┐
│                   apps/                       │
│  ┌─────────────┐  ┌──────────┐  ┌─────────┐  │
│  │ wechat/      │  │ flutter/  │  │harmonyos/│  │
│  │ mini programa│  │iOS/Android│  │ APP HarmonyOS│
│  └──────┬──────┘  └────┬─────┘  └────┬────┘  │
│         │   funciones idénticas      │        │
│         └──────────┬─────────┘                │
│                    │ HTTP API                 │
├────────────────────┼─────────────────────────┤
│              service/                         │
│         API de negocio (webman v2)            │
│              Puerto: 8787                     │
│                    │                          │
│                    │ MySQL/Redis/ES compartidos│
│                    │                          │
│              admin/                           │
│      API del panel (webman v2)                │
│              Puerto: 8787                     │
│                    │                          │
│         ┌──────────┴──────────┐               │
│         │                     │               │
│    admin/apps/flutter/    Flutter Web         │
│     Frontend del panel (PC)                   │
└──────────────────────────────────────────────┘
```

## admin/ — Panel de administración

```
admin/
├── app/
│   ├── admin/controller/       # Controladores del extremo de administración
│   │   ├── BaseController          # Controlador base
│   │   ├── DashboardController     # Dashboard
│   │   ├── UserController          # Gestión de usuarios
│   │   ├── RoleController          # Gestión de roles
│   │   ├── PermissionController    # Gestión de permisos
│   │   ├── ConfigController        # Configuración del sistema
│   │   ├── LogController           # Registros de operaciones
│   │   ├── ProfileController       # Centro personal
│   │   ├── ExportController        # Exportación
│   │   ├── ImportController        # Importación
│   │   ├── UploadController        # Carga de archivos
│   │   ├── HealthController        # Comprobación de salud
│   │   ├── DocsController          # Documentación de API
│   │   ├── MetricsController       # Métricas Prometheus
│   │   │                            # ✅ Módulos de negocio implementados:
│   │   ├── TechnicianController    #   Gestión de técnicos (lista/revisión/horarios/exportación)
│   │   ├── MemberController        #   Gestión de miembros (nivel/consumo)
│   │   ├── StoreController         #   CRUD de tiendas
│   │   ├── ServiceController       #   CRUD de servicios
│   │   ├── ServiceCategoryController # CRUD de categorías de servicios (árbol)
│   │   ├── ProductController       #   CRUD de productos
│   │   ├── MallOrderController     #   Pedidos de la tienda/envío/posventa
│   │   ├── SalesStatsController    #   Estadísticas de ventas (caché Redis)
│   │   ├── AppointmentOrderController  # Pedidos de reserva (cancelar/completar)
│   │   ├── MemberCardController    #   CRUD de definiciones de tarjetas de membresía
│   │   ├── ReviewController        #   Gestión de evaluaciones de servicios
│   │   ├── ReportController        #   Estadísticas de informes de datos
│   │   ├── CouponController        #   CRUD de cupones
│   │   ├── FinanceController       #   Historial financiero/estadísticas
│   │   ├── WithdrawalController    #   Revisión de retiros (aprobar/rechazar/completar)
│   │   ├── CommissionController    #   Configuración de comisiones/premios y penalizaciones
│   │   ├── WithdrawalAccountController # Gestión de cuentas de retiro
│   │   ├── WithdrawalConfigController  # Configuración de límites de retiro
│   │   ├── BannerController        #   CRUD de banners
│   │   ├── AnnouncementController  #   CRUD/publicación de anuncios
│   │   ├── FaqController           #   CRUD de preguntas frecuentes
│   │   ├── FeedbackController      #   Comentarios/respuestas
│   │   ├── MomentController        #   Revisión de momentos
│   │   ├── AgreementController     #   Edición/publicación de acuerdos
│   │   ├── AboutController         #   Configuración de «Sobre nosotros»
│   │   └── SystemMessageController #   Plantillas/envío de mensajes del sistema
│   │   │                            # ✅ Módulos extendidos:
│   │   ├── ServiceCardController    #   Diseño de tarjetas
│   │   ├── SystemMonitorController  #   Monitorización del sistema
│   │   ├── IpBlacklistController    #   Gestión de lista negra de IP
│   │   ├── DbBackupController       #   Respaldo de base de datos
│   │   ├── SmsConfigController      #   Configuración de SMS
│   │   ├── StorageConfigController  #   Configuración de almacenamiento
│   │   ├── StoreManagerController   #   Cuentas de gerente de tienda
│   │   ├── TrainingController       #   Formación de técnicos
│   │   ├── ScheduledTaskController  #   Tareas programadas
│   │   ├── CustomerProfileController #  Perfil del cliente
│   │   ├── BatchMessageController   #   Push por lotes
│   │   ├── RefundWorkflowController #   Revisión de reembolsos
│   │   ├── TechnicianTierController #   Niveles de técnico
│   │   │                            # ✅ Nuevos de las rondas 22-25:
│   │   ├── FullReductionController  #   Actividades de reducción de importe
│   │   ├── AttendanceController     #   Asistencia de técnicos
│   │   ├── ProfitSharingController  #   Reparto de WeChat
│   │   ├── LuckyWheelController     #   Ruleta de puntos
│   │   ├── PointsExchangeGoodsController # Productos de canje de puntos
│   │   ├── ReviewAuditController    #   Revisión de imágenes de evaluaciones
│   │   ├── InvoiceController        #   Factura electrónica
│   │   ├── TicketController         #   Tickets de atención al cliente
│   │   ├── ReferralRewardController #   Registros de comisión de nivel 1
│   │   ├── ReferralLevel2Controller #   Registros de comisión de nivel 2
│   │   ├── ReturnCustomerController #   Recompensa de cliente habitual
│   │   ├── SeckillController        #   Actividades de oferta flash
│   │   ├── VersionController        #   Gestión de versiones de APP
│   │   ├── TechnicianScheduleController # Gestión de horarios/exportación CSV
│   │   ├── AftersaleController      #   Gestión de posventa
│   │   ├── OrderVerificationController # Registros de verificación
│   │   ├── CommunityModerationController # Revisión de la comunidad
│   │   ├── VideoAuditController     #   Revisión de vídeos
│   │   └── InstallController        #   Asistente de instalación
│   ├── api/v1/controller/      # API pública v1
│   │   ├── AuthController
│   │   └── CaptchaController
│   ├── common/                 # Utilidades comunes
│   │   ├── HashidsService
│   │   ├── SnowflakeService
│   │   ├── EncryptionService
│   │   ├── TechnicianWithdrawalService
│   │   └── WechatPayService
│   ├── middleware/             # Middleware
│   │   ├── Cors
│   │   ├── RateLimit
│   │   ├── ApiVersion
│   │   ├── AdminAuth
│   │   ├── AdminPermission
│   │   └── OperationLog
│   ├── model/                  # Modelos de datos (solo 6 específicos: AdminPermission/AdminRole/AdminUser/OperationLog/OperationLogDetail/SystemConfig; el resto se comparten con service vía psr-4)
│   ├── queue/                  # Tareas de cola
│   └── process/                # Procesos
├── apps/
│   ├── flutter/                # Frontend del panel en Flutter Web
│   │   └── lib/app/
│   │       ├── pages/           #   Páginas (20)
│   │       │   ├── dashboard/   #   Dashboard
│   │       │   ├── login/       #   Inicio de sesión
│   │       │   ├── user/        #   Gestión de usuarios
│   │       │   ├── member/      #   Gestión de miembros
│   │       │   ├── role/        #   Permisos de roles
│   │       │   ├── config/      #   Configuración del sistema
│   │       │   ├── log/         #   Registros de operaciones
│   │       │   ├── profile/     #   Centro personal
│   │       │   ├── technician/  #   Gestión de técnicos
│   │       │   ├── schedule/    #   Horarios
│   │       │   ├── service/     #   Gestión de servicios/productos
│   │       │   ├── service_card/#   Diseño de tarjetas
│   │       │   ├── order/       #   Gestión de pedidos
│   │       │   ├── verification/#   Registros de verificación
│   │       │   ├── coupon/      #   Cupones
│   │       │   ├── withdrawal/  #   Revisión de retiros
│   │       │   ├── report/      #   Estadísticas de informes
│   │       │   ├── review/      #   Gestión de evaluaciones
│   │       │   ├── announcement/#   Anuncios
│   │       │   └── faq/         #   Preguntas frecuentes
│   │       ├── services/        #   Capa de servicios API
│   │       ├── layouts/         #   Diseños
│   │       └── theme/           #   Tema
│   ├── harmonyos/               # Extremo de administración HarmonyOS (ArkTS)
│   └── weixin/                  # Extremo de administración WeChat
├── config/                     # Archivos de configuración
│   ├── route.php
│   ├── middleware.php
│   ├── database.php
│   ├── jwt.php
│   ├── snowflake.php
│   ├── hashids.php
│   ├── encryption.php
│   ├── encryptable.php
│   └── ...
├── database/
│   └── backup/                 # Scripts de respaldo (estructura de tablas y datos semilla unificados en docs/install.sql)
├── docs/                       # Documentación del panel de administración
├── public/                     # Archivos de entrada
├── runtime/                    # Tiempo de ejecución
├── tests/                      # Pruebas
├── vendor/                     # Dependencias
├── CLAUDE.md
├── composer.json
├── Dockerfile
└── docker-compose.yml
```

## service/ — API de negocio

```
service/
├── app/
│   ├── api/v1/controller/       # API pública v1 (26 controladores)
│   │   ├── AuthController          # Inicio de sesión/registro/contraseña olvidada/renovación/cambio de identidad
│   │   ├── CaptchaController       # Código de verificación SMS (límite de tráfico Redis)
│   │   ├── CommonController        # Configuración pública/acuerdos/regiones
│   │   ├── ContentController       # Banners/anuncios/artículos
│   │   ├── DocsController          # Documentación OpenAPI (hg/apidoc)
│   │   ├── LbsController           # Tiendas cercanas (Haversine)/geocodificación inversa
│   │   ├── GuestController         # Modo invitado (navegación de solo lectura sin inicio de sesión, caché Redis)
│   │   ├── SeckillController       # Actividades/compra de oferta flash (canal independiente)
│   │   ├── PromotionController     # Compra grupal (el antiguo canal flash_sale se ha retirado)
│   │   ├── ServiceController       # Categorías de servicios/servicios/productos/tiendas
│   │   ├── ServicePackageController # Paquetes de servicios
│   │   ├── StoreManagerController  # Panel de trabajo del gerente de tienda (overview/orders/technicians/revenue)
│   │   ├── TechnicianController    # Información pública de técnicos
│   │   ├── BrowseHistoryController # Historial de navegación
│   │   ├── CalendarController      # Calendario de reservas (vistas mensual/diaria)
│   │   ├── CommunityController     # Dinámicas de la comunidad
│   │   ├── CommunityCommentController # Comentarios de la comunidad
│   │   ├── FullReductionController # Actividades de reducción de importe
│   │   ├── PaymentNotifyController # Devoluciones de llamada de pago (WeChat/Alipay)
│   │   ├── PrintController         # Impresión
│   │   ├── PrivacyController       # Cumplimiento de privacidad (exportación de datos/cancelación)
│   │   ├── QueueController         # Llamada de turnos
│   │   ├── VersionController       # Gestión de versiones de APP/detección de actualizaciones
│   │   ├── VideoController         # Vídeos
│   │   ├── WechatController        # Asuntos relacionados con WeChat
│   │   └── WheelController         # Ruleta de la suerte de puntos
│   ├── user/v1/controller/      # Módulo de usuario v1 (14 controladores)
│   │   ├── ProfileController       # Información personal/contraseña/teléfono/cancelación/cierre de sesión
│   │   ├── AddressController       # CRUD de direcciones (gestión de dirección predeterminada)
│   │   ├── FavoriteController      # Favoritos (servicios/técnicos)
│   │   ├── FeedbackController      # Comentarios (texto + imágenes)
│   │   ├── ReferralController      # Promoción/código QR/usuarios recomendados
│   │   ├── CheckInController       # Registro diario
│   │   ├── DeviceController        # Gestión de dispositivos del usuario
│   │   ├── GrowthController        # Nivel de crecimiento (resumen/records/levels)
│   │   ├── HealthProfileController # Perfil de salud
│   │   ├── InvoiceController       # Solicitud/lista/detalle de factura electrónica
│   │   ├── InvoiceTitleController  # Biblioteca de títulos de factura
│   │   ├── NotifySettingController # Preferencias de notificaciones
│   │   ├── PointsTransferController# Transferencia de puntos
│   │   └── TicketController        # Tickets de atención al cliente
│   ├── technician/v1/controller/ # Módulo de técnico v1 (10 controladores)
│   │   ├── ProfileController       # Archivo del técnico/solicitud de incorporación
│   │   ├── ScheduleController      # Consulta/configuración de horarios
│   │   ├── OrderController         # Lista de pedidos del técnico
│   │   ├── WorkController          # Panel de trabajo (today/records/start/complete)
│   │   ├── EarningController       # Resumen de ganancias + historial
│   │   ├── WithdrawController      # Solicitud de retiro (día config('withdraw.gate_day') de cada mes, configurable)
│   │   ├── ServiceRecordController # Registros de servicio
│   │   ├── ExamController          # Evaluación en línea
│   │   ├── AttendanceController    # Registro de entrada/salida
│   │   └── ReviewController        # Respuestas del técnico a evaluaciones
│   ├── order/v1/controller/     # Módulo de pedidos v1 (8 controladores + 9 traits)
│   │   ├── OrderController         # Pedido (bloqueo del técnico)/lista/detalle/cancelación/pago/reembolso/verificación (entrada agregada, 38 líneas, todos los métodos vienen de traits)
│   │   ├── OrderCreateTrait        # Creación de pedidos store/auxiliar de precios (475 líneas)
│   │   ├── OrderQueryTrait         # Consulta de pedidos lista/detalle/logística (205 líneas)
│   │   ├── OrderPayTrait           # Pago pay/pago con saldo/descuento con puntos (415 líneas)
│   │   ├── OrderCancelTrait        # Cancelación de pedidos (272 líneas)
│   │   ├── OrderRefundTrait        # Solicitud de reembolso (379 líneas)
│   │   ├── OrderCompensateTrait    # Escaneo de compensación de reembolsos + devolución de cupones/puntos (345 líneas)
│   │   ├── OrderVerifyTrait        # Verificación comisión/puntos (256 líneas)
│   │   ├── OrderRescheduleTrait    # Reprogramación de reservas (181 líneas)
│   │   ├── OrderNotifyTrait        # Notificaciones suscripción/plantilla/interna/WebSocket (195 líneas)
│   │   └── OrderLockTrait          # Utilidades de bloqueo distribuido (80 líneas)
│   │   ├── AftersaleController     # Posventa
│   │   ├── CartController          # Carrito
│   │   ├── IcsController           # Exportación de calendario ICS
│   │   ├── ReviewController        # Evaluaciones/evaluaciones complementarias
│   │   ├── SignatureController     # Firmas
│   │   ├── TimelineController      # Línea de tiempo de estados de pedido
│   │   └── WaitlistController      # Lista de espera
│   ├── wallet/v1/controller/    # Módulo de billetera v1 (2 controladores)
│   │   ├── WalletController        # Saldo/recarga/historial de transacciones/pago con saldo
│   │   └── WalletTransferController# Transferencias entre usuarios
│   ├── marketing/v1/controller/ # Módulo de marketing v1 (7 controladores)
│   │   ├── CouponController        # Lista de cupones/recepción/descuento en pedidos
│   │   ├── CardController          # Lista de tarjetas de membresía/compra/my/use de tarjeta por uso
│   │   ├── PointController         # Historial de puntos/recuperación por consumo
│   │   ├── GiftCardController      # Tarjetas regalo/canje redeem
│   │   ├── MemberBenefitController # Beneficios de membresía
│   │   ├── MemberCardController    # Definiciones de tarjetas de membresía
│   │   └── PointsExchangeController# Tienda de canje de puntos
│   ├── notification/v1/controller/ # Módulo de notificaciones v1 (1 controlador)
│   │   └── NotificationController  # Lista de notificaciones/marcar leído
│   ├── common/                  # Capacidades comunes (BaseController, etc.)
│   ├── middleware/              # Middleware
│   │   ├── ApiVersion              # Control de versión de API (cabecera API-Version)
│   │   ├── Auth                    # Autenticación JWT + validación del estado del usuario
│   │   ├── Cors                    # Gestión de CORS
│   │   ├── Security                # Detección de seguridad (security-php)
│   │   └── TechnicianAuth          # Validación de identidad de técnico
│   └── model/                   # Modelos de datos (81)
│       ├── User.php → appointment_user
│       ├── TechnicianProfile.php → appointment_technician_profile
│       ├── Service.php → appointment_service (ES: appointment_services)
│       ├── Product.php → appointment_product (ES: appointment_products)
│       ├── Store.php → appointment_store
│       ├── Order.php → appointment_order (incluye reglas de reembolso/máquina de estados)
│       ├── Coupon.php → appointment_coupon
│       ├── MemberCard.php → appointment_member_card
│       ├── Notification.php → appointment_notification
│       └── ... (81 archivos de modelo en total; admin tiene otros 6 específicos, 87 en total)
├── config/                     # Archivos de configuración
├── public/                     # Entrada
├── runtime/                    # Tiempo de ejecución
├── vendor/                     # Dependencias
├── start.php
├── composer.json
└── Dockerfile
```

## apps/ — Frontend del extremo de usuario

### apps/wechat/ — Mini programa WeChat

```
apps/wechat/
├── app.js                      # Entrada de la aplicación
├── app.json                    # Configuración global
├── app.wxss                    # Estilos globales
├── pages/
│   ├── auth/                   # Autenticación
│   │   ├── login               #   Inicio de sesión
│   │   ├── register            #   Registro
│   │   ├── forget-password     #   Contraseña olvidada
│   │   └── agreement           #   Visualización de acuerdos
│   ├── home/                   # Inicio (banners/anuncios/categorías/búsqueda)
│   ├── service/                # Servicios
│   │   ├── list                #   Lista de servicios
│   │   └── detail              #   Detalle del servicio
│   ├── order/                  # Pedidos
│   │   ├── list                #   Lista de pedidos
│   │   ├── detail              #   Detalle del pedido
│   │   └── confirm             #   Confirmar pedido
│   ├── cart/                   # Carrito
│   ├── cards/                  # Tarjetas de membresía (compra/mis tarjetas/uso de tarjeta por uso my/use)
│   ├── gift-cards/             # Tarjetas regalo (canje redeem/ingreso)
│   ├── points/                 # Puntos (historial/canje)
│   ├── marketing/              # Marketing (cupones, etc.)
│   ├── favorite/               # Favoritos
│   ├── feedback/               # Comentarios
│   ├── referral/               # Promoción
│   ├── message/                # Mensajes
│   │   ├── list                #   Lista de mensajes
│   │   └── detail              #   Detalle del mensaje
│   ├── tech-work/              # Panel de trabajo del técnico
│   │   ├── index               #   Inicio del panel (today/records/start/complete)
│   │   ├── schedule            #   Horarios
│   │   ├── order-list          #   Pedidos
│   │   ├── scan-verify         #   Verificación por escaneo
│   │   ├── member-list         #   Lista de miembros
│   │   ├── member-detail       #   Detalle del miembro
│   │   ├── earnings            #   Ganancias
│   │   ├── withdrawal          #   Retiro
│   │   ├── transaction-list    #   Detalle de transacciones
│   │   └── training            #   Formación
│   ├── user/                   # Centro personal
│   │   ├── index               #   Información personal
│   │   ├── settings            #   Configuración
│   │   └── switch-role         #   Cambio de identidad
│   └── wallet/                 # Billetera (saldo/recarga/historial de transacciones)
├── components/                 # Componentes comunes
│   ├── navbar
│   ├── tabbar
│   ├── service-card
│   ├── technician-card
│   ├── coupon-popup
│   └── lbs-selector
├── utils/                      # Utilidades
│   ├── api.js                  #   Solicitudes HTTP
│   ├── auth.js                 #   Gestión de autenticación
│   ├── location.js             #   Localización LBS
│   └── constants.js            #   Constantes
├── styles/                     # Estilos comunes
└── images/                     # Recursos de imágenes
```

### apps/flutter/ — APP Flutter

```
apps/flutter/
├── lib/
│   ├── main.dart               # Entrada
│   ├── app.dart                # Configuración de la App/rutas/tema
│   ├── pages/                  # Páginas (estructura coherente con el mini programa)
│   │   ├── auth/
│   │   ├── home/
│   │   ├── service/
│   │   ├── order/
│   │   ├── cart/
│   │   ├── technician/
│   │   ├── tech_work/
│   │   ├── user/
│   │   ├── marketing/
│   │   ├── message/
│   │   ├── store/
│   │   └── other/
│   ├── widgets/                # Componentes comunes
│   ├── services/               # Servicios API
│   │   ├── api_service         #   HTTP (Dio)
│   │   ├── auth_service        #   Autenticación
│   │   └── location_service    #   Localización
│   ├── models/                 # Modelos de datos
│   ├── state/                  # Gestión de estado
│   └── utils/                  # Utilidades
├── android/                    # Proyecto Android
├── ios/                        # Proyecto iOS
├── pubspec.yaml
└── ...
```

## Cadena de ejecución de middleware

### service/

```
API pública:  Cors → Security → RateLimit → Controller
API de usuario:  Cors → Security → RateLimit → Auth → Controller
API de técnico:  Cors → Security → RateLimit → Auth → TechnicianAuth → Controller
Devoluciones de llamada de pago: Cors → Security → Controller
```

### admin/

```
API pública:  Cors → Security → RateLimit → Controller
API de gestión:  Cors → Security → RateLimit → AdminAuth → AdminPermission → OperationLog → Controller
Comprobación de salud: Cors → Security → RateLimit → Controller
```

## Lista de tablas de base de datos

Todas las tablas usan el prefijo `appointment_`, claves primarias BIGINT no autoincrementales (generadas por Snowflake).

| Dominio | Nombre de tabla | Descripción |
|----|------|------|
| Usuario | appointment_user | Tabla unificada de usuarios |
| Usuario | appointment_user_address | Direcciones de envío |
| Técnico | appointment_technician_profile | Archivo del técnico |
| Técnico | appointment_technician_schedule | Horarios del técnico |
| Técnico | appointment_technician_service | Servicios disponibles del técnico |
| Técnico | appointment_technician_earnings | Historial de ganancias del técnico |
| Técnico | appointment_technician_withdrawal | Registros de retiro del técnico |
| Técnico | appointment_technician_attendance | Asistencia del técnico |
| Técnico | appointment_technician_member_note | Archivo del cliente |
| Servicio | appointment_service_category | Categorías de servicios |
| Servicio | appointment_service | Servicios |
| Servicio | appointment_product | Productos |
| Servicio | appointment_store | Tiendas |
| Pedido | appointment_order | Tabla principal de pedidos (columna de relación seckill_id, Ronda 24) |
| Pedido | appointment_order_item | Detalles del pedido |
| Pedido | appointment_order_payment | Registros de pago |
| Pedido | appointment_order_refund | Registros de reembolso |
| Pedido | appointment_order_review | Evaluaciones de servicios |
| Pedido | appointment_order_verification | Registros de verificación |
| Pedido | appointment_order_reschedule | Registros de reprogramación de reservas (Ronda 17) |
| Marketing | appointment_coupon | Definiciones de cupones |
| Marketing | appointment_user_coupon | Cupones de usuario |
| Marketing | appointment_user_coupon_transfer | Registros de transferencia de cupones (Ronda 17) |
| Marketing | appointment_user_points_transfer | Registros de transferencia de puntos (Ronda 19) |
| Marketing | appointment_technician_tier_log | Registros de cambio de nivel de técnico (Ronda 17) |
| Marketing | appointment_member_card | Definiciones de tarjetas de membresía |
| Marketing | appointment_user_member_card | Tarjetas de membresía de usuario |
| Marketing | appointment_member_card_usage | Registros de uso de tarjetas por uso |
| Marketing | appointment_user_points | Historial de puntos |
| Marketing | appointment_gift_card | Tarjetas regalo |
| Marketing | appointment_user_referral | Promoción de usuarios |
| Marketing | appointment_user_favorite | Favoritos de usuarios |
| Billetera | appointment_user_wallet | Saldo de la billetera del usuario |
| Billetera | appointment_wallet_recharge | Registros de recarga de la billetera |
| Billetera | appointment_wallet_txn | Historial de transacciones de la billetera |
| Billetera | appointment_wallet_transfer | Registros de transferencia entre usuarios (Ronda 19) |
| Usuario | appointment_user_notify_setting | Preferencias de notificaciones (Ronda 19) |
| Contenido | appointment_banner | Banners |
| Contenido | appointment_announcement | Anuncios |
| Contenido | appointment_platform_agreement | Acuerdos de la plataforma |
| Contenido | appointment_faq | Preguntas frecuentes |
| Contenido | appointment_feedback | Comentarios |
| Contenido | appointment_moment | Dinámicas de momentos |
| Contenido | appointment_notification | Notificaciones de mensajes |
| Finanzas | appointment_finance_transaction | Historial de ingresos y gastos |
| Finanzas | appointment_technician_commission_config | Configuración de comisiones |
| Finanzas | appointment_withdrawal_account | Cuentas de retiro |
| Finanzas | appointment_withdrawal_config | Configuración de límites de retiro |
| Sistema | appointment_admin_user | Usuarios de administración (ya creada) |
| Sistema | appointment_admin_role | Roles (ya creada) |
| Sistema | appointment_admin_permission | Permisos (ya creada) |
| Sistema | appointment_admin_user_role | Relación usuario-rol (ya creada) |
| Sistema | appointment_admin_role_permission | Relación rol-permiso (ya creada) |
| Sistema | appointment_system_config | Configuración del sistema (ya creada) |
| Sistema | appointment_operation_log | Registros de operaciones (ya creada) |
| Usuario | appointment_user_growth | Historial de crecimiento (Ronda 20) |
| Usuario | appointment_growth_level | Niveles de crecimiento (Ronda 20) |
| Pedido | appointment_invoice | Factura electrónica (Ronda 20) |
| Usuario | appointment_ticket | Tickets de atención al cliente (Ronda 20) |
| Marketing | appointment_referral_level2_reward | Registros de comisión de nivel 2 (Ronda 20) |
| Usuario | appointment_invoice_title | Biblioteca de títulos de factura (Ronda 21) |
| Usuario | appointment_browse_history | Historial de navegación (Ronda 21) |
| Marketing | appointment_full_reduction_activity | Actividades de reducción de importe (Ronda 22) |
| Técnico | appointment_technician_attendance | Asistencia del técnico (Ronda 22) |
| Sistema | appointment_push_log | Registros de push de APP (Ronda 22) |
| Finanzas | appointment_profit_sharing | Registros de reparto de WeChat (Ronda 22) |
| Pedido | appointment_order_status_log | Línea de tiempo de estados de pedido (Ronda 23) |
| Usuario | appointment_user_health_profile | Perfil de salud del usuario (Ronda 23) |
| Marketing | appointment_lucky_wheel | Definiciones de premios de la ruleta (Ronda 23) |
| Marketing | appointment_wheel_record | Registros de sorteos de la ruleta (Ronda 23) |
| Marketing | appointment_seckill_activity | Actividades de oferta flash (Ronda 24) |
| Sistema | appointment_app_version | Versiones de APP (Ronda 24) |

### Lista complementaria (parte de las 95 tablas de docs/install.sql no listadas arriba; la lista completa y autoritativa es install.sql)

| Dominio | Nombre de tabla | Descripción |
|----|------|------|
| Marketing | appointment_card_transfer | Transferencia de tarjetas por uso |
| Usuario | appointment_check_in | Registro diario |
| Contenido | appointment_community_post | Dinámicas de la comunidad |
| Contenido | appointment_community_comment | Comentarios de la comunidad |
| Técnico | appointment_exam | Evaluaciones |
| Técnico | appointment_exam_question | Preguntas de evaluación |
| Técnico | appointment_exam_attempt | Respuestas de evaluación |
| Sistema | appointment_operation_log_detail | Detalles de registros de operaciones |
| Pedido | appointment_order_aftersale | Posventa de pedidos |
| Marketing | appointment_points_exchange_goods | Productos de canje de puntos |
| Marketing | appointment_promotion | Actividades de compra grupal |
| Marketing | appointment_promotion_participant | Participantes de compra grupal |
| Pedido | appointment_queue_number | Llamada de turnos |
| Servicio | appointment_service_package | Paquetes de servicios |
| Técnico | appointment_service_record | Registros de servicio |
| Contenido | appointment_share | Registros de compartir |
| Pedido | appointment_signature | Firmas |
| Técnico | appointment_technician_tier_config | Configuración de niveles de técnico |
| Técnico | appointment_training_course | Cursos de formación |
| Técnico | appointment_training_progress | Progreso de formación |
| Usuario | appointment_user_device | Dispositivos del usuario |
| Marketing | appointment_user_points_exchange | Registros de canje de puntos |
| Contenido | appointment_video_post | Dinámicas de vídeo |
| Pedido | appointment_waitlist | Lista de espera |

## Reserva de servicios externos

| Servicio | Uso | Punto de integración |
|------|------|--------|
| Plataforma abierta de WeChat | Inicio de sesión con WeChat/UnionID | WechatAuthService |
| WeChat Pay | Pago/reembolso/retiro | WechatPayService |
| Proveedor de SMS | Códigos de verificación/notificaciones | SmsService |
| Servicio de mapas | Localización LBS/navegación/cálculo de distancias | MapService |
