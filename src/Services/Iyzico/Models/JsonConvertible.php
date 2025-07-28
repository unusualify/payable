<?php

namespace Unusualify\Payable\Services\Iyzico\Models;

interface JsonConvertible
{
    public function getJsonObject();

    public function toJsonString();
}
