# Diagrama de arquitectura de seguridad
> **Languages**: [中文](../../diagrams/SECURITY-ARCHITECTURE.md) · [English](../../en/diagrams/SECURITY-ARCHITECTURE.md) · [한국어](../../ko/diagrams/SECURITY-ARCHITECTURE.md) · [Русский](../../ru/diagrams/SECURITY-ARCHITECTURE.md) · [Deutsch](../../de/diagrams/SECURITY-ARCHITECTURE.md) · [Français](../../fr/diagrams/SECURITY-ARCHITECTURE.md) · [Português](../../pt/diagrams/SECURITY-ARCHITECTURE.md) · [हिन्दी](../../hi/diagrams/SECURITY-ARCHITECTURE.md) · [العربية](../../ar/diagrams/SECURITY-ARCHITECTURE.md) · [বাংলা](../../bn/diagrams/SECURITY-ARCHITECTURE.md) · [Bahasa Indonesia](../../id/diagrams/SECURITY-ARCHITECTURE.md) · [日本語](../../ja/diagrams/SECURITY-ARCHITECTURE.md)

## 1. Sistema de defensa en profundidad

```mermaid
graph TB
    subgraph 边界防护["Primera capa: protección de frontera"]
        WAF["WAF / Nginx<br/>Cabeceras de seguridad de respuesta<br/>Protección de archivos sensibles<br/>TLS 1.3"]
    end

    subgraph 接入防护["Segunda capa: protección de acceso"]
        CORS["Middleware Cors<br/>Lista blanca CORS_ALLOW_ORIGIN<br/>* con eco · sin configurar solo mismo origen<br/>7 cabeceras de seguridad<br/>Preflight OPTIONS"]
    end

    subgraph 攻击检测["Tercera capa: detección de ataques"]
        SEC["Middleware Security<br/>erikwang2013/security-php<br/>31 detectores de ataques<br/>XSS / inyección SQL / CSRF<br/>Path traversal / inclusión de archivos<br/>Detección de Origin CSRF (block)"]
        BLOCK["Bloqueo automático<br/>5 ataques/60 s<br/>→ lista negra de IP 15 min"]
    end

    subgraph 流量控制["Cuarta capa: control de tráfico"]
        RL["Middleware RateLimit<br/>Ventana deslizante Redis + atomicidad Lua<br/>Por defecto: 60 veces/min/IP<br/>Inicio de sesión: 10 veces/min<br/>Registro: 5 veces/min<br/>Verificación: 1 vez/60 s/teléfono"]
    end

    subgraph 身份认证["Quinta capa: autenticación de identidad"]
        AUTH["Middleware Auth<br/>JWT Bearer Token (7 días)<br/>JWT_SECRET_KEY obligatoria<br/>se niega el arranque si falta/es valor por defecto público<br/>Hash bcrypt de contraseñas<br/>Renovación de Token + lista negra<br/>Bloqueo de inicio de sesión: 5 fallos → 15 min<br/>Límite de concurrencia: máximo 3 Tokens"]
        TECH_AUTH["TechnicianAuth<br/>Comprobación del expediente del técnico<br/>Verificación del estado approved"]
        ADMIN_AUTH["AdminAuth<br/>Autenticación JWT del extremo admin<br/>Lista negra de Tokens"]
    end

    subgraph 权限控制["Sexta capa: control de permisos"]
        RBAC["AdminPermission<br/>Verificación de permisos de roles RBAC<br/>Caché Redis 60 s<br/>Usuario → rol → permiso"]
        POSTER["Verificación Poster<br/>erikwang2013/poster-php<br/>Eliminar/auditar/retirar<br/>Verificación aleatoria en operaciones sensibles"]
    end

    subgraph 数据安全["Séptima capa: seguridad de datos"]
        ENC_API["Cifrado en la capa API<br/>erikwang2013/encryption<br/>Cifrado/descifrado de campos sensibles"]
        ENC_DB["Cifrado en la capa DB<br/>erikwang2013/encryptable<br/>Cifrado/descifrado automático con el trait del Model<br/>Solo se cifran real_name/id_card, etc.<br/>phone/wx_openid deben almacenarse en claro<br/>(el inicio de sesión y la comprobación de duplicados dependen de consultas en claro)"]
        HASHID["Cifrado/descifrado de ID<br/>erikwang2013/hashids<br/>Oculta los ID reales al exterior<br/>Codificación/decodificación recursiva"]
        SLOG["Registros de seguridad<br/>M3 desinfecta las anomalías de forma unificada<br/>Mensaje genérico + Log::error<br/>Los datos sensibles no entran en los registros<br/>OperationLog con 8 orígenes"]
    end

    subgraph 管理端防护["Octava capa: protección del extremo de gestión"]
        EXCEL["Protección de exportación<br/>safeCellValue()<br/>= + - @ / Tab/CR iniciales<br/>prefijo ' para escapar e impedir inyección de fórmulas"]
        UPLOAD["Validación de subidas<br/>finfo magic bytes<br/>MIME y extensión no coinciden<br/>→ 422 rechazado"]
        INSTALL["Bloqueo de instalación<br/>Ya instalado (installed=1<br/>o existe administrador)<br/>→ 404 desactiva el asistente de instalación"]
    end

    请求["HTTP Request"] --> WAF
    WAF --> CORS
    CORS --> SEC
    SEC -->|"Supera"| RL
    SEC -->|"Ataque detectado"| BLOCK
    BLOCK -.->|"Rechazo"| 拒绝["HTTP 403/429<br/>Registro del registro de ataques"]
    RL -->|"Supera"| AUTH
    RL -->|"Límite superado"| 限流拒绝["HTTP 429<br/>Retry-After"]
    AUTH --> TECH_AUTH
    AUTH --> ADMIN_AUTH
    TECH_AUTH --> RBAC
    ADMIN_AUTH --> RBAC
    RBAC --> POSTER
    POSTER --> ENC_API
    ENC_API --> ENC_DB
    ENC_DB --> HASHID
    HASHID --> SLOG
    SLOG --> EXCEL
    EXCEL --> UPLOAD
    UPLOAD --> INSTALL
    INSTALL --> 响应["HTTP Response<br/>Datos cifrados + codificados"]

    classDef layer1 fill:#e3f2fd,stroke:#1565c0,stroke-width:2px,color:#01579b
    classDef layer2 fill:#bbdefb,stroke:#1976d2,stroke-width:2px,color:#01579b
    classDef layer3 fill:#ffcdd2,stroke:#c62828,stroke-width:2px,color:#b71c1c
    classDef layer4 fill:#fff9c4,stroke:#f9a825,stroke-width:2px,color:#f57f17
    classDef layer5 fill:#c8e6c9,stroke:#2e7d32,stroke-width:2px,color:#1b5e20
    classDef layer6 fill:#e1bee7,stroke:#7b1fa2,stroke-width:2px,color:#4a148c
    classDef layer7 fill:#d7ccc8,stroke:#5d4037,stroke-width:2px,color:#3e2723
    classDef layer8 fill:#cfd8dc,stroke:#37474f,stroke-width:2px,color:#263238
    classDef reject fill:#ff5252,stroke:#b71c1c,stroke-width:2px,color:#fff

    class WAF layer1
    class CORS layer2
    class SEC,BLOCK layer3
    class RL layer4
    class AUTH,TECH_AUTH,ADMIN_AUTH layer5
    class RBAC,POSTER layer6
    class ENC_API,ENC_DB,HASHID,SLOG layer7
    class EXCEL,UPLOAD,INSTALL layer8
    class 拒绝,限流拒绝 reject
```

