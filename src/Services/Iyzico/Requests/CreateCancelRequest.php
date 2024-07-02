<?php

namespace Unusualify\Payable\Services\Iyzico\Requests;

use Unusualify\Payable\Services\Iyzico\Models\JsonBuilder;
use Unusualify\Payable\Services\Iyzico\Requests\Request;
use Unusualify\Payable\Services\Iyzico\Models\RequestStringBuilder;

class CreateCancelRequest extends Request
{
    private $paymentId;
    private $ip;
    private $reason;
    private $description;

    public function getIp()
    {
        return $this->ip;
    }

    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    public function getPaymentId()
    {
        return $this->paymentId;
    }

    public function setPaymentId($paymentId)
    {
        $this->paymentId = $paymentId;
    }

    public function getReason()
    {
        return $this->reason;
    }

    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getJsonObject()
    {
        return JsonBuilder::fromJsonObject(parent::getJsonObject())
            ->add("paymentId", $this->getPaymentId())
            ->add("ip", $this->getIp())
            ->add("reason", $this->getReason())
            ->add("description", $this->getDescription())
            ->getObject();
    }

    public function toPKIRequestString()
    {
        return RequestStringBuilder::create()
            ->appendSuper(parent::toPKIRequestString())
            ->append("paymentId", $this->getPaymentId())
            ->append("ip", $this->getIp())
            ->append("reason", $this->getReason())
            ->append("description", $this->getDescription())
            ->getRequestString();
    }
}
