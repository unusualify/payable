<?php

namespace Unusualify\Payable\Services\Iyzico\Requests;

use Unusualify\Payable\Services\Iyzico\Models\JsonBuilder;
use Unusualify\Payable\Services\Iyzico\Requests\Request;
use Unusualify\Payable\Services\Iyzico\Models\RequestStringBuilder;

class CreateRefundRequestV2 extends Request
{
  private $paymentId;
  private $price;

  public function getPaymentId()
  {
    return $this->paymentId;
  }

  public function setPaymentId($paymentId)
  {
    $this->paymentId = $paymentId;
  }

  public function getPrice()
  {
    return $this->price;
  }

  public function setPrice($price)
  {
    $this->price = $price;
  }

  public function getJsonObject()
  {
    return JsonBuilder::fromJsonObject(parent::getJsonObject())
      ->add("paymentId", $this->getPaymentId())
      ->addPrice("price", $this->getPrice())
      ->getObject();
  }

  public function toPKIRequestString()
  {
    return RequestStringBuilder::create()
      ->appendSuper(parent::toPKIRequestString())
      ->append("paymentTransactionId", $this->getPaymentId())
      ->appendPrice("price", $this->getPrice())
      ->getRequestString();
  }
}