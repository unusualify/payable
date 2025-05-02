<?php


namespace Unusualify\Payable\Services;

use Illuminate\Http\Request as HttpRequest;
use Unusualify\Payable\Models\Payment as ModelsPayment;


class IdealService extends BuckarooService{

    public function __construct($mode = null)
    {
        $this->service = 'ideal';

        parent::__construct(
            $this->service
        );

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
            $params['status'] = 'success';
          
            $this->updateRecord(
                $params['id'],
                self::STATUS_COMPLETED,
                json_encode($request->all()),
            );

            return $this->generatePostForm($params, route(config('payable.return_url')));

        }else{
            $params['status'] = 'error';

            $this->updateRecord(
                $params['id'],
                self::STATUS_FAILED,
                json_encode($request->all()),
            );


            return $this->generatePostForm($params, route(config('payable.return_url')));

        }
    }
}



