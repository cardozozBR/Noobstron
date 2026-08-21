# JOURNEY — Nossa Plataforma

## Estado atual

A plataforma está na Fase 9 — Pipeline Comercial.

As etapas 9.1 — Pipelines e 9.3 — Atividades e tarefas estão implementadas
e validadas.

A etapa 9.2 — Oportunidades possui o núcleo operacional completo, incluindo
governança, HTTP, auditoria, interface e internacionalização. O requisito
`Motivo de perda` permanece pendente no ROADMAP.

A etapa atual é 9.4 — Dashboard comercial.

O `DashboardService` já possui:

- métricas de oportunidades;
- valor total do pipeline;
- valor ponderado do pipeline;
- oportunidades agrupadas por etapa;
- métricas de atividades;
- atividades atrasadas;
- próximas atividades;
- isolamento por tenant;
- testes próprios e regressão CRM.

O próximo passo é integrar essas métricas ao dashboard existente, preservando
as métricas administrativas atuais.

---

## Baseline validada

### Testes

- 13 testes executados;
- 19 assertions;
- suíte completa verde;
- testes de multi-tenancy verdes;
- testes de permissões verdes.

### Multi-tenancy

- resolução do tenant pelo hostname;
- tenant ativo;
- tenant inexistente retorna 404;
- tenant inativo retorna 404;
- isolamento de usuários;
- criação de usuário respeitando o tenant atual;
- usuário de outro tenant não pode ser acessado.

### Permissões

- acesso autorizado à área de usuários;
- bloqueio sem permissão;
- acesso autorizado à auditoria;
- bloqueio à auditoria sem permissão;
- permissões vinculadas ao tenant atual.

### Infraestrutura Laravel

- rotas verificadas: 18 rotas;
- migrations verificadas: 9 migrations executadas;
- seeders verificados;
- middlewares verificados;
- autenticação verificada;
- configuração de tenant verificada.

### Qualidade

- `git diff --check` validado;
- trailing whitespace revisado;
- newline/EOF revisados;
- formatação revisada;
- ausência de arquivos de debug esquecidos confirmada.

---

## Marco atual

A Fase 1 está em fechamento de baseline.

A próxima etapa será a Fase 2 — Auditoria.

---

## Regra de trabalho

Cada etapa concluída deve atualizar:

- ROADMAP.md
- JOURNEY.md

E, quando fizer sentido, gerar um commit Git.

---

## Histórico

### Marco anterior



### Reset do roadmap

Novo planejamento iniciado após revisão do estado real do projeto.

---

## Fase 2 — Auditoria

A auditoria foi validada e sua suíte específica foi criada.

### Validações

- eventos oficiais definidos;
- login e logout;
- falha de login;
- alteração de perfil;
- criação, atualização e exclusão de usuário;
- alteração de role;
- alteração de permissões;
- isolamento por tenant;
- preservação de 	enant_id;
- preservação de user_id quando aplicável;
- eventos de sistema sem usuário;
- senhas e dados sensíveis não registrados;
- filtros por usuário, ação e período;
- busca por descrição;
- filtro usuário/sistema;
- limpeza e preservação dos filtros na paginação.

### Testes

Suíte completa:

18 testes
29 assertions

Resultado: **verde**.

A Fase 2 está concluída.

### Próxima etapa

**Fase 3 — Usuários e Controle de Acesso.**

---

## Fase 3 — Usuários e Controle de Acesso

A gestão de usuários e o controle de acesso foram validados.

### Validações

- listagem de usuários;
- criação de usuários;
- edição de usuários;
- exclusão de usuários;
- validações de entrada;
- isolamento por tenant;
- regras por role;
- associação usuário/permissão;
- middleware de permissão;
- auditoria de alterações;
- proteção contra acesso cruzado por ID.

Resultado: **concluída**.

---

## Fase 4 — Experiência e Interface

A interface principal da plataforma foi padronizada e validada.

### Dashboard

- visão geral do tenant;
- métricas;
- últimos eventos;
- ações administrativas;
- estado vazio.

### Usuários

- listagem;
- criação;
- edição;
- permissões;
- estados vazios;
- mensagens de sucesso e erro;
- padronização visual.

### Auditoria

- filtros;
- filtro por origem;
- paginação;
- estado vazio;
- métricas;
- padronização visual.

### Perfil

- edição dos dados;
- alteração de senha;
- mensagens de feedback;
- padronização visual.

### Responsividade

- validação em viewport móvel de 390px;
- header ajustado;
- Dashboard responsivo;
- Auditoria responsiva;
- telas de usuários responsivas;
- Perfil responsivo.

### Validação final

Suíte completa:

41 testes
85 assertions

Resultado: **verde**.

`git diff --check` sem erros de whitespace.

A Fase 4 está concluída.

### Próxima etapa

**Fase 5 — Robustez.**

---

## Fase 5 — Robustez

### 5.1 — Testes de regressão

A cobertura de regressão foi ampliada.

Novas suítes:

- ProfileTest;
- UserPermissionTest;
- UserProtectionTest;
- InputValidationTest;
- ExceptionHandlingTest.

### 5.2 — Validação de entradas

Foram validados:

- campos obrigatórios;
- limites de tamanho;
- formato de e-mail;
- unicidade de e-mail por tenant;
- senha mínima;
- confirmação de senha;
- roles válidas;
- proteção contra criação com dados inválidos.

### 5.3 — Tratamento de exceções

Foram adicionadas páginas personalizadas para:

- 403;
- 404;
- 419;
- 500.

Os comportamentos foram cobertos por testes automatizados.

### Segurança do ambiente de testes

Foi identificado que o ambiente Docker podia expor o banco PostgreSQL local aos testes.

Foi criado o comando seguro:

`test-safe.cmd`

Esse comando força:

- APP_ENV=testing;
- DB_CONNECTION=sqlite;
- DB_DATABASE=:memory:.

Também foi adicionada uma trava em `tests/TestCase.php` para impedir execução da suíte fora do ambiente seguro.

Foi validado que a execução dos testes não altera mais o banco local.

### Validação atual

Suíte completa:

75 testes
155 assertions

Resultado: **verde**.

### Próxima etapa

**5.4 — Logs.**
---

## Fase 5 — Robustez concluída

### Validação final

A Fase 5 consolidou:

- testes de regressão;
- validação de entradas;
- tratamento de exceções;
- logging;
- melhorias de performance;
- índices de banco;
- segurança;
- revisão e otimização de queries.

A consulta das métricas de auditoria foi consolidada para reduzir consultas redundantes.

Suíte completa:

80 testes
164 assertions

Resultado: **verde**.

Banco local preservado após os testes:

- 2 tenants;
- 4 usuários.

A Fase 5 está concluída.

### Próxima etapa

**Fase 6 — Preparação para Produção.**
---

## Fase 6 — Preparação para Produção

### 6.1 — Configuração de produção

Foi criada uma configuração de produção separada do ambiente local.

Principais entregas:

- `Dockerfile.production` com PHP-FPM;
- dependências Composer sem pacotes de desenvolvimento;
- build Vite reproduzível com `package-lock.json`;
- Nginx dedicado;
- `compose.production.yaml`;
- PostgreSQL production em volume separado;
- `.env.production.example`;
- `.env.production` real ignorado pelo Git;
- `APP_ENV=production`;
- `APP_DEBUG=false`;
- config, routes, events e views cacheados;
- health check `/up`;
- isolamento entre banco development e production;
- nenhuma conta padrão de desenvolvimento criada em production.

Validação:

- backend healthy;
- nginx healthy;
- postgres healthy;
- `/up` retornando 200;
- `/login` do tenant retornando 200;
- production com 2 tenants e 0 usuários.

Resultado: **6.1 concluída**.
### 6.2 — Variáveis de ambiente

As variáveis de ambiente de produção foram revisadas e consolidadas.

Validações realizadas:

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- timezone `America/Fortaleza`;
- PostgreSQL configurado como banco padrão;
- sessões persistidas em banco;
- criptografia de sessão habilitada;
- cookies seguros habilitados;
- cookies HttpOnly habilitados;
- SameSite configurado como `lax`;
- `.env.production` real ignorado pelo Git;
- `.env.production.example` atualizado;
- nenhuma chamada direta a `env()` fora dos arquivos de configuração.

Resultado: **6.2 concluída**.
### 6.3 — Banco

O banco PostgreSQL de produção foi validado.

Validações realizadas:

- PostgreSQL 18 em serviço separado;
- usuário da aplicação `plataforma_app` sem privilégios administrativos;
- tabelas pertencentes ao usuário da aplicação;
- sequences pertencentes ao usuário da aplicação;
- banco de produção persistido em volume Docker exclusivo;
- todas as migrations aplicadas;
- aplicação conectando via PostgreSQL;
- persistência validada após restart do container;
- 10 migrations preservadas;
- 2 tenants preservados;
- 0 usuários de desenvolvimento em production.

O tráfego PostgreSQL permanece restrito à rede Docker interna.
TLS deverá ser exigido quando o banco for externo ou gerenciado.

Resultado: **6.3 concluída**.
### 6.4 — Cache

O cache de produção foi validado com armazenamento em PostgreSQL.

Validações realizadas:

- `CACHE_STORE=database`;
- tabelas `cache` e `cache_locks` presentes;
- tabelas pertencentes ao usuário `plataforma_app`;
- índices de chave e expiração presentes;
- gravação e leitura de cache funcionando;
- persistência confirmada diretamente no PostgreSQL;
- remoção de chave validada;
- serviços de produção permaneceram saudáveis.

Neste estágio, Redis não é necessário. O cache em banco atende à arquitetura atual com menor complexidade operacional.

Resultado: **6.4 concluída**.
### 6.5 — Filas

A infraestrutura de filas de produção foi configurada e validada.

Validações realizadas:

- `QUEUE_CONNECTION=database`;
- tabelas `jobs`, `job_batches` e `failed_jobs` presentes;
- tabelas pertencentes ao usuário `plataforma_app`;
- índices de fila presentes;
- serviço `worker` dedicado adicionado ao `compose.production.yaml`;
- worker executado como `www-data`;
- processamento configurado com tentativas e timeout;
- job persistido no PostgreSQL;
- job consumido pelo worker;
- efeito do job validado pela aplicação;
- fila finalizada com `jobs=0`;
- nenhuma falha registrada em `failed_jobs`;
- arquivos temporários de validação removidos.

Resultado: **6.5 concluída**.
### 6.6 — Storage

O storage de produção foi configurado com persistência em volume Docker dedicado.

Validações realizadas:

- volume `app_storage` dedicado;
- backend com acesso de leitura e escrita;
- worker compartilhando o mesmo storage;
- Nginx com acesso somente leitura;
- arquivos públicos servidos em `/storage/`;
- storage privado não exposto via HTTP;
- persistência validada após recriação dos containers;
- configuração do Nginx validada com `nginx -t`;
- arquivos temporários de validação removidos.

Resultado: **6.6 concluída**.
### 6.7 — Backup

A estratégia de backup de produção foi implementada e validada.

Componentes protegidos:

- banco PostgreSQL;
- volume persistente `app_storage`.

Implementação:

- backup PostgreSQL em formato custom com `pg_dump -Fc`;
- backup compactado do storage;
- checksums SHA-256 para integridade;
- metadados com commit Git e contagens de referência;
- diretório `backups/` ignorado pelo Git;
- scripts reutilizáveis de backup e validação de restore.

Validação realizada:

- checksums válidos;
- restore PostgreSQL em container e volume temporários;
- 10 migrations restauradas;
- 2 tenants restaurados;
- 0 usuários restaurados;
- storage restaurado com 3 arquivos;
- volumes temporários removidos ao final;
- volumes reais de produção não foram sobrescritos.

Resultado: **6.7 concluída**.
### 6.8 — Monitoramento

A base de monitoramento operacional de produção foi configurada e validada.

Validações realizadas:

- logging Laravel em canal `daily`;
- nível de produção configurado como `warning`;
- retenção de logs Laravel por 14 dias;
- rotação de logs Docker configurada para todos os serviços;
- limite de 10 MB por arquivo de log;
- retenção de até 5 arquivos por container;
- backend, Nginx e PostgreSQL com healthchecks ativos;
- restart policy `unless-stopped`;
- escrita controlada de log Laravel validada em produção;
- mensagem de warning encontrada em `storage/logs`;
- serviços permaneceram estáveis após recriação.

Neste estágio, a observabilidade externa foi mantida fora do escopo. Integrações como Sentry, Prometheus ou Grafana podem ser adicionadas quando houver necessidade operacional.

Resultado: **6.8 concluída**.
### 6.9 — Health check

Os healthchecks de produção foram revisados e consolidados.

Validações realizadas:

- backend com healthcheck de PHP-FPM;
- Nginx validando o endpoint `/up`;
- PostgreSQL validado com `pg_isready`;
- worker com healthcheck dedicado;
- worker validado via `queue:monitor`;
- fila `default` operacional;
- nenhuma tarefa pendente;
- nenhuma tarefa falha;
- backend, Nginx, PostgreSQL e worker em estado `healthy`;
- endpoint `/up` mantido como liveness leve da aplicação.

Resultado: **6.9 concluída**.
### 6.10 — Deploy

O processo de deploy de produção foi automatizado e validado.

Implementação:

- script `deploy-production.ps1`;
- wrapper `deploy-production.cmd`;
- documentação operacional em `DEPLOY.md`;
- validação de preflight;
- atualização Git opcional;
- backup automático pré-deploy;
- build das imagens de produção;
- execução de migrations com `--force`;
- recriação dos serviços;
- espera automática pelos healthchecks;
- validação HTTP do endpoint `/up`;
- validação final do banco;
- validação da fila;
- verificação de jobs falhos.

Validação real executada:

- backup pré-deploy concluído;
- imagens backend, worker e Nginx construídas;
- PostgreSQL saudável;
- migrations sem pendências;
- backend saudável;
- Nginx saudável;
- PostgreSQL saudável;
- worker saudável;
- `/up` retornando HTTP 200;
- fila `default` operacional;
- nenhuma tarefa falha;
- deploy encerrado com `DEPLOY_OK`.

Resultado: **6.10 concluída**.

---

## Fase 6 — Preparação para Produção concluída

A preparação para produção foi concluída com:

- configuração de produção;
- variáveis de ambiente;
- PostgreSQL;
- cache;
- filas;
- storage persistente;
- backup e restore;
- monitoramento;
- healthchecks;
- deploy automatizado.

Resultado: **Fase 6 concluída**.
### 7.1 — Configurações globais do tenant

A fundação global por tenant foi implementada e validada.

Configurações adicionadas:

- país por tenant através de `country_code`;
- locale por tenant;
- timezone por tenant;
- moeda padrão por tenant;
- defaults seguros para tenants brasileiros;
- catálogo inicial de idiomas, países e moedas suportados.

Idiomas planejados pela fundação global:

- Português do Brasil (`pt-BR`);
- Inglês (`en`);
- Espanhol (`es`);
- Japonês (`ja`);
- Chinês simplificado (`zh-CN`).

Validações implementadas:

- normalização de país e moeda;
- rejeição de países não suportados;
- rejeição de locales não suportados;
- validação de timezone através dos identificadores oficiais do PHP;
- rejeição de moedas não suportadas;
- isolamento das configurações entre tenants.

Contexto de execução:

- o middleware `ResolveTenant` aplica automaticamente o locale do tenant;
- o middleware aplica automaticamente o timezone do tenant;
- locale e timezone anteriores são restaurados ao final da requisição;
- configurações inválidas de tenant impedem a execução da requisição.

Segurança dos testes:

- identificado risco de execução de testes diretamente contra o PostgreSQL de development;
- `test-safe.cmd` consolidado como entrada segura da suíte;
- `artisan` passou a bloquear `php artisan test` quando o ambiente não estiver explicitamente configurado com SQLite em memória;
- isolamento validado com development preservado antes e depois da suíte.

Validação executada:

- 92 testes verdes;
- 193 assertions;
- suíte completa executada com SQLite em memória;
- development preservado com 2 tenants e 4 usuários;
- migration aplicada em production;
- production passou de 10 para 11 migrations;
- 2 tenants de production preservados;
- tenants existentes receberam `BR`, `pt-BR`, `America/Fortaleza` e `BRL`;
- backend saudável;
- Nginx saudável;
- PostgreSQL saudável;
- worker saudável;
- endpoint do tenant retornando HTTP 200;
- endpoint `/up` retornando HTTP 200;
- backup pré-deploy criado e validado antes da migration.

Resultado: **7.1 concluída**.
### 7.2 — Internacionalização da interface

A interface da aplicação foi preparada para operação internacional por tenant.

Idiomas implementados:

- Português do Brasil (`pt-BR`);
- Inglês (`en`);
- Espanhol (`es`);
- Japonês (`ja`);
- Chinês simplificado (`zh-CN`).

Estrutura criada:

- diretórios de tradução por locale;
- traduções gerais em `ui.php`;
- auditoria em `audit.php`;
- páginas de erro em `errors.php`;
- mensagens de validação em `validation.php`;
- fallback global padronizado para `pt-BR`.

Áreas internacionalizadas:

- layout global;
- página inicial;
- login;
- dashboard;
- perfil;
- usuários;
- permissões;
- auditoria;
- páginas 403, 404, 419 e 500;
- mensagens padrão de validação;
- mensagem customizada de credenciais inválidas.

Comportamento por tenant:

