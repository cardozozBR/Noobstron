# ROADMAP — Nossa Plataforma

## 🎯 Objetivo

Construir uma plataforma multi-tenant estável, segura e preparada para evolução,
mantendo cada etapa validada antes de avançar.

---

# 📍 Estado atual

**Fase:** Administração da nossa plataforma

**Etapa atual:** 16.1 — Painel global

**Concluído recentemente:**

- 15.1 — Site público
- 15.2 — Cadastro self-service
- Refinamento pós-15.2 — integração Home + self-service
- 15.3 — Onboarding

**Em andamento:**

- 16.1 — Painel global

**Próxima etapa:** 16.1 — Painel global

**Último marco Git:**

`28cb29c docs: close onboarding milestone`

---

# 🟡 FASE 1 — ESTABILIZAÇÃO E BASELINE

## 1.1 — Corrigir e validar testes

- [x] Identificar todos os testes existentes
- [x] Remover/corrigir testes com erro de sintaxe
- [x] Garantir que todos os testes carregam corretamente
- [x] `php artisan test` totalmente verde
- [x] Registrar quantidade final de testes e assertions — 13 testes / 19 assertions

## 1.2 — Validar qualidade do código

- [x] `git diff --check` sem erros
- [x] Corrigir trailing whitespace
- [x] Corrigir problemas de newline/EOF
- [x] Revisar formatação dos arquivos alterados
- [x] Confirmar que não existem arquivos de debug esquecidos

## 1.3 — Validar infraestrutura Laravel

- [x] Conferir rotas
- [x] Conferir migrations
- [x] Conferir seeders
- [x] Conferir middleware
- [x] Conferir configuração de autenticação
- [x] Conferir configuração de tenant

## 1.4 — Validar multi-tenancy

- [x] Tenant é resolvido pelo hostname
- [x] Tenant inativo não pode ser acessado
- [x] Tenant inexistente retorna 404
- [x] Consultas são isoladas por tenant
- [x] Criação de registros respeita o tenant atual
- [x] Usuário de outro tenant não pode ser acessado

## 1.5 — Validar permissões

- [x] Usuário autorizado acessa usuários
- [x] Usuário sem permissão recebe 403
- [x] Usuário autorizado acessa auditoria
- [x] Usuário sem permissão recebe 403
- [x] Permissões permanecem isoladas por tenant

## 1.6 — Criar baseline

- [x] Toda a suíte de testes verde
- [x] `git diff --check` limpo
- [x] `git status` revisado
- [x] Commit de baseline criado
- [x] ROADMAP atualizado
- [x] JOURNEY atualizado

---

# 🟡 FASE 2 — AUDITORIA

## 2.1 — Validar estrutura dos eventos

- [x] Eventos oficiais definidos
- [x] Login
- [x] Logout
- [x] Falha de login
- [x] Alteração de perfil
- [x] Criação de usuário
- [x] Atualização de usuário
- [x] Exclusão de usuário
- [x] Alteração de role
- [x] Alteração de permissões

## 2.2 — Validar segurança da auditoria

- [x] tenant_id preservado
- [x] user_id preservado quando aplicável
- [x] Eventos de sistema suportados
- [x] Senhas nunca registradas
- [x] Dados sensíveis não registrados

## 2.3 — Melhorar filtros

- [x] Filtro por usuário
- [x] Filtro por ação
- [x] Filtro por período
- [x] Busca por descrição
- [x] Filtro usuário/sistema
- [x] Limpar filtros
- [x] Preservar filtros na paginação

## 2.4 — Testes da auditoria

- [x] Criar suíte específica de auditoria
- [x] Testar filtros
- [x] Testar isolamento por tenant
- [x] Testar eventos sem usuário
- [x] Testar paginação
- [x] Teste final da auditoria
---

# 🟡 FASE 3 — USUÁRIOS E CONTROLE DE ACESSO

