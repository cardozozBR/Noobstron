<?php

return [
    'navigation' => '催收',
    'title' => '催收',
    'description' => '管理催收尝试、发送和失败记录。',
    'create_title' => '新建催收',
    'create_description' => '为待支付的应收款创建新的催收尝试。',
    'empty' => '暂无催收记录。',
    'create_action' => '创建催收',
    'back' => '返回催收',
    'select_receivable' => '选择应收款',
    'no_schedule' => '未安排',
    'actions' => '操作',
    'sent_action' => '标记为已发送',
    'failed_action' => '标记为失败',
    'cancel_action' => '取消',
    'external_reference_placeholder' => '外部参考',
    'failure_reason_placeholder' => '失败原因',

    'fields' => [
        'receivable' => '应收款',
        'customer' => '客户',
        'attempt' => '尝试次数',
        'status' => '状态',
        'scheduled_at' => '计划时间',
        'sent_at' => '发送时间',
        'channel' => '渠道',
        'recipient' => '接收方',
        'external_reference' => '外部参考',
        'failure_reason' => '失败原因',
    ],

    'statuses' => [
        'pending' => '待处理',
        'sent' => '已发送',
        'paid' => '已支付',
        'failed' => '失败',
        'cancelled' => '已取消',
    ],

    'messages' => [
        'created' => '催收已创建。',
        'sent' => '催收已标记为已发送。',
        'failed' => '催收已标记为失败。',
        'cancelled' => '催收已取消。',
    ],
];