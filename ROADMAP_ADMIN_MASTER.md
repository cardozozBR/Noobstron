# Roadmap --- Painel de Administração Master

## Objetivo final

Transformar `/platform` em um painel seguro para administrar tenants,
assinaturas, receita, saúde da infraestrutura, integrações, falhas
operacionais e suporte, reduzindo a necessidade de acesso direto ao
banco ou ao servidor para operações rotineiras.

## Fase 1 --- Dashboard global

**Status: concluída e em produção**

-   [x] Autenticação do administrador da plataforma
-   [x] Visão global de tenants
-   [x] Assinaturas ativas
-   [x] Assinaturas suspensas
-   [x] Trials ativos
-   [x] Trials vencendo
-   [x] Usuários globais
-   [x] MRR contratual
-   [x] ARR
-   [x] Uso global
-   [x] Resumo das assinaturas
-   [x] Atalhos para Tenants, Contatos, Saúde e Webhooks

## Fase 2 --- Monitoramento operacional

**Status: concluída e em produção**

-   [x] Webhooks falhos
-   [x] Webhooks em processamento
-   [x] Destaque visual quando existem falhas
-   [x] Jobs pendentes
-   [x] Jobs falhos
-   [x] Falhas de e-mail
-   [x] Falhas de WhatsApp
-   [x] Cards com estado saudável quando contador = `0`

O dashboard já funciona como um painel de alerta operacional.

## Fase 3 --- Investigação de falhas

**Status: concluída e em produção**

### Webhooks

-   [x] Lista global
-   [x] Filtro de falhas
-   [x] Visualização operacional
-   [x] Retry de webhook
-   [x] Retry somente quando existe payload válido

### E-mail

-   [x] Card clicável
-   [x] Página global `/platform/email-failures`
-   [x] Tenant da mensagem
-   [x] Destinatário
-   [x] Assunto
-   [x] Motivo da falha
-   [x] Data da falha
-   [x] Paginação

### WhatsApp

-   [x] Card clicável
-   [x] Página global `/platform/whatsapp-failures`
-   [x] Tenant
-   [x] Destinatário
-   [x] Telefone
-   [x] Mensagem
-   [x] Provider
-   [x] Motivo da falha
-   [x] Data da falha
-   [x] Paginação

## Fase 4 --- Recuperação operacional

**Status: concluída e em produção**

O objetivo é fazer o painel deixar de apenas detectar problemas e passar
a permitir a recuperação segura.

### E-mail

-   [x] Investigar o mecanismo atual de envio de e-mail
-   [x] Implementar retry seguro de e-mail falho
-   [x] Adicionar botão **Reprocessar**
-   [x] Evitar reenvio duplicado/acidental
-   [x] Registrar resultado do retry
-   [x] Criar testes Feature

### WhatsApp

-   [x] Investigar o mecanismo atual de envio de WhatsApp
-   [x] Implementar retry seguro de WhatsApp
-   [x] Adicionar botão **Reprocessar**
-   [x] Tratar falhas do provider
-   [x] Evitar duplicidade
-   [x] Criar testes Feature

**Prioridade: alta.**

## Fase 5 --- Gestão da fila

**Status: concluída e em produção**

-   [x] Criar página `/platform/jobs`
-   [x] Listar jobs pendentes
-   [x] Listar jobs falhos
-   [x] Exibir nome/tipo do job
-   [x] Exibir data/hora da falha
-   [x] Exibir erro de forma segura
-   [x] Retry individual
-   [x] Avaliar retry em lote — avaliado e não recomendado para o lançamento por segurança operacional
-   [x] Permitir remoção segura de failed job
-   [x] Adicionar confirmações para operações perigosas
-   [x] Criar testes

Objetivo: reduzir a necessidade de entrar no servidor para executar
operações como `queue:retry`.

## Fase 6 --- Saúde operacional avançada

**Status: concluída e em produção**

-   [x] Banco de dados
-   [x] Storage
-   [x] Fila
-   [x] Failed jobs
-   [x] Configuração de e-mail
-   [x] Destinatário comercial
-   [x] Worker ativo/saudável
-   [x] Scheduler saudável
-   [x] Última execução esperada do scheduler
-   [x] Stripe configurado
-   [x] WhatsApp/provider configurado
-   [x] Estados visuais `OK / Atenção / Crítico`
-   [x] Timestamp da verificação
-   [x] Testes

Validação concluída em produção:

-   Commit: `e6625d9`
-   Suíte completa: `2002 passed (4844 assertions)`
-   Banco, storage, fila, scheduler e worker validados
-   Heartbeats automáticos de scheduler e worker validados
-   Stripe detectado como configurado
-   WhatsApp detectado corretamente conforme configuração dos tenants
-   Estados `OK / Atenção / Crítico` validados
-   Smoke test visual de `/platform/health` aprovado
## Fase 7 --- Gestão avançada de tenants

**Status: suspensão/reativação, cancelamento administrativo e correção administrativa de plano implementados e validados.**

### Visão detalhada

-   [x] Tela detalhada do tenant
-   [x] Dados cadastrais
-   [x] Status
-   [x] Plano atual
-   [x] Assinatura atual
-   [x] Histórico de assinaturas
-   [x] Trial
-   [x] Usuários
-   [x] Uso
-   [x] E-mails falhos do tenant
-   [x] WhatsApps falhos do tenant
-   [x] Webhooks relacionados
-   [x] Informações de cobrança
-   [x] Atalhos operacionais

### Ações administrativas a avaliar

-   [x] Suspender tenant
-   [x] Reativar tenant
-   [x] Alterar/prorrogar trial
-   [x] Cancelar assinatura
-   [x] Corrigir plano

Essas ações devem exigir confirmação e auditoria.

Validação concluída em produção:

-   Commit da visão detalhada: `40a4a65`
-   Commit de auditoria + prorrogação de trial: `4d23547`
-   Isolamento Tenant A → Tenant B validado por testes
-   Histórico de assinaturas, cobrança, falhas e webhooks relacionados validados em produção
-   Prorrogação de trial validada em produção
-   Auditoria da prorrogação validada em `platform_admin_audit_logs`
-   Atalhos operacionais por tenant validados em produção
-   Filtros por `tenant_id` validados para falhas de e-mail e WhatsApp
-   Filtro de webhooks por tenant validado via `external_reference` das assinaturas
-   Combinação de `tenant_id` com filtro de status de webhooks validada
-   Isolamento Tenant A → Tenant B validado nos atalhos operacionais
-   Commit implantado: `8fde793`

### Fase 7B — Suspensão e reativação de tenant

-   [x] Suspensão administrativa implementada como `active` → `blocked`
-   [x] Reativação administrativa implementada como `blocked` → `active`
-   [x] Ações protegidas por `platform.admin`
-   [x] Confirmação e motivo obrigatório
-   [x] Auditoria `tenant.suspended` e `tenant.reactivated`
-   [x] `before_state` e `after_state` registrados
-   [x] Tenant `blocked` deixa de resolver o workspace
-   [x] Assinatura existente permanece inalterada
-   [x] Cobertura específica: 10 testes / 46 assertions
-   [x] Regressão ampliada: 65 testes / 341 assertions
-   [x] `git diff --check` sem erros

Fase 7B validada em produção.

- Suspensão `active` → `blocked` validada.
- Tenant bloqueado deixa de resolver o workspace.
- Assinatura permanece inalterada durante suspensão.
- Reativação `blocked` → `active` validada.
- Workspace volta a responder após reativação.
- Auditoria `tenant.suspended` validada.
- Auditoria `tenant.reactivated` validada.
- Smoke realizado com tenant de teste em produção.
- Deploy concluído com `DEPLOY_OK`.
- Commit implantado: `a85a489`.

### Fase 7C — Cancelamento administrativo de assinatura Stripe

-   [x] Ação administrativa de cancelamento implementada
-   [x] Cancelamento configurado para o fim do período atual
-   [x] Assinatura permanece `active` até efetivação pela Stripe
-   [x] Integração com Stripe via `cancel_at_period_end=true`
-   [x] Motivo administrativo obrigatório
-   [x] Ação protegida por `platform.admin`
-   [x] Auditoria `subscription.cancellation_scheduled`
-   [x] `before_state` e `after_state` registrados
-   [x] Falha da Stripe não grava `cancel_at` localmente
-   [x] UI exibida apenas para assinatura Stripe ativa sem cancelamento agendado
-   [x] Testes automatizados concluídos
-   [x] Deploy de produção concluído com `DEPLOY_OK`
-   [x] Interface validada em produção
-   [x] Smoke da chamada externa de cancelamento em Stripe Test

**Checkpoint da Fase 7C:** cancelamento administrativo de assinatura Stripe implementado e implantado em produção.

