<?php

namespace Unusualify\Payable\Services\Iyzico\Requests;


use Unusualify\Payable\Services\Iyzico\Models\JsonBuilder;
use Unusualify\Payable\Services\Iyzico\Models\RequestStringBuilder;
use Unusualify\Payable\Services\Iyzico\Requests\Request;

class ReportingPaymentDetailRequest extends Request
{
  private $paymentConversationId;

  public function getPaymentConversationId()
  {
    return $this->paymentConversationId;
  }

  public function setPaymentConversationId($paymentConversationId)
  {
    $this->paymentConversationId = $paymentConversationId;
  }

  public function getJsonObject()
  {
    return JsonBuilder::fromJsonObject(parent::getJsonObject())
      ->add("paymentConversationId", $this->getPaymentConversationId())
      ->getObject();
  }
}