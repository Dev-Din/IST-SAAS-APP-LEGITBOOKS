<?php

return [
    'smtp' => [
        'host'       => env('MAIL_SMTP_HOST', 'smtp.gmail.com'),
        'port'       => env('MAIL_SMTP_PORT', 587),
        'username'   => env('MAIL_SMTP_USERNAME'),
        'password'   => env('MAIL_SMTP_PASSWORD'),
        'encryption' => env('MAIL_SMTP_ENCRYPTION', 'tls'),
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', env('MAIL_SMTP_USERNAME')),
            'name'    => env('MAIL_FROM_NAME', 'LegitBooks'),
        ],
    ],
];

