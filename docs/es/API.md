# Documentación de API

## Descripción general

- **API de negocio** (service/): `http://localhost:8787` — proporciona las interfaces de negocio al mini programa/APP
- **API del panel de administración** (admin/): `http://localhost:8787` — proporciona las interfaces al Flutter Web del panel
- **Método de autenticación**: Bearer Token (JWT), cabecera de solicitud `Authorization: Bearer <token>`
- **Control de versiones**: la versión de API se controla con la cabecera `API-Version: v1`, no aparece en la URL. Predeterminado v1
- **Codificación de ID**: todos los campos de ID en solicitudes/respuestas usan codificación hashids, ocultando los ID reales de la base de datos al exterior
- **Documentación OpenAPI**: generada con `hg/apidoc`, separada para el extremo de administración y el cliente

| Extremo | Dirección de documentación OpenAPI | Descripción |
|------|------|------|
| Administración | `GET http://localhost:8787/api/docs` | Especificación completa de la API del panel (OpenAPI 3.0 JSON) |
| Cliente | `GET http://localhost:8787/api/docs` | Especificación completa de la API de negocio (OpenAPI 3.0 JSON) |

Las direcciones anteriores se pueden importar con herramientas como Swagger UI para ver la documentación interactiva.

- **Formato de respuesta general**:

```json
{
  "code": 0,
  "message": "操作成功",
  "data": {}
}
```

Respuesta paginada:
```json
{
  "code": 0,
  "message": "success",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

---

## I. API de negocio (service/ :8787)

### 1. Interfaces públicas (sin autenticación)

#### 1.1 Código de verificación

**`POST /api/captcha/send`** — envía el código de verificación SMS

Solicitud:
```json
{
  "phone": "13800138000"
}
```
Respuesta: `{"code":0,"message":"验证码已发送","data":null}`

Límite: solo se puede enviar 1 vez cada 60 segundos, el código de verificación es válido durante 5 minutos.

---

#### 1.2 Autenticación

**`POST /api/auth/register`** — registro con teléfono

Solicitud:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "abc123",
  "confirm_password": "abc123",
  "referral_code": "A1B2C3D4"
}
```
Respuesta:
```json
{
  "code": 0,
  "message": "注册成功",
  "data": {
    "token": "eyJhbGciOi...",
    "user": {
      "id": "aB3xK9mQ",
      "phone": "138****8000",
      "nickname": "用户138****8000",
      "user_type": "customer",
      "active_role": "customer",
      "referral_code": "E5F6G7H8"
    }
  }
}
```

---

**`POST /api/auth/login`** — inicio de sesión con contraseña

Solicitud:
```json
{
  "phone": "13800138000",
  "password": "abc123"
}
```
Respuesta: igual que la de registro, incluye token e información del usuario.

---

**`POST /api/auth/login-by-code`** — inicio de sesión con código de verificación

Solicitud:
```json
{
  "phone": "13800138000",
  "code": "123456"
}
```
Respuesta: igual que el inicio de sesión. Los usuarios no registrados crean la cuenta automáticamente.

---

**`POST /api/auth/forget-password`** — contraseña olvidada

Solicitud:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "newpass123",
  "confirm_password": "newpass123"
}
```

---

**`POST /api/auth/refresh`** — renovar Token

Cabecera de solicitud: `Authorization: Bearer <token antiguo>`
Respuesta: `{"code":0,"data":{"token":"eyJhbGciOi..."}}`

---

#### 1.3 WeChat

**`POST /api/wechat/mini-login`** — inicio de sesión del mini programa

Solicitud: `{"code":"código de inicio de sesión de WeChat"}`
Nota: el primer inicio de sesión requiere llamar después a `/api/wechat/phone` para vincular el teléfono.

---

**`POST /api/wechat/phone`** — vincular teléfono

Solicitud: `{"code":"código del componente de teléfono de WeChat"}`

---

**`POST /api/wechat/oa-login`** — inicio de sesión de la cuenta oficial

Solicitud: `{"code":"código de autorización de la cuenta oficial"}`

---

#### 1.4 Servicios públicos

**`GET /api/common/config`** — configuración pública

Respuesta: incluye los textos de los acuerdos (acuerdo de usuario / acuerdo de privacidad / acuerdo de servicio), la información de «Sobre nosotros» y el número de versión.

---

**`GET /api/common/area`** — lista de ciudades y regiones

---

#### 1.5 Consulta de servicios

**`GET /api/service/categories`** — lista de categorías

Parámetros: `?parent_id=0`

---

**`GET /api/service/items`** — lista de servicios

Parámetros: `?category_id=&page=1&per_page=10&sort=sales`

---

**`GET /api/service/detail/{id}`** — detalle del servicio

La respuesta incluye: imagen/nombre/precio/especificaciones/duración/ventas/lista de evaluaciones.

---

**`GET /api/service/products`** — lista de productos

**`GET /api/service/stores`** — lista de tiendas

Parámetros: `?lat=&lng=&city=`

---

#### 1.6 Consulta de técnicos

**`GET /api/technician/list`** — lista de técnicos

Parámetros: `?lat=&lng=&service_id=&page=1`
Ordenada por distancia de cerca a lejos, devuelve: avatar/nombre/puntuación/número de pedidos/número de favoritos/distancia/hora más temprana disponible/si puede prestar servicio.

---

**`GET /api/technician/detail/{id}`** — detalle del técnico

La respuesta incluye: imagen/nombre/introducción/puntuación/distancia/lista de servicios disponibles/evaluaciones.

---

**`GET /api/technician/schedule/{id}`** — horarios del técnico

Parámetros: `?date=2026-05-26`
Devuelve los intervalos de reserva disponibles y su estado para esa fecha.

---

#### 1.7 Contenido

**`GET /api/content/banners`** — banners

Parámetros: `?position=home`

**`GET /api/content/articles`** — lista de anuncios/artículos

Parámetros: `?type=announcement&page=1`

**`GET /api/content/article/{id}`** — detalle del artículo

---

#### 1.8 LBS

**`GET /api/lbs/nearby-stores`** — tiendas cercanas

Parámetros: `?lat=&lng=&radius=5000`

**`GET /api/lbs/geocode`** — geocodificación inversa

Parámetros: `?lat=&lng=`

---

### 2. Interfaces de usuario (requieren autenticación JWT)

Todas las interfaces llevan en la cabecera `Authorization: Bearer <token>`

#### 2.1 Perfil personal

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/user/profile` | Obtener información personal |
| PUT | `/api/user/profile` | Actualizar apodo/avatar/género |
| POST | `/api/user/change-password` | Cambiar contraseña (old_password/new_password/confirm_password) |
| POST | `/api/user/change-phone` | Cambiar teléfono (old_code/new_phone/new_code) |
| POST | `/api/user/cancel-account` | Cancelar cuenta (requiere verificar contraseña) |
| POST | `/api/user/logout` | Cerrar sesión (el token entra en la lista negra) |
| POST | `/api/user/switch-role` | Cambiar identidad (role: customer/technician) |

Cambiar a technician requiere tener un archivo de técnico con estado approved.

