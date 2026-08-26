# Diseño de arquitectura

## Arquitectura en capas

```
┌─────────────────────────────────────────┐
│          Capa de presentación            │
│  Mini programa WeChat / Flutter APP / Flutter Web │
├─────────────────────────────────────────┤
│              Capa de rutas               │
│  config/route.php — grupos de rutas + enlace de middleware │
├─────────────────────────────────────────┤
│           Capa de middleware             │
│  Cors → Security → RateLimit → Auth      │
│  → TechnicianAuth → OperationLog         │
├─────────────────────────────────────────┤
│          Capa de controladores           │
│  BaseController → controladores de negocio │
├─────────────────────────────────────────┤
│            Capa de servicios             │
│  common/ — Snowflake/Hashids/Encryption  │
├─────────────────────────────────────────┤
│              Capa de modelos             │
│  Eloquent ORM + Encryptable + Scout      │
├─────────────────────────────────────────┤
│               Capa de datos              │
│  MySQL / Redis / Elasticsearch           │
└─────────────────────────────────────────┘
```

## Diseño de middleware

### Cadena de ejecución

```
Cors → Security(detección de 31 tipos de ataques) → RateLimit → Auth(JWT+estado del usuario)
    → [TechnicianAuth(identidad de técnico)] → [AdminPermission(RBAC)] → [OperationLog(origen en 8 extremos)]
    → Controller
```

### Responsabilidades del middleware

| Middleware | Ámbito | Función |
|--------|--------|------|
| Cors | Global | Preflight OPTIONS + cabeceras de respuesta CORS |
| Security | Global | erikwang2013/security-php, detección de 31 tipos de ataques |
| RateLimit | Global | Ventana deslizante Redis + atomicidad Lua |
| Auth | Grupo de rutas | Análisis JWT + validación de existencia/estado del usuario |
| TechnicianAuth | Grupo de rutas | Consulta del archivo del técnico + validación del estado approved |
| AdminAuth | Grupo de rutas | Autenticación JWT del extremo admin + lista negra |
| AdminPermission | Grupo de rutas | Validación de permisos RBAC, caché Redis 60 s |
| OperationLog | Grupo de rutas | Registro de operaciones + detección automática del origen en 8 extremos |

### Estrategia de limitación de tráfico

| Interfaz | Límite |
|------|------|
| Predeterminado | 60 veces/minuto/IP |
| Inicio de sesión | 10 veces/minuto |
| Registro | 5 veces/minuto |
| Código de verificación | 1 vez/60 segundos/teléfono |

## Principios de diseño de la base de datos

### Estrategia de claves primarias

- Todas las claves primarias: BIGINT UNSIGNED NOT NULL, no autoincrementales
- Generadas en la capa de aplicación por `erikwang2013/snowflake-php`
- Model: `$incrementing = false`, `$keyType = 'string'`

### Prefijo de tablas

Prefijo unificado `erik_`, configurado en `config/database.php`. Los modelos escriben el nombre de tabla original y el ORM añade el prefijo automáticamente.

### Cifrado de campos sensibles

Con el trait `erikwang2013/encryptable`:

```php
use Erikwang2013\Encryptable\Encryptable;

class User extends Model
{
    use Encryptable;
    protected array $encryptable = [
        'phone', 'wx_openid', 'wx_unionid', 'real_name',
    ];
}
```

La longitud VARCHAR de los campos cifrados se fija en 500 (expansión de datos cifrados).

### Soft delete y marcas de tiempo

- Eloquent SoftDeletes: `deleted_at` DATETIME DEFAULT NULL
- Todas las tablas incluyen `created_at` + `updated_at`

## Mecanismo de cifrado/descifrado de ID de API

### Solicitud: decodeIds()

El frontend envía ID con codificación hashids → el controlador llama a `$this->decodeIds($request->all())` para decodificar.

### Respuesta: encodeIds()

Los ID del resultado de la consulta DB → `BaseController::success()` llama automáticamente a `encodeIds()` para codificar → devuelve cadenas hashids.

### Reglas

Procesamiento recursivo de los campos del array cuya clave sea `id` o termine en `_id`.

## Diseño de seguridad

### Defensa en profundidad

```
WAF → Cors → Security(detección de 31 tipos) → RateLimit → Auth(JWT+estado)
    → [validación de identidad] → [RBAC] → Controller(cifrado del Model) → Respuesta
```

### Seguridad de autenticación

- Contraseñas: hash bcrypt
- JWT: validez de 7 días + renovación + lista negra
- Bloqueo: 5 fallos → 15 minutos
- Concurrencia: máx. 3 tokens

### Seguridad de datos

- Capa API: erikwang2013/encryption
- Capa DB: trait erikwang2013/encryptable
- Registros: los datos sensibles no entran en los registros

### Seguridad de operaciones

- erikwang2013/poster-php: verificación antes de eliminar/revisar/retirar
- Middleware Security: detección de XSS/inyección SQL/CSRF/path traversal

## Integración con Elasticsearch

`erikwang2013/webman-scout` sincroniza automáticamente los modelos con ES:

```php
use Erikwang2013\WebmanScout\Searchable;

class Service extends Model
{
    use Searchable;
    public function searchableAs(): string { return 'erik_services'; }
}
```

## Exportación Excel/PDF

- Excel: PhpSpreadsheet, los campos sensibles se desidentifican automáticamente
- PDF: exportación visual del dashboard

## Detección del origen en 8 extremos

OperationLog analiza el User-Agent:

```
iPad → iPadOS / Mac → macOS / Windows → Windows
Linux → Linux / iPhone → ios / Android → android
HarmonyOS → harmonyOS / otros → web
```


## Pruebas TDD

| Proyecto | Número de pruebas | Estado |
|------|--------|------|
| admin/ | 60 | ✅ Superadas |
| service/ | 21 | ✅ Superadas |
| Total | 81 | ✅ |

Cobertura de pruebas: reglas de reembolso / estados de pedido / Hashids / sistema de colas / cifrado / código de verificación
