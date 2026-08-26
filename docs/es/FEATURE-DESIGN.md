# Diseño de funciones
> **Languages**: [中文](../FEATURE-DESIGN.md) · [English](../en/FEATURE-DESIGN.md) · [한국어](../ko/FEATURE-DESIGN.md) · [Русский](../ru/FEATURE-DESIGN.md) · [Deutsch](../de/FEATURE-DESIGN.md) · [Français](../fr/FEATURE-DESIGN.md) · [Português](../pt/FEATURE-DESIGN.md) · [हिन्दी](../hi/FEATURE-DESIGN.md) · [العربية](../ar/FEATURE-DESIGN.md) · [বাংলা](../bn/FEATURE-DESIGN.md) · [Bahasa Indonesia](../id/FEATURE-DESIGN.md) · [日本語](../ja/FEATURE-DESIGN.md)

## Flujo de compra

### Flujo de reserva de servicios (pedido directo)

```
Detalle del servicio → confirmar pedido (tienda/técnico/hora/cupón/notas) → leer el acuerdo de servicio
    → enviar pedido → bloquear técnico en Redis 3 min → pago WeChat → pago correcto
    → notificar al usuario + técnico → llega la hora del servicio → el técnico confirma el inicio
    → servicio completado → verificación con código QR → evaluación del usuario → pedido completado
```

### Flujo de compra de productos (modo carrito)

```
Lista de productos → agregar al carrito → confirmar carrito (cambiar cantidad/eliminar)
    → enviar pedido → pagar → envío → recepción → completado
```

## Máquina de estados del pedido

```
pending(pendiente de pago) → paid(pagado) → confirmed(confirmado)
    → serving(en servicio) → completed(completado) → reviewed(evaluado)

pending → cancelled(cancelado)
paid → cancelled
paid → refunding(en reembolso) → refunded(reembolsado)
```

## Mecanismo de bloqueo del técnico

El usuario entra en la página de confirmación de pedido → Redis SETNX bloquea 3 minutos. Se libera al salir/expirar.

```
SETNX lock:tech:123:2026-05-26-14:00 user_456 EX 180
 → correcto: continuar con el pedido
 → fallo: el técnico ya está bloqueado
```

## Reglas de reembolso

| Condición | Proporción de reembolso |
|------|----------|
| Dentro de los 15 minutos del pedido o >6 horas antes del inicio | 100% |
| ≤6 horas antes del inicio | 90% |
| Iniciado pero sin confirmar el servicio | 80% |
| Después de confirmar el inicio del servicio | 0% (sin reembolso) |

## Reglas de descuento

| Tipo | Condición | Descuento | Acumulación |
|------|------|------|------|
| Descuento de horas valle | 10-12 h / 17-18 h / después de las 21:00 | 9% de descuento | Acumulable con cupones |
| Reserva anticipada | Más de 30 minutos antes | 5% de descuento | No acumulable con cupones |

## Retiro de técnicos

- Retiro disponible el día 20 de cada mes, T+1 llega a la billetera de WeChat
- Verificado sin liquidar: confirmación automática en 3 días
- Importe mínimo / importe retenido / múltiplo de cien configurados en el panel

### Flujo de retiro

```
Solicitar retiro → verificación poster-php → revisión del panel (aprobar/rechazar)
    → completar retiro → llega a la billetera de WeChat → generar historial financiero
```

### Tipos de ganancias

| Tipo | Descripción |
|------|------|
| commission | Comisión de servicio |
| bonus | Bono (cliente habitual / asistencia) |
| penalty | Multa (sin archivo del cliente en 24 h) |
| subsidy | Subsidio |
| attendance | Recompensa de asistencia completa |

### Recompensa de cliente habitual

Segunda compra al mismo técnico dentro de 30 días → registrar bono

### Archivo del cliente

El archivo debe escribirse dentro de las 24 h tras completar cada pedido; si no, no hay comisión

## Diseño de puntos

- Obtención por consumo, obtención por recomendación (configurable en el panel)
- Canje 1:100 por tarjeta regalo (configurable en el panel)
- La tabla de historial de puntos registra cada cambio + saldo

## Diseño de tarjetas de membresía

| Tipo | Facturación | Descripción |
|------|------|------|
| month | Por día | Tarjeta mensual normal |
| vip | Por día | Tarjeta anual VIP |
| times | Por uso | Tarjeta por uso, combinación libre de servicios |

Tarjeta por uso: al comprar se elige la combinación de servicios (A×3+B×5), cada uso consume 1 uso del servicio correspondiente. Agotada → used_up, caducada → expired.

## Cambio de identidad

```
Cliente → cambiar a técnico → comprobar si el archivo del técnico está approved
    → sí: active_role=technician, cambio de página
    → no: guiar hacia la solicitud de incorporación

Técnico → cambiar a cliente → active_role=customer, cambio de página
```

## Recompensa de nuevos usuarios

```
Registro → generar código de recomendación → con recomendador → crear registro de promoción
    → enviar automáticamente cupón de nuevo usuario (Fase 5)
    → el recomendador obtiene puntos (tras el primer pedido del recomendado)
```

## Diseño de pago (reserva para WeChat Pay)

```
POST /api/order/pay/{id}
    → crear registro de pago → llamar al pedido unificado de WeChat (WechatPayService reservado)
    → devolver parámetros de pago → el frontend inicia el pago
    → devolución de llamada de WeChat /api/wechat/notify → verificar firma → actualizar estado a paid
    → notificar al usuario + técnico
```