- o locale configurado no tenant controla automaticamente a interface;
- atributos HTML `lang` acompanham o locale ativo;
- traduções são resolvidas durante a requisição;
- mensagens de validação respeitam o locale do tenant;
- fallback permanece em `pt-BR`.

Validações realizadas:

- tradução runtime validada nos cinco idiomas;
- login japonês validado;
- dashboard japonês validado;
- validação padrão japonesa validada;
- mensagem customizada de autenticação em espanhol validada;
- ausência de hardcodes principais confirmada;
- suíte completa com 99 testes e 224 assertions;
- suíte específica de internacionalização com 7 testes e 31 assertions;
- development preservado com 2 tenants e 4 usuários após os testes;
- isolamento da base de testes confirmado.

E-mails e notificações:

- não existem atualmente Mailables ou Notifications funcionais na aplicação;
- nenhuma implementação artificial foi criada apenas para satisfazer o roadmap;
- novos fluxos de e-mail ou notificação deverão obrigatoriamente respeitar o locale do tenant e utilizar arquivos de tradução desde sua criação.

Resultado: **7.2 concluída**.
### 7.3 — Moedas e valores monetários

A fundação monetária global foi implementada e validada.

Moedas suportadas:

- BRL;
- USD;
- EUR;
- JPY;
- CNY.

Estrutura implementada:

- catálogo central de moedas;
- metadados de casas decimais por moeda;
- representação segura em minor units inteiras;
- conversão decimal a partir de string;
- arredondamento half-up;
- suporte a moedas sem casas decimais;
- formatação monetária baseada em `NumberFormatter`;
- locale e moeda tratados como conceitos independentes;
- formatação automática usando o contexto do tenant.

Segurança monetária:

- valores não dependem de `float` como fonte de verdade;
- entradas decimais inválidas são rejeitadas;
- moedas não suportadas são rejeitadas;
- overflow de inteiro é detectado explicitamente;
- arredondamento que cause overflow também é bloqueado;
- valores negativos usam arredondamento simétrico.

Contexto por tenant:

- moeda padrão obtida do tenant atual;
- locale do tenant controla a apresentação visual;
- mesmo valor pode ser exibido de forma diferente entre tenants;
- catálogo monetário validado contra `config/global.php`.

Validações executadas:

- 17 testes monetários unitários com 37 assertions;
- 4 testes de contexto monetário do tenant com 7 assertions;
- BRL, USD, EUR, JPY e CNY validados;
- arredondamento half-up validado;
- JPY validado sem casas decimais;
- conversão visual validada por locale;
- overflow validado;
- development preservado durante os testes;
- suíte completa da aplicação validada após a implementação.

Resultado: **7.3 concluída**.
---

## 7.4 — Países, endereços e telefones

Foi criada a fundação regional da aplicação para permitir que dados
dependentes de país sejam representados sem acoplamento ao modelo brasileiro.

### Países

Foi criado o objeto `Country`, responsável por:

- normalizar códigos ISO de país;
- validar países suportados pela plataforma;
- disponibilizar o código telefônico internacional;
- manter consistência com o catálogo global de países.

O catálogo foi validado contra as configurações globais do tenant.

### Telefones

Foi criado o objeto `PhoneNumber`, permitindo:

- associação explícita do telefone a um país;
- geração da representação internacional;
- validação do limite compatível com E.164;
- comparação de números pela representação internacional.

A estrutura não assume formato brasileiro de telefone.

### Endereços

Foi criado o objeto `InternationalAddress`.

A estrutura utiliza campos internacionais genéricos e não exige conceitos
específicos do Brasil, permitindo representar endereços de diferentes países.

Foram validados cenários brasileiros e japoneses, incluindo normalização de
campos opcionais e obrigatórios.

### Documentos fiscais

Foi criado o objeto extensível `TaxIdentifier`.

Um documento fiscal possui:

- país;
- tipo;
- valor.

Isso permite adicionar regras fiscais específicas de cada região sem colocar
essas regras na estrutura genérica.

Para o Brasil foi criado `BrazilTaxIdentifier`, com suporte a:

- CPF;
- CNPJ;
- remoção de máscara;
- validação dos dígitos verificadores;
- rejeição de documentos inválidos.

CPF e CNPJ permanecem, portanto, regras regionais brasileiras e não conceitos
globais da plataforma.

### Segurança e isolamento

Nenhuma migration ou alteração prematura de schema foi introduzida nesta etapa.

Os objetos criados formam a camada de domínio que poderá ser utilizada por
entidades futuras quando endereço, telefone ou documento fiscal precisarem ser
persistidos.

A suíte completa da aplicação foi executada após a implementação:

- 147 testes aprovados;
- 329 assertions;
- nenhuma regressão detectada;
- banco de desenvolvimento preservado;
- catálogo de países validado contra as configurações globais.
## 7.5 — Datas, horas e timezone

Foi criada a fundação temporal da aplicação para separar de forma explícita
o instante persistido do horário local apresentado a cada tenant.

### Política temporal

A aplicação passa a adotar UTC como referência global para persistência e
execução do runtime.

A política definida é:

- timestamps persistidos representam instantes em UTC;
- o runtime global da aplicação permanece em UTC;
- o timezone do tenant não altera o timezone global do processo;
- horários locais são convertidos explicitamente nas fronteiras da aplicação;
- apresentação de datas utiliza o timezone configurado no tenant.

Essa separação evita que o mesmo instante seja interpretado de formas
diferentes durante persistência, consultas e processamento interno.

### Contexto temporal do tenant

Foi criado o suporte `TenantDateTime`, responsável por operações temporais
dependentes do tenant.

A estrutura permite:

- converter um instante UTC para o timezone do tenant;
- converter uma data e hora local do tenant para UTC;
- transformar um dia local em seu intervalo UTC correspondente;
- formatar datas para apresentação no timezone correto;
- respeitar diferenças reais de duração causadas por transições de DST;
- rejeitar entradas locais inválidas.

### Filtros e apresentação

O filtro temporal da auditoria foi ajustado para interpretar datas informadas
pelo usuário no timezone do tenant e consultar o intervalo UTC correspondente.

As apresentações de timestamps nas interfaces relevantes também passaram a
utilizar a conversão explícita pelo contexto temporal do tenant.

Dessa forma, armazenamento e consulta permanecem baseados em UTC enquanto o
usuário visualiza os horários de acordo com sua configuração regional.

### Runtime UTC

O timezone global versionável da aplicação foi padronizado como `UTC`.

O middleware de resolução de tenant continua aplicando o locale do tenant,
mas não modifica mais o timezone global do processo durante a requisição.

O timezone do tenant passou a ser utilizado somente pelas operações que
realmente dependem de contexto local.

### Normalização dos timestamps legados

Foi criada a migration
`2026_08_15_220513_normalize_legacy_application_timestamps_to_utc.php`
para normalizar timestamps históricos que haviam sido persistidos usando o
horário local `America/Fortaleza`.

Como Fortaleza utiliza UTC-3 nesse conjunto de dados legado, os instantes
existentes foram convertidos para sua representação UTC correspondente antes
da adoção definitiva do runtime UTC.

A migration foi executada de forma controlada em development após backup e
validação prévia dos valores.

### Validação da persistência

Após a transição, foi realizado um probe controlado de persistência.

O timestamp criado pelo Laravel coincidiu com o horário UTC do runtime PHP e
com o horário UTC reportado pelo PostgreSQL, confirmando que novos registros
passam a ser persistidos de acordo com a política temporal definida.

O registro temporário utilizado nessa validação foi removido após o teste.

### Segurança e isolamento

A mudança foi validada sem perda dos dados de development.

Após a normalização:

- os 2 tenants permaneceram preservados;
- os 4 usuários permaneceram preservados;
- o probe temporário de auditoria foi removido;
- a migration de normalização consta como executada;
- o runtime da aplicação reporta `UTC`.

A suíte completa da aplicação foi executada após a transição:

- 155 testes aprovados;
- 342 assertions aprovadas.

Também foram validados especificamente cenários de:

- conversão UTC para timezone do tenant;
- conversão de horário local para UTC;
- filtros de auditoria por dia local;
- formatação de datas;
- isolamento entre tenants;
- dias afetados por DST;
- preservação do runtime global em UTC.
## 7.6 — Preferências e branding

Foi criada a fundação de identidade visual e preferências de apresentação por tenant.

### Nome comercial

O campo existente `Tenant::name` foi adotado como nome comercial do tenant.

Esse nome passou a ser utilizado como identidade visível do ambiente, sem criar
uma segunda propriedade redundante para o mesmo conceito.

Quando nenhum logo está configurado, o nome comercial funciona como fallback
visual no cabeçalho da aplicação.

### Cor principal

Foi criada a configuração `brand_primary_color`.

A normalização e validação são centralizadas em `TenantBrandingSettings`.

A cor:

- aceita formato hexadecimal completo;
- é normalizada para letras maiúsculas;
- pode permanecer nula no banco;
- utiliza `#2563EB` como fallback seguro de apresentação;
- é aplicada ao layout e ao login através de variável CSS.

Cores semânticas de erro, sucesso, bordas e estados independentes continuam
separadas da identidade visual do tenant.

### Logo

Foi criada a configuração `logo_path`.

O logo é armazenado no filesystem público em diretórios isolados por tenant:

`tenant-branding/{tenant_id}/`

O upload aceita inicialmente:

- PNG;
- JPEG;
- WebP;
- arquivos de até 2 MB.

SVG não foi habilitado nesta etapa para reduzir a superfície de risco.

A implementação permite:

- envio de novo logo;
- substituição do logo existente;
- remoção explícita;
- exclusão do arquivo anterior após substituição;
- preview do logo atual nas configurações;
- exibição do logo no cabeçalho da aplicação;
- fallback para o nome comercial quando não existe logo.

### Configurações do tenant

Foi criada a área `/settings`.

As rotas são protegidas pela nova permissão:

`settings.update`

A permissão foi adicionada ao catálogo da aplicação e ao conjunto padrão do
papel administrativo.

A tela permite editar:

- nome comercial;
- cor principal;
- logo.

As alterações afetam somente o tenant corrente.

### Internacionalização

A nova área de configurações foi internacionalizada para os cinco locales
suportados:

- `pt-BR`;
- `en`;
- `es`;
- `ja`;
- `zh-CN`.

Também foi adicionada a entrada de navegação correspondente em cada idioma.

A mensagem de validação da cor e a mensagem de sucesso utilizam traduções da
interface.

### Auditoria

Alterações nas configurações geram o evento:

`tenant.settings.updated`

O evento permanece associado ao tenant e ao usuário responsável pela alteração.

### Storage

O disk `public` é utilizado para os arquivos de branding.

O link `public/storage` foi preparado para servir os arquivos armazenados em
`storage/app/public`.

A configuração de production já utiliza volume compartilhado para
`storage/app`, mantendo os arquivos disponíveis para backend e servidor web.

### Segurança e isolamento

Foram validados cenários de:

- usuário com permissão acessando as configurações;
- usuário sem permissão recebendo `403`;
- administrador acessando as configurações;
- atualização somente do tenant corrente;
- rejeição de cor inválida;
- upload de logo;
- substituição com remoção do arquivo anterior;
- remoção explícita do logo;
- rejeição de arquivo inválido;
- isolamento de armazenamento entre tenants;
- renderização do logo configurado;
- fallback para nome comercial;
- internacionalização da tela;
- auditoria das alterações.

A migration
`2026_08_16_111741_add_branding_settings_to_tenants_table.php`
foi aplicada em development.

Após a implementação:

- 2 tenants permaneceram preservados;
- 4 usuários permaneceram preservados;
- 9 permissões estavam cadastradas;
- `settings.update` estava disponível;
- `brand_primary_color` e `logo_path` estavam presentes no schema.

A suíte completa da aplicação foi executada após a conclusão:

- 182 testes aprovados;
- 406 assertions aprovadas.

## 7.7 — Feature flags e capacidades

Foi criada a fundação de capabilities por tenant para permitir que recursos da
aplicação sejam habilitados, desabilitados e limitados de forma independente.

### Catálogo de features

Foi criado o enum `Feature`, que centraliza o catálogo inicial de recursos
controláveis:

- `users`;
- `audit`;
- `branding`.

O catálogo fornece valores estáveis e labels para uso interno da aplicação.

### Features por tenant

Foi criada a tabela `tenant_features`.

Cada registro associa:

- um tenant;
- uma feature;
- seu estado habilitado ou desabilitado;
- um limite opcional.

A combinação entre `tenant_id` e `feature` é única.

A ausência de uma feature para um tenant é interpretada de forma segura como
recurso desabilitado.

Foi criada a camada `TenantCapabilities`, responsável por:

- consultar se uma feature está habilitada;
- habilitar ou desabilitar features;
- consultar limites;
- definir ou remover limites;
- configurar estado e limite em uma única operação;
- aplicar perfis de capabilities.

### Preservação dos tenants existentes

Após a criação da estrutura, foi executado um backfill controlado para os
tenants já existentes.

As features:

- `users`;
- `audit`;
- `branding`;

foram habilitadas para os tenants existentes para preservar o comportamento da
aplicação durante a introdução da nova camada.

Em development permaneceram 6 registros de `tenant_features`, correspondentes
às três features dos dois tenants existentes.

### Middleware de feature

Foi criado o middleware `feature`.

O middleware resolve a feature pelo catálogo e bloqueia com `403` quando o
tenant atual não possui a capability habilitada.

As rotas existentes passaram a combinar autorização por permissão com
disponibilidade por tenant.

A cobertura aplicada foi:

- gestão de usuários protegida por `feature:users`;
- auditoria protegida por `feature:audit`;
- configurações e branding protegidos por `feature:branding`.

Dessa forma, permissão do usuário e disponibilidade do recurso permanecem como
conceitos independentes.

### Limites por recurso

Foi adicionada a coluna `limit_value` em `tenant_features`.

A semântica definida é:

- `null`: nenhum limite configurado;
- `0`: limite zero;
- inteiro positivo: limite máximo explícito.

O primeiro uso real de limite foi aplicado à criação de usuários.

Quando `Feature::USERS` possui limite configurado, a criação de novos usuários
é bloqueada ao atingir o teto do tenant.

Foram validados cenários de:

- limite ausente;
- limite zero;
- limite positivo;
- remoção do limite;
- rejeição de valor negativo;
- idempotência;
- isolamento de limites entre tenants;
- criação abaixo do limite;
- bloqueio ao atingir o limite.

### Estrutura preparada para planos

Foi criada uma operação de configuração agregada de capabilities.

`TenantCapabilities::applyProfile()` permite aplicar um conjunto de definições
de feature, estado e limite em uma única operação transacional.

Essa estrutura prepara a aplicação para uma futura camada de planos sem
introduzir prematuramente conceitos de cobrança, assinatura ou billing.

Um plano futuro poderá ser traduzido para um perfil de capabilities e aplicado
ao tenant sem alterar a camada de execução das features.

### Auditoria de capabilities

Foi criado `TenantCapabilityManager`.

Essa camada fica responsável pelas mutações administrativas auditáveis,
mantendo `TenantCapabilities` como primitive de domínio para consulta e
persistência.

São registrados os eventos:

- `tenant.feature.updated`;
- `tenant.feature.limit.updated`;
- `tenant.capability_profile.applied`.

Mudanças idempotentes não geram eventos duplicados.

Também foi validado que o evento pode ser associado explicitamente ao tenant
alvo, independentemente do tenant presente no contexto atual.

Para suportar esse cenário, o trait `BelongsToTenant` passou a respeitar um
`tenant_id` explicitamente definido antes de utilizar o tenant do contexto.

O escopo global de leitura por tenant permanece ativo.

### Migrations

A etapa criou e aplicou em development:

`2026_08_16_121243_create_tenant_features_table.php`

`2026_08_16_121934_seed_existing_tenant_features.php`

`2026_08_16_123910_add_limit_value_to_tenant_features_table.php`

As três migrations constam como executadas.

### Segurança e isolamento

Foram validados cenários de:

- feature ausente desabilitada por padrão;
- habilitação e desabilitação explícitas;
- isolamento de features entre tenants;
- combinação entre permissão e feature;
- bloqueio de auditoria quando a feature está desabilitada;
- bloqueio de users quando a feature está desabilitada;
- bloqueio de branding quando a feature está desabilitada;
- isolamento de limites entre tenants;
- isolamento de auditoria de capabilities;
- preservação dos escopos multi-tenant;
- preservação das validações existentes;
- preservação das proteções administrativas existentes.

Após a implementação, development permaneceu com:

- 2 tenants;
- 4 usuários;
- 9 permissões;
- 6 registros de `tenant_features`;
- 0 registros temporários em `audit_logs`.

As seis capabilities existentes permaneceram habilitadas e sem limite
configurado.

### Validação final

A suíte completa da aplicação foi executada após a conclusão da etapa:

- 221 testes aprovados;
- 477 assertions aprovadas.

A regressão incluiu funcionalidades de:

- autenticação;
- permissões;
- isolamento multi-tenant;
- internacionalização;
- auditoria;
- branding;
- configurações;
- criação e proteção de usuários;
- validação de entrada;
- datas e timezone;
- capabilities;
- limites por recurso.

## 7.8.3 — Performance

Foi realizada a validação estrutural de performance da fundação da aplicação.

### Índices estruturais

Foi criado o teste `PerformanceFoundationTest` para verificar a presença dos
índices necessários às consultas fundamentais da aplicação.