3.1 — Usuários
[x] Listagem
[x] Criação
[x] Edição
[x] Exclusão
[x] Validações
[x] Isolamento por tenant

3.2 — Roles
[x] Revisar roles existentes
[x] Regras por role
[x] Testes de acesso
[x] Auditoria das alterações

3.3 — Permissões
[x] Revisar catálogo
[x] Revisar associação usuário/permissão
[x] Revisar middleware
[x] Testes completos

---

# 🟡 FASE 4 — EXPERIÊNCIA E INTERFACE

- [x] Dashboard
- [x] Usuários
- [x] Auditoria
- [x] Perfil
- [x] Estados vazios
- [x] Mensagens de erro
- [x] Responsividade
- [x] Padronização visual

---

# 🟡 FASE 5 — ROBUSTEZ

- [x] Testes de regressão
- [x] Validação de entradas
- [x] Tratamento de exceções
- [x] Logs
- [x] Performance
- [x] Índices de banco
- [x] Segurança
- [x] Revisão de queries

---

# 🟡 FASE 6 — PREPARAÇÃO PARA PRODUÇÃO

- [x] Configuração de produção
- [x] Variáveis de ambiente
- [x] Banco
- [x] Cache
- [x] Filas
- [x] Storage
- [x] Backup
- [x] Monitoramento
- [x] Health check
- [x] Deploy

---
---

# 🟡 FASE 7 — FUNDAÇÃO GLOBAL E INTERNACIONALIZAÇÃO

## 7.1 — Configurações globais do tenant

- [x] País
- [x] Idioma principal
- [x] Locale
- [x] Timezone
- [x] Moeda padrão
- [x] Preferências regionais
- [x] Testes de isolamento por tenant

## 7.2 — Internacionalização da interface

- [x] Estrutura de traduções
- [x] Português pt-BR
- [x] Inglês en
- [x] Espanhol es
- [x] Japonês ja
- [x] Chinês simplificado zh-CN
- [x] Mensagens de validação traduzíveis
- [x] Política de internacionalização para futuros e-mails e notificações
- [x] Fallback de idioma
- [x] Testes

## 7.3 — Moedas e valores monetários

- [x] ISO 4217
- [x] Valor monetário seguro
- [x] Formatação por locale
- [x] BRL
- [x] USD
- [x] EUR
- [x] JPY
- [x] CNY
- [x] Testes de arredondamento
- [x] Testes de conversão visual

## 7.4 — Países, endereços e telefones

- [x] País ISO
- [x] Endereço internacional
- [x] Telefone internacional
- [x] Código de país
- [x] Documento fiscal extensível
- [x] CPF/CNPJ como regra regional brasileira
- [x] Testes

## 7.5 — Datas, horas e timezone

- [x] Persistência de timestamps em UTC
- [x] Runtime global da aplicação em UTC
- [x] Timezone configurável por tenant
- [x] Conversão de UTC para timezone do tenant
- [x] Conversão de data/hora local para UTC
- [x] Filtros de data respeitando timezone do tenant
- [x] Formatação de datas por tenant
- [x] Suporte a dias com transição de DST
- [x] Normalização segura de timestamps legados
- [x] Testes

## 7.6 — Preferências e branding

- [x] Nome comercial
- [x] Logo
- [x] Cor/identidade básica
- [x] Configurações do tenant
- [x] Preferências de exibição
- [x] Testes

## 7.7 — Feature flags e capacidades

- [x] Catálogo de features
- [x] Feature por tenant
- [x] Limites por recurso
- [x] Estrutura preparada para planos
- [x] Auditoria
- [x] Testes

## 7.8 — Validação da fundação global

- [x] Testes completos
- [x] Regressão multi-tenant
- [x] Segurança
- [x] Performance
- [x] Documentação
- [x] Validação final

---

# 🟡 FASE 8 — CRM E CLIENTES

## 8.1 — Leads

