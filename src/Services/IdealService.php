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
        // dd($request);

        $paramsToRemoved = [
            'card_name',
            'card_no',
            'card_year',
            'card_month',
            'card_cvv',
            'user_ip',
            'oid',
            'orderid',
            'terminaluserid',
            'txnamount',
            'terminalid',
        ];
        // dd($request->all());
        $resp = array_filter($request->all(), function($key) use ($paramsToRemoved) {
            return !in_array($key, $paramsToRemoved);
        }, ARRAY_FILTER_USE_KEY);

        $params = [
            'id' => $request->query('payment_id'),
            'service_payment_id' => $request->brq_payment,
            'order_id' => $request->brq_invoicenumber,
            'order_data' => $request->brq_ordernumber
        ];
        if($request->brq_statuscode == "190"){
            $params['status'] = 'success';
            //on success update record accordingly
            // dd($request->all());

            $custom_fields = $this->updateRecord(
                $params['id'],
                'COMPLETED',
                json_encode($request->all()),
            );
            // dd($custom_fields);

            $params['custom_fields'] = $custom_fields;

            return $this->generatePostForm($params, route(config('payable.return_url')));

        }else{
            $params['status'] = 'error';
            // dd($resp);
            $custom_fields = $this->updateRecord(
                $params['id'],
                'FAILED',
                json_encode($request->all()),
            );
            // dd($custom_fields);
            $params = [
                'status' => 'fail',
                'id' => $request->query('payment_id'),
                'service_payment_id' => $request->paymentId,
                'order_id' => $request->order_id,
                'order_data' => $request->all(),
                'custom_fields' => $custom_fields,
            ];

            return $this->generatePostForm($params, route(config('payable.return_url')));

        }
    }
}



