<?php

namespace Jamilco\Main\Soap;

use Jamilco\Main\Soap;

class Envelope
{
    var $Header;
    var $Body;

    function __construct()
    {
        $this->Header = new Soap\Header();
        $this->Body = new Soap\Body();
    }
}