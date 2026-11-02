<?php

return [
    'label' => 'Set up the authenticator app',
    'modal' => [
        'heading' => 'Set up your authenticator app',
        'description' => 'You need an authenticator app on your phone, such as Google Authenticator or Microsoft Authenticator.',
        'content' => [
            'qr_code' => [
                'instruction' => 'Scan this QR code with your authenticator app.',
                'alt' => 'QR code to scan with your authenticator app',
            ],
            'text_code' => [
                'instruction' => 'Or type this key into the app:',
            ],
            'recovery_codes' => [
                'instruction' => 'Keep these recovery codes somewhere safe and offline. Each one signs you in once if you lose your phone. They are shown only now.',
            ],
        ],
        'form' => [
            'password' => [
                'label' => 'Your password',
                'validation_attribute' => 'password',
            ],
            'code' => [
                'label' => 'Code from your authenticator app',
                'below_content' => 'You will enter a code from the app each time you sign in to the console.',
                'messages' => [
                    'invalid' => 'That code is not right. Enter the code your authenticator app shows now.',
                    'rate_limited' => 'Too many attempts. Wait a minute, then try again.',
                ],
            ],
        ],
        'actions' => [
            'submit' => ['label' => 'Turn on the authenticator app'],
        ],
    ],
    'notifications' => [
        'enabled' => ['title' => 'Your authenticator app is set up'],
    ],
];