-   Cancelamento solicitado ao fim do período atual.
-   Assinatura permanece `active` até efetivação pela Stripe.
-   Auditoria `subscription.cancellation_scheduled` implementada.
-   Motivo administrativo obrigatório.
-   Falha da Stripe não altera `cancel_at` localmente.
-   UI validada em produção para assinatura Stripe ativa.
-   Deploy concluído com `DEPLOY_OK`.
-   Commit implantado: `b66c0d1`.
-   Smoke destrutivo não executado em Stripe Live por segurança.
-   Chamada externa validada em Stripe Test com `cancel_at_period_end=true`.

### Fase 7D — Correção administrativa de plano

-   [x] Correção administrativa de plano implementada
-   [x] Ação protegida por `platform.admin`
-   [x] Motivo administrativo obrigatório
-   [x] Reutilização de `StripeSubscriptionPlanChangeService`
-   [x] Auditoria `subscription.plan_corrected`
-   [x] `before_state` e `after_state` registrados
-   [x] Falha da Stripe preserva o plano local
-   [x] Assinatura com cancelamento agendado não pode ter plano corrigido
-   [x] Interface validada em produção
-   [x] Smoke externo validado em Stripe Test
-   [x] Prorrata configurada com `create_prorations`

**Checkpoint da Fase 7D:** correção administrativa de plano implementada, implantada e validada.

-   Deploy funcional concluído no commit `8fbc066`.
-   Rota administrativa validada em produção.
-   Interface “Corrigir plano da assinatura” validada em produção.
-   Smoke destrutivo não executado em Stripe Live por segurança.
-   Smoke externo executado em Stripe Test.
-   Assinatura permaneceu `active`.
-   Preço Stripe alterado de `9900` para `24900`.
-   `price_1U6rw5Cyb3ZNP2kpSXvSqVuK` confirmado diretamente na Stripe Test.
-   Estado local só é atualizado após sucesso da Stripe.

**Status: concluída e validada, incluindo smoke externo em Stripe Test.**



## Fase 8 --- Auditoria administrativa

**Status: fundação concluída e em produção; instrumentação das ações em andamento**

Criada estrutura `platform_admin_audit_logs`.

Registrar, quando aplicável:

-   [x] Administrador
-   [x] Ação executada
-   [x] Tenant afetado
-   [x] Entidade afetada
-   [x] ID da entidade
-   [x] Estado anterior
-   [x] Estado posterior
-   [x] IP
-   [x] Data/hora
-   [x] Resultado
-   [x] Motivo/observação

### Eventos instrumentados

-   [x] Admin prorrogou trial
-   [x] Admin reprocessou webhook
-   [x] Admin reprocessou e-mail
-   [x] Admin reprocessou WhatsApp
-   [x] Admin suspendeu tenant
-   [x] Admin reativou tenant

Validação em produção:

-   Migration `2026_08_25_150000_create_platform_admin_audit_logs_table`
-   Commit: `4d23547`
-   Registro `tenant.trial_extended` validado em produção
-   `before_state` e `after_state` validados
-   IP, administrador, tenant e resultado validados
-   Auditoria `webhook.reprocessed` validada.
-   Auditoria `email.reprocessed` validada.
-   Auditoria `whatsapp.reprocessed` validada.
-   Administrador, tenant, entidade e resultado registrados em `platform_admin_audit_logs`.
-   Smoke controlado de `whatsapp.reprocessed` validado em produção com `Queue::fake()` e rollback.
-   Smoke controlado de `email.reprocessed` validado em produção com `Queue::fake()` e rollback.
-   Smoke controlado de `webhook.reprocessed` validado em produção com dados temporários e rollback.
-   Nenhum envio externo foi realizado nos smokes controlados.
-   Commit implantado: `3217734`.

## Fase 9 --- Segurança do Platform Admin

-   [x] Revisar todas as rotas protegidas por platform.admin
-   [x] Garantir isolamento correto de ResolveTenant
-   [x] Revisar rate limiting do login
-   [x] Revisar CSRF
-   [x] Revisar sessões
-   [x] Revisar logout
-   [x] Proteção contra enumeração
-   [x] Confirmação para ações destrutivas
-   [x] Revisão de exposição de payloads
-   [x] Garantir que secrets/tokens não sejam exibidos
-   [x] Sanitizar erros apresentados ao administrador
-   [x] Testes de autorização
-   [x] Garantir que administrador inativo não tenha acesso

Validação em produção:

-   Rotas `/platform` protegidas por `platform.admin` validadas em produção.
-   Login público e redirecionamento de rotas protegidas sem sessão validados.
-   Sessão em produção validada com `database`, `secure=true`, `http_only=true` e `same_site=lax`.
-   Sanitização de dados sensíveis validada em e-mail, WhatsApp e webhooks.
-   Secrets artificiais não foram renderizados nas telas administrativas.
-   `[REDACTED]` validado nas três superfícies operacionais.
-   Smoke executado com rollback, sem persistência dos dados temporários.
-   Commit implantado: `06c3be6`.

## Fase 10 --- Operação comercial

-   [ ] Melhorar gestão de contatos comerciais
-   [x] Status do lead
-   [ ] Histórico
-   [x] Tenant convertido
-   [x] Plano contratado
-   [x] Receita por plano
-   [x] Novas assinaturas
-   [ ] Cancelamentos
-   [ ] Trials convertidos
-   [ ] Trials expirados sem conversão
-   [ ] Churn básico
-   [ ] Indicadores comerciais no dashboard

## Fase 11 --- UX final do painel

-   [ ] Navegação consistente entre páginas
-   [ ] Menu do Admin Master
-   [ ] Breadcrumbs
-   [ ] Cards clicáveis com comportamento consistente
-   [ ] Estados vazios
-   [ ] Estados de erro
-   [ ] Badges
-   [ ] Tabelas responsivas
-   [ ] Paginação consistente
-   [ ] Confirmações de ações
-   [ ] Mensagens de sucesso/erro
-   [ ] Revisão mobile
-   [ ] Acessibilidade básica
-   [ ] Padronizar português e acentuação

## Fase 12 --- Fechamento para produção

-   [ ] Pint
-   [ ] `php -l`
-   [ ] Blade `view:cache`
-   [ ] `route:list`
-   [ ] Testes específicos do Platform Admin
-   [ ] Suíte completa
-   [ ] `git diff --check`
-   [ ] Revisão de segurança
-   [ ] Deploy
-   [ ] Health dos containers
-   [ ] Smoke test em produção
-   [ ] Testar login/logout
-   [ ] Dashboard
-   [ ] Tenants
-   [ ] Saúde
-   [ ] Webhooks
-   [ ] E-mails
-   [ ] WhatsApp
-   [ ] Jobs
-   [ ] Ações de retry
-   [ ] Auditoria

## Ordem recomendada daqui para frente

1.  **Fase 4:** retry seguro de e-mail e depois WhatsApp.
2.  **Fase 5:** gestão de jobs.
3.  **Fase 8:** auditoria administrativa.
4.  **Fase 7:** gestão avançada de tenants.
5.  **Fase 6:** saúde operacional avançada.
6.  **Fases 9 e 10:** segurança e operação comercial.
7.  **Fases 11 e 12:** acabamento visual, revisão e homologação.

## Checkpoint atual

> **Admin Master: Fases 1 a 6 concluídas em produção. Fase 7 com suspensão/reativação validadas em produção e cancelamento administrativo Stripe implementado e implantado. Fase 8 com fundação de auditoria administrativa concluída. Próxima etapa: validar o cancelamento administrativo em Stripe Test e seguir para a próxima ação avançada de tenant.**

### Última validação conhecida

-   Fase 7A read-only validada em produção.
-   Auditoria administrativa global validada em produção.
-   Prorrogação de trial auditada validada em produção.
-   Última suíte completa: **2008 testes aprovados / 4891 assertions**.
-   Último commit implantado: `4d23547` --- `add platform admin audit and trial extension`.
-   Próxima implementação: **suspender/reativar tenant com confirmação, auditoria e testes**.
### Última validação conhecida

-   Dashboard global em produção.
-   Monitoramento de webhooks em produção.
-   Retry de webhook em produção.
-   Monitoramento e retry de e-mail em produção.
-   Monitoramento e retry de WhatsApp em produção.
-   Página `/platform/jobs` em produção.
-   Retry individual de `failed_jobs` em produção.
-   Remoção segura individual de `failed_jobs` em produção.
-   Retry em lote avaliado e deliberadamente não adotado no lançamento por segurança operacional.
-   Última suíte completa informada: **1996 testes aprovados / 4820 assertions**.
-   Último commit implantado: `33cc15b` --- `add safe failed job removal to platform admin`.
-   Próxima fase: **Fase 6 — Saúde operacional avançada**.

