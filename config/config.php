<?php

/**
 * PayPal Setting & API Credentials
 * Created by Raza Mehdi <srmk@outlook.com>.
 */

return [
  "table" => "unfy_payments",
  "services" => [
    'paypal' => [
      'mode'    => env('PAYPAL_MODE', 'sandbox'), // Can only be 'sandbox' Or 'live'. If empty or invalid, 'live' will be used.
      'sandbox' => [
        'client_id'         => env('PAYPAL_SANDBOX_CLIENT_ID', ''),
        'client_secret'     => env('PAYPAL_SANDBOX_CLIENT_SECRET', ''),
        'app_id'            => env('PAYPAL_APP_ID'),
      ],
      'live' => [
        'client_id'         => env('PAYPAL_LIVE_CLIENT_ID', ''),
        'client_secret'     => env('PAYPAL_LIVE_CLIENT_SECRET', ''),
        'app_id'            => env('PAYPAL_LIVE_APP_ID', ''),
      ],

      'payment_action' => env('PAYPAL_PAYMENT_ACTION', 'Sale'), // Can only be 'Sale', 'Authorization' or 'Order'
      'currency'       => env('PAYPAL_CURRENCY', 'USD'),
      'notify_url'     => env('PAYPAL_NOTIFY_URL', ''), // Change this accordingly for your application.
      'locale'         => env('PAYPAL_LOCALE', 'en_US'), // force gateway language  i.e. it_IT, es_ES, en_US ... (for express checkout only)
      'validate_ssl'   => env('PAYPAL_VALIDATE_SSL', true), // Validate SSL when creating api client.
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
    'garantipos' => [
      'mode' => env('GARANTI_POS_MODE', 'sandbox'),
      'sandbox' => [
        'url' => env('GARANTI_TEST_PAYMENT_URL', 'sandbox'),
        'merchant_id' => env('GARANTI_TEST_MERCHANT_ID', ''),
        'terminal_id' => env('GARANTI_TEST_3D_TERMINAL_ID'),
        '3dpay_terminal_id' => env('GARANTI_TEST_3D_PAY_TERMINAL_ID'),
        '3d_oos_pay_terminal_id' => env('GARANTI_TEST_3D_OOS_PAY_TERMINAL_ID'),
        'provision_userid' => env('GARANTI_TEST_PROV_USERID'),
        'provision_pw' => env('GARANTI_TEST_PROV_PW'),
        'secure_key' => env('GARANTI_TEST_SECURE_KEY'),
        'pay_provision_userid' => env('GARANTIPAY_PROV_USER_ID'),
        'pay_provision_pw' => env('GARANTIPAY_PROV_USER_PW'),
        'api_version' => '512'

      ],
      'live' => [
        'url' => env('GARANTI_PAYMENT_URL', 'sandbox'),
        'merchant_id' => env('GARANTI_MERCHANT_ID', ''),
        'terminal_id' => env('GARANTI_3D_TERMINAL_ID'),
        '3d_terminal_id' => env('GARANTI_3D_TERMINAL_ID'),
        '3d_oos_pay_terminal_id' => env('GARANTI_3D_PAY_TERMINAL_ID'),
        'provision_userid' => env('GARANTI_PROV_USER_ID'),
        'provision_pw' => env('GARANTI_PROV_USER_PW'),
        'secure_key' => env('GARANTI_SECURE_KEY'),
        'api_version' => '512'

      ],
      'store_key' => env('GARANTI_STORE_KEY', ''),
      'payment_type' => 'creditcard'
    ],
    'tebpos' => [
      'mode' => env('TEB_POS_MODE', 'sandbox'),
      'sandbox' => [
        'url' => env('TEB_PAYMENT_URL'),
        'merchant_id' => env('TEB_CLIENT_ID', ''),
        'store_key' => env('TEB_STORE_KEY', '')
      ],
      'live' => [
        'url' => env('TEB_PAYMENT_URL'),
        'merchant_id' => env('TEB_CLIENT_ID', ''),
        'store_key' => env('TEB_STORE_KEY', '')
      ]

    ],
    'tebcommonpos' => [
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
      ]

    ]
  ],

];