#### 2.2 Gestión de direcciones

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/user/addresses` | Lista de direcciones |
| POST | `/api/user/addresses` | Agregar dirección (contact_name/contact_phone/province/city/district/detail/lat/lng/is_default) |
| GET | `/api/user/addresses/{id}` | Detalle de la dirección |
| PUT | `/api/user/addresses/{id}` | Actualizar dirección |
| DELETE | `/api/user/addresses/{id}` | Eliminar dirección |

Al establecer una como predeterminada, las demás predeterminadas se cancelan automáticamente.

#### 2.3 Favoritos

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/user/favorites` | Lista de favoritos (?type=service/technician) |
| POST | `/api/user/favorites` | Agregar favorito (target_type/target_id) |
| DELETE | `/api/user/favorites/{id}` | Quitar favorito |

#### 2.4 Comentarios

`POST /api/user/feedback` — enviar comentario (content + array images)

#### 2.5 Promoción y recomendación

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/user/referral` | Información de promoción (código de recomendación/número de recomendados/número de primer pedido/puntos obtenidos) |
| GET | `/api/user/referral/qrcode` | Código QR de promoción (código de recomendación + enlace de invitación) |
| GET | `/api/user/referral/referred-users` | Lista de usuarios recomendados |
| GET | `/api/user/referral/earnings` | Detalle de comisiones de distribución (paginado: apodo/avatar del recomendado/número de pedido/importe/hora de emisión) |

**Comisión de distribución**: se emite tras el primer pedido completed del recomendado, importe = paid_amount × reward_rate (erik_system_config referral.reward_rate, por defecto 0.05, los valores inválidos retroceden a la constante). Triple idempotencia con bloqueo de fila + comprobación de rewarded_at vacío + re-verificación del primer pedido; ingreso en WalletTxn type=referral_reward.

#### 2.6 Transferencia de puntos (Ronda 19)

| Método | Ruta | Descripción |
|------|------|------|
| POST | `/api/user/points/transfer` | Transferencia de puntos (to_user_id hashid/points) |
| GET | `/api/user/points/transfers` | Registros de transferencia (?direction=sent/received&page=1) |

**Transferencia de puntos**: decodificación hashid del receptor + existencia 404, transferirse a uno mismo 422, puntos 1-10000 422, saldo insuficiente por agregación SUM 422, límite diario acumulado de 10000 422. Protección de concurrencia: bloqueo Redis NX points_transfer:{user} 30 s → lockForUpdate del último historial de ambas partes dentro de la transacción (user_id ascendente contra deadlocks por transferencias mutuas) → re-verificación dentro del bloqueo de saldo/límite/receptor. Norma del historial: emisor type=consume/source=points_transfer negativo (balance = instantánea anterior − esta), receptor type=earn/source=points_transfer positivo con expires_at (PointsExpiryTimer puede caducarlo normalmente); tras commit, notificación interna al receptor type='points_received' (el fallo solo warn).

#### 2.7 Preferencias de notificaciones (Ronda 19)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/user/notify-settings` | Consultar los interruptores de notificación (los 5 tipos completos) |
| PUT | `/api/user/notify-settings` | Actualizar interruptores por lotes (types: {service_reminder: 0/1, ...}) |

**Interruptores de notificación**: tabla erik_user_notify_setting (clave compuesta única user_id+type, fila ausente = activado por defecto). 5 tipos: service_reminder recordatorio de servicio / card_expiry recordatorio de caducidad (tarjetas + cupones bajo el mismo paraguas) / points_expiry caducidad de puntos / marketing marketing (reservado) / system sistema (no se puede desactivar, PUT lo fuerza a 1). Control: notifySettingEnabled enganchado a los 3 procesos de temporizador ServiceReminderTimer/ExpiryReminderTimer/PointsExpiryTimer + mapeo de escenarios de eventos de suscripción (PAY/REFUND/VERIFIED/RESCHEDULE→system siempre se envía, REMINDER→service_reminder, EXPIRY→card_expiry); cuando el tipo está desactivado, se omiten tanto las notificaciones internas como los mensajes de suscripción.

---

### 3. Interfaces de técnico (requieren JWT + identidad de técnico)

#### 3.1 Archivo del técnico

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/technician/profile` | Obtener el archivo del técnico |
| PUT | `/api/technician/profile` | Actualizar el archivo (avatar/intro/real_name/gender/id_card/id_card_front/id_card_back) |

El primer relleno completo se considera solicitud de incorporación, status=pending a la espera de revisión.

#### 3.2 Horarios

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/technician/schedule` | Consulta de horarios (?start_date=&end_date=) |
| PUT | `/api/technician/schedule` | Configurar horarios (date/time_slots/status), intervalos superpuestos 422 «conflicto con el horario existente» |
| POST | `/api/technician/schedule/batch` | Horarios masivos (Ronda 23): tramo de fechas ≤7 días + filtro weekdays, los días con horarios existentes se saltan, respuesta created/skipped |

#### 3.3 Pedidos del técnico

`GET /api/technician/orders` — lista de pedidos (?status=&page=1)

#### 3.4 Ganancias

`GET /api/technician/earnings` — resumen de ganancias (today_income/pending_settlement/balance + lista de historial)

#### 3.5 Retiro

`POST /api/technician/withdraw` — solicitar retiro (amount)
Reglas: retiro disponible el día 20 de cada mes, T+1 llega a la cuenta, importe mínimo/límite de múltiplo de cien configurados en el panel.

**Reserva en tránsito (2026-08-26)**: al solicitar, el saldo se descuenta como reserva en tránsito (pending/approved); antes de la transferencia de aprobación se re-verifica que settled − withdrawn − en tránsito ≥ importe del retiro; la aprobación concurrente no provoca doble pago.

#### 3.6 Respuesta a evaluaciones (Ronda 18)

`POST /api/technician/review/reply/{order_id}` — respuesta del técnico a la evaluación (reply). Evaluación inexistente/no propia unificada en 404 (no filtra la existencia); respuesta existente 422 (rechazo idempotente sin sobrescribir); respuesta vacía 422. Al responder correctamente, notificación interna al usuario (type='review_reply').

