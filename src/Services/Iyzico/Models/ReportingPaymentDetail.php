<?php

namespace Unusualify\Payable\Services\Iyzico\Models;

use Unusualify\Payable\Services\Iyzico\Model\Mapper\ReportingPaymentDetailMapper;
use Unusualify\Payable\Services\Iyzico\Options;
use Unusualify\Payable\Services\Iyzico\Requests\ReportingPaymentDetailRequest;

class ReportingPaymentDetail extends ReportingPaymentDetailResource
{
    public static function create(ReportingPaymentDetailRequest $request, Options $options)
    {
        $uri = $options->getBaseUrl().'/v2/reporting/payment/details'.RequestStringBuilder::requestToStringQuery($request, 'reporting');
        $rawResult = parent::httpClient()->getV2($uri, parent::getHttpHeadersV2($uri, null, $options));

        return ReportingPaymentDetailMapper::create($rawResult)->jsonDecode()->mapReportingPaymentDetail(new ReportingPaymentDetail);
    }
}
