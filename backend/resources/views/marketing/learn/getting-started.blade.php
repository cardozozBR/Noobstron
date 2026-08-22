@extends('layouts.marketing')

@section(
    'title',
    'Primeiros passos com o Noobstron'
)

@section(
    'meta_description',
    'Aprenda passo a passo como configurar o Noobstron, organizar sua equipe, cadastrar clientes, trabalhar com leads, oportunidades, propostas e vendas.'
)

@section('content')

<style>
.guide-page {
    padding-bottom: 80px;
}

.guide-hero {
    padding: 64px 0 48px;
}

.guide-hero-inner {
    max-width: 860px;
}

.guide-back {
    display: inline-flex;
    margin-bottom: 24px;
    color: #64748b;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
}

.guide-back:hover {
    color: #0f172a;
}

.guide-eyebrow {
    display: inline-block;
    margin-bottom: 14px;
    color: var(--primary);
    font-size: 14px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.guide-title {
    max-width: 850px;
    margin: 0;
    font-size: clamp(38px, 6vw, 64px);
    line-height: 1.04;
    letter-spacing: -.045em;
}

.guide-lead {
    max-width: 760px;
    margin: 22px 0 0;
    color: #64748b;
    font-size: 19px;
    line-height: 1.75;
}

.guide-progress {
    display: grid;
    grid-template-columns: repeat(
        9,
        minmax(0, 1fr)
    );
    gap: 8px;
    margin-top: 38px;
}

.guide-progress-item {
    padding: 12px 8px;
    border-radius: 10px;
    background: #f8fafc;
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    text-align: center;
}

.guide-layout {
    display: grid;
    grid-template-columns:
        minmax(0, 240px)
        minmax(0, 760px);
    gap: 54px;
    align-items: start;
}

.guide-nav {
    position: sticky;
    top: 24px;
    padding: 20px;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #fff;
}

.guide-nav-title {
    display: block;
    margin-bottom: 14px;
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
}

.guide-nav a {
    display: block;
    padding: 8px 0;
    color: #64748b;
    font-size: 14px;
    line-height: 1.4;
    text-decoration: none;
}

.guide-nav a:hover {
    color: var(--primary);
}

.guide-content {
    min-width: 0;
}

.guide-section {
    padding: 18px 0 54px;
    border-bottom: 1px solid #e2e8f0;
    scroll-margin-top: 32px;
}

.guide-section:last-child {
    border-bottom: 0;
}

.guide-step {
    display: inline-flex;
    min-width: 38px;
    height: 38px;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    border-radius: 999px;
    background: #eff6ff;
    color: var(--primary);
    font-size: 14px;
    font-weight: 800;
}

.guide-section h2 {
    margin: 0 0 16px;
    font-size: 31px;
    line-height: 1.2;
    letter-spacing: -.03em;
}

.guide-section h3 {
    margin: 30px 0 12px;
    font-size: 20px;
}

.guide-section p {
    margin: 0 0 16px;
    color: #475569;
    font-size: 16px;
    line-height: 1.8;
}

.guide-section ul {
    margin: 16px 0 22px;
    padding-left: 22px;
    color: #475569;
}

.guide-section li {
    margin-bottom: 9px;
    line-height: 1.65;
}

.guide-box {
    margin: 24px 0;
    padding: 22px;
    border-radius: 16px;
    background: #f8fafc;
}

.guide-box strong {
    display: block;
    margin-bottom: 8px;
    color: #0f172a;
}

.guide-box p {
    margin: 0;
}

.guide-example {
    margin: 24px 0;
    padding: 22px;
    border-left: 4px solid var(--primary);
    border-radius: 0 14px 14px 0;
    background: #eff6ff;
}

.guide-example strong {
    display: block;
    margin-bottom: 8px;
}

.guide-flow {
    display: grid;
    gap: 10px;
    margin: 24px 0;
}

.guide-flow-item {
    padding: 16px 18px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
}

.guide-flow-arrow {
    color: #94a3b8;
    text-align: center;
    font-weight: 800;
}

.guide-checklist {
    display: grid;
    gap: 10px;
    margin-top: 22px;
}

.guide-check {
    display: flex;
    gap: 12px;
    padding: 15px;
    border-radius: 12px;
    background: #f8fafc;
}

.guide-check-icon {
    flex: 0 0 auto;
    font-weight: 800;
    color: #16a34a;
}

.guide-next {
    margin-top: 54px;
    padding: 38px;
    border-radius: 22px;
    background: #0f172a;
    color: #fff;
}

.guide-next h2 {
    margin: 0 0 12px;
    font-size: 30px;
}

.guide-next p {
    max-width: 620px;
    margin: 0 0 24px;
    color: #cbd5e1;
    line-height: 1.7;
}

.guide-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

@media (max-width: 900px) {
    .guide-layout {
        grid-template-columns: 1fr;
    }

    .guide-nav {
        position: static;
    }

    .guide-progress {
        grid-template-columns: repeat(
            3,
            minmax(0, 1fr)
        );
    }
}

@media (max-width: 600px) {
    .guide-progress {
        grid-template-columns: 1fr 1fr;
    }

    .guide-next {
        padding: 28px 20px;
    }
}
</style>

<div class="guide-page">

    <section class="guide-hero">
        <div class="container guide-hero-inner">

            <a
                href="{{ route('marketing.learn.index') }}"
                class="guide-back"
            >
                ← Central de Aprendizado
            </a>

            <span class="guide-eyebrow">
                Guia 01 • Primeiros passos
            </span>

            <h1 class="guide-title">
                Do cadastro à primeira venda
                organizada no Noobstron.
            </h1>

            <p class="guide-lead">
                Este guia mostra como preparar sua empresa,
                organizar sua equipe, cadastrar clientes,
                trabalhar com leads e oportunidades e construir
                um processo comercial que possa evoluir com
                sua operação.
            </p>

            <div class="guide-progress">
                <div class="guide-progress-item">Empresa</div>
                <div class="guide-progress-item">Equipe</div>
                <div class="guide-progress-item">Clientes</div>
                <div class="guide-progress-item">Pipeline</div>
                <div class="guide-progress-item">Leads</div>
                <div class="guide-progress-item">Oportunidades</div>
                <div class="guide-progress-item">Atividades</div>
                <div class="guide-progress-item">Propostas</div>
                <div class="guide-progress-item">Venda</div>
            </div>

        </div>
    </section>

    <section>
        <div class="container guide-layout">

            <aside class="guide-nav">

                <span class="guide-nav-title">
                    Neste guia
                </span>

                <a href="#visao-geral">
                    Antes de começar
                </a>

                <a href="#empresa">
                    1. Configure sua empresa
                </a>

                <a href="#equipe">
                    2. Monte sua equipe
                </a>

                <a href="#clientes">
                    3. Organize clientes
                </a>

                <a href="#pipeline">
                    4. Crie seu pipeline
                </a>

                <a href="#leads">
                    5. Organize seus leads
                </a>

                <a href="#oportunidades">
                    6. Crie oportunidades
                </a>

                <a href="#atividades">
                    7. Planeje atividades
                </a>

                <a href="#propostas">
                    8. Crie propostas
                </a>

                <a href="#venda">
                    9. Registre a venda
                </a>

                <a href="#evoluir">
                    Como evoluir
                </a>

            </aside>

            <main class="guide-content">

                <section
                    id="visao-geral"
                    class="guide-section"
                >

                    <span class="guide-step">
                        00
                    </span>

                    <h2>
                        Antes de começar
                    </h2>

                    <p>
                        O Noobstron foi pensado para conectar
                        informações que normalmente ficam
                        espalhadas entre planilhas, agendas,
                        caixas de e-mail, WhatsApp e sistemas
                        diferentes.
                    </p>

                    <p>
                        Você não precisa configurar todos os
                        recursos no primeiro dia. A melhor
                        estratégia é construir uma base simples
                        e confiável e evoluir conforme a equipe
                        começa a utilizar o sistema.
                    </p>

                    <div class="guide-flow">

                        <div class="guide-flow-item">
                            Organize empresa e equipe
                        </div>

                        <div class="guide-flow-arrow">
                            ↓
                        </div>

                        <div class="guide-flow-item">
                            Centralize clientes e leads
                        </div>

                        <div class="guide-flow-arrow">
                            ↓
                        </div>

                        <div class="guide-flow-item">
                            Estruture o processo comercial
                        </div>

                        <div class="guide-flow-arrow">
                            ↓
                        </div>

                        <div class="guide-flow-item">
                            Acompanhe oportunidades e atividades
                        </div>

                        <div class="guide-flow-arrow">
                            ↓
                        </div>

                        <div class="guide-flow-item">
                            Crie propostas e registre vendas
                        </div>

                    </div>

                    <div class="guide-box">
                        <strong>
                            Regra para começar bem
                        </strong>

                        <p>
                            Não tente reproduzir toda a complexidade
                            da empresa imediatamente. Comece pelo
                            processo comercial que sua equipe realmente
                            utiliza hoje.
                        </p>
                    </div>

                </section>

                <section
                    id="empresa"
                    class="guide-section"
                >

                    <span class="guide-step">
                        01
                    </span>

                    <h2>
                        Configure sua empresa
                    </h2>

                    <p>
                        O primeiro passo é garantir que o workspace
                        represente corretamente sua organização.
                        Essas informações ajudam o Noobstron a
                        preparar o ambiente para sua operação.
                    </p>

                    <h3>
                        Revise os dados principais
                    </h3>

                    <ul>
                        <li>Nome da empresa.</li>
                        <li>País de operação.</li>
                        <li>Idioma principal.</li>
                        <li>Segmento de atuação.</li>
                    </ul>

                    <p>
                        O segmento ajuda a contextualizar o tipo de
                        operação, enquanto país e idioma influenciam
                        a experiência apresentada aos usuários.
                    </p>

                    <div class="guide-example">
                        <strong>Exemplo</strong>

                        Uma empresa de serviços pode começar
                        configurando o segmento como Serviços e
                        posteriormente estruturar um pipeline
                        específico para orçamento, negociação e
                        fechamento.
                    </div>

                </section>

                <section
                    id="equipe"
                    class="guide-section"
                >

                    <span class="guide-step">
                        02
                    </span>

                    <h2>
                        Monte sua equipe
                    </h2>

                    <p>
                        Depois da empresa, cadastre as pessoas que
                        realmente vão participar do processo.
                    </p>

                    <p>
                        Usuários separados permitem identificar
                        responsáveis, controlar permissões e manter
                        histórico das ações realizadas.
                    </p>

                    <h3>
                        Comece com quem precisa operar
                    </h3>

                    <ul>
                        <li>Administradores.</li>
                        <li>Gestores comerciais.</li>
                        <li>Vendedores.</li>
                        <li>Atendimento.</li>
                        <li>Outros responsáveis necessários.</li>
                    </ul>

                    <div class="guide-box">
                        <strong>
                            Evite compartilhar usuários
                        </strong>

                        <p>
                            Cada pessoa deve utilizar sua própria
                            conta. Isso melhora segurança,
                            responsabilidade e auditoria.
                        </p>
                    </div>

                </section>

                <section
                    id="clientes"
                    class="guide-section"
                >

                    <span class="guide-step">
                        03
                    </span>

                    <h2>
                        Organize seus clientes
                    </h2>

                    <p>
                        O cadastro de clientes é a base do
                        relacionamento dentro do Noobstron.
                        É onde informações importantes deixam
                        de ficar espalhadas.
                    </p>

                    <h3>
                        Centralize o contexto
                    </h3>

                    <ul>
                        <li>Dados principais do cliente.</li>
                        <li>Contatos.</li>
                        <li>Telefones.</li>
                        <li>E-mails.</li>
                        <li>Endereços.</li>
                        <li>Histórico de relacionamento.</li>
                    </ul>

                    <p>
                        Quanto mais organizado o cadastro, mais fácil
                        fica entender quem é o cliente e o que já
                        aconteceu no relacionamento comercial.
                    </p>

                    <div class="guide-example">
                        <strong>Exemplo</strong>

                        Se um vendedor sair de férias, outro membro
                        da equipe pode consultar o histórico do
                        cliente e continuar o atendimento com muito
                        mais contexto.
                    </div>

                    <h3>
                        E se eu já tiver muitos clientes?
                    </h3>

                    <p>
                        Em vez de cadastrar um por um, utilize o
                        processo de importação para trazer dados
                        existentes de forma organizada.
                    </p>

                </section>

                <section
                    id="pipeline"
                    class="guide-section"
                >

                    <span class="guide-step">
                        04
                    </span>

                    <h2>
                        Crie seu pipeline de vendas
                    </h2>

                    <p>
                        O pipeline representa as etapas que uma
                        oportunidade percorre até chegar ao
                        fechamento.
                    </p>

                    <p>
                        Não existe um pipeline universal. Ele deve
                        representar o processo real da sua empresa.
                    </p>

                    <div class="guide-flow">

                        <div class="guide-flow-item">
                            Primeiro contato
                        </div>

                        <div class="guide-flow-arrow">
                            ↓
                        </div>

                        <div class="guide-flow-item">
                            Qualificação
                        </div>

                        <div class="guide-flow-arrow">
                            ↓
                        </div>

                        <div class="guide-flow-item">
                            Proposta
                        </div>

                        <div class="guide-flow-arrow">
                            ↓
                        </div>

                        <div class="guide-flow-item">
                            Negociação
                        </div>

                        <div class="guide-flow-arrow">
                            ↓
                        </div>

                        <div class="guide-flow-item">
                            Fechamento
                        </div>

                    </div>

                    <div class="guide-box">
                        <strong>
                            Comece simples
                        </strong>

                        <p>
                            Um pipeline com quatro ou cinco etapas
                            bem definidas costuma ser mais útil que
                            quinze etapas que ninguém entende.
                        </p>
                    </div>

                </section>

                <section
                    id="leads"
                    class="guide-section"
                >

                    <span class="guide-step">
                        05
                    </span>

                    <h2>
                        Organize seus leads
                    </h2>

                    <p>
                        Leads representam potenciais clientes que
                        ainda estão em processo de identificação
                        ou qualificação.
                    </p>

                    <p>
                        Registrar a origem e o status desses contatos
                        ajuda a equipe a entender de onde chegam as
                        oportunidades e quais merecem atenção.
                    </p>

                    <h3>
                        Algumas origens possíveis
                    </h3>

                    <ul>
                        <li>Site.</li>
                        <li>Indicação.</li>
                        <li>Campanha.</li>
                        <li>WhatsApp.</li>
                        <li>Prospecção ativa.</li>
                        <li>Evento.</li>
                    </ul>

                    <p>
                        Quando um lead se torna uma oportunidade
                        comercial real, ele pode avançar para a
                        próxima etapa do processo.
                    </p>

                </section>

                <section
                    id="oportunidades"
                    class="guide-section"
                >

                    <span class="guide-step">
                        06
                    </span>

                    <h2>
                        Transforme interesse em oportunidade
                    </h2>

                    <p>
                        Uma oportunidade representa uma negociação
                        concreta em andamento.
                    </p>

                    <p>
                        Nesse momento, você começa a acompanhar
                        informações como valor esperado, etapa
                        atual, responsável e evolução da negociação.
                    </p>

                    <div class="guide-example">
                        <strong>Exemplo</strong>

                        Um lead solicita uma demonstração e confirma
                        interesse em contratar. Ele deixa de ser
                        apenas um contato e passa a representar uma
                        oportunidade comercial.
                    </div>

                    <h3>
                        Mantenha o pipeline atualizado
                    </h3>

                    <p>
                        Ao mudar a situação da negociação, mova a
                        oportunidade para a etapa correspondente.
                        Isso mantém a visão comercial próxima da
                        realidade.
                    </p>

                </section>

                <section
                    id="atividades"
                    class="guide-section"
                >

                    <span class="guide-step">
                        07
                    </span>

                    <h2>
                        Nunca perca o próximo passo
                    </h2>

                    <p>
                        Uma oportunidade sem próxima ação definida
                        tende a ser esquecida.
                    </p>

                    <p>
                        Use atividades para registrar o que precisa
                        acontecer depois.
                    </p>

                    <ul>
                        <li>Ligação.</li>
                        <li>Reunião.</li>
                        <li>Enviar proposta.</li>
                        <li>Retornar ao cliente.</li>
                        <li>Fazer follow-up.</li>
                        <li>Confirmar documentação.</li>
                    </ul>

                    <div class="guide-box">
                        <strong>
                            Uma prática simples
                        </strong>

                        <p>
                            Sempre que terminar uma interação
                            importante, pergunte:
                            “qual é o próximo passo e quando ele
                            deve acontecer?”
                        </p>
                    </div>

                </section>

                <section
                    id="propostas"
                    class="guide-section"
                >

                    <span class="guide-step">
                        08
                    </span>

                    <h2>
                        Transforme a negociação em proposta
                    </h2>

                    <p>
                        Quando a oportunidade estiver madura,
                        formalize a oferta por meio de uma proposta.
                    </p>

                    <p>
                        O catálogo ajuda a manter produtos e serviços
                        organizados para serem utilizados nas
                        propostas comerciais.
                    </p>

                    <h3>
                        Uma proposta clara deve ajudar o cliente a entender
                    </h3>

                    <ul>
                        <li>O que está sendo oferecido.</li>
                        <li>Quantidade.</li>
                        <li>Valores.</li>
                        <li>Condições comerciais.</li>
                        <li>Contexto da negociação.</li>
                    </ul>

                    <p>
                        O objetivo não é apenas gerar um documento,
                        mas manter a proposta conectada à negociação
                        que originou aquela venda.
                    </p>

                </section>

                <section
                    id="venda"
                    class="guide-section"
                >

                    <span class="guide-step">
                        09
                    </span>

                    <h2>
                        Registre a primeira venda
                    </h2>

                    <p>
                        Quando a negociação for concluída, registre
                        a venda para que o resultado comercial não
                        fique desconectado do restante do processo.
                    </p>

                    <p>
                        A partir daí, informações financeiras e
                        recebíveis podem continuar o ciclo.
                    </p>

                    <div class="guide-flow">

                        <div class="guide-flow-item">
                            Lead
                        </div>

                        <div class="guide-flow-arrow">
                            ↓
                        </div>

                        <div class="guide-flow-item">
                            Oportunidade
                        </div>

                        <div class="guide-flow-arrow">
                            ↓
                        </div>

                        <div class="guide-flow-item">
                            Proposta
                        </div>

                        <div class="guide-flow-arrow">
                            ↓
                        </div>

                        <div class="guide-flow-item">
                            Venda
                        </div>

                        <div class="guide-flow-arrow">
                            ↓
                        </div>

                        <div class="guide-flow-item">
                            Recebimento e relacionamento
                        </div>

                    </div>

                </section>

                <section
                    id="evoluir"
                    class="guide-section"
                >

                    <span class="guide-step">
                        10
                    </span>

                    <h2>
                        Agora você pode evoluir
                    </h2>

                    <p>
                        Depois que equipe, clientes e processo
                        comercial estiverem organizados, o próximo
                        passo é ganhar eficiência.
                    </p>

                    <div class="guide-checklist">

                        <div class="guide-check">
                            <span class="guide-check-icon">
                                ✓
                            </span>

                            <span>
                                Centralizar e-mail e WhatsApp.
                            </span>
                        </div>

                        <div class="guide-check">
                            <span class="guide-check-icon">
                                ✓
                            </span>

                            <span>
                                Padronizar templates de comunicação.
                            </span>
                        </div>

                        <div class="guide-check">
                            <span class="guide-check-icon">
                                ✓
                            </span>

                            <span>
                                Automatizar tarefas repetitivas.
                            </span>
                        </div>

                        <div class="guide-check">
                            <span class="guide-check-icon">
                                ✓
                            </span>

                            <span>
                                Utilizar notificações e lembretes.
                            </span>
                        </div>

                        <div class="guide-check">
                            <span class="guide-check-icon">
                                ✓
                            </span>

                            <span>
                                Acompanhar indicadores financeiros.
                            </span>
                        </div>

                        <div class="guide-check">
                            <span class="guide-check-icon">
                                ✓
                            </span>

                            <span>
                                Utilizar IA para apoiar atividades
                                da equipe.
                            </span>
                        </div>

                    </div>

                    <div class="guide-next">

                        <h2>
                            Comece com uma base organizada.
                        </h2>

                        <p>
                            Você não precisa dominar todos os recursos
                            hoje. O importante é colocar o processo
                            principal para funcionar e evoluir a partir
                            dele.
                        </p>

                        <div class="guide-actions">

                            <a
                                href="{{ route('register') }}"
                                class="button"
                            >
                                Começar meu trial
                            </a>

                            <a
                                href="{{ route(
                                    'marketing.learn.index'
                                ) }}"
                                class="button button-secondary"
                            >
                                Ver outros guias
                            </a>

                        </div>

                    </div>

                </section>

            </main>

        </div>
    </section>

</div>

@endsection