<?php

namespace Unusualify\Payable\Services\Paypal\Traits;

trait PaypalAPI
{
    use PaypalAPI\BillingPlans;
    use PaypalAPI\CatalogProducts;
    use PaypalAPI\Disputes;
    use PaypalAPI\DisputesActions;
    use PaypalAPI\Identity;
    use PaypalAPI\Invoices;
    use PaypalAPI\InvoicesSearch;
    use PaypalAPI\InvoicesTemplates;
    use PaypalAPI\Orders;
    use PaypalAPI\PartnerReferrals;
    use PaypalAPI\PaymentAuthorizations;
    use PaypalAPI\PaymentCaptures;
    use PaypalAPI\PaymentExperienceWebProfiles;
    use PaypalAPI\PaymentMethodsTokens;
    use PaypalAPI\PaymentRefunds;
    use PaypalAPI\Payouts;
    use PaypalAPI\ReferencedPayouts;
    use PaypalAPI\Reporting;
    use PaypalAPI\Subscriptions;
    use PaypalAPI\Trackers;
    use PaypalAPI\WebHooks;
    use PaypalAPI\WebHooksEvents;
    use PaypalAPI\WebHooksVerification;

    /**
     * Login through Paypal API to get access token.
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/get-an-access-token-curl/
     * @see https://developer.paypal.com/docs/api/get-an-access-token-postman/
     */
    public function getAccessToken()
    {
        $this->apiEndPoint = 'v1/oauth2/token';
        $this->options['auth'] = [$this->config['client_id'], $this->config['client_secret']];
        $this->options['request_body'] = [
            'grant_type' => 'client_credentials',
        ];
        $this->type = 'encoded';
        $this->generateBasicAuthHeaders();
        // dd($this->url, $this->apiEndPoint);
        $this->verb = 'post';
        $response = json_decode($this->doPaypalRequest());

        unset($this->options['auth']);
        unset($this->options[$this->httpBodyParam]);
        // dd($response);
        if (isset($response->access_token)) {
            $this->setAccessToken($response);
        }

        return $response;
    }

    public function generateBasicAuthHeaders()
    {
        // dd($this->headers);
        $this->headers['Authorization'] = 'Basic '.base64_encode($this->options['auth'][0].':'.$this->options['auth'][1]);
    }

    /**
     * Set Paypal Rest API access token.
     *
     *
     * @return void
     */
    public function setAccessToken(object $response)
    {
        $this->access_token = $response->access_token;

        $this->setPaypalAppId($response);
        // dd($response->token_type, $this->access_token);
        // dd($this->setRequestHeader('Authorization',
        //     "{$response->token_type} {$this->access_token}"),
        //     $response->token_type, $this->access_token);
        $this->setRequestHeader('Authorization', "{$response->token_type} {$this->access_token}");

        if (isset($_SESSION['Paypal-Request-Id'])) {
            $this->setRequestHeader('Paypal-Request-Id', "{$_SESSION['Paypal-Request-Id']}".uniqid());
        } else {
            $_SESSION['Paypal-Request-Id'] = session()->getId().uniqid();
            $this->setRequestHeader('Paypal-Request-Id', "{$_SESSION['Paypal-Request-Id']}");
        }
        // dd($this->headers);
    }

    public function generateRequestId() {}

    /**
     * Set Paypal App ID.
     *
     * @param  object  $response
     * @return void
     */
    private function setPaypalAppId($response)
    {
        $app_id = empty($response->app_id) ? $this->config->app_id : $response->app_id;

        $this->config['app_id'] = $app_id;
    }

    /**
     * Set records per page for list resources API calls.
     *
     *
     * @return \Unusualify\Payable\Services\Paypal\PaypalService
     */
    public function setPageSize(int $size): \Unusualify\Payable\Services\PaypalService
    {
        $this->page_size = $size;

        return $this;
    }

    /**
     * Set the current page for list resources API calls.
     *
     * @param  int  $size
     * @return \Unusualify\Payable\Services\Paypal\PaypalService
     */
    public function setCurrentPage(int $page): \Unusualify\Payable\Services\PaypalService
    {
        $this->current_page = $page;

        return $this;
    }

    /**
     * Toggle whether totals for list resources are returned after every API call.
     *
     *
     * @return \Unusualify\Payable\Services\Paypal\PaypalService
     */
    public function showTotals(bool $totals): \Unusualify\Payable\Services\PaypalService
    {
        $this->show_totals = var_export($totals, true);

        return $this;
    }
}
