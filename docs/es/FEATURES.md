# Descripción de funciones
> **Languages**: [中文](../FEATURES.md) · [English](../en/FEATURES.md) · [한국어](../ko/FEATURES.md) · [Русский](../ru/FEATURES.md) · [Deutsch](../de/FEATURES.md) · [Français](../fr/FEATURES.md) · [Português](../pt/FEATURES.md) · [हिन्दी](../hi/FEATURES.md) · [العربية](../ar/FEATURES.md) · [বাংলা](../bn/FEATURES.md) · [Bahasa Indonesia](../id/FEATURES.md) · [日本語](../ja/FEATURES.md)

> **Estado del proyecto**: todo completado ✅ | 109 controladores | 103 modelos | 344 pruebas (service 240 / admin 104) | WebSocket | devolución de llamada de pago | llamada de turnos | evaluación | comunidad

## I. Extremo de usuario (mini programa WeChat + APP Flutter)

Las funciones del mini programa y de la APP del extremo de usuario son idénticas. La cuenta unificada soporta el cambio de identidad cliente/técnico.

### 1. Autenticación

| Función | Descripción |
|------|------|
| Registro con teléfono | Teléfono + código de verificación + contraseña + confirmar contraseña, soporta código de recomendación |
| Inicio de sesión con contraseña | Teléfono registrado + contraseña |
| Inicio de sesión con código de verificación | Teléfono registrado + código de verificación |
| Inicio de sesión con WeChat | Inicio de sesión autorizado por WeChat, el primer acceso requiere vincular el teléfono |
| Modo invitado | Se puede navegar pero no hacer pedidos; hacer pedidos requiere registrarse |
| Contraseña olvidada | Cambiar contraseña con código de verificación |
| Acuerdo de usuario / acuerdo de privacidad | Editable en el panel de administración, se muestra al registrarse |

### 2. Página de inicio

| Función | Descripción |
|------|------|
| Localización LBS | Localiza el área actual, muestra los servicios de esa área, soporta cambiar de ciudad |
| Banners | Rotación automática, el panel de administración configura el salto (página web / detalle / sin operación) |
| Anuncios | Reproducción en marquesina, clic para ver la lista, agregados en el panel de administración |
| Categorías de servicios | Imagen / nombre / precio / ventas, clic para ver el detalle |
| Cupón para nuevos usuarios | Obtenido automáticamente al registrarse |

### 3. Servicios

| Función | Descripción |
|------|------|
| Información básica | Imagen / nombre / precio / ventas / especificaciones / duración del servicio / detalle del servicio |
| Evaluaciones de usuarios | Visualización del contenido de las evaluaciones, se pueden ver más |
| Reserva de servicios | Entra a la página de confirmación de pedido |
| Selección de tienda | Dirección de la tienda (navegación) / horario de atención / teléfono de contacto |
| Selección de técnico | Nombre / avatar / puntuación del técnico |
| Hora del servicio | Elegir el intervalo de reserva |
| 9% de descuento en horas valle | 10-12 h / 17-18 h / después de las 21:00 |
| 5% de descuento por reserva anticipada | 30 minutos antes, no acumulable con cupones |
| Cupones | Muestra el importe disponible, usar / no usar |
| Notas | Notas de necesidad del servicio (límite de caracteres) |
| Acuerdo de servicio | Leer y confirmar antes de enviar |

### 4. Búsqueda de productos y carrito

| Función | Descripción |
|------|------|
| Búsqueda de productos | Búsqueda por nombre |
| Filtro por categoría | Búsqueda por categoría |
| Detalle del producto | Cantidad comprable / favoritos / compartir / agregar al carrito / comprar ahora |
| Carrito | Seleccionar / eliminar / modificar cantidad |

### 5. Pedidos

| Función | Descripción |
|------|------|
| Todos los pedidos | Visualización por pestañas de estado |
| Pendiente de pago | Ver / pagar |
| Pendiente de envío / recogida | Avisar envío / cancelar pedido / ver |
| Pendiente de recepción | Información logística / confirmar recepción |
| Pendiente de evaluación | Detalle del pedido / evaluación de texto + imágenes |
| Completado | Visualización de la información del pedido |
| Reglas de reembolso | Dentro de los 15 min del pedido o >6 h reembolso 100% / <6 h 90% / tras el inicio 80% / tras confirmar no se reembolsa |

### 6. Técnicos (perspectiva del cliente)

| Función | Descripción |
|------|------|
| Lista de técnicos | De cerca a lejos / avatar / nombre / número de pedidos / puntuación / favoritos / distancia / horas disponibles / reservar ahora |
| Detalle del técnico | Imagen / nombre / distancia / pedidos / evaluaciones / favoritos / lista de servicios disponibles |
| Incorporación de técnicos | Rellenar la información para solicitar ser técnico, descargar la APP del extremo de técnico |

### 7. Panel de trabajo del técnico (tras el cambio de identidad)

| Función | Descripción |
|------|------|
| Resumen de hoy | Vista general de pedidos / ingresos de hoy |
| Configuración de horarios | Establecer los intervalos de reserva por día |
| Mis pedidos | Reservados sin verificar / completados |
| Verificación por escaneo | Escanear el código QR del usuario para verificar usos |
| Gestión de clientes | Lista de clientes atendidos / datos de consumo / tarjetas por uso / edición de archivos |
| Gestión de ganancias | Ingresos de hoy / en liquidación / saldo de la billetera |
| Fondos en tránsito | Verificado sin liquidar, confirmación automática en 3 días |
| Retiro | Día 20 de cada mes, T+1 llega a la billetera de WeChat; revisión del extremo de administración, importes ≥500 con aprobación en dos niveles (gerente de tienda → finanzas); reserva en tránsito del saldo al solicitar, re-verificación antes de la transferencia de aprobación, aprobación concurrente previene doble pago (refuerzo 2026-08-26) |
| Asistencia | Registro de entrada / salida / carga de fotos de higiene |
| Recompensa de cliente habitual | Registro de bono por segunda compra dentro de 30 días |
| Formación profesional | Cursos de vídeo / cursos de texto e imagen |
| Tareas de hoy | WorkController today: obtiene en tiempo real las tareas pendientes de hoy |
| Registros completados | WorkController records: historial de registros completados |
| Iniciar/completar servicio | WorkController start/complete: bloqueo de fila + guardias de máquina de estados + idempotencia, escribe notificación interna automáticamente al completar |
| Panel de trabajo del mini programa | tech-work con tres pestañas: verificación por escaneo / tareas de hoy / registros completados |

### 8. Centro personal

| Función | Descripción |
|------|------|
| Información personal | Avatar / apodo / teléfono |
| Cambio de identidad | Cliente ↔ técnico |
| Notificaciones de mensajes | Notificaciones internas (appointment_notification); página del centro de mensajes: paginación / desplegable de refresco / resaltado de leídos / marcar leído / marcar todos leídos |
| Mis tarjetas de membresía | Mensual / VIP anual / por uso (caducidad / usos / usados / restantes) |
| Mis puntos | Registros de obtención / puntos disponibles / registros de uso (1:100 canje por tarjeta regalo); puntos por registro diario y por consumo, recuperación proporcional al reembolsar, detalle paginado + filtro type/source |
| Mis tarjetas regalo | Tarjetas de efectivo / regalos físicos; el tipo cash se recarga directamente en la billetera al canjear |
| Cupones | Obtenidos disponibles / usados / caducados |
| Mis favoritos | Servicios favoritos |
| Seguir la cuenta oficial | Ventana emergente de código QR, mantener pulsado para guardar |
| Promoción de usuarios | Explicación de promoción / póster de código QR / lista de usuarios recomendados / recompensas de puntos |
| Comentarios | Envío de texto + imágenes, respuesta en 24 h |
| Sobre nosotros | LOGO / introducción / teléfono de atención al cliente / sitio web / correo |