A validação foi implementada de forma compatível com o ambiente de testes
SQLite, evitando dependência direta das tabelas internas do PostgreSQL.

### Dashboard

Foi validado que a consulta de eventos recentes utilizada pelo dashboard
permanece limitada, evitando carregamento não controlado do histórico de
auditoria.

### Usuários

Foi validado que a listagem de usuários pode ser ordenada pelo nome utilizando
a estrutura de índices existente.

### Regressão

A regressão direcionada de performance, auditoria e gerenciamento de usuários
foi executada com sucesso:

- 20 testes aprovados;
- 60 assertions aprovadas.

Em seguida, a suíte completa da aplicação foi executada:

- 227 testes aprovados;
- 495 assertions aprovadas.

Nenhuma regressão funcional foi encontrada.

### Preservação de development

Após as validações, o ambiente development permaneceu preservado:

- 2 tenants;
- 4 usuários;
- 9 permissões;
- 6 registros em `tenant_features`;
- nenhum registro inesperado em `audit_logs`.

A validação de performance da fundação foi concluída sem alterações funcionais
no ambiente de development.

## 7.8.1 — Regressão multi-tenant

Foi realizada uma regressão específica da fundação multi-tenant.

### Resolução e contexto

Foi revisado o fluxo de resolução do tenant a partir do host e o uso do
`TenantContext` durante a requisição.

A aplicação mantém o tenant corrente como contexto explícito para operações que
dependem do ambiente acessado.

### Isolamento de usuários

O model `User` utiliza o escopo multi-tenant da aplicação.

As consultas comuns de usuários permanecem restritas ao tenant corrente,
incluindo buscas por identificador e listagens.

Também foi validado que usuários criados através do controller são associados ao
tenant atual.

### Auditoria

`AuditLog` permanece protegido pelo escopo multi-tenant.

Os eventos de auditoria são consultados e apresentados somente dentro do tenant
corrente.

Para operações administrativas explícitas, o trait `BelongsToTenant` respeita um
`tenant_id` previamente definido e utiliza o contexto somente como fallback.

### Capabilities

`TenantFeature` é acessado através da camada `TenantCapabilities`.

Essa camada sempre recebe explicitamente o tenant alvo e restringe consultas e
mutações por `tenant_id`.

Foram validados:

- isolamento do estado das features;
- isolamento dos limites;
- aplicação de perfis por tenant;
- auditoria no tenant alvo.

### Regressão

Foram exercitados fluxos de:

- autenticação entre tenants;
- usuários;
- permissões;
- auditoria;
- configurações;
- branding;
- capabilities;
- limites;
- contexto global;
- dados regionais.

A regressão multi-tenant foi concluída sem perda de isolamento entre tenants.

## 7.8.2 — Segurança

Foi realizada uma revisão de segurança da fundação web e multi-tenant.

### Autenticação

O login continua restringindo a autenticação ao tenant corrente através de
`tenant_id`.

Após autenticação válida, a sessão é regenerada.

No logout:

- o usuário é removido da sessão;
- a sessão é invalidada;
- o token CSRF é regenerado.

### Proteção contra brute force

Foi adicionado rate limiting ao login.

A chave de limitação combina:

- tenant;
- e-mail normalizado;
- endereço IP.

Essa composição impede que falhas de autenticação em um tenant consumam o limite
de outro tenant.

Foram configuradas cinco tentativas dentro da janela de controle.

Após autenticação bem-sucedida, o contador correspondente é removido.

Foram adicionados testes para:

- bloqueio após falhas repetidas;
- limpeza do contador após login válido;
- isolamento do rate limit entre tenants.

### Sessão em production

A configuração de production foi validada com:

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- `SESSION_SECURE_COOKIE=true`;
- `SESSION_HTTP_ONLY=true`;
- `SESSION_SAME_SITE=lax`.

### Autorização

As rotas protegidas continuam combinando autenticação, permissões e capabilities
quando necessário.

O middleware de permissão também verifica que o usuário autenticado pertence ao
tenant resolvido.

### Uploads

O upload de logo permanece restrito a:

- PNG;
- JPEG;
- WebP;
- máximo de 2 MB.

SVG continua desabilitado nesta etapa.

### Validação

Após o hardening de autenticação, a suíte completa permaneceu verde.

A revisão não identificou necessidade de enfraquecer nenhuma proteção existente.

## 7.8.4 — Documentação

A documentação da fundação global foi consolidada após as validações de
isolamento, segurança e performance.

O `ROADMAP.md` passou a refletir como concluídos:

- testes completos;
- regressão multi-tenant;
- segurança;
- performance;
- documentação.

A validação final permanece propositalmente pendente até a execução do
checkpoint final da fase.

O `JOURNEY.md` registra as decisões técnicas e as evidências acumuladas durante
a validação da fundação, incluindo:

- política multi-tenant;
- capabilities por tenant;
- limites por recurso;
- internacionalização;
- timezone e persistência UTC;
- branding;
- segurança de autenticação;
- isolamento de sessão;
- índices estruturais;
- paginação e consultas limitadas;
- preservação do ambiente de development.

Essa documentação funciona como baseline antes da entrada na Fase 8.

## 7.8.5 — Validação final

A fundação global da aplicação foi submetida a uma validação técnica final antes
do encerramento da Fase 7.

### Estado do repositório

A validação foi iniciada com o working tree limpo.

Após todas as verificações técnicas, nenhuma alteração inesperada foi produzida
no repositório.

### Docker

A configuração do Docker Compose foi validada com sucesso.

Os serviços principais estavam operacionais:

- backend;
- PostgreSQL.

O PostgreSQL permaneceu saudável durante a validação.

### Migrations

Todas as migrations existentes estavam aplicadas no banco de development.

A execução de `php artisan migrate --pretend` confirmou que não existiam
migrations pendentes.

### Dados de development

Os dados existentes foram preservados:

- 2 tenants;
- 4 usuários;
- 9 permissões;
- 6 registros em `tenant_features`;
- 0 registros persistentes em `audit_logs`.

Nenhum dado de development foi recriado ou removido pela validação.

### Capabilities

Os dois tenants existentes permaneceram com as três capabilities fundamentais
habilitadas:

- `users`;
- `audit`;
- `branding`.

A estrutura continuou isolada por tenant e preparada para limites opcionais.

### Índices

Foram validados no PostgreSQL os índices estruturais relevantes da fundação,
incluindo:

- unicidade de e-mail por tenant;
- ordenação de usuários por tenant e nome;
- consultas de auditoria por tenant, data, ação e usuário;
- unicidade de feature por tenant.

### Segurança de production

A configuração de production foi revisada e confirmou:

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- cookies de sessão seguros;
- cookies de sessão HTTP-only;
- `SameSite=lax`.

### Rotas críticas

As rotas fundamentais permaneceram registradas para:

- gerenciamento de usuários;
- permissões de usuários;
- auditoria;
- configurações do tenant.

As proteções por autenticação, permissões e capabilities permanecem cobertas
pela suíte automatizada.

### Suíte final

A suíte completa foi executada novamente no checkpoint final.

Resultado:

- 227 testes aprovados;
- 495 assertions aprovadas;
- nenhuma regressão detectada.

A suíte cobre, entre outros pontos:

- autenticação;
- rate limiting;
- isolamento multi-tenant;
- permissões;
- capabilities;
- limites por recurso;
- auditoria;
- internacionalização;
- moedas;
- datas e fusos horários;
- branding;
- configurações do tenant;
- validação de entrada;
- tratamento de erros;
- segurança;
- performance estrutural.

### Encerramento da Fase 7

Com a validação final concluída, a fundação global da plataforma está
formalmente validada.

A Fase 7 passa a ser considerada concluída, estabelecendo uma base para o
desenvolvimento dos módulos de negócio seguintes sem remover as garantias já
construídas de:

- isolamento multi-tenant;
- segurança;
- internacionalização;
- consistência monetária;
- tratamento correto de data e hora;
- permissões;
- auditoria;
- capabilities;
- branding;
- performance estrutural;
- cobertura automatizada.

## 8.1 — Leads

Foi implementado o primeiro módulo de CRM da plataforma.

### Estrutura de domínio

Foi criado o model `Lead`, isolado por tenant através do trait
`BelongsToTenant`.

Cada lead possui:

- nome;
- e-mail opcional;
- telefone opcional;
- status;
- origem;
- responsável opcional;
- tags;
- observações;
- timestamps.

Foram criados os enums `LeadStatus` e `LeadSource`.

Os status disponíveis são:

- `new`;
- `contacted`;
- `qualified`;
- `unqualified`.

As origens disponíveis são:

- `manual`;
- `website`;
- `referral`;
- `social`;
- `other`.

### Isolamento multi-tenant

Leads são associados automaticamente ao tenant corrente.

Foi validado que registros de outro tenant não podem ser listados, localizados,
editados ou excluídos pelo tenant atual.

O responsável pelo lead também precisa pertencer ao mesmo tenant.

### Capability

Foi adicionada a capability:

`leads`

Todas as rotas do módulo usam `feature:leads`.

Os tenants existentes receberam a capability habilitada através de migration
versionada.

### Permissões

Foram adicionadas:

- `leads.view`;
- `leads.create`;
- `leads.update`;
- `leads.delete`.

O papel administrativo passou a receber todas essas permissões e os
administradores existentes foram atualizados por migration versionada.

### CRUD

Foi implementado CRUD completo:

- listagem;
- cadastro;
- edição;
- exclusão.

A listagem possui:

- busca por nome, e-mail e telefone;
- filtro por status;
- filtro por origem;
- filtro por responsável;
- paginação de 20 registros.

### Tags e observações

Tags são armazenadas como JSON nesta etapa.

Antes da persistência elas são normalizadas com remoção de espaços, valores
vazios e duplicatas.

Observações são armazenadas como texto opcional.

### Auditoria

As operações geram:

- `lead.created`;
- `lead.updated`;
- `lead.deleted`.

### Interface e navegação

Foram criadas telas de listagem, cadastro e edição.

O link de Leads foi integrado à navegação principal e é exibido somente quando
o usuário possui `leads.view` e o tenant possui a capability `leads` habilitada.

### Internacionalização

O módulo foi internacionalizado para:

- `pt-BR`;
- `en`;
- `es`;
- `ja`;
- `zh-CN`.

Status, origens, campos, filtros, ações, mensagens e navegação utilizam
traduções.

### Banco de dados

Foram criadas e aplicadas as migrations:

`2026_08_16_140000_create_leads_table.php`

`2026_08_16_140100_seed_existing_lead_capabilities_and_permissions.php`

A tabela possui índices por tenant combinados com:

- nome;
- status;
- responsável;
- data de criação.

### Development

Após a implementação:

- 2 tenants permaneceram preservados;
- 4 usuários permaneceram preservados;
- 13 permissões estavam cadastradas;
- 8 capabilities de tenant estavam cadastradas;
- 0 audit logs persistentes estavam presentes;
- 0 leads de negócio foram criados automaticamente.

### Testes

O teste funcional final de gerenciamento de Leads terminou com:

- 30 testes aprovados;
- 64 assertions aprovadas.

A regressão ampliada da etapa terminou com:

- 96 testes aprovados;
- 198 assertions aprovadas.

A suíte completa terminou com:

- 271 testes aprovados;
- 586 assertions aprovadas.

Nenhuma regressão foi detectada.
## 8.2 — Contatos e clientes

Foi implementada a fundação de contatos e clientes do CRM, mantendo isolamento
multi-tenant, capabilities, permissões, auditoria e internacionalização da
plataforma.

### Clientes

Foi criado o domínio de clientes com suporte inicial a:

- pessoa física;
- pessoa jurídica;
- responsável;
- tags;
- dados fiscais e cadastrais;
- busca;
- filtros;
- paginação.

O cadastro, edição, visualização e exclusão são protegidos por permissões e pela
capability `customers`.

### Pessoa física e pessoa jurídica

O tipo do cliente é representado pelo enum `CustomerType`.

A aplicação permite manter clientes individuais e empresas utilizando a mesma
fundação, com validações específicas para os dados aplicáveis a cada tipo.

### Contatos

Clientes podem possuir contatos relacionados.

Os contatos permanecem vinculados ao cliente e ao tenant corrente, impedindo
associações entre tenants diferentes.

### Telefones

Clientes podem possuir múltiplos telefones.

Os telefones são normalizados antes da persistência e podem opcionalmente ser
associados a um contato do mesmo cliente.

Também existe suporte a telefone principal, garantindo consistência quando um
novo telefone é definido como principal.

### E-mails

Clientes podem possuir múltiplos endereços de e-mail.

Os valores são normalizados e validados antes da persistência.

E-mails podem ser associados a contatos do mesmo cliente e um novo e-mail
principal substitui corretamente o principal anterior.

### Endereços

Clientes podem possuir múltiplos endereços.

A estrutura contempla dados como país, cidade, região, CEP e linhas de endereço.

Um novo endereço principal substitui corretamente o endereço principal
anterior.

### Histórico

Foi criada uma estrutura de histórico do cliente.

Alterações relevantes no cliente e em seus relacionamentos geram registros de
histórico associados ao tenant, cliente e, quando aplicável, usuário
responsável.

### Isolamento multi-tenant

O domínio foi validado contra referências cruzadas entre tenants.

Clientes e seus contatos, telefones, e-mails, endereços e histórico somente
podem ser consultados e relacionados dentro do tenant corrente.

Também foi validado que telefones e e-mails associados a contatos utilizem
contatos pertencentes ao mesmo cliente.

### Capabilities

Foi adicionada a capability:

`customers`

A ausência ou desativação da capability bloqueia o acesso ao recurso.

A estrutura também permanece preparada para limites por tenant.

### Permissões

Foram adicionadas permissões específicas para clientes:

- `customers.view`;
- `customers.create`;
- `customers.update`;
- `customers.delete`.

O papel administrativo recebe essas permissões através da sincronização padrão.

### Navegação

A área de clientes foi integrada à navegação principal.

A exibição depende diretamente da permissão `customers.view` e da capability
`customers`, sem depender da permissão de gerenciamento de usuários.

### Internacionalização

A área de clientes foi internacionalizada para os cinco locales suportados:

- `pt-BR`;
- `en`;
- `es`;
- `ja`;
- `zh-CN`.

As traduções possuem paridade de chaves entre os locales.

A renderização em japonês também foi validada diretamente em UTF-8, incluindo
bytes, codepoints e resolução pelo sistema de tradução do Laravel.

### Validação

Foram validados cenários de:

- criação de pessoa física;
- criação de pessoa jurídica;
- edição e exclusão;
- validação de CPF;
- responsável pertencente ao tenant;
- normalização de tags;
- filtros;
- paginação;
- busca por dados relacionados;
- contatos;
- telefones;
- e-mails;
- endereços;
- histórico;
- registros principais;
- referências entre cliente e contato;
- isolamento entre tenants;
- permissões;
- capability;
- internacionalização;
- navegação.

A regressão específica de Customers foi executada juntamente com a fundação,
Leads, auditoria, internacionalização e controles de acesso.

Os dados existentes de development permaneceram preservados durante a
implementação.

## 8.3 — Conversão de lead

Foi implementado o fluxo de conversão de leads em clientes, conectando os dois
domínios do CRM sem duplicar dados de origem e mantendo rastreabilidade da
operação.

### Vínculo entre Lead e Customer

A tabela `leads` passou a armazenar:

- `converted_customer_id`;
- `converted_at`.

O relacionamento permite identificar o cliente criado a partir do lead e,
inversamente, o lead que originou um cliente convertido.

A conversão de um mesmo lead somente pode ocorrer uma vez.

### Serviço de conversão

Foi criado o `LeadConversionService`.

A conversão é executada dentro de transação e:

- cria o cliente;
- preserva o nome;
- preserva o responsável;
- preserva tags;
- preserva observações;
- copia o e-mail quando existente;
- copia o telefone quando válido;
- registra a data da conversão;
- vincula lead e cliente;
- cria histórico do cliente;
- gera auditoria.

A implementação suporta conversão para:

- pessoa física;
- pessoa jurídica.

### E-mail e telefone

Quando o lead possui e-mail válido, ele é criado como e-mail principal do novo
cliente.

Quando existe telefone compatível com a normalização internacional da
plataforma, ele é criado como telefone principal.

Um telefone opcional inválido não impede a conversão do restante do lead.

### Conversão duplicada

A operação possui proteção contra conversão repetida.

A validação ocorre antes e durante a transação, impedindo a criação de um segundo
cliente para o mesmo lead.

Também foi criado índice de unicidade relacionado ao vínculo de conversão.

### Operação HTTP

Foi criada a rota:

`POST /leads/{id}/convert`

A rota utiliza a action `LeadController@convert`.

A operação exige:

- permissão `leads.update`;
- capability `leads`;
- capability `customers`.

O tipo do cliente é validado antes da conversão.

Após sucesso, o usuário é redirecionado para o detalhe do Customer criado.

### Interface

A tela de edição do lead passou a disponibilizar a conversão quando:

- o usuário possui `leads.update`;
- a feature `leads` está habilitada;
- a feature `customers` está habilitada;
- o lead ainda não foi convertido.

O formulário permite selecionar:

- pessoa física;
- pessoa jurídica.

Quando o lead já foi convertido, o formulário deixa de ser exibido e a
interface apresenta o estado de conversão e um link para o Customer resultante.

### Internacionalização

Os textos da conversão foram adicionados aos cinco locales suportados:

- `pt-BR`;
- `en`;
- `es`;
- `ja`;
- `zh-CN`.

