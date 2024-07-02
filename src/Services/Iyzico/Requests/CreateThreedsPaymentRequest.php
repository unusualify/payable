<?php
namespace Unusualify\Payable\Services\Iyzico\Requests;

use Unusualify\Payable\Services\Iyzico\Models\JsonBuilder;
use Unusualify\Payable\Services\Iyzico\Requests\Request;
use Unusualify\Payable\Services\Iyzico\Models\RequestStringBuilder;

class CreateThreedsPaymentRequest extends Request
{
  private $paymentId;
  private $conversationData;

  public function getPaymentId()
  {
    return $this->paymentId;
  }

  public function setPaymentId($paymentId)
  {
    $this->paymentId = $paymentId;
  }

  public function getConversationData()
  {
    return $this->conversationData;
  }

  public function setConversationData($conversationData)
  {
    $this->conversationData = $conversationData;
  }

  public function getJsonObject()
  {
    return JsonBuilder::fromJsonObject(parent::getJsonObject())
      ->add("paymentId", $this->getPaymentId())
      ->add("conversationData", $this->getConversationData())
      ->getObject();
  }

  public function toPKIRequestString()
  {
    return RequestStringBuilder::create()
      ->appendSuper(parent::toPKIRequestString())
      ->append("paymentId", $this->getPaymentId())
      ->append("conversationData", $this->getConversationData())
      ->getRequestString();
  }
}