#### 3.6 Panel de trabajo

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/technician/work/today` | Lista de tareas de hoy |
| GET | `/api/technician/work/records` | Registros completados paginados |
| POST | `/api/technician/work/{id}/start` | Iniciar servicio |
| POST | `/api/technician/work/{id}/complete` | Completar servicio |

**Tareas de hoy**: status ∈ [confirmed, serving], service_time de hoy o vacío, devuelve service_name/price/nickname/avatar.

**Registros completados**: status ∈ [serving, completed], ordenados por service_end_at descendente, respuesta paginada con meta.

**Iniciar/completar servicio**: bloqueo de fila + validación de máquina de estados, operaciones idempotentes. Iniciar servicio escribe service_start_at; completar servicio escribe service_end_at y envía notificación interna. Códigos de error: no propio 403, estado incorrecto 422, hashid inválido 422.

---

### 4. Interfaces de pedidos (requieren autenticación JWT)

| Método | Ruta | Descripción |
|------|------|------|
| POST | `/api/order` | Crear pedido (order_type/items/store_id/technician_id/service_time/coupon_id/user_coupon_id/promotion_id/remark) |
| GET | `/api/order/list` | Lista de pedidos (?status=&page=1) |
| GET | `/api/order/detail/{id}` | Detalle del pedido |
| POST | `/api/order/cancel/{id}` | Cancelar pedido (reason) |
| POST | `/api/order/pay/{id}` | Iniciar pago (pay_channel: wechat/balance, use_points: descuento con puntos opcional) |
| POST | `/api/order/refund/{id}` | Solicitar reembolso |
| POST | `/api/order/verify/{id}` | Verificación (code: valor del código QR) |
| POST | `/api/order/reschedule/{id}` | Reprogramación de reserva (new_service_time obligatorio/reason opcional) |
| GET | `/api/order/logistics/{id}` | Seguimiento logístico (Ronda 19, pedidos product) |
| POST | `/api/order/review/{order_id}` | Enviar evaluación (rating 1-5/content/images) (registro completado en la Ronda 19) |
| POST | `/api/order/review/{order_id}/append` | Evaluación complementaria (content/images separados por comas) (Ronda 19) |

**Estados del pedido**: pending(pendiente de pago) → paid(pagado) → confirmed(confirmado) → serving(en servicio) → completed(completado)

**Al crear el pedido**: Redis SETNX bloquea al técnico 3 minutos, se libera al salir de la página o al expirar.

**Anti-manipulación de precios (2026-08-26)**: los importes de los ítems del pedido siempre se basan en los registros de la base de datos (target_type=service consulta erik_service, product consulta erik_product), los precios enviados por el cliente no participan en el cálculo; target_type desconocido 422; target_id debe enviarse con valor codificado en hashid (enviar el id raw lo decodifica a 0 → 422 «el producto no existe o se ha retirado»); los precios de compra grupal/flash también según DB.

**Reglas de reembolso**: dentro de los 15 min del pedido o >6 h antes del inicio reembolso 100% / ≤6 h 90% / iniciado 80% / tras confirmar el inicio no se reembolsa.

**Descuento con cupones**: al crear el pedido se puede pasar opcionalmente user_coupon_id (hashid). Códigos de error: cupón ajeno 404, umbral insuficiente/caducado/retirado/usado 422, hashid inválido 422. El descuento es en dos fases: al hacer el pedido, PriceCalculator.applyCoupon valida de solo lectura y calcula el importe del descuento escrito en discount_amount; tras el pago correcto, consume pone el cupón en used; al reembolsar, restoreCouponAndCard lo devuelve idempotentemente.

**Pago con saldo y reembolso**: pasar `pay_channel: "balance"` en el cuerpo de la solicitud de pago usa el saldo de la billetera; tanto el reembolso de WeChat como el de saldo recargan el importe en el saldo de la billetera.

**Descuento con puntos**: el cuerpo de la solicitud de pago puede pasar opcionalmente `use_points` (entero). Validación del saldo de puntos con agregación SUM (la columna balance de erik_user_points es una instantánea del incremento individual, no se puede usar directamente como saldo), importe del descuento = floor(use_points / config('app.points_rate', 100)) yuanes, importe a pagar real = original a pagar − descuento (mínimo 0.01; si supera el a pagar, se aplica el descuento máximo sobre el a pagar sin desperdiciar puntos). Al tener éxito escribe historial de consumo type=consume/source=points_offset (idempotente, los reintentos no descuentan de nuevo). Saldo insuficiente 422.

**Recuperación de puntos**: al cancelar/reembolsar se devuelven los puntos consumidos con points_offset (type=earn/source=points_refund): cancelación completa, reembolso proporcional, 5 puntos de enganche idempotentes (refundOffsetPoints).

**Pedido de compra grupal (Ronda 16)**: al crear el pedido se puede pasar opcionalmente `promotion_id` (hashid). Validación: solo tipo group_buy, actividad dentro del período de validez, el llamante es participante, no está lleno (grupo ya formado bloqueado 422), el servicio del pedido coincide con la actividad; precio de grupo = precio original × discount_percent/100, prohibidos cupones/tarjetas por uso/puntos superpuestos (enviar cualquiera da 422). El pedido se guarda con promotion_id/participant_id; el pago reutiliza por completo `POST /api/order/pay/{id}`, y al pagar se determina de forma diferida si la actividad está cerrada (vencida sin formar grupo) → el pedido se cancela automáticamente y se libera el bloqueo del técnico.

**Pedido flash (Ronda 18, retirado)**: ~~al crear el pedido pasar `promotion_id` (tipo flash_sale)~~ — desde 2026-08 el antiguo canal de promoción FLASH_SALE se ha eliminado, la rama promocional de store() solo conserva GROUP_BUY (promotion no grupal 422); las ofertas flash usan el canal `/api/seckill` de la Ronda 24 (seckill_id inyectado en store con reducción de stock por bloqueo de fila dentro de la transacción), PromotionController::index filtra flash_sale, show/join le devuelven 400, la constante `Promotion::TYPE_FLASH_SALE` se conserva para compatibilidad con datos históricos.

**Reprogramación de reserva (Ronda 17)**: `POST /api/order/reschedule/{id}` pasando new_service_time (obligatorio) + reason (opcional), cambia de hora con el mismo técnico. Reglas: solo pedidos propios (404 si no es propio), solo tipo appointment con estado pending/paid/confirmed (resto 422), ≥ 6 horas antes del inicio del servicio original (alineado con la ventana de reembolso completo) para poder reprogramar. Protección de concurrencia: B1 order_lock (misma familia de exclusión mutua que pay/cancel/refund) → bloqueo del técnico en el nuevo horario con Redis SETNX EX 180 (contra sobreventa en reprogramación concurrente) → re-lectura con bloqueo de fila dentro de la transacción + validación DB de conflicto de horarios B2 (excluyendo este pedido) → actualización de service_time + registro en erik_order_reschedule → liberación del bloqueo del horario original, el bloqueo del nuevo horario queda en manos de este pedido → mensaje de suscripción SCENE_RESCHEDULE (degradación a notificación interna si no está configurado). En la ruta de fallo, la transacción revierte y también libera el bloqueo del nuevo horario.

**Seguimiento logístico (Ronda 19)**: `GET /api/order/logistics/{id}` — solo consultable por pedidos product propios (no propio/no producto/no enviado unificados en 404). Lee el JSON de order.remark (shipping_company/tracking_no/shipped_at, escrito por admin MallOrderController::ship() al enviar), parseShippingInfo/parseReceiver con doble análisis de respaldo al formato antiguo; teléfono del receptor desidentificado 138\*\*\*\*5678.

**Evaluaciones (Ronda 19)**: `POST /api/order/review/{order_id}` envía la evaluación (rating obligatorio 1-5, content/images opcionales): no propio 404, no completed 422, evaluación repetida 400. `POST /api/order/review/{order_id}/append` evaluación complementaria (content obligatorio, images separados por comas): evaluación inexistente/no propia unificada en 404, no completed 422, complemento repetido 422, contenido vacío 422; al tener éxito escribe append_content/append_images(JSON)/append_at y notifica internamente al técnico type='review_append', la respuesta expone los campos append.

### 4.1 Interfaces de posventa (requieren autenticación JWT)

| Método | Ruta | Descripción |
|------|------|------|
| POST | `/api/aftersales` | Solicitar posventa (order_id hashid/type: refund\|exchange/reason), validación de pedido propio 404, solo se puede solicitar con estado paid+completed 422, deduplicación de posventa en curso del mismo pedido 422 |
| GET | `/api/aftersales` | Lista de mi posventa (?status=&page=1&limit=) |
| GET | `/api/aftersales/{id}` | Detalle de posventa (validación de pertenencia 404) |

**Estados de posventa**: pending(pendiente de revisión) → approved(aprobado) / rejected(rechazado). approved solo cambia el estado, la acción de reembolso reutiliza `POST /api/order/refund/{id}`.

---

### 4.2 Interfaces de compra grupal/promoción (requieren autenticación JWT; FLASH_SALE retirado)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/promotions` | Lista de actividades (?type=group_buy; flash_sale filtrado y no devuelto) |
| GET | `/api/promotions/{id}` | Detalle de la actividad (incluye número de participantes/si se formó el grupo; tipo flash_sale 400) |
| GET | `/api/promotions/{id}/participants` | Lista de participantes |
| POST | `/api/promotions/join/{id}` | Participar en la actividad (completado en la Ronda 15: la respuesta incluye discount_percent/original_price/group_price; tipo flash_sale 400) |

