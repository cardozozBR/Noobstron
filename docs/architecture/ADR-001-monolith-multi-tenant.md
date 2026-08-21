# ADR-001 — Arquitetura Monolítica Modular Multi-Tenant

- **Status:** Aceita
- **Data:** 2026-08-10
- **Decisão:** Monólito modular com arquitetura multi-tenant

## 1. Contexto

A plataforma será um produto SaaS destinado a diferentes empresas,
profissionais e segmentos de negócio.

O sistema deverá permitir que múltiplas empresas utilizem a mesma
infraestrutura da aplicação mantendo seus dados logicamente isolados.

A arquitetura também deverá permitir evolução gradual da plataforma sem
introduzir complexidade operacional desnecessária.

## 2. Decisão

Adotaremos inicialmente uma arquitetura de:

- monólito modular;
- aplicação Laravel;
- banco de dados PostgreSQL;
- Redis para cache e filas;
- Docker para padronização do ambiente;
- arquitetura multi-tenant;
- APIs internas bem definidas entre módulos.

A aplicação será organizada por responsabilidades e domínios, evitando
acoplamento desnecessário entre funcionalidades.

## 3. Modelo Multi-Tenant

A plataforma terá uma entidade principal representando o tenant/empresa.

De forma conceitual:

```text
Platform
   │
   ├── Tenant A
   │     ├── Users
   │     ├── Products
   │     ├── Customers
   │     └── Orders
   │
   ├── Tenant B
   │     ├── Users
   │     ├── Products
   │     ├── Customers
   │     └── Orders
   │
   └── Tenant C
         ├── Users
         ├── Products
         ├── Customers
         └── Orders