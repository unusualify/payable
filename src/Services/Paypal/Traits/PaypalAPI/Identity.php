<?php

namespace Unusualify\Payable\Services\Paypal\Traits\PaypalAPI;

trait Identity
{
    /**
     * Get user profile information.
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/identity/v1/#userinfo_get
     */
    public function showProfileInfo()
    {
        $this->apiEndPoint = 'v1/identity/openidconnect/userinfo?schema=openid';

        $this->setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->verb = 'get';

        return $this->doPaypalRequest();
    }

    /**
     * List Users.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/identity/v2/#users_list
     */
    public function listUsers(string $field = 'userName')
    {
        $this->apiEndPoint = "v2/scim/Users?filter={$field}";

        $this->setRequestHeader('Content-Type', 'application/scim+json');

        $this->verb = 'get';

        return $this->doPaypalRequest();
    }

    /**
     * Show details for a user by ID.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/identity/v2/#users_get
     */
    public function showUserDetails(string $user_id)
    {
        $this->apiEndPoint = "v2/scim/Users/{$user_id}";

        $this->setRequestHeader('Content-Type', 'application/scim+json');

        $this->verb = 'get';

        return $this->doPaypalRequest();
    }

    /**
     * Delete a user by ID.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/identity/v2/#users_get
     */
    public function deleteUser(string $user_id)
    {
        $this->apiEndPoint = "v2/scim/Users/{$user_id}";

        $this->setRequestHeader('Content-Type', 'application/scim+json');

        $this->verb = 'delete';

        return $this->doPaypalRequest(false);
    }

    /**
     * Create a merchant application.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/identity/v1/#applications_post
     */
    public function createMerchantApplication(string $client_name, array $redirect_uris, array $contacts, string $payer_id, string $migrated_app, string $application_type = 'web', string $logo_url = '')
    {
        $this->apiEndPoint = 'v1/identity/applications';

        $this->options['json'] = array_filter([
            'application_type' => $application_type,
            'redirect_uris' => $redirect_uris,
            'client_name' => $client_name,
            'contacts' => $contacts,
            'payer_id' => $payer_id,
            'migrated_app' => $migrated_app,
            'logo_uri' => $logo_url,
        ]);

        $this->verb = 'post';

        return $this->doPaypalRequest();
    }

    /**
     * Create a merchant application.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/identity/v1/#account-settings_post
     */
    public function setAccountProperties(array $features, string $account_property = 'BRAINTREE_MERCHANT')
    {
        $this->apiEndPoint = 'v1/identity/account-settings';

        $this->options['json'] = [
            'account_property' => $account_property,
            'features' => $features,
        ];

        $this->verb = 'post';

        return $this->doPaypalRequest();
    }

    /**
     * Create a merchant application.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/identity/v1/#account-settings_deactivate
     */
    public function disableAccountProperties(string $account_property = 'BRAINTREE_MERCHANT')
    {
        $this->apiEndPoint = 'v1/identity/account-settings/deactivate';

        $this->options['json'] = [
            'account_property' => $account_property,
        ];

        $this->verb = 'post';

        return $this->doPaypalRequest();
    }

    /**
     * Get a client token.
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/multiparty/checkout/advanced/integrate/#link-sampleclienttokenrequest
     */
    public function getClientToken()
    {
        $this->apiEndPoint = 'v1/identity/generate-token';

        $this->verb = 'post';

        return $this->doPaypalRequest();
    }
}
