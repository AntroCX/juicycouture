<?php

namespace Jamilco\Main\Soap;

use Jamilco\Main\Soap;

class Fault
{
    var $FaultCode;
    var $FaultString;
    var $detail;

    function __construct($faultCode = "", $faultString = "", $detail = '')
    {
        $this->FaultCode = $faultCode;
        $this->FaultString = $faultString;
        $this->detail = $detail;
    }

    function faultCode()
    {
        return $this->FaultCode;
    }

    function faultString()
    {
        return $this->FaultString;
    }

    function detail()
    {
        return $this->detail;
    }
}