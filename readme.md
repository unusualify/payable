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


## Troubleshooting

[This section would typically include common issues and their solutions.]

## Contributing

[Contribution guidelines would go here.]

## License

MIT License
Copyright (c) 2024 Unusualify
Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:
The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