A interface de conversão em japonês também foi validada por teste funcional.

### Auditoria e histórico

A conversão gera o evento de auditoria:

`lead.converted`

No histórico do cliente é criado o evento:

`customer.converted_from_lead`

Assim, a operação fica rastreável tanto pela auditoria global quanto pelo
histórico específico do cliente.

### Isolamento multi-tenant

O lead convertido precisa pertencer ao tenant corrente.

Foi validado que um usuário de outro tenant não consegue consultar nem converter
um lead externo ao seu contexto.

O Customer criado e todos os dados copiados permanecem associados ao mesmo
tenant do lead.

### Banco de dados

Foi criada e aplicada a migration:

`2026_08_16_160000_add_conversion_to_leads_table.php`

Foram adicionados:

- FK de `converted_customer_id` para `customers`;
- índice por tenant e data de conversão;
- unicidade por tenant e Customer convertido.

A execução de `migrate --pretend` após a aplicação confirmou ausência de
migrations pendentes.

### Validação

Foram cobertos cenários de:

- conversão básica;
- pessoa física;
- pessoa jurídica;
- preservação de responsável;
- preservação de tags e observações;
- criação de e-mail principal;
- criação de telefone principal;
- telefone opcional inválido;
- vínculo Lead → Customer;
- vínculo Customer → Lead;
- data da conversão;
- bloqueio de conversão duplicada;
- histórico;
- auditoria;
- isolamento entre tenants;
- operação HTTP;
- permissões;
- capabilities;
- validação do tipo;
- interface;
- estado de lead convertido;
- link para o Customer;
- internacionalização.

Os dados persistentes de development permaneceram preservados durante toda a
implementação.

## 8.4 — Importação

Foi implementada a fundação completa de importação CSV para os domínios de Leads
e Clientes.

### Registro da importação

Cada upload passa a gerar um registro em `imports`, isolado pelo tenant corrente.

O registro mantém informações como:

- usuário responsável;
- destino da importação;
- nome original do arquivo;
- caminho seguro no storage;
- MIME type;
- tamanho;
- delimitador;
- encoding;
- cabeçalho;
- mapping;
- quantidade de linhas;
- quantidade processada;
- sucessos;
- falhas;
- status;
- timestamps de início e conclusão;
- eventual erro global.

### Linhas de importação

Foi criada a estrutura `import_rows`.

Cada linha processada armazena:

- tenant;
- import correspondente;
- número original da linha no CSV;
- status;
- dados normalizados;
- erros;
- tipo da entidade criada;
- ID da entidade criada.

Existe unicidade por importação e número de linha, fornecendo proteção adicional
contra reprocessamento duplicado.

### CSV

A leitura de CSV suporta:

- vírgula;
- ponto e vírgula;
- cabeçalhos;
- linhas em branco;
- normalização de nomes de colunas;
- aliases;
- colunas adicionais ignoradas;
- detecção de cabeçalhos duplicados.

### Destinos

Foram implementados dois destinos:

- Leads;
- Clientes.

Cada destino possui catálogo próprio de campos suportados e obrigatórios.

### Validação e normalização

O pipeline executa:

1. inspeção do CSV;
2. normalização do cabeçalho;
3. construção do mapping;
4. leitura das linhas;
5. normalização dos valores;
6. validação;
7. separação entre linhas válidas e inválidas.

Foram normalizados, entre outros:

- e-mails;
- telefones;
- tags;
- status e origem do Lead;
- tipo PF/PJ do cliente;
- país do documento;
- tipo do documento;
- número do documento.

### Preview

O preview apresenta:

- total de linhas;
- total válido;
- total inválido;
- colunas ignoradas;
- dados normalizados;
- erros por linha e campo.

O preview não persiste Leads nem Clientes.

### Execução

A execução definitiva processa cada linha de forma independente.

Uma linha inválida não impede as linhas válidas de serem importadas.

Linhas válidas podem criar:

- Leads;
- Clientes;
- e-mail principal do cliente;
- telefone principal do cliente.

### Idempotência

Uma importação concluída não é executada novamente.

Além disso, linhas já registradas em `import_rows` não são processadas novamente.

Isso protege contra duplicação em reexecuções e retries.

### Jobs em fila

Foi criado o Job `ProcessImport`.

O Job:

- restaura o tenant correto;
- carrega a importação dentro do tenant;
- chama o mesmo `ImportExecutionService`;
- possui retries;
- possui timeout;
- trata falhas inesperadas;
- mantém idempotência.

### Estados

A importação suporta os estados:

- `uploaded`;
- `parsed`;
- `processing`;
- `completed`;
- `completed_with_errors`;
- `failed`.

### Governança

Foi adicionada a feature:

`imports`

Também foram adicionadas as permissões:

- `imports.view`;
- `imports.create`.

Os tenants existentes receberam a feature de importação habilitada e os
administradores existentes receberam as novas permissões.

### Interface

Foi implementado fluxo web completo para:

- listar importações;
- iniciar novo upload;
- escolher destino;
- escolher delimitador;
- visualizar preview;
- confirmar processamento;
- visualizar estado;
- visualizar contadores;
- visualizar erros por linha.

### Segurança e multi-tenant

As rotas de importação exigem:

- autenticação;
- feature `imports`;
- permissão adequada.

Imports e linhas de imports utilizam isolamento pelo tenant.

Um usuário não consegue acessar uma importação pertencente a outro tenant.

Os arquivos também são armazenados em diretórios separados por tenant.

### Auditoria

São auditados os eventos:

- `import.uploaded`;
- `import.dispatched`;
- `import.completed`;
- `import.failed`.

### Internacionalização

A interface de importação possui traduções completas nos locales:

- `pt-BR`;
- `en`;
- `es`;
- `ja`;
- `zh-CN`.

A paridade das chaves e a renderização em japonês foram verificadas por testes.

### Banco de dados

Foram aplicadas as migrations:

- `2026_08_16_170000_create_imports_table`;
- `2026_08_16_170100_seed_existing_import_capabilities_and_permissions`.

Após a aplicação, o development manteve os dados anteriores preservados e passou
a possuir as estruturas e capabilities necessárias à importação.

### Validação

A implementação foi coberta por testes de:

- parser CSV;
- normalização de cabeçalho;
- mapping;
- leitura das linhas;
- normalização;
- validação;
- preview;
- ausência de persistência no preview;
- execução de Leads;
- execução de Clientes;
- sucesso parcial;
- erros por linha;
- idempotência;
- isolamento por tenant;
- jobs;
- retries e falhas;
- permissões;
- capabilities;
- auditoria;
- fluxo HTTP;
- interface;
- navegação;
- internacionalização.

## 9.1 — Pipelines

Foi implementada a fundação completa de pipelines comerciais multi-tenant.

### Pipelines

Cada tenant pode possuir múltiplos pipelines.

Cada pipeline possui:

- nome;
- descrição;
- estado ativo/inativo;
- indicação de pipeline padrão.

O nome é único dentro do tenant, mas pode ser reutilizado por tenants diferentes.

### Pipeline padrão

O primeiro pipeline criado para um tenant torna-se padrão automaticamente.

O sistema garante no máximo um pipeline padrão por tenant.

Ao definir outro pipeline como padrão, o anterior deixa de ser padrão de forma
transacional.

Também foi adicionada proteção no PostgreSQL por índice único parcial para impedir
dois pipelines padrão simultâneos no mesmo tenant.

Ao excluir ou desmarcar o pipeline padrão, outro pipeline disponível é promovido
quando necessário.

### Etapas

Cada pipeline pode possuir etapas configuráveis.

Cada etapa possui:

- nome;
- posição;
- estado ativo/inativo.

As etapas são retornadas sempre em ordem de posição.

### Ordenação

A posição é única dentro de cada pipeline.

O serviço de etapas suporta:

- inclusão no final;
- inclusão em posição intermediária;
- movimentação para cima;
- movimentação para baixo;
- reordenação completa;
- compactação das posições após exclusão;
- idempotência de reordenação.

As alterações de posição são executadas transacionalmente para não violar as
constraints de unicidade.

### Multi-tenancy

Pipelines e etapas utilizam o mecanismo `BelongsToTenant`.

Um pipeline de outro tenant não pode ser localizado ou modificado pelo tenant
corrente.

Uma etapa também não pode ser associada a pipeline pertencente a outro tenant.

### Governança

Foi adicionada a feature:

`pipelines`

Também foram adicionadas as permissões:

- `pipelines.view`;
- `pipelines.create`;
- `pipelines.update`;
- `pipelines.delete`.

Os tenants existentes receberam a feature habilitada e os administradores
existentes receberam as quatro novas permissões.

### HTTP

Foi implementado fluxo HTTP para:

- listar pipelines;
- criar pipeline;
- editar pipeline;
- definir pipeline padrão;
- excluir pipeline;
- criar etapa;
- editar etapa;
- excluir etapa;
- reordenar etapas.

As rotas exigem autenticação, feature e permissão adequada.

### Auditoria

São registrados os eventos:

- `pipeline.created`;
- `pipeline.updated`;
- `pipeline.default_changed`;
- `pipeline.deleted`;
- `pipeline_stage.created`;
- `pipeline_stage.updated`;
- `pipeline_stage.deleted`;
- `pipeline_stage.reordered`.

Os registros de auditoria respeitam o isolamento por tenant.

### Interface

Foi criada interface funcional para:

- listagem dos pipelines;
- criação;
- edição;
- definição do padrão;
- ativação e desativação;
- gerenciamento das etapas;
- reordenação.

O refinamento visual continuará em etapa específica de UI/UX.

### Internacionalização

Pipelines possuem traduções completas para:

- `pt-BR`;
- `en`;
- `es`;
- `ja`;
- `zh-CN`.

A paridade das chaves e a renderização baseada no locale do tenant foram
validadas por testes.

### Banco

Foram aplicadas as migrations:

- `2026_08_16_180000_create_pipelines_tables`;
- `2026_08_16_180100_seed_existing_pipeline_capabilities_and_permissions`.

As tabelas criadas são:

- `pipelines`;
- `pipeline_stages`.

O development existente foi preservado durante a aplicação.

### Validação

A implementação possui cobertura para:

- isolamento por tenant;
- múltiplos pipelines;
- pipeline padrão;
- unicidade do padrão;
- criação e edição de etapas;
- ordenação;
- reordenação;
- exclusão e compactação;
- idempotência;
- permissões;
- feature flags;
- HTTP;
- auditoria;
- navegação;
- internacionalização;
- regressão da fundação e CRM.

---

## 9.2 — Oportunidades

Foi implementado o núcleo operacional de oportunidades comerciais multi-tenant.

### Domínio

As oportunidades possuem:

- nome;
- cliente;
- pipeline;
- etapa;
- responsável;
- valor monetário em minor units;
- moeda;
- probabilidade;
- data prevista de fechamento;
- observações.

Os relacionamentos são validados dentro do tenant corrente.

Uma oportunidade não pode utilizar cliente, pipeline, etapa ou responsável
pertencente a outro tenant.

### Pipeline

Quando o pipeline não é informado, o serviço pode utilizar o pipeline padrão.

Quando a etapa não é informada, a primeira etapa ativa disponível é utilizada.

A movimentação entre etapas é suportada e, quando necessário, também altera
o pipeline associado.

### Governança

Foi adicionada a feature:

`opportunities`

Também foram adicionadas as permissões:

- `opportunities.view`;
- `opportunities.create`;
- `opportunities.update`;
- `opportunities.delete`.

Os tenants existentes receberam a feature e os administradores existentes
receberam as permissões correspondentes.

### HTTP e interface

Foi implementado fluxo HTTP para:

- listar oportunidades;
- criar oportunidade;
- editar oportunidade;
- atualizar oportunidade;
- alterar etapa;
- excluir oportunidade.

A interface possui listagem, criação e edição, com traduções para:

- `pt-BR`;
- `en`;
- `es`;
- `ja`;
- `zh-CN`.

### Auditoria

As operações principais de oportunidades são auditadas e respeitam o
isolamento por tenant.

### Banco

Foram aplicadas as migrations:

- `2026_08_16_190000_create_opportunities_table`;
- `2026_08_16_190100_seed_existing_opportunity_capabilities_and_permissions`.

### Pendência conhecida

O requisito de motivo de perda ainda não possui implementação explícita e
permanece aberto no ROADMAP.

---

## 9.3 — Atividades e tarefas

Foi implementado o domínio de atividades comerciais multi-tenant.

### Tipos

São suportados:

- tarefas;
- ligações;
- reuniões;
- follow-up.

### Dados e relacionamentos

As atividades suportam:

- título;
- descrição;
- tipo;
- status;
- cliente;
- oportunidade;
- responsável;
- vencimento;
- data de conclusão.

Os relacionamentos são validados dentro do tenant corrente.

### Estados

O fluxo suporta:

- pendente;
- concluída;
- cancelada;
- conclusão;
- reabertura;
- cancelamento.

### Governança

Foi adicionada a feature:

`activities`

Também foram adicionadas as permissões:

- `activities.view`;
- `activities.create`;
- `activities.update`;
- `activities.delete`.

### HTTP e interface

Foi implementado CRUD HTTP, transições de estado, filtros e interface completa.

As traduções estão disponíveis para:

- `pt-BR`;
- `en`;
- `es`;
- `ja`;
- `zh-CN`.

### Lembretes

Atividades pendentes com responsável e vencimento nas próximas 24 horas podem
gerar lembretes.

O campo `reminder_notified_at` evita o envio repetido do mesmo lembrete.

Foi criado o comando:

`activities:send-reminders`

O scheduler executa esse comando de hora em hora com proteção contra
sobreposição.

### Notificações

Foi adotado o canal database do Laravel.

A aplicação possui:

- tabela `notifications`;
- notificação `ActivityDueReminder`;
- inbox de notificações;
- contador de não lidas;
- leitura individual;
- marcação de todas como lidas;
- isolamento entre usuários;
- isolamento entre tenants.

### Banco

Foram aplicadas as migrations:

- `2026_08_16_200000_create_activities_table`;
- `2026_08_16_200100_seed_existing_activity_capabilities_and_permissions`;
- `2026_08_16_200200_create_notifications_table`;
- `2026_08_16_200300_add_reminder_notification_to_activities_table`.

As seis migrations de Opportunities, Activities e Notifications foram
executadas no PostgreSQL de desenvolvimento no batch 12.

### Validação final da Fase 9

Foi executada regressão completa após as migrations.

Resultado registrado:

- 607 testes;
- 1308 assertions;
- suíte completa verde;
- `git diff --check` limpo;
- Blade compilado;
- scheduler registrado;
- migrations validadas no PostgreSQL.

O checkpoint da implementação foi criado no commit:

`e0eb785 feat(crm): add opportunities activities and notifications`

---

## 9.4 — Dashboard comercial

A implementação do dashboard comercial foi iniciada.

### Fundação de métricas

Foi criado o `DashboardService` com suporte a:

- quantidade total de oportunidades;
- valor total do pipeline;
- valor ponderado pela probabilidade;
- atividades pendentes;
- atividades atrasadas;
- atividades vencendo nas próximas 24 horas;
- oportunidades agrupadas por etapa;
- valor por etapa;
- próximas atividades ordenadas por vencimento.

Todas as consultas utilizam o escopo multi-tenant existente.

### Testes

O serviço possui cinco testes próprios cobrindo:

- métricas de oportunidades;
- métricas de atividades;
- agrupamento por etapa;
- ordenação das próximas atividades;
- isolamento entre tenants.

A validação atual terminou com:

- 5 testes do DashboardService / 15 assertions;
- 55 testes / 103 assertions na regressão CRM;
- `git diff --check` limpo.

### Próximo passo


### 9.4 concluída — Dashboard comercial

A etapa 9.4 foi concluída com integração completa do dashboard
comercial.

Entregas finais:

- métricas de leads;
- taxa de conversão de leads;
- métricas de oportunidades;
- valor total do pipeline;
- valor ponderado pela probabilidade;
- pipeline agrupado por etapa;
- oportunidades agrupadas por responsável;
- métricas de atividades;
- atividades pendentes;
- atividades atrasadas;
- atividades vencendo nas próximas 24 horas;
- próximas atividades;
- integração com o controller;
- interface condicionada por permissões;
- interface condicionada por feature flags;
- internacionalização;
- isolamento por tenant;
- testes HTTP e de serviço.

Validação final da etapa:

- DashboardServiceTest + DashboardHttpTest:
  19 testes e 65 assertions;
- suíte completa:
  626 testes e 1373 assertions;
- sintaxe PHP validada;
- Blade compilado;
- migrations aplicadas;
- scheduler de lembretes preservado;
- git diff --check sem erros.

### Próximo passo

FASE 10 — Produtos, Serviços e Propostas.

Iniciar 10.1 — Catálogo.

---

## 10.1 — Catálogo concluído

A etapa 10.1 implementou o catálogo comercial multi-tenant da plataforma.

### Domínio

Foi criado `CatalogItem` com suporte a:

- produtos;
- serviços;
- SKU/código opcional;
- preço armazenado em minor units;
- moeda ISO suportada pela fundação global;
- status ativo/inativo;
- isolamento por tenant.

O código é único dentro do tenant e pode ser reutilizado entre tenants
diferentes.

### Serviço e validações

Foi criado `CatalogService` para centralizar criação e atualização dos itens.

A implementação cobre:

- normalização de campos textuais;
- código vazio convertido para `null`;
- moeda padrão herdada do tenant;
- atualização parcial;
- ativação e desativação;
- validação de tipo;
- validação de preço;
- validação de moeda;
- proteção contra acesso cross-tenant.

### Governança

O catálogo foi integrado à governança existente com:

- feature flag própria;
- permissões de visualização, criação, atualização e exclusão;
- sincronização das permissões administrativas;
- isolamento da feature entre tenants.

### HTTP e interface

Foram adicionadas seis rotas HTTP para:

- listagem;
- criação;
- formulário de criação;
- edição;
- atualização;
- exclusão.

A interface foi integrada à navegação principal e respeita feature flag e
permissões.

### Internacionalização

O catálogo possui traduções para todos os locales suportados:

- pt-BR;
- en;
- es;
- ja;
- zh-CN.

### Banco

Foram aplicadas as migrations:

- `2026_08_16_210000_create_catalog_items_table`;
- `2026_08_16_210100_seed_existing_catalog_capabilities_and_permissions`.

As migrations foram executadas no PostgreSQL de desenvolvimento no batch 13.

A tabela `catalog_items` possui:

- chave estrangeira para tenant com cascade delete;
- unicidade de código por tenant;
- índice composto por tenant, tipo e status.

### Validação final

A validação final da etapa terminou com:

- suíte específica: 72 testes e 140 assertions;
- suíte completa: 667 testes e 1444 assertions;
- sintaxe PHP validada;
- traduções PHP validadas;
- Blade compilado;
- seis rotas HTTP registradas;
- migrations aplicadas;
- estrutura da tabela validada;
- isolamento multi-tenant preservado;
- `git diff --check` limpo;
- nenhum arquivo temporário detectado.

### Próximo passo

10.2 — Propostas e orçamentos.

---

## 10.2 — Propostas e orçamentos concluída

A etapa 10.2 implementou o fluxo completo de propostas comerciais
multi-tenant.

### Domínio

Foram criados:

- Proposal;
- ProposalItem;
- ProposalStatus;
- vínculo opcional com cliente;
- vínculo opcional com oportunidade;
- itens ordenados;
- snapshot dos dados comerciais do catálogo.

O snapshot preserva nome, código, tipo e preço utilizados na proposta,
evitando alterações históricas quando o catálogo for modificado depois.

### Valores e itens

A proposta suporta:

- múltiplos itens;
- itens provenientes do catálogo;
- itens manuais;
- quantidade decimal;
- preço unitário;
- desconto;
- impostos extensíveis;
- subtotal;
- desconto total;
- impostos totais;
- total final;
- moeda do tenant.

Os valores monetários permanecem armazenados em minor units.

Os cálculos são centralizados no ProposalService.

### Governança

Foi adicionada a feature:

proposals

Também foram adicionadas as permissões:

- proposals.view;
- proposals.create;
- proposals.update;
- proposals.delete.

A feature e as permissões respeitam o tenant atual.

### HTTP e interface

Foram implementadas oito rotas para:

- listar propostas;
- abrir formulário de criação;
- criar;
- editar;
- atualizar;
- excluir;
- gerar PDF;
- enviar por e-mail.

A interface suporta:

- filtros;
- criação e edição;
- múltiplos itens dinâmicos;
- seleção de catálogo;
- itens manuais;
- quantidade;
- desconto;
- impostos;
- prévia de totais;
- validade;
- status;
- download de PDF;
- envio.

### Internacionalização

Propostas possuem traduções para:

- pt-BR;
- en;
- es;
- ja;
- zh-CN.

### PDF

Foi integrada a dependência:

`barryvdh/laravel-dompdf`

A proposta pode ser exportada em PDF contendo:

- emissor;
- cliente;
- oportunidade;
- status;
- validade;
- itens;
- quantidade;
- preços;
- descontos;
- impostos;
- subtotal;
- total;
- observações.

O download exige autenticação, feature, permissão e respeita o isolamento
multi-tenant.

### Envio

Foi criado ProposalMail.

O fluxo de envio:

- valida o e-mail do destinatário;
- gera e anexa o PDF;
- utiliza o locale do tenant;
- altera a proposta para sent somente após envio bem-sucedido;
- mantém a proposta em draft quando o transporte falha;
- impede envio de proposta pertencente a outro tenant.

### Auditoria

São registrados:

- proposal.created;
- proposal.updated;
- proposal.deleted;
- proposal.sent.

Os registros utilizam o mecanismo existente de auditoria e permanecem
isolados por tenant.

### Banco

Foram aplicadas as migrations:

- 2026_08_16_220000_create_proposals_tables;
- 2026_08_16_220100_seed_existing_proposal_capabilities_and_permissions.

As duas migrations foram aplicadas no PostgreSQL de desenvolvimento no
batch 14.

### Validação

A etapa foi validada com:

- ProposalSendTest: 6 testes e 20 assertions;
- regressão específica final de propostas: 55 testes e 143 assertions;
- suíte completa: 715 testes e 1556 assertions;
- oito rotas de propostas registradas;
- sintaxe PHP validada;
- Blade compilado;
- PDF validado;
- isolamento multi-tenant validado;
- falha de transporte de e-mail validada;
- git diff --check limpo.

### Checkpoint Git

A implementação funcional foi consolidada no commit:

`0890cb0 feat(proposals): add multi-tenant commercial proposals`

### Próximo passo

10.3 — Fechamento de venda.

---

## 10.3 — Fechamento de venda

A etapa 10.3 implementou o fechamento comercial multi-tenant da plataforma.

### Domínio

Foi criado `Sale` com suporte a:

- tenant;
- cliente;
- oportunidade;
- proposta opcional;
- número de venda;
- valor final em minor units;
- moeda;
- data de fechamento;
- snapshots comerciais da origem da venda.

Cada oportunidade pode possuir apenas uma venda.

O número da venda é único dentro do tenant e pode ser reutilizado entre
tenants diferentes.

### Fechamento

Foi criado `SaleService` para centralizar o fechamento comercial.

O fluxo permite:

- fechamento direto de oportunidade;
- valor final personalizado;
- geração automática de número;
- número personalizado normalizado;
- fechamento baseado em proposta aceita;
- uso do valor final da proposta aceita;
- rejeição de proposta não aceita;
- rejeição de proposta pertencente a outra oportunidade;
- rejeição de proposta pertencente a outro cliente;
- rejeição de fechamento duplicado;
- validação de valor;
- validação de moeda;
- rollback completo em falha.

### Propostas

O ciclo comercial de propostas foi explicitamente validado para:

- proposta aceita;
- proposta recusada.

Propostas não aceitas não podem ser utilizadas como origem de uma venda.

### Governança

Sales foi integrado à governança existente com:

- feature flag `sales`;
- permissão de visualização;
- permissão de criação;
- permissão de atualização;
- permissão de exclusão;
- sincronização administrativa;
- isolamento da feature entre tenants.

### HTTP e interface

Foram adicionadas três rotas:

- listagem de vendas;
- formulário de fechamento a partir de oportunidade;
- criação da venda.

A listagem de oportunidades passou a:

- exibir a ação de fechar venda quando permitido;
- respeitar feature flag e permissão;
- exibir o estado de venda fechada;
- impedir novo fechamento visual quando a oportunidade já possui venda.

A navegação principal também foi integrada ao módulo de vendas.

### Internacionalização

Sales possui traduções para:

- pt-BR;
- en;
- es;
- ja;
- zh-CN.

### Auditoria e histórico

O fechamento registra o evento:

`sale.closed`

A auditoria contém dados do fechamento e permanece isolada por tenant.

A listagem de vendas passou a exibir um histórico dos fechamentos recentes,
reutilizando `AuditLog`, sem criar uma estrutura duplicada de persistência.

O histórico respeita:

- tenant atual;
- permissão de visualização;
- usuário responsável;
- data no timezone do tenant;
- ação;
- descrição.

### Banco

Foram aplicadas as migrations:

- `2026_08_16_230000_create_sales_table`;
- `2026_08_16_230100_seed_existing_sales_capabilities_and_permissions`.

As duas migrations foram aplicadas no PostgreSQL de desenvolvimento no
batch 15.

### Validação

A etapa foi validada com:

- SaleHistoryTest: 3 testes e 9 assertions;
- suíte final específica de Sales: 48 testes e 114 assertions;
- regressão comercial final: 159 testes e 326 assertions;
- suíte completa: 765 testes e 1676 assertions;
- três rotas de Sales registradas;
- sintaxe PHP validada;
- traduções validadas;
- Blade compilado;
- fechamento direto validado;
- fechamento por proposta aceita validado;
- proposta recusada validada;
- proteção contra fechamento duplicado validada;
- auditoria validada;
- histórico validado;
- isolamento multi-tenant validado;
- migrations aplicadas no batch 15;
- `git diff --check` limpo.

### Checkpoint Git

A implementação funcional foi consolidada no commit:

`857437b feat(sales): add multi-tenant commercial closing`

### Próximo passo

11.1 — Contas a receber.

---

## 11.1 — Contas a receber

A etapa 11.1 implementou o domínio financeiro de contas a receber
multi-tenant da plataforma.

### Domínio

Foi criado `Receivable` com suporte a:

- tenant;
- cliente;
- venda opcional;
- título;
- moeda;
- valor em minor units;
- vencimento;
- status;
- data de pagamento;
- referência de pagamento.

O vínculo com venda é opcional e permite múltiplos títulos relacionados
à mesma venda, preservando a possibilidade de parcelamento.

### Status

Foi criado `ReceivableStatus` com os estados:

- pending;
- paid;
- cancelled.

O status inicial é pending.

Somente títulos pendentes podem ser alterados, pagos ou cancelados.

### Serviço

Foi criado `ReceivableService` para centralizar as regras de negócio.

O serviço suporta:

- criação;
- atualização parcial;
- moeda padrão do tenant;
- associação opcional com venda;
- pagamento;
- cancelamento;
- normalização do título;
- normalização da moeda;
- validação de valor;
- validação do vencimento;
- rejeição de cliente pertencente a outro tenant;
- rejeição de venda pertencente a outro tenant;
- rejeição de venda pertencente a outro cliente;
- rejeição de alteração em título não pendente.

### Pagamento

O pagamento registra:

- status paid;
- paid_at;
- payment_reference opcional.

Uma tentativa inválida de novo pagamento é rejeitada.

### Governança

Foi adicionada a feature:

`receivables`

Também foram adicionadas as permissões:

- receivables.view;
- receivables.create;
- receivables.update;
- receivables.delete.

Administradores recebem as permissões pelo mecanismo existente de
RolePermissionSync.

A feature é isolada entre tenants.

### HTTP e interface

Foram adicionadas sete rotas:

- listagem;
- criação;
- armazenamento;
- edição;
- atualização;
- pagamento;
- cancelamento.

A interface permite:

- listar contas a receber;
- criar título;
- editar título pendente;
- associar cliente;
- associar venda opcional;
- definir valor;
- definir moeda;
- definir vencimento;
- visualizar status;
- marcar como paga;
- informar referência de pagamento;
- cancelar título.

A navegação respeita simultaneamente a feature e a permissão de
visualização.

### Internacionalização

Contas a receber possuem traduções para:

- pt-BR;
- en;
- es;
- ja;
- zh-CN.

### Auditoria

São registrados os eventos:

- receivable.created;
- receivable.updated;
- receivable.paid;
- receivable.cancelled.

Os eventos reutilizam o mecanismo central de `AuditService`.

Falhas de pagamento não geram eventos adicionais.

A auditoria permanece isolada por tenant.

### Banco

Foram aplicadas as migrations:

- `2026_08_16_240000_create_receivables_table`;
- `2026_08_16_240100_seed_existing_receivable_capabilities_and_permissions`.

As duas migrations foram aplicadas no PostgreSQL de desenvolvimento no
batch 16.

A tabela `receivables` foi validada com 13 colunas.

A migration de governança foi validada para:

- dois tenants existentes;
- feature receivables habilitada nos dois tenants;
- dois administradores existentes;
- quatro permissões de receivables em cada administrador.

### Validação

A etapa foi validada com:

- suíte específica final de Receivables: 50 testes e 108 assertions;
- regressão financeiro-comercial: 175 testes e 371 assertions;
- suíte completa: 815 testes e 1784 assertions;
- sete rotas de Receivables registradas;
- sintaxe PHP validada;
- traduções validadas;
- Blade compilado;
- isolamento multi-tenant validado;
- feature flag validada;
- permissões validadas;
- pagamento validado;
- cancelamento validado;
- auditoria validada;
- falha de pagamento validada;
- cinco locales validados;
- caracteres de controle validados;
- migrations aplicadas no batch 16;
- `git diff --check` limpo.

### Checkpoint Git

A implementação funcional foi consolidada no commit:

`58c14bb feat(finance): add multi-tenant accounts receivable`

### Próximo passo

11.2 — Cobranças.
---

## 11.2 — Cobranças

A etapa 11.2 implementou o domínio financeiro de cobranças multi-tenant
integrado às contas a receber da plataforma.

### Domínio

Foi criado `Charge` com suporte a:

- tenant;
- conta a receber;
- tentativa;
- status;
- canal;
- referência externa;
- data programada;
- data de envio;
- data de pagamento;
- data de falha;
- motivo da falha;
- metadados.

Múltiplas cobranças podem estar associadas à mesma conta a receber,
permitindo registrar tentativas sucessivas sem perder o histórico.

O número da tentativa é positivo e incrementado para novas cobranças da
mesma conta a receber.

### Status

Foi criado `ChargeStatus` para representar o ciclo de vida da cobrança.

O fluxo suporta cobranças:

- pendentes;
- enviadas;
- falhas;
- pagas;
- canceladas.

Cobranças pagas não podem ser canceladas.

Cobranças com falha podem ser canceladas.

Contas a receber pagas ou canceladas não podem receber novas cobranças.

Quando uma conta a receber é paga, cobranças abertas relacionadas são
atualizadas para refletir o pagamento.

### Serviço

Foi criado `ChargeService` para centralizar as regras de negócio.

O serviço suporta:

- criação de cobrança;
- incremento automático de tentativa;
- marcação como enviada;
- registro de falha;
- exigência de motivo para falha;
- cancelamento;
- integração com o status da conta a receber;
- rejeição de conta a receber pertencente a outro tenant;
- rejeição de alteração de cobrança pertencente a outro tenant;
- proteção de estados finais;
- auditoria das transições.

### Histórico e consultas

Foi criado `ChargeQueryService` para centralizar consultas operacionais de
cobrança.

O serviço permite consultar:

- cobranças programadas até uma data;
- cobranças pendentes que exigem lembrete;
- cobranças relacionadas a contas vencidas;
- cobranças futuras dentro de uma janela;
- informações isoladas pelo tenant atual.

Cobranças já enviadas não retornam como pendentes para lembrete.

Cobranças pagas não retornam como atrasadas.

### Lembretes e atrasos

A etapa adicionou suporte para identificar cobranças que precisam de
lembrete e cobranças relacionadas a contas a receber vencidas.

O controle considera o estado da cobrança e o vencimento da conta a
receber, preservando o isolamento multi-tenant.

### Recorrência

Foi criado `ChargeRecurrence` para representar regras recorrentes de
cobrança.

Também foi criado `ChargeRecurrenceFrequency` para representar a frequência
da recorrência.

O `ChargeRecurrenceService` suporta:

- criação de recorrência;
- processamento de recorrências vencidas;
- geração de cobrança a partir da recorrência;
- frequência mensal;
- frequência semanal;
- intervalo configurável;
- avanço automático da próxima execução;
- data final opcional;
- desativação automática após a data final;
- desativação quando a conta a receber é paga;
- cancelamento;
- validação de intervalo;
- isolamento por tenant.

Recorrências vencidas são processadas somente quando estão ativas e dentro
das regras configuradas.

### Governança

Foi adicionada a feature:

`charges`

Também foram adicionadas as permissões:

- charges.view;
- charges.create;
- charges.update;
- charges.delete.

Administradores recebem as permissões pelo mecanismo existente de
`RolePermissionSync`.

A feature permanece isolada entre tenants.

### HTTP e interface

Foram adicionadas seis rotas de cobranças:

- listagem;
- criação;
- armazenamento;
- marcação como enviada;
- registro de falha;
- cancelamento.

A interface permite:

- listar cobranças;
- criar cobrança;
- associar conta a receber;
- visualizar status;
- visualizar tentativas;
- marcar cobrança como enviada;
- registrar falha;
- cancelar cobrança.

A navegação respeita simultaneamente a feature e a permissão de
visualização.

### Internacionalização

Cobranças possuem traduções para:

- pt-BR;
- en;
- es;
- ja;
- zh-CN.

### Auditoria

São registrados os eventos:

- charge.created;
- charge.sent;
- charge.failed;
- charge.cancelled.

Os eventos reutilizam o mecanismo central de auditoria da plataforma.

Operações inválidas não criam eventos adicionais.

A auditoria permanece isolada por tenant.

### Banco

Foram aplicadas as migrations:

- `2026_08_17_010000_create_charges_table`;
- `2026_08_17_010100_seed_existing_charge_capabilities_and_permissions`;
- `2026_08_17_010200_create_charge_recurrences_table`.

As três migrations foram aplicadas no PostgreSQL de desenvolvimento no
batch 17.

Foram validadas:

- tabela `charges`;
- tabela `charge_recurrences`;
- quatro permissões de charges;
- feature charges nos tenants existentes;
- permissões administrativas de charges.

### Validação

A etapa foi validada com:

