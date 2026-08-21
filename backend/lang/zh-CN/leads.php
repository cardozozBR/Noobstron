<?php

return [
    'title' => '潜在客户',
    'new' => '新建潜在客户',
    'edit' => '编辑潜在客户',
    'empty' => '未找到潜在客户。',

    'fields' => [
        'name' => '姓名',
        'email' => '电子邮箱',
        'phone' => '电话',
        'status' => '状态',
        'source' => '来源',
        'responsible' => '负责人',
        'tags' => '标签',
        'notes' => '备注',
    ],

    'filters' => [
        'search' => '搜索',
        'search_placeholder' => '姓名、邮箱或电话',
        'all_statuses' => '全部',
        'all_sources' => '全部',
        'all_responsibles' => '全部',
        'filter' => '筛选',
        'clear' => '清除',
    ],

    'actions' => [
        'create' => '创建潜在客户',
        'edit' => '编辑',
        'delete' => '删除',
        'save' => '保存更改',
        'back' => '返回',
        'cancel' => '取消',
    ],

    'responsible_none' => '无负责人',
    'tag_placeholder' => '标签',
    'actions_column' => '操作',
    'contact' => '联系方式',

    'status' => [
        'new' => '新建',
        'contacted' => '已联系',
        'qualified' => '已确认',
        'unqualified' => '不合格',
    ],

    'source' => [
        'manual' => '手动',
        'website' => '网站',
        'referral' => '推荐',
        'social' => '社交媒体',
        'other' => '其他',
    ],

    'messages' => [
        'created' => '潜在客户创建成功。',
        'updated' => '潜在客户更新成功。',
        'deleted' => '潜在客户删除成功。',
    ],
    'conversion' => '客户转换',
    'convert' => '转换为客户',
    'customer_type' => '客户类型',
    'converted' => '已转换线索',
    'individual' => '个人',
    'convert_confirm' => '确定要将此线索转换为客户吗？',
    'view_customer' => '查看客户',
    'company' => '企业',
    'converted_at' => '转换时间',
    'convert_help' => '使用此线索的数据创建客户。',
    'conversion_success' => '线索已成功转换为客户。',

    'ai_rewrite' => '使用 AI 重写',
    'ai_rewrite_empty' => '请先输入备注再使用 AI。',
    'ai_rewrite_loading' => '正在重写...',
    'ai_rewrite_success' => '备注已重写。',
    'ai_rewrite_error' => '无法重写备注。',
    'ai_rewrite_instruction' => '请在保留原意的同时，以清晰、自然且专业的方式重写此潜在客户备注。',];