### 9. Configuración

| Función | Descripción |
|------|------|
| Cambiar contraseña | Contraseña actual + nueva contraseña + confirmar nueva contraseña |
| Cambiar teléfono | Código de verificación del teléfono actual + código de verificación del nuevo teléfono |
| Acuerdo de usuario | Visualización de texto, editable en el panel |
| Acuerdo de privacidad | Visualización de texto, editable en el panel |
| Detectar actualización | Número de versión + actualizar |
| Cancelación de cuenta | Explicación de la cancelación + confirmación de la operación |
| Cerrar sesión | Borrar el estado de inicio de sesión |

### 10. Billetera de valor almacenado (Ronda 6)

| Función | Descripción |
|------|------|
| Saldo de la billetera | GET /api/v1/wallet saldo + historial (tablas user_wallet/wallet_recharge/wallet_txn) |
| Recarga | POST /api/v1/wallet/recharge crea la orden de recarga; POST /api/v1/wallet/recharge/{id}/pay pago de recarga con WeChat Pay, la devolución de llamada usa el número de pedido con prefijo R |
| Pago con saldo | Canal de pago del pedido pay_channel=balance |
| Reintegro en reembolso | Los reembolsos de WeChat/saldo recargan automáticamente el saldo (refundToBalance / creditRefundToWallet) |

### 11. Mensajes de suscripción (Rondas 6+8)

| Función | Descripción |
|------|------|
| Escenarios de suscripción | 3 escenarios de eventos de pedido: pago correcto / reembolso recibido / verificación correcta |
| Idempotencia | Marca push_sent_at para evitar push duplicados |
| Degradación | Sin plantilla de suscripción configurada, degradación automática a notificación interna |

### 12. Cierre de verificación de tarjeta por uso (Ronda 8)

| Función | Descripción |
|------|------|
| Mi tarjeta por uso | GET /api/v1/marketing/cards/my calcula en tiempo real used_up/expired |
| Verificación y descuento de usos | POST /api/v1/marketing/cards/use: Redis NX idempotente + bloqueo de fila lockForUpdate, crea directamente pedido completed + OrderItem + OrderPayment(pay_type='card') |

### 13. Descuento con cupones (Ronda 9)

| Función | Descripción |
|------|------|
| Elegir cupón al hacer el pedido | Opcional pasar user_coupon_id al hacer el pedido, PriceCalculator.applyCoupon validación de solo lectura + cálculo de importe |
| Tipos de descuento | fixed importe fijo / percent porcentaje, umbral min_amount de descuento por importe |
| Consumo y devolución | consume marca used al pagar correctamente; restoreCouponAndCard devuelve idempotentemente al reembolsar |

### 14. Tarjeta regalo (Ronda 9)

| Función | Descripción |
|------|------|
| Canje | redeem: el tipo cash recarga en la billetera (bloqueo de fila contra doble ingreso, WalletTxn type='gift_card'), el tipo gift solo se marca |
| Mi tarjeta regalo | GET /api/v1/marketing/gift-cards/my |

### 15. Sistema de puntos (Rondas 9+10)

| Función | Descripción |
|------|------|
| Puntos por registro diario | CheckIn registro diario |
| Puntos por consumo | Al verificar floor(paid×1), idempotente con order_id, instantánea de balance |
| Recuperación al reembolsar | clawbackOrderPoints recupera proporcionalmente (3 puntos de integración) |
| Puntos como efectivo | Pasar use_points al pagar, 100 puntos = 1 yuan (config app.points_rate), validación de saldo con agregación SUM, historial de consumo source=points_offset idempotente |
| Recuperación de puntos (Ronda 15) | La cancelación/reembolso devuelve los puntos points_offset: refundOffsetPoints con 5 puntos de enganche (doCancel 3 rutas/doRefund transacción WeChat/creditRefundToWallet/completeOneRefundCompensation), source=points_refund idempotente |
| Detalle de puntos | GET /api/v1/marketing/points paginación + filtro type/source, type unificado a earn |

### 16. Cadena de pedidos del mini programa (Ronda 10)

| Función | Descripción |
|------|------|
| Página de detalle del servicio | service/detail |
| Página de confirmación de pedido | order/confirm: elegir cupón / umbral deshabilitado / importe estimado en el cliente → POST /order → pago WeChat/saldo |
| Tamaño de páginas | El mini programa tiene ahora 20 páginas en total |

### 17. Tres entradas del lado del usuario (Ronda 10)

| Función | Descripción |
|------|------|
| Favoritos | Página de favoritos favorite (entrada desde la página de usuario) |
| Promoción | referral: código de invitación / copiar enlace / lista de usuarios recomendados |
| Comentarios | Formulario de comentarios feedback |

### 18. Autorización de mensajes de suscripción (Ronda 14)

| Función | Descripción |
|------|------|
| Autorización de suscripción | utils/subscribe.js gestiona centralizadamente los IDs de plantilla (nombres de clave alineados con appointment_system_config.wechat_app.template_ids del servidor) |
| Escenarios de activación | wx.requestSubscribeMessage dentro de las devoluciones de llamada de gesto tras reserva/pago correcto; sin ID de plantilla configurado o rechazo del usuario, todo silencioso |
| Cadena del servidor | Envío de WechatTemplateMessageService + recordatorio de NotificationReminderService 2 h~1 h antes de la reserva + escaneo del proceso AutoCancelTimer |

### 19. Posventa: cambio y devolución (Ronda 14)

| Función | Descripción |
|------|------|
| Solicitar posventa | POST /api/v1/aftersales: type=refund/exchange, validación de pedido propio/paid+completed/deduplicación por pedido |
| Mi posventa | GET /api/v1/aftersales lista paginada + GET /api/v1/aftersales/{id} detalle |
| Flujo de revisión | approve/reject en el extremo de administración (rejected requiere remark); approved solo cambia el estado, el reembolso reutiliza la interfaz de reembolso de pedidos |

### 20. Compra grupal / oferta flash (Ronda 15)

> Desde 2026-08 el canal FLASH_SALE está retirado: PromotionController::index filtra flash_sale, show/join le devuelven 400, las ofertas flash usan el canal «43. Oferta flash (Ronda 24)»; la constante `Promotion::TYPE_FLASH_SALE` se conserva para compatibilidad con datos históricos. Esta sección y «27. Pedido flash» son registros históricos.

| Función | Descripción |
|------|------|
| Lista/detalle de actividades | GET /api/v1/promotions + /api/v1/promotions/{id}, filtro de tipo group_buy/flash_sale |
| Participación | POST /api/v1/promotions/join/{id}: bloqueo Redis NX contra sobreventa (flash_sale usa max_people como límite de stock), participación repetida 422, group_buy bloqueado al llenarse, cierre diferido si no se llena al vencer (show/join ponen status 0) |
| Lista de participantes | GET /api/v1/promotions/{id}/participants |
| Corrección de estados | PromotionParticipant cambia a constantes enteras 0/1/2/3 (corrige el daño de join 1366 en modo estricto) |

### 21. Pedido de compra grupal (Ronda 16)

