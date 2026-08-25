# Roadmap --- Finalização da Plataforma Noobstron

## Objetivo

Levar a plataforma do estado atual até pronta para lançamento e operação
com clientes reais, evitando refazer funcionalidades que já foram
implementadas e homologadas.

## 1. Cobrança e assinaturas --- CONCLUÍDO

Stripe já implementado e validado em operação real.

-   [x] Integração Stripe
-   [x] Pagamentos reais
-   [x] Assinaturas
-   [x] Renovação
-   [x] Cancelamento
-   [x] Webhooks
-   [x] Tratamento/monitoramento de webhooks
-   [x] MRR/ARR no Admin Master
-   [x] Estados das assinaturas
-   [x] Testes dos principais fluxos

**Pendência para lançamento:** apenas regressão/smoke test final. Não é
uma nova fase de desenvolvimento.

## 2. Arquitetura Multi-tenant --- ESSENCIALMENTE CONCLUÍDA

A estrutura já utiliza mecanismos como:

-   [x] `TenantContext`
-   [x] `BelongsToTenant`
-   [x] Middleware de resolução de tenant
-   [x] Escopos por tenant
-   [x] Separação da área global `/platform`
-   [x] Consultas globais explicitamente usando `withoutGlobalScopes()`
    quando necessário

### Antes do lançamento

Não reconstruir essa arquitetura. Fazer somente auditoria final de
regressão/segurança, verificando especialmente acesso cruzado Tenant A →
Tenant B em operações sensíveis.

## 3. Admin Master --- EM ANDAMENTO

### Já concluído

-   [x] Login do Platform Admin
-   [x] Dashboard global
-   [x] Tenants
-   [x] Assinaturas
-   [x] Trials
-   [x] MRR
-   [x] ARR
-   [x] Uso global
-   [x] Contatos comerciais
-   [x] Saúde operacional
-   [x] Webhooks
-   [x] Retry de webhook
-   [x] Jobs pendentes no dashboard
-   [x] Jobs falhos no dashboard
-   [x] Falhas globais de e-mail
-   [x] Página de falhas de e-mail
-   [x] Falhas globais de WhatsApp
-   [x] Página de falhas de WhatsApp
-   [x] Alertas visuais no dashboard
-   [x] Retry seguro de e-mails falhos
-   [x] Retry seguro de WhatsApps falhos
-   [x] Gestão detalhada de jobs/failed jobs
-   [x] Retry individual de failed jobs
-   [x] Remoção segura individual de failed jobs
-   [x] Saúde operacional avançada
-   [x] Heartbeat do scheduler
-   [x] Heartbeat do worker
-   [x] Monitoramento de Stripe
-   [x] Monitoramento de configuração WhatsApp
-   [x] Estados operacionais `OK / Atenção / Crítico`
-   [x] Remoção segura individual de failed jobs
-   [x] Retry em lote avaliado e não adotado no lançamento por segurança operacional
-   [x] Visão detalhada por tenant
-   [x] Histórico de assinaturas por tenant
-   [x] Cobrança por tenant
-   [x] Falhas de e-mail e WhatsApp por tenant
-   [x] Webhooks relacionados por tenant
-   [x] Auditoria administrativa global
-   [x] Prorrogação de trial auditada
-   [x] Suspensão administrativa de tenant (`active` → `blocked`)
-   [x] Reativação administrativa de tenant (`blocked` → `active`)
-   [x] Auditoria de suspensão/reativação de tenant
-   [x] Bloqueio efetivo do workspace para tenant `blocked`
-   [x] Preservação da assinatura durante suspensão/reativação

### Ainda falta

-   [ ] Completar ações administrativas necessárias por tenant
-   [ ] Instrumentar auditoria nas demais ações administrativas
-   [ ] Revisão de segurança do Admin Master
-   [ ] Acabamento final da UX
-   [ ] Homologação completa do `/platform`