## 2. Matriz de componentes de seguridad

```mermaid
graph LR
    subgraph 组件["Componentes de seguridad"]
        C1["security-php<br/>━━━━━━━━<br/>Detección de 31 ataques<br/>XSS/inyección SQL/CSRF<br/>Path traversal/inclusión de archivos<br/>Detección de Origin CSRF"]
        C2["encryption<br/>━━━━━━━━<br/>AES-256-CBC<br/>Cifrado/descifrado en la capa API<br/>Admite rotación de claves"]
        C3["encryptable<br/>━━━━━━━━<br/>Cifrado/descifrado automático de campos DB<br/>Solo real_name/id_card, etc.<br/>phone/wx_openid en claro<br/>VARCHAR(500) compatible con la expansión del cifrado"]
        C4["hashids<br/>━━━━━━━━<br/>Codificación/decodificación de ID<br/>Procesamiento recursivo de relaciones<br/>Oculta los ID reales al exterior"]
        C5["jwt-webman<br/>━━━━━━━━<br/>Bearer Token<br/>JWT_SECRET_KEY obligatoria<br/>se niega el arranque si falta/es valor por defecto<br/>7 días + renovación + lista negra<br/>concurrencia ≤3"]
        C6["poster-php<br/>━━━━━━━━<br/>Verificación aleatoria antes de la operación<br/>Eliminar/auditar/retirar<br/>Evita operaciones erróneas"]
        C7["snowflake-php<br/>━━━━━━━━<br/>ID distribuido BIGINT<br/>No autoincremental, evita la enumeración<br/>Único globalmente"]
    end

    subgraph 攻击面["Superficies de ataque protegidas"]
        A1["Ataques de inyección<br/>SQL/comandos/LDAP"]
        A2["XSS/CSRF<br/>Scripting entre sitios/falsificación de peticiones"]
        A3["Path traversal<br/>Atravesamiento de directorios/inclusión de archivos"]
        A4["Fuerza bruta<br/>Fuerza bruta de inicio de sesión/códigos de verificación"]
        A5["Filtración de datos<br/>Enumeración de ID/campos sensibles"]
        A6["Operaciones no autorizadas<br/>Escalada horizontal/vertical"]
        A7["Abuso de concurrencia<br/>Token en exceso/golpeo de interfaces"]
    end

    C1 -.->|"Defensa"| A1
    C1 -.->|"Defensa"| A2
    C1 -.->|"Defensa"| A3
    C2 -.->|"Defensa"| A5
    C3 -.->|"Defensa"| A5
    C4 -.->|"Defensa"| A5
    C5 -.->|"Defensa"| A4
    C5 -.->|"Defensa"| A7
    C6 -.->|"Defensa"| A6
    C7 -.->|"Defensa"| A5

    classDef comp fill:#e8eaf6,stroke:#3949ab,stroke-width:2px,color:#1a237e
    classDef attack fill:#ffebee,stroke:#c62828,stroke-width:1px,color:#b71c1c

    class C1,C2,C3,C4,C5,C6,C7 comp
    class A1,A2,A3,A4,A5,A6,A7 attack
```

