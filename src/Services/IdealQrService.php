<?php


namespace Unusualify\Payable\Services;

use Illuminate\Http\Request as HttpRequest;
use Unusualify\Payable\Models\Payment as ModelsPayment;
use Illuminate\Support\Facades\Redirect;


class IdealQrService extends BuckarooService{

    
    public function __construct($mode = null)
    {
        $this->service = 'ideal-qr';

        parent::__construct(
            $this->service
        );

    }

     /**
     * pay
     *
     * @param  mixed $params
     * @return void
     */
    public function pay(array $params)
    {
        $payment = $this->createRecord(
            $this->hydrateRecordParams($params)
        );
       
        
        $params['returnURL'] = route('payable.response').'?payment_service='. $this->service . '&payment_id=' . $payment->id;
        $response = $this->buckaroo->method('ideal_qr')->generate([
            'description' => 'Test purchase',
            'returnURL' => $params['returnURL'],
            'minAmount' =>  $params['paid_price'],
            'maxAmount' =>  $params['paid_price'],
            'imageSize' => '600',
            'purchaseId' => $params['order_id'],
            'isOneOff' => true,
            'amount' => $params['paid_price'],
            'amountIsChangeable' => false,
            'expiration' => '2030-09-30',
            'isProcessing' => false,
        ]);

        if($response->isSuccess()){
            $servicesParams = $response->getServiceParameters();
            $qrimageurl = $servicesParams['qrimageurl'];
            return $qrimageurl;
        }else if ($response->hasError()){
            return $response->getSomeError();
        }else{
            return 'Something went wrong please contact with administrator.';
        }
    }



    public function handleResponse(HttpRequest $request)
    {
    
        $params = [
            'id' => $request->query('payment_id'),
            'payment_service' => $request->query('payment_service'),
            'order_id' => $request->brq_invoicenumber,
            'order_data' => json_encode($request->all()),
            'message' => $request->brq_statusmessage
        ];


        if($request->brq_statuscode == "190"){
            $params['status'] = $this::RESPONSE_STATUS_SUCCESS;
          
            $this->updateRecord(
                $params['id'],
                self::STATUS_COMPLETED,
                json_encode($request->all()),
            );

            return $this->generatePostForm($params, route(config('payable.return_url')));

        }else{
            $params['status'] = $this::RESPONSE_STATUS_ERROR;

            $this->updateRecord(
                $params['id'],
                self::STATUS_FAILED,
                json_encode($request->all()),
            );


            return $this->generatePostForm($params, route(config('payable.return_url')));

        }
    }

    public function refund(array $params)
    {
        if(empty($params['payment_id'])){
            return [
                'status' => $this::RESPONSE_STATUS_ERROR,
                'id' => $params['payment_id'],
                'payment_service' => $this->service,
                'message' => 'Payment id is required'
            ];
        }
        $payment = ModelsPayment::find($params['payment_id']);
        if(empty($payment)){
            return [
                'status' => $this::RESPONSE_STATUS_ERROR,
                'id' => $params['payment_id'],
                'payment_service' => $this->service,
                'message' => 'Payment not found'
            ];
        }

        if($payment->status != 'COMPLETED'){
            return [
                'status' => $this::RESPONSE_STATUS_ERROR,
                'id' => $params['payment_id'],
                'payment_service' => $this->service,
                'message' => 'Payment is not completed'
            ];
        }

        if(empty($params['transaction_key']) || empty($params['order_id']) || empty($params['paid_price'])){
            $payment_response = json_decode($payment->response);
            if (isset($payment_response->brq_transactions)) {
                $params['transaction_key'] = $payment_response->brq_transactions;
            } else {
                return [
                    'status' => $this::RESPONSE_STATUS_ERROR,
                    'id' => $params['payment_id'],
                    'payment_service' => $this->service,
                    'message' => 'Transaction key not found in payment response'
                ];
            }
            $params['order_id'] = $payment->order_id;
            $params['paid_price'] = $payment->amount;
        }

        $response = $this->buckaroo->method('ideal')->refund([
            'invoice' => $params['order_id'], //Set invoice number of the transaction to refund
            'originalTransactionKey' => $params['transaction_key'], //Set transaction key of the transaction to refund
            'amountCredit' => $params['paid_price'],
        ]);
        if($response->isSuccess()){
            $this->updateRecord(
                $params['payment_id'],
                self::STATUS_REFUNDED,
                $response
            );
            return [
                'status' => $this::RESPONSE_STATUS_SUCCESS,
                'id' => $params['payment_id'],
                'payment_service' => $this->service,
                'order_data' => json_encode($response),
                'message' => $response->getMessage()
            ];
        }else{
            return [
                'status' => $this::RESPONSE_STATUS_ERROR,
                'id' => $params['payment_id'],
                'payment_service' => $this->service,
                'order_data' => $response->data(),
                'message' => $response->getSomeError()
            ];
        }
    }
}