**Checkpoint da Fase 7B:** suspender/reativar tenant concluído e validado em produção.

-   Suspensão `active` → `blocked` validada.
-   Tenant `blocked` deixa de resolver o workspace.
-   Assinatura permanece inalterada durante suspensão.
-   Reativação `blocked` → `active` validada.
-   Workspace volta a responder após reativação.
-   Auditoria `tenant.suspended` e `tenant.reactivated` validada.
-   Deploy concluído com `DEPLOY_OK`.
-   Commit implantado: `a85a489`.
## 4. Auditoria completa do produto do tenant

Esta deve ser a primeira grande etapa depois do Admin Master.

Não presumir que funcionalidades estão faltando. Analisar o repositório
inteiro:

-   [ ] Routes
-   [ ] Controllers
-   [ ] Models
-   [ ] Services
-   [ ] Jobs
-   [ ] Events/listeners
-   [ ] Policies/middlewares
-   [ ] Migrations
-   [ ] Commands/scheduler
-   [ ] Views/frontend
-   [ ] APIs
-   [ ] Integrações
-   [ ] Configurações
-   [ ] Testes

Cada módulo deverá receber uma classificação:

-   `CONCLUÍDO`
-   `PARCIAL`
-   `FALTANDO`
-   `PRECISA HOMOLOGAÇÃO`
-   `PÓS-LANÇAMENTO`

E uma prioridade:

-   `P0` = bloqueia lançamento
-   `P1` = importante, mas lançamento pode ser avaliado
-   `P2` = melhoria pós-lançamento

Essa auditoria deverá gerar o roadmap técnico final de lançamento.

## 5. E-mail e WhatsApp --- Homologação operacional

Grande parte já existe. Depois do Admin Master, confirmar o
comportamento real dos provedores.

### E-mail

-   [ ] Envio real
-   [ ] Falha real
-   [ ] Retry
-   [ ] Idempotência
-   [ ] Provider indisponível
-   [ ] Registro correto dos erros
-   [ ] Monitoramento pelo Admin Master

### WhatsApp

-   [ ] Envio real
-   [ ] Status do provider
-   [ ] Delivered/read, quando suportado
-   [ ] Falha real
-   [ ] Retry
-   [ ] Idempotência
-   [ ] Provider indisponível
-   [ ] Monitoramento pelo Admin Master

Se esses testes já tiverem sido realizados, durante a auditoria os
respectivos itens devem ser marcados como concluídos.

## 6. Infraestrutura e operação de produção

Garantir não apenas que a aplicação funciona, mas que pode ser
recuperada quando algo dá errado.

### Containers já observados

-   [x] Backend
-   [x] Nginx
-   [x] PostgreSQL
-   [x] Worker
-   [x] Scheduler
-   [x] Health checks básicos

### Verificação final

-   [ ] Backup automático confirmado
-   [ ] Teste real de restauração do backup
-   [ ] Procedimento de rollback
-   [ ] Monitoramento externo
-   [ ] Alertas de indisponibilidade
-   [ ] Logs
-   [ ] Rotação/retenção de logs
-   [ ] Espaço em disco
-   [ ] Certificado HTTPS/renovação
-   [ ] Cenário de worker parado
-   [ ] Cenário de scheduler parado
-   [ ] Cenário de banco indisponível
-   [ ] Procedimento de recuperação de desastre

O teste de restauração é especialmente importante: ter backup sem provar
que ele restaura corretamente não fecha a estratégia de recuperação.

## 7. Auditoria final de segurança

Não reconstruir o sistema de segurança. Fazer revisão pré-lançamento.

