<?php

namespace Unusualify\Payable\Services;

interface URequestInterface
{
    /**
     * Post Curl Template
     *
     * @param  array  $fields  Post body to be sent
     * @return object
     */
    public function postReq(string $url, string $endPoint, array $postFields, array $headers, string $type);

    /**
     * Get Curl Template
     *
     * @param  array  $parameters  get query to be sent
     * @return object
     */
    public function getReq(string $endPoint, array $parameters, array $headers);

    /**
     * Set Mode
     *
     * @return null
     */
    public function setMode(string $mode);

    /*
     * Set Config for payment services
     *
     * @param array $config
     */
    public function setConfig();
}