| Función | Descripción |
|------|------|
| Precio de grupo | La respuesta de join devuelve discount_percent/original_price/group_price |
| Pedido de grupo | POST /api/v1/order pasando promotion_id: valida solo group_buy / actividad vigente / el llamante es participante / no está lleno / el servicio coincide; precio de grupo = precio original × discount_percent/100, prohibidos cupones/tarjetas por uso/puntos superpuestos (422) |
| Marcado del pedido | appointment_order añade columnas promotion_id/participant_id + índices |
| Manejo de grupo no formado | Al vencer sin llenarse → cierre de la actividad + cancelación por lotes de los pedidos pending de esa actividad (idempotente); pay() determina de forma diferida si está cerrado y cancela automáticamente el pedido liberando el bloqueo del técnico |

### 22. Comisión de distribución (Ronda 16)

| Función | Descripción |
|------|------|
| Reglas de emisión | Tras el primer pedido completed del recomendado: importe = paid_amount × reward_rate (appointment_system_config referral.reward_rate, por defecto 0.05, retroceso a constante si es inválido), solo se emite si >0 |
| Puntos de enganche | ReferralRewardService::handleOrderCompleted enganchado dentro de la transacción de WorkController::complete (entrada única serving→completed, la verificación solo llega a serving y no lo activa), el fallo revierte todo y es reintentable |
| Idempotencia | appointment_user_referral bloqueo de fila lockForUpdate + comprobación de rewarded_at vacío + re-verificación del primer pedido dentro del bloqueo (concurrencia/llamadas repetidas solo emiten una vez) |
| Ingreso | Bloqueo de fila de billetera acumulado + WalletTxn type='referral_reward' (balance_after + número de pedido en remark); el registro de recomendación escribe reward_type/reward_amount/rewarded_at/first_order_at |
| Detalle | GET /api/v1/user/referral/earnings paginado (apodo/avatar del recomendado/número de pedido/importe/hora) |

### 23. Tienda de canje de puntos (Ronda 16)

| Función | Descripción |
|------|------|
| Productos de canje | appointment_points_exchange_goods: type=coupon/gift_card/wallet, points_cost/value (DECIMAL(25,2) contra pérdida de precisión de ID de avalancha)/stock/status |
| Lista de productos | GET /api/v1/marketing/points-exchange: productos en venta + stock restante en tiempo real + cantidad canjeada |
| Canje | POST /api/v1/marketing/points-exchange/{id}: bloqueo Redis NX + bloqueo de fila del producto contra sobrecanje; validación SUM de puntos (422 si insuficiente) + deducción de UserPoints type='consume' source='exchange'; coupon emite cupón / wallet ingresa saldo (WalletTxn points_exchange) / gift_card devuelve tarjeta con código |
| Idempotencia | Índice único uk_user_goods limita una vez por usuario y producto + re-verificación dentro del bloqueo + respaldo 1062; el registro de canje es instantánea en appointment_user_points_exchange |

### 24. Reprogramación de reservas (Ronda 17)

| Función | Descripción |
|------|------|
| Interfaz | POST /api/v1/order/reschedule/{id}: new_service_time (obligatorio) + reason (opcional), cambia de hora con el mismo técnico |
| Reglas | Solo pedidos propios (404 si no es propio); solo tipo appointment con estado pending/paid/confirmed (resto 422); ≥ 6 horas antes del inicio del servicio original (alineado con la ventana de reembolso completo) |
| Protección de concurrencia | B1 order_lock (misma familia de exclusión mutua que pay/cancel/refund) → bloqueo del técnico en el nuevo horario con Redis SETNX EX 180 (contra sobreventa en reprogramación concurrente) → re-lectura con bloqueo de fila dentro de la transacción + validación DB de conflicto de horarios B2 (excluyendo este pedido) |
| Cierre | Actualiza service_time + registra appointment_order_reschedule (con reason) + libera el bloqueo del horario original/la tenencia de este pedido del bloqueo del nuevo horario; en caso de fallo, la transacción revierte y también libera el bloqueo del nuevo horario |
| Notificaciones | Mensaje de suscripción SCENE_RESCHEDULE (sin plantilla configurada, degradación a notificación interna «reprogramación de reserva correcta») + pushOrderUpdate |

### 25. Transferencia de cupones (Ronda 17)

| Función | Descripción |
|------|------|
| Interfaces | POST /api/v1/marketing/coupons/transfer (user_coupon_id) genera un código de transferencia único de 8 caracteres sin ambigüedad (respaldo uk_code, válido 7 días); POST /api/v1/marketing/coupons/claim (code) para recibir; GET /api/v1/marketing/coupons/transfers emitidos (pending/claimed/expired) + recibidos (claimed) paginado |
| Validación | Cupón propio/available/definición del cupón sin caducar/no transferido antes (422); no se puede recibir un cupón transferido por uno mismo, el receptor no puede ser el titular original |
| Contra abuso | Bloqueo Redis NX coupon_transfer_claim:{code} (30 s) + re-verificación con bloqueo de fila dentro de la transacción contra doble gasto; índice único uk_user_coupon limita una transferencia por cupón; los cupones transferidos no se pueden volver a transferir (el cupón nuevo no tiene registro de transferencia y queda bloqueado de forma natural); determinación diferida de caducidad → expired + restauración del cupón original a available |
| Recepción | Dentro de la transacción el cupón original pasa a used + se crea un nuevo UserCoupon vinculado al receptor (coupon_id sin cambios, es decir, la validez no cambia) + el registro de transferencia pasa a claimed |

### 26. Caducidad de puntos (Ronda 17)

| Función | Descripción |
|------|------|
| Período de validez | Columna appointment_user_points.expires_at; todos los earn (registro diario/consumo/recuperación) se guardan con expires_at = now + points.expiry_days (por defecto 365, ≤0 nunca caduca); consume/use dejan vacío |
| Ejecución de caducidad | El proceso programado PointsExpiryTimer escanea cada 60 s con cursor (100/lote) las filas earn con expires_at < now → escribe filas de deducción negativa type=expire (source=expiry + order_id rastrea el historial original) → notificación interna agregada por usuario «Tienes X puntos caducados» |
| Idempotencia | ① la fila expire apunta con order_id al historial earn original, lockForUpdate de la fila original dentro de la transacción + re-verificación exists (los procesos concurrentes se serializan en el bloqueo de fila) ② paginación con cursor de id ③ las notificaciones solo se generan en las rondas reales de deducción |
| Criterio | El saldo disponible con agregación SUM incluye las filas negativas expire; los puntos caducados no pueden convertirse en efectivo ni canjearse |

### 27. Pedido flash (Ronda 18, retirado)

> Sustituido por el canal `/api/v1/seckill` de la Ronda 24 (la rama promocional de store() solo conserva la compra grupal), ver «43. Oferta flash».

| Función | Descripción |
|------|------|
| Interfaz | POST /api/v1/order pasando promotion_id (tipo flash_sale): precio flash = round(total × (100 − discount_percent)/100, 2), coherente con el criterio de precio flash de PromotionController |
| Validación | Lista blanca de tipos [group_buy, flash_sale] (resto 422); actividad en curso; el llamante es participante; el servicio del pedido coincide con la actividad; agotado participants_count ≥ max_people 422 «se ha agotado»; prohibidos cupones/tarjetas por uso/puntos superpuestos 422 |
| Caducidad | pay() determina de forma diferida isFlashSaleClosed (mismo patrón que isGroupBuyClosed): flash caducado → actividad a 0 + cancelación por lotes de los pedidos pending de la actividad + cancelación automática de este pedido + liberación del bloqueo del técnico 422 |

### 28. Recordatorio de servicio + recordatorio de caducidad (Ronda 18)