## 3. Flujo de autenticación y autorización

```mermaid
flowchart TD
    A["Petición del cliente"] --> B{"¿Hay Token?"}
    B -->|"No"| C["Devolver 401<br/>Indicar inicio de sesión"]
    B -->|"Sí"| D["Analizar el Token JWT"]
    D --> E{"¿Token válido?"}
    E -->|"Caducado"| F{"¿Refresh Token?"}
    F -->|"Sí"| G["Renovar el Token<br/>El Token antiguo entra en la lista negra"]
    F -->|"No"| C
    G --> H["Devolver el nuevo Token"]
    E -->|"Válido"| I{"Comprobación de la lista negra"}
    I -->|"En la lista"| C
    I -->|"Normal"| J["Consultar la información del usuario"]
    J --> K{"¿El usuario existe y está habilitado?"}
    K -->|"No"| L["Devolver 403<br/>Cuenta deshabilitada"]
    K -->|"Sí"| M{"¿Número de fallos de inicio de sesión?"}
    M -->|"≥5 veces/15 min"| N["Devolver 429<br/>Cuenta bloqueada"]
    M -->|"Normal"| O{"¿Número de Tokens concurrentes?"}
    O -->|">3"| P["Los Tokens antiguos se invalidan automáticamente<br/>y entran en la lista negra"]
    O -->|"≤3"| Q{"¿Se requiere identidad de técnico?"}
    Q -->|"Sí"| R{"¿Expediente del técnico approved?"}
    R -->|"No"| S["Devolver 403<br/>No es técnico o está en auditoría"]
    R -->|"Sí"| T{"¿Se requiere RBAC?"}
    Q -->|"No"| T
    T -->|"Sí"| U{"Verificación de permisos"}
    U -->|"Sin permiso"| V["Devolver 403<br/>Sin permiso para la operación"]
    U -->|"Con permiso"| W["Ejecutar la lógica de negocio"]
    T -->|"No"| W
    W --> X["Devolver la respuesta<br/>ID codificados<br/>Datos sensibles cifrados"]

    style C fill:#ffcdd2,stroke:#c62828,color:#333
    style L fill:#ffcdd2,stroke:#c62828,color:#333
    style N fill:#ffcdd2,stroke:#c62828,color:#333
    style S fill:#ffcdd2,stroke:#c62828,color:#333
    style V fill:#ffcdd2,stroke:#c62828,color:#333
    style W fill:#c8e6c9,stroke:#2e7d32,color:#333
    style X fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 4. Flujo de seguridad de datos

```mermaid
flowchart LR
    subgraph 输入["Entrada del usuario"]
        I1["Teléfono en claro"]
        I2["DNI en claro"]
        I3["OpenID en claro"]
        I4["Nombre en claro"]
    end

    subgraph API加密["Capa API (encryption)"]
        E1["encrypt(id_card)<br/>→ ciphertext"]
        E2["encrypt(real_name)<br/>→ ciphertext"]
    end

    subgraph DB存储["Almacenamiento en la capa DB"]
        D1["erik_user.phone<br/>almacenado en claro<br/>el inicio de sesión y la comprobación de duplicados dependen de consultas en claro"]
        D2["erik_technician_profile<br/>.id_card VARCHAR(500)<br/>cifrado con encryptable"]
        D3["erik_user.wx_openid<br/>almacenado en claro"]
        D4["erik_user.real_name<br/>cifrado con encryptable"]
    end

    subgraph ID处理["Tratamiento de ID (hashids + snowflake)"]
        H1["Generación Snowflake<br/>1860000000000001"]
        H2["Codificación Hashids<br/>→ 'Kx9mP2vR'"]
        H3["Respuesta de la API<br/>id: 'Kx9mP2vR'"]
    end

    subgraph 输出["Salida al exterior"]
        O1["ID codificados<br/>no enumerables"]
        O2["Campos sensibles desidentificados<br/>los registros no contienen texto en claro"]
        O3["Cabeceras de respuesta con políticas de seguridad<br/>CSP/CORS/HSTS"]
    end

    I1 --> D1
    I2 --> E1 --> D2
    I3 --> D3
    I4 --> E2 --> D4
    D1 --> H1 --> H2 --> H3
    H3 --> O1
    D1 --> O2
    O1 --> O3

    classDef input fill:#e3f2fd,stroke:#1565c0,color:#333
    classDef encrypt fill:#fff3e0,stroke:#f57c00,color:#333
    classDef db fill:#fce4ec,stroke:#c62828,color:#333
    classDef id fill:#e8f5e9,stroke:#2e7d32,color:#333
    classDef output fill:#f3e5f5,stroke:#7b1fa2,color:#333

    class I1,I2,I3,I4 input
    class E1,E2 encrypt
    class D1,D2,D3,D4 db
    class H1,H2,H3 id
    class O1,O2,O3 output
```
