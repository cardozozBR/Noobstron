<?php

return [
    'required' => ':attribute为必填项。',
    'string' => ':attribute必须是文本。',
    'email' => ':attribute必须是有效的电子邮箱地址。',
    'unique' => '该:attribute已被使用。',
    'confirmed' => ':attribute确认内容不匹配。',
    'in' => '所选的:attribute无效。',
    'integer' => ':attribute必须是整数。',
    'date' => ':attribute必须是有效日期。',
    'array' => ':attribute必须是有效列表。',
    'exists' => '所选的:attribute无效。',

    'min' => [
        'string' => ':attribute至少需要:min个字符。',
        'numeric' => ':attribute不能小于:min。',
        'array' => ':attribute至少需要:min项。',
    ],

    'max' => [
        'string' => ':attribute不能超过:max个字符。',
        'numeric' => ':attribute不能大于:max。',
        'array' => ':attribute不能超过:max项。',
    ],

    'auth' => [
        'invalid_credentials' => '电子邮箱或密码不正确。',
    ],

    'attributes' => [
        'name' => '姓名',
        'email' => '电子邮箱',
        'password' => '密码',
        'password_confirmation' => '密码确认',
        'role' => '角色',
        'action' => '操作',
        'search' => '搜索',
        'user_id' => '用户',
        'origin' => '来源',
        'date_from' => '开始日期',
        'date_to' => '结束日期',
        'permissions' => '权限',
    ],
];