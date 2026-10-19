<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    // Mailgun (เดิม sendmail() ใน system_config.php ของ SR: Mailgun API domain mg.srshippingsystem.com)
    // secret: MAILGUN_SECRET หรือ MAILGUN_SECRET_FILE (ไฟล์ key นอก repo — อ่านตอนใช้ mailer ครั้งแรก
    // ใน AppServiceProvider จึงไม่ถูกเขียนลง config cache)
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN', 'mg.srshippingsystem.com'),
        'secret' => env('MAILGUN_SECRET'),
        'secret_file' => env('MAILGUN_SECRET_FILE'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => env('MAILGUN_SCHEME', 'api'), // api = messages API แบบ sendmail() เดิม (https = ส่ง MIME ดิบ)
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
