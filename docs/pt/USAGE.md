> Tradução em português · Original: [中文](../USAGE.md)

# Instruções de utilização
> **Languages**: [中文](../USAGE.md) · [English](../en/USAGE.md) · [한국어](../ko/USAGE.md) · [Русский](../ru/USAGE.md) · [Deutsch](../de/USAGE.md) · [Français](../fr/USAGE.md) · [Español](../es/USAGE.md) · [हिन्दी](../hi/USAGE.md) · [العربية](../ar/USAGE.md) · [বাংলা](../bn/USAGE.md) · [Bahasa Indonesia](../id/USAGE.md) · [日本語](../ja/USAGE.md)

## Início de sessão no painel de administração

Administrador predefinido: `admin` / `admin123` | Endereço: `http://localhost:8787`

> Altere a palavra-passe imediatamente após o primeiro início de sessão

---

## Fluxo de configuração do sistema

### 1. Definições básicas
Configuração do sistema → preencher nome da plataforma/LOGO → Sobre nós → telefone do apoio ao cliente/site/e-mail → Acordos da plataforma → editar acordo do utilizador/acordo de privacidade

### 2. Lojas e serviços
Gestão de lojas → adicionar loja (nome/endereço/coordenadas/telefone/horário) → Categorias de serviços → criar categoria → Serviços → adicionar serviço (nome/preço/duração/especificações) → Gestão de produtos → adicionar produto/cartões e cupões

### 3. Adesão de técnicos
Candidatura na aplicação do técnico → aprovação na «Gestão de técnicos» do painel de administração → após aprovação, o técnico define o horário → pode receber marcações

### 4. Configuração operacional
Carrossel → carregar + definir redirecionamento | Anúncios → publicar anúncios deslizantes | Cupões → criar cupão de novo utilizador/cupão de valor mínimo | Cartões de membro → cartão mensal/VIP/cartão de vezes | Comissões → definir a percentagem de comissão do técnico

---

## Operação diária do painel de administração

### Painel de controlo
Após o início de sessão, a página inicial mostra 7 cartões de estatísticas renderizados dinamicamente (total de utilizadores / novos hoje / utilizadores ativos / registos de operações / reservas de hoje / levantamentos pendentes / técnicos pendentes), gráficos de tendência de 30 dias (volume de encomendas / montante / novos utilizadores / atividade), um gráfico circular de distribuição do estado dos utilizadores (ativado/desativado) e os últimos 10 registos de operações (cache Redis `svc:dashboard` 300 s); a navegação rápida conduz diretamente aos módulos pendentes, e as mensagens internas entregam notificações de novas encomendas/reembolsos.

### Relatórios de dados
A página de relatórios oferece 3 tipos de relatórios (intervalo de 7/30 dias, suportado por `GET /admin/reports/orders|technicians|distribution`, cache Redis 300 s):
- **Estatísticas de encomendas** — resumo (número de encomendas/montante pago/reembolsos/receita líquida) + tendência diária
- **Desempenho dos técnicos** — TOP 10 de técnicos (número de encomendas/receita/avaliação, nomes mascarados, ordenável por número ou receita)
- **Distribuição de canais** — distribuição dos canais de pagamento (WeChat/Alipay/saldo) + distribuição dos estados das encomendas

Também estão disponíveis as estatísticas de vendas (`svc:sales_stats`: resumo de encomendas do período por loja/tipo de serviço) e as estatísticas financeiras (`svc:finance_stats`: resumo de receitas/reembolsos/levantamentos/comissões do período).

---

## Fluxo do lado do utilizador

### Registo e início de sessão
Pesquisa no WeChat/leitura de código QR → registo com número de telemóvel + código de verificação (código de recomendação opcional) → ou início de sessão com um toque no WeChat → o novo utilizador recebe automaticamente um cupão

### Marcação de serviços
Navegar pelas categorias na página inicial → tocar no serviço para ver os detalhes → consultar preço/avaliações → Marcar já → selecionar loja/técnico/horário/cupão → confirmar a encomenda → pagamento WeChat → pagamento concluído

### Gestão de encomendas
Por pagar: concluir o pagamento | Pago: aguardar o serviço | Concluído: avaliar (estrelas + texto + imagens) | Reembolso: cálculo automático da percentagem de reembolso

### Centro pessoal
Encomendas/cupões/cartões de membro/pontos/favoritos | Centro de divulgação: obter código QR de divulgação para ganhar pontos | Feedback: texto + imagens

---

## Operações do lado do técnico

### Alternância de identidade
Na aplicação «Eu» → alternar para técnico → secretária de trabalho

### Trabalho diário
- **Definição de horário**: definir os períodos de tempo disponíveis para marcação por dia
- **Consultar marcações**: lista das encomendas marcadas para hoje
- **Verificação por código QR**: ler o código QR do utilizador para verificar utilizações
- **Ficheiro de membro**: preencher o ficheiro do cliente no prazo de 24h por encomenda (sem comissão se exceder o prazo)
- **Registo de presenças**: entrada/saída/fotografia de higiene

### Rendimentos
Consultar o rendimento de hoje/fundos em trânsito/saldo → levantamento no dia 20 de cada mês → T+1 para a carteira WeChat

### Crescimento
Frequentar cursos de formação → participar em exames → aprovação aumenta o nível do técnico (influencia a taxa de comissão)

---

## Interface API

A documentação da interface é mantida de forma independente, ver [API.md](API.md) (API de negócio + API do painel de administração, com exemplos de pedido/resposta e endpoint OpenAPI).

---

## WebSocket

```
ws://localhost:8282
```

Autenticação: `{"type":"auth","token":"<JWT>"}`

Eventos: `order_update` / `technician_online` / `system_notice`

---

## Configuração de notificações push

iOS (APNs): configurar apns_key_id/team_id/bundle_id/ficheiro .p8
Android (FCM): configurar fcm_server_key

Registo de dispositivos da APP: `POST /api/v1/user/device/register {"platform":"ios","device_token":"..."}`

---

## Tarefas agendadas

| Tarefa | Frequência | Descrição |
|------|------|------|
| Cancelamento automático de encomendas | 30 segundos | por pagar há mais de 30 minutos |
| Liquidação automática de rendimentos | 3 dias | liquidação da comissão de encomendas concluídas |
| Expiração de cupões | todos os dias | marcar como expired |
| Expiração de cartões de membro | todos os dias | marcar como expired |

---

## Regras de reembolso

| Condição | Percentagem |
|------|------|
| No prazo de 15 minutos após a encomenda ou >6h até ao início | 100% |
| ≤6h até ao início | 90% |
| Já iniciado mas não confirmado | 80% |
| Após confirmação do início | 0% |

---

## Monitorização

```bash
GET /health          # verificação de saúde
GET /metrics         # métricas Prometheus
GET /.well-known/security.txt  # contacto de segurança
```

## Testes

```bash
admin/ && phpunit --bootstrap tests/bootstrap.php     # 60 tests
service/ && phpunit --configuration phpunit.xml        # 21 tests
```
