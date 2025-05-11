<?php

namespace Unusualify\Payable\Services;

use Unusualify\Payable\Services\PaymentService;
use Unusualify\Payable\Services\Currency;
use Illuminate\Http\Request as HttpRequest;
use Unusualify\Payable\Models\Enums\PaymentStatus;

class GarantiPosService extends PaymentService
{

    public $version = "v0.01";

    protected $terminalID ; //Terminal id

    protected $merchantID;

    protected $provUserID; // Terminal prov user name

    protected $terminalUserID; // Terminal user id

    protected $provUserPassword; // Terminal prov user password

    protected $garantiPayProvUserID; // GarantiPay için prov username

    protected $garantiPayProvUserPassword; // GarantiPay için prov user password

    protected $paymentType;  // Payment type - for credit cards: "creditcard", for GarantiPay : "garantipay"

    protected $storeKey;

    public $timeOutPeriod = "180";

    public $paymentRefreshTime = "1"; // Amount of time that user will be waiting after payment

    public $addCampaignInstallment = "N";

    public $totalInstallamentCount = "0";

    public $installmentOnlyForCommercialCard = "N";

    public $params = [];

    public $debugPaymentUrl = "https://eticaret.garanti.com.tr/destek/postback.aspx";


    /**
     * Has Refund
     *
     * @var bool
     */
    public static $hasRefund = false;

    /**
     * Has Cancel
     *
     * @var bool
     */
    public static $hasCancel = false;


    // GarantiPay tanımlamalar
    public $garantiPay = "Y"; // Usage of GarantiPay: Y/N
    public $bnsUseFlag = "Y"; // Usage of Bonu : Y/N
    public $fbbUseFlag = "Y"; // Usage of Fbb: Y/N
    public $chequeUseflag = "N"; // Usage of cheque: Y/N
    public $mileUseflag = "N"; // Usage of Mile: Y/N


    //Translate the status messages
    public $mdStatuses = array(
        0 => "Doğrulama başarısız, 3-D Secure imzası geçersiz",
        1 => "Tam doğrulama",
        2 => "Kart sahibi banka veya kart 3D-Secure üyesi değil",
        3 => "Kartın bankası 3D-Secure üyesi değil",
        4 => "Kart sahibi banka sisteme daha sonra kayıt olmayı seçmiş",
        5 => "Doğrulama yapılamıyor",
        7 => "Sistem hatası",
        8 => "Bilinmeyen kart numarası",
        9 => "Üye işyeri 3D-Secure üyesi değil",
    );


    public function __construct($mode = null)
    {
        parent::__construct();

        $this->setCredentials();
    }

    /*
    * Set proper .env variables to proper attributes
    */
    public function setCredentials()
    {
        // $this->setConfig();

        $tempConfig = $this->config[$this->mode];

        $this->url = $tempConfig['url'];
        $this->merchantID = $tempConfig['merchant_id'];
        $this->terminalID = $tempConfig['terminal_id'];
        $this->terminalUserID = $tempConfig['terminal_userid'];
        $this->provUserID = $tempConfig['provision_userid'];
        $this->provUserPassword = $tempConfig['provision_pw'];
        $this->garantiPayProvUserID = $tempConfig['pay_provision_userid'];
        $this->garantiPayProvUserPassword = $tempConfig['pay_provision_pw'];
        $this->storeKey = $tempConfig['secure_key'];
        $this->paymentType = $this->config['payment_type'];

        $this->params = [
            "refreshtime" => $this->paymentRefreshTime,
            "secure3dsecuritylevel" => "3D_PAY",
            "txntype" => "sales",
            "apiversion" => $tempConfig['api_version'],
            "mode" => $this->mode,
            "terminalprovuserid" => $this->provUserID,
            "terminaluserid" => $this->terminalUserID,
            "terminalid" => $this->terminalID,
            "terminalmerchantid" => $this->merchantID,
        ];
    }

