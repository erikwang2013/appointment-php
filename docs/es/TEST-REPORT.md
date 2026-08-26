# Informe del equipo de pruebas — Auditoría completa de cobertura de pruebas
> **Languages**: [中文](../TEST-REPORT.md) · [English](../en/TEST-REPORT.md) · [한국어](../ko/TEST-REPORT.md) · [Русский](../ru/TEST-REPORT.md) · [Deutsch](../de/TEST-REPORT.md) · [Français](../fr/TEST-REPORT.md) · [Português](../pt/TEST-REPORT.md) · [हिन्दी](../hi/TEST-REPORT.md) · [العربية](../ar/TEST-REPORT.md) · [বাংলা](../bn/TEST-REPORT.md) · [Bahasa Indonesia](../id/TEST-REPORT.md) · [日本語](../ja/TEST-REPORT.md)

> Fecha de generación: 2026-08-26　Versión: v1.3.8
> Equipo: deep-audit (tester-php / tester-api / tester-ui / tester-go / tester-rust)

## 1. Resumen ejecutivo

| Rol | Tarea | Resultado |
|------|------|------|
| Ingeniero de pruebas PHP | Pruebas unitarias/integración de todos los módulos | 70 pruebas existentes + las nuevas de esta ronda (ver §3) |
| Ingeniero de pruebas de API | Automatización de todas las interfaces | Las pruebas de integración de la capa de controladores son la forma de automatización de API de este proyecto (§4) |
| Ingeniero de automatización de UI | Pruebas de extremo a extremo de todas las páginas | El entorno no lo permite, conclusión en §5 |
| Ingeniero de pruebas GO | Pruebas unitarias | **Omitido: el proyecto no tiene código GO** (cero archivos .go) |
| Ingeniero de pruebas Rust | Pruebas unitarias | **Omitido: el proyecto no tiene código Rust** (cero archivos .rs) |

## 2. Stack tecnológico y forma de las pruebas

- Backend: PHP 8.3 webman, dos aplicaciones (service extremo de usuario / admin extremo del panel), comparten los modelos de service
- Framework de pruebas: PHPUnit + Eloquent, **modo de MySQL real + reversión de transacciones** (sin mocks), skip automático si la DB no está disponible
- Ejecución de pruebas: `cd service && php -d memory_limit=2G vendor/bin/phpunit`
- Automatización de API = pruebas de integración de la capa de controladores (construyen Request y llaman directamente a los métodos del controlador, con DB real y reversión de transacciones)

## 3. Cobertura de pruebas PHP

**Resultado completo: 558 tests / 2508 aserciones, 0 fallos 0 errores 0 skips** (2 deprecations de vendor existentes, 2 avisos de PHPUnit existentes, ninguno introducido en esta ronda; los 4 skips originales del umbral de retiro se han eliminado siendo inyectables vía config('withdraw.gate_day'), ejecutables todos los días)

### Nuevas de esta ronda (tester-php, 6 archivos 32 casos, todas con DB real + reversión de transacciones)

| Archivo de prueba | Casos | Cobertura |
|---------|------|------|
| CartControllerTest | 4 | Guardado normalizado (lista blanca/qty≥1/descarte de entradas sucias), no-array 400, carrito vacío, vaciado |
| PointControllerTest | 4 | Saldo = instantánea más reciente, meta de paginación, filtro type/source, lista vacía |
| AddressControllerTest | 7 | Agregar + predeterminada, obligatorio 400, exclusión mutua de predeterminadas, prioridad de predeterminada, exceso de permiso 404, cambiar predeterminada, eliminar + segundo 404 |
| FavoriteControllerTest | 7 | Favoritos de servicio/técnico, tipo inválido 400, duplicado 400, incremento/decremento de favorite_count, favorito huérfano, eliminar 404 |
| ReferralControllerTest | 5 | Generación de código de invitación + estadísticas, usuario 404, URL del código QR, lista de recomendados, detalle de comisiones |
| WithdrawControllerTest | 5 | Rechazo fuera del día de retiro (config inyectada a un día distinto de hoy), correcto, saldo insuficiente, <10 yuanes, sin cuenta (ejecutable todos los días, 0 skips) |

### Cobertura existente (70 archivos, sin cambios)

Más de 35 controladores cubiertos: Auth/máquina de estados de Order/reembolso/verificación/cambio de fecha/devolución de llamada de pago/oferta flash/compra grupal/cupones/tarjetas regalo/puntos/billetera/transferencia/tarjetas de membresía/crecimiento/comisiones/retiros/registro de asistencia/horarios/facturas/logística/push/mensajes de suscripción/colas, etc.

