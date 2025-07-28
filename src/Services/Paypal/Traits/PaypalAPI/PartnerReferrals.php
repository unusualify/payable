<?php

namespace Unusualify\Payable\Services\Paypal\Traits\PaypalAPI;

trait PartnerReferrals
{
    /**
     * Create a Partner Referral.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/partner-referrals/v2/#partner-referrals_create
     */
    public function createPartnerReferral(array $partner_data)
    {
        $this->apiEndPoint = 'v2/customer/partner-referrals';

        $this->options['json'] = $partner_data;

        $this->verb = 'post';

        return $this->doPaypalRequest();
    }

    /**
     * Get Partner Referral Details.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/partner-referrals/v2/#partner-referrals_read
     */
    public function showReferralData(string $partner_referral_id)
    {
        $this->apiEndPoint = "v2/customer/partner-referrals/{$partner_referral_id}";

        $this->verb = 'get';

        return $this->doPaypalRequest();
    }

    /**
     * List Seller Tracking Information.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/partner-referrals/v1/#merchant-integration_find
     */
    public function listSellerTrackingInformation(string $partner_id, string $tracking_id)
    {
        $this->apiEndPoint = "v1/customer/partners/{$partner_id}/merchant-integrations?tracking_id={$tracking_id}";

        $this->verb = 'get';

        return $this->doPaypalRequest();
    }

    /**
     * Show Seller Status.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/partner-referrals/v1/#merchant-integration_status
     */
    public function showSellerStatus(string $partner_id, string $merchant_id)
    {
        $this->apiEndPoint = "v1/customer/partners/{$partner_id}/merchant-integrations/{$merchant_id}";

        $this->verb = 'get';

        return $this->doPaypalRequest();
    }

    /**
     * List Merchant Credentials.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/partner-referrals/v1/#merchant-integration_credentials
     */
    public function listMerchantCredentials(string $partner_id)
    {
        $this->apiEndPoint = "v1/customer/partners/{$partner_id}/merchant-integrations/credentials";

        $this->verb = 'get';

        return $this->doPaypalRequest();
    }
}
