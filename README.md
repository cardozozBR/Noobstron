# Nossa Plataforma

> Plataforma SaaS multi-tenant para criação e gerenciamento de soluções
> digitais para empresas e profissionais.

## 1. Visão

A Nossa Plataforma será um sistema SaaS que permitirá que empresas e
profissionais criem e gerenciem seu próprio ambiente digital sem precisar
desenvolver um sistema do zero.

A plataforma será modular e poderá atender diferentes tipos de negócios,
como comércio, delivery, serviços, agendamentos e divulgação.

## 2. Objetivos

- Criar uma plataforma SaaS escalável.
- Permitir múltiplas empresas utilizando o mesmo sistema.
- Isolar rigorosamente os dados entre empresas.
- Oferecer uma experiência simples para o cliente.
- Permitir diferentes modelos de negócio.
- Oferecer planos e assinaturas.
- Construir uma base segura e preparada para crescimento.

## 3. Princípios do projeto

### Segurança primeiro

Segurança será considerada desde a arquitetura até a produção.

### Multi-tenancy

Cada empresa terá seu próprio ambiente lógico e seus dados não poderão
ser acessados por outras empresas.

### Qualidade

Toda funcionalidade importante deverá possuir testes adequados.

### Simplicidade

Não criaremos complexidade desnecessária antes de existir necessidade real.

### Evolução

A arquitetura deverá permitir a inclusão de novos módulos sem
comprometer os módulos existentes.

## 4. Público-alvo

A plataforma poderá atender:

- lojas;
- restaurantes;
- delivery;
- prestadores de serviços;
- profissionais autônomos;
- empresas que precisam de presença digital;
- outros segmentos futuramente.

## 5. Modelo de negócio

O modelo principal será baseado em assinaturas.

Possíveis modelos:

- plano mensal;
- plano anual;
- período de teste;
- recursos adicionais;
- domínio personalizado;
- serviços complementares.

Os preços e recursos definitivos serão definidos durante a fase de
planejamento comercial.

## 6. Arquitetura inicial

A arquitetura inicial será baseada em um monólito modular.

Tecnologias planejadas:

- Laravel;
- PHP;
- PostgreSQL;
- Redis;
- Docker;
- Docker Compose;
- Git;
- GitHub;
- Tailwind CSS;
- Livewire.

A escolha definitiva das versões será realizada durante a configuração
do ambiente.

## 7. Estrutura prevista

```text
nossa-plataforma/
│
├── app/
├── database/
├── resources/
├── routes/
├── tests/
├── docker/
├── docs/
├── storage/
├── .gitignore
└── README.md