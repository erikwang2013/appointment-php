> Tradução em português · Original: [中文](../SECURITY-AUDIT-REPORT.md)

# Relatório de auditoria de segurança — Sistema de agendamento (appointment-php)
> **Languages**: [中文](../SECURITY-AUDIT-REPORT.md) · [English](../en/SECURITY-AUDIT-REPORT.md) · [한국어](../ko/SECURITY-AUDIT-REPORT.md) · [Русский](../ru/SECURITY-AUDIT-REPORT.md) · [Deutsch](../de/SECURITY-AUDIT-REPORT.md) · [Français](../fr/SECURITY-AUDIT-REPORT.md) · [Español](../es/SECURITY-AUDIT-REPORT.md) · [हिन्दी](../hi/SECURITY-AUDIT-REPORT.md) · [العربية](../ar/SECURITY-AUDIT-REPORT.md) · [বাংলা](../bn/SECURITY-AUDIT-REPORT.md) · [Bahasa Indonesia](../id/SECURITY-AUDIT-REPORT.md) · [日本語](../ja/SECURITY-AUDIT-REPORT.md)

**Data**: 2026-08-04
**Âmbito da auditoria**: service (sistema de serviços de agendamento), admin (painel de administração aberto)
**Versão PHP**: 8.3.7
**Framework**: webman v2

---

## I. Resultados dos testes

| Item de teste | Service | Admin |
|--------|---------|-------|
| Verificação de sintaxe PHP (total) | Aprovado | Aprovado |
| Testes unitários PHPUnit | 59 tests / 165 assertions PASS | 59 tests / 165 assertions PASS |
| Análise estática PHPStan | Não instalado (timeout no download das dev dependencies) | Não instalado (timeout no download das dev dependencies) |

---

## II. Visão geral das camadas de proteção de segurança

```
Pedido → Nginx (cabeçalhos de segurança+proteção de ficheiros sensíveis) → Cors (CORS+cabeçalhos de segurança) → SecurityMiddleware (deteção de 31 tipos de ataques) → RateLimit (janela deslizante Redis) → Auth (JWT) → Controller
                                                                                                   ↓
                                                                                    Lista negra de IPs (5 ataques/60s → banimento 15min)
                                                                                    Bloqueio de conta (5 falhas/15min → bloqueio 15min)
```

---

## III. Problemas corrigidos

### 3.1 CORS do Service sem cabeçalhos de resposta de segurança → corrigido
**Ficheiro**: `service/app/middleware/Cors.php`
- Adicionados 6 cabeçalhos de segurança: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, CSP, X-Permitted-Cross-Domain-Policies
- Agora consistente com a configuração de cabeçalhos de segurança do admin

### 3.2 Service sem bloqueio por falhas de início de sessão → corrigido
**Ficheiro**: `service/app/api/v1/controller/AuthController.php`
- `login()` e `loginByCode()` têm agora contagem de falhas no Redis
- 5 falhas/15 minutos → bloqueio → HTTP 429
- Degradação graciosa quando o Redis falha

### 3.3 Origin CORS codificado como `*` → corrigido
**Ficheiros**: `service/app/middleware/Cors.php`, `admin/app/middleware/Cors.php`
- Passou a ser configurado através da variável de ambiente `CORS_ALLOW_ORIGIN`
- Vazio por predefinição → `*` (compatibilidade para trás)

### 3.4 Service sem dependência security-php → corrigido
**Operações**:
- Adicionado `allow-plugins.erikwang2013/security-php` ao composer.json
- Executado `composer install --no-dev` para instalar a dependência
- Ficheiro de configuração publicado em `config/plugin/erikwang2013/security-php/app.php`
- Detetor de Origin CSRF (`csrf_origin`) ativado (modo block)

### 3.5 Nginx do Service sem Permissions-Policy → corrigido
**Ficheiro**: `service/docs/nginx.conf`
- Adicionado `add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;`

### 3.6 Complementos da configuração do ecossistema → corrigidos
- `service/.env.example` e `admin/.env.example` passaram a incluir `CORS_ALLOW_ORIGIN`
- `service/.env.docker` e `admin/.env.docker` passaram a incluir `CORS_ALLOW_ORIGIN`

---

## IV. Lista completa da proteção de segurança atual

### 4.1 Camada WAF — 31 detetores de ataques

| Modo | Detetor | Quantidade |
|------|--------|------|
| **block** (interceção 403) | XSS, injeção SQL, injeção de comandos, path traversal, upload de ficheiros, SSRF, XXE, desserialização, injeção LDAP, injeção de cabeçalhos de e-mail, Open Redirect, ataques JWT, ataques de Host header, Request Smuggling, injeção GraphQL, injeção XPATH, JNDI/Log4Shell, injeção SSI, injeção CSV, fuga de dados, Prototype Pollution, hijacking WebSocket, bypass CORS, DNS Rebinding, validação de métodos HTTP, tamanho do corpo do pedido (10MB), whitelist de Content-Type, Origin CSRF | 28 |
| **log** (apenas registo) | injeção de cabeçalhos de resposta, SSTI, injeção NoSQL | 3 |

### 4.2 Autenticação e autorização

| Mecanismo | Service | Admin |
|------|---------|-------|
| Autenticação JWT | middleware Auth | middleware AdminAuth |
| Lista negra JWT | adicionado no término de sessão | adicionado no término de sessão e no excesso de sessões |
| Permissões RBAC | — | formato method.path, cache Redis 60s |
| Bloqueio de conta | 5 vezes/15 minutos (Redis) | 5 vezes/15 minutos (Redis) |
| Limite de sessões concorrentes | — | máximo 3 Tokens |
| Hash de palavras-passe | bcrypt | bcrypt |

