> Tradução em português · Original: [中文](../TEST-REPORT.md)

# Relatório da equipa de testes — auditoria de cobertura total de testes
> **Languages**: [中文](../TEST-REPORT.md) · [English](../en/TEST-REPORT.md) · [한국어](../ko/TEST-REPORT.md) · [Русский](../ru/TEST-REPORT.md) · [Deutsch](../de/TEST-REPORT.md) · [Français](../fr/TEST-REPORT.md) · [Español](../es/TEST-REPORT.md) · [हिन्दी](../hi/TEST-REPORT.md) · [العربية](../ar/TEST-REPORT.md) · [বাংলা](../bn/TEST-REPORT.md) · [Bahasa Indonesia](../id/TEST-REPORT.md) · [日本語](../ja/TEST-REPORT.md)

> Data de geração: 2026-08-26　Versão: v1.3.8
> Equipa: deep-audit (tester-php / tester-api / tester-ui / tester-go / tester-rust)

## 1. Resumo executivo

| Papel | Tarefa | Resultado |
|------|------|------|
| Engenheiro de testes PHP | Testes unitários/integração de todos os módulos | 70 testes existentes + novos desta ronda (ver §3) |
| Engenheiro de testes de API | Automatização de todas as interfaces | Os testes de integração da camada de controladores são a forma de automatização de API deste projeto (§4) |
| Engenheiro de automatização de UI | End-to-end de todas as páginas | Ambiente indisponível, conclusão em §5 |
| Engenheiro de testes GO | Testes unitários | **Saltado: o projeto não tem código GO** (zero ficheiros .go) |
| Engenheiro de testes Rust | Testes unitários | **Saltado: o projeto não tem código Rust** (zero ficheiros .rs) |

## 2. Stack tecnológica e forma dos testes

- Backend: PHP 8.3 webman, duas aplicações (service lado do utilizador / admin lado da gestão), partilham os modelos do service
- Framework de testes: PHPUnit + Eloquent, modo **MySQL real + rollback de transações** (não mock), skip automático se a BD estiver indisponível
- Execução de testes: `cd service && php -d memory_limit=2G vendor/bin/phpunit`
- Automatização de API = testes de integração da camada de controladores (constroem Request e chamam diretamente os métodos dos controladores, acedem à BD real, rollback de transações)

## 3. Cobertura de testes PHP

**Resultado total: 558 tests / 2508 assertions, 0 falhas 0 erros 0 skip** (2 deprecation de vendor existentes, 2 notices PHPUnit existentes, nenhum introduzido nesta ronda; os 4 skips originais do gate de levantamento foram eliminados tornando `config('withdraw.gate_day')` injetável, execução em qualquer dia)

### Novos nesta ronda (tester-php, 6 ficheiros 32 casos, todos com BD real + rollback de transações)

| Ficheiro de teste | Casos | Cobertura |
|---------|------|------|
| CartControllerTest | 4 | normalização ao guardar (whitelist/qty≥1/remoção de entradas inválidas), 400 para não-array, carrinho vazio, esvaziar |
| PointControllerTest | 4 | saldo=último snapshot, meta de paginação, filtro por type/source, lista vazia |
| AddressControllerTest | 7 | criar+predefinido, 400 para campos obrigatórios, exclusividade do predefinido, prioridade do predefinido, 404 por acesso não autorizado, mudar predefinido, eliminar+segundo 404 |
| FavoriteControllerTest | 7 | favoritar serviço/técnico, 400 para tipo inválido, 400 para duplicado, incremento/decremento de favorite_count, favorito órfão, 404 ao eliminar |
| ReferralControllerTest | 5 | geração de código de convite+estatísticas, 404 do utilizador, URL do código QR, lista de recomendados, detalhes da comissão |
| WithdrawControllerTest | 5 | rejeição fora do dia de gate (config injetável diferente de hoje), sucesso, saldo insuficiente, <10 yuan, sem conta (execução em qualquer dia, 0 skip) |

### Cobertura existente (70 ficheiros, inalterados)

35+ controladores cobertos: Auth/Máquina de estados de Order/Reembolso/Verificação/Remarcação/Callback de pagamento/Secagem relâmpago/Compra em grupo/Cupões/Cartão-presente/Pontos/Carteira/Transferência/Cartão de membro/Valor de crescimento/Comissões/Levantamento/Check-in/Horário/Fatura/Logística/Push/Mensagens de subscrição/Filas, etc.

