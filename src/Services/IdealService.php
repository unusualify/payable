<?php


namespace Unusualify\Payable\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redirect;

class IdealService extends BuckarooService
{
    /**
     * Has Refund
     *
     * @var bool
     */
    public static $hasRefund = true;

    /**
     * Has Cancel
     *
     * @var bool
     */
    public static $hasCancel = false;

    public function __construct($mode = null)
    {
        parent::__construct($mode, 'ideal');

    }

    /**
     * pay
     *
     * @param  mixed $params
     * @return void
     */
    public function pay(array $params)
    {
        $params = $this->hydrateParams($params);

        $response = $this->buckaroo->method($this->service)->pay($params);

        if($response->hasRedirect()){
            $redirectUrl = $response->getRedirectUrl();
            $transactionKey = $response->getTransactionKey();

            // dd(
            //     $redirectUrl,
            //     $transactionKey,
            //     $params,
            //     $this->payment->response,
            //     get_class_methods($response),
            //     $response,

            // );

            return Redirect::to($redirectUrl);

        }else if ($response->hasError()){
            // dd($response->getSomeError());
            return $response->getSomeError();
        }else{
            return 'Something went wrong please contact with administrator.';
        }
    }

    public function handleResponse(Request $request)
    {
        $responsePayload = Arr::except($request->all(), ['payment_id', 'payment_service']);

        $this->payment->update([
            'status' => $request->brq_statuscode == "190" ? $this->getStatusEnum()::COMPLETED : $this->getStatusEnum()::FAILED,
            'response' => $responsePayload,
        ]);

        $params = [
            'id' => $request->get('payment_id'),
            'status' => $request->brq_statuscode == '190' ? self::RESPONSE_STATUS_SUCCESS : self::RESPONSE_STATUS_ERROR,
            'payment_service' => $request->get('payment_service'),
            'order_id' => $request->brq_invoicenumber,
            'order_data' => json_encode($request->all()),
            'message' => $request->brq_statusmessage
        ];

        return $this->generatePostForm($params, $this->getPayableReturnUrl());
    }

    /**
     * Refund
     *
     * @param  array|object $params
     * @return void
     */
    public function refund(array|object $params)
    {
        $refundRequest = $this->validateRefundRequest($params);

        if(!$refundRequest['validated']){
            return $refundRequest;
        }

        $params = (array) $params;
        $payment = $refundRequest['payment'];

        $paymentResponse = $payment->response;

        $amount = (isset($params['amount']) && $params['amount'] > 0) ? $params['amount'] / 100 : $paymentResponse->brq_amount ?? (float) ($payment->amount / 100);
        $invoice = $params['order_id'] ?? $paymentResponse->brq_invoicenumber ?? $payment->order_id;
        $currency = $params['currency'] ?? $paymentResponse->brq_currency ?? $payment->currency;
        $transactionKey = $params['transaction_key'] ?? null;

        if(!$transactionKey){
            if (isset($paymentResponse->brq_transactions)) {
                $transactionKey = $paymentResponse->brq_transactions;
            } else {
                return array_merge($refundRequest, [
                    'message' => 'Transaction key not found in payment response'
                ]);
            }
        }

        $response = $this->buckaroo->method('ideal')->refund([
            'originalTransactionKey' => $transactionKey, //Set transaction key of the transaction to refund
            'invoice' => $invoice, //Set invoice number of the transaction to refund
            'amountCredit' => $amount,
            'currency' => $currency,
        ]);

        // dd(
        //     $transactionKey,
        //     $response->isFailed(),
        //     $response->isSuccess(),
        //     $response->hasSomeError(),
        //     $response->getSomeError(),
        //     $response->hasError(),
        //     $response->getFirstError(),
        //     $response->hasMessage(),
        //     $response->getMessage(),
        //     $response,
        //     get_class_methods($response)
        // );


        $refundResponseStatus = $this::RESPONSE_STATUS_ERROR;
        $message = 'Refund failed';

        if($response->isSuccess()){
            if($payment){
                $payment->update([
                    'status' => $this->getStatusEnum()::REFUNDED,
                    'response' => $response,
                ]);
            }

            $refundResponseStatus = $this::RESPONSE_STATUS_SUCCESS;
            $message = 'Refunded successfully';
        }

        return array_merge($refundRequest, [
            'status' => $refundResponseStatus,
            'order_data' => json_encode($response),
            'message' => $message
        ]);
    }
}