| Función | Descripción |
|------|------|
| Recordatorio antes del inicio del servicio | ServiceReminderTimer escanea cada 60 s service_time ∈ [now+1h, now+1h+60s), status confirmed/serving, pedidos tipo appointment → notificación interna (type='service_reminder', con servicio/técnico/tienda/hora) + mensaje de suscripción SCENE_REMINDER |
| Recordatorio de caducidad | ExpiryReminderTimer escanea cada 6 h end_at ∈ (now, now+3d+6h]: tarjetas de membresía active (type='card_expiry') + cupones available (type='coupon_expiry', whereHas con end_at de la definición del cupón asociado) + mensaje de suscripción SCENE_EXPIRY |
| Idempotencia | Ambos con cursor de id 100/lote + re-verificación con bloqueo de fila dentro de la transacción + comprobación de duplicados de notificaciones (la columna order_id registra el id de origen / el id del pedido como clave contra duplicados); solo se escribe push_sent_at si el push del mensaje de suscripción tuvo éxito, el fallo se reintenta en la siguiente ronda |
| Degradación | Sin plantilla configurada (WECHAT_SUBSCRIBE_TEMPLATE_REMINDER / _EXPIRY), degradación automática a solo notificación interna |

### 29. Respuesta del técnico a las evaluaciones (Ronda 18)

| Función | Descripción |
|------|------|
| Interfaz | POST /api/v1/technician/review/reply/{order_id} (middleware de identidad de técnico): evaluación inexistente/no propia unificada en 404; respuesta existente 422 (rechazo idempotente sin sobrescribir); respuesta vacía 422 |
| Tras responder | Notificación interna al usuario (type='review_reply', try/catch no bloqueante + Log) |
| Datos | appointment_order_review añade idempotentemente la columna replied_at (la columna reply ya existía al crear la tabla); en el panel, la lista/detalle de evaluaciones expone reply/replied_at a través de decorate()->toArray() |

### 30. Notificación de llegada de recarga (Ronda 18)

| Función | Descripción |
|------|------|
| Interfaz | La devolución de llamada de recarga de WeChat (número de pedido con prefijo R) handleRechargeNotify dentro de la transacción: tras WalletTxn escribe la notificación interna type='wallet_recharge', «Has recargado correctamente ¥X.XX» (importe en yuanes, number_format 2 dígitos) |
| Idempotencia | Reutiliza la idempotencia existente de la devolución de llamada (lockForUpdate de la fila de recarga + re-verificación de status, solo la primera vez pending→paid llega a la notificación); la notificación y el cambio de estado se confirman atómicamente en la misma transacción, sin brecha de crash; verificación de firma fallida / pedido inexistente / importe incorrecto no escriben notificación |
| Tolerancia a fallos | Escritura de notificación con try/catch, el fallo solo registra warning sin bloquear el flujo principal |

### 31. Transferencia de saldo (Ronda 19)

| Función | Descripción |
|------|------|
| Interfaz | POST /api/v1/wallet/transfer: decodificación hashid del receptor + existencia 404, transferirse a uno mismo 422, importe 0.01-1000 por operación 422 (comparación DECIMAL, prohibido float), saldo insuficiente 422, límite diario acumulado de 5000 yuanes 422 |
| Concurrencia/idempotencia | Bloqueo Redis NX wallet_transfer:{from} 30 s serializa al emisor; dentro de la transacción lockForUpdate de las filas de billetera por user_id ascendente de ambas partes (orden fijo contra deadlocks); tras el éxito SETNX de client_token 24 h contra envíos duplicados (las solicitudes fallidas no registran token y se pueden reintentar) |
| Ingreso | Deduce al emisor + suma al receptor + doble historial WalletTxn (transfer_out/transfer_in con instantánea balance_after) + registro de transferencia completed + notificación interna al receptor type='balance_received' (el fallo solo registra log) |
| Registros | GET /api/v1/wallet/transfers (direction=out/in paginado) + GET /transfers/{id} (404 si no es de ninguna de las dos partes) |

### 32. Transferencia de puntos (Ronda 19)

| Función | Descripción |
|------|------|
| Interfaz | POST /api/v1/user/points/transfer: receptor inexistente 404, transferirse a uno mismo 422, puntos 1-10000 422, saldo insuficiente por agregación SUM 422, límite diario acumulado de 10000 422 |
| Concurrencia/idempotencia | Bloqueo Redis NX points_transfer:{user} 30 s; dentro de la transacción lockForUpdate del último historial de ambas partes (user_id ascendente contra deadlocks por transferencias mutuas) + re-verificación dentro del bloqueo de saldo/límite/receptor |
| Norma del historial | Emisor type=consume source=points_transfer negativo (balance = instantánea anterior − esta, mismo criterio que points_offset/exchange); receptor type=earn source=points_transfer positivo con expires_at (PointsExpiryTimer puede caducarlo normalmente); registro de transferencia escrito dentro de la transacción, tras commit notificación interna al receptor type='points_received' |
| Registros | GET /api/v1/user/points/transfers (direction=sent/received paginado, apodo de la contraparte) |

### 33. Evaluación complementaria + completar ruta de envío (Ronda 19)

| Función | Descripción |
|------|------|
| Evaluación complementaria | POST /api/v1/order/review/{order_id}/append: evaluación inexistente/no propia unificada en 404, no completed 422, complemento repetido 422 (si append_content/append_at tiene algún valor no vacío se rechaza), contenido vacío 422; al tener éxito escribe append_content/append_images(JSON)/append_at + notificación interna al técnico type='review_append' |
| Envío de evaluación | Completa el registro de POST /api/v1/order/review/{order_id} (ReviewController::store no tenía ruta y era inalcanzable); de paso corrige el TypeError latente: findByOrderId recibía int violando la firma de string (comparar con la conversión (string) de append), el registro expone que la llamada devolvía 500 |
| Datos | appointment_order_review añade tres columnas append_content TEXT/append_images JSON/append_at DATETIME (migración idempotente); la respuesta expone los campos append |

### 34. Seguimiento logístico del extremo de usuario (Ronda 19)

| Función | Descripción |
|------|------|
| Interfaz | GET /api/v1/order/logistics/{id}: solo consultable por pedidos de producto propios (no propio/no producto/no enviado unificados en 404) |
| Datos | Lee el JSON de order.remark (shipping_company/tracking_no/shipped_at, escrito por admin MallOrderController::ship() al enviar); parseShippingInfo/parseReceiver doble análisis con respaldo al formato antiguo |
| Desidentificación | Teléfono del receptor con maskPhone (138\*\*\*\*5678), contra fugas |

### 35. Preferencias de notificaciones (Ronda 19)

| Función | Descripción |
|------|------|
| Datos | Tabla appointment_user_notify_setting (clave compuesta única user_id+type uk_user_type, fila ausente = activado por defecto); 5 tipos: service_reminder recordatorio de servicio / card_expiry recordatorio de caducidad (tarjetas + cupones bajo el mismo paraguas) / points_expiry caducidad de puntos / marketing marketing (reservado) / system sistema (no se puede desactivar, PUT lo fuerza a 1) |
| Interfaces | GET /api/v1/user/notify-settings devuelve los 5 interruptores completos; PUT upsert por lotes sin filas duplicadas |
| Control | NotificationReminderService::notifySettingEnabled enganchado a 3 procesos de temporizador (ServiceReminderTimer/ExpiryReminderTimer tarjetas+cupones/PointsExpiryTimer, los temporizadores insertan directamente en appointment_notification sin pasar por la ruta de escritura del servicio, por lo que cada uno añade el mismo tipo de control) + eventos de suscripción (sendSubscribeForOrderEvent/Notification, mapeo de escenarios PAY/REFUND/VERIFIED/RESCHEDULE→system siempre se envía, REMINDER→service_reminder, EXPIRY→card_expiry); cuando el tipo está desactivado, se omiten tanto las notificaciones internas como los mensajes de suscripción |

