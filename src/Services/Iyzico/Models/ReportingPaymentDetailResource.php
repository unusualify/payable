<?php

namespace Unusualify\Payable\Services\Iyzico\Models;

use Unusualify\Payable\Services\Iyzico\IyzicoResource;

class ReportingPaymentDetailResource extends IyzicoResource
{
    private $payments;

    public function getPayments()
    {
        return $this->payments;
    }

    public function setPayments($payments)
    {
        $this->payments = $payments;
    }
}
