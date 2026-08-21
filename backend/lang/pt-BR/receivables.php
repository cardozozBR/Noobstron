<?php

return [
    'navigation' => 'Contas a receber',
    'title' => 'Contas a receber',
    'index_description' => 'Acompanhe títulos, vencimentos e pagamentos dos clientes.',
    'create_title' => 'Nova conta a receber',
    'create_description' => 'Cadastre um novo título financeiro para o cliente.',
    'edit_title' => 'Editar conta a receber',
    'edit_description' => 'Atualize os dados do título enquanto ele estiver pendente.',
    'empty' => 'Nenhuma conta a receber registrada.',
    'select_customer' => 'Selecione o cliente',
    'no_sale' => 'Sem venda vinculada',
    'back' => 'Voltar para contas a receber',
    'create_action' => 'Criar conta a receber',
    'edit_action' => 'Editar',
    'update_action' => 'Salvar alterações',
    'pay_action' => 'Marcar como paga',
    'cancel_action' => 'Cancelar',
    'actions' => 'Ações',
    'closed_edit_notice' => 'Somente títulos pendentes podem ser alterados.',
    'payment_reference_placeholder' => 'Referência do pagamento',

    'fields' => [
        'title' => 'Título',
        'customer' => 'Cliente',
        'sale' => 'Venda',
        'currency' => 'Moeda',
        'amount' => 'Valor',
        'amount_minor' => 'Valor em minor units',
        'due_date' => 'Vencimento',
        'status' => 'Status',
        'payment' => 'Pagamento',
        'paid_at' => 'Pago em',
        'payment_reference' => 'Referência',
    ],

    'statuses' => [
        'pending' => 'Pendente',
        'paid' => 'Pago',
        'cancelled' => 'Cancelado',
    ],

    'messages' => [
        'created' => 'Conta a receber criada.',
        'updated' => 'Conta a receber atualizada.',
        'paid' => 'Conta a receber marcada como paga.',
        'cancelled' => 'Conta a receber cancelada.',
    ],
];