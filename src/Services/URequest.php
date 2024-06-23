<?php

namespace Unusualify\Payable\Services;

use GuzzleHttp\Client;

abstract class URequest implements URequestInterface 
{

    /**
     * Invoice Operation Mode
     * @var String
     */
    public $mode = 'test';
    /** 
     * Test TOKEN URL
     * @var String
     */
    protected $testTokenUrl;
    /** 
     * Prod TOKEN URL
     * @var String
     */
    protected $prodTokenUrl;
    /**
     * Test API URL
     * @var String
     */
    protected $testUrl;

    /**
     * Production API URL
     * @var String
     */
    protected $prodUrl;

    protected $url;

    /**
     * API Key for service
     * @var String
     */
    protected $apiKey;

    /**
     * API Test Key for service
     * @var String
     */
    protected $apiTestKey;

    /**
     * API Production Key for service
     * @var String
     */
    protected $apiProdKey;    


    /**
     * API Secret Hash for service
     * @var String
     */
    protected $apiSecret;

    /**
     * API Test Secret Hash for service
     * @var String
     */
    protected $apiTestSecret;

    /**
     * API Prod Secret Hash for service
     * @var String
     */
    protected $apiProdSecret;

    protected $returnQueries = [
        'success' => '?success=true',
        'error' => '?error=true'
    ];

    /**
     * API Token Created
     * @var String
     */
    protected $token;

    protected $client;

    protected $headers;
    
    public function __construct(
        $mode = 'sandbox',
        $testTokenUrl = null,
        $prodTokenUrl = null,
        $testUrl = null,
        $prodUrl = null,
        $apiProdKey = null,
        $apiTestKey = null,
        $apiProdSecret = null,
        $apiTestSecret = null,
        $root_path = null,
        $token_path = null,
        $path = null,
        $token_refresh_time = null,
        $headers = null,
        $envVar = null,
        $redirect_url = null
        ) {
            $this->setMode($mode);
            $this->setHeaders($headers);
            $this->client = new Client();
    }

    public function setMode($mode= 'sandbox')
    {
        if($mode == 'production'){
            $this->setProd();
        }else{
            $this->setTest();
        }
    }

    public function setHeaders($headers){
        $this->headers = $headers;
        foreach($this->headers as $key => $value){
            if(str_contains(strtolower($key), 'authorization')){
                // dd($this->apiKey);
                $this->headers[$key] = $this->headers[$key] . ' ' .$this->apiKey;
            }
        }
    }

    public function setTest()
    {
        $this->test();
    }

    private function test()
    {
        $this->mode = 'sandbox';
        $this->url = $this->testUrl;
        $this->apiKey = $this->apiTestKey;
        $this->apiSecret = $this->apiTestSecret;

    }

    public function setProd()
    {
        $this->prod();
    }
    
    private function prod()
    {
        $this->mode = 'production';
        $this->url = $this->prodUrl;
        $this->apiKey = $this->apiProdKey;
        $this->apiSecret = $this->apiProdSecret;
    }

    function postReq($url, $endPoint, $postFields, $headers, $type)
    {
        try{
            if ($type == 'json') {
                // dd($this->client);
                if (count($headers) < 1) {
                    $headers = [
                        "Content-Type" => "application/json",
                        "Accept"=> "*/*",
                    ];
                }
                $headers["Accept"] = "*/*";
                // dd($postFields, "{$url}{$endPoint}", $this->headers);
                // dd($headers);
                // dd($this, $this->client);
                // dd($url,$endPoint);
                // dd($postFields);
                // dd($headers);
                // dd(json_encode($postFields, JSON_UNESCAPED_SLASHES));
                $res = $this->client->post("{$url}{$endPoint}", [
                    'headers' => $headers,
                    'json' => $postFields
                ]);
            } else if ($type == 'encoded') {
                // dd($postFields);  
                if (count($headers) < 1) {
                    $headers['Content-Type'] = "application/x-www-form-urlencoded";
                }
                // dd($url.$endPoint, $postFields, $headers);
                $res = $this->client->post("{$url}{$endPoint}", [
                    'headers' => $headers,
                    'form_params' => $postFields
                ]);

            } else if ($type == 'raw'){
                if (count($headers) < 1) {
                    $headers['Content-Type'] = "text/plain";
                }
                // dd("{$url}{$endPoint}");
                // dd($postFields, $headers);
                $res = $this->client->post("{$url}{$endPoint}", [
                    'headers' => $headers,
                    'body' => $postFields
                ]);
            }
            else {
                
                $res = $this->client->post("{$url}/{$endPoint}", [
                    'headers' => $headers,
                    'body' => json_encode($postFields)
                ]);
            }
            // return $res;
            // dd($res->getBody()->getContents());
            $safeData = $res->getBody()->getContents();
            // dd($safeData);
            if(json_decode($res->getBody()->getContents()) != null){
                return $res->getBody()->getContents();
            }
            // dd($safeData);
            return $safeData;
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    function getReq($url, $endPoint, $parameters = [], $headers = []){
        try{
            dd($url, $headers);
            $res = $this->client->get(
                "{$url}/{$endPoint}",
                [
                    'query' => $parameters,
                    'headers' => $headers
                ]
            );
            // dd($res);
            return json_decode($res->getBody()->getContents());
            
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    function deleteReq($url, $endPoint, $parameters = [], $headers = []){
        try {
            $res = $this->client->delete(
                "{$url}/{$endPoint}",
                [
                    'query' => $parameters,
                    'headers' => $headers
                ]
            );
            return json_decode($res->getBody()->getContents());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    abstract function setConfig();

    abstract function getConfigName();
}