    public function pay(array $params)
    {
        $endpoint = 'servlet/gt3dengine';
        $this->params['txntimestamp'] = time();
        $this->params += $params;

        $this->params['successurl'] = $this->getRedirectUrl(['success' => 'true']);
        $this->params['errorurl'] = $this->getRedirectUrl(['success' => 'false']);

        $this->params['secure3dhash'] = $this->GenerateHashData($this->payment->id);
        // dd($this->params['secure3dhash'], $this);
        $this->headers['Content-Type'] ='application/x-www-form-urlencoded';

        $resp = $this->postReq(
            $this->url,
            $endpoint,
            $this->hydrateParams($this->params),
            $this->headers,
            'encoded',
        );

        return print_r($resp);
    }
    public function pay_(array $params)
    {
        // Store original parameters for later use
        $this->params['txntimestamp'] = time();
        $this->params += $params;

        // Set success and error URLs
        $successUrl = $this->getRedirectUrl(['success' => 'true']);
        $errorUrl = $this->getRedirectUrl(['success' => 'false']);

        // Get the required values
        // $orderId = $this->payment->id;
        $orderId = $params['order_id'];
        $amount = $this->formatPrice($params['amount']);
        $currencyCode = Currency::getNumericCode($params['currency']);
        $cardNumber = $params['card_no'];
        $cardExpMonth = $params['card_month'];
        $cardExpYear = $this->formatCardYear($params['card_year']);
        $cardCvv = $params['card_cvv'];
        $cardHolderName = $params['card_name'];

        dd($this->GenerateHashData($this->payment->id), $this);

        // Create the XML request structure
        $requestData = [
            'Mode' => $this->mode === 'live' ? 'PROD' : 'TEST',
            'Version' => $this->config[$this->mode]['api_version'],
            'Terminal' => [
                'ProvUserID' => $this->provUserID,
                // 'UserID' => $this->terminalUserID,
                'UserID' => $this->provUserID,
                'HashData' => $this->GenerateHashData($this->payment->id),
                // 'HashData' => $this->GenerateHashData($orderId, $cardNumber, $amount, $currencyCode),
                'ID' => $this->terminalID,
                'MerchantID' => $this->merchantID,
            ],
            'Customer' => [
                'IPAddress' => $params['user_ip'],
                'EmailAddress' => $params['user_email'],
            ],
            'Card' => [
                'Number' => $cardNumber,
                'ExpireDate' => $cardExpMonth . $cardExpYear,
                'CVV2' => $cardCvv,
            ],
            'Order' => [
                'OrderID' => $params['order_id'],
                'GroupID' => '',
                'Description' => $params['description'] ?? '',
            ],
            'Transaction' => [
                'Type' => 'sales',
                'InstallmentCnt' => $params['installment_count'] ?? '',
                'Amount' => $amount,
                'CurrencyCode' => $currencyCode,
                'CardholderPresentCode' => '0', // E-commerce transaction
                'MotoInd' => 'N',
                'Secure3D' => [
                    'AuthenticationCode' => '',
                    'SecurityLevel' => '3D_PAY',
                    'TxnID' => '',
                    'Md' => '',
                    'SuccessUrl' => $successUrl,
                    'ErrorUrl' => $errorUrl,
                ],
            ],
        ];

        // Convert to XML
        $xmlRequest = $this->arrayToXml($requestData, '<GVPSRequest/>');

        // Set headers for XML
        $this->headers['Content-Type'] = 'application/xml';

        // Make the request
        $endpoint = 'VPServlet';
        $resp = $this->postReq(
            $this->url,
            $endpoint,
            $xmlRequest,
            $this->headers,
            'xml',
        );

        dd($requestData, simplexml_load_string($resp));

        return $this->processResponse($resp);
    }

    /**
     * Cancel a payment transaction
     * Can only be used on the same day as the original transaction
     *
     * @param array|object $params Parameters including order_id and hostrefnum/transid
     * @return mixed Response from payment gateway
     */
    public function cancel(array|object $params)
    {
        $params = (array) $params;
        // Check required parameters
        if (!isset($params['order_id']) || (!isset($params['transid']) && !isset($params['hostrefnum']))) {
            return ['error' => 'Missing required parameters: order_id or transaction reference number'];
        }

        // Set provider user ID for cancellation operations
        $originalProvUserID = $this->provUserID;
        $this->provUserID = "PROVRFN";

        $endpoint = 'VPServlet';
        $orderId = $params['order_id'];
        $amount = $this->formatPrice($params['amount']);
        $currencyCode = Currency::getNumericCode($params['currency'] ?? 'TRY');
        $retRefNum = $params['transid'] ?? $params['hostrefnum'];

        // Create the XML request
        $requestData = [
            'Mode' => $this->mode === 'live' ? 'PROD' : 'TEST',
            'Version' => '512',
            'Terminal' => [
                'ProvUserID' => $this->provUserID,
                'UserID' => $this->terminalUserID,
                'HashData' => $this->GenerateHashData($orderId, null, $amount, $currencyCode),
                'ID' => $this->terminalID,
                'MerchantID' => $this->merchantID,
            ],
            'Customer' => [
                'IPAddress' => $params['customeripaddress'] ?? $params['ip_address'] ?? $_SERVER['REMOTE_ADDR'],
                'EmailAddress' => $params['customeremailaddress'] ?? $params['user_email'] ?? '',
            ],
            'Order' => [
                'OrderID' => $orderId,
                'GroupID' => '',
            ],
            'Transaction' => [
                'Type' => 'void',
                'InstallmentCnt' => '',
                'Amount' => $amount,
                'CurrencyCode' => $currencyCode,
                'CardholderPresentCode' => '0',
                'MotoInd' => 'N',
                'OriginalRetrefNum' => $retRefNum,
            ],
        ];

        // Convert to XML
        $xmlRequest = $this->arrayToXml($requestData, '<GVPSRequest/>');

        // Set headers
        $this->headers['Content-Type'] = 'application/xml';

        // Make request
        $resp = $this->postReq(
            $this->url,
            $endpoint,
            $xmlRequest,
            $this->headers,
            'xml',
        );

        dd($resp);

        // Reset provUserID to original value
        $this->provUserID = $originalProvUserID;

        return $this->processResponse($resp);
    }