- [x] Cadastro
- [x] Edição
- [x] Exclusão
- [x] Status
- [x] Origem
- [x] Responsável
- [x] Tags
- [x] Observações
- [x] Isolamento por tenant
- [x] Auditoria
- [x] Testes

## 8.2 — Contatos e clientes

- [x] Pessoa física
- [x] Pessoa jurídica
- [x] Contatos
- [x] Telefones
- [x] E-mails
- [x] Endereços
- [x] Histórico
- [x] Tags
- [x] Busca
- [x] Filtros
- [x] Testes

## 8.3 — Conversão de lead

- [x] Lead para cliente
- [x] Preservação de histórico
- [x] Auditoria
- [x] Testes

## 8.4 — Importação

- [x] CSV
- [x] Validação
- [x] Preview
- [x] Erros de importação
- [x] Jobs em fila
- [x] Auditoria
- [x] Testes

---

# 🟡 FASE 9 — PIPELINE COMERCIAL

## 9.1 — Pipelines

- [x] Múltiplos pipelines
- [x] Etapas configuráveis
- [x] Ordem das etapas
- [x] Pipeline padrão
- [x] Testes

## 9.2 — Oportunidades

- [x] Criação
- [x] Valor
- [x] Responsável
- [x] Cliente
- [x] Etapa
- [x] Probabilidade
- [x] Data prevista
- [ ] Motivo de perda
- [x] Auditoria
- [x] Feature flag
- [x] Permissões
- [x] Isolamento por tenant
- [x] HTTP
- [x] Interface
- [x] Internacionalização
- [x] Testes

## 9.3 — Atividades e tarefas

- [x] Tarefas
- [x] Ligações
- [x] Reuniões
- [x] Follow-up
- [x] Vencimento
- [x] Responsável
- [x] Cliente
- [x] Oportunidade
- [x] Status
- [x] Conclusão
- [x] Reabertura
- [x] Cancelamento
- [x] Feature flag
- [x] Permissões
- [x] Isolamento por tenant
- [x] HTTP
- [x] Interface
- [x] Internacionalização
- [x] Lembretes de vencimento
- [x] Scheduler de lembretes
- [x] Notificações
- [x] Inbox de notificações
- [x] Testes

## 9.4 — Dashboard comercial

- [x] Leads
- [x] Métricas de oportunidades
- [x] Conversão
- [x] Pipeline por etapa
- [x] Valor em aberto
- [x] Vendas por responsável
- [x] Métricas de atividades
- [x] Atividades atrasadas
- [x] Próximas atividades
- [x] Isolamento por tenant
- [x] DashboardService
- [x] Testes do serviço
- [x] Integração com controller
- [x] Interface
- [x] Internacionalização
- [x] Testes HTTP
- [x] Regressão final

---

# 🟡 FASE 10 — PRODUTOS, SERVIÇOS E PROPOSTAS

## 10.1 — Catálogo

- [x] Produtos
- [x] Serviços
- [x] SKU/código
- [x] Preço
- [x] Moeda
- [x] Status
- [x] Testes

## 10.2 — Propostas e orçamentos

- [x] Criação
- [x] Itens
- [x] Quantidade
- [x] Desconto
- [x] Impostos extensíveis
- [x] Validade
- [x] Status
- [x] PDF
- [x] Envio
- [x] Auditoria
- [x] Testes

## 10.3 — Fechamento de venda

- [x] Proposta aceita
- [x] Proposta recusada
- [x] Conversão em venda
- [x] Histórico
- [x] Testes

---

# 🟡 FASE 11 — FINANCEIRO OPERACIONAL

## 11.1 — Contas a receber

- [x] Título
- [x] Cliente
- [x] Valor
- [x] Moeda
- [x] Vencimento
- [x] Status
- [x] Pagamento
- [x] Testes

## 11.2 — Cobranças

- [x] Geração
- [x] Histórico
- [x] Lembretes
- [x] Atrasos
- [x] Recorrência
- [x] Testes

