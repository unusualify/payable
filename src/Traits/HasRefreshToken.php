<?php

namespace Unusualify\Payable\Traits;

trait HasRefreshToken
{
  // Get refresh token from file 
  // Get access token by using refresh token

  public function fetchRefreshToken($endpoint, $data, $headers, $type)
  {  
    $response = $this->postReq($this->prodTokenUrl, $endpoint, $data, $headers, $type); // Parameter from class 

    return $response;
  }

  public function saveRefreshToken($token_json, $token_name) //Refresh token key, access token key, etc. etc.
  {
    //If it has a refresh token add refresh token to the json object
    $this->token = $token_json; //There must

    $path = "{$this->root_path}/{$this->token_path}";

    return file_put_contents($path, $this->token);
  }

  public function fetchAccessToken($path, $refresh_token, $fields, $endpoint)
  {
    $response = $this->postWithBearer($this->prodTokenUrl, $endpoint, $fields, $this->headers,'encoded');

    return $response;
  }
}

