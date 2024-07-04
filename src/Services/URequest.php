<?php

namespace Unusualify\Payable\Services;

use GuzzleHttp\Client;

abstract class URequest implements URequestInterface 
{

    /**
     * Invoice Operation Mode
     * @var String
     */
    public $mode = 'sandbox';
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
    protected $url;

    /**
     * API Key for service
     * @var String
     */
    protected $apiKey;  

    /**
     * API Secret Hash for service
     * @var String
     */
    protected $apiSecret;

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
        $headers = null,

        ) {
            $this->setHeaders($headers);
            $this->client = new Client();
    }

    public function setHeaders($headers)
    {
        $this->headers = $headers;
        foreach($this->headers as $key => $value){
            if(str_contains(strtolower($key), 'authorization')){
                // dd($this->apiKey);
                $this->headers[$key] = $this->headers[$key] . ' ' .$this->apiKey;
            }
        }
    }

    function postReq($url, $endPoint, $postFields, $headers, $type, $mode = null)
    {
        // dd($url.$endPoint);
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
                if ($mode == 'test') {
                    // dd(
                    //     json_encode($postFields),
                    //     $headers,
                    //     "{$url}{$endPoint}"
                    // );
                }
                // dd($postFields, "{$url}{$endPoint}", $this->headers);
                $res = $this->client->post("{$url}{$endPoint}", [
                    'headers' => $headers,
                    'json' => $postFields
                ]);
                // dd($postFields,$headers, "{$url}{$endPoint}" );
            }else if ($type == 'encoded') {
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
                if ($mode == 'test') {
                    // dd($postFields, $headers, "{$url}{$endPoint}");
                }
                // dd($headers);
                // dd("{$url}{$endPoint}");
                // dd($postFields, $headers);
                $res = $this->client->post("{$url}{$endPoint}", [
                    'headers' => $headers,
                    'body' => $postFields
                ]);
            } else if($type == 'multipart'){
                if (count($headers) < 1) {
                    $headers['Content-Type'] = "multipart/form-data";
                }
                // dd($headers);
                $res = $this->client->post("{$url}{$endPoint}",[
                    'multipart' => $postFields,
                    // 'headers' => $headers
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

    function getReq($url, $endPoint, $parameters = [], $headers = [])
    {
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

    function deleteReq($url, $endPoint, $parameters = [], $headers = [])
    {
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

    abstract function setMode($mode);
}