- suíte específica final de Charges: 68 testes e 136 assertions;
- regressão financeira de Charges e Receivables: 118 testes e 244 assertions;
- seis rotas de Charges registradas;
- migrations aplicadas no batch 17;
- sintaxe PHP validada;
- traduções validadas;
- Blade compilado;
- geração de cobranças validada;
- histórico e consultas validados;
- lembretes validados;
- controle de atrasos validado;
- recorrência validada;
- isolamento multi-tenant validado;
- feature flag validada;
- permissões validadas;
- auditoria validada;
- caracteres de controle validados;
- `git diff --check` limpo.

### Checkpoint Git

A implementação funcional foi consolidada no commit:

`2d1684d feat(finance): add multi-tenant charges`

### Próximo passo

11.3 — Indicadores financeiros.

---

## 11.3 — Indicadores financeiros

A etapa 11.3 concluiu a Fase 11 — Financeiro Operacional com indicadores
financeiros multi-tenant baseados nas contas a receber da plataforma.

### Serviço

Foi criado `FinancialIndicatorService` para centralizar o cálculo dos
indicadores financeiros.

O serviço suporta:

- total recebido;
- total a receber;
- total atrasado;
- filtro de recebimentos por período;
- receita agrupada por período;
- receita agregada por cliente;
- resumo dos principais indicadores;
- isolamento pelo tenant atual.

Os valores recebidos utilizam contas a receber pagas.

Os valores a receber utilizam contas pendentes.

Os valores atrasados consideram contas pendentes com vencimento anterior à
data de referência.

Contas já pagas não são consideradas atrasadas.

### Receita por período

A receita por período é calculada a partir das contas a receber pagas.

Os recebimentos podem ser filtrados por intervalo de datas e agrupados por
dia, permitindo acompanhar a evolução da receita ao longo do período.

### Receita por cliente

A receita por cliente agrega contas a receber pagas por cliente.

O indicador preserva o isolamento multi-tenant e não mistura informações de
clientes pertencentes a tenants diferentes.

### Governança

Foi adicionada governança específica para indicadores financeiros.

A feature de indicadores financeiros foi integrada ao catálogo de features
da plataforma.

Também foi adicionada permissão específica de visualização.

Administradores recebem a permissão pelo mecanismo existente de
`RolePermissionSync`.

A feature e seus dados permanecem isolados entre tenants.

### HTTP e interface

Foi criado `FinancialIndicatorController`.

Foi registrada a rota:

`financial-indicators.index`

A página de indicadores apresenta os principais números financeiros e
permite consultar informações respeitando o tenant atual.

O acesso exige simultaneamente:

- autenticação;
- feature habilitada;
- permissão de visualização.

Intervalos de datas inválidos são rejeitados.

A navegação exibe indicadores financeiros somente quando o usuário possui
acesso autorizado.

### Internacionalização

A interface de indicadores financeiros possui traduções para:

- pt-BR;
- en;
- es;
- ja;
- zh-CN.

A página utiliza o locale configurado para o tenant.

### Banco e provisionamento

Foi criada e aplicada a migration:

`2026_08_17_020000_seed_existing_financial_indicator_capabilities_and_permissions`

A migration foi aplicada no PostgreSQL de desenvolvimento no batch 18.

Ela provisiona as capacidades e permissões necessárias para tenants e
administradores existentes.

### Validação

A etapa foi validada com:

- suíte específica de indicadores financeiros: 21 testes e 37 assertions;
- regressão financeira final: 170 testes e 350 assertions;
- serviço de indicadores validado;
- total recebido validado;
- total a receber validado;
- total atrasado validado;
- filtro por período validado;
- receita por período validada;
- receita por cliente validada;
- resumo financeiro validado;
- isolamento multi-tenant validado;
- feature flag validada;
- permissão validada;
- rota HTTP validada;
- interface validada;
- internacionalização validada;
- migration aplicada no batch 18;
- `git diff --check` limpo.

### Checkpoint Git

A implementação funcional foi consolidada no commit:

`b47dcb8 feat(finance): add multi-tenant financial indicators`

### Fechamento da Fase 11

Com a conclusão dos indicadores financeiros, a Fase 11 — Financeiro
Operacional foi concluída.

A fase passou a oferecer:

- contas a receber;
- cobranças;
- histórico de cobranças;
- lembretes;
- controle de atrasos;
- recorrência de cobranças;
- indicadores de valores recebidos;
- indicadores de valores a receber;
- indicadores de valores atrasados;
- receita por período;
- receita por cliente;
- governança multi-tenant;
- permissões;
- feature flags;
- internacionalização;
- auditoria nos fluxos financeiros aplicáveis.

### Próximo passo

12.1 — E-mail.
---

## 12.1 — E-mail

A etapa 12.1 iniciou a Fase 12 — Comunicação Omnichannel com uma base de comunicação por e-mail multi-tenant integrada à governança da plataforma.

### Domínio

Foi criado o domínio de mensagens de e-mail com `EmailMessage`, status próprios e relacionamento com o tenant.

As mensagens suportam:

- destinatário;
- nome opcional do destinatário;
- assunto;
- corpo;
- estado da mensagem;
- registro de envio;
- registro de falha;
- motivo de falha;
- novas tentativas;
- isolamento pelo tenant atual.

Transições inválidas de estado são rejeitadas e não deixam alterações parciais.

### Templates

Foi criado `EmailTemplate` para permitir templates reutilizáveis por tenant.

O serviço de templates suporta:

- criação;
- atualização;
- placeholders;
- descoberta de variáveis;
- renderização de assunto;
- renderização de corpo;
- validação de variáveis ausentes;
- rejeição de variáveis desconhecidas;
- isolamento multi-tenant.

### Envio

A camada de envio foi organizada em serviços específicos.

Foram implementados:

- `EmailMessageService`;
- `EmailDeliveryService`;
- `EmailQueueService`;
- `SendEmailMessageJob`;
- `TenantEmailMessageMail`.

O fluxo permite criar mensagens, enviá-las, registrar falhas e realizar novas tentativas de forma controlada.

Mensagens já enviadas não são reenviadas indevidamente.

### Fila

O envio possui processamento assíncrono por job.

O job restaura o contexto correto do tenant antes de acessar e processar a mensagem.

Também foram validados:

- despacho de mensagens pendentes;
- retry de mensagens com falha;
- idempotência;
- proteção contra acesso entre tenants;
- configuração de tentativas do job.

### Histórico e auditoria

As principais transições do ciclo de vida do e-mail são auditadas.

Foram validados eventos para:

- criação;
- envio;
- falha;
- retry.

Operações inválidas não criam eventos adicionais.

A auditoria permanece isolada por tenant.

### Governança

A funcionalidade de e-mail foi integrada ao catálogo de features e permissões da plataforma.

Foram adicionadas permissões específicas para operações do canal de e-mail e integração com o mecanismo existente de `RolePermissionSync`.

Administradores recebem as permissões previstas pelo provisionamento.

A feature e as permissões permanecem isoladas entre tenants.

### HTTP e interface

Foram criados:

- `EmailController`;
- `EmailTemplateController`.

A interface oferece:

- listagem de mensagens;
- criação de e-mail;
- envio;
- retry;
- gerenciamento de templates.

As rotas exigem autenticação, feature habilitada e permissões compatíveis com cada operação.

A interface respeita o tenant atual.

### Internacionalização

A interface de e-mail possui traduções para:

- pt-BR;
- en;
- es;
- ja;
- zh-CN.

As telas utilizam o locale configurado para o tenant.

### Banco

Foram criadas e aplicadas as migrations:

- `2026_08_17_030000_create_email_messages_table`;
- `2026_08_17_030100_create_email_templates_table`;
- `2026_08_17_030200_seed_existing_email_capabilities_and_permissions`.

As migrations foram aplicadas no PostgreSQL de desenvolvimento no batch 19.

Foram validados:

- tabela `email_messages`;
- tabela `email_templates`;
- capacidades de e-mail;
- permissões de e-mail;
- provisionamento dos tenants existentes;
- permissões administrativas.

Antes da aplicação das migrations foi criado backup PostgreSQL de segurança.

### Validação

A etapa foi validada com:

- suíte final de E-mail: 113 testes e 200 assertions;
- domínio de mensagens validado;
- templates validados;
- envio validado;
- fila validada;
- retry validado;
- idempotência validada;
- histórico e auditoria validados;
- isolamento multi-tenant validado;
- feature flag validada;
- permissões validadas;
- rotas HTTP validadas;
- interface validada;
- internacionalização validada;
- migrations aplicadas no batch 19;
- PostgreSQL validado;
- aplicação web validada;
- `git diff --check` limpo.

### Checkpoint Git

A implementação funcional foi consolidada no commit:

`dd941c1 feat(email): add multi-tenant email communication`

### Próximo passo

12.2 — WhatsApp.

---

## 12.2 — WhatsApp

A etapa 12.2 expandiu a Fase 12 — Comunicação Omnichannel com suporte multi-tenant a WhatsApp.

### Domínio

Foi criado o domínio de mensagens WhatsApp com:

- `WhatsAppMessage`;
- `WhatsAppMessageStatus`;
- relacionamento com o tenant;
- mensagens outbound e inbound;
- estados pending, sent, delivered, read, failed e received;
- isolamento multi-tenant.

### Templates

Foi criado suporte a templates WhatsApp por tenant.

A camada de templates suporta:

- criação;
- atualização;
- placeholders;
- descoberta de variáveis;
- renderização;
- validação de variáveis ausentes;
- rejeição de variáveis desconhecidas;
- isolamento por tenant.

### Provider e conexão por tenant

Foi criada uma camada desacoplada de provider com:

- contrato `WhatsAppProvider`;
- `WhatsAppProviderRegistry`;
- `WhatsAppProviderConfig`;
- configuração por tenant;
- resolução de configuração ativa;
- suporte a múltiplos providers;
- isolamento de configurações entre tenants.

### Envio e fila

O envio foi estruturado por meio de:

- `WhatsAppDeliveryService`;
- `WhatsAppQueueService`;
- `SendWhatsAppMessageJob`.

Foram validados:

- envio de mensagens pendentes;
- falha de provider;
- processamento assíncrono;
- restauração do tenant no job;
- prevenção de reenvio de mensagens não pendentes;
- proteção contra acesso entre tenants.

### Recebimento e webhooks

Foi criada a camada de recebimento de eventos com:

- `WhatsAppWebhookService`;
- `WhatsAppWebhookHttpService`;
- `WhatsAppWebhookVerifier`;
- `WhatsAppWebhookNormalizer`;
- `WhatsAppWebhookController`.

O webhook suporta eventos de:

- mensagem recebida;
- entrega;
- leitura;
- falha.

Também foram validados:

- idempotência de eventos duplicados;
- provider desconhecido;
- tenant inexistente;
- tenant inativo;
- assinatura inválida;
- isolamento entre tenants.

A rota pública do webhook foi explicitamente excluída do `ResolveTenant` por hostname, pois o tenant é resolvido pelo `tenantSlug` da própria URL.

### Governança

A funcionalidade de WhatsApp foi integrada à governança existente da plataforma.

Foram adicionados:

- feature `whatsapp`;
- permissão `whatsapp.view`;
- permissão `whatsapp.create`;
- permissão `whatsapp.send`;
- permissão `whatsapp.templates`.

O `PermissionSeeder` e o `RolePermissionSync` foram atualizados seguindo o mesmo padrão já utilizado pelo canal de e-mail.

### HTTP e interface

Foram criados:

- `WhatsAppController`;
- `WhatsAppTemplateController`.

A interface oferece:

- histórico de mensagens;
- criação de mensagens;
- envio;
- gerenciamento de templates.

As rotas exigem autenticação, feature habilitada e permissões compatíveis com cada operação.

O menu principal exibe WhatsApp apenas quando o tenant possui a feature habilitada e o usuário possui `WHATSAPP_VIEW`.

### Internacionalização

A interface WhatsApp possui traduções para:

- pt-BR;
- en;
- es;
- ja;
- zh-CN.

### Auditoria

O ciclo de vida de mensagens WhatsApp passou a registrar auditoria para:

- criação;
- envio;
- entrega;
- leitura;
- falha;
- recebimento.

Também foram validados:

- ausência de logs extras em transições inválidas;
- isolamento de logs entre tenants;
- compatibilidade com a auditoria já existente do canal de e-mail.

### Banco

Foram criadas e aplicadas as migrations:

- `2026_08_17_040000_create_whatsapp_messages_table`;
- `2026_08_17_040100_create_whatsapp_templates_table`;
- `2026_08_17_040200_create_whatsapp_provider_configs_table`.

As migrations foram aplicadas no PostgreSQL de desenvolvimento no batch 20.

Foram validadas as tabelas:

- `whatsapp_messages`;
- `whatsapp_templates`;
- `whatsapp_provider_configs`.

Antes da aplicação das migrations foi criado backup PostgreSQL de segurança.

### Validação

A etapa foi validada com:

- suíte final WhatsApp: 111 testes e 186 assertions;
- domínio de mensagens validado;
- templates validados;
- provider e configuração por tenant validados;
- envio validado;
- fila validada;
- recebimento validado;
- webhooks validados;
- idempotência validada;
- histórico validado;
- auditoria validada;
- isolamento multi-tenant validado;
- feature flag validada;
- permissões validadas;
- rotas HTTP validadas;
- interface validada;
- internacionalização validada;
- migrations aplicadas no batch 20;
- PostgreSQL validado;
- regressão do canal de e-mail preservada;
- `git diff --check` limpo.

### Checkpoints Git

A implementação principal foi consolidada no commit:

`6a80a30 feat(whatsapp): add multi-tenant whatsapp communication`

A auditoria do ciclo de vida foi consolidada no commit:

`1552cf9 feat(whatsapp): audit message lifecycle`

### Próximo passo

12.3 — Caixa de entrada.

---

## 12.3 — Caixa de entrada

A etapa 12.3 concluiu a Fase 12 — Comunicação Omnichannel com uma caixa de entrada unificada multi-tenant para os canais de e-mail e WhatsApp.

### Conversas

Foi criado o domínio `Conversation`, permitindo consolidar comunicações por canal e endereço externo dentro do tenant atual.

As conversas suportam:

- canais de e-mail e WhatsApp;
- status open, pending e closed;
- nome de exibição;
- responsável;
- associação a lead ou cliente;
- registro da última mensagem;
- isolamento multi-tenant.

A resolução de conversas é idempotente por tenant, canal e endereço externo.

### Mensagens e integração dos canais

As mensagens existentes de e-mail e WhatsApp foram integradas às conversas.

A camada de integração permite:

- associação automática de mensagens às conversas;
- consolidação do histórico dos dois canais;
- preservação dos serviços existentes de e-mail e WhatsApp;
- isolamento entre tenants.

### Caixa de entrada

Foram criados serviços específicos para consulta e composição da caixa de entrada:

- `ConversationInboxService`;
- `ConversationHistoryService`;
- `ConversationMessageService`;
- `ConversationService`.

A caixa de entrada suporta:

- listagem de conversas;
- busca;
- filtros;
- atribuição de responsável;
- alteração de status;
- associação a lead ou cliente;
- histórico unificado de mensagens;
- visualização de detalhes da conversa.

### Governança

A caixa de entrada foi integrada à governança existente da plataforma.

Foram adicionados feature e permissões específicas para acesso, atribuição e gerenciamento das conversas.

O provisionamento administrativo foi integrado ao `PermissionSeeder` e ao `RolePermissionSync`.

### HTTP e interface

Foi criado `ConversationController` com suporte a respostas HTML e JSON.

As rotas da caixa de entrada permitem:

- listar conversas;
- visualizar uma conversa;
- atribuir responsável;
- alterar o status.

A interface foi integrada à navegação principal e respeita feature, permissões e tenant atual.

### Internacionalização

A interface da caixa de entrada possui traduções para:

- pt-BR;
- en;
- es;
- ja;
- zh-CN.

### Banco

Foram criadas e aplicadas as migrations:

- `2026_08_17_050000_create_conversations_table`;
- `2026_08_17_050100_link_communication_messages_to_conversations`.

As migrations foram aplicadas no PostgreSQL de desenvolvimento no batch 21.

Foram validados:

- tabela `conversations`;
- relacionamento das mensagens de e-mail com conversas;
- relacionamento das mensagens WhatsApp com conversas;
- índices e constraints da estrutura de conversas;
- isolamento multi-tenant.

Antes da aplicação das migrations foi criado backup PostgreSQL de segurança.

### Validação

A etapa foi validada com:

- suíte final da Caixa de entrada: 97 testes e 190 assertions;
- domínio de conversas validado;
- resolução idempotente validada;
- associação automática de mensagens validada;
- histórico unificado validado;
- busca e filtros validados;
- atribuição validada;
- estados de atendimento validados;
- associação a lead/cliente validada;
- isolamento multi-tenant validado;
- feature flag validada;
- permissões validadas;
- rotas HTTP validadas;
- respostas HTML e JSON validadas;
- interface validada;
- internacionalização validada;
- migrations aplicadas no batch 21;
- PostgreSQL validado;
- regressões dos canais de e-mail e WhatsApp preservadas;
- `git diff --check` limpo.

### Checkpoint Git

A implementação funcional foi consolidada no commit:

`392d061 feat(inbox): add multi-tenant unified inbox`

### Fechamento da Fase 12

Com a conclusão da Caixa de entrada, a Fase 12 — Comunicação Omnichannel foi concluída.

