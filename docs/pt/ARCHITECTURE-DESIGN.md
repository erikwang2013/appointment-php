> Tradução em português · Original: [中文](../ARCHITECTURE-DESIGN.md)

# Design da arquitetura
> **Languages**: [中文](../ARCHITECTURE-DESIGN.md) · [English](../en/ARCHITECTURE-DESIGN.md) · [한국어](../ko/ARCHITECTURE-DESIGN.md) · [Русский](../ru/ARCHITECTURE-DESIGN.md) · [Deutsch](../de/ARCHITECTURE-DESIGN.md) · [Français](../fr/ARCHITECTURE-DESIGN.md) · [Español](../es/ARCHITECTURE-DESIGN.md) · [हिन्दी](../hi/ARCHITECTURE-DESIGN.md) · [العربية](../ar/ARCHITECTURE-DESIGN.md) · [বাংলা](../bn/ARCHITECTURE-DESIGN.md) · [Bahasa Indonesia](../id/ARCHITECTURE-DESIGN.md) · [日本語](../ja/ARCHITECTURE-DESIGN.md)

## Arquitetura em camadas

```
┌─────────────────────────────────────────┐
│          Camada de apresentação (Presentation)          │
│  Miniprograma WeChat / Flutter APP / Flutter Web   │
├─────────────────────────────────────────┤
│           Camada de rotas (Route)           │
│  config/route.php — grupos de rotas + vínculo de middleware  │
├─────────────────────────────────────────┤
│        Camada de middleware (Middleware)        │
│  Cors → Security → RateLimit → Auth      │
│  → TechnicianAuth → OperationLog         │
├─────────────────────────────────────────┤
│        Camada de controladores (Controller)        │
│  BaseController → Controladores de negócio        │
├─────────────────────────────────────────┤
│          Camada de serviços (Service)          │
│  common/ — Snowflake/Hashids/Encryption  │
├─────────────────────────────────────────┤
│           Camada de modelos (Model)          │
│  Eloquent ORM + Encryptable + Scout      │
├─────────────────────────────────────────┤
│            Camada de dados (Data)            │
│  MySQL / Redis / Elasticsearch           │
└─────────────────────────────────────────┘
```

## Design de middleware

### Cadeia de execução

```
Cors → Security(31 deteções de ataques) → RateLimit → Auth(JWT+estado do utilizador)
    → [TechnicianAuth(identidade de técnico)] → [AdminPermission(RBAC)] → [OperationLog(origem em 8 terminais)]
    → Controller
```

### Responsabilidades do middleware

| Middleware | Âmbito | Funcionalidade |
|--------|--------|------|
| Cors | Global | Pré-verificação OPTIONS + cabeçalhos de resposta CORS |
| Security | Global | erikwang2013/security-php, deteção de 31 tipos de ataques |
| RateLimit | Global | Janela deslizante Redis + atomização Lua |
| Auth | Grupo de rotas | Análise JWT + validação de existência/estado do utilizador |
| TechnicianAuth | Grupo de rotas | Consulta do perfil do técnico + validação do estado approved |
| AdminAuth | Grupo de rotas | Autenticação JWT do lado admin + lista negra |
| AdminPermission | Grupo de rotas | Validação de permissões RBAC, cache Redis de 60s |
| OperationLog | Grupo de rotas | Registo de operações + deteção automática de origem em 8 terminais |

### Estratégia de limitação de tráfego

| Interface | Limite |
|------|------|
| Padrão | 60 vezes/minuto/IP |
| Login | 10 vezes/minuto |
| Registo | 5 vezes/minuto |
| Código de verificação | 1 vez/60 segundos/número de telefone |

## Princípios de design da base de dados

### Estratégia de chave primária

- Todas as chaves primárias: BIGINT UNSIGNED NOT NULL, não autoincrementadas
- Geradas em camada de aplicação por `erikwang2013/snowflake-php`
- Model: `$incrementing = false`, `$keyType = 'string'`

### Prefixo de tabelas

Prefixo unificado `appointment_`, configurado em `config/database.php`. Os Model escrevem o nome original da tabela e o ORM adiciona o prefixo automaticamente.

### Encriptação de campos sensíveis

Usa a trait `erikwang2013/encryptable`:

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

O comprimento VARCHAR dos campos encriptados é 500 (a encriptação expande os dados).

### Soft delete e timestamps

- Eloquent SoftDeletes: `deleted_at` DATETIME DEFAULT NULL
- Todas as tabelas incluem `created_at` + `updated_at`

## Mecanismo de encriptação/desencriptação de IDs de API

### Pedido: decodeIds()

O frontend envia IDs codificados com hashids → o controlador chama `$this->decodeIds($request->all())` para desencriptar.

### Resposta: encodeIds()

Os IDs dos resultados de consulta ao DB → `BaseController::success()` chama automaticamente `encodeIds()` para codificar → devolve strings hashids.

### Regras

Processa recursivamente os campos do array cuja chave seja `id` ou termine em `_id`.

## Design de segurança

### Defesa em profundidade

```
WAF → Cors → Security(31 deteções) → RateLimit → Auth(JWT+estado)
    → [Validação de identidade] → [RBAC] → Controller(encriptação do Model) → Resposta
```

### Segurança de autenticação

- Palavra-passe: hash bcrypt
- JWT: validade de 7 dias + refresh + lista negra
- Bloqueio: 5 falhas → 15 minutos
- Concorrência: no máximo 3 Tokens

### Segurança de dados

- Camada API: erikwang2013/encryption
- Camada DB: trait erikwang2013/encryptable
- Registos: dados sensíveis não entram nos registos

### Segurança de operações

- erikwang2013/poster-php: verificação antes de eliminar/auditar/levantar
- Middleware Security: deteção de XSS/injeção SQL/CSRF/travessia de caminhos

## Integração com Elasticsearch

`erikwang2013/webman-scout` sincroniza automaticamente os modelos para o ES:

```php
use Erikwang2013\WebmanScout\Searchable;

class Service extends Model
{
    use Searchable;
    public function searchableAs(): string { return 'appointment_services'; }
}
```

## Exportação Excel/PDF

- Excel: PhpSpreadsheet, mascaramento automático de campos sensíveis
- PDF: exportação visual do painel Dashboard

## Deteção de origem em 8 terminais

O OperationLog analisa através do User-Agent:

```
iPad → iPadOS / Mac → macOS / Windows → Windows
Linux → Linux / iPhone → ios / Android → android
HarmonyOS → harmonyOS / outro → web
```

## Testes TDD

| Projeto | Número de testes | Estado |
|------|--------|------|
| admin/ | 60 | ✅ Aprovado |
| service/ | 21 | ✅ Aprovado |
| Total | 81 | ✅ |

Cobertura de testes: regras de reembolso / estado de pedidos / Hashids / sistema de filas / encriptação / códigos de verificação