**Reglas de participación**: group_buy bloqueado al llenarse (≥min_people), nueva participación 422 tras formarse el grupo; cierre diferido si no se llena al vencer (show/join ponen status 0). Después de join, hacer el pedido a precio de grupo se describe en «Pedido de compra grupal (Ronda 16)». Las ofertas flash ya no pasan por este canal, ver «24. Interfaces de oferta flash».

---

### 5. Interfaces de marketing (requieren autenticación JWT)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/marketing/coupons` | Lista de cupones (?status=available/used/expired) |
| POST | `/api/marketing/coupons/receive` | Recibir cupón (coupon_id) |
| GET | `/api/marketing/cards` | Lista de tarjetas de membresía |
| POST | `/api/marketing/cards/buy` | Comprar tarjeta de membresía (card_id) |
| GET | `/api/marketing/cards/my` | Lista de mis tarjetas por uso |
| POST | `/api/marketing/cards/use` | Verificar tarjeta por uso (user_card_id/service_id/remark?) |
| GET | `/api/marketing/gift-cards` | Lista de tarjetas regalo |
| GET | `/api/marketing/gift-cards/my` | Mis tarjetas regalo (registros redeem) |
| POST | `/api/marketing/gift-cards/redeem` | Canjear tarjeta regalo (el tipo cash recarga el saldo de la billetera tras el canje) |
| GET | `/api/marketing/points` | Historial de puntos (?type=earn/use/expire&source=order/referral/gift_card/check_in/admin) |
| GET | `/api/marketing/points-exchange` | Lista de productos de canje de puntos (en venta + stock restante en tiempo real + cantidad canjeada) |
| POST | `/api/marketing/points-exchange/{id}` | Canjear (type=coupon emite cupón / wallet ingresa saldo / gift_card devuelve tarjeta con código) |
| POST | `/api/marketing/coupons/transfer` | Generar código de transferencia (user_coupon_id: código único de 8 caracteres/válido 7 días) |
| POST | `/api/marketing/coupons/claim` | Recibir cupón transferido (code) |
| GET | `/api/marketing/coupons/transfers` | Registros de transferencia (emitidos pending/claimed/expired + recibidos claimed) |

**Tarjeta por uso**: cards/my devuelve card_id/name/type/services/total_times/used_times/remaining_times/start_at/end_at/status (calculado en tiempo real). La verificación correcta devuelve {order_id, usage_id, remaining_times}; códigos de error: hashid inválido 422, usos insuficientes 422, caducada 400, no propio 404, anti-duplicado de Redis 400.

**Tarjeta regalo**: gift-cards/my devuelve los registros redeem (type/amount/gift_name/status/used_at).

**Reglas de puntos**: detalle paginado, filtro de tipo (earn/use/expire), filtro de fuente (order/referral/gift_card/check_in/admin). Puntos por registro diario (CheckIn, type=earn); puntos por consumo floor(paid_amount×1), emitidos al verificar e idempotentes; el reembolso recupera puntos proporcionalmente.

**Caducidad de puntos (Ronda 17)**: columna erik_user_points.expires_at (configuración points.expiry_days, por defecto 365 días, ≤0 nunca caduca), todos los earn se guardan con período de validez; el proceso programado PointsExpiryTimer escanea cada 60 s con cursor las filas earn caducadas, escribe filas de deducción negativa type=expire (source=expiry + order_id rastrea el historial original, triple idempotencia) + notificación interna agregada «Tienes X puntos caducados»; el saldo disponible con criterio SUM incluye las filas negativas expire, los puntos caducados no pueden convertirse en efectivo ni canjearse.

**Transferencia de cupones (Ronda 17)**: transfer valida que el cupón sea propio/available/la definición del cupón no haya caducado/no haya sido transferido antes, genera un código de transferencia único de 8 caracteres sin ambigüedad (índice único uk_code de respaldo), válido 7 días. claim con protección contra abuso: bloqueo Redis NX (coupon_transfer_claim:{code} 30 s) + re-verificación con bloqueo de fila contra doble gasto, índice único uk_user_coupon limita una transferencia por cupón, los cupones transferidos no se pueden volver a transferir (el cupón nuevo no tiene registro de transferencia y queda bloqueado de forma natural), no se puede recibir un cupón transferido por uno mismo 422, el receptor no puede ser el titular original; determinación diferida de caducidad → expired y restauración del cupón original a available. Dentro de la transacción de claim, el cupón original pasa a used + se crea un nuevo UserCoupon vinculado al receptor (coupon_id sin cambios, es decir, la validez no cambia) + el registro pasa a claimed.

---

### 6. Interfaces de notificaciones (requieren autenticación JWT)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/notification` | Lista de notificaciones (?type=order/system&page=1) |
| PUT | `/api/notification/read/{id}` | Marcar leída |
| PUT | `/api/notification/read-all` | Marcar todas leídas |

---

### 7. Interfaces de billetera (requieren autenticación JWT)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/wallet` | Saldo de la billetera + historial paginado |
| POST | `/api/wallet/recharge` | Crear orden de recarga (amount: yuanes) |
| POST | `/api/wallet/recharge/{id}/pay` | Iniciar pago de la orden de recarga (WeChat) |
| POST | `/api/wallet/transfer` | Transferencia de saldo (to_user_id hashid/amount/remark opcional/client_token opcional) (Ronda 19) |
| GET | `/api/wallet/transfers` | Registros de transferencia (?direction=out/in&page=1) (Ronda 19) |
| GET | `/api/wallet/transfers/{id}` | Detalle de la transferencia (solo visible para ambas partes, ajeno 404) (Ronda 19) |

**Historial**: tipos de wallet_txn: recharge / consume / refund / gift_card / referral_reward (comisión de distribución) / referral_level2 (comisión de nivel 2) / points_exchange (ingreso por canje de puntos), devuelto paginado.

**Recarga**: `POST /api/wallet/recharge` pasando amount (yuanes) crea la orden de recarga y devuelve el hashid de la orden. `POST /api/wallet/recharge/{id}/pay` inicia el pago de WeChat, la respuesta incluye sign_params (mismo patrón que el pago de pedidos); la devolución de llamada de pago distingue las órdenes de recarga de los pedidos con el out_trade_no de prefijo R.

**Pago con saldo**: pasar `pay_channel: "balance"` en el cuerpo de la solicitud de pago del pedido usa el saldo de la billetera; tanto el reembolso de WeChat como el de saldo recargan el importe en el saldo de la billetera.

**Transferencia de saldo (Ronda 19)**: `POST /api/wallet/transfer` — decodificación hashid del receptor + existencia 404, transferirse a uno mismo 422, importe 0.01-1000 por operación 422 (comparación DECIMAL, prohibido float), saldo insuficiente 422, límite diario acumulado de 5000 yuanes 422. Concurrencia/idempotencia: bloqueo Redis NX wallet_transfer:{from} 30 s serializa al emisor → lockForUpdate de las filas de billetera de ambas partes por user_id ascendente dentro de la transacción (orden fijo contra deadlocks) → deducción al emisor + suma al receptor + doble historial WalletTxn (transfer_out/transfer_in con instantánea balance_after) + registro de transferencia completed + notificación interna al receptor type='balance_received' (el fallo solo registra log). client_token opcional: tras el éxito SETNX 24 h contra envíos duplicados (las solicitudes fallidas no registran token y se pueden reintentar).