---

## II. Panel de administración (Web para PC)

Aplicación de una sola página en Flutter Web, 21 páginas en total: dashboard/usuarios/roles/configuración/logs/verificación/horarios/servicios/técnicos/pedidos/cupones/membresías/tarjetas por uso/anuncios/FAQ/retiros/evaluaciones/informes/centro personal/panel de trabajo de tienda.

### 1. Dashboard de inicio

- Estadísticas en tiempo real: número de usuarios / total de pedidos / número de técnicos / número de pedidos de servicio
- Gráfico de líneas: tendencia de volumen de pedidos / tendencia de importes / nuevos usuarios / actividad
- Navegación rápida: botones de módulos pendientes
- Mensajes internos: notificaciones de nuevos pedidos / notificaciones de reembolsos

### 2. Gestión de técnicos

- Lista de técnicos: búsqueda por UID/teléfono/nombre/ubicación/hora de registro
- Visualización de la lista: número/UID/teléfono/apodo/recomendador/estado/número de alumnos/rendimiento/estado de la cuenta/hora de registro/último inicio de sesión/ubicación
- Operaciones: exportar / modificar superior / ver subordinados / modificar contraseña y teléfono / gestión de horarios / configuración de ítems de servicio técnico / ver progreso de cursos
- Agregar: nombre/género/teléfono/DNI/foto del DNI
- Revisar solicitudes de incorporación

### 3. Gestión de usuarios

- Lista de miembros: nombre/teléfono/avatar/nivel/importe de consumo
- Búsqueda: UID/teléfono/apodo/hora de registro
- Operaciones: detalle / modificar superior / ver subordinados / modificar contraseña y teléfono / establecer nivel de membresía

### 4. Gestión de tiendas

- Lista de tiendas: habilitar/deshabilitar/eliminar
- Agregar tienda: nombre/dirección/coordenadas/teléfono/horario de atención/imágenes

### 5. Gestión de servicios

- Lista de servicios: búsqueda por nombre/categoría; número/nombre/tipo/descuento/precio mínimo/ventas/portada/orden/estado/hora
- Operaciones: agregar/modificar/eliminar/diseño de tarjetas
- Lista de productos: tipo/nombre/descuento/precio mínimo/ventas/stock/portada/orden/estado/hora

### 6. Gestión de la tienda

- Pedidos de la tienda: detalle/envío/logística/impresión
- Pedidos de posventa: ver/revisar/imprimir
- Gestión de evaluaciones: ver/revisar (show/hide)/eliminar (ReviewController index/show/audit/destroy)
- Historial de pagos
- Estadísticas de ventas

### 7. Gestión de pedidos

- Pedidos pendientes de uso: búsqueda multicriterio
- Operaciones: detalle/cancelación de la plataforma/confirmar completado

### 8. Actividades de cupones

- Lista: número/imagen/tipo/nombre/alta y baja/total/restantes/administrador/hora/fecha de finalización
- Operaciones: agregar/modificar/eliminar

### 9. Gestión financiera

- División de pedidos: búsqueda/detalle
- Retiros de técnicos: revisión de WithdrawalController; importes ≥500 con aprobación en dos niveles (gerente de tienda store_approved_at → finanzas finance_approved_at); máquina de estados pending→approved→completed (rejected/failed)
- Configuración de comisiones: modificar tasa de comisión / ciclo de liquidación / premios y penalizaciones / saldo
- Historial de ingresos y gastos
- Gestión de cuentas de retiro
- Configuración de límites de retiro

### 10. Gestión de contenido

- CRUD de banners
- Configuración de «Sobre nosotros»
- Revisión de momentos de redes sociales
- CRUD de preguntas frecuentes
- Gestión de comentarios
- CRUD de anuncios de la plataforma

### 11. Configuración

- Edición de acuerdos de la plataforma (acuerdo de usuario / acuerdo de privacidad / acuerdo de servicio)
- Configuración de comisión unificada de técnicos
- Plantillas de mensajes del sistema (incluye la configuración de plantillas de mensajes de suscripción del mini programa, degradación automática a notificación interna si no está configurada)
- Gestión de permisos de subcuentas (el gerente de tienda puede emitir cupones + gestionar horarios)

### 12. Funciones extendidas

- Diseño de tarjetas: combinación de proyecto+producto / tarifa manual / configuración de comisiones
- Monitorización del sistema: panel en tiempo real de CPU/memoria/disco/Redis/MySQL/colas
- Lista negra de IP: visualización de registros de ataques de security-php + bloqueo manual
- Respaldo de base de datos: respaldo/descarga/restauración desde la interfaz web
- Perfil del cliente: vista 360 / preferencias de consumo / marketing por segmentos
- Push por lotes: mensajes de plantilla / envío grupal segmentado
- Flujo de revisión de reembolsos: aprobación en dos niveles (gerente de tienda → finanzas)
- Niveles de técnico: evaluación automática junior/senior/expert
- Tareas programadas: cancelación automática / liquidación / gestión de caducidad
- Configuración de SMS: gestión multicanal Alibaba Cloud / Tencent Cloud
- Configuración de almacenamiento: local/OSS/COS/CDN
- Informes mejorados: campos personalizados / informes por correo programados
- Exportación de horarios: exportación Excel de registros de reservas / listas de asistencia
- Restricción de género de técnicos: control de género para servicios específicos
- Formación de técnicos: gestión de cursos / seguimiento del progreso de aprendizaje
- Cuentas de gerente de tienda: aislamiento de datos store_id + permisos exclusivos

### 13. Informes de datos (Ronda 7)

- ReportController con 3 extremos: estadísticas de pedidos / rendimiento de técnicos / distribución de tiendas
- Caché Redis svc:admin_report:{type}:{start}:{end}, TTL 300

### 14. Gestión de tarjetas de membresía (Ronda 10)

- Columna de nivel de membresía appointment_user.member_level (migración 000008)
- CRUD completo de MemberCardController (permisos 365-369): GET/POST/PUT/DELETE /admin/member-cards
- Página de gestión de definiciones de tarjetas de membresía en Flutter

### 15. Gestión de posventa (Ronda 14)

- Tabla appointment_order_aftersale (migración 000009): type=refund/exchange, status=pending/approved/rejected/completed
- AftersaleController: GET /admin/aftersales (paginación + filtro status/uid/order_no) + POST /admin/aftersales/{id}/review (approve/reject+remark)
- Página Flutter de gestión de posventa (lista + diálogo de revisión, permisos 370/371), diseño registrado

### 16. Panel de trabajo del gerente de tienda (Ronda 15)

- service /api/v1/store-manager: overview (pedidos de hoy/ingresos/en curso/número de técnicos/número de verificaciones) + orders (paginación + filtro de estado) + technicians (con horarios de hoy) + revenue (agregación de los últimos 7 días), requireStoreId() fuerza el aislamiento por store_id (403 sin tienda)
- admin StoreController::workbenchOverview (GET /admin/stores/workbench-overview?store_id=, criterio coherente con service) + filtro store_id en la lista de pedidos de AppointmentOrderController (decodificación hashid)
- Página Flutter del panel de trabajo de tienda: desplegable de tienda + filtro de estado + 5 tarjetas de resumen + DataTable de pedidos + paginación (permiso 372)

