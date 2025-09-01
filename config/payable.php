<?php

/**
 * Paypal Setting & API Credentials
 * Created by Raza Mehdi <srmk@outlook.com>.
 */

return [
    'table' => 'unfy_payments',
    'model' => \Unusualify\Payable\Models\Payment::class,
    'status_enum' => \Unusualify\Payable\Models\Enums\PaymentStatus::class,
    'additional_fillable' => [],
    'middleware' => [],
    'session_key' => 'payable',
    'return_url' => 'payment.response',
    'services' => [
        'paypal' => [
            'mode' => env('PAYPAL_MODE', 'sandbox'), // Can only be 'sandbox' Or 'live'. If empty or invalid, 'live' will be used.
            'sandbox' => [
                'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID', ''),
                'client_secret' => env('PAYPAL_SANDBOX_CLIENT_SECRET', ''),
                'app_id' => env('PAYPAL_APP_ID'),
            ],
            'live' => [
                'client_id' => env('PAYPAL_LIVE_CLIENT_ID', ''),
                'client_secret' => env('PAYPAL_LIVE_CLIENT_SECRET', ''),
                'app_id' => env('PAYPAL_LIVE_APP_ID', ''),
            ],
            'payment_action' => env('PAYPAL_PAYMENT_ACTION', 'Sale'), // Can only be 'Sale', 'Authorization' or 'Order'
            'currency' => env('PAYPAL_CURRENCY', 'USD'),
            'notify_url' => env('PAYPAL_NOTIFY_URL', ''), // Change this accordingly for your application.
            'locale' => env('PAYPAL_LOCALE', 'en_US'), // force gateway language  i.e. it_IT, es_ES, en_US ... (for express checkout only)
            'validate_ssl' => env('PAYPAL_VALIDATE_SSL', true), // Validate SSL when creating api client.
        ],
        'iyzico' => [
            'mode' => 'sandbox',
            'merchant_id' => env('IYZICO_MERCHANT_ID'),
            'token_refresh_time' => env('IYZICO_TOKEN_REFRESH_TIME'),

            'sandbox' => [
                'url' => env('IYZICO_TEST_URL'),
                'api_key' => env('IYZICO_TEST_API_KEY'),
                'api_secret' => env('IYZICO_TEST_API_SECRET'),
            ],
            'live' => [
                'url' => env('IYZICO_PROD_URL'),
                'api_key' => env('IYZICO_PROD_API_KEY'),
                'api_secret' => env('IYZICO_PROD_API_SECRET'),
            ],
        ],
        'garanti-pos' => [
            'mode' => env('GARANTI_POS_MODE', 'sandbox'),
            'sandbox' => [
                'url' => env('GARANTI_TEST_PAYMENT_URL', 'sandbox'),
                'merchant_id' => env('GARANTI_TEST_MERCHANT_ID', ''),
                'terminal_id' => env('GARANTI_TEST_3D_TERMINAL_ID', ''),
                '3dpay_terminal_id' => env('GARANTI_TEST_3D_PAY_TERMINAL_ID', ''),
                '3d_oos_pay_terminal_id' => env('GARANTI_TEST_3D_OOS_PAY_TERMINAL_ID', ''),
                'terminal_userid' => env('GARANTI_TEST_TERMINAL_USERID', ''),
                'provision_userid' => env('GARANTI_TEST_PROV_USERID', ''),
                'provision_pw' => env('GARANTI_TEST_PROV_PW', ''),
                'secure_key' => env('GARANTI_TEST_SECURE_KEY', ''),
                'pay_provision_userid' => env('GARANTIPAY_PROV_USER_ID', ''),
                'pay_provision_pw' => env('GARANTIPAY_PROV_USER_PW', ''),
                'api_version' => '512',

            ],
            'live' => [
                'url' => env('GARANTI_PAYMENT_URL', 'sandbox'),
                'merchant_id' => env('GARANTI_MERCHANT_ID', ''),
                'terminal_id' => env('GARANTI_3D_TERMINAL_ID', ''),
                '3d_terminal_id' => env('GARANTI_3D_TERMINAL_ID', ''),
                '3d_oos_pay_terminal_id' => env('GARANTI_3D_PAY_TERMINAL_ID', ''),
                'provision_userid' => env('GARANTI_PROV_USER_ID', ''),
                'terminal_userid' => env('GARANTI_TERMINAL_USERID', ''),
                'provision_pw' => env('GARANTI_PROV_USER_PW', ''),
                'secure_key' => env('GARANTI_SECURE_KEY', ''),
                'pay_provision_userid' => env('GARANTIPAY_PROV_USER_ID', ''),
                'pay_provision_pw' => env('GARANTIPAY_PROV_USER_PW', ''),
                'api_version' => '512',
            ],
            'store_key' => env('GARANTI_STORE_KEY', ''),
            'payment_type' => 'creditcard',
        ],
        'teb-pos' => [
            'mode' => env('TEB_POS_MODE', 'sandbox'),
            'sandbox' => [
                'url' => env('TEB_PAYMENT_URL'),
                'merchant_id' => env('TEB_CLIENT_ID', ''),
                'store_key' => env('TEB_STORE_KEY', ''),
            ],
            'live' => [
                'url' => env('TEB_PAYMENT_URL'),
                'merchant_id' => env('TEB_CLIENT_ID', ''),
                'store_key' => env('TEB_STORE_KEY', ''),
            ],
        ],
        'teb-common-pos' => [
            'mode' => env('TEB_COMMON_POS_MODE', ''),
            'sandbox' => [
                'url' => env('TEB_COMMON_TEST_URL', ''),
                'client_id' => env('TEB_COMMON_TEST_CLIENT_ID', ''),
                'api_user' => env('TEB_COMMON_TEST_API_USER', ''),
                'api_password' => env('TEB_COMMON_TEST_API_PASS', ''),
            ],
            'live' => [
                'url' => env('TEB_COMMON_URL', ''),
                'client_id' => env('TEB_COMMON_CLIENT_ID', ''),
                'api_user' => env('TEB_COMMON_API_USER', ''),
                'api_password' => env('TEB_COMMON_API_PASS', ''),
            ],
        ],
        'ideal' => [
            'mode' => env('BUCKAROO_MODE', 'sandbox'),
            'sandbox' => [
                'website_key' => env('BUCKAROO_SANDBOX_WEBSITE_KEY', ''),
                'secret_key' => env('BUCKAROO_SANDBOX_SECRET_KEY', ''),
            ],
            'live' => [
                'website_key' => env('BUCKAROO_LIVE_WEBSITE_KEY', ''),
                'secret_key' => env('BUCKAROO_LIVE_SECRET_KEY', ''),
            ],
        ],
        'ideal-qr' => [
            'mode' => env('BUCKAROO_MODE', 'sandbox'),
            'sandbox' => [
                'website_key' => env('BUCKAROO_SANDBOX_WEBSITE_KEY', ''),
                'secret_key' => env('BUCKAROO_SANDBOX_SECRET_KEY', ''),
            ],
            'live' => [
                'website_key' => env('BUCKAROO_LIVE_WEBSITE_KEY', ''),
                'secret_key' => env('BUCKAROO_LIVE_SECRET_KEY', ''),
            ],
        ],
        'revolut' => [
            'mode' => env('REVOLUT_MODE', 'sandbox'),
            'api_version' => env('REVOLUT_API_VERSION', '2024-05-01'),
            'sandbox' => [
                'api_url' => env('REVOLUT_SANDBOX_API_URL', ''),
                'public_key' => env('REVOLUT_SANDBOX_PUBLIC_KEY', ''),
                'secret_key' => env('REVOLUT_SANDBOX_SECRET_KEY', ''),
            ],
            'live' => [
                'api_url' => env('REVOLUT_LIVE_API_URL', ''),
                'public_key' => env('REVOLUT_LIVE_PUBLIC_KEY', ''),
                'secret_key' => env('REVOLUT_LIVE_SECRET_KEY', ''),
            ],
        ],
    ],
];