---

### 8. Interfaces del panel de trabajo del gerente de tienda (requieren autenticación JWT)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/store-manager/overview` | Resumen de hoy (pedidos de hoy/ingresos de hoy/en curso/número de técnicos/número de verificaciones) |
| GET | `/api/store-manager/orders` | Lista de pedidos de la tienda (?status=&page=&limit=) |
| GET | `/api/store-manager/technicians` | Lista de técnicos (incluye horarios de hoy) |
| GET | `/api/store-manager/revenue` | Agregación de ingresos de los últimos 7 días |

**Aislamiento por store_id**: requireStoreId() obliga a que el usuario actual esté vinculado a una tienda (erik_user.store_id), 403 sin tienda; todas las consultas se filtran por store_id.

---

### 9. Interfaces de nivel de crecimiento (requieren autenticación JWT, Ronda 20)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/growth` | Resumen de crecimiento actual (balance/nivel/diferencia al siguiente nivel/nombre del nivel) |
| GET | `/api/growth/records` | Historial de crecimiento paginado (?page=&limit=) |
| GET | `/api/growth/levels` | Lista de niveles (pública, sin necesidad de inicio de sesión) |

**Ingreso de crecimiento**: registro diario +10; envío de evaluación +20 (las evaluaciones complementarias no ingresan); consumo floor(paid) 1 punto por cada yuan (dentro de la devolución de llamada de pago se reutiliza la re-verificación de estado, idempotente, las devoluciones de llamada repetidas no ingresan de nuevo).

### 10. Interfaces de factura (requieren autenticación JWT, Ronda 20)

| Método | Ruta | Descripción |
|------|------|------|
| POST | `/api/invoices` | Solicitar factura (order_id hashid/order_type: service=servicio/points_exchange=canje de puntos/order_type predeterminado service; el importe y el título los aporta el servidor, no manipulables) |
| GET | `/api/invoices` | Lista de facturas (?status=&page=) |
| GET | `/api/invoices/{id}` | Detalle de la factura (solo propio) |

**Contra duplicados**: clave única uk_order_type(order_id, order_type), solicitud duplicada del mismo pedido y tipo 422 (incluye captura de respaldo de MySQL 1062).

### 11. Interfaces de tickets de atención al cliente (requieren autenticación JWT, Ronda 20)

| Método | Ruta | Descripción |
|------|------|------|
| POST | `/api/tickets` | Enviar ticket (title/content obligatorios) |
| GET | `/api/tickets` | Lista de tickets (?status=open/closed&page=) |
| GET | `/api/tickets/{id}` | Detalle del ticket (solo propio, ajeno 404) |
| POST | `/api/tickets/{id}/close` | Cerrar ticket (solo propio/solo open; rating opcional 1-5 de satisfacción, fuera de rango/no entero 422, si no se proporciona compatible NULL) |

### 12. Interfaces de calendario de reservas (requieren autenticación JWT, Ronda 20)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/calendar/technician/{id}` | Vista mensual (?month=YYYY-MM): time_slots de horarios expandidos en intervalos horarios + exclusión de lo ya reservado |
| GET | `/api/calendar/technician/{id}/day` | Vista diaria (?date=YYYY-MM-DD): detalle de intervalos disponibles/reservados/no disponibles de ese día |

### 13. Interfaces de títulos de factura (requieren autenticación JWT, Ronda 21)

| Método | Ruta | Descripción |
|------|------|------|
| POST | `/api/invoice-titles` | Guardar título (title_type: personal/company; company requiere tax_no; título duplicado del mismo usuario 422; la primera entrada es predeterminada automáticamente) |
| GET | `/api/invoice-titles` | Lista de títulos (el predeterminado arriba) |
| PUT | `/api/invoice-titles/{id}` | Editar título (solo propio) |
| DELETE | `/api/invoice-titles/{id}` | Eliminar título (solo propio; al eliminar el predeterminado se asigna automáticamente la más antigua) |
| POST | `/api/invoice-titles/{id}/default` | Establecer predeterminado (transacción limpia las otras filas del mismo usuario) |

**Vinculación en la solicitud**: POST /api/invoices soporta title_id opcional — resuelve el título e incorpora automáticamente invoice_title/tax_no/title_type, sin title_id se conserva la ruta de relleno manual original.

### 14. Interfaces de historial de navegación (requieren autenticación JWT, Ronda 21)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/browse-history` | Servicios vistos recientemente (join del nombre del servicio/portada/precio/precio original, viewed_at descendente, per_page por defecto 15 máximo 50) |
| DELETE | `/api/browse-history/{item_id}` | Eliminar uno (solo propio, inválido/ajeno 404) |
| DELETE | `/api/browse-history` | Vaciar el historial (solo propio) |

**Momento del registro**: se registra automáticamente tras acceder correctamente a la interfaz de detalle del servicio (sin inicio de sesión se salta; la navegación repetida solo actualiza viewed_at sin insertar de nuevo).

### 15. Interfaces de actividades de reducción de importe (Ronda 22)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/full-reduction-activities` | Lista de actividades de reducción de importe activas (status=1 y dentro del período de validez, ordenadas por importe de reducción descendente; interfaz pública) |

**Reglas de superposición en el pedido**: la reducción de importe solo aplica a pedidos estándar (compra grupal/flash saltan), el umbral (threshold) se juzga sobre el importe a pagar tras el descuento de cupón/tarjeta por uso, orden de superposición **cupón/tarjeta por uso → reducción de importe → descuento por nivel**; se toma la actividad de mayor reducción; el importe del descuento se suma a discount_amount, la nota añade «reducción de importe: por X, descuento Y»; pago real tras reducción con mínimo de 0.01 yuanes.

### 16. Exportación ICS de mis reservas (requiere autenticación JWT, Ronda 22)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/order/ics` | Exportar los pedidos vigentes de los últimos 90 días (pending/paid/confirmed/serving) como iCal (RFC5545) |

**Salida**: `Content-Type: text/calendar; charset=utf-8` + `Content-Disposition: attachment; filename="my-appointments.ics"`. VEVENT: UID=ID del pedido, TZID=Asia/Shanghai, resumen «Reserva: nombre del servicio» (degradación a «Reserva» si falta), descripción (técnico/tienda/dirección, salta si falta), LOCATION nombre de la tienda; texto escapado según RFC5545 (\, \; \\ \n) + plegado de líneas de 75 bytes. Sin pedidos devuelve un calendario vacío válido; solo exporta pedidos propios.

### 17. Interfaces de asistencia del técnico (requieren autenticación JWT, Ronda 22)

| Método | Ruta | Descripción |
|------|------|------|
| POST | `/api/technician/attendance/check-in` | Registro de entrada (duplicado del mismo día 422, índice único de respaldo contra concurrencia; >10:00 se marca retraso) |
| POST | `/api/technician/attendance/check-out` | Registro de salida (sin fichar/fichado de salida 422, bloqueo de fila contra concurrencia) |
| GET | `/api/technician/attendance` | Lista de asistencia del mes actual + resumen de días de asistencia/horas totales/horas medias (?month=YYYY-MM, inválido 422) |

