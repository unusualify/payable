<?php

namespace Unusualify\Payable\Services\Paypal\Traits;

trait PaypalAPI
{
  use PaypalAPI\Trackers;
  use PaypalAPI\CatalogProducts;
  use PaypalAPI\Disputes;
  use PaypalAPI\DisputesActions;
  use PaypalAPI\Identity;
  use PaypalAPI\Invoices;
  use PaypalAPI\InvoicesSearch;
  use PaypalAPI\InvoicesTemplates;
  use PaypalAPI\Orders;
  use PaypalAPI\PartnerReferrals;
  use PaypalAPI\PaymentExperienceWebProfiles;
  use PaypalAPI\PaymentMethodsTokens;
  use PaypalAPI\PaymentAuthorizations;
  use PaypalAPI\PaymentCaptures;
  use PaypalAPI\PaymentRefunds;
  use PaypalAPI\Payouts;
  use PaypalAPI\ReferencedPayouts;
  use PaypalAPI\BillingPlans;
  use PaypalAPI\Subscriptions;
  use PaypalAPI\Reporting;
  use PaypalAPI\WebHooks;
  use PaypalAPI\WebHooksVerification;
  use PaypalAPI\WebHooksEvents;

  /**
   * Login through PayPal API to get access token.
   *
   * @throws \Throwable
   *
   * @return array|\Psr\Http\Message\StreamInterface|string
   *
   * @see https://developer.paypal.com/docs/api/get-an-access-token-curl/
   * @see https://developer.paypal.com/docs/api/get-an-access-token-postman/
   */
  public function getAccessToken()
  {
    $this->apiEndPoint = 'v1/oauth2/token';

    $this->options['auth'] = [$this->config['client_id'], $this->config['client_secret']];
    $this->options[$this->httpBodyParam] = [
      'grant_type' => 'client_credentials',
    ];
    // dd($this->options);
    $response = $this->doPayPalRequest();
    dd($response);
    unset($this->options['auth']);
    unset($this->options[$this->httpBodyParam]);
    // dd($response);
    if (isset($response['access_token'])) {
      $this->setAccessToken($response);
    }

    return $response;
  }

  /**
   * Set PayPal Rest API access token.
   *
   * @param array $response
   *
   * @return void
   */
  public function setAccessToken(array $response)
  {
    $this->access_token = $response['access_token'];

    $this->setPayPalAppId($response);

    $this->setRequestHeader('Authorization', "{$response['token_type']} {$this->access_token}");
  }

  /**
   * Set PayPal App ID.
   *
   * @param array $response
   *
   * @return void
   */
  private function setPayPalAppId(array $response)
  {
    $app_id = empty($response['app_id']) ? $this->config['app_id'] : $response['app_id'];

    $this->config['app_id'] = $app_id;
  }

  /**
   * Set records per page for list resources API calls.
   *
   * @param int $size
   *
   * @return \Srmklive\PayPal\Services\PayPal
   */
  public function setPageSize(int $size): \Srmklive\PayPal\Services\PayPal
  {
    $this->page_size = $size;

    return $this;
  }

  /**
   * Set the current page for list resources API calls.
   *
   * @param int $size
   *
   * @return \Srmklive\PayPal\Services\PayPal
   */
  public function setCurrentPage(int $page): \Srmklive\PayPal\Services\PayPal
  {
    $this->current_page = $page;

    return $this;
  }

  /**
   * Toggle whether totals for list resources are returned after every API call.
   *
   * @param bool $totals
   *
   * @return \Srmklive\PayPal\Services\PayPal
   */
  public function showTotals(bool $totals): \Srmklive\PayPal\Services\PayPal
  {
    $this->show_totals = var_export($totals, true);

    return $this;
  }
}