## 11.3 — Indicadores financeiros

- [x] Recebido
- [x] A receber
- [x] Atrasado
- [x] Receita por período
- [x] Receita por cliente
- [x] Testes

---

# 🟡 FASE 12 — COMUNICAÇÃO OMNICHANNEL

## 12.1 — E-mail

- [x] Configuração
- [x] Templates
- [x] Envio
- [x] Histórico
- [x] Fila
- [x] Testes

## 12.2 — WhatsApp

- [x] Provedor
- [x] Conexão por tenant
- [x] Templates
- [x] Envio
- [x] Recebimento
- [x] Webhooks
- [x] Histórico
- [x] Auditoria
- [x] Testes

## 12.3 — Caixa de entrada

- [x] Conversas
- [x] Responsável
- [x] Status
- [x] Associação a lead/cliente
- [x] Busca
- [x] Filtros
- [x] Testes

---

# 🟡 FASE 13 — AUTOMAÇÕES E WORKFLOWS

## 13.1 — Gatilhos

- [x] Lead criado
- [x] Etapa alterada
- [x] Proposta enviada
- [x] Pagamento vencido
- [x] Cliente inativo
- [x] Eventos customizáveis

## 13.2 — Condições

- [x] Campos
- [x] Status
- [x] Valor
- [x] Tempo
- [x] Responsável
- [x] Segmentação

## 13.3 — Ações

- [x] Criar tarefa
- [x] Enviar e-mail
- [x] Enviar WhatsApp
- [x] Alterar etapa
- [x] Atribuir responsável
- [x] Criar notificação
- [x] Webhook externo

## 13.4 — Execução

- [x] Queue
- [x] Retry
- [x] Idempotência
- [x] Logs
- [x] Auditoria
- [x] Testes

---

# 🟡 FASE 14 — SAAS BILLING

## 14.1 — Planos

- [x] Plano Start
- [x] Plano Pro
- [x] Plano Business
- [x] Plano Enterprise
- [x] Recursos por plano
- [x] Limites por plano
- [x] Preços por moeda

## 14.2 — Trial

- [x] Período de teste
- [x] Início
- [x] Expiração
- [x] Conversão
- [x] Bloqueio controlado

## 14.3 — Assinaturas

- [x] Ativa
- [x] Cancelada
- [x] Suspensa
- [x] Vencida
- [x] Upgrade
- [x] Downgrade
- [x] Renovação
- [x] Testes

## 14.4 — Pagamentos

- [x] Provedor de pagamento
- [x] Checkout
- [x] Webhooks
- [x] Pagamento aprovado
- [x] Pagamento falho
- [x] Reembolso
- [x] Idempotência
- [x] Auditoria
- [x] Testes

## 14.5 — Uso e limites

- [x] Usuários
- [x] Storage
- [x] Mensagens
- [x] IA
  - [x] Fundação multi-tenant de IA
  - [x] Configuração e adapter do provider OpenAI
  - [x] Limites, contabilização e enforcement de uso
  - [x] Governança por feature e permissão
  - [x] Serviço de AI Assistant para rewrite
  - [x] Endpoint governado `POST /ai/rewrite`
  - [x] Rewrite no composer do WhatsApp
  - [x] Rewrite no composer de e-mail
  - [x] Rewrite em templates de e-mail
  - [x] Rewrite em notas de propostas
  - [x] Rewrite em notas de oportunidades
  - [x] Rewrite em descrição de atividades
  - [x] Rewrite em notas de leads
  - [x] Rewrite em notas de clientes
  - [ ] Opcional: rewrite em notas de contatos de clientes
  - [ ] Opcional: rewrite em descrição de pipelines
  - [ ] Opcional: rewrite em templates de WhatsApp
- [x] Features premium
- [x] Bloqueio elegante
- [x] Upgrade sugerido

---

# 🟡 FASE 15 — SELF-SERVICE E ONBOARDING

