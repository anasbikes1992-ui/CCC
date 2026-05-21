<?php

return [
    'whatsapp' => [
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
    ],

    'notify_lk' => [
        'user_id' => env('NOTIFY_LK_USER_ID'),
        'api_key' => env('NOTIFY_LK_API_KEY'),
        'sender_id' => env('NOTIFY_LK_SENDER_ID', 'NotifyDEMO'),
    ],

    'fcm' => [
        'credentials_path' => env('FCM_CREDENTIALS_PATH'),
        'project_id' => env('FCM_PROJECT_ID'),
    ],

    'webxpay' => [
        'merchant_id' => env('WEBXPAY_MERCHANT_ID'),
        'secret_key' => env('WEBXPAY_SECRET_KEY'),
        'return_url' => env('WEBXPAY_RETURN_URL'),
    ],

    'qr' => [
        'secret' => env('QR_TOKEN_SECRET'),
        'ttl_days' => (int) env('QR_TOKEN_TTL_DAYS', 30),
    ],
];
