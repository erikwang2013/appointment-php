# Descripción de la arquitectura

## Visión general del sistema

El Sistema de Reservas de Servicios adopta una arquitectura de tres extremos + dos servicios:

```
┌─────────────────────────────────────────────────────┐
│                 Capa de terminales de usuario        │
│  ┌──────────────┐  ┌──────────────┐                 │
│  │ Mini programa│  │ Flutter APP  │                 │
│  │ apps/wechat/ │  │ apps/flutter/│                 │
│  └──────┬───────┘  └──────┬───────┘                 │
│         │   Funcionalmente equivalente  │            │
│         └────────┬─────────┘                         │
│                  │ Cambio de identidad cliente/técnico│
├──────────────────┼──────────────────────────────────┤
│               Capa de API de negocio                  │
│  ┌──────────────┐  ┌──────────────┐                 │
│  │ service/ API │  │ admin/ API   │                 │
│  │ Puerto 8787  │  │ Puerto 8787  │                 │
│  └──────┬───────┘  └──────┬───────┘                 │
│         │                  │                          │
│         └────────┬─────────┘                          │
│                  │ MySQL/Redis/ES compartidos         │
├──────────────────┼──────────────────────────────────┤
│                  Capa de datos                        │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌──────────┐     │
│  │ MySQL  │ │ Redis  │ │  ES    │ │Servicios │     │
│  │        │ │        │ │        │ │de terceros│     │
│  └────────┘ └────────┘ └────────┘ └──────────┘     │
└─────────────────────────────────────────────────────┘
```

## Composición del proyecto

### service/ — Servicio de API de negocio

Proporciona todas las interfaces de negocio al mini programa WeChat y a la APP Flutter. webman v2, puerto 8787.

**División de módulos:**

| Módulo | Ruta | Autenticación | Descripción |
|------|------|------|------|
| API pública | `api/` | Ninguna | Inicio de sesión / registro / código de verificación / devolución de llamada de WeChat |
| Módulo de usuario | `user/` | JWT | Perfil / direcciones / favoritos / comentarios / promoción |
| Módulo de técnico | `technician/` | JWT+técnico | Archivo / horarios / panel de trabajo / verificación / membresía / ganancias / retiros |
| Módulo de servicios | `service/` | Mixto | Categorías / servicios / búsqueda / tiendas |
| Módulo de pedidos | `order/` | JWT | Carrito / pedido / pago / reembolso / verificación / evaluación (OrderController dividido en 10 traits por dominio de negocio, rutas y nombres de métodos sin cambios) |
| Módulo de marketing | `marketing/` | JWT | Cupones / tarjetas de membresía (por uso) / puntos / tarjetas regalo / beneficios de membresía |
| Módulo de billetera | `wallet/` | JWT | Saldo / recarga / historial de transacciones / pago con saldo |
| Módulo de contenido | `content/` | Mixto | Banners / anuncios / notificaciones |
| Módulo LBS | `lbs/` | Público | Ciudades / tiendas cercanas |

### admin/ — Panel de administración

Panel de administración para PC. webman v2 + Flutter Web, puerto 8787.

**Módulos existentes:** autenticación, dashboard, gestión de usuarios, permisos de roles, configuración del sistema, registros de operaciones, carga de archivos, protección de seguridad