### 18. Interfaces de cumplimiento de privacidad (requieren autenticación JWT, Ronda 22)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/privacy/data` | Exportación de datos (JSON agrupado por personal/orders/points/wallet_txns/reviews/addresses/invoices; el log del servidor solo registra el teléfono desidentificado + la cantidad) |
| POST | `/api/privacy/close-request` | Solicitar cancelación (saldo distinto de 0 / pedidos sin completar / tickets en curso 422; pone close_status=1 + close_requested_at) |
| POST | `/api/privacy/close-cancel` | Cancelar la solicitud de cancelación (close_status 1→0) |
| POST | `/api/privacy/close-confirm` | Confirmar la cancelación (solo tras 72 h; close_status=2 + close_at + teléfono/apodo anonimizados a user{id} + status=0) |

**Bloqueo de inicio de sesión**: el inicio de sesión de una cuenta con close_status=2 devuelve 403 «cuenta cancelada».

### 19. Interfaces de perfil de salud del usuario (requieren autenticación JWT, Ronda 23)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/health-profile` | Consultar mi perfil de salud (sin perfil devuelve objeto vacío) |
| PUT | `/api/health-profile` | Crear/actualizar (upsert, uno por persona; allergies/health_notes con límite de 500 caracteres, preferred_technician_id valida existencia; solo actualiza los campos proporcionados, respuesta con codificación hashid) |
| DELETE | `/api/health-profile` | Eliminar mi perfil (solo propio) |

Campos: allergies (historial de alergias) / health_notes (notas de salud) / preferred_technician_id (técnico preferido, anulable).

### 20. Interfaces de contraseña de pago de la billetera (requieren autenticación JWT, Ronda 23)

| Método | Ruta | Descripción |
|------|------|------|
| POST | `/api/wallet/pay-password/set` | Establecer contraseña de pago (6 dígitos `\d{6}`; si ya está establecida, requiere la contraseña antigua 422 de bloqueo) |
| POST | `/api/wallet/pay-password/verify` | Validar la contraseña de pago (devuelve booleano correcto/incorrecto, sin escribir) |
| POST | `/api/wallet/pay-password/check` | Consultar si está establecida (set: true/false) |

Almacenamiento: hash password_hash() + pay_password_set_at, nunca se almacena en texto plano.

### 21. Interfaces de línea de tiempo de estados de pedido (requieren autenticación JWT, Ronda 23)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/order/{id}/timeline` | Línea de tiempo de cambios de estado del pedido (descendente; solo propio, pedido ajeno 404 sin filtrar la existencia) |

Puntos de marcado: envío/pago (la devolución de llamada de WeChat markOrderPaid es el único punto de consumo)/cancelación/confirmación del técnico/solicitud de reembolso/reembolso aprobado/inicio del servicio/servicio completado/cancelación automática por tiempo de espera/operación del panel (operator=admin), 8 tipos de cambios en total.

### 22. Interfaces de la ruleta de la suerte de puntos (requieren autenticación JWT, Ronda 23)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/wheel/prizes` | Lista de premios de la ruleta (oculta los campos sensibles weight/stock) |
| POST | `/api/wheel/spin` | Sorteo de una vez (Redis NX + bloqueo de fila contra concurrencia; extracción ponderada con random_int; puntos → historial earn con fecha de caducidad, saldo → ingreso con lockForUpdate, cupón → pending de emisión manual, sin premio → lose; idempotencia client_token) |
| GET | `/api/wheel/records` | Mis registros de sorteos (paginado) |

### 23. Interfaces de modo invitado (Ronda 24)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/guest/home` | Agregación del inicio (banners/anuncios/categorías de servicios/servicios populares, caché Redis svc:guest:home 300 s) |
| GET | `/api/guest/services` | Lista de servicios (?category_id=hashid&sort=newest\|sales\|price&page/per_page≤50) |
| GET | `/api/guest/services/{id}` | Detalle del servicio (inexistente 404) |
| GET | `/api/guest/stores` | Lista de tiendas |
| GET | `/api/guest/technicians` | Lista de técnicos (solo aprobados; ?service_id=hashid filtro; puntuación descendente) |

Entradas de navegación sin inicio de sesión que no requieren autenticación (solo middleware ApiVersion).

### 24. Interfaces de oferta flash (requieren autenticación JWT, Ronda 24)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/seckill` | Lista de actividades de oferta flash (status=1 y dentro de la ventana de tiempo; incluye vendidos = pedidos con erik_order.seckill_id, stock restante) |
| GET | `/api/seckill/{id}` | Detalle de la actividad (state=not_started/ongoing/ended) |
| POST | `/api/seckill/{id}/buy` | Pedido flash (idempotencia client_token + Redis NX 30 s contra concurrencia + validación de la actividad; ya no reserva stock de antemano) |

**Reglas de pedido (desde 2026-08-26)**: el stock se reduce uniformemente con bloqueo de fila dentro de la transacción de `/api/order store()`, buy solo hace la validación de entrada/idempotencia; precio flash = seckill_price (según DB), sin superposición de cupones/puntos/tarjetas de membresía; la cancelación del pedido no repone el stock; llamar directamente a `/api/order` con seckill_id también descuenta stock.

### 25. Interfaces de comprobación de versión de APP (Ronda 24)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/api/app/version?platform=android|ios` | Comprobación de la versión más reciente (platform inválido 422; sin versión devuelve objeto vacío; interfaz pública) |

Respuesta: id/platform/version_code/version_name/force_update (1=obligatoria)/changelog/download_url.

---

## II. API del panel de administración (admin/ :8787)

Cabecera de solicitud: `Authorization: Bearer <admin_token>`, `API-Version: v1`

### Dashboard

**`GET /admin/dashboard`** — datos del dashboard

Respuesta: user_count / order_count / technician_count / today_revenue + datos de gráficos (volumen de pedidos/importes/nuevos usuarios/actividad)

### Gestión de usuarios

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/user` | Lista de usuarios (?keyword/status/page/per_page) |
| POST | `/admin/user` | Agregar usuario |
| GET | `/admin/user/{id}` | Detalle del usuario |
| PUT | `/admin/user/{id}` | Editar usuario |
| DELETE | `/admin/user/{id}` | Eliminar usuario |
| POST | `/admin/user/batch/destroy` | Eliminación por lotes |
| POST | `/admin/user/batch/status` | Habilitar/deshabilitar por lotes |

### Gestión de tarjetas de membresía

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/member-cards` | Lista de tarjetas (?keyword/status/page/per_page) |
| GET | `/admin/member-cards/{id}` | Detalle de la tarjeta |
| POST | `/admin/member-cards` | Agregar tarjeta (validación JSON de services) |
| PUT | `/admin/member-cards/{id}` | Actualizar tarjeta/alta y baja |
| DELETE | `/admin/member-cards/{id}` | Eliminar tarjeta (rechazado si hay usuarios con la tarjeta) |

IDs de permiso: 365-369.

### Panel de trabajo de tienda (Ronda 15)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/stores/workbench-overview` | Resumen del panel de trabajo de tienda (?store_id=hashid: pedidos de hoy/ingresos de hoy/en curso/número de técnicos/verificaciones de hoy, criterio coherente con el extremo service) |
| GET | `/admin/orders` | La lista de pedidos añade filtro store_id (decodificación hashid) |

