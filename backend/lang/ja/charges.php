<?php

return [
    'navigation' => '請求',
    'title' => '請求',
    'description' => '請求試行、送信、失敗を管理します。',
    'create_title' => '新しい請求',
    'create_description' => '未払いの売掛金に対する新しい請求試行を作成します。',
    'empty' => '請求は登録されていません。',
    'create_action' => '請求を作成',
    'back' => '請求一覧へ戻る',
    'select_receivable' => '売掛金を選択',
    'no_schedule' => '予定なし',
    'actions' => '操作',
    'sent_action' => '送信済みにする',
    'failed_action' => '失敗にする',
    'cancel_action' => 'キャンセル',
    'external_reference_placeholder' => '外部参照',
    'failure_reason_placeholder' => '失敗理由',

    'fields' => [
        'receivable' => '売掛金',
        'customer' => '顧客',
        'attempt' => '試行',
        'status' => 'ステータス',
        'scheduled_at' => '予定日時',
        'sent_at' => '送信日時',
        'channel' => 'チャネル',
        'recipient' => '送信先',
        'external_reference' => '外部参照',
        'failure_reason' => '失敗理由',
    ],

    'statuses' => [
        'pending' => '保留中',
        'sent' => '送信済み',
        'paid' => '支払済み',
        'failed' => '失敗',
        'cancelled' => 'キャンセル済み',
    ],

    'messages' => [
        'created' => '請求を作成しました。',
        'sent' => '請求を送信済みにしました。',
        'failed' => '請求を失敗として記録しました。',
        'cancelled' => '請求をキャンセルしました。',
    ],
];