A fase passou a oferecer:

- comunicação por e-mail;
- comunicação por WhatsApp;
- templates por canal;
- envio e recebimento;
- webhooks;
- processamento assíncrono;
- histórico de comunicação;
- auditoria;
- caixa de entrada unificada;
- conversas multi-canal;
- atribuição e estados de atendimento;
- busca e filtros;
- isolamento multi-tenant;
- governança por features e permissões;
- internacionalização.

### Próximo passo

13.1 — Gatilhos.
---

## 13.1 — Gatilhos

A etapa 13.1 iniciou a Fase 13 — Automações e Workflows com a implementação da fundação de gatilhos multi-tenant da plataforma.

### Fundação de gatilhos

Foi criada uma infraestrutura genérica formada por:

- `TriggerType`;
- `TriggerOccurrence`;
- `TriggerListener`;
- `TriggerDispatcher`.

A fundação permite representar ocorrências tipadas, transportar payload e subject, preservar o tenant de origem e registrar listeners pelo nome do gatilho.

### Gatilhos de domínio

Foram integrados:

- `lead.created`;
- `opportunity.stage_changed`;
- `proposal.sent`;
- `receivable.overdue`;
- `customer.inactive`.

### Ledger e idempotência

Foi criada a tabela `trigger_occurrences` para persistir a identidade das ocorrências processadas.

A identidade considera:

- tenant;
- nome do gatilho;
- tipo do subject;
- ID do subject;
- boundary.

A migration:

`2026_08_17_060000_create_trigger_occurrences_table`

foi aplicada no PostgreSQL de desenvolvimento no batch 22.

Antes da aplicação foi criado backup PostgreSQL de segurança.

### Recebíveis vencidos

`receivable.overdue` processa recebíveis pendentes vencidos e utiliza a própria data de vencimento como boundary.

Foi criado `ReceivableOverdueTenantRunner`, que:

- processa apenas tenants ativos;
- estabelece o `TenantContext`;
- respeita o timezone de cada tenant;
- limpa o contexto após execução;
- preserva idempotência.

Foi registrado o comando:

`triggers:dispatch-overdue-receivables`

com execução horária e proteção contra sobreposição.

### Cliente inativo

Foi implementado `customer.inactive`.

A última atividade observável utiliza `customer_history`, com fallback para `customer.created_at`.

O prazo permanece parametrizável para integração posterior com a camada de condições.

### Eventos customizáveis

Foi criado `CustomTriggerService`.

A primitive suporta:

- nome customizado obrigatório;
- normalização do nome;
- payload;
- subject opcional;
- isolamento por tenant;
- dispatch pelo nome customizado.

Nenhum endpoint público foi criado nesta etapa.

### Validação

A regressão final confirmou:

- suíte final de gatilhos: 42 testes e 141 assertions;
- regressão de clientes: 64 testes e 154 assertions;
- regressão dos domínios relacionados: 54 testes e 98 assertions;
- migration `060000` aplicada no batch 22;
- scheduler registrado;
- idempotência real validada;
- `git diff --check` limpo.

### Checkpoints Git

A fundação principal foi consolidada em:

`05512fe feat(automation): add multi-tenant trigger foundation`

O fechamento funcional foi consolidado em:

`a66fcdb feat(automation): add inactive and custom triggers`

### Fechamento

Foram concluídos os seis itens previstos em 13.1:

- Lead criado;
- Etapa alterada;
- Proposta enviada;
- Pagamento vencido;
- Cliente inativo;
- Eventos customizáveis.

### Próximo passo

13.2 — Condições.

---

## 13.2 — Condições

A etapa 13.2 implementou a fundação de avaliação de condições para Automações e Workflows.

### Fundação de condições

Foram criados:

- `ConditionOperator`;
- `AutomationCondition`;
- `ConditionEvaluator`;
- `TriggerConditionContext`.

A engine permite avaliar condições sem acoplamento a um domínio específico e reutiliza o contexto produzido pelos gatilhos.

### Campos e operadores

Foram validados:

- acesso a campos por dot notation;
- igualdade e diferença;
- comparações numéricas;
- `CONTAINS`;
- `IN`;
- verificações de `NULL`;
- composição AND por `matchesAll`;
- composição OR por `matchesAny`.

### Status

Condições de status foram validadas com valores escalares e enums normalizados no contexto dos gatilhos.

### Valor

Comparações de valor foram validadas utilizando representação em minor units quando aplicável, evitando dependência de ponto flutuante para valores monetários.

### Tempo

Condições temporais foram validadas sobre valores de data e tempo presentes no contexto da automação.

### Responsável

Foram validadas condições para responsável específico, ausência de responsável e composição com outros critérios.

### Segmentação

A segmentação foi implementada por composição da engine existente, sem criar uma segunda engine.

Podem participar dos segmentos:

- tags;
- status;
- origem;
- tipo de cliente;
- responsável;
- valor;
- tenant;
- demais campos disponíveis no contexto.

Tags podem ser avaliadas com `CONTAINS`, enquanto grupos de critérios podem utilizar `matchesAll` e `matchesAny`.

### Integração com gatilhos

`TriggerConditionContext` transforma uma ocorrência de gatilho em contexto avaliável e normaliza valores necessários para a condition engine.

Isso mantém a camada de condições integrada à fundação criada em 13.1 sem acoplar a engine diretamente aos modelos de domínio.

### Validação

A regressão final confirmou:

- suíte final de Condições: 81 testes e 108 assertions;
- regressão de Gatilhos: 37 testes e 131 assertions;
- regressão dos domínios relacionados: 130 testes e 222 assertions;
- nenhuma migration nova;
- `git diff --check` limpo.

### Checkpoint Git

A implementação funcional foi consolidada no commit:

`24e04a8 feat(automation): add condition evaluation foundation`

O checkpoint documental anterior da Fase 13 era:

`8d71dc6 docs: close triggers milestone`

### Fechamento

Foram concluídos os seis itens previstos em 13.2:

- Campos;
- Status;
- Valor;
- Tempo;
- Responsável;
- Segmentação.

### Próximo passo

13.3 — Ações.


---

## 13.3 — Ações

A etapa 13.3 implementou a fundação de execução de ações para Automações e Workflows.

### Fundação de ações

Foram criados:

- `AutomationActionType`;
- `AutomationAction`;
- `AutomationActionResult`;
- contrato `AutomationActionHandler`;
- `AutomationActionExecutor`.

O executor permite registrar handlers por tipo de ação, valida compatibilidade de tenant entre a ação e o contexto e produz resultados explícitos de sucesso ou falha.

### Criar tarefa

Foi implementado `CreateTaskActionHandler` reutilizando `ActivityService`.

### Enviar e-mail

Foi implementado `SendEmailActionHandler`, reutilizando `EmailMessageService` e `EmailQueueService` e preservando envio assíncrono.

### Enviar WhatsApp

Foi implementado `SendWhatsAppActionHandler`, reutilizando `WhatsAppMessageService` e `WhatsAppQueueService`.

### Alterar etapa

Foi implementado `ChangeStageActionHandler` reutilizando `OpportunityService::moveToStage`.

A atualização automática de pipeline e o trigger `opportunity.stage_changed` foram preservados.

### Atribuir responsável

Foi implementado `AssignResponsibleActionHandler` utilizando `OpportunityService::update`.

### Criar notificação

Foi criada `AutomationDatabaseNotification` e implementado `CreateNotificationActionHandler` sobre o canal database já existente.

### Webhook externo

Foi implementado `SendWebhookActionHandler` com POST JSON pelo Laravel HTTP client.

A ação suporta URL HTTP/HTTPS validada, headers opcionais, payload opcional e resultado explícito para falhas HTTP.

Políticas globais de retry, queue, idempotência, logs e auditoria permanecem para 13.4 — Execução.

### Validação

A consolidação funcional confirmou:

- suíte completa de Ações: 69 testes e 128 assertions;
- regressão dos domínios utilizados pelas ações: 78 testes e 143 assertions;
- regressão da foundation de Automações, Gatilhos e Condições: 58 testes e 115 assertions;
- nenhuma migration nova;
- `git diff --check` limpo.

### Checkpoint Git

A implementação funcional foi consolidada no commit:

`2584b21 feat(automation): add action execution foundation`

O checkpoint documental anterior da Fase 13 era:

`f07c5db docs: close conditions milestone`

### Fechamento

Foram concluídos os sete itens previstos em 13.3:

- Criar tarefa;
- Enviar e-mail;
- Enviar WhatsApp;
- Alterar etapa;
- Atribuir responsável;
- Criar notificação;
- Webhook externo.

### Próximo passo

13.4 — Execução.


---

## 13.4 — Execução

A etapa 13.4 implementou a fundação de execução assíncrona e resiliente para as ações de Automações e Workflows.

### Queue

Foi criado `ExecuteAutomationActionJob`, responsável por restaurar o `TenantContext`, reconstruir a ação e executar o handler registrado.

Foi criado `AutomationActionQueueService`, que despacha ações para a fila e gera uma `execution_key` quando ela não é informada.

`AutomationActionExecutor` passou a ser registrado centralmente no container com os sete handlers de ações.

### Retry

O job de execução foi configurado com três tentativas e backoff progressivo de 60 e 300 segundos.

Falhas explícitas de `AutomationActionResult` são convertidas em exceção para preservar o mecanismo de retry da queue.

### Idempotência

Foi criada a tabela `automation_action_executions` e o model `AutomationActionExecution`.

Foi criado `AutomationActionExecutionLedger`, que utiliza uma chave única por tenant e `execution_key`.

Execuções concluídas são ignoradas em redelivery, enquanto execuções incompletas podem ser retomadas por retry.

A conclusão da execução é registrada em `completed_at`.

A migration `2026_08_17_070000_create_automation_action_executions_table.php` foi aplicada no batch 23.

### Logs

O job registra logs estruturados para:

- `automation.action.started`;
- `automation.action.completed`;
- `automation.action.failed`.

Os logs incluem apenas `tenant_id`, `execution_key`, `action_type` e, em falhas definitivas, informações da exceção.

Parameters, contexto da automação e payloads sensíveis não são registrados.

### Auditoria

A execução passou a utilizar `AuditService` para registrar:

- `automation.action.completed`;
- `automation.action.failed`.

Execuções assíncronas preservam `user_id` nulo e utilizam o tenant explícito.

A descrição da auditoria contém apenas identificadores seguros da execução, sem persistir parâmetros ou payloads sensíveis.

Redelivery de uma execução já concluída não cria auditoria duplicada.

As auditorias de domínio produzidas pelos handlers existentes foram preservadas.

### Validação

A validação consolidada confirmou:

- suíte final de Execução: 36 testes e 71 assertions;
- regressão das sete Ações: 56 testes e 107 assertions;
- regressão da infraestrutura de Queue, Retry e Idempotência: 27 testes e 51 assertions;
- regressão de Auditoria: 30 testes e 44 assertions;
- regressão da foundation de Automações, Gatilhos e Condições: 46 testes e 78 assertions;
- sete handlers registrados centralmente;
- payload sensível ausente de logs e auditoria;
- migration `070000` aplicada no batch 23;
- `git diff --check` limpo.

### Checkpoint Git

A implementação funcional foi consolidada no commit:

`27e3394 feat(automation): add execution foundation`

O checkpoint documental anterior da Fase 13 era:

`146a3c5 docs: close actions milestone`

### Fechamento

Foram concluídos os seis itens previstos em 13.4:

- Queue;
- Retry;
- Idempotência;
- Logs;
- Auditoria;
- Testes.

Com o fechamento de 13.4, a Fase 13 — Automações e Workflows foi concluída.

### Próximo passo

14.1 — Planos.


---

## 14.1 — Planos

A etapa 14.1 implementou a fundação de planos da camada SaaS Billing e definiu a primeira política comercial oficial do produto.

### Fundação de planos

Foram criados:

- `Plan`;
- `PlanFeature`;
- `PlanPrice`;
- `PlanCatalog`;
- `PlanCapabilityProfile`;
- `PlanPriceResolver`.

A persistência utiliza as tabelas `plans`, `plan_features` e `plan_prices`.

A migration `2026_08_17_080000_create_plans_foundation.php` foi aplicada no batch 24.

### Catálogo oficial

Foram definidos quatro planos:

- Start;
- Pro;
- Business;
- Enterprise.

`PlanCatalogSeeder` persiste o catálogo de forma idempotente.

### Recursos por plano

Os recursos reutilizam o enum `Feature` já existente.

`PlanFeature` representa feature habilitada, estado e limite do plano.

`PlanCapabilityProfile` traduz essa configuração para o mesmo contrato utilizado pela infraestrutura de capabilities dos tenants.

Features desabilitadas também são persistidas, permitindo que futuras trocas de plano removam capabilities que não pertencem ao novo perfil.

### Limites por plano

A primeira política comercial definiu limite de usuários por plano:

- Start: 3 usuários;
- Pro: 10 usuários;
- Business: 30 usuários;
- Enterprise: ilimitado.

Enterprise representa ausência de limite por `limit_value = null`.

### Preços por moeda

`PlanPrice` persiste valores em minor units e reutiliza o contrato `Currency`/`Money` existente.

A política comercial inicial em BRL foi definida como:

- Start: R$ 99/mês (`9900` minor units);
- Pro: R$ 249/mês (`24900` minor units);
- Business: R$ 499/mês (`49900` minor units);
- Enterprise: sob consulta, sem preço persistido.

`PlanPriceResolver` resolve o preço configurado para a moeda solicitada e rejeita moedas sem preço comercial.

### Política comercial

Foi criado `PlanCommercialPolicy`, que centraliza features, limites e preços oficiais dos quatro planos.

Business e Enterprise habilitam todas as features atualmente disponíveis.

Start e Pro utilizam subconjuntos progressivos de recursos.

### Validação

A validação da fundação confirmou:

- suíte final da foundation de Planos: 21 testes e 34 assertions;
- regressão de capabilities: 11 testes e 16 assertions;
- regressão monetária: 17 testes e 37 assertions;
- política comercial: 11 testes e 33 assertions;
- regressão final de Planos e infraestrutura relacionada: 36 testes e 61 assertions;
- banco de desenvolvimento com 4 planos;
- 72 registros em `plan_features`;
- 3 registros em `plan_prices`;
- migration `080000` aplicada no batch 24;
- `git diff --check` limpo.

### Checkpoints Git

A fundação técnica foi consolidada no commit:

`0184057 feat(billing): add plan foundation`

A política comercial foi consolidada no commit:

`b6f723b feat(billing): define plan commercial policy`

O checkpoint documental anterior era:

`fee6a02 docs: close execution milestone`

### Fechamento

Foram concluídos os sete itens previstos em 14.1:

- Plano Start;
- Plano Pro;
- Plano Business;
- Plano Enterprise;
- Recursos por plano;
- Limites por plano;
- Preços por moeda.

### Próximo passo

14.2 — Trial.


---

## 14.2 — Trial

A etapa 14.2 implementou o ciclo de vida de trial da camada SaaS Billing.

### Período de teste

Foi criado `TrialPeriod`, responsável pela semântica temporal do período de avaliação.

O período padrão foi definido em 14 dias, com suporte a duração customizável.

O contrato diferencia momentos anteriores ao início, trial ativo e expiração.

### Início

O tenant passou a persistir:

- `trial_started_at`;
- `trial_ends_at`.

Foi criado `TrialService`, que inicia o trial de forma explícita e impede inicialização duplicada.

As datas utilizam casts `immutable_datetime`.

### Expiração

`TrialService` expõe os estados:

- `not_started`;
- `active`;
- `expired`.

A expiração ocorre exatamente em `trial_ends_at`.

Foi criado `TrialExpirationTenantRunner`, que processa tenants ativos com trial vencido.

O runner restaura `TenantContext` por tenant e sempre limpa o contexto em `finally`.

Foi registrado o comando:

`trials:block-expired`

O comando é executado de forma horária e utiliza `withoutOverlapping()`.

### Bloqueio controlado

Trials expirados podem mover tenants com status `active` para `blocked`.

O bloqueio é idempotente.

Tenants sem trial, trials ainda ativos e tenants com status diferente de `active` são preservados.

O bloqueio não remove capabilities, dados do tenant ou datas do trial.

### Conversão

O item Conversão já se encontrava concluído antes da implementação desta etapa e foi preservado sem reimplementação.

### Persistência

A migration `2026_08_17_090000_add_trial_fields_to_tenants_table.php` foi aplicada no batch 25.

### Validação

A validação consolidada confirmou:

- suíte final de Trial: 31 testes e 55 assertions;
- regressão de Tenant: 29 testes e 86 assertions;
- comando `trials:block-expired` registrado;
- scheduler horário registrado;
- `withoutOverlapping()` configurado;
- runner idempotente;
- limpeza do `TenantContext` validada;
- migration `090000` aplicada no batch 25;
- `git diff --check` limpo.

### Checkpoint Git

A implementação funcional foi consolidada no commit:

`89ae7dc feat(billing): add trial lifecycle foundation`

O checkpoint documental anterior era:

`9c5d8dd docs: close plans milestone`

### Fechamento

Foram concluídos os cinco itens previstos em 14.2:

- Período de teste;
- Início;
- Expiração;
- Conversão;
- Bloqueio controlado.

### Próximo passo

14.3 — Assinaturas.


---

## 14.3 — Assinaturas

A etapa 14.3 implementou o ciclo de vida de assinaturas da camada SaaS Billing.

### Fundação

