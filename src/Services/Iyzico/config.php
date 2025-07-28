<?php

use Unusualify\Payable\Services\Iyzico\Models\Options;

class IyzicoConfig
{
    public static function options($apiKey, $secretKey, $baseUrl)
    {
        $options = new Options($apiKey, $secretKey, $baseUrl);

        return $options;
    }
}
