# Laravel Payment Package Documentation

## Overview

This Laravel package provides a flexible and extensible solution for integrating various payment gateways into your Laravel application. It offers a unified interface for handling different payment services, making it easier to manage multiple payment providers within your project.

## Key Features

- Easy integration with multiple payment gateways
- Unified interface for common payment operations (pay, cancel, refund)
- Customizable configuration for each payment service
- Built-in model for storing payment records
- Extensible architecture for adding new payment services

## Installation

To install the package, run the following command in your Laravel project:

```bash
composer require unusualify/payable
```

## Configuration

After installation, publish the configuration file:

```bash
php artisan vendor:publish --provider="Unusualify\Payable\LaravelServiceProvider"
```

This will create a `payable.php` configuration file in your `config` directory.

In the `payable.php` configuration file, you need to specify a route name for return_url. In default it is set as `payment.response`.
You should create a GET & POST routes for the same url and specify a a route name.

## Usage

### Basic Usage

To use a payment service:

```php
use Unusualify\Payable\Payable;

$payable = new Payable('gateway_slug');
$result = $payable->pay($params);
```

Replace `'gateway_slug'` with the slug of your desired payment gateway (e.g., 'stripe', 'paypal').

### Available Methods

- `pay($params)`: Process a payment
- `cancel($params)`: Cancel a payment
- `refund($params)`: Refund a payment
- `formatPrice($price)`: Format the price according to the gateway's requirements
- `formatAmount($amount)`: Format the amount according to the gateway's requirements

### Storing Payment Records

The package includes a `Payment` model for storing payment records. You can access it like any other Eloquent model:

```php
use Unusualify\Payable\Models\Payment;

$payment = Payment::create([
    'payment_gateway' => 'paypal',
    'order_id' => '12345',
    'amount' => 1000,
    'currency_id' => 'USD',
    'status' => 'pending',
    'email' => 'customer@example.com',
    // ... other fields
]);
```

### Payment Payload Guide

When using the `pay()` method, you need to provide a properly structured payload. Here's an example of a complete payment payload:

```php
$payload = [
    // Required fields
    'amount' => 1000,                           // Amount in the smallest currency unit (e.g., cents)
    'currency' => 'USD',                        // Currency code in ISO 4217 format
    'order_id' => uniqid(''),                // Unique order identifier
    
    // Locale and payment details
    'locale' => 'en',                           // User's locale
    'installment' => '1',                       // Number of installments (if supported)
    'payment_group' => 'PRODUCT',               // Payment category
    
    // Card information
    'card_name' => 'John Doe',                  // Cardholder name
    'card_no' => '4111111111111111',            // Card number (no spaces)
    'card_month' => '12',                       // Expiration month
    'card_year' => '2025',                      // Expiration year
    'card_cvv' => '123',                        // Security code
    
    // User information
    'user_name' => 'John',                      // User's first name
    'user_surname' => 'Doe',                    // User's last name
    'user_gsm' => '+1234567890',                // User's phone number
    'user_email' => 'john.doe@example.com',     // User's email
    'user_ip' => '127.0.0.1',                   // User's IP address
    
    // Billing/shipping information
    'company_name' => 'Example Corp',           // Company name
    'user_address' => '123 Main St',            // Street address
    'user_city' => 'New York',                  // City
    'user_country' => 'US',                     // Country
    'user_zip_code' => '10001',                 // Postal/ZIP code
    
    // Basket information
    'basket_id' => uniqid(),                    // Unique basket identifier
    'items' => [
        [
            'id' => '1',                        // Product ID
            'name' => 'Product Name',           // Product name
            'category1' => 'Category',          // Primary category
            'category2' => 'Subcategory',       // Secondary category
            'price' => 1000,                    // Product price
            'type' => 'VIRTUAL',                // Product type
        ],
        // Add more items as needed
    ],
    
    // Custom data (optional)
    'custom_data' => [
        'reference_id' => 'REF123',
        'notes' => 'Special instructions'
    ],
];

// Optional payment record attributes
$paymentPayload = [
    'price_id' => 1,                            // Reference to your price model
    'payment_service_id' => 2,                  // Payment service ID
    'currency_id' => 3,                         // Currency ID in your system
];

$payable = new Payable('gateway_slug');
$result = $payable->pay($payload, $paymentPayload);
```

Notes:
- The `amount` should be provided in the smallest currency unit (e.g., cents for USD/EUR, pence for GBP)
- Different payment gateways may require different fields. Check your specific gateway's documentation
- The `paymentPayload` parameter is optional and used to store additional data in the payment record

## Troubleshooting

[This section would typically include common issues and their solutions.]

## Contributing

[Contribution guidelines would go here.]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
