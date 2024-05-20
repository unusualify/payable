<?php

namespace Unusualify\Payable\Services;


use Illuminate\Support\Str;

class RequestService extends URequest{

  public $mode;
  protected $testTokenUrl;
  protected $prodTokenUrl;

  protected $testUrl;
  protected $prodUrl;

  public $apiProdKey;

  public $apiTestKey; //Actual prod key

  public $apiProdSecret;

  public $apiTestSecret; //Actual prod key

  public $apiKey;

  public $apiSecret;

  protected $url;

  protected $root_path;

  protected $token_path;

  protected $path;

  protected $token_refresh_time; //by minute
  protected $envVar;
  protected $redirect_url;

  protected $headers = [ 
    'Authorization' => 'Bearer',
    'Content-Type' => 'application/json',
  ];

  public function __construct(
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
  ){
    $this->mode = $this->mode == null ? (getenv($envVar) ? getenv($envVar) : 'test') : $this->mode;
    parent::__construct(
      mode : $this->mode,
      headers: $this->headers,
      prodUrl: $prodUrl,
      testUrl: $testUrl,
      prodTokenUrl: $prodTokenUrl,
      testTokenUrl: $testTokenUrl,
      apiProdKey: $apiProdKey,
      apiTestKey: $apiTestKey
    );

    $this->root_path = base_path();
    $this->path = "{$this->root_path}/{$this->token_path}";

    $this->redirect_url = $redirect_url;
  }
}
