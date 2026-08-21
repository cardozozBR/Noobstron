<?php

return [
    'navigation' => '活动',
    'title' => '活动',
    'new' => '新建活动',
    'edit' => '编辑活动',
    'create_title' => '新建活动',
    'edit_title' => '编辑活动',
    'create_description' => '创建任务、电话、会议或跟进。',
    'index_description' => '管理销售任务、联系和日程。',
    'empty' => '未找到活动。',
    'none' => '无',
    'actions_column' => '操作',
    'confirm_delete' => '确定要删除此活动吗？',

    'types' => [
        'task' => '任务',
        'call' => '电话',
        'meeting' => '会议',
        'follow_up' => '跟进',
    ],

    'statuses' => [
        'pending' => '待处理',
        'completed' => '已完成',
        'cancelled' => '已取消',
    ],

    'fields' => [
        'type' => '类型',
        'status' => '状态',
        'title' => '标题',
        'description' => '描述',
        'customer' => '客户',
        'opportunity' => '商机',
        'responsible' => '负责人',
        'due_at' => '截止时间',
    ],

    'filters' => [
        'search' => '搜索',
        'search_placeholder' => '活动标题',
        'all_types' => '所有类型',
        'all_statuses' => '所有状态',
        'all_customers' => '所有客户',
        'all_opportunities' => '所有商机',
        'all_responsibles' => '所有负责人',
        'filter' => '筛选',
        'clear' => '清除',
    ],

    'actions' => [
        'create' => '创建活动',
        'save' => '保存更改',
        'back' => '返回',
        'cancel' => '取消',
        'edit' => '编辑',
        'delete' => '删除',
        'complete' => '完成',
        'reopen' => '重新打开',
        'cancel_activity' => '取消活动',
    ],

    'messages' => [
        'created' => '活动创建成功。',
        'updated' => '活动更新成功。',
        'completed' => '活动已完成。',
        'reopened' => '活动已重新打开。',
        'cancelled' => '活动已取消。',
        'deleted' => '活动删除成功。',
    ],

    'ai_rewrite' => '使用 AI 重写',
    'ai_rewrite_empty' => '请先输入描述再使用 AI。',
    'ai_rewrite_loading' => '正在重写...',
    'ai_rewrite_success' => '描述已重写。',
    'ai_rewrite_error' => '无法重写描述。',
    'ai_rewrite_instruction' => '请在保留原意的同时，以清晰、自然且专业的方式重写此活动描述。',];
