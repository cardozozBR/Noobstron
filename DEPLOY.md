# Deploy de Produção

Este documento descreve o procedimento de deploy da aplicação em produção usando Docker Compose.

## Pré-requisitos

O servidor deve possuir:

- Git;
- Docker;
- Docker Compose;
- repositório da aplicação configurado;
- arquivo `backend/.env.production` configurado e fora do Git.

## Deploy

O deploy automatizado é executado com:

    .\deploy-production.cmd

O fluxo realiza:

1. validação do `compose.production.yaml`;
2. validação do repositório Git;
3. atualização do código;
4. backup pré-deploy;
5. build das imagens de produção;
6. inicialização e validação do PostgreSQL;
7. execução das migrations com `--force`;
8. inicialização dos serviços;
9. validação dos healthchecks;
10. validação do endpoint `/up`;
11. validação das migrations;
12. validação da fila.

O deploy somente é considerado concluído quando o script retorna `DEPLOY_OK`.

## Opções

Para executar sem atualizar o repositório:

    .\deploy-production.cmd -SkipGitPull

## Backup

O processo protege:

- PostgreSQL;
- volume `app_storage`.

Os backups são armazenados em `backups/` e não são versionados.

Validação de restore:

    .\validate-production-restore.cmd ".\backups\<timestamp>"

## Serviços

A stack de produção possui:

- backend;
- nginx;
- postgres;
- worker;
- scheduler.

Backend, Nginx, PostgreSQL e worker devem terminar em estado `healthy`.
O scheduler deve permanecer em estado `running` executando `php artisan schedule:work`.

## Validação pós-deploy

Confirmar:

- `DEPLOY_OK`;
- containers `healthy`;
- `/up` retornando HTTP 200;
- migrations aplicadas;
- fila operacional;
- ausência de failed jobs.

Consultar serviços:

    docker compose -f compose.production.yaml ps

## Segurança

Nunca versionar:

- `backend/.env.production`;
- backups reais;
- credenciais;
- secrets;
- dumps de produção.

## Requisitos obrigatórios antes do go-live

Além do deploy técnico, o ambiente público deve ter:

- HTTPS/TLS válido no proxy ou load balancer que publica o Nginx;
- DNS do domínio principal e wildcard `*.seu-dominio` apontando para a aplicação;
- `APP_URL` com o domínio público correto;
- SMTP transacional real configurado para verificação e recuperação de senha;
- `MARKETING_CONTACT_EMAIL` configurado;
- `APP_DEBUG=false` e `APP_ENV=production`;
- backups automáticos e restore testado;
- worker e scheduler ativos;
- monitoramento externo do endpoint `/up`;
- provedor de pagamento real registrado antes de aceitar cobrança automática;
- revisão jurídica dos Termos de Uso e Política de Privacidade.

O Nginx incluído neste repositório publica HTTP internamente. A terminação TLS deve ser feita por um proxy/load balancer externo ou por uma configuração HTTPS equivalente antes de exposição à internet.
