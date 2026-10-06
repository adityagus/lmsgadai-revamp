<?php
return [
    'resend' => [
        'key' => env('MAIL_API'),
        'from' => env('MAIL_FROM_ADDRESS'),
    ],
    'mix' => [
        'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage/aktif/',
        'img_url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage/aktif/',
    ],
];
