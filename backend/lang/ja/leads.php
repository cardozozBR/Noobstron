<?php

return [
    'title' => 'リード',
    'new' => '新規リード',
    'edit' => 'リードを編集',
    'empty' => 'リードが見つかりません。',

    'fields' => [
        'name' => '名前',
        'email' => 'メール',
        'phone' => '電話番号',
        'status' => 'ステータス',
        'source' => '流入元',
        'responsible' => '担当者',
        'tags' => 'タグ',
        'notes' => 'メモ',
    ],

    'filters' => [
        'search' => '検索',
        'search_placeholder' => '名前、メール、電話番号',
        'all_statuses' => 'すべて',
        'all_sources' => 'すべて',
        'all_responsibles' => 'すべて',
        'filter' => '絞り込む',
        'clear' => 'クリア',
    ],

    'actions' => [
        'create' => 'リードを作成',
        'edit' => '編集',
        'delete' => '削除',
        'save' => '変更を保存',
        'back' => '戻る',
        'cancel' => 'キャンセル',
    ],

    'responsible_none' => '担当者なし',
    'tag_placeholder' => 'タグ',
    'actions_column' => '操作',
    'contact' => '連絡先',

    'status' => [
        'new' => '新規',
        'contacted' => '連絡済み',
        'qualified' => '有望',
        'unqualified' => '対象外',
    ],

    'source' => [
        'manual' => '手動',
        'website' => 'ウェブサイト',
        'referral' => '紹介',
        'social' => 'SNS',
        'other' => 'その他',
    ],

    'messages' => [
        'created' => 'リードを作成しました。',
        'updated' => 'リードを更新しました。',
        'deleted' => 'リードを削除しました。',
    ],
    'conversion' => '顧客変換',
    'convert' => '顧客に変換',
    'customer_type' => '顧客タイプ',
    'converted' => '変換済みリード',
    'individual' => '個人',
    'convert_confirm' => 'このリードを顧客に変換しますか？',
    'view_customer' => '顧客を表示',
    'company' => '法人',
    'converted_at' => '変換日時',
    'convert_help' => 'このリードの情報から顧客を作成します。',
    'conversion_success' => 'リードを顧客に変換しました。',

    'ai_rewrite' => 'AIで書き直す',
    'ai_rewrite_empty' => 'AIを使用する前に備考を入力してください。',
    'ai_rewrite_loading' => '書き直しています...',
    'ai_rewrite_success' => '備考を書き直しました。',
    'ai_rewrite_error' => '備考を書き直せませんでした。',
    'ai_rewrite_instruction' => '元の意味を保ちながら、このリードの備考を明確で自然かつプロフェッショナルな表現に書き直してください。',];
