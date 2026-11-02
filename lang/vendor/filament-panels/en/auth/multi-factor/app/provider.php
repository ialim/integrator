<?php

return [
    'management_schema' => [
        'actions' => [
            'label' => 'Authenticator app',
            'below_content' => 'An app on your phone, such as Google Authenticator or Microsoft Authenticator, shows a new 6-digit code every 30 seconds.',
            'messages' => [
                'enabled' => 'Set up',
                'disabled' => 'Not set up',
            ],
        ],
    ],
    'login_form' => [
        'label' => 'A code from your authenticator app',
        'code' => [
            'label' => 'Code from your authenticator app',
            'actions' => [
                'use_recovery_code' => ['label' => 'Use a recovery code instead'],
            ],
            'messages' => [
                'invalid' => 'That code is not right. Enter the code your authenticator app shows now.',
            ],
        ],
        'recovery_code' => [
            'label' => 'Recovery code',
            'messages' => [
                'invalid' => 'That recovery code is not right, or it has been used already.',
            ],
        ],
    ],
];
