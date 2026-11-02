<?php

// Staff console sign-in copy: plain voice, sentence case, buttons that say what they do.
return [
    'title' => 'Sign in',
    'heading' => 'Sign in to the staff console',
    'form' => [
        'email' => ['label' => 'Email address'],
        'password' => ['label' => 'Password'],
        'actions' => [
            'authenticate' => ['label' => 'Continue'],
        ],
    ],
    'multi_factor' => [
        'heading' => 'Enter your authenticator code',
        'subheading' => 'Open the authenticator app on your phone and enter the 6-digit code it shows for integrator.',
        'form' => [
            'actions' => [
                'authenticate' => ['label' => 'Sign in'],
            ],
        ],
    ],
    'messages' => [
        'failed' => 'The email address or password is not right. Check them and try again.',
    ],
];