    /**
     * Refund a payment transaction
     * Used for refunding transactions from previous days
     *
     * @param array|object $params Parameters including order_id and amount
     * @return mixed Response from payment gateway
     */
    public function refund(array|object $params)
    {
        $params = (array) $params;
        // Check required parameters
        if (!isset($params['order_id']) || !isset($params['amount'])) {
            return ['error' => 'Missing required parameters: order_id or amount'];
        }

        // Set provider user ID for refund operations
        $originalProvUserID = $this->provUserID;
        $this->provUserID = "PROVRFN";

        $endpoint = 'VPServlet';
        $orderId = $params['order_id'];
        $amount = $this->formatPrice($params['amount']);
        $currencyCode = Currency::getNumericCode($params['currency'] ?? 'TRY');

        // Create the XML request
        $requestData = [
            'Mode' => $this->mode === 'live' ? 'PROD' : 'TEST',
            'Version' => '512',
            'Terminal' => [
                'ProvUserID' => $this->provUserID,
                'UserID' => $this->terminalUserID,
                'HashData' => $this->GenerateHashData($orderId, null, $amount, $currencyCode),
                'ID' => $this->terminalID,
                'MerchantID' => $this->merchantID,
            ],
            'Customer' => [
                'IPAddress' => $params['customeripaddress'] ?? $params['ip_address'] ?? $_SERVER['REMOTE_ADDR'],
                'EmailAddress' => $params['customeremailaddress'] ?? $params['user_email'] ?? '',
            ],
            'Order' => [
                'OrderID' => $orderId,
                'GroupID' => '',
            ],
            'Transaction' => [
                'Type' => 'refund',
                'InstallmentCnt' => '',
                'Amount' => $amount,
                'CurrencyCode' => $currencyCode,
                'CardholderPresentCode' => '0',
                'MotoInd' => 'N',
            ],
        ];

        // Convert to XML
        $xmlRequest = $this->arrayToXml($requestData, '<GVPSRequest/>');

        // Set headers
        $this->headers['Content-Type'] = 'application/xml';

        // Make request
        $resp = $this->postReq(
            $this->url,
            $endpoint,
            $xmlRequest,
            $this->headers,
            'xml',
        );

        // Reset provUserID to original value
        $this->provUserID = $originalProvUserID;

        return $this->processResponse($resp);
    }

    /**
     * Process the response from the gateway
     *
     * @param mixed $response Raw response from gateway
     * @return array Processed response
     */
    private function processResponse($response)
    {
        // Try to parse as XML
        try {
            $xml = simplexml_load_string($response);
            if ($xml) {
                dd($xml);
                return $this->parseXmlResponse($xml);
            }
        } catch (\Exception $e) {
            // Not XML or parsing error
        }

        // Try to parse as JSON
        $json = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        // Return raw response if unable to parse
        return [
            'raw_response' => $response,
            'status' => 'unknown',
            'message' => 'Unable to parse response'
        ];
    }

    /**
     * Parse XML response from Garanti
     *
     * @param \SimpleXMLElement $xml Response as XML
     * @return array Parsed response
     */
    private function parseXmlResponse($xml)
    {
        $result = [];

        // Extract basic response data
        if (isset($xml->Transaction->Response)) {
            $result['code'] = (string)$xml->Transaction->Response->Code;
            $result['message'] = (string)$xml->Transaction->Response->Message;
            $result['status'] = ((string)$xml->Transaction->Response->Code === 'Approved') ? 'success' : 'error';
            $result['reason_code'] = (string)$xml->Transaction->Response->ReasonCode;
        }

        // Extract transaction details if available
        if (isset($xml->Transaction->RetrefNum)) {
            $result['ref_num'] = (string)$xml->Transaction->RetrefNum;
        }

        // Extract order ID if available
        if (isset($xml->Order->OrderID)) {
            $result['order_id'] = (string)$xml->Order->OrderID;
        }

        return $result;
    }

    private function GenerateSecurityData()
    {
        $password = $this->provUserPassword;
        $data = [
            $password,
            str_pad((int)$this->terminalID, 9, 0, STR_PAD_LEFT)
        ];
        $shaData =  sha1(implode('', $data));
        return strtoupper($shaData);
    }

