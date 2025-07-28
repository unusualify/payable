<?php

namespace Unusualify\Payable\Services\Iyzico\Models;

class RequestFormatter
{
    public static function formatPrice($price)
    {
        if (strpos($price, '.') === false) {
            return $price.'.0';
        }
        $subStrIndex = 0;
        $priceReversed = strrev($price);
        for ($i = 0; $i < strlen($priceReversed); $i++) {
            if (strcmp($priceReversed[$i], '0') == 0) {
                $subStrIndex = $i + 1;
            } elseif (strcmp($priceReversed[$i], '.') == 0) {
                $priceReversed = '0'.$priceReversed;
                break;
            } else {
                break;
            }
        }

        return strrev(substr($priceReversed, $subStrIndex));
    }
}