## 15.1 — Site comercial

- [x] Home
- [x] Recursos
- [x] Preços
- [x] FAQ
- [x] Contato
- [x] SEO básico

## 15.2 — Cadastro self-service

- [x] Criar conta
- [x] Verificar e-mail
- [x] Criar tenant
- [x] Escolher país
- [x] Escolher idioma
- [x] Escolher plano
- [x] Trial
- [x] Testes

### Refinamento pós-15.2 — integração Home + self-service

- [x] Hero da Home direciona para o trial self-service
- [x] Start, Pro e Business direcionam para `/register`
- [x] Enterprise permanece em fluxo comercial
- [x] Contato permanece exclusivamente comercial
- [x] CTAs dos planos usam padrão visual consistente
- [x] `/register` apresenta resumo de plano e trial
- [x] Regressões de marketing e cadastro validadas
## 15.3 — Onboarding

- [x] Dados da empresa
- [x] Segmento
- [x] Equipe
- [x] Pipeline inicial
- [x] Importação
- [x] Checklist
- [x] Primeiro valor percebido

---

# 🟡 FASE 16 — ADMINISTRAÇÃO DA NOSSA PLATAFORMA

## 16.1 — Painel global

- [ ] Tenants
- [x] Usuários
- [ ] Assinaturas
- [ ] Receita
- [ ] MRR
- [ ] ARR
- [ ] Trials
- [ ] Churn
- [ ] Uso

## 16.2 — Saúde operacional

- [ ] Serviços
- [ ] Filas
- [ ] Failed jobs
- [ ] Backups
- [x] Storage
- [ ] Integrações
- [ ] Incidentes

## 16.3 — Suporte interno

- [ ] Busca de tenant
- [ ] Diagnóstico
- [ ] Impersonation segura
- [x] Auditoria de suporte
- [ ] Bloqueios administrativos

---

# 🟡 FASE 17 — AGENTE DE SUPORTE DA NOSSA PLATAFORMA

- [ ] Base de conhecimento
- [ ] Chat de suporte
- [ ] Contexto do tenant
- [ ] Consulta de plano
- [ ] Consulta de assinatura
- [ ] Consulta de usuários
- [ ] Diagnóstico automático
- [ ] Ações seguras
- [ ] Confirmação para ações sensíveis
- [ ] Escalonamento humano
- [x] Auditoria
- [x] Testes

---

# 🟡 FASE 18 — AGENTE DE ATENDIMENTO PARA CLIENTES

- [ ] Agente por tenant
- [ ] Personalidade configurável
- [ ] Base de conhecimento por empresa
- [ ] WhatsApp
- [ ] Chat web
- [ ] Captação de lead
- [ ] Qualificação
- [ ] Agendamento
- [ ] Follow-up
- [ ] Handoff para humano
- [ ] Limites por plano
- [ ] Métricas
- [x] Auditoria
- [x] Testes

---

# 🟡 FASE 19 — IA E INTELIGÊNCIA COMERCIAL

- [ ] Resumo de conversas
- [ ] Lead scoring
- [ ] Sugestão de próxima ação
- [ ] Geração de respostas
- [ ] Geração de propostas
- [ ] Análise de pipeline
- [ ] Previsão de vendas
- [ ] Clientes em risco
- [ ] Reativação
- [ ] Copiloto de gestão
- [ ] Controle de custos de IA
- [ ] Limites por tenant
- [ ] Segurança
- [x] Testes

---

# 🟡 FASE 20 — EXPANSÃO INTERNACIONAL

- [ ] Novos idiomas
- [ ] Novas moedas
- [ ] Novos meios de pagamento
- [ ] Billing internacional
- [ ] Formatos fiscais regionais
- [ ] Domínios regionais
- [ ] Conteúdo comercial por país
- [ ] Suporte internacional
- [x] Testes regionais

---

# 🟡 FASE 21 — PRIVACIDADE, COMPLIANCE E SEGURANÇA AVANÇADA

