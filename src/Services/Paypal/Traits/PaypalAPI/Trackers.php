<?php

namespace Unusualify\Payable\Services\Paypal\Traits\PaypalAPI;

trait Trackers
{
    /**
     * Adds tracking information, with or without tracking numbers, for multiple Paypal transactions.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/tracking/v1/#trackers-batch_post
     */
    public function addBatchTracking(array $data)
    {
        $this->apiEndPoint = 'v1/shipping/trackers-batch';

        $this->options['json'] = $data;

        $this->verb = 'post';

        return $this->doPaypalRequest();
    }

    /**
     * Adds tracking information for a Paypal transaction.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/tracking/v1/#trackers_post
     */
    public function addTracking(array $data)
    {
        $this->apiEndPoint = 'v1/shipping/trackers';

        $this->options['json'] = $data;

        $this->verb = 'post';

        return $this->doPaypalRequest();
    }

    /**
     * List tracking information based on Transaction ID or tracking number.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/tracking/v1/#trackers-batch_get
     */
    public function listTrackingDetails(string $transaction_id, ?string $tracking_number = null)
    {
        $this->apiEndPoint = "v1/shipping/trackers?transaction_id={$transaction_id}".! empty($tracking_number) ? "&tracking_number={$tracking_number}" : '';

        $this->verb = 'get';

        return $this->doPaypalRequest();
    }

    /**
     * Update tracking information.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/tracking/v1/#trackers_put
     */
    public function updateTrackingDetails(string $tracking_id, array $data)
    {
        $this->apiEndPoint = "v1/shipping/trackers/{$tracking_id}";

        $this->options['json'] = $data;

        $this->verb = 'put';

        return $this->doPaypalRequest(false);
    }

    /**
     * Show tracking information.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/tracking/v1/#trackers_get
     */
    public function showTrackingDetails(string $tracking_id)
    {
        $this->apiEndPoint = "v1/shipping/trackers/{$tracking_id}";

        $this->verb = 'get';

        return $this->doPaypalRequest();
    }
}