### 17. Productos de canje de puntos (Ronda 16)

- PointsExchangeGoodsController: GET/POST/PUT/DELETE /admin/points-exchange-goods + POST {id}/toggle-status (alta y baja) + GET {id}/exchanges (registros de canje, con teléfono + análisis del JSON de result)
- Migraciones 000012 (dos tablas) + 000013 (permisos 373-378) aplicadas

### 18. Registros de comisiones (Ronda 16)

- ReferralRewardController: GET /admin/referral-rewards (solo registros con rewarded_at no vacío, paginación + filtro por palabra clave de apodo/teléfono del recomendador o recomendado, codificación hashid, permiso 379)

### 19. Evaluación automática de nivel de técnico (Ronda 17)

- TierRatingService::evaluate(technicianId, allowDowngrade=false): estadísticas en tiempo real del número de pedidos completed de appointment_order + puntuación media de appointment_order_review (redondeo a 1 decimal) escritas de vuelta en profile.order_count/rating, coincidencia de mayor a menor según appointment_technician_tier_config (min_orders/min_rating), sin coincidencia cae al nivel mínimo
- Reglas de subida/bajada: solo sube, no baja (el nivel está vinculado a la tasa de comisión y al coeficiente de precios; una bajada automática afectaría los ingresos del técnico y provocaría disputas, el descenso lo maneja manualmente admin); solo con allowDowngrade=true (escenario de re-evaluación manual en el panel) se ejecuta la bajada, que también registra log + notificación
- Idempotencia: si el nivel correspondiente coincide con profile.tier_id, solo sincroniza estadísticas, sin log ni notificación
- Log: los cambios escriben en appointment_technician_tier_log (id/technician_id/old_tier_id/new_tier_id/reason/created_at) + notificación interna (type='tier')
- Puntos de activación: WorkController::complete / escritura de evaluaciones en ReviewController / determinación diferida al ver el perfil en ProfileController
- Extremo de administración: TechnicianTierController mantiene la capacidad de configuración manual; GET /admin/technician-tiers/logs vista paginada del log de cambios (join del nombre del técnico y de los nombres de los niveles antiguo y nuevo, ID con codificación hashid, permiso 380)

### 20. Visualización de respuestas a evaluaciones (Ronda 18)

- ReviewController añade reply(): GET /admin/reviews/{id}/reply detalle de la respuesta (decodeId → find → 404 → salida decorate, reply='' sin responder, reply/replied_at expuestos vía toArray)
- Ruta estática (antes de audit, definida antes de resource); semilla de permiso id 381 (slug 'get.admin/reviews/{id}/reply', tipo 3, asociación idempotente del rol superadmin)
- Punto de permiso: 381

### 21. Calendario de reservas (Ronda 20)

- CalendarController vistas mensual/diaria: GET /api/v1/calendar/technician/{id} (vista mensual) + /day (vista diaria)
- Fuente de datos: time_slots JSON de technician_schedule expandido en intervalos horarios por día de la semana, exclusión de los horarios ya reservados de appointment_order ese día (status ∈ pending/paid/confirmed/serving), salida de los intervalos restantes disponibles
- Uso: selección visual de horarios con horarios de tienda, el frontend se desplaza horizontalmente por días + selección por celda de hora

### 22. Nivel de crecimiento del usuario (Ronda 20)

- appointment_user_growth (historial) + appointment_growth_level (semillas de niveles: 5 niveles — bronce 0 / plata 100 / oro 500 / platino 2000 / diamante 5000)
- Puntos de ingreso de crecimiento: registro diario +10 (CheckInController); envío de evaluación +20 (ReviewController::store, las evaluaciones complementarias no ingresan); consumo floor(paid) 1 punto por cada yuan (WechatPayService::markOrderPaid, reutiliza la re-verificación de estado de pago existente, idempotente de forma natural, las devoluciones de llamada repetidas no ingresan de nuevo)
- Interfaces: GET /api/v1/growth (resumen del nivel actual: balance/level/diferencia al siguiente nivel); GET /api/v1/growth/records (historial paginado); GET /api/v1/growth/levels (lista pública de niveles, sin necesidad de inicio de sesión)
- Estrategia de fallo: cualquier punto de ingreso con try/catch y log, sin afectar el flujo principal

### 23. Factura electrónica (Ronda 20)

- appointment_invoice: uk_order_type(order_id,order_type) contra solicitudes duplicadas del mismo pedido (solicitud duplicada 422, con captura de respaldo de MySQL 1062); idx_user_created/idx_status
- Extremo de usuario: POST /api/v1/invoices (solicitud, importe/título aportados por el servidor desde el pedido, no manipulables); GET /api/v1/invoices (lista); GET /api/v1/invoices/{id} (detalle)
- Extremo de administración: InvoiceController issue (emitir: escribe invoice_no + status=issued + issued_at) / reject (rechazar: status=rejected + reject_reason), permiso 382 lista / 383 emisión / 384 rechazo
- Máquina de estados: pending → issued / rejected

### 24. Tickets de atención al cliente (Ronda 20)

- appointment_ticket: el usuario envía el ticket (title/content), el panel responde añadiendo (reply_content/replied_at), el usuario puede cerrarlo (closed_at)
- Extremo de usuario: POST /api/v1/tickets (enviar); GET /api/v1/tickets (lista); GET /api/v1/tickets/{id} (detalle, solo propio); POST /api/v1/tickets/{id}/close (cerrar)
- Extremo de administración: TicketController index (lista) / reply (respuesta), rutas estáticas definidas antes de resource para evitar la sombra de {id}; permiso 385 respuesta de tickets / 387 visualización de la lista de tickets
- Máquina de estados: open → replied (tras responder vuelve a open y puede responderse de nuevo) / closed

### 25. Distribución multinivel: comisión de nivel 2 (Ronda 20)

- ReferralRewardService::payLevel2Reward(paidAmount, orderId): tras el pago correcto del pedido, busca al recomendador del recomendador de nivel 1 (relación de recomendación de nivel 2) y emite paid×level2_rate (configuración del sistema referral.level2_rate, por defecto 0.02)
- Idempotencia: bloqueo de fila dentro de la transacción + clave única uk_order_referred(order_id, level2_user_id), las devoluciones de llamada de pago repetidas/la concurrencia no emiten de nuevo; el fallo con try/catch solo registra log sin afectar el flujo principal de pago
- Ingreso: WalletTxn type='referral_level2' (constante TYPE_REFERRAL_LEVEL2) + acumulación del saldo de la billetera
- Extremo de administración: ReferralLevel2Controller index registros paginados (permiso 386), join de los apodos de los usuarios de los dos niveles

### 26. Beneficios de nivel de crecimiento implementados (Ronda 21)

- El esqueleto JSON de GrowthLevel.benefits toma forma: semillas de migración con 5 niveles (bronce {"discount_rate":1.0,"points_multiplier":1.0}, plata 0.98/1.1, oro 0.95/1.2, platino 0.92/1.3, diamante 0.9/1.5)
- Descuento por nivel: OrderController::store applyGrowthDiscount() — solo pedidos estándar (promotion_id vacío, prohibida la superposición en compra grupal/flash); orden: importe a pagar tras cupón/tarjeta por uso × discount_rate; el importe del descuento se suma a discount_amount, el pedido añade la nota «descuento por nivel: plata 9,8 %, ahorro ¥2.00» trazable; protección de precio mínimo: pago real tras descuento ≥0.01 yuanes (≥100 en céntimos), si no llega el descuento se trunca a 0
- Multiplicador de puntos: WechatPayService::markOrderPaid cambia el crecimiento de floor(paid) a floor(paid × points_multiplier), el multiplicador se toma por el nivel en el momento del pago (se acumula antes del ingreso, este pedido no sube de nivel); el punto de enganche try/catch de R20 se conserva íntegro
- Reutilización de consultas: GrowthLevel::levelForGrowth() toma el nivel por crecimiento acumulado, reutilizable en pedido/pago; GET /api/v1/growth ya devuelve benefits y next_gap (implementado en R20, sin cambios)