Foram criados:

- `SubscriptionStatus`;
- `SubscriptionPeriod`;
- `Subscription`;
- `SubscriptionService`.

A persistência utiliza a tabela `subscriptions` e vincula cada assinatura a um tenant e a um plano.

A migration `2026_08_18_100000_create_subscriptions_table.php` foi aplicada no batch 26.

### Estados

Foram definidos os estados:

- `active`;
- `cancelled`;
- `suspended`;
- `expired`.

Assinaturas podem ser criadas ativas, canceladas, suspensas, retomadas e expiradas ao final do período vigente.

Estados terminais impedem transições incompatíveis.

### Período

`SubscriptionPeriod` utiliza início inclusivo e fim exclusivo.

A assinatura persiste `current_period_start` e `current_period_end` com casts imutáveis.

### Upgrade e downgrade

Trocas de plano são permitidas somente para assinaturas ativas.

Upgrade e downgrade reutilizam o mesmo contrato técnico de troca de plano.

Troca para o mesmo plano é idempotente.

Ao trocar de plano, `PlanCapabilityProfile::definitions()` produz o profile do novo plano e `TenantCapabilityManager::applyProfile()` reaplica features e limites ao tenant.

### Renovação

Assinaturas ativas podem ser renovadas por um ou mais meses.

O novo período começa exatamente no `current_period_end` anterior.

A renovação preserva plano e status ativo.

Assinaturas canceladas, suspensas ou expiradas não podem ser renovadas.

### Validação

A validação consolidada confirmou:

- suíte final de Assinaturas: 39 testes e 49 assertions;
- regressão de capabilities: 13 testes e 20 assertions;
- regressão de Planos: 18 testes e 35 assertions;
- regressão de Trial: 22 testes e 35 assertions;
- sintaxe PHP validada nos 10 arquivos funcionais;
- migration `100000` aplicada no batch 26;
- `git diff --check` limpo.

### Checkpoint Git

A implementação funcional foi consolidada no commit:

`f3ec9af feat(billing): add subscription lifecycle foundation`

O checkpoint documental anterior era:

`ee7e774 docs: close trial milestone`

### Fechamento

Foram concluídos os oito itens previstos em 14.3:

- Ativa;
- Cancelada;
- Suspensa;
- Vencida;
- Upgrade;
- Downgrade;
- Renovação;
- Testes.

### Próximo passo

14.4 — Pagamentos.

---

## 14.4 — Pagamentos

A etapa 14.4 implementou a fundação de processamento de pagamentos da camada SaaS Billing.

### Provedor de pagamento

Foi criado o contrato `PaymentProvider`, desacoplando a aplicação de provedores externos específicos.

`PaymentProviderResult` representa resultados de operações do provider, incluindo referência externa, URL de checkout e falhas.

`PaymentProviderRegistry` registra e resolve providers por código normalizado.

O registry foi registrado como singleton no container para preservar os providers registrados entre os serviços que participam do fluxo.

Nenhum provider comercial real foi acoplado nesta etapa.

### Checkout

Foi criado `PaymentCheckoutService`.

O serviço resolve o provider configurado, inicia o checkout e sincroniza o resultado com a `Charge`.

Checkout bem-sucedido move a cobrança para enviada.

Falha retornada pelo provider move a cobrança para falha com o motivo correspondente.

### Webhooks

Foram criados os contratos:

- `PaymentWebhookVerifier`;
- `PaymentWebhookNormalizer`.

A verificação de autenticidade do webhook ocorre antes da normalização do payload.

`PaymentWebhookNormalizer` transforma o payload externo em `PaymentProviderEvent`.

Foi criado `PaymentWebhookHttpService`, responsável por coordenar verificação, normalização e processamento do evento.

Também foi criado `PaymentWebhookController` e registrada a rota pública:

`POST webhooks/payment/{tenantSlug}/{provider}`

O tenant ativo é resolvido explicitamente pela rota de webhook.

Provider desconhecido e tenant inativo são rejeitados.

Assinaturas inválidas retornam resposta não autorizada antes do processamento do payload.

### Pagamento aprovado

Eventos `payment.approved` são processados por `PaymentEventProcessor`.

O evento localiza a cobrança pela referência externa e sincroniza `Charge` e `Receivable` como pagos.

### Pagamento falho

Eventos `payment.failed` são processados pelo mesmo pipeline.

A cobrança correspondente é marcada como falha preservando a semântica existente de `ChargeService`.

Referências externas desconhecidas e tipos de evento não suportados são rejeitados.

### Reembolso

Foi criado `PaymentRefundService`.

Somente cobranças pagas podem ser enviadas para reembolso.

O serviço reutiliza `PaymentProvider::refund()`.

Falhas retornadas pelo provider são rejeitadas.

O reembolso é auditado.

Nesta fundação, o reembolso não altera automaticamente o status do `Receivable`.

### Idempotência

Foi criado `PaymentEventReceipt`.

Eventos processados são identificados pela combinação:

- provider;
- event ID.

A restrição permite que o mesmo event ID exista em providers diferentes, mas impede processamento duplicado para o mesmo provider.

A migration `2026_08_18_110000_create_payment_event_receipts_table.php` foi aplicada no batch 27.

O processamento idempotente é reutilizado também pelo fluxo HTTP de webhooks.

### Auditoria

A infraestrutura de auditoria existente foi preservada.

Operações de cobrança continuam auditadas.

Reembolsos também geram auditoria.

As regressões confirmaram isolamento entre tenants.

### Validação

A validação consolidada confirmou:

- suíte final de Pagamentos: 33 testes e 60 assertions;
- regressão de Charge/Receivable: 26 testes e 44 assertions;
- `PaymentProviderRegistry` registrado como singleton;
- checkout validado;
- `payment.approved` validado;
- `payment.failed` validado;
- refund validado;
- idempotência por provider/event ID validada;
- webhook HTTP validado;
- isolamento entre tenants preservado;
- rota `webhooks.payment.handle` registrada;
- migration `110000` aplicada no batch 27;
- sintaxe PHP validada nos arquivos funcionais;
- `git diff --check` limpo.

### Checkpoint Git

A implementação funcional foi consolidada no commit:

`2c35292 feat(billing): add payment processing foundation`

O checkpoint documental anterior era:

`c574107 docs: close subscriptions milestone`

### Fechamento

Foram concluídos os nove itens previstos em 14.4:

- Provedor de pagamento;
- Checkout;
- Webhooks;
- Pagamento aprovado;
- Pagamento falho;
- Reembolso;
- Idempotência;
- Auditoria;
- Testes.

### Próximo passo

14.5 — Uso e limites.

## 14.5 — Uso e limites

Status: concluído.

A etapa 14.5 consolidou governança e enforcement de uso por tenant e plano.

Entregas validadas:

- Usuários:
  - limite comercial por plano;
  - bloqueio de criação ao atingir o limite;
  - suporte a limite zero e ilimitado;
  - isolamento entre tenants.

- Storage:
  - contabilização de uso por tenant;
  - enforcement em imports;
  - enforcement em branding;
  - projeção de consumo antes da gravação;
  - suporte a limite zero e ilimitado.

- Mensagens:
  - quota compartilhada entre e-mail e WhatsApp outbound;
  - mensagens inbound não consomem quota;
  - isolamento entre tenants;
  - compatibilidade com tenants legados.

- IA:
  - capability e permission governance;
  - configuração de provider;
  - execução governada;
  - limite de tokens;
  - contabilização do uso real;
  - endpoint de rewrite;
  - integrações principais de UI em WhatsApp, e-mail, templates de e-mail,
    propostas, oportunidades, atividades, leads e clientes.

Integrações adicionais de rewrite foram deliberadamente deixadas como opcionais:
notas de contatos de clientes, descrição de pipelines e templates de WhatsApp.

Validação final:

- users=validated
- storage=validated
- messages=validated
- ai=validated
- working-tree=clean

Checkpoint funcional anterior ao fechamento documental: ca131ae.

### Fechamento funcional adicional da 14.5

Após a validação dos quatro pilares de uso e limites, a etapa também concluiu os itens comerciais e de UX restantes:

- Features premium:
  - matriz de capabilities por plano validada;
  - enforcement por middleware `feature:*`;
  - capabilities isoladas por tenant;
  - nenhuma nova fundação foi necessária.

- Bloqueio elegante:
  - criada `FeatureUnavailableException`;
  - feature indisponível passou a ter UX 403 específica;
  - 403 comum por falta de permissão foi preservado;
  - mensagens traduzidas em cinco locales.

- Upgrade sugerido:
  - contrato existente de `UsageBlockedException::upgradeSuggested` reutilizado;
  - limite excedido passou a responder HTTP 429;
  - página HTML específica para limite atingido;
  - resposta JSON inclui `upgrade_suggested`;
  - mensagem recomenda plano superior;
  - nenhum link de billing foi criado porque ainda não existe rota de upgrade/billing no produto.

Validação final:

- Features premium=implemented
- Bloqueio elegante=implemented
- Upgrade sugerido=implemented
- HTTP 429 handling=ok
- HTML upgrade suggestion=ok
- JSON upgrade_suggested=ok
- usage regression=ok
- AI HTTP regression=ok

Com isso, todos os itens funcionais previstos para a etapa 14.5 foram concluídos.
## 15.1 — Site comercial

Status: concluído.

A etapa 15.1 criou a presença pública inicial da plataforma e separou
claramente o site comercial da aplicação autenticada.

Entregas validadas:

- Home pública:
  - rota pública em `/`;
  - dashboard autenticado preservado em `/dashboard`;
  - isolamento da Home em relação ao middleware de tenant.

- Recursos:
  - apresentação comercial das capacidades centrais da plataforma;
  - CRM e clientes;
  - leads e oportunidades;
  - propostas e vendas;
  - atividades e acompanhamento;
  - e-mail e WhatsApp;
  - automação e IA.

- Preços:
  - apresentação dos planos Start, Pro, Business e Enterprise;
  - preços mensais alinhados ao catálogo comercial existente;
  - nenhuma condição anual inventada.

- FAQ:
  - respostas para dúvidas comerciais recorrentes;
  - escopo mantido na Home pública.

- Contato:
  - formulário público com nome, e-mail, empresa e mensagem;
  - contrato visual e HTTP POST preparado;
  - persistência, criação de Lead e envio de e-mail não foram acoplados
    sem uma definição explícita de destino comercial/tenant.

- SEO básico:
  - title;
  - meta description;
  - canonical;
  - Open Graph;
  - Twitter Card;
  - `robots.txt` dinâmico;
  - `sitemap.xml` dinâmico;
  - URLs absolutas sensíveis ao ambiente;
  - `lang="pt-BR"`, charset e viewport validados.

Validação funcional:

- `PublicMarketingTest`: 9 testes verdes;
- 62 assertions;
- regressão do dashboard autenticado preservada;
- working tree limpa após os commits funcionais.

Checkpoints principais:

- `71ae2b3` — Home pública;
- `62a02d8` — Recursos;
- `641402b` — Preços;
- `8e6ef3e` — FAQ;
- `0bc920a` — Contato;
- `272a6e8` — SEO básico.

Próximo passo:

15.2 — Cadastro self-service.

## 15.2 — Cadastro self-service

Status: concluído.

A etapa 15.2 entregou o fluxo público de cadastro self-service
da plataforma.

Entregas validadas:

- criação pública de conta em `/register`;
- criação atômica de tenant e primeiro usuário administrador;
- slug derivado do nome da empresa;
- sincronização das permissões iniciais;
- seleção e persistência de país;
- seleção e persistência de idioma;
- seleção de plano self-service;
- planos Start, Pro e Business disponíveis no cadastro;
- Enterprise excluído deliberadamente do self-service;
- assinatura inicial criada com o plano selecionado;
- capabilities iniciais aplicadas ao tenant;
- trial iniciado automaticamente com duração padrão de 14 dias;
- rollback atômico validado para falhas internas;
- validações para slug existente, país, idioma e plano inválidos;
- `User` integrado ao contrato `MustVerifyEmail`;
- envio de notificação de verificação após cadastro concluído;
- verificação por link assinado;
- reenvio de link de verificação;
- rejeição de link sem assinatura;
- rejeição de hash inválido;
- rejeição de tentativa de verificação cross-user;
- `email_verified_at` atualizado após confirmação;
- dashboard protegido para exigir e-mail verificado.

Decisões deliberadas:

- não há autenticação automática imediatamente após o cadastro;
- o destino pós-cadastro é o login do tenant;
- o destino após confirmação de e-mail também é o login do tenant;
- `verified` foi aplicado inicialmente ao dashboard;
- logout, profile e demais rotas autenticadas não foram
  indiscriminadamente bloqueadas;
- a integração visual da Home comercial com o cadastro
  self-service ainda será tratada separadamente.

Checkpoints relevantes:

- `dfc51cf` — cadastro atômico;
- `20ad221` — bootstrap inicial das capabilities;
- `0bc2417` — robustez das validações;
- `4be460b` — rollback atômico;
- `dbec01f` — fluxo de verificação de e-mail;
- `ab74594` — dashboard protegido por e-mail verificado.

Com isso, todos os itens funcionais previstos para a etapa 15.2
foram concluídos.

Próximo refinamento:
integrar a Home comercial ao fluxo `/register`.
## Refinamento pós-15.2 — integração Home + self-service

Status: concluído.

Após o fechamento funcional do cadastro self-service da etapa 15.2,
foi realizado um refinamento da jornada comercial entre a Home pública
e o fluxo `/register`.

Objetivo:

tornar o caminho entre descoberta do produto, escolha de plano e
cadastro self-service mais claro e consistente, sem alterar as regras
de domínio já entregues na 15.2.

Entregas validadas:

- CTA principal "Começar trial" adicionado ao hero da Home;
- "Conhecer recursos" preservado como ação secundária;
- "Entrar" preservado no hero;
- plano Start direcionando para `/register`;
- plano Pro direcionando para `/register`;
- plano Business direcionando para `/register`;
- Pro mantido visualmente destacado;
- Enterprise mantido fora do fluxo self-service;
- Enterprise preservado como jornada comercial;
- seção Contato mantida exclusivamente comercial;
- CTAs redundantes de cadastro removidos da seção Contato;
- CTAs Start, Pro e Business padronizados com o componente visual `button`;
- formulário `/register` preservando empresa, nome, e-mail, senha,
  país, idioma e plano;
- resumo da contratação adicionado antes da criação da conta;
- plano escolhido exibido no resumo;
- período de avaliação exibido no resumo;
- duração do trial continuando derivada de `$trialDays`;
- botão final "Criar conta" preservado;
- nenhuma regra de backend alterada nesse refinamento;
- `PublicMarketingTest` validado;
- `SelfServiceRegistrationTest` validado.

Decisões de experiência:

- Hero é o principal ponto de entrada para trial;
- Start, Pro e Business convertem diretamente para self-service;
- Enterprise permanece comercial;
- Contato permanece comercial;
- `/register` apresenta contexto de plano e trial antes do submit.

Commits funcionais relacionados:

- `e3ff365` — integração inicial da Home com `/register`;
- `6487d0e` — CTAs self-service no hero e nos planos;
- `c6b022e` — seção Contato mantida comercial-only;
- `134eba0` — refinamento visual dos CTAs e resumo de contratação.

Checkpoint funcional atual:

`134eba0`

A etapa seguinte do ROADMAP permanece:

15.3 — Onboarding.
---

## 15.3 — Onboarding concluído

A etapa 15.3 consolidou o onboarding inicial do tenant sobre
domínios já existentes da plataforma, evitando duplicação de
responsabilidades e preservando isolamento, permissões e features.

### Dados da empresa

- nome da empresa editável;
- slug permanece estável;
- país e idioma são apresentados a partir do contexto atual do tenant.

### Segmento

- segmento persistido no tenant;
- taxonomia controlada e orientada por configuração;
- opções iniciais: Serviços, Comércio, Indústria, Tecnologia e Outro;
- conceito mantido separado da segmentação operacional do CRM.

### Equipe

- membros atuais do tenant são apresentados no onboarding;
- criação de pessoas reutiliza o fluxo existente de usuários;
- limites de usuários e permissões existentes permanecem responsáveis
  pela governança.

### Pipeline inicial

- pipeline padrão ativo e suas etapas são apresentados;
- configuração reutiliza o gerenciamento existente de pipelines;
- nenhum domínio paralelo de pipeline foi criado.

### Importação

- onboarding direciona para o fluxo existente de importação;
- upload, preview, dispatch e execução continuam sob o domínio de
  importações existente;
- importação permanece opcional para conclusão do onboarding.

### Checklist

- progresso é derivado do estado real dos domínios;
- Dados da empresa, Segmento, Equipe e Pipeline inicial possuem estado
  objetivo;
- Importação é apresentada como opcional;
- nenhum estado persistente específico de checklist foi criado.

### Primeiro valor percebido

- onboarding direciona o usuário para cadastrar o primeiro lead;
- o CTA reutiliza `leads.create`;
- criação e governança de leads permanecem no domínio existente;
- nenhum estado persistente específico de primeiro valor foi criado.

### Validação

O fechamento funcional da etapa confirmou:

- CompanyOnboardingTest verde;
- regressão de usuários verde;
- regressão de pipelines verde;
- regressão de importação verde;
- regressão de leads verde;
- DashboardHttpTest verde;
- ausência de domínios duplicados;
- working tree limpa antes da documentação.

Checkpoint funcional anterior ao fechamento documental: `2d904ed`.
