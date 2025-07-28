<?php

namespace Unusualify\Payable\Services;

class Currency
{
    private const CURRENCY_CODES = [
        'TRY' => 949,
        'USD' => 840,
        'EUR' => 978,
        'GBP' => 826,
        'CHF' => 756,
        'JPY' => 392,
    ];

    private $code;

    private $numericCode;

    public function __construct($currencyCode)
    {
        if (is_object($currencyCode) && isset($currencyCode->iso_4217_number)) {
            $this->numericCode = $currencyCode->iso_4217_number;

            return;
        }

        if (! is_string($currencyCode) || ! isset(self::CURRENCY_CODES[$currencyCode])) {
            throw new \InvalidArgumentException('Invalid or unsupported currency code');
        }

        $this->code = $currencyCode;
        $this->numericCode = self::CURRENCY_CODES[$currencyCode];
    }

    public static function getNumericCode($currencyCode): int
    {
        if (is_object($currencyCode) && isset($currencyCode->iso_4217_number)) {
            return $currencyCode->iso_4217_number;
        }

        if (! isset(self::CURRENCY_CODES[$currencyCode])) {
            throw new \InvalidArgumentException('Unsupported currency code: '.$currencyCode);
        }

        return self::CURRENCY_CODES[$currencyCode];
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getNumeric(): int
    {
        return $this->numericCode;
    }

    public static function isSupported($currencyCode): bool
    {
        if (is_object($currencyCode) && isset($currencyCode->iso_4217_number)) {
            return true;
        }

        return isset(self::CURRENCY_CODES[$currencyCode]);
    }

    public static function getSupportedCurrencies(): array
    {
        return array_keys(self::CURRENCY_CODES);
    }
}
