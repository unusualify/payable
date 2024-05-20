<?php

namespace Unusualify\Payable\Services;


interface URequestInterface
{

    /**
     * 
     * Set Mode
     *
     * @return null
     */
    public function setMode(String $mode);

    /**
     * 
     * Convert Mode to Test
     *
     * @return null
     */
    public function setTest();

    /**
     * 
     * Convert Mode to Prod
     *
     * @return null
     */
    public function setProd();

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
 
}