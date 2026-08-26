# Instrucciones de uso

## Inicio de sesión en el panel de administración

Administrador predeterminado: `admin` / `admin123` | Dirección: `http://localhost:8787`

> Cambie la contraseña inmediatamente después del primer inicio de sesión

---

## Flujo de configuración del sistema

### 1. Configuración básica
Configuración del sistema → completar el nombre de la plataforma / LOGO → Sobre nosotros → Teléfono de atención al cliente / sitio web / correo → Acuerdo de la plataforma → editar el acuerdo de usuario / acuerdo de privacidad

### 2. Tiendas y servicios
Gestión de tiendas → agregar tienda (nombre / dirección / coordenadas / teléfono / horario) → Categorías de servicios → crear categoría → Servicios → agregar servicio (nombre / precio / duración / especificaciones) → Gestión de productos → agregar producto / tarjeta / cupón

### 3. Incorporación de técnicos
Solicitud en la APP del técnico → revisión en «Gestión de técnicos» del panel de administración → tras la aprobación, el técnico configura sus horarios → puede recibir reservas

### 4. Configuración de operaciones
Banners → cargar + configurar el salto | Anuncios → publicar anuncios de noticias | Cupones → crear cupones para nuevos usuarios / cupones de descuento por importe | Tarjetas de membresía → mensual / VIP / por uso | Comisiones → configurar el porcentaje de comisión de técnicos

---

## Flujo del extremo de usuario

### Registro e inicio de sesión
Búsqueda / escaneo en WeChat → registro con teléfono + código de verificación (código de recomendación opcional) → o inicio de sesión con un clic de WeChat → los nuevos usuarios reciben un cupón automáticamente

### Reserva de servicios
Navegar por las categorías en la página de inicio → hacer clic en el servicio para ver el detalle → ver precio / evaluaciones → reservar ahora → elegir tienda / técnico / hora / cupón → confirmar pedido → pago con WeChat → pago correcto

### Gestión de pedidos
Pendiente de pago: completar el pago | Pagado: esperando el servicio | Completado: evaluar (estrellas + texto + imágenes) | Reembolso: cálculo automático del porcentaje de reembolso

### Centro personal
Pedidos / cupones / tarjetas de membresía / puntos / favoritos | Centro de promoción: obtener código QR de promoción para ganar puntos | Comentarios: texto + imágenes

---

## Operaciones del extremo de técnico

### Cambio de identidad
«Mi» en la APP → cambiar a técnico → panel de trabajo

### Trabajo diario
- **Configuración de horarios**: definir los intervalos de reserva por día
- **Ver reservas**: lista de pedidos reservados de hoy
- **Verificación por escaneo**: escanear el código QR del usuario para verificar usos
- **Archivo de cliente**: completar el archivo del cliente dentro de las 24 h de cada pedido (sin comisión si se excede el tiempo)
- **Registro de asistencia**: registro de entrada / salida / fotos de higiene

### Ganancias
Ver ingresos de hoy / fondos en tránsito / saldo → retiro el día 20 de cada mes → T+1 llega a la billetera de WeChat

### Crecimiento
Estudiar cursos de formación → participar en evaluaciones → al aprobar se sube el nivel de técnico (afecta la tasa de comisión)

---

## Interfaces API

La documentación de interfaces se mantiene de forma independiente, ver [API.md](API.md) (API de negocio + API del panel de administración, con ejemplos de solicitud/respuesta y extremo OpenAPI).

---

## WebSocket

```
ws://localhost:8282
```

Autenticación: `{"type":"auth","token":"<JWT>"}`

Eventos: `order_update` / `technician_online` / `system_notice`

---

## Configuración de push

iOS (APNs): configurar apns_key_id/team_id/bundle_id/archivo .p8  
Android (FCM): configurar fcm_server_key

Registro de dispositivo en la APP: `POST /api/user/device/register {"platform":"ios","device_token":"..."}`

---

## Tareas programadas

| Tarea | Frecuencia | Descripción |
|------|------|------|
| Cancelación automática de pedidos | 30 segundos | Pendiente de pago más de 30 minutos |
| Liquidación automática de ganancias | 3 días | Liquidar comisiones de pedidos completados |
| Caducidad de cupones | Diario | Marcar expired |
| Caducidad de tarjetas de membresía | Diario | Marcar expired |

---

## Reglas de reembolso

| Condición | Proporción |
|------|------|
| Dentro de los 15 minutos del pedido o >6 h antes del inicio | 100% |
| ≤6 h antes del inicio | 90% |
| Iniciado sin confirmar | 80% |
| Después de confirmar el inicio | 0% |

---

## Monitorización

```bash
GET /health          # Comprobación de salud
GET /metrics         # Métricas Prometheus
GET /.well-known/security.txt  # Contacto de seguridad
```

## Pruebas

```bash
admin/ && phpunit --bootstrap tests/bootstrap.php     # 60 tests
service/ && phpunit --configuration phpunit.xml        # 21 tests
```
