# Release Candidate — validação antes do lançamento

Este pacote é um **Release Candidate**, não uma autorização automática de produção.

## Corrigido / implementado nesta revisão

- ciclo HTTP de autenticação multi-tenant e contexto necessário para sessão em banco;
- redirect pós-login para o dashboard do tenant;
- verificação de e-mail preservando o contexto do tenant;
- recuperação de senha por tenant;
- formulário comercial público com persistência e proteção de taxa;
- consulta de contatos comerciais pelo Admin Master;
- Termos de Uso e Política de Privacidade públicos;
- navegação pública e sitemap ampliados;
- visão global e saúde operacional no Admin Master;
- headers básicos de segurança no Nginx de produção;
- documentação e exemplos de configuração para deploy.

## Validações realizadas no ambiente de revisão

- `php -l` no código PHP revisado: sem erros de sintaxe;
- bootstrap do Laravel e `route:list`: carregam;
- 165 rotas carregadas, sem nomes de rota duplicados;
- novos testes de autenticação, password reset, contato e operações passam em análise de sintaxe.

## Validação obrigatória no Docker antes de produção

Execute `scripts/validate-release-candidate.ps1` na raiz do projeto. O script não faz deploy nem commit. Ele sobe o ambiente, limpa caches, executa migrations no ambiente local e roda a suíte de testes pelo wrapper seguro do projeto.

Além dos testes automatizados, valide manualmente em janela anônima:

1. Home pública e troca de idioma.
2. Cadastro self-service.
3. Login de Admin Master.
4. Login de admin de tenant e usuário comum.
5. Logout e novo login.
6. Esqueci minha senha e reset de senha.
7. Verificação de e-mail.
8. Formulário de contato.
9. CRUDs principais de CRM.
10. Isolamento: usuário do tenant A não pode acessar dados do tenant B.

## Bloqueadores externos de lançamento

- configurar domínio real, DNS wildcard dos tenants e HTTPS;
- configurar SMTP real e testar entrega, SPF/DKIM/DMARC;
- configurar destinatário comercial;
- configurar worker de filas e scheduler permanentes;
- configurar backup automatizado e testar restauração;
- configurar monitoramento/alertas;
- revisar textos jurídicos com profissional adequado ao mercado de lançamento;
- configurar e validar um provider real de pagamentos antes de cobrar clientes;
- configurar credenciais reais de integrações utilizadas (WhatsApp/IA/pagamentos etc.).

Nunca publique arquivos `.env` ou outros segredos em ZIPs, repositórios ou tickets.


## RC2 focus

- TenantContext is resolved before the session/auth middleware lifecycle.
- TenantContext remains a Laravel scoped binding; no manual post-response clear.
- Tenant host `/` redirects to `/login` or `/dashboard`; central host keeps marketing home.
- Full test runner uses 512 MB memory so Dompdf can execute late in the full suite.
- Feature catalog expectation includes the existing AI capability.


## RC3 — produção / lançamento

A suíte funcional do RC anterior foi validada com **1859 testes aprovados (4196 assertions), zero falhas**.

Nesta revisão de produção também foram corrigidos:

- scheduler incluído explicitamente no build e no `up` do deploy;
- validação do processo scheduler no pós-deploy;
- backup deixou de assumir `postgres` / `nossa_plataforma` e passa a resolver `POSTGRES_USER` e `POSTGRES_DB` do ambiente real;
- novo `validate-production-readiness.cmd` para preflight de configuração sem imprimir secrets.

A suíte automatizada verde é necessária, mas não substitui os requisitos externos de go-live listados neste documento.


## RC4 — homologação de UX

Correções provenientes da homologação manual local:

- gateway de workspace preserva a porta da requisição (`:8000` local) em vez de depender de `APP_URL`;
- gateway explica o significado de workspace;
- cadastro exibe resumo de validação, erros por campo e preserva empresa, nome e e-mail;
- tela de verificação de e-mail recebeu layout consistente, mostra o e-mail da conta, feedback de reenvio e opção de sair;
- testes de regressão adicionados para porta do workspace e feedback do cadastro.
