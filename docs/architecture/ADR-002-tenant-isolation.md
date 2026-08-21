# ADR-002 — Isolamento de Tenants

- **Status:** Aceita
- **Data:** 2026-08-10
- **Decisão:** Banco compartilhado com isolamento lógico por tenant

## 1. Contexto

A Nossa Plataforma será um sistema SaaS utilizado por múltiplas empresas.

Cada empresa deverá possuir seus próprios usuários, produtos, clientes,
pedidos, configurações e demais dados operacionais.

Um requisito crítico do sistema é impedir que dados pertencentes a um
tenant sejam acessados por usuários de outro tenant.

O isolamento deverá ser tratado como requisito de segurança e não apenas
como uma regra de interface.

## 2. Decisão

Inicialmente utilizaremos:

- PostgreSQL;
- banco compartilhado;
- tabelas compartilhadas;
- identificação explícita de tenant;
- coluna `tenant_id` nas entidades pertencentes a um tenant;
- contexto de tenant durante cada requisição;
- autorização baseada no tenant;
- testes automatizados de isolamento.

O modelo poderá ser revisado futuramente caso requisitos de escala,
compliance ou operação justifiquem outra estratégia.

## 3. Conceito de Tenant

Um tenant representa uma empresa ou organização que utiliza a plataforma.

Exemplo:

```text
Tenant A
├── Usuários
├── Produtos
├── Clientes
├── Pedidos
└── Configurações

Tenant B
├── Usuários
├── Produtos
├── Clientes
├── Pedidos
└── Configurações