IDs de permiso: 372.

### Productos de canje de puntos (Ronda 16)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/points-exchange-goods` | Lista de productos (?keyword/status/page/per_page) |
| POST | `/admin/points-exchange-goods` | Agregar producto (type=coupon/gift_card/wallet; coupon pasa hashid, wallet/gift_card pasan importe en yuanes) |
| PUT | `/admin/points-exchange-goods/{id}` | Actualizar producto |
| DELETE | `/admin/points-exchange-goods/{id}` | Eliminar producto |
| POST | `/admin/points-exchange-goods/{id}/toggle-status` | Cambio de alta y baja |
| GET | `/admin/points-exchange-goods/{id}/exchanges` | Lista de registros de canje (incluye teléfono del usuario + instantánea de result) |

IDs de permiso: 373-378.

### Registros de comisiones (Ronda 16)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/referral-rewards` | Registros de comisiones (?keyword=&page=&limit=, solo registros ya emitidos, filtro por apodo o teléfono del recomendador/recomendado, codificación hashid) |

ID de permiso: 379.

### Niveles de técnico (Ronda 17)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/technician-tiers/logs` | Registro de cambios de nivel (join del nombre del técnico y de los nombres de los niveles antiguo y nuevo, codificación hashid, paginado) |

ID de permiso: 380.

**Evaluación automática**: TierRatingService::evaluate calcula en tiempo real (pedidos completed de erik_order + puntuación media de evaluaciones, redondeo a 1 decimal) y escribe de vuelta en profile.order_count/rating, coincidiendo de mayor a menor según erik_technician_tier_config (min_orders/min_rating), sin coincidencia cae al nivel mínimo. Solo sube, no baja (la bajada afecta a la tasa de comisión y al coeficiente de precios, con respaldo manual del panel; allowDowngrade=true para re-evaluación manual); idempotente (si el nivel coincide solo sincroniza estadísticas); los cambios se registran en erik_technician_tier_log + notificación interna. Puntos de activación: WorkController::complete / escritura de evaluaciones en ReviewController / determinación diferida al ver el perfil en ProfileController.

### Visualización de respuestas a evaluaciones (Ronda 18)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/reviews/{id}/reply` | Detalle de la respuesta a la evaluación (decodeId → find → 404 → salida decorate; sin responder reply='', reply/replied_at expuestos vía toArray; ruta estática antes de resource) |

ID de permiso: 381 (slug 'get.admin/reviews/{id}/reply').

### Gestión de facturas (Ronda 20)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/invoices` | Lista de facturas (?status=pending/issued/rejected&page=) |
| POST | `/admin/invoices/{id}/issue` | Emitir factura (invoice_no obligatorio, status→issued + issued_at; idempotente: ya emitida 422) |
| POST | `/admin/invoices/{id}/reject` | Rechazar (reject_reason obligatorio, status→rejected; solo pending se puede rechazar) |

IDs de permiso: 382 lista / 383 emisión / 384 rechazo.

### Gestión de tickets (Ronda 20)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/tickets` | Lista de tickets (?status=&page=, ruta estática antes de resource para evitar shadow) |
| POST | `/admin/tickets/{id}/reply` | Responder ticket (content obligatorio, escribe reply_content/replied_at, el ticket vuelve a open) |
| GET | `/admin/tickets/satisfaction` | Resumen de satisfacción (Ronda 21): total/rated_count/unrated_count/average 1 decimal/distribution 1-5 estrellas con las faltantes en 0; ruta estática antes de resource |

IDs de permiso: 385 respuesta de tickets / 387 visualización de la lista de tickets / 388 estadísticas de satisfacción de tickets.

### Revisión de imágenes de evaluaciones (Ronda 21)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/review-audit` | Lista de evaluaciones con imágenes (JSON_LENGTH(images)>0, ?status=visible/hidden&page=, join del apodo del usuario y del nombre del técnico, ID con codificación hashid) |
| POST | `/admin/review-audit/{id}/hide` | Ocultar evaluación (solo visible se puede ocultar, si no 422; al ocultar, la lista de evaluaciones del técnico del extremo de usuario deja de ser visible automáticamente) |
| POST | `/admin/review-audit/{id}/restore` | Restaurar evaluación (solo hidden se puede restaurar, si no 422) |

IDs de permiso: 389 lista / 390 ocultar / 391 restaurar.

### Registros de comisión de nivel 2 (Ronda 20)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/referral-level2` | Registros de comisión de nivel 2 (join de los apodos del recomendador de nivel 1 y del recomendador de nivel 2, paginado) |

ID de permiso: 386. Regla de emisión: tras el pago del pedido, al recomendador del recomendador de nivel 1 se le envía paid×level2_rate (configuración del sistema referral.level2_rate, por defecto 0.02), uk_order_referred contra duplicados.

### Gestión de asistencia (Ronda 22)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/attendance` | Registros de asistencia (?date=YYYY-MM&name=nombre del técnico&page=; join real_name, ID con codificación hashid) |
| GET | `/admin/attendance/stats` | Estadísticas agrupadas por técnico (días de registro/horas totales/horas medias; ?date=YYYY-MM, inválido 422) |

IDs de permiso: 392 lista / 393 estadísticas.

### Gestión de actividades de reducción de importe (Ronda 22)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/full-reduction-activities` | Lista de actividades (paginado) |
| POST | `/admin/full-reduction-activities` | Agregar (threshold/reduction/title/status/start_at/end_at) |
| PUT | `/admin/full-reduction-activities/{id}` | Editar |
| POST | `/admin/full-reduction-activities/{id}/toggle-status` | Alta y baja |
| DELETE | `/admin/full-reduction-activities/{id}` | Eliminar (con confirmPassword) |

IDs de permiso: 396 lista / 397 crear / 398 editar / 399 alta y baja / 400 eliminar (un registro de permiso corresponde a un solo slug method.path, por eso 5 rutas, 5 registros).

### Registros de reparto (Ronda 22)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/profit-sharing` | Registros de reparto (leftJoin del número de pedido/apodo del técnico, ?status&order_no&technician_name&page=, codificación hashid) |

ID de permiso: 394. Lógica del servidor: erik_system_config group=profit_sharing (enabled/receiver_ratio); no habilitado, degradación disabled solo con log; habilitado, tras el pago correcto se solicita automáticamente el reparto (importe = pago real × receiver_ratio por defecto 0.7, el mismo pedido pending/success salta por idempotencia); sin credenciales no ejecuta HTTP, la estructura de la solicitud se registra en el log.

### Gestión de la ruleta de puntos (Ronda 23)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/lucky-wheel` | Lista de premios de la ruleta (incluye weight/stock, paginado) |
| POST | `/admin/lucky-wheel` | Agregar premio (nombre/tipo points/balance/coupon/none/peso/stock/imagen) |
| GET/PUT | `/admin/lucky-wheel/{id}` | Detalle / editar |
| DELETE | `/admin/lucky-wheel/{id}` | Eliminar |
| POST | `/admin/lucky-wheel/{id}/toggle-status` | Alta y baja |
| GET | `/admin/lucky-wheel/records` | Registros de sorteos (?status&page=, incluye apodo del usuario/nombre del premio) |

IDs de permiso: 401-406. Las rutas estáticas `/lucky-wheel/records` y `/lucky-wheel/{id}/toggle-status` se registran antes de resource para evitar la sombra de {id}.

