<?php

return [
    'required' => ':attributeは必須です。',
    'string' => ':attributeは文字列で入力してください。',
    'email' => ':attributeには有効なメールアドレスを入力してください。',
    'unique' => 'この:attributeはすでに使用されています。',
    'confirmed' => ':attributeの確認が一致しません。',
    'in' => '選択された:attributeは無効です。',
    'integer' => ':attributeには整数を入力してください。',
    'date' => ':attributeには有効な日付を入力してください。',
    'array' => ':attributeには有効なリストを指定してください。',
    'exists' => '選択された:attributeは無効です。',

    'min' => [
        'string' => ':attributeは:min文字以上で入力してください。',
        'numeric' => ':attributeは:min以上で入力してください。',
        'array' => ':attributeには:min個以上の項目が必要です。',
    ],

    'max' => [
        'string' => ':attributeは:max文字以内で入力してください。',
        'numeric' => ':attributeは:max以下で入力してください。',
        'array' => ':attributeは:max個以内にしてください。',
    ],

    'auth' => [
        'invalid_credentials' => 'メールアドレスまたはパスワードが正しくありません。',
    ],

    'attributes' => [
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード確認',
        'role' => '役割',
        'action' => 'アクション',
        'search' => '検索',
        'user_id' => 'ユーザー',
        'origin' => '発生元',
        'date_from' => '開始日',
        'date_to' => '終了日',
        'permissions' => '権限',
    ],
];