### 27. Gestión de títulos de factura (Ronda 21)

- appointment_invoice_title (uk_user_title(user_id, title_type, invoice_title) contra duplicados + idx_user_default)
- Interfaces: POST /api/v1/invoice-titles (guardar, company requiere tax_no, duplicado 422); GET (lista, el predeterminado arriba); PUT /{id} (editar, solo propio); DELETE /{id} (eliminar, solo propio); POST /{id}/default (establecer predeterminado, transacción limpia las otras filas del mismo usuario)
- Regla de predeterminado: la primera fila guardada es predeterminada automáticamente; al eliminar el predeterminado se asigna automáticamente la más antigua
- Vinculación en la solicitud: InvoiceController::store acepta title_id opcional que resuelve el título e incorpora invoice_title/tax_no/title_type, sin title_id se conserva la ruta de relleno manual original; la lógica contra duplicados de uk_order_type no se toca

### 28. Satisfacción de tickets (Ronda 21)

- appointment_ticket añade rating TINYINT NULL + rated_at DATETIME NULL (migración 000303)
- Puntuación al cerrar: TicketController::close() soporta rating opcional 1-5 (validación de entero con filter_var, fuera de rango/no entero 422; si se proporciona escribe rating+rated_at, si no se mantiene NULL compatible con clientes antiguos; se conserva la regla de cerrar solo tickets open)
- Estadísticas del panel: GET /admin/tickets/satisfaction (ruta estática antes de resource para evitar la sombra de {id}) devuelve total/rated_count/unrated_count/average (1 decimal)/distribution (cantidad por estrella 1-5, las faltantes se rellenan con 0); permiso 388

### 29. Revisión de imágenes de evaluaciones (Ronda 21)

- ReviewAuditController de admin (nuevo, sin tocar ReviewController existente): GET /admin/review-audit lista de evaluaciones con imágenes (filtro JSON_LENGTH(images)>0 + leftJoin del apodo del usuario y del nombre del técnico + filtro de status + codificación hashid); POST /{id}/hide ocultar; POST /{id}/restore restaurar
- Máquina de estados: hide solo puede ocultar visible, restore solo puede restaurar hidden (validación bidireccional 422); los estados de OrderReview son un sistema entero (STATUS_HIDDEN=0/STATUS_VISIBLE=1)
- Cadena de efecto: la lista de evaluaciones de técnicos del extremo de usuario ya filtra por status → tras ocultar deja de ser visible automáticamente
- Permisos: 389 lista / 390 ocultar / 391 restaurar

### 30. Historial de navegación del usuario (Ronda 21)

- appointment_browse_history (único uk_user_item(user_id, item_id), la navegación repetida solo actualiza viewed_at sin insertar de nuevo; idx_user_viewed para ordenación)
- Enganche de registro: ServiceController::detail() registra tras el éxito (try/catch + Log::warning sin afectar el flujo principal; las rutas públicas no tienen JWT, si user_id está vacío se salta el anónimo)
- Interfaces: GET /api/v1/browse-history (join de appointment_service nombre/portada/precio/precio original, viewed_at descendente, per_page por defecto 15 máximo 50, item_id hashid); DELETE /{item_id} (solo propio, inválido/ajeno 404); DELETE / (vaciar solo lo propio)

### 31. Marketing de reducción de importe (Ronda 22)

- appointment_full_reduction_activity (threshold/reduction/title/status/start_at/end_at + idx_status_status_time)
- Superposición en el pedido: solo pedidos estándar (compra grupal/flash saltan), el umbral se juzga sobre el importe a pagar tras el descuento de cupón/tarjeta por uso, orden **cupón/tarjeta por uso → reducción de importe → descuento por nivel**; se toma la actividad de mayor reducción; el importe del descuento se suma a discount_amount + nota «reducción de importe: por X, descuento Y»; pago real tras reducción con mínimo de 0.01 yuanes (en céntimos)
- Extremo de usuario GET /api/v1/full-reduction-activities (público, activas ordenadas por importe de reducción descendente)
- admin FullReductionController: CRUD + toggle-status alta y baja (destroy con confirmPassword)
- Permisos: 396 lista / 397 crear / 398 editar / 399 alta y baja / 400 eliminar (un registro de permiso corresponde a un solo slug method.path, 5 rutas divididas en 5 registros)

### 32. Exportación ICS de mis reservas (Ronda 22)

- IcsController GET /api/v1/order/ics: exportación iCal (RFC5545) de los pedidos pending/paid/confirmed/serving de los últimos 90 días, solo propios
- VEVENT: UID=ID del pedido, DTSTAMP(UTC), TZID=Asia/Shanghai, duración por defecto 1 h, resumen «Reserva: nombre del servicio» (degradación a «Reserva» si falta), descripción del técnico/tienda/dirección (salta si falta), LOCATION; escape de texto (\, \; \\ \n) + plegado de líneas de 75 bytes
- Sin pedidos devuelve un calendario vacío válido (esqueleto `BEGIN:VCALENDAR`)

### 33. Asistencia de técnicos (Ronda 22)

- appointment_technician_attendance (date/check_in_at/check_out_at/status + índice único uk_technician_date contra registro duplicado concurrente)
- Extremo de técnico (TechnicianAuth): check-in duplicado el mismo día 422; check-out sin fichar/fichado de salida 422 + bloqueo de fila; >10:00 se marca retraso; GET lista del mes actual + días de asistencia/horas totales/horas medias (?month=YYYY-MM inválido 422)
- admin: GET /admin/attendance (filtro por fecha + nombre del técnico, join real_name, hashid) + /stats (estadísticas agrupadas por técnico)
- Permisos: 392 lista / 393 estadísticas

### 34. Servicio push de APP (Ronda 22)

- AppPushService (config group=push: enabled por defecto 0 / provider jpush/getui/placeholder): no habilitado, degradación silenciosa solo con log; habilitado, construye la estructura de plataforma/título/contenido/payload registrando Log + escribe appointment_push_log (status=sent); la integración con el SDK del proveedor queda en TODO (sin credenciales no se envía realmente)
- Integración en 5 eventos: pago correcto (WechatPayService::markOrderPaid), reembolso automático (autoRefundCancelledOrder), reembolso manual (doRefund/refundToBalance), compensación de reembolso (completeOneRefundCompensation), recordatorio de inicio del servicio (ServiceReminderTimer); todos con try/catch sin bloquear el flujo principal
- appointment_push_log (user_id/title/content/payload JSON/status/provider + idx_user)

### 35. Reparto oficial de WeChat (Ronda 22)

- WechatProfitSharingService (config group=profit_sharing: enabled/receiver_ratio, credenciales reutilizadas de wechat_pay): no habilitado, degradación disabled solo con log sin escribir en DB; habilitado → validación de importe (>0 y ≤paid, pago real × 0.7 por defecto) + idempotencia (el mismo pedido pending/success salta) → escribe registro pending → construye la estructura de «solicitud de reparto único» (sin credenciales no ejecuta HTTP, el contenido de la solicitud se registra en log y el registro permanece pending); doRequest privado aislado por HTTP y testeable
- WechatPayService::markOrderPaid engancha requestSharing tras el envío (fallo con try/catch solo log)
- appointment_profit_sharing (único uk_sharing_no + idx_order); admin GET /admin/profit-sharing lista (join del número de pedido/apodo del técnico, filtro por estado/número de pedido/nombre del técnico)
- Permiso: 394