- [ ] LGPD
- [ ] GDPR
- [ ] Consentimentos
- [ ] Política de retenção
- [ ] Exportação de dados
- [ ] Direito de exclusão
- [ ] Registro de consentimento
- [ ] Segurança de integrações
- [ ] Gestão de secrets
- [ ] Rate limiting avançado
- [ ] MFA
- [ ] Sessões e dispositivos
- [x] Auditoria administrativa
- [x] Testes de segurança

---

# 🟡 FASE 22 — ESCALA E ALTA DISPONIBILIDADE

- [ ] Redis
- [ ] Queue dedicada
- [ ] Workers escaláveis
- [x] Storage externo
- [ ] Banco gerenciado
- [ ] Read replicas quando necessário
- [ ] CDN
- [ ] Observabilidade externa
- [ ] Métricas
- [ ] Alertas
- [ ] Autoscaling
- [ ] Disaster recovery
- [x] Testes de carga
- [x] Testes de recuperação

---

# 🟡 FASE 23 — PRODUTO COMERCIAL FINAL / LANÇAMENTO

## 23.1 — QA final

- [ ] Regressão completa
- [x] Testes de segurança
- [x] Testes de carga
- [x] Testes multi-tenant
- [x] Testes de billing
- [x] Testes de onboarding
- [x] Testes de restore

## 23.2 — Comercial

- [ ] Pricing final
- [ ] Planos finais
- [ ] Política de trial
- [ ] Página de preços
- [ ] Termos
- [ ] Privacidade
- [ ] Contratos necessários

## 23.3 — Operação

- [ ] Runbooks
- [ ] Suporte
- [ ] SLA quando aplicável
- [ ] Processo de incidentes
- [ ] Processo de backup
- [ ] Processo de restore

## 23.4 — Lançamento

- [ ] Clientes piloto
- [ ] Feedback estruturado
- [ ] Correções
- [ ] Primeiros clientes pagantes
- [ ] Métricas de aquisição
- [ ] Métricas de retenção
- [ ] Métricas de receita
- [ ] Go-live comercial

---

# 📊 REGRA DE PROGRESSO

Uma etapa só será marcada como concluída quando:

1. implementação estiver funcionando;
2. testes relacionados estiverem verdes;
3. `git diff --check` estiver limpo;
4. não houver debug temporário;
5. segurança e isolamento multi-tenant forem preservados;
6. migrations forem reversíveis quando aplicável;
7. estado estiver registrado no `ROADMAP.md`;
8. progresso estiver registrado no `JOURNEY.md`;
9. validação real for executada quando a etapa envolver infraestrutura, billing, automação ou integrações.

---

# 🎯 PRINCÍPIO DO PRODUTO

A Nossa Plataforma deve evoluir de uma fundação técnica multi-tenant para um SaaS B2B global capaz de:

- captar clientes;
- organizar vendas;
- controlar operação;
- cobrar;
- automatizar processos;
- atender usuários;
- oferecer agentes de IA;
- operar com baixa intervenção manual;
- crescer internacionalmente;
- gerar receita recorrente.

---

# 🚦 PRÓXIMO PASSO

## 13.3 — Ações

A etapa 13.2 — Condições foi concluída.

A fundação de automações agora consegue avaliar condições sobre o contexto produzido pelos gatilhos.

Foram concluídas condições para:

- campos;
- status;
- valor;
- tempo;
- responsável;
- segmentação.

A próxima etapa deve implementar as ações executáveis dos workflows:

- criar tarefa;
- enviar e-mail;
- enviar WhatsApp;
- alterar etapa;
- atribuir responsável;
- criar notificação;
- webhook externo.

A implementação deve preservar:

- isolamento multi-tenant;
- integração com gatilhos e condições;
- idempotência;
- segurança entre domínios;
- auditoria quando aplicável;
- processamento assíncrono quando necessário;
- testes.
