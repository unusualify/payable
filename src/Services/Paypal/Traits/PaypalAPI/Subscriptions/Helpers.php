<?php

namespace Unusualify\Payable\Services\Paypal\Traits\PaypalAPI\Subscriptions;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Throwable;

trait Helpers
{
    /**
     * @var array
     */
    protected $trial_pricing = [];

    /**
     * @var int
     */
    protected $payment_failure_threshold = 3;

    /**
     * @var array
     */
    protected $product;

    /**
     * @var array
     */
    protected $billing_plan;

    /**
     * @var array
     */
    protected $shipping_address;

    /**
     * @var array
     */
    protected $payment_preferences;

    /**
     * @var bool
     */
    protected $has_setup_fee = false;

    /**
     * @var array
     */
    protected $taxes;

    /**
     * @var string
     */
    protected $custom_id;

    /**
     * Setup a subscription.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws Throwable
     */
    public function setupSubscription(string $customer_name, string $customer_email, string $start_date = '')
    {
        $start_date = ! empty($start_date) ? Carbon::parse($start_date)->toIso8601String() : Carbon::now()->toIso8601String();

        $body = [
            'plan_id' => $this->billing_plan['id'],
            'start_time' => $start_date,
            'quantity' => 1,
            'subscriber' => [
                'name' => [
                    'given_name' => $customer_name,
                ],
                'email_address' => $customer_email,
            ],
        ];

        if ($this->has_setup_fee) {
            $body['plan'] = [
                'payment_preferences' => $this->payment_preferences,
            ];
        }

        if (isset($this->shipping_address)) {
            $body['subscriber']['shipping_address'] = $this->shipping_address;
        }

        if (isset($this->experience_context)) {
            $body['application_context'] = $this->experience_context;
        }

        if (isset($this->taxes)) {
            $body['taxes'] = $this->taxes;
        }

        if (isset($this->custom_id)) {
            $body['custom_id'] = $this->custom_id;
        }

        $subscription = $this->createSubscription($body);
        $subscription['billing_plan_id'] = $this->billing_plan['id'];
        $subscription['product_id'] = $this->product['id'];

        unset($this->product);
        unset($this->billing_plan);
        unset($this->trial_pricing);
        unset($this->return_url);
        unset($this->cancel_url);

        return $subscription;
    }

    /**
     * Add a subscription trial pricing tier.
     *
     * @param  float|int  $price
     */
    public function addPlanTrialPricing(string $interval_type, int $interval_count, float $price = 0, int $total_cycles = 1): \Srmklive\Paypal\Services\Paypal
    {
        $this->trial_pricing = $this->addPlanBillingCycle($interval_type, $interval_count, $price, $total_cycles, true);

        return $this;
    }

    /**
     * Create a recurring daily billing plan.
     *
     * @param  float|int  $price
     *
     * @throws Throwable
     */
    public function addDailyPlan(string $name, string $description, float $price, int $total_cycles = 0): \Srmklive\Paypal\Services\Paypal
    {
        if (isset($this->billing_plan)) {
            return $this;
        }

        $plan_pricing = $this->addPlanBillingCycle('DAY', 1, $price, $total_cycles);
        $billing_cycles = empty($this->trial_pricing) ? [$plan_pricing] : collect([$this->trial_pricing, $plan_pricing])->filter()->toArray();

        $this->addBillingPlan($name, $description, $billing_cycles);

        return $this;
    }

    /**
     * Create a recurring weekly billing plan.
     *
     * @param  float|int  $price
     *
     * @throws Throwable
     */
    public function addWeeklyPlan(string $name, string $description, float $price, int $total_cycles = 0): \Srmklive\Paypal\Services\Paypal
    {
        if (isset($this->billing_plan)) {
            return $this;
        }

        $plan_pricing = $this->addPlanBillingCycle('WEEK', 1, $price, $total_cycles);
        $billing_cycles = empty($this->trial_pricing) ? [$plan_pricing] : collect([$this->trial_pricing, $plan_pricing])->filter()->toArray();

        $this->addBillingPlan($name, $description, $billing_cycles);

        return $this;
    }

    /**
     * Create a recurring monthly billing plan.
     *
     * @param  float|int  $price
     *
     * @throws Throwable
     */
    public function addMonthlyPlan(string $name, string $description, float $price, int $total_cycles = 0): \Srmklive\Paypal\Services\Paypal
    {
        if (isset($this->billing_plan)) {
            return $this;
        }

        $plan_pricing = $this->addPlanBillingCycle('MONTH', 1, $price, $total_cycles);
        $billing_cycles = empty($this->trial_pricing) ? [$plan_pricing] : collect([$this->trial_pricing, $plan_pricing])->filter()->toArray();

        $this->addBillingPlan($name, $description, $billing_cycles);

        return $this;
    }

    /**
     * Create a recurring annual billing plan.
     *
     * @param  float|int  $price
     *
     * @throws Throwable
     */
    public function addAnnualPlan(string $name, string $description, float $price, int $total_cycles = 0): \Srmklive\Paypal\Services\Paypal
    {
        if (isset($this->billing_plan)) {
            return $this;
        }

        $plan_pricing = $this->addPlanBillingCycle('YEAR', 1, $price, $total_cycles);
        $billing_cycles = empty($this->trial_pricing) ? [$plan_pricing] : collect([$this->trial_pricing, $plan_pricing])->filter()->toArray();

        $this->addBillingPlan($name, $description, $billing_cycles);

        return $this;
    }