### Correções desta ronda (encontradas pelo tester-php)

- 【bug】AddressController::show/update/destroy e FavoriteController::destroy não faziam descodificação hashids, chamadas com hashid devolviam 404.
  Correção da causa raiz: `BaseController::decodeId` passou a ter compatibilidade de passagem direta para IDs só numéricos (se o hashids não descodificar e `ctype_digit` devolve o valor original),
  as 89 chamadas de todo o repositório beneficiam uniformemente; entrada de decodeId adicionada nos 4 métodos de controladores. Regressão total aprovada.
- 【bug】com o hashids min-length igual a 0, alguns IDs numéricos nus (como 306) eram exatamente a codificação hashids válida de outros IDs,
  decodeId descodificava erradamente para um ID errado (404 intermitente no AddressControllerTest, reprodução aleatória em várias execuções totais).
  Correção da causa raiz: em service/admin `config/hashids.php`, o `length` da ligação main passou de 0 para 8,
  a codificação tem sempre ≥8 caracteres, sem interseção de comprimento com IDs numéricos nus (<8 ou 16 dígitos), a ambiguidade foi eliminada do espaço de codificação.
  5 execuções consecutivas do AddressControllerTest verificam estabilidade, regressão total aprovada.
- O gate do dia de levantamento, codificado com o dia 20, passou a `config('withdraw.gate_day')` injetável (config/withdraw.php),
  os 4 casos de skip originais "apenas dia 20 de cada mês" passaram a injetar o dia de gate por reflexão, executáveis em qualquer dia, 0 skip.

## 4. Conclusão dos testes de automatização de API

- O projeto não tem scripts de teste HTTP em camada separada; os 70 ficheiros de teste existentes são todos testes de integração da camada de controladores (BD real),
  cobrem 35+ controladores, equivalentes a testes de automatização de interfaces
- Matriz de cobertura de testes em §3
- **Smoke test HTTP executado** (2026-08-26): a porta 8787 estava ocupada por outro projeto, por isso o serviço foi iniciado temporariamente com o
  `config/process.php` do service a ouvir na 8791 (32 workers webman + websocket + 4 temporizadores todos [OK]),
  medido na prática `GET /health` → `{"code":0,"message":"ok"}`, `GET /api/guest/services` → HTTP 200
  com JSON normal (IDs codificados com hashids visíveis), depois stop e restauro da configuração, zero processos residuais
- Sugestão: adicionar no CI flutter build web → E2E Playwright dos caminhos críticos do painel de administração (ver §5)

## 5. Conclusão de end-to-end de UI

- Clientes: Flutter (apps/flutter lado do utilizador, admin/apps/flutter lado da gestão), miniprograma WeChat (apps/wechat),
  HarmonyOS (apps/harmonyos), admin/apps/weixin
- Estado atual: o Flutter web do admin não tem artefacto de build (build/web não existe); não há serviços de UI em execução na máquina;
  o miniprograma WeChat/HarmonyOS não têm canal de automatização de browser
- **Conclusão: o ambiente de automatização end-to-end não está disponível**. Sugestão: adicionar no CI: flutter build web → Playwright para conduzir
  os caminhos críticos do painel (início de sessão→lista de encomendas→verificação); miniprograma/HarmonyOS exigem testes manuais em dispositivo real/emulador
- Fornecido: admin/public/apidoc (página de documentação da interface)

## 6. GO / Rust

Digitalização recursiva da raiz do projeto: **0 ficheiros .go, 0 ficheiros .rs** (excluindo vendor/node_modules/.git).
As toolchains estão instaladas (go / rustc disponíveis) mas não há alvo testável. Se forem introduzidos serviços GO/Rust no futuro, é necessário adicionar testes.

## 7. Riscos residuais (áreas de alto valor não cobertas)

- Fluxo principal da order (já coberto por testes a nível de traits como OrderState/OrderRefundFlow)
- Callback real do WeChat Pay (WechatPayService tem teste unitário, o sandbox real do WeChat não foi testado em conjunto)
- Módulos com dependências externas: impressão, LBS, códigos de verificação, etc.

(§3 a preencher após o regresso do tester-php)