-   [ ] Autenticação
-   [ ] Autorização
-   [ ] Sessões
-   [ ] CSRF
-   [ ] Rate limiting
-   [ ] Recuperação de senha
-   [ ] Uploads
-   [ ] Endpoints públicos
-   [ ] APIs
-   [ ] Secrets
-   [ ] Logs sensíveis
-   [ ] Platform Admin
-   [ ] Ações destrutivas
-   [ ] Testes Tenant A → Tenant B
-   [ ] Tentativas de IDOR
-   [ ] Exposição indevida de informações
-   [ ] Configuração de produção

Aqui também será confirmada definitivamente a segurança do isolamento
multi-tenant já implementado.

## 8. LGPD, legal e operação

Verificar primeiro o que já existe.

-   [ ] Política de Privacidade
-   [ ] Termos de Uso
-   [ ] Tratamento de dados pessoais
-   [ ] Exclusão de conta/dados
-   [ ] Exportação quando aplicável
-   [ ] Retenção de dados
-   [ ] Cookies, se aplicável
-   [ ] Contato para privacidade
-   [ ] Procedimento de incidente
-   [ ] Procedimento de suporte
-   [ ] Procedimento de cancelamento

O que já estiver implementado será marcado como concluído durante a
auditoria.

## 9. Homologação completa

Quando os P0 estiverem resolvidos, executar uma rodada de homologação
como se a plataforma estivesse sendo usada por clientes reais.

### Jornada de cliente

-   [ ] Cadastro/onboarding
-   [ ] Login
-   [ ] Configuração inicial
-   [ ] Usuários/permissões
-   [ ] Operações principais do produto
-   [ ] E-mail
-   [ ] WhatsApp
-   [ ] Assinatura
-   [ ] Pagamento
-   [ ] Renovação
-   [ ] Cancelamento
-   [ ] Logout

### Jornada administrativa

-   [ ] Login Platform Admin
-   [ ] Dashboard
-   [ ] Tenants
-   [ ] Assinaturas
-   [ ] Contatos
-   [ ] Saúde
-   [ ] Webhooks
-   [ ] Jobs
-   [ ] E-mails falhos
-   [ ] WhatsApps falhos
-   [ ] Retries
-   [ ] Auditoria

### Falhas simuladas

-   [ ] E-mail falha
-   [ ] WhatsApp falha
-   [ ] Job falha
-   [ ] Webhook falha
-   [ ] Provider indisponível
-   [ ] Operação duplicada
-   [ ] Acesso não autorizado

## 10. Gate de lançamento

Antes do go-live:

-   [ ] Nenhum P0 aberto
-   [ ] Suíte completa verde
-   [ ] Migrations revisadas
-   [ ] Backup confirmado
-   [ ] Restauração testada
-   [ ] Rollback documentado/testado
-   [ ] Segurança revisada
-   [ ] Multi-tenancy validado
-   [ ] Providers externos validados
-   [ ] Admin Master homologado
-   [ ] Produto do tenant homologado
-   [ ] Produção saudável
-   [ ] Smoke test Stripe
-   [ ] Smoke test e-mail
-   [ ] Smoke test WhatsApp
-   [ ] Monitoramento ativo
-   [ ] Procedimento de suporte definido

**Resultado:** GO / NO-GO.

## 11. Lançamento

Com o gate aprovado:

**Noobstron → produção comercial.**

Depois disso, itens não críticos passam para um roadmap pós-lançamento,
sem bloquear a entrada dos primeiros clientes.

## Sequência recomendada

1.  Terminar Admin Master.
2.  Auditoria completa do repositório/produto.
3.  Criar lista real P0 / P1 / P2.
4.  Resolver P0.
5.  Infraestrutura + segurança + LGPD.
6.  Homologação completa.
7.  Gate GO/NO-GO.
8.  Lançamento.

## Checkpoint atual

> **Admin Master: recuperação operacional, gestão de filas, saúde operacional avançada, visão detalhada de tenants e fundação da auditoria administrativa concluídas em produção. Commit atual: `4d23547`. Última suíte completa: 2008 testes / 4891 assertions. Próxima etapa: ações administrativas auditadas por tenant.**