    /**
     * Create a recurring billing plan with custom intervals.
     *
     * @param  float|int  $price
     *
     * @throws Throwable
     */
    public function addCustomPlan(string $name, string $description, float $price, string $interval_unit, int $interval_count, int $total_cycles = 0): \Srmklive\Paypal\Services\Paypal
    {
        $billing_intervals = ['DAY', 'WEEK', 'MONTH', 'YEAR'];

        if (isset($this->billing_plan)) {
            return $this;
        }

        if (! in_array($interval_unit, $billing_intervals)) {
            throw new \RuntimeException('Billing intervals should either be '.implode(', ', $billing_intervals));
        }

        $plan_pricing = $this->addPlanBillingCycle($interval_unit, $interval_count, $price, $total_cycles);
        $billing_cycles = empty($this->trial_pricing) ? [$plan_pricing] : collect([$this->trial_pricing, $plan_pricing])->filter()->toArray();

        $this->addBillingPlan($name, $description, $billing_cycles);

        return $this;
    }

    /**
     * Add Plan's Billing cycle.
     */
    protected function addPlanBillingCycle(string $interval_unit, int $interval_count, float $price, int $total_cycles, bool $trial = false): array
    {
        $pricing_scheme = [
            'fixed_price' => [
                'value' => bcdiv($price, 1, 2),
                'currency_code' => $this->getCurrency(),
            ],
        ];

        if (empty($this->trial_pricing)) {
            $plan_sequence = 1;
        } else {
            $plan_sequence = 2;
        }

        return [
            'frequency' => [
                'interval_unit' => $interval_unit,
                'interval_count' => $interval_count,
            ],
            'tenure_type' => ($trial === true) ? 'TRIAL' : 'REGULAR',
            'sequence' => ($trial === true) ? 1 : $plan_sequence,
            'total_cycles' => $total_cycles,
            'pricing_scheme' => $pricing_scheme,
        ];
    }

    /**
     * Create a product for a subscription's billing plan.
     *
     *
     * @throws Throwable
     */
    public function addProduct(string $name, string $description, string $type, string $category): \Srmklive\Paypal\Services\Paypal
    {
        if (isset($this->product)) {
            return $this;
        }

        $request_id = Str::random();

        $product = $this->createProduct([
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'category' => $category,
        ], $request_id);

        if ($error = data_get($product, 'error', false)) {
            throw new \RuntimeException(data_get($error, 'details.0.description', 'Failed to add product'));
        }

        $this->product = $product;

        return $this;
    }

    /**
     * Add subscription's billing plan's product by ID.
     */
    public function addProductById(string $product_id): \Srmklive\Paypal\Services\Paypal
    {
        $this->product = [
            'id' => $product_id,
        ];

        return $this;
    }

    /**
     * Add subscription's billing plan by ID.
     */
    public function addBillingPlanById(string $plan_id): \Srmklive\Paypal\Services\Paypal
    {
        $this->billing_plan = [
            'id' => $plan_id,
        ];

        return $this;
    }

    /**
     * Create a product for a subscription's billing plan.
     *
     *
     * @throws Throwable
     */
    protected function addBillingPlan(string $name, string $description, array $billing_cycles): void
    {
        $request_id = Str::random();

        $plan_params = [
            'product_id' => $this->product['id'],
            'name' => $name,
            'description' => $description,
            'status' => 'ACTIVE',
            'billing_cycles' => $billing_cycles,
            'payment_preferences' => [
                'auto_bill_outstanding' => true,
                'setup_fee_failure_action' => 'CONTINUE',
                'payment_failure_threshold' => $this->payment_failure_threshold,
            ],
        ];

        $billingPlan = $this->createPlan($plan_params, $request_id);
        if ($error = data_get($billingPlan, 'error', false)) {
            throw new \RuntimeException(data_get($error, 'details.0.description', 'Failed to add billing plan'));
        }
        $this->billing_plan = $billingPlan;
    }

    /**
     * Set custom failure threshold when adding a subscription.
     */
    public function addPaymentFailureThreshold(int $threshold): \Srmklive\Paypal\Services\Paypal
    {
        $this->payment_failure_threshold = $threshold;

        return $this;
    }

    /**
     * Add setup fee when adding a subscription.
     */
    public function addSetupFee(float $price): \Srmklive\Paypal\Services\Paypal
    {
        $this->has_setup_fee = true;
        $this->payment_preferences = [
            'auto_bill_outstanding' => true,
            'setup_fee' => [
                'value' => $price,
                'currency_code' => $this->getCurrency(),
            ],
            'setup_fee_failure_action' => 'CONTINUE',
            'payment_failure_threshold' => $this->payment_failure_threshold,
        ];

        return $this;
    }

    /**
     * Add shipping address.
     */
    public function addShippingAddress(string $full_name, string $address_line_1, string $address_line_2, string $admin_area_2, string $admin_area_1, string $postal_code, string $country_code): \Srmklive\Paypal\Services\Paypal
    {
        $this->shipping_address = [
            'name' => [
                'full_name' => $full_name,
            ],
            'address' => [
                'address_line_1' => $address_line_1,
                'address_line_2' => $address_line_2,
                'admin_area_2' => $admin_area_2,
                'admin_area_1' => $admin_area_1,
                'postal_code' => $postal_code,
                'country_code' => $country_code,
            ],
        ];

        return $this;
    }

    /**
     * Add taxes when creating a subscription.
     *
     *
     * @return \Srmklive\Paypal\Services\Paypal
     */
    public function addTaxes(float $percentage)
    {
        $this->taxes = [
            'percentage' => $percentage,
            'inclusive' => false,
        ];

        return $this;
    }

    /**
     * Add custom id.
     *
     *
     * @return \Srmklive\Paypal\Services\Paypal
     */
    public function addCustomId(string $custom_id)
    {
        $this->custom_id = $custom_id;

        return $this;
    }
}
