<?php

return [
    'navigation' => 'å•†è«‡',
    'title' => 'å•†è«‡',
    'new' => 'æ–°ã—ã„å•†è«‡',
    'edit' => 'å•†è«‡ã‚’ç·¨é›†',
    'create_title' => 'æ–°ã—ã„å•†è«‡',
    'edit_title' => 'å•†è«‡ã‚’ç·¨é›†',
    'create_description' => 'æ–°ã—ã„å–¶æ¥­å•†è«‡ã‚’ä½œæˆã—ã¾ã™ã€‚',


    'index_description' => '営業商談を管理します。',
    'empty' => '商談が見つかりません。',
    'actions_column' => '操作',
    'confirm_delete' => 'この商談を削除しますか？',

    'filters' => [
        'search' => '検索',
        'search_placeholder' => '商談名',
        'all_customers' => 'すべての顧客',
        'all_pipelines' => 'すべてのパイプライン',
        'all_stages' => 'すべてのステージ',
        'all_responsibles' => 'すべての担当者',
        'filter' => '絞り込む',
        'clear' => 'クリア',
    ],
    'fields' => [
        'name' => 'åå‰',
        'customer' => 'é¡§å®¢',
        'pipeline' => 'ãƒ‘ã‚¤ãƒ—ãƒ©ã‚¤ãƒ³',
        'stage' => 'ã‚¹ãƒ†ãƒ¼ã‚¸',
        'responsible' => 'æ‹…å½“è€…',
        'value_minor' => 'æœ€å°é€šè²¨å˜ä½ã®é‡‘é¡',
        'currency' => 'é€šè²¨',
        'probability' => 'ç¢ºåº¦ (%)',
        'expected_close_date' => 'äºˆå®šæˆç´„æ—¥',
        'notes' => 'ãƒ¡ãƒ¢',
    ],

    'actions' => [
        'create' => 'å•†è«‡ã‚’ä½œæˆ',
        'save' => 'å¤‰æ›´ã‚’ä¿å­˜',
        'back' => 'æˆ»ã‚‹',
        'cancel' => 'ã‚­ãƒ£ãƒ³ã‚»ãƒ«',
        'edit' => 'ç·¨é›†',
        'delete' => 'å‰Šé™¤',
    ],

    'select_customer' => 'é¡§å®¢ã‚’é¸æŠž',
    'select_pipeline' => 'ãƒ‘ã‚¤ãƒ—ãƒ©ã‚¤ãƒ³ã‚’é¸æŠž',
    'select_stage' => 'ã‚¹ãƒ†ãƒ¼ã‚¸ã‚’é¸æŠž',
    'responsible_none' => 'æ‹…å½“è€…ãªã—',
    'stage_help' => 'é¸æŠžã—ãŸãƒ‘ã‚¤ãƒ—ãƒ©ã‚¤ãƒ³ã«å±žã™ã‚‹ã‚¹ãƒ†ãƒ¼ã‚¸ã‚’é¸æŠžã—ã¦ãã ã•ã„ã€‚',
    'value_minor_help' => 'é‡‘é¡ã‚’é€šè²¨ã®æœ€å°å˜ä½ã§å…¥åŠ›ã—ã¦ãã ã•ã„ã€‚',

    'messages' => [
        'created' => 'å•†è«‡ã‚’ä½œæˆã—ã¾ã—ãŸã€‚',
        'updated' => 'å•†è«‡ã‚’æ›´æ–°ã—ã¾ã—ãŸã€‚',
        'stage_changed' => 'å•†è«‡ã®ã‚¹ãƒ†ãƒ¼ã‚¸ã‚’æ›´æ–°ã—ã¾ã—ãŸã€‚',
        'deleted' => 'å•†è«‡ã‚’å‰Šé™¤ã—ã¾ã—ãŸã€‚',
    ],

    'ai_rewrite' => 'AIで書き直す',
    'ai_rewrite_empty' => 'AIを使用する前に備考を入力してください。',
    'ai_rewrite_loading' => '書き直しています...',
    'ai_rewrite_success' => '備考を書き直しました。',
    'ai_rewrite_error' => '備考を書き直せませんでした。',
    'ai_rewrite_instruction' => '元の意味を保ちながら、商談の備考を明確で自然かつプロフェッショナルな表現に書き直してください。',];