    /**
     * Modified version of GenerateHashData to support cancel and refund operations
     *
     * @param string $orderId Order ID
     * @param string|null $cardNumber Card number (can be null for cancel/refund)
     * @param string $amount Transaction amount
     * @param string $currencyCode Currency code
     * @return string Hashed data
     */
    public function GenerateHashData($orderId, $cardNumber = null, $amount = null, $currencyCode = null)
    {
        // If called with parameters from cancel/refund
        if ($amount !== null && $currencyCode !== null) {
            $hashedPassword = $this->GenerateSecurityData();
            $data = [
                $orderId,
                $this->terminalID,
                $cardNumber,
                $amount,
                $currencyCode,
                $hashedPassword
            ];
            return strtoupper(hash("sha512", implode('', $data)));
        }

        // Original implementation for payment
        $orderId  = $this->params['order_id'];
        $terminalId = $this->terminalID;
        $amount = $this->formatPrice($this->params['amount']);
        $currencyCode = Currency::getNumericCode($this->params['currency']);
        $storeKey = $this->storeKey;
        $installmentCount = "";
        $successUrl = $this->getRedirectUrl(['success' => 'true']);
        $errorUrl = $this->getRedirectUrl(['success' => 'false']);
        $type = $this->params['txntype'];
        $hashedPassword = $this->GenerateSecurityData();

        return strtoupper(hash('sha512', $terminalId . $orderId . $amount . $currencyCode . $successUrl . $errorUrl . $type . $installmentCount . $storeKey . $hashedPassword));
    }

    public function hydrateParams(array $params)
    {
        if($params['mode'] == 'live')
            $params['mode'] = 'PROD';
        else
            $params['mode'] = 'TEST';

        $params['orderid'] = $params['order_id'];
        // $params['txnamount'] = $this->formatPrice($params['paid_price']);
        $params['txnamount'] = $this->formatPrice($params['amount']);
        $params['lang'] = $params['locale'];
        $params['txncurrencycode'] = Currency::getNumericCode($params['currency']);
        $params['customeremailaddress'] = $params['user_email'];
        $params['customeripaddress'] = $params['user_ip'];
        $params['txntimestamp'] = date('Y-m-d\TH:i:s\Z', time());
        $params['txninstallmentcount'] = "";
        $params['cardholdername'] = $params['card_name'];
        $params['cardnumber'] = $params['card_no'];
        $params['cardexpiredatemonth'] = $params['card_month'];
        $params['cardexpiredateyear'] = $this->formatCardYear($params['card_year']);
        $params['cardcvv2'] = $params['card_cvv'];
        $params['companyname'] = $params['company_name'];
        return $params;
    }

    public function handleResponse(HttpRequest $request)
    {
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

        $cleanedResponse = array_filter($request->all(), function($key) use ($paramsToRemoved) {
            return !in_array($key, $paramsToRemoved);
        }, ARRAY_FILTER_USE_KEY);

        $responseStatus = $request->mdstatus == 1 ? self::RESPONSE_STATUS_SUCCESS : self::RESPONSE_STATUS_ERROR;
        $recordStatus = $request->mdstatus == 1 ? PaymentStatus::COMPLETED : PaymentStatus::FAILED;
        $responseMessage = $this->mdStatuses[$request->mdstatus];

        $this->payment->update([
            'status' => $recordStatus,
            'response' => $cleanedResponse
        ]);

        $responsePayload = [
            'status' => $responseStatus,
            'id' => $request->query('payment_id'),
            'payment_service' => $request->query('payment_service'),
            'order_id' => $request->query('order_id'),
            'order_data' => $request->all(),
            'message' => $responseMessage
        ];

        return $this->generatePostForm($responsePayload, route(config('payable.return_url')));
    }

    public function formatPrice($price)
    {
        return $price;
        // return round($price, 2) * 100;
    }

    public function formatCardYear($year)
    {
        return substr($year, 2);
    }

    /**
     * Convert array to XML
     *
     * @param array $array Array to convert
     * @param string $rootElement Root element tag
     * @return string XML string
     */
    protected function arrayToXml($array, $rootElement = null)
    {
        $xml = new \SimpleXMLElement($rootElement !== null ? $rootElement : '<root/>');

        $this->arrayToXmlHelper($array, $xml);

        return $xml->asXML();
    }

    /**
     * Helper function for arrayToXml conversion
     */
    private function arrayToXmlHelper($array, &$xml)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item' . $key; // change numeric keys to item0, item1, etc.
                }
                $subnode = $xml->addChild($key);
                $this->arrayToXmlHelper($value, $subnode);
            } else {
                if (is_numeric($key)) {
                    $key = 'item' . $key;
                }
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }
}
