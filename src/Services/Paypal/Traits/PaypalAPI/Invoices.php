<?php

namespace Unusualify\Payable\Services\Paypal\Traits\PaypalAPI;

trait Invoices
{
    /**
     * Create a new draft invoice.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/invoicing/v2/#invoices_create
     */
    public function createInvoice(array $data)
    {
        $this->apiEndPoint = 'v2/invoicing/invoices';

        $this->options['json'] = $data;

        $this->verb = 'post';

        return $this->doPaypalRequest();
    }

    /**
     * Get list of invoices.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/invoicing/v2/#invoices_list
     */
    public function listInvoices(array $fields = [])
    {
        $fields_list = collect($fields);

        $fields = ($fields_list->count() > 0) ? "&fields={$fields_list->implode(',')}" : '';

        $this->apiEndPoint = "v2/invoicing/invoices?page={$this->current_page}&page_size={$this->page_size}&total_required={$this->show_totals}{$fields}";

        $this->verb = 'get';

        return $this->doPaypalRequest();
    }

    /**
     * Send an existing invoice.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/invoicing/v2/#invoices_send
     */
    public function sendInvoice(string $invoice_id, string $subject = '', string $note = '', bool $send_recipient = true, bool $send_merchant = false, array $recipients = [])
    {
        $this->apiEndPoint = "v2/invoicing/invoices/{$invoice_id}/send";

        $this->options['json'] = $this->getInvoiceMessagePayload($subject, $note, $recipients, $send_recipient, $send_merchant);

        $this->verb = 'post';

        return $this->doPaypalRequest(false);
    }

    /**
     * Send reminder for an existing invoice.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/invoicing/v2/#invoices_remind
     */
    public function sendInvoiceReminder(string $invoice_id, string $subject = '', string $note = '', bool $send_recipient = true, bool $send_merchant = false, array $recipients = [])
    {
        $this->apiEndPoint = "v2/invoicing/invoices/{$invoice_id}/remind";

        $this->options['json'] = $this->getInvoiceMessagePayload($subject, $note, $recipients, $send_recipient, $send_merchant);

        $this->verb = 'post';

        return $this->doPaypalRequest(false);
    }

    /**
     * Cancel an existing invoice which is already sent.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/invoicing/v2/#invoices_cancel
     */
    public function cancelInvoice(string $invoice_id, string $subject = '', string $note = '', bool $send_recipient = true, bool $send_merchant = false, array $recipients = [])
    {
        $this->apiEndPoint = "v2/invoicing/invoices/{$invoice_id}/cancel";

        $this->options['json'] = $this->getInvoiceMessagePayload($subject, $note, $recipients, $send_recipient, $send_merchant);

        $this->verb = 'post';

        return $this->doPaypalRequest(false);
    }

    /**
     * Register payment against an existing invoice.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/invoicing/v2/#invoices_payments
     */
    public function registerPaymentInvoice(string $invoice_id, string $payment_date, string $payment_method, float $amount, string $payment_note = '', string $payment_id = '')
    {
        $this->apiEndPoint = "v2/invoicing/invoices/{$invoice_id}/payments";

        $data = [
            'payment_id' => $payment_id,
            'payment_date' => $payment_date,
            'method' => $payment_method,
            'note' => $payment_note,
            'amount' => [
                'currency_code' => $this->currency,
                'value' => $amount,
            ],
        ];

        $this->options['json'] = $data;

        $this->verb = 'post';

        return $this->doPaypalRequest();
    }

    /**
     * Delete payment against an existing invoice.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/invoicing/v2/#invoices_payments-delete
     */
    public function deleteExternalPaymentInvoice(string $invoice_id, string $transaction_id)
    {
        $this->apiEndPoint = "v2/invoicing/invoices/{$invoice_id}/payments/{$transaction_id}";

        $this->verb = 'delete';

        return $this->doPaypalRequest(false);
    }

    /**
     * Register payment against an existing invoice.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/invoicing/v2/#invoices_refunds
     */
    public function refundInvoice(string $invoice_id, string $payment_date, string $payment_method, float $amount)
    {
        $this->apiEndPoint = "v2/invoicing/invoices/{$invoice_id}/refunds";

        $data = [
            'refund_date' => $payment_date,
            'method' => $payment_method,
            'amount' => [
                'currency_code' => $this->currency,
                'value' => $amount,
            ],
        ];

        $this->options['json'] = $data;

        $this->verb = 'post';

        return $this->doPaypalRequest();
    }

    /**
     * Delete refund against an existing invoice.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/invoicing/v2/#invoices_refunds-delete
     */
    public function deleteRefundInvoice(string $invoice_id, string $transaction_id)
    {
        $this->apiEndPoint = "v2/invoicing/invoices/{$invoice_id}/refunds/{$transaction_id}";

        $this->verb = 'delete';

        return $this->doPaypalRequest(false);
    }

    /**
     * Generate QR code against an existing invoice.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/invoicing/v2/#invoices_generate-qr-code
     */
    public function generateQRCodeInvoice(string $invoice_id, int $width = 100, int $height = 100)
    {
        $this->apiEndPoint = "v2/invoicing/invoices/{$invoice_id}/generate-qr-code";

        $this->options['json'] = [
            'width' => $width,
            'height' => $height,
        ];
        $this->verb = 'post';

        return $this->doPaypalRequest();
    }

    /**
     * Generate the next invoice number.
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/invoicing/v2/#invoices_generate-next-invoice-number
     */
    public function generateInvoiceNumber()
    {
        $this->apiEndPoint = 'v2/invoicing/generate-next-invoice-number';

        $this->verb = 'post';

        return $this->doPaypalRequest();
    }

    /**
     * Show details for an existing invoice.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/invoicing/v2/#invoices_get
     */
    public function showInvoiceDetails(string $invoice_id)
    {
        $this->apiEndPoint = "v2/invoicing/invoices/{$invoice_id}";

        $this->verb = 'get';

        return $this->doPaypalRequest();
    }

    /**
     * Update an existing invoice.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/invoicing/v2/#invoices_update
     */
    public function updateInvoice(string $invoice_id, array $data)
    {
        $this->apiEndPoint = "v2/invoicing/invoices/{$invoice_id}";

        $this->options['json'] = $data;

        $this->verb = 'put';

        return $this->doPaypalRequest();
    }

    /**
     * Delete an invoice.
     *
     *
     *
     * @return array|\Psr\Http\Message\StreamInterface|string
     *
     * @throws \Throwable
     *
     * @see https://developer.paypal.com/docs/api/invoicing/v2/#invoices_list
     */
    public function deleteInvoice(string $invoice_id)
    {
        $this->apiEndPoint = "v2/invoicing/invoices/{$invoice_id}";

        $this->verb = 'delete';

        return $this->doPaypalRequest(false);
    }

    /**
     * Get Invoice Message Payload.
     */
    protected function getInvoiceMessagePayload(string $subject, string $note, array $recipients, bool $send_recipient, bool $send_merchant): array
    {
        $data = [
            'subject' => ! empty($subject) ? $subject : '',
            'note' => ! empty($note) ? $note : '',
            'additional_recipients' => (collect($recipients)->count() > 0) ? $recipients : '',
            'send_to_recipient' => $send_recipient,
            'send_to_invoicer' => $send_merchant,
        ];

        return collect($data)->filter()->toArray();
    }
}
