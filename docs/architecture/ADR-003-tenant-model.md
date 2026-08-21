# ADR-003 - Modelo de Tenant

- Status: Aceita
- Data: 2026-08-10
- Decisao: Tenant representa uma organizacao dentro da plataforma.

## Contexto

A Nossa Plataforma sera um SaaS multi-tenant.

Uma mesma instalacao da aplicacao atendera diversas empresas.
Os dados de cada empresa deverao permanecer logicamente isolados.

Precisamos definir a entidade que representa uma empresa e como
usuarios serao associados a ela.

## Decisao

Cada empresa sera representada por um registro na tabela `tenants`.

Estrutura inicial:

tenants
- id
- name
- slug
- status
- created_at
- updated_at

## Usuarios

Um usuario podera pertencer a um ou mais tenants.

O relacionamento sera realizado pela tabela `tenant_users`.

tenant_users
- tenant_id
- user_id
- role
- created_at
- updated_at

A combinacao tenant_id + user_id sera unica.

## Papeis

Os papeis iniciais serao:

- owner
- manager
- staff

O papel sera definido dentro de cada tenant.

Um usuario podera ser owner em um tenant e staff em outro.

## Proprietario

Nao sera utilizado owner_id diretamente em tenants.

O proprietario sera identificado pelo papel owner em tenant_users.

## Seguranca

O tenant atual devera ser determinado e validado pelo servidor.

O cliente nao podera informar livremente outro tenant_id para acessar
dados de outra empresa.

Toda operacao devera validar:

- usuario
- tenant
- associacao
- permissao
- recurso
- operacao

Esta decisao complementa o ADR-002 - Isolamento de Tenants.

## Integridade

Relacionamentos com tenants deverao utilizar foreign keys.

A associacao tenant_users devera impedir duplicidades.

Dados pertencentes a um tenant deverao possuir referencia consistente
ao tenant correspondente.

## Exclusao

A exclusao fisica de tenants nao sera uma operacao comum inicialmente.

A suspensao ou desativacao sera preferivel a remocao permanente.

## Auditoria

Operacoes administrativas importantes deverao ser auditaveis.

Exemplos:

- criacao
- suspensao
- reativacao
- alteracao de configuracoes
- alteracao de usuarios
- alteracao de permissoes

Senhas, tokens e outros segredos nunca deverao ser registrados em logs.

## Regra Fundamental

Um tenant representa uma organizacao independente.

Nenhuma operacao podera ignorar o contexto do tenant.

Dados de um tenant nunca poderao ser acessados por outro tenant.