**Distribución de modelos:** `admin/app/model/` conserva solo 6 modelos específicos (AdminPermission/AdminRole/AdminUser/OperationLog/OperationLogDetail/SystemConfig); el resto de modelos comparten la versión de service a través de composer psr-4 (`app\model\` → `../service/app/model/`), evitando la duplicación de modelos; la clase base `support\Model` está alineada con service, y el método de relación `UserPointsExchange::user()` se ha incorporado al modelo de la versión de service.

**Módulos extendidos:** gestión de técnicos, gestión de membresías, gestión de tiendas, gestión de servicios/productos, gestión de pedidos, cupones, tarjetas de membresía, revisión de retiros, gestión de evaluaciones, estadísticas de informes, gestión financiera, gestión de contenido, configuración del sistema

### apps/ — Frontend del extremo de usuario

| Directorio | Tecnología | Plataforma |
|------|------|------|
| `apps/wechat/` | Mini programa WeChat nativo | WeChat |
| `apps/flutter/` | Flutter 3.x + GetX + Dio | iOS + Android |

## Componentes principales

### Snowflake ID

Todas las claves primarias las genera `erikwang2013/snowflake-php`, BIGINT no autoincremental, garantizando unicidad global distribuida. `service/support/Model::nextId()` reutiliza una única instancia de Snowflake dentro del proceso; las copias de `generateId()` de los 64 modelos se han eliminado (herencia unificada de la implementación de la clase base).

### Hashids

Los ID en las solicitudes/respuestas de API se codifican con `erikwang2013/hashids`, exponiendo cadenas hash al exterior.

### Autenticación JWT

`erikwang2013/jwt-webman` Bearer Token, validez de 7 días, con soporte de renovación y lista negra.

### Cifrado de datos

- **Capa API**: `erikwang2013/encryption` cifrado/descifrado de datos sensibles
- **Capa DB**: trait `erikwang2013/encryptable` cifrado/descifrado automático de campos

### Protección de seguridad

- `erikwang2013/security-php`: detección de 31 tipos de ataques
- `erikwang2013/poster-php`: verificación aleatoria de operaciones sensibles
- Bloqueo de inicio de sesión: 5 fallos bloquean 15 minutos
- Límite de concurrencia: máx. 3 tokens válidos

### Documentación de API

`hg/apidoc` genera la especificación OpenAPI 3.0, separada para el extremo de administración y el cliente:

| Extremo | Dirección | Descripción |
|------|------|------|
| Administración | `admin/ GET /api/docs` | API del panel de administración (JWT+RBAC) |
| Cliente | `service/ GET /api/docs` | API de negocio (JWT Bearer) |

La documentación es de acceso público y se puede importar en Swagger UI para ver la documentación de interfaces interactiva.

### Elasticsearch

`erikwang2013/webman-scout` sincroniza automáticamente los modelos con ES, soportando búsqueda de texto completo.

## Cadena de ejecución de middleware

### Middleware de service/

```
API pública:  Cors → Security(detección de 31 tipos) → RateLimit → ApiVersion → Controller
API de usuario:  Cors → Security → RateLimit → Auth(JWT) → Controller
API de técnico:  Cors → Security → RateLimit → ApiVersion → Auth → TechnicianAuth → Controller
```

### Middleware de admin/

```
API pública:  Cors → Security → RateLimit → Controller
API de gestión:  Cors → Security → RateLimit → AdminAuth(JWT) → AdminPermission(RBAC) → OperationLog → Controller
Comprobación de salud: Cors → Security → RateLimit → Controller
```

## Flujos de datos

### Flujo de solicitudes

```
Cliente → Cors → Security → RateLimit → Auth(JWT) → [TechnicianAuth] → Controller
    → Model(cifrado/descifrado encryptable) → BaseController(codificación hashids) → respuesta JSON
```

### Flujo de reserva

```
Navegar servicios → elegir tienda/técnico/hora → enviar pedido → bloquear técnico en Redis 3 min
    → pago WeChat → notificar al técnico → inicio del servicio → servicio completado → evaluación → pedido completado
```

## 8 extremos de origen de operación

## Últimas extensiones

| Categoría | Función |
|------|------|
| Tiempo real | Push WebSocket / devolución de llamada de pago / APNs+FCM |
| Mensajes | Push de mensajes de suscripción (sendSubscribeMessage, 3 escenarios de eventos de pedido) |
| Billetera | Valor almacenado de saldo / pago con saldo / reintegro en reembolso |
| Tienda | Impresión Bluetooth / firma electrónica / llamada de turnos |
| Técnico | Evaluación en línea / presentación de vídeo corto / panel de trabajo (today/records/start/complete) |
| Comunidad | Publicar/comentar/dar me gusta/revisión |
| Sistema | Multilingüe (chino/inglés) / cancelación automática de pedidos / datos semilla |

El campo `source` registra el origen de la operación: web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### Integración de servicios de terceros

| Servicio | Clase | Capacidad |
|------|------|------|
| WeChat Pay | WechatPayService | Pedido unificado / consulta / reembolso / retiro a la billetera |
| SMS | SmsService | Doble canal Alibaba Cloud / Tencent Cloud |
| Mapas | MapService | AMap / Tencent geocodificación inversa / distancia / navegación |
| Mensajes de plantilla | WechatTemplateMessageService | Push de pedido/reembolso/recordatorio + mensajes de suscripción (sendSubscribeMessage, 3 escenarios de eventos de pedido) |
