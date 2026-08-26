# Sistema de Reservas de Servicios — Guía de instalación
> **Languages**: [中文](../INSTALL.md) · [English](../en/INSTALL.md) · [한국어](../ko/INSTALL.md) · [Русский](../ru/INSTALL.md) · [Deutsch](../de/INSTALL.md) · [Français](../fr/INSTALL.md) · [Português](../pt/INSTALL.md) · [हिन्दी](../hi/INSTALL.md) · [العربية](../ar/INSTALL.md) · [বাংলা](../bn/INSTALL.md) · [Bahasa Indonesia](../id/INSTALL.md) · [日本語](../ja/INSTALL.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## Requisitos del entorno

| Componente | Versión mínima | Descripción |
|------|----------|------|
| PHP | 8.3+ | Extensiones: bcmath, curl, gd, mbstring, pdo, pdo_mysql, pcntl, redis |
| MySQL | 8.0+ | Prefijo de tablas `erik_`, juego de caracteres utf8mb4 |
| Redis | 6.0+ | Caché / límite de tráfico / sesión / almacenamiento de códigos de verificación |
| Composer | 2.x | Gestión de dependencias PHP |
| Elasticsearch | 8.x (opcional) | Búsqueda de texto completo, no afecta a las funciones principales si no se instala |

---

## I. Asistente de instalación web (recomendado)

Tras iniciar el panel de administración, acceda a `/install` desde el navegador para entrar en el asistente de instalación de un solo clic:

```bash
# 1. Instalar dependencias y arrancar
cd admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
php start.php start -d     # Puerto predeterminado 8787
```

Abra `http://localhost:8787/install` en el navegador y complete en 4 pasos:

1. **Comprobación del entorno** — detecta automáticamente la versión de PHP, las extensiones requeridas y los permisos de archivos
2. **Configuración de la base de datos** — rellene la información de conexión MySQL y haga clic en probar conexión
3. **Cuenta de administrador** — establezca el nombre de la aplicación, el nombre de usuario y la contraseña del administrador
4. **Ejecutar la instalación** — importación automática de SQL → creación del administrador → escritura de la configuración .env

Tras la instalación, inicie sesión con el nombre de usuario y la contraseña configurados. Una instalación correcta escribe el archivo `.install.lock`, y la interfaz `/install` hace una doble validación (bloqueo de archivo + isInstalled) contra reinstalaciones; `.install.lock` está añadido a `.gitignore`. Se recomienda eliminar la ruta `/install` de `admin/config/route.php` en producción.

---

## II. Instalación manual

### 2.1 Clonar el proyecto

```bash
git clone <repo-url> appointment-php
cd appointment-php
```

### 1.2 Instalar dependencias PHP

```bash
# Servicio de API de negocio
cd service/
cp .env.example .env
composer install --no-dev --optimize-autoloader

# Panel de administración
cd ../admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
```

### 1.3 Configurar las variables de entorno

Edite `service/.env` (API de negocio) y `admin/.env` (panel de administración), modificando las siguientes configuraciones clave:

```bash
# Conexión de base de datos
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=appointment          # service usa appointment, admin usa open_admin
DB_USERNAME=root
DB_PASSWORD=your-password

# Conexión Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# Clave JWT — en producción cámbiela obligatoriamente a una cadena aleatoria de 64 caracteres
JWT_SECRET_KEY=your-64-char-random-string

# Claves de cifrado — en producción cámbielas obligatoriamente
ENCRYPTION_KEY=your-32-byte-key
ENCRYPTABLE_KEY=your-32-byte-key

# Sal de Hashids — en producción cámbiela obligatoriamente
HASHIDS_SALT=your-random-salt

# Modo de depuración — en producción debe ser false
APP_DEBUG=false
```

> Explicación completa de las variables en `service/.env.example` y `admin/.env.example`.

### 1.4 Crear la base de datos e importar

```bash
# Crear la base de datos (service y admin pueden usar la misma base de datos o separadas)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS appointment DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS open_admin DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importar el script de instalación unificado (incluye todas las 54+ tablas + datos de permisos + datos de demostración)
mysql -u root -p appointment < docs/install.sql
mysql -u root -p open_admin < docs/install.sql
```

> `docs/install.sql` está combinado a partir de todos los archivos de migración, 2723 líneas en total, e incluye toda la estructura de tablas y datos semilla del panel de administración y del servicio de negocio. En una instalación nueva se ejecuta una vez; ejecutarlo repetidamente sobre una base de datos existente se interrumpirá por conflictos de claves/columnas; en escenarios de actualización, haga primero un respaldo o resuelva los conflictos manualmente.

### 1.5 Iniciar los servicios

```bash
# Iniciar el servicio de API de negocio (puerto predeterminado 8787)
cd service/
php start.php start -d

# Iniciar el panel de administración (puerto predeterminado 8787)
cd ../admin/
php start.php start -d
```

### 1.6 Verificar la instalación

```bash
# API de negocio
curl http://localhost:8787/api/common/config

# Comprobación de salud del panel de administración
curl http://localhost:8787/health

# Inicio de sesión en el panel de administración (cuenta y contraseña predeterminadas abajo)
curl -X POST http://localhost:8787/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### 1.7 Cuenta predeterminada

| Rol | Nombre de usuario | Contraseña | Descripción |
|------|--------|------|------|
| Superadministrador | `admin` | `admin123` | Tiene todos los permisos |

> Cambie la contraseña inmediatamente después del primer inicio de sesión.

---

## III. Despliegue con Docker

### 2.1 Servicio de API de negocio

```bash
cd service/
cp .env.docker .env
# Edite .env, modifique claves y contraseñas
docker-compose up -d
```

Orquestación: nginx (80/443) + app (8787) + mysql (3306) + redis (6379) + elasticsearch (9200)

### 2.2 Panel de administración

```bash
cd admin/
cp .env.docker .env
docker-compose up -d
```

### 2.3 Importar la base de datos en el entorno Docker

```bash
# Copie install.sql al contenedor y ejecútelo
docker cp docs/install.sql appointment-svc-mysql:/tmp/
docker exec -it appointment-svc-mysql mysql -u root -p appointment < /tmp/install.sql
```

---

## IV. Visión general de la estructura de la base de datos

| Dominio | Nº de tablas | Tablas principales |
|----|------|--------|
| Panel de administración | 8 | `erik_admin_user`, `erik_admin_role`, `erik_admin_permission`, `erik_operation_log` |
| Dominio de usuario | 4 | `erik_user`, `erik_user_address`, `erik_user_favorite`, `erik_user_device` |
| Dominio de técnico | 8 | `erik_technician_profile`, `erik_technician_schedule`, `erik_technician_earning`, `erik_technician_withdrawal`, `erik_technician_tier_config` |
| Dominio de servicios | 4 | `erik_service_category`, `erik_service`, `erik_service_package`, `erik_service_record` |
| Dominio de pedidos | 5 | `erik_order`, `erik_order_item`, `erik_order_payment`, `erik_order_refund`, `erik_order_review` |
| Dominio de marketing | 8 | `erik_coupon`, `erik_member_card`, `erik_gift_card`, `erik_user_points`, `erik_promotion` |
| Colas | 1 | `erik_queue_number` |
| Dominio de contenido | 5 | `erik_banner`, `erik_announcement`, `erik_faq`, `erik_feedback`, `erik_platform_agreement` |
| Dominio de comunidad | 3 | `erik_post`, `erik_comment`, `erik_moment` |
| Tiendas | 1 | `erik_store` |
| Formación | 2 | `erik_training_course`, `erik_training_progress` |
| Exámenes | 3 | `erik_exam`, `erik_exam_question`, `erik_exam_attempt` |
| Sistema | 3 | `erik_system_config`, `erik_notification`, `erik_signature` |
| **Total** | **55** | |

Todas las tablas usan el prefijo `erik_`, la clave primaria `id` es BIGINT no autoincremental (generada en la capa de aplicación por snowflake-php).

---

## V. Ejecutar las pruebas

```bash
# Pruebas de la API de negocio (21 tests)
cd service/
php vendor/bin/phpunit

# Pruebas del panel de administración (59 tests)
cd admin/
php vendor/bin/phpunit

# Análisis estático
php vendor/bin/phpstan analyse --level=5 app/

# Comprobación de estilo de código
php vendor/bin/php-cs-fixer fix --dry-run --diff
```

---

## VI. Configuración de servicios de terceros

En «Configuración del sistema» del panel de administración, rellene los siguientes grupos de configuración:

| Grupo de configuración | Uso | Obligatorio |
|--------|------|------|
| `wechat_pay` | Número de comercio de WeChat Pay / clave API / certificados | Necesario para la función de pago |
| `wechat_app` | AppID / AppSecret del mini programa WeChat | Necesario para el inicio de sesión con WeChat |
| `sms` | Proveedor de SMS (aliyun/tencent) + firma/plantilla | Necesario para los códigos de verificación SMS |
| `map_service` | Servicio de mapas (amap/tencent) + clave API | Necesario para la función LBS |
| `storage` | Almacenamiento de objetos (oss/cos) + AccessKey/Endpoint | Necesario para la carga de archivos |

---

## VII. Preguntas frecuentes

**P: Al arrancar da error `Class 'support\Model' not found`**
R: Ejecute `composer dump-autoload`.

**P: Fallo de conexión a la base de datos `SQLSTATE[HY000] [2002]`**
R: Compruebe la configuración de `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD` en `.env`.

**P: Error de codificación al importar SQL**
R: Use `mysql -u root -p --default-character-set=utf8mb4 < docs/install.sql`

**P: Fallo de conexión a Redis**
R: Confirme que Redis está iniciado y compruebe la configuración de `REDIS_HOST`/`REDIS_PORT`.

**P: Puerto ocupado**
R: Modifique el puerto de `listen` en `config/server.php`.

**P: El código de verificación no se muestra**
R: Confirme que la extensión GD está instalada y que la configuración de `POSTER_CAPTCHA_STORAGE` es correcta (local puede usar `file`, producción use `redis`).

**P: Elasticsearch no funciona**
R: ES es un componente opcional; confirme que `SCOUT_HOSTS` está bien configurado y que el servicio ES está iniciado.

---

## VIII. Estructura de directorios

```
appointment-php/
├── admin/                    # Panel de administración (webman v2)
│   ├── app/                  # Controladores / modelos / middleware
│   ├── config/               # Configuración de rutas / base de datos / middleware
│   ├── database/             # Scripts de respaldo (estructura de tablas y datos semilla unificados en docs/install.sql)
│   ├── tests/                # Pruebas PHPUnit (59 tests)
│   ├── .env.example          # Plantilla de variables de entorno
│   ├── .env.docker           # Variables de entorno Docker
│   ├── Dockerfile            # Archivo de construcción Docker
│   └── docker-compose.yml    # Orquestación Docker
├── service/                  # Servicio de API de negocio (webman v2)
│   ├── app/                  # Controladores / modelos / middleware
│   ├── config/               # Configuración de seguridad / rutas / base de datos
│   ├── seed.php              # Ejecutor de datos semilla de demostración (lee el segmento de datos de demostración de docs/install.sql)
│   ├── tests/                # Pruebas PHPUnit (21 tests)
│   ├── .env.example          # Plantilla de variables de entorno
│   ├── .env.docker           # Variables de entorno Docker
│   ├── Dockerfile            # Archivo de construcción Docker
│   └── docker-compose.yml    # Orquestación Docker
├── docs/                     # Documentación
│   ├── INSTALL.md            # Esta guía de instalación
│   ├── install.sql           # Script unificado de instalación de base de datos (2723 líneas)
│   ├── ARCHITECTURE.md       # Documento de diseño de arquitectura
│   ├── API.md                # Documento de referencia de API
│   └── AUDIT-REPORT.md       # Informe de revisión
└── .github/workflows/        # Canalizaciones CI/CD
    └── ci.yml
```