### 36. Cumplimiento de privacidad (Ronda 22)

- GET /api/v1/privacy/data: exportación de datos (agrupada por personal/orders/points/wallet_txns/reviews/addresses/invoices; el log solo registra el teléfono desidentificado + la cantidad)
- Cierre completo: close-request (saldo distinto de 0 / pedidos sin completar / tickets en curso 422 → close_status=1) → close-cancel (1→0) → close-confirm (tras 72 h → close_status=2 + close_at + teléfono/apodo anonimizados a user{id} + status=0)
- appointment_user añade close_status/close_requested_at/close_at (migración ALTER idempotente); AuthController login/loginByCode devuelven 403 «cuenta cancelada» para close_status=2

### 37. Perfil de salud del usuario (Ronda 23)

- GET/PUT/DELETE /api/v1/health-profile: uno por persona (índice único uk_user), upsert solo actualiza los campos proporcionados
- allergies/health_notes con límite de 500 caracteres, preferred_technician_id valida existencia, respuesta con codificación hashid
- Migración 000504_user_health_profile; HealthProfileTest 6 tests

### 38. Contraseña de pago de la billetera (Ronda 23)

- POST /api/v1/wallet/pay-password/{set,verify,check}: validación de 6 dígitos, almacenamiento de password_hash + pay_password_set_at
- Si ya está establecida, modificar requiere la contraseña antigua 422; verify solo valida sin escribir; check devuelve si está establecida
- Migración 000502 (ALTER idempotente de dos columnas con INFORMATION_SCHEMA); WalletPayPasswordTest 7 tests

### 39. Horarios masivos de técnicos (Ronda 23)

- POST /api/v1/technician/schedule/batch: tramo de fechas ≤7 días + filtro weekdays, los días con horarios existentes se saltan
- La configuración individual también activa la detección de intervalos superpuestos (422 «conflicto con el horario existente: HH:MM-HH:MM»)
- ScheduleConflictTest 5 tests

### 40. Línea de tiempo de estados de pedido (Ronda 23)

- GET /api/v1/order/{id}/timeline: solo consultable por el propio usuario (ajeno 404), devuelto en orden descendente; el detalle de pedido de admin incorpora el array timeline
- OrderStatusLog::record() con 8 tipos de puntos de marcado estáticos: envío/pago/cancelación/confirmación/solicitud de reembolso/reembolso aprobado/inicio del servicio/servicio completado/cancelación automática por tiempo de espera/operación del panel (operator=admin)
- La devolución de llamada de pago markOrderPaid es el único punto de consumo; record() interno con try/catch + Log::warning que nunca bloquea el flujo principal
- Migración 000501_order_status_log; OrderTimelineTest 4 tests

### 41. Ruleta de la suerte de puntos (Ronda 23)

- GET /api/v1/wheel/prizes (oculta weight/stock); POST /api/v1/wheel/spin: Redis NX + bloqueo de fila contra concurrencia, sorteo ponderado con random_int, idempotencia client_token
- Ingreso de premios: puntos → historial earn (con fecha de caducidad, puede caducar normalmente con PointsExpiryTimer), saldo → lockForUpdate, cupón → pending de emisión manual, sin premio → lose
- GET /api/v1/wheel/records mis registros paginados; admin /admin/lucky-wheel CRUD + alta y baja + registros (permisos 401-406)
- Migraciones 000503 (appointment_lucky_wheel + appointment_wheel_record + semillas de demostración w60/w40) + 000505 (semillas de permisos); LuckyWheelTest admin 3 + service 6 tests

### 42. Modo invitado (Ronda 24)

- GET /api/v1/guest/{home,services,services/{id},stores,technicians}: entradas de navegación sin inicio de sesión que no requieren autenticación (interfaz pública)
- home agrega banners/anuncios/categorías de servicios/servicios populares, caché Redis svc:guest:home 300 s; services soporta filtro por categoría + ordenación newest/sales/price (page/per_page≤50); technicians solo aprobados, filtrable por service_id, puntuación descendente
- Cubierto por GuestControllerTest

### 43. Oferta flash (Ronda 24)

- appointment_seckill_activity (name/service_id/seckill_price/original_price/stock/start_at/end_at/status); vendidos = número de pedidos de appointment_order.seckill_id
- GET /api/v1/seckill (status=1 + ventana de tiempo), /{id} (state=not_started/ongoing/ended), POST /{id}/buy: idempotencia client_token (8-64 caracteres, SETNX 24 h) + Redis NX 30 s contra concurrencia + validación de la actividad (desde 2026-08-26 ya no se reserva stock de antemano)
- El pedido inyecta seckill_id reutilizando OrderController::store; el stock se reduce uniformemente con bloqueo de fila dentro de la transacción de store() (llamar directamente a /api/v1/order con seckill_id también descuenta stock), precio flash = seckill_price (según DB), sin superposición de cupones/puntos/tarjetas de membresía; la cancelación del pedido no repone el stock; el antiguo canal de promoción FLASH_SALE se ha eliminado (la rama promocional de store() solo conserva la compra grupal, PromotionController index filtra flash_sale, show/join 400), las ofertas flash solo pasan por este canal
- admin /admin/seckill CRUD + alta y baja + lista de pedidos (permisos 407-411, 420); migración 000606 semillas de permisos; SeckillTest service + admin

### 44. Gestión de versiones de APP y detección de actualizaciones (Ronda 24)

- appointment_app_version (platform/version_code/version_name/force_update/changelog/download_url/status)
- GET /api/v1/app/version?platform=android|ios detección pública de actualizaciones (platform inválido 422; de status=1 toma la más reciente; si no hay, objeto vacío)
- admin /admin/versions CRUD (permisos 416-419); migración 000609 semillas de permisos; VersionTest service + admin

### 45. Recompensa de cliente habitual (Ronda 24)

- ReturnCustomerRewardService: la segunda compra del usuario al mismo técnico dentro de 30 días (pedido completado) otorga al técnico un bono = pago real paid_amount × ratio (system_config group=return_customer, ratio por defecto 0.05, interruptor enabled, valores inválidos retroceden al predeterminado)
- Registrado en appointment_technician_earnings (type=return_customer, status=pending) reutilizando la cadena de liquidación de comisiones, el resumen de earnings del extremo de técnico lo incluye automáticamente; idempotencia por order_id+type; llamado dentro de la transacción con bloqueo de fila de WorkController::complete
- admin /admin/return-customer/config (GET/PUT) + /rewards (?keyword nombre del técnico/número de pedido/apodo del usuario) (permisos 412-414); migración 000607 semillas de permisos; ReturnCustomerRewardServiceTest

### 46. Exportación de horarios (Ronda 24)

- GET /admin/technician-schedule/export: CSV (UTF-8 BOM, Excel lo abre directamente), nombre de archivo schedules_{YmdHis}.csv
- start_date/end_date obligatorios (YYYY-MM-DD, inválido 422) y tramo ≤31 días; technician_id opcional (hashid, inválido 422)
- Columnas: ID del técnico / nombre del técnico / fecha / detalle de intervalos (time_slots JSON analizado como "09:00-12:00, 14:00-18:00")
- Permiso: 415; migración 000608 semillas de permisos; cubierto por ScheduleExportTest
