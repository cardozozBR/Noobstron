<?php

return [
    'navigation' => '活動',
    'title' => '活動',
    'new' => '新しい活動',
    'edit' => '活動を編集',
    'create_title' => '新しい活動',
    'edit_title' => '活動を編集',
    'create_description' => 'タスク、電話、会議、フォローアップを登録します。',
    'index_description' => '営業活動と予定を管理します。',
    'empty' => '活動が見つかりません。',
    'none' => 'なし',
    'actions_column' => '操作',
    'confirm_delete' => 'この活動を削除しますか？',

    'types' => [
        'task' => 'タスク',
        'call' => '電話',
        'meeting' => '会議',
        'follow_up' => 'フォローアップ',
    ],

    'statuses' => [
        'pending' => '未完了',
        'completed' => '完了',
        'cancelled' => 'キャンセル',
    ],

    'fields' => [
        'type' => '種類',
        'status' => '状態',
        'title' => 'タイトル',
        'description' => '説明',
        'customer' => '顧客',
        'opportunity' => '商談',
        'responsible' => '担当者',
        'due_at' => '期限',
    ],

    'filters' => [
        'search' => '検索',
        'search_placeholder' => '活動タイトル',
        'all_types' => 'すべての種類',
        'all_statuses' => 'すべての状態',
        'all_customers' => 'すべての顧客',
        'all_opportunities' => 'すべての商談',
        'all_responsibles' => 'すべての担当者',
        'filter' => '絞り込む',
        'clear' => 'クリア',
    ],

    'actions' => [
        'create' => '活動を作成',
        'save' => '変更を保存',
        'back' => '戻る',
        'cancel' => 'キャンセル',
        'edit' => '編集',
        'delete' => '削除',
        'complete' => '完了',
        'reopen' => '再開',
        'cancel_activity' => '活動をキャンセル',
    ],

    'messages' => [
        'created' => '活動を作成しました。',
        'updated' => '活動を更新しました。',
        'completed' => '活動を完了しました。',
        'reopened' => '活動を再開しました。',
        'cancelled' => '活動をキャンセルしました。',
        'deleted' => '活動を削除しました。',
    ],

    'ai_rewrite' => 'AIで書き直す',
    'ai_rewrite_empty' => 'AIを使用する前に説明を入力してください。',
    'ai_rewrite_loading' => '書き直しています...',
    'ai_rewrite_success' => '説明を書き直しました。',
    'ai_rewrite_error' => '説明を書き直せませんでした。',
    'ai_rewrite_instruction' => '元の意味を保ちながら、このアクティビティの説明を明確で自然かつプロフェッショナルな表現に書き直してください。',];