### Gestión de recompensa de cliente habitual (Ronda 24)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/return-customer/config` | Visualización de configuración (interruptor enabled / proporción ratio) |
| PUT | `/admin/return-customer/config` | Actualización de configuración (enabled in:0,1; ratio between:0.01,1) |
| GET | `/admin/return-customer/rewards` | Lista de registros de recompensas (?keyword nombre del técnico/número de pedido/apodo del usuario, type=return_customer paginado) |

IDs de permiso: 412-414. Regla de recompensa: la segunda compra del usuario al mismo técnico dentro de 30 días (pedido completado) emite un bono = pago real × ratio (por defecto 0.05), registrado en erik_technician_earnings (type=return_customer, status=pending) que se liquida junto con la cadena de liquidación de comisiones; idempotente por pedido, sin emisión duplicada.

### Gestión de actividades de oferta flash (Ronda 24)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/seckill` | Lista de actividades (paginado) |
| POST | `/admin/seckill` | Agregar actividad (name/service_id/seckill_price/original_price/stock/start_at/end_at) |
| GET | `/admin/seckill/{id}` | Detalle de la actividad |
| PUT | `/admin/seckill/{id}` | Editar |
| DELETE | `/admin/seckill/{id}` | Eliminar |
| POST | `/admin/seckill/{id}/toggle-status` | Alta y baja |
| GET | `/admin/seckill/{id}/orders` | Lista de pedidos flash |

IDs de permiso: 407-411, 420. Vendidos = pedidos con erik_order.seckill_id; reducción de stock con bloqueo de fila, bloqueo por agotado.

### Gestión de versiones de APP (Ronda 24)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/versions` | Lista de versiones |
| POST | `/admin/versions` | Agregar versión (platform/version_code/version_name/force_update/changelog/download_url/status) |
| PUT | `/admin/versions/{id}` | Editar |
| DELETE | `/admin/versions/{id}` | Eliminar |

IDs de permiso: 416-419. La interfaz de detección de actualizaciones /api/app/version toma la más reciente (mayor updated_at/id) de las de status=1.

### Exportación de horarios (Ronda 24)

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/technician-schedule/export` | Exportación CSV de horarios (UTF-8 BOM, Excel lo abre directamente; start_date/end_date obligatorios y tramo ≤31 días; technician_id opcional hashid) |

ID de permiso: 415. Columnas: ID del técnico/nombre del técnico/fecha/detalle de intervalos (time_slots JSON analizado como "09:00-12:00, 14:00-18:00").

### Roles y permisos

| Método | Ruta | Descripción |
|------|------|------|
| GET/POST/PUT/DELETE | `/admin/role` | CRUD de roles |
| GET/POST/PUT/DELETE | `/admin/permission` | CRUD de permisos (estructura de árbol) |

### Configuración del sistema

| Método | Ruta | Descripción |
|------|------|------|
| GET | `/admin/config` | Lista de configuraciones |
| POST | `/admin/config` | Agregar configuración (group/key/value/type/description) |
| PUT | `/admin/config/{id}` | Editar configuración |
| DELETE | `/admin/config/{id}` | Eliminar configuración |

### Registros de operaciones

**`GET /admin/log`** — consulta de registros

Parámetros: `?user_id/action/source/start_date/end_date/page`

Campo `source`: web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### Exportación

| Método | Ruta | Descripción |
|------|------|------|
| POST | `/admin/export/excel` | Exportación Excel (type: users/technicians/orders/finance). Los campos sensibles se desidentifican automáticamente |
| POST | `/admin/export/pdf` | Exportación de panel PDF (type: dashboard) |

### Carga de archivos

**`POST /admin/upload`** — carga de archivos (multipart/form-data)

### Centro personal

| Método | Ruta | Descripción |
|------|------|------|
| PUT | `/admin/profile` | Modificar perfil personal |
| PUT | `/admin/profile/password` | Cambiar contraseña |
| POST | `/admin/profile/logout` | Cerrar sesión |

### Importación

**`POST /admin/import/users`** — importación masiva de usuarios (Excel)

### Monitorización

| Método | Ruta | Autenticación | Descripción |
|------|------|------|------|
| GET | `/health` | Ninguna | Comprobación de salud |
| GET | `/metrics` | Ninguna | Métricas Prometheus |
| GET | `/.well-known/security.txt` | Ninguna | Contacto de seguridad (RFC 9116) |
| GET | `/api/docs` | Ninguna | Documentación de API |

---

## III. Notas generales

### Códigos de error

| code | Descripción |
|------|------|
| 0 | Correcto |
| 401 | Sin inicio de sesión o token caducado |
| 403 | Sin permiso |
| 404 | Recurso inexistente |
| 422 | Fallo de validación de parámetros |
| 429 | Solicitudes demasiado frecuentes |

### Codificación de ID

- Todos los campos `id` y `*_id` de las respuestas de API se codifican con hashids
- Los parámetros `id` enviados en las solicitudes también deben usar el formato de codificación hashids
- El frontend usa directamente las cadenas codificadas, sin necesidad de decodificar manualmente

### Desidentificación de teléfonos

Formato de teléfono en las respuestas: `138****8000`. Mismo tratamiento en la exportación Excel.

### Cifrado de datos

- Capa API: los campos sensibles de las respuestas se cifran con `erikwang2013/encryption`
- Capa DB: el teléfono/DNI/ID de WeChat, etc., se cifran y descifran automáticamente con `erikwang2013/encryptable`

### Configuración de variables de entorno

| Variable | Descripción |
|------|------|
| WECHAT_SUBSCRIBE_TEMPLATE_ID | ID de plantilla de mensaje de suscripción de recordatorio de reserva |
| WECHAT_SUBSCRIBE_TEMPLATE_PAID | ID de plantilla de mensaje de suscripción de pago correcto |
| WECHAT_SUBSCRIBE_TEMPLATE_REFUND | ID de plantilla de mensaje de suscripción de reembolso |
| WECHAT_SUBSCRIBE_TEMPLATE_VERIFIED | ID de plantilla de mensaje de suscripción de verificación |
| WECHAT_SUBSCRIBE_TEMPLATE_REMINDER | ID de plantilla de mensaje de suscripción de recordatorio antes del inicio del servicio (Ronda 18) |
| WECHAT_SUBSCRIBE_TEMPLATE_EXPIRY | ID de plantilla de mensaje de suscripción de recordatorio de caducidad de tarjetas de membresía/cupones (Ronda 18) |

Cuando las plantillas de mensajes de suscripción no están configuradas, se degrada automáticamente a notificación interna.

**Escenarios de mensajes de suscripción**: SCENE_PAY (pago correcto) / SCENE_REFUND (reembolso recibido) / SCENE_VERIFIED (verificación correcta) / SCENE_RESCHEDULE (reprogramación correcta) / SCENE_REMINDER (recordatorio antes del inicio del servicio, Ronda 18) / SCENE_EXPIRY (recordatorio de caducidad, Ronda 18). push_sent_at solo se escribe si el push tuvo éxito; el fallo se reintenta en la siguiente ronda.

**Notificación de llegada de recarga (Ronda 18)**: la devolución de llamada de recarga de WeChat (número de pedido con prefijo R) escribe dentro de la transacción la notificación interna type='wallet_recharge' «Has recargado correctamente ¥X.XX»; reutiliza la idempotencia de la devolución de llamada (solo la primera vez pending→paid la activa), confirmación atómica en la misma transacción que el cambio de estado, el fallo de escritura no bloquea el flujo principal.
