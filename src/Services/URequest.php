<?php

namespace Unusualify\Payable\Services;

use GuzzleHttp\Client;

abstract class URequest implements URequestInterface
{
    /**
     * Invoice Operation Mode
     *
     * @var string
     */
    public $mode = 'sandbox';

    /**
     * Test TOKEN URL
     *
     * @var string
     */
    protected $testTokenUrl;

    /**
     * Prod TOKEN URL
     *
     * @var string
     */
    protected $prodTokenUrl;

    /**
     * Test API URL
     *
     * @var string
     */
    protected $url;

    /**
     * API Key for service
     *
     * @var string
     */
    protected $apiKey;

    /**
     * API Secret Hash for service
     *
     * @var string
     */
    protected $apiSecret;

    protected $returnQueries = [
        'success' => '?success=true',
        'error' => '?error=true',
    ];

    /**
     * API Token Created
     *
     * @var string
     */
    protected $token;

    protected $client;

    protected $headers;

    public function __construct(
        $mode = 'sandbox',
        $headers = null,

    ) {
        $this->setHeaders($headers);
        $this->client = new Client;
    }

    public function setHeaders($headers)
    {
        $this->headers = $headers;
        foreach ($this->headers as $key => $value) {
            if (str_contains(strtolower($key), 'authorization')) {
                // dd($this->apiKey);
                $this->headers[$key] = $this->headers[$key].' '.$this->apiKey;
            }
        }
    }

    public function postReq($url, $endPoint, $postFields, $headers, $type, $mode = null)
    {
        // dd($url.$endPoint);
        // dd($postFields);
        try {
            if ($type == 'json') {
                // dd($this->client);

                if (count($headers) < 1) {
                    $headers = [
                        'Content-Type' => 'application/json',
                        'Accept' => '*/*',
                    ];
                }
                $headers['Accept'] = '*/*';
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
                    'json' => $postFields,
                ]);
                // dd($postFields,$headers, "{$url}{$endPoint}" );
            } elseif ($type == 'encoded') {
                // dd($postFields);
                if (count($headers) < 1) {
                    $headers['Content-Type'] = 'application/x-www-form-urlencoded';
                }
                // dd($postFields);
                // dd($url.$endPoint, $postFields, $headers);
                $res = $this->client->post("{$url}{$endPoint}", [
                    'headers' => $headers,
                    'form_params' => $postFields,
                ]);
            } elseif ($type == 'raw') {
                if (count($headers) < 1) {
                    $headers['Content-Type'] = 'text/plain';
                }
                if ($mode == 'test') {
                    // dd($postFields, $headers, "{$url}{$endPoint}");
                }
                // dd($headers);
                // dd("{$url}{$endPoint}");
                // dd($postFields, $headers);
                $res = $this->client->post("{$url}{$endPoint}", [
                    'headers' => $headers,
                    'body' => $postFields,
                ]);
            } elseif ($type == 'xml') {
                if (count($headers) < 1) {
                    $headers['Content-Type'] = 'application/xml';
                }
                $res = $this->client->post("{$url}{$endPoint}", [
                    'headers' => $headers,
                    'body' => $postFields,
                ]);
            } elseif ($type == 'multipart') {
                if (count($headers) < 1) {
                    $headers['Content-Type'] = 'multipart/form-data';
                }
                // dd($headers);
                $res = $this->client->post("{$url}{$endPoint}", [
                    'multipart' => $postFields,
                    // 'headers' => $headers
                ]);
            } else {
                $res = $this->client->post("{$url}/{$endPoint}", [
                    'headers' => $headers,
                    'body' => json_encode($postFields),
                ]);
            }
            // return $res;
            // dd($res->getBody()->getContents());
            $safeData = $res->getBody()->getContents();
            // dd($safeData);
            if (json_decode($res->getBody()->getContents()) != null) {
                return $res->getBody()->getContents();
            }

            // dd($safeData);
            return $safeData;
        } catch (\Exception $e) {
            if($e instanceof \GuzzleHttp\Exception\ClientException) {
                if($e->getResponse()->getStatusCode() == 422) {
                    return [
                        'status' => 'error',
                        'code' => $e->getResponse()->getStatusCode(),
                        'message' => $e->getResponse()->getBody()->getContents(),
                    ];
                } else if($e->getResponse()->getStatusCode() == 400) {
                    return [
                        'status' => 'error',
                        'code' => $e->getResponse()->getStatusCode(),
                        'message' => $e->getResponse()->getBody()->getContents(),
                    ];
                } else if($e->getResponse()->getStatusCode() == 404) {
                    return [
                        'status' => 'error',
                        'code' => $e->getResponse()->getStatusCode(),
                        'message' => $e->getResponse()->getBody()->getContents(),
                    ];
                }
            }
            return [
                'status' => 'error',
                'code' => $e->getResponse()->getStatusCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getReq($url, $endPoint, $parameters = [], $headers = [])
    {
        $headers['Accept'] = '*/*';
        try {
            // dd("{$url}{$endPoint}", $headers, $parameters);
            $res = $this->client->get(
                "{$url}{$endPoint}",
                [
                    'query' => $parameters,
                    'headers' => $headers,
                ]
            );

            // dd(json_encode($res));
            // dd($res,"{$url}{$endPoint}", $headers, $parameters, $res->getBody()->getContents());
            // return $res;
            return json_decode($res->getBody()->getContents());

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteReq($url, $endPoint, $parameters = [], $headers = [])
    {
        try {
            $res = $this->client->delete(
                "{$url}/{$endPoint}",
                [
                    'query' => $parameters,
                    'headers' => $headers,
                ]
            );

            return json_decode($res->getBody()->getContents());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    abstract public function setConfig();

    abstract public function setMode($mode);

    abstract public function hydrateParams(array $params);
}
