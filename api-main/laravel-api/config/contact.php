<?php

return [
    'notification' => [
        'address' => env('CONTACT_NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),
        'name' => env('CONTACT_NOTIFICATION_NAME', env('MAIL_FROM_NAME', 'Yono Todolist')),
    ],

    'brand_name' => env('CONTACT_BRAND_NAME', env('APP_NAME', 'Yono Todolist')),
    'support_email' => env('CONTACT_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS', 'support@example.com')),
];
