<?php

namespace Jamilco\Gtm\GtmRequest\Exceptions;

class GtmRequestException extends \RuntimeException
{
    /** @var string|bool */
    protected $result;
    /** @var resource|false */
    protected $curlHandle;

    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return bool|string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param bool|string $result
     * @return GtmRequestException
     */
    public function setResult($result): GtmRequestException
    {
        $this->result = $result;
        return $this;
    }
    /**
     * @return false|resource
     */
    public function getCurlHandle()
    {
        return $this->curlHandle;
    }

    /**
     * @param false|resource $curlHandle
     * @return GtmRequestException
     */
    public function setCurlHandle($curlHandle): GtmRequestException
    {
        $this->curlHandle = $curlHandle;
        return $this;
    }
}
