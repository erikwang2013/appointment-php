> Tradução em português · Original: [中文](../INSTALL.md)

# Sistema de Serviços de Agendamento — Guia de instalação
> **Languages**: [中文](../INSTALL.md) · [English](../en/INSTALL.md) · [한국어](../ko/INSTALL.md) · [Русский](../ru/INSTALL.md) · [Deutsch](../de/INSTALL.md) · [Français](../fr/INSTALL.md) · [Español](../es/INSTALL.md) · [हिन्दी](../hi/INSTALL.md) · [العربية](../ar/INSTALL.md) · [বাংলা](../bn/INSTALL.md) · [Bahasa Indonesia](../id/INSTALL.md) · [日本語](../ja/INSTALL.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## Requisitos de ambiente

| Componente | Versão mínima | Descrição |
|------|----------|------|
| PHP | 8.3+ | Extensões: bcmath, curl, gd, mbstring, pdo, pdo_mysql, pcntl, redis |
| MySQL | 8.0+ | Prefixo de tabelas `appointment_`, charset utf8mb4 |
| Redis | 6.0+ | Cache / limitação de tráfego / Sessão / armazenamento de códigos de verificação |
| Composer | 2.x | Gestão de dependências PHP |
| Elasticsearch | 8.x (opcional) | Pesquisa de texto integral, sem instalar não afeta as funcionalidades principais |

---

## I. Assistente de instalação Web (recomendado)

Depois de iniciar o painel de administração, aceda a `/install` no navegador para entrar no assistente de instalação com um clique:

```bash
# 1. Instalar dependências e iniciar
cd admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
php start.php start -d     # porta padrão 8787
```

Abra `http://localhost:8787/install` no navegador e conclua em 4 passos:

1. **Verificação de ambiente** — deteta automaticamente a versão do PHP, extensões necessárias e permissões de ficheiros
2. **Configuração da base de dados** — preencha as informações de ligação MySQL e clique em testar ligação
3. **Conta de administrador** — defina o nome da aplicação, nome de utilizador e palavra-passe do administrador
4. **Executar instalação** — importação automática do SQL → criação do administrador → escrita da configuração .env

Após a instalação, inicie sessão com o nome de utilizador e palavra-passe definidos. Uma instalação bem-sucedida escreve o ficheiro `.install.lock`, e a interface `/install` faz dupla validação (bloqueio de ficheiro + isInstalled) contra instalação repetida; `.install.lock` foi adicionado ao `.gitignore`. Recomenda-se eliminar a rota `/install` de `admin/config/route.php` em produção.

---

## II. Instalação manual

### 2.1 Clonar o projeto

```bash
git clone <repo-url> appointment-php
cd appointment-php
```

### 1.2 Instalar dependências PHP

```bash
# Serviço de API de negócio
cd service/
cp .env.example .env
composer install --no-dev --optimize-autoloader

# Painel de administração
cd ../admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
```

### 1.3 Configurar variáveis de ambiente

Edite `service/.env` (API de negócio) e `admin/.env` (painel de administração), alterando as seguintes configurações chave:

```bash
# Ligação à base de dados
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=appointment          # service usa appointment, admin usa open_admin
DB_USERNAME=root
DB_PASSWORD=your-password

# Ligação Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# Chave JWT — em produção, mude para uma string aleatória de 64 caracteres
JWT_SECRET_KEY=your-64-char-random-string

# Chave de encriptação — em produção, mude obrigatoriamente
ENCRYPTION_KEY=your-32-byte-key
ENCRYPTABLE_KEY=your-32-byte-key

# Sal do Hashids — em produção, mude obrigatoriamente
HASHIDS_SALT=your-random-salt

# Modo de depuração — em produção, tem de ser false
APP_DEBUG=false
```

> A descrição completa das variáveis está em `service/.env.example` e `admin/.env.example`.

### 1.4 Criar a base de dados e importar

```bash
# Criar a base de dados (service e admin podem usar a mesma ou bases separadas)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS appointment DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS open_admin DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importar o script de instalação unificado (inclui todas as 54+ tabelas + dados de permissões + dados de demonstração)
mysql -u root -p appointment < docs/install.sql
mysql -u root -p open_admin < docs/install.sql
```

> `docs/install.sql` é a fusão de todos os ficheiros de migração, 2723 linhas no total, incluindo toda a estrutura de tabelas e dados de seed do painel de administração e do serviço de negócio. Execute uma única vez numa instalação nova; executar repetidamente numa base existente interrompe por conflitos de chave primária/colunas; em cenários de atualização, faça primeiro um backup ou trate os conflitos manualmente.

### 1.5 Iniciar os serviços

```bash
# Iniciar o serviço de API de negócio (porta padrão 8787)
cd service/
php start.php start -d

# Iniciar o painel de administração (porta padrão 8787)
cd ../admin/
php start.php start -d
```

### 1.6 Verificar a instalação

```bash
# API de negócio
curl http://localhost:8787/api/common/config

# Verificação de saúde do painel de administração
curl http://localhost:8787/health

# Login no painel de administração (conta e palavra-passe padrão abaixo)
curl -X POST http://localhost:8787/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### 1.7 Conta padrão

| Papel | Nome de utilizador | Palavra-passe | Descrição |
|------|--------|------|------|
| Superadministrador | `admin` | `admin123` | Tem todas as permissões |

> Após o primeiro login, altere a palavra-passe imediatamente.

---

## III. Implantação Docker

### 2.1 Serviço de API de negócio

```bash
cd service/
cp .env.docker .env
# edite .env, altere as chaves e palavras-passe
docker-compose up -d
```

Orquestração: nginx (80/443) + app (8787) + mysql (3306) + redis (6379) + elasticsearch (9200)

### 2.2 Painel de administração

```bash
cd admin/
cp .env.docker .env
docker-compose up -d
```

### 2.3 Importar a base de dados em ambiente Docker

```bash
# Copiar install.sql para o contentor e executar
docker cp docs/install.sql appointment-svc-mysql:/tmp/
docker exec -it appointment-svc-mysql mysql -u root -p appointment < /tmp/install.sql
```

---

## IV. Visão geral da estrutura da base de dados

| Domínio | N.º de tabelas | Tabelas principais |
|----|------|--------|
| Painel de administração | 8 | `appointment_admin_user`, `appointment_admin_role`, `appointment_admin_permission`, `appointment_operation_log` |
| Domínio do utilizador | 4 | `appointment_user`, `appointment_user_address`, `appointment_user_favorite`, `appointment_user_device` |
| Domínio do técnico | 8 | `appointment_technician_profile`, `appointment_technician_schedule`, `appointment_technician_earning`, `appointment_technician_withdrawal`, `appointment_technician_tier_config` |
| Domínio de serviços | 4 | `appointment_service_category`, `appointment_service`, `appointment_service_package`, `appointment_service_record` |
| Domínio de pedidos | 5 | `appointment_order`, `appointment_order_item`, `appointment_order_payment`, `appointment_order_refund`, `appointment_order_review` |
| Domínio de marketing | 8 | `appointment_coupon`, `appointment_member_card`, `appointment_gift_card`, `appointment_user_points`, `appointment_promotion` |
| Filas | 1 | `appointment_queue_number` |
| Domínio de conteúdo | 5 | `appointment_banner`, `appointment_announcement`, `appointment_faq`, `appointment_feedback`, `appointment_platform_agreement` |
| Domínio da comunidade | 3 | `appointment_post`, `appointment_comment`, `appointment_moment` |
| Lojas | 1 | `appointment_store` |
| Formação | 2 | `appointment_training_course`, `appointment_training_progress` |
| Exames | 3 | `appointment_exam`, `appointment_exam_question`, `appointment_exam_attempt` |
| Sistema | 3 | `appointment_system_config`, `appointment_notification`, `appointment_signature` |
| **Total** | **55** | |

Todas as tabelas usam o prefixo `appointment_`, com a chave primária `id` em BIGINT não autoincrementada (gerada na camada de aplicação pelo snowflake-php).

---

## V. Executar testes

```bash
# Testes da API de negócio (21 tests)
cd service/
php vendor/bin/phpunit

# Testes do painel de administração (59 tests)
cd admin/
php vendor/bin/phpunit

# Análise estática
php vendor/bin/phpstan analyse --level=5 app/

# Verificação de estilo de código
php vendor/bin/php-cs-fixer fix --dry-run --diff
```

---

## VI. Configuração de serviços de terceiros

Preencha os seguintes grupos de configuração em "Configuração do sistema" do painel de administração:

| Grupo de configuração | Utilização | Obrigatório |
|--------|------|------|
| `wechat_pay` | N.º de comerciante do pagamento WeChat / chave da API / certificado | Necessário para pagamentos |
| `wechat_app` | AppID / AppSecret do miniprograma WeChat | Necessário para login WeChat |
| `sms` | Fornecedor de SMS (aliyun/tencent) + assinatura/modelo | Necessário para códigos de verificação por SMS |
| `map_service` | Serviço de mapas (amap/tencent) + API Key | Necessário para LBS |
| `storage` | Armazenamento de objetos (oss/cos) + AccessKey/Endpoint | Necessário para carregamento de ficheiros |

---

## VII. Perguntas frequentes

**P: Erro ao iniciar `Class 'support\Model' not found`**
R: Execute `composer dump-autoload`.

**P: Falha na ligação à base de dados `SQLSTATE[HY000] [2002]`**
R: Verifique a configuração de `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD` no `.env`.

**P: Erro de codificação ao importar o SQL**
R: Use `mysql -u root -p --default-character-set=utf8mb4 < docs/install.sql`

**P: Falha na ligação Redis**
R: Confirme que o Redis está iniciado e verifique a configuração de `REDIS_HOST`/`REDIS_PORT`.

**P: Porta ocupada**
R: Altere a porta de `listen` em `config/server.php`.

**P: O código de verificação não aparece**
R: Confirme que a extensão GD está instalada e que `POSTER_CAPTCHA_STORAGE` está configurado corretamente (local pode usar `file`, produção usa `redis`).

**P: Elasticsearch não funciona**
R: O ES é um componente opcional; confirme que `SCOUT_HOSTS` está configurado corretamente e que o serviço ES está iniciado.

---

## VIII. Estrutura de diretórios

```
appointment-php/
├── admin/                    # Painel de administração (webman v2)
│   ├── app/                  # Controladores / modelos / middleware
│   ├── config/               # Configuração de rotas / base de dados / middleware
│   ├── database/             # Scripts de backup (estrutura de tabelas e seeds unificados em docs/install.sql)
│   ├── tests/                # Testes PHPUnit (59 tests)
│   ├── .env.example          # Modelo de variáveis de ambiente
│   ├── .env.docker           # Variáveis de ambiente Docker
│   ├── Dockerfile            # Ficheiro de construção Docker
│   └── docker-compose.yml    # Orquestração Docker
├── service/                  # Serviço de API de negócio (webman v2)
│   ├── app/                  # Controladores / modelos / middleware
│   ├── config/               # Configuração de segurança / rotas / base de dados
│   ├── seed.php              # Executor de seeds de dados de demonstração (lê a secção de dados de demonstração de docs/install.sql)
│   ├── tests/                # Testes PHPUnit (21 tests)
│   ├── .env.example          # Modelo de variáveis de ambiente
│   ├── .env.docker           # Variáveis de ambiente Docker
│   ├── Dockerfile            # Ficheiro de construção Docker
│   └── docker-compose.yml    # Orquestração Docker
├── docs/                     # Documentação
│   ├── INSTALL.md            # Este guia de instalação
│   ├── install.sql           # Script unificado de instalação da base de dados (2723 linhas)
│   ├── ARCHITECTURE.md       # Documento de design da arquitetura
│   ├── API.md                # Documento de referência da API
│   └── AUDIT-REPORT.md       # Relatório de revisão
└── .github/workflows/        # Pipeline CI/CD
    └── ci.yml
```
