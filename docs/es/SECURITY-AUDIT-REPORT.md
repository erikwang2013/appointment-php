# Informe de revisión de seguridad — Sistema de Reservas (appointment-php)

**Fecha**: 2026-08-04
**Alcance de la revisión**: service (sistema de servicio de reservas), admin (panel de administración abierto)
**Versión de PHP**: 8.3.7
**Framework**: webman v2

---

## I. Resultados de las pruebas

| Elemento de prueba | Service | Admin |
|--------|---------|-------|
| Comprobación de sintaxis PHP (completa) | Correcta | Correcta |
| Pruebas unitarias PHPUnit | 59 tests / 165 assertions PASS | 59 tests / 165 assertions PASS |
| Análisis estático PHPStan | No instalado (tiempo de espera en la descarga de dependencias de desarrollo) | No instalado (tiempo de espera en la descarga de dependencias de desarrollo) |

---

## II. Panorama de las capas de protección de seguridad

```
Petición → Nginx (cabeceras de seguridad + protección de archivos sensibles) → Cors (CORS + cabeceras de seguridad) → SecurityMiddleware (detección de 31 ataques) → RateLimit (ventana deslizante Redis) → Auth (JWT) → Controller
                                                                                                   ↓
                                                                                    Lista negra de IP (5 ataques/60 s → bloqueo 15 min)
                                                                                    Bloqueo de cuenta (5 fallos/15 min → bloqueo 15 min)
```

---

## III. Problemas corregidos

### 3.1 CORS de Service sin cabeceras de seguridad de respuesta → corregido
**Archivo**: `service/app/middleware/Cors.php`
- Se añadieron 7 cabeceras de seguridad: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, CSP, X-Permitted-Cross-Domain-Policies
- Ahora es coherente con la configuración de cabeceras de seguridad de admin

### 3.2 A Service le faltaba el bloqueo por fallos de inicio de sesión → corregido
**Archivo**: `service/app/api/v1/controller/AuthController.php`
- Los métodos `login()` y `loginByCode()` incorporan un contador de fallos en Redis
- 5 fallos/15 minutos → bloqueo → HTTP 429
- Degradación elegante ante fallos de Redis

### 3.3 Origin de CORS codificado como `*` → corregido
**Archivos**: `service/app/middleware/Cors.php`, `admin/app/middleware/Cors.php`
- Ahora se configura mediante la variable de entorno `CORS_ALLOW_ORIGIN`
- Vacío = `*` por defecto (compatibilidad hacia atrás)

### 3.4 A Service le faltaba la dependencia security-php → corregido
**Operación**:
- Se añadió `allow-plugins.erikwang2013/security-php` a composer.json
- Se ejecutó `composer install --no-dev` para instalar la dependencia
- El archivo de configuración se publicó en `config/plugin/erikwang2013/security-php/app.php`
- El detector de Origin CSRF (`csrf_origin`) está habilitado (modo block)

### 3.5 Nginx de Service sin Permissions-Policy → corregido
**Archivo**: `service/docs/nginx.conf`
- Se añadió `add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;`

### 3.6 Completado de la configuración del ecosistema → corregido
- `service/.env.example` y `admin/.env.example` incorporan `CORS_ALLOW_ORIGIN`
- `service/.env.docker` y `admin/.env.docker` incorporan `CORS_ALLOW_ORIGIN`

---

## IV. Lista completa de la protección de seguridad actual

### 4.1 Capa WAF — 31 detectores de ataques

| Modo | Detectores | Cantidad |
|------|--------|------|
| **block** (bloqueo 403) | XSS, inyección SQL, inyección de comandos, path traversal, subida de archivos, SSRF, XXE, deserialización, inyección LDAP, inyección en cabeceras de email, Open Redirect, ataques JWT, ataques de Host header, Request Smuggling, inyección GraphQL, inyección XPATH, JNDI/Log4Shell, inyección SSI, inyección CSV, filtración de datos, Prototype Pollution, secuestro de WebSocket, bypass de CORS, DNS Rebinding, validación de métodos HTTP, tamaño del cuerpo de la petición (10 MB), lista blanca de Content-Type, Origin CSRF | 28 |
| **log** (solo registro) | Inyección en cabeceras de respuesta, SSTI, inyección NoSQL | 3 |

### 4.2 Autenticación y autorización

| Mecanismo | Service | Admin |
|------|---------|-------|
| Autenticación JWT | Middleware Auth | Middleware AdminAuth |
| Lista negra de JWT | Al cerrar sesión | Al cerrar sesión + al superar el límite de sesiones |
| Permisos RBAC | — | Formato method.path, caché Redis 60 s |
| Bloqueo de cuenta | 5 veces/15 min (Redis) | 5 veces/15 min (Redis) |
| Límite de sesiones concurrentes | — | Máximo 3 Tokens |
| Hash de contraseñas | bcrypt | bcrypt |

### 4.3 Limitación de tráfico

| Ruta | Service | Admin |
|------|---------|-------|
| Por defecto | 60 veces/min/IP | 60 veces/min/IP |
| Inicio de sesión | 10 veces/min | — |
| Registro | 5 veces/min | — |
| SMS / olvido de contraseña | 5 veces/min | — |

### 4.4 Seguridad de datos

