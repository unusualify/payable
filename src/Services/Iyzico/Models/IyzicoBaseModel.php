<?php

namespace Unusualify\Payable\Services\Iyzico\Models;

abstract class IyzicoBaseModel implements JsonConvertible, RequestStringConvertible
{
    public function toJsonString()
    {
        return JsonBuilder::jsonEncode($this->getJsonObject());
    }
}
