<?php

namespace Unusualify\Payable\Services\Paypal\Traits\PaypalAPI\InvoiceSearch;

use Carbon\Carbon;

trait Filters
{
  /**
   * @var array
   */
  protected $invoice_search_filters = [];

  /**
   * @var array
   */
  protected $invoices_date_types = [
    'invoice_date',
    'due_date',
    'payment_date',
    'creation_date',
  ];

  /**
   * @var array
   */
  protected $invoices_status_types = [
    'DRAFT',
    'SENT',
    'SCHEDULED',
    'PAID',
    'MARKED_AS_PAID',
    'CANCELLED',
    'REFUNDED',
    'PARTIALLY_PAID',
    'PARTIALLY_REFUNDED',
    'MARKED_AS_REFUNDED',
    'UNPAID',
    'PAYMENT_PENDING',
  ];

  /**
   * @param string $email
   *
   * @return \Srmklive\Paypal\Services\Paypal
   */
  public function addInvoiceFilterByRecipientEmail(string $email): \Srmklive\Paypal\Services\Paypal
  {
    $this->invoice_search_filters['recipient_email'] = $email;

    return $this;
  }

  /**
   * @param string $name
   *
   * @return \Srmklive\Paypal\Services\Paypal
   */
  public function addInvoiceFilterByRecipientFirstName(string $name): \Srmklive\Paypal\Services\Paypal
  {
    $this->invoice_search_filters['recipient_first_name'] = $name;

    return $this;
  }

  /**
   * @param string $name
   *
   * @return \Srmklive\Paypal\Services\Paypal
   */
  public function addInvoiceFilterByRecipientLastName(string $name): \Srmklive\Paypal\Services\Paypal
  {
    $this->invoice_search_filters['recipient_last_name'] = $name;

    return $this;
  }

  /**
   * @param string $name
   *
   * @return \Srmklive\Paypal\Services\Paypal
   */
  public function addInvoiceFilterByRecipientBusinessName(string $name): \Srmklive\Paypal\Services\Paypal
  {
    $this->invoice_search_filters['recipient_business_name'] = $name;

    return $this;
  }

  /**
   * @param string $invoice_number
   *
   * @return \Srmklive\Paypal\Services\Paypal
   */
  public function addInvoiceFilterByInvoiceNumber(string $invoice_number): \Srmklive\Paypal\Services\Paypal
  {
    $this->invoice_search_filters['invoice_number'] = $invoice_number;

    return $this;
  }

  /**
   * @param array $status
   *
   * @throws \Exception
   *
   * @return \Srmklive\Paypal\Services\Paypal
   *
   * @see https://developer.paypal.com/docs/api/invoicing/v2/#definition-invoice_status
   */
  public function addInvoiceFilterByInvoiceStatus(array $status): \Srmklive\Paypal\Services\Paypal
  {
    $invalid_status = false;

    foreach ($status as $item) {
      if (!in_array($item, $this->invoices_status_types)) {
        $invalid_status = true;
      }
    }

    if ($invalid_status === true) {
      throw new \Exception('status should be always one of these: ' . implode(',', $this->invoices_date_types));
    }

    $this->invoice_search_filters['status'] = $status;

    return $this;
  }

  /**
   * @param string $reference
   * @param bool   $memo
   *
   * @return \Srmklive\Paypal\Services\Paypal
   */
  public function addInvoiceFilterByReferenceorMemo(string $reference, bool $memo = false): \Srmklive\Paypal\Services\Paypal
  {
    $field = ($memo === false) ? 'reference' : 'memo';

    $this->invoice_search_filters[$field] = $reference;

    return $this;
  }

  /**
   * @param string $currency_code
   *
   * @return \Srmklive\Paypal\Services\Paypal
   */
  public function addInvoiceFilterByCurrencyCode(string $currency_code = ''): \Srmklive\Paypal\Services\Paypal
  {
    $currency = !isset($currency_code) ? $this->getCurrency() : $currency_code;

    $this->invoice_search_filters['currency_code'] = $currency;

    return $this;
  }

  /**
   * @param float  $start_amount
   * @param float  $end_amount
   * @param string $amount_currency
   *
   * @throws \Exception
   *
   * @return \Srmklive\Paypal\Services\Paypal
   */
  public function addInvoiceFilterByAmountRange(float $start_amount, float $end_amount, string $amount_currency = ''): \Srmklive\Paypal\Services\Paypal
  {
    if ($start_amount > $end_amount) {
      throw new \Exception('Starting amount should always be less than end amount!');
    }

    $currency = !isset($amount_currency) ? $this->getCurrency() : $amount_currency;

    $this->invoice_search_filters['total_amount_range'] = [
      'lower_amount' => [
        'currency_code' => $currency,
        'value'         => $start_amount,
      ],
      'upper_amount' => [
        'currency_code' => $currency,
        'value'         => $end_amount,
      ],
    ];

    return $this;
  }

  /**
   * @param string $start_date
   * @param string $end_date
   * @param string $date_type
   *
   * @throws \Exception
   *
   * @return \Srmklive\Paypal\Services\Paypal
   */
  public function addInvoiceFilterByDateRange(string $start_date, string $end_date, string $date_type): \Srmklive\Paypal\Services\Paypal
  {
    $start_date_obj = Carbon::parse($start_date);
    $end_date_obj = Carbon::parse($end_date);

    if ($start_date_obj->gt($end_date_obj)) {
      throw new \Exception('Starting date should always be less than the end date!');
    }

    if (!in_array($date_type, $this->invoices_date_types)) {
      throw new \Exception('date type should be always one of these: ' . implode(',', $this->invoices_date_types));
    }

    $this->invoice_search_filters["{$date_type}_range"] = [
      'start' => $start_date,
      'end'   => $end_date,
    ];

    return $this;
  }

  /**
   * @param bool $archived
   *
   * @return \Srmklive\Paypal\Services\Paypal
   */
  public function addInvoiceFilterByArchivedStatus(bool $archived = null): \Srmklive\Paypal\Services\Paypal
  {
    $this->invoice_search_filters['archived'] = $archived;

    return $this;
  }

  /**
   * @param array $fields
   *
   * @return \Srmklive\Paypal\Services\Paypal
   *
   * @see https://developer.paypal.com/docs/api/invoicing/v2/#definition-field
   */
  public function addInvoiceFilterByFields(array $fields): \Srmklive\Paypal\Services\Paypal
  {
    $this->invoice_search_filters['status'] = $fields;

    return $this;
  }
}
