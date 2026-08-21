<?php

return [
    'navigation' => '应收账款',
    'title' => '应收账款',
    'index_description' => '管理客户应收款、到期日和付款情况。',
    'create_title' => '新建应收款',
    'create_description' => '为客户创建新的应收款记录。',
    'edit_title' => '编辑应收款',
    'edit_description' => '在应收款处于待支付状态时更新其信息。',
    'empty' => '暂无应收款记录。',
    'select_customer' => '选择客户',
    'no_sale' => '未关联销售',
    'back' => '返回应收账款',
    'create_action' => '创建应收款',
    'edit_action' => '编辑',
    'update_action' => '保存更改',
    'pay_action' => '标记为已支付',
    'cancel_action' => '取消',
    'actions' => '操作',
    'closed_edit_notice' => '只有待支付的应收款可以修改。',
    'payment_reference_placeholder' => '付款参考',

    'fields' => [
        'title' => '标题',
        'customer' => '客户',
        'sale' => '销售',
        'currency' => '货币',
        'amount' => '金额',
        'amount_minor' => '最小货币单位金额',
        'due_date' => '到期日',
        'status' => '状态',
        'payment' => '付款',
        'paid_at' => '支付时间',
        'payment_reference' => '参考',
    ],

    'statuses' => [
        'pending' => '待支付',
        'paid' => '已支付',
        'cancelled' => '已取消',
    ],

    'messages' => [
        'created' => '应收款已创建。',
        'updated' => '应收款已更新。',
        'paid' => '应收款已标记为已支付。',
        'cancelled' => '应收款已取消。',
    ],
];