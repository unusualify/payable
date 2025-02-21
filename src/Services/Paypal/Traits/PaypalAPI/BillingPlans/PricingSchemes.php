<?php

namespace Unusualify\Payable\Services\Paypal\Traits\PaypalAPI\BillingPlans;

use Throwable;

trait PricingSchemes
{
    protected $pricing_schemes = [];

    /**
     * Add pricing scheme for the billing plan.
     *
     * @param string $interval_unit
     * @param int    $interval_count
     * @param float  $price
     * @param bool   $trial
     *
     * @throws Throwable
     *
     * @return \Srmklive\Paypal\Services\Paypal
     */
    public function addPricingScheme(string $interval_unit, int $interval_count, float $price, bool $trial = false)
    {
        $this->pricing_schemes[] = $this->addPlanBillingCycle($interval_unit, $interval_count, $price, $trial);

        return $this;
    }

    /**
     * Process pricing updates for an existing billing plan.
     *
     * @throws \Throwable
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     */
    public function processBillingPlanPricingUpdates()
    {
        return $this->updatePlanPricing($this->billing_plan['id'], $this->pricing_schemes);
    }
}
