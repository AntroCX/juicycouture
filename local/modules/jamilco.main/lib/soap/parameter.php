<?php

namespace Jamilco\Main\Soap;

use Jamilco\Main\Soap;

class Parameter
{
    var $Name;
    var $Value;

    function __construct($name, $value)
    {
        $this->Name = $name;
        $this->Value = $value;
    }

    function setName($name)
    {
        $this->Name = $name;
    }

    function name()
    {
        return $this->Name;
    }

    function setValue($value)
    {

    }

    function value()
    {
        return $this->Value;
    }
}
