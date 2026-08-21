<?php

return [
    'navigation' => '売掛金',
    'title' => '売掛金',
    'index_description' => '顧客の請求、支払期限、入金状況を管理します。',
    'create_title' => '売掛金を作成',
    'create_description' => '顧客向けの新しい請求を登録します。',
    'edit_title' => '売掛金を編集',
    'edit_description' => '未払いの売掛金情報を更新します。',
    'empty' => '登録された売掛金はありません。',
    'select_customer' => '顧客を選択',
    'no_sale' => '売上との関連なし',
    'back' => '売掛金一覧へ戻る',
    'create_action' => '売掛金を作成',
    'edit_action' => '編集',
    'update_action' => '変更を保存',
    'pay_action' => '支払済みにする',
    'cancel_action' => 'キャンセル',
    'actions' => '操作',
    'closed_edit_notice' => '未払いの売掛金のみ変更できます。',
    'payment_reference_placeholder' => '支払参照',

    'fields' => [
        'title' => 'タイトル',
        'customer' => '顧客',
        'sale' => '売上',
        'currency' => '通貨',
        'amount' => '金額',
        'amount_minor' => '最小通貨単位の金額',
        'due_date' => '支払期限',
        'status' => 'ステータス',
        'payment' => '支払',
        'paid_at' => '支払日時',
        'payment_reference' => '参照',
    ],

    'statuses' => [
        'pending' => '未払い',
        'paid' => '支払済み',
        'cancelled' => 'キャンセル済み',
    ],

    'messages' => [
        'created' => '売掛金を作成しました。',
        'updated' => '売掛金を更新しました。',
        'paid' => '売掛金を支払済みにしました。',
        'cancelled' => '売掛金をキャンセルしました。',
    ],
];