| Medida | Service | Admin |
|------|---------|-------|
| Cifrado de campos de base de datos | AES-256-CBC (6 modelos) | AES-256-CBC |
| Cifrado de transmisión API | AES-256-CBC | AES-256-CBC |
| Ofuscación de ID (Hashids) | Todos los ID externos | Todos los ID externos |
| ID Snowflake | BIGINT no autoincremental | BIGINT no autoincremental |
| Desidentificación de campos sensibles | Teléfono móvil desidentificado | Datos de exportación desidentificados |

---

## V. Recomendaciones pendientes

### 5.1 Recomendación: cambiar el almacenamiento de security-php a Redis (producción)
**Actual**: ambos servicios usan almacenamiento tipo `file` (archivo JSON local)
**Riesgo**: en despliegues de múltiples instancias, la lista negra de IP no se comparte; un atacante puede cambiar de instancia para eludirla
**Recomendación**: en producción, cambiar `storage.type` a `redis`

### 5.2 Recomendación: atributos de seguridad de la cookie de sesión
**Actual**: `secure: false`, `same_site: ''`
**Riesgo**: la cookie puede transmitirse por HTTP; la protección CSRF se debilita
**Recomendación**: en producción, establecer `secure: true`, `same_site: 'Lax'`

### 5.3 Recomendación: instalar la dependencia de desarrollo PHPStan
**Actual**: `composer install --dev` falló por tiempo de espera de red
**Operación**: `composer install --dev` o `composer require --dev phpstan/phpstan`

### 5.4 Recordatorio: cambiar todas las claves antes del despliegue en producción
Las claves de marcador de posición de `.env.docker` deben sustituirse por valores generados aleatoriamente antes del despliegue en producción:
- `JWT_SECRET_KEY`
- `HASHIDS_SALT`, `HASHIDS_ALT_SALT`
- `ENCRYPTION_KEY`, `ENCRYPTABLE_KEY`
- `DB_PASSWORD`

---

## VI. Documentación producida

| Documento | Ruta |
|------|------|
| Arquitectura de seguridad de Service | `service/docs/SECURITY.md` |
| Arquitectura de seguridad de Admin | `admin/docs/SECURITY.md` |
| Este informe de revisión | `docs/SECURITY-AUDIT-REPORT.md` |

---

## VII. Conclusión de la revisión

**Valoración global de la protección de seguridad: buena**

- Las capas de defensa en profundidad están completas (Nginx → WAF → Rate Limit → Auth → RBAC)
- 31 detectores de ataques con cobertura global; 28 en modo de bloqueo
- Protección de autenticación multicapa: JWT + lista negra + bloqueo de cuenta + lista negra de IP
- Cifrado AES-256-CBC en la capa de datos + ofuscación Hashids
- Se corrigieron tres problemas clave en service: falta de cabeceras de seguridad de respuesta, falta de bloqueo de inicio de sesión y falta del paquete WAF
- Las recomendaciones son optimizaciones de configuración para producción, no vulnerabilidades de seguridad

---

## VIII. Ronda de correcciones del 2026-08-26 (endurecimiento de seguridad)

| Elemento | Corrección |
|----|---------|
| Prevención de manipulación en pedidos | En `OrderController::store()`, el precio de los elementos del pedido siempre se toma de los registros de la base de datos (service → erik_service, product → erik_product); el precio del cliente no participa en el cálculo; target_type desconocido → 422; target_id debe ser hashid (si el id en crudo se decodifica como 0 → 422 «El producto no existe o está fuera de catálogo»); los precios de compra grupal/oferta flash también se toman de la base de datos |
| Descuento de inventario unificado en ofertas flash | El inventario se descuenta de forma unificada mediante bloqueo de filas dentro de la transacción de `/api/order store()`; `SeckillController::buy` ya no descuenta inventario por adelantado (se conservan el bloqueo de actividad Redis + la idempotencia de client_token); llamar directamente a `/api/order` con seckill_id también descuenta inventario |
| Retiros de técnicos | Al solicitar, el saldo se descuenta y se reserva como en tránsito (pending/approved); antes de transferir tras la aprobación, se reverifica que settled − withdrawn − en tránsito ≥ importe del retiro; las aprobaciones concurrentes no producen dobles pagos |
| Devoluciones de llamada de pago | En la devolución de WeChat, total_fee se compara estrictamente con el importe a pagar del pedido; si no coincide, se rechaza; los registros de la devolución de Alipay se desinfectan (sin buyer_id/seller_id, etc.) |
| Protección de /install | Tras una instalación correcta se escribe `.install.lock`; el endpoint de instalación hace doble validación (bloqueo de archivo + isInstalled); `.gitignore` ignora `.install.lock` |
| Consolidación de dependencias | webman-scout unificado en 2.0.5 (service/admin); se añadió opensearch-project/opensearch-php ^2.6; dompdf/security-php/webman-database fijados a versiones exactas (sin comodín `"*"`) |
| Ingeniería | Se eliminó `service/app/common/StorageService.php` (código muerto); en `admin/app/common/` se añadieron TechnicianWithdrawalService/WechatPayService (admin se despliega de forma independiente sin depender del código de service); se reparó phpstan.neon de ambas aplicaciones para que sea ejecutable (php -d memory_limit=2G) |