### 4.3 Limitação de tráfego

| Rota | Service | Admin |
|------|---------|-------|
| Predefinido | 60 vezes/minuto/IP | 60 vezes/minuto/IP |
| Início de sessão | 10 vezes/minuto | — |
| Registo | 5 vezes/minuto | — |
| SMS/recuperação de palavra-passe | 5 vezes/minuto | — |

### 4.4 Segurança de dados

| Medida | Service | Admin |
|------|---------|-------|
| Encriptação de campos da base de dados | AES-256-CBC (6 modelos) | AES-256-CBC |
| Encriptação de transmissão API | AES-256-CBC | AES-256-CBC |
| Ofuscação de IDs (Hashids) | todos os IDs externos | todos os IDs externos |
| IDs Snowflake | BIGINT não autoincrementado | BIGINT não autoincrementado |
| Desmascaramento de campos sensíveis | desmascaramento de números de telemóvel | desmascaramento de dados exportados |

---

## V. Recomendações pendentes

### 5.1 Recomendação: armazenamento do security-php com Redis (produção)
**Atual**: os dois serviços usam armazenamento do tipo `file` (ficheiro JSON local)
**Risco**: em implantação multi-instância, a lista negra de IPs não é partilhada; os atacantes podem alternar de instância para contornar
**Recomendação**: em produção, alterar `storage.type` para `redis`

### 5.2 Recomendação: atributos de segurança do cookie de sessão
**Atual**: `secure: false`, `same_site: ''`
**Risco**: o cookie pode ser transmitido por HTTP, enfraquecendo a proteção CSRF
**Recomendação**: em produção, definir `secure: true`, `same_site: 'Lax'`

### 5.3 Recomendação: instalar a dev dependency do PHPStan
**Atual**: `composer install --dev` falhou por timeout de rede
**Operações**: `composer install --dev` ou `composer require --dev phpstan/phpstan`

### 5.4 Aviso: alterar todas as chaves antes da implantação em produção
As chaves de marcador de posição no `.env.docker` devem ser substituídas por valores gerados aleatoriamente antes da implantação em produção:
- `JWT_SECRET_KEY`
- `HASHIDS_SALT`, `HASHIDS_ALT_SALT`
- `ENCRYPTION_KEY`, `ENCRYPTABLE_KEY`
- `DB_PASSWORD`

---

## VI. Produção documental

| Documento | Caminho |
|------|------|
| Arquitetura de segurança do Service | `service/docs/SECURITY.md` |
| Arquitetura de segurança do Admin | `admin/docs/SECURITY.md` |
| Este relatório de auditoria | `docs/SECURITY-AUDIT-REPORT.md` |

---

## VII. Conclusão da auditoria

**Classificação global da proteção de segurança: boa**

- Camadas de defesa em profundidade completas (Nginx → WAF → Rate Limit → Auth → RBAC)
- 31 detetores de ataques com cobertura global, 28 em modo de interceção
- Proteção de autenticação multicamada: JWT + lista negra + bloqueio de conta + lista negra de IPs
- Encriptação AES-256-CBC na camada de dados + ofuscação Hashids
- Corrigidos três problemas-chave do service: cabeçalhos de resposta de segurança em falta, bloqueio de início de sessão em falta, pacote WAF em falta
- As recomendações são otimizações de configuração para produção, não vulnerabilidades de segurança

---

## VIII. Ronda de correções 2026-08-26 (reforço de segurança)

| Item | Conteúdo da correção |
|----|---------|
| Anti-adulteração na criação de encomendas | O preço dos itens em OrderController::store() passa a ser sempre o registo da base de dados (service→erik_service, product→erik_product), o preço enviado pelo cliente não participa no cálculo; target_type desconhecido → 422; target_id obrigatoriamente hashid (raw id descodificado como 0 → 422 «Produto inexistente ou fora de catálogo»); preços de compra em grupo/relâmpago também com base na BD |
| Dedução unificada do stock de vendas relâmpago | O stock é deduzido uniformemente com bloqueio de linha dentro da transação do /api/order store(); SeckillController::buy deixa de deduzir stock antecipadamente (mantém o bloqueio de atividade Redis + idempotência client_token); chamar diretamente /api/order com seckill_id também deduz stock |
| Levantamento do técnico | No pedido, o saldo é debitado e reservado como fundos em trânsito (pending/approved); antes da transferência após aprovação, reverificação de settled−withdrawn−em trânsito ≥ montante do levantamento; aprovações concorrentes não duplicam o pagamento |
| Callback de pagamento | No callback WeChat, total_fee é comparado rigorosamente com o montante a pagar da encomenda; não correspondência → rejeição; desmascaramento dos registos do callback Alipay (sem buyer_id/seller_id, etc.) |
| Proteção do /install | Após instalação bem-sucedida, escreve .install.lock; a interface install tem dupla validação (bloqueio de ficheiro + isInstalled); .gitignore já ignora .install.lock |
| Convergência de dependências | webman-scout unificado em 2.0.5 (service/admin); adicionado opensearch-project/opensearch-php ^2.6; dompdf/security-php/webman-database com versões exatas bloqueadas (remoção do wildcard "*") |
| Engenharia | Eliminado service/app/common/StorageService.php (código morto); adicionados em admin/app/common/ TechnicianWithdrawalService/WechatPayService (implantação independente do admin sem depender do código do service); phpstan.neon das duas aplicações corrigido para ser executável (php -d memory_limit=2G) |