### Correcciones de esta ronda (encontradas por tester-php)

- 【bug】AddressController::show/update/destroy y FavoriteController::destroy no hacían la decodificación hashids, las llamadas con hashid daban 404.
  Corrección de raíz: `BaseController::decodeId` añade compatibilidad de paso directo para dígitos puros (si hashids no puede decodificar y ctype_digit, devuelve el valor tal cual),
  las 89 llamadas de todo el repositorio se benefician de forma unificada; se añadió decodeId en las entradas de 4 métodos de controladores. Regresión completa superada.
- 【bug】con min-length de hashids en 0, algunos ID numéricos desnudos (como 306) resultaban ser codificaciones hashids válidas de otros ID,
  y decodeId podía decodificar erróneamente a un ID incorrecto (404 ocasional en AddressControllerTest, reproducción aleatoria en múltiples ejecuciones completas).
  Corrección de raíz: en service/admin `config/hashids.php` la conexión main `length` 0→8,
  la codificación es siempre ≥8 caracteres, sin intersección de longitud con los ID numéricos desnudos (<8 o 16 dígitos), la ambigüedad se elimina del espacio de codificación.
  5 ejecuciones consecutivas de AddressControllerTest verifican estabilidad, regresión completa superada.
- El día de retiro codificado a 20 se cambia a `config('withdraw.gate_day')` inyectable (config/withdraw.php),
  los 4 casos originales de skip «solo el día 20 de cada mes» pasan a inyectar el día de retiro por reflexión, ejecutables todos los días, 0 skips.

## 4. Conclusión de la automatización de pruebas de API

- Este proyecto no tiene scripts de pruebas HTTP independientes; los 70 archivos de pruebas existentes son pruebas de integración de la capa de controladores (DB real),
  cubriendo más de 35 controladores, equivalente a la automatización de interfaces API
- La matriz de cobertura de pruebas está en §3
- **Prueba de humo HTTP ejecutada** (2026-08-26): el puerto 8787 estaba ocupado por otro proyecto, por lo que temporalmente se cambió el listener de
  `config/process.php` de service al 8791 para arrancar el servicio (32 workers webman + websocket + 4 temporizadores, todos [OK]),
  medido `GET /health` → `{"code":0,"message":"ok"}`, `GET /api/guest/services` → HTTP 200
  con JSON normal (ID con codificación hashids visibles), luego stop y restauración de la configuración, cero procesos residuales
- Se recomienda añadir en CI flutter build web → E2E con Playwright de las rutas clave del panel (ver §5)

## 5. Conclusión de extremo a extremo de UI

- Cliente: Flutter (apps/flutter extremo de usuario, admin/apps/flutter extremo del panel), mini programa WeChat (apps/wechat),
  HarmonyOS (apps/harmonyos), admin/apps/weixin
- Estado actual: el Flutter web de admin no tiene artefactos construidos (build/web no existe); no hay servicios de UI en ejecución en esta máquina;
  el mini programa WeChat/HarmonyOS no tiene canal de automatización de navegador
- **Conclusión: el entorno de automatización de extremo a extremo no está disponible**. Se recomienda añadir en CI: flutter build web → Playwright
  para las rutas clave del panel (inicio de sesión → lista de pedidos → verificación); el mini programa/HarmonyOS requiere pruebas manuales en dispositivo real/emulador
- Ya se ha proporcionado: admin/public/apidoc (página de documentación de interfaces)

## 6. GO / Rust

Escaneo recursivo de la raíz del proyecto: **0 archivos .go, 0 archivos .rs** (excluyendo vendor/node_modules/.git).
Las herramientas están instaladas (go / rustc disponibles) pero no hay objeto que probar. Si en el futuro se introduce un servicio GO/Rust, habrá que añadir pruebas adicionales.

## 7. Riesgos restantes (áreas valiosas no cubiertas)

- Flujo principal de order (cubierto a nivel de traits como OrderState/OrderRefundFlow)
- Devolución de llamada real de WeChat Pay (WechatPayService tiene pruebas unitarias, el sandbox real de WeChat no se ha probado en conjunto)
- Módulos con dependencias externas como impresión, LBS, códigos de verificación

(§3 pendiente de completar cuando tester-php devuelva los resultados)
