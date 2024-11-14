<?php

namespace Unusualify\Payable\Services\Iyzico\Models;

interface RequestStringConvertible
{
    public function toPKIRequestString();
}