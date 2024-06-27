<?php

namespace Unusualify\Payable\Services;


interface URequestInterface
{

  

    /**
     * 
     * Post Curl Template
     *
     * @param array $fields Post body to be sent
     * @param string $endPoint
     * @return object
     */
    public function postReq(String $url, String $endPoint, Array $postFields, Array $headers, String $type);

    /**
     * 
     * Get Curl Template
     * 
     * @param array $parameters get query to be sent
     * @param string $endPoint
     * @return object
     */
    public function getReq(String $endPoint, Array $parameters, Array $headers);
    

    /*
     * Set Config for payment services
     * 
     * @param array $config  
     */
    public function setConfig();

    /*
     * Create config name based on the class name
     * @return string configName
     */
    public function getConfigName();


    /**
     * 
     * Set Mode
     *
     * @return null
     */
    public function setMode(String $mode);

    /**
     * 
     * Convert Mode to Sandbox
     *
     * @return null
     */
    public function setSandbox();

    /**
     * 
     * Convert Mode to Live
     *
     * @return null
     */
    public function setLive();
}
