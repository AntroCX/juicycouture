<?php

namespace Jamilco\Main\Soap;

use Jamilco\Main\Soap;

define("BX_SOAP_ENV", "http://schemas.xmlsoap.org/soap/envelope/");
define("BX_SOAP_ENC", "http://schemas.xmlsoap.org/soap/encoding/");
define("BX_SOAP_SCHEMA_INSTANCE", "http://www.w3.org/2001/XMLSchema-instance");
define("BX_SOAP_SCHEMA_DATA", "http://www.w3.org/2001/XMLSchema");

define("BX_SOAP_ENV_PREFIX", "SOAP-ENV");
define("BX_SOAP_ENC_PREFIX", "SOAP-ENC");
define("BX_SOAP_XSI_PREFIX", "xsi");
define("BX_SOAP_XSD_PREFIX", "xsd");

define("BX_SOAP_INT", 1);
define("BX_SOAP_STRING", 2);

define('WS_SP_SERVICE_PATH', '/_vti_bin/lists.asmx');
define('WS_SP_SERVICE_NS', 'http://schemas.microsoft.com/sharepoint/soap/');

$GLOBALS["xsd_simple_type"] = [
    "string"       => "string",
    "bool"         => "boolean",
    "boolean"      => "boolean",
    "int"          => "integer",
    "integer"      => "integer",
    "double"       => "double",
    "float"        => "float",
    "number"       => "float",
    "base64"       => "base64Binary",
    "base64Binary" => "base64Binary",
    "any"          => "any",
];

class Request extends Envelope
{
    /// The request name
    var $Name;

    /// The request target namespace
    var $Namespace;

    /// Headers
    var $Headers = [];

    /// Additional body element attributes.
    var $BodyAttributes = [];

    /// Contains the request parameters
    var $Parameters = [];

    function __construct($name = "", $namespace = "", $parameters = [])
    {
        $this->Name = $name;
        $this->Namespace = $namespace;

        // call the parents constructor
        parent::__construct();

        foreach ($parameters as $name => $value) {
            $this->addParameter($name, $value);
        }
    }

    function name()
    {
        return $this->Name;
    }

    function get_namespace()
    {
        return $this->Namespace;
    }

    function GetSOAPAction($separator = '/')
    {
        if ($this->Namespace[strlen($this->Namespace) - 1] != $separator) {
            return $this->Namespace.$separator.$this->Name;
        }

        return $this->Namespace.$this->Name;
    }

    function addSOAPHeader($name, $value)
    {
        $this->Headers[] = \SoapXmlCreator::encodeValueLight($name, $value);
    }

    //     Adds a new attribute to the body element.
    function addBodyAttribute($name, $value)
    {
        $this->BodyAttributes[$name] = $value;
    }

    //      Adds a new parameter to the request. You have to provide a prameter name
    //      and value.
    function addParameter($name, $value)
    {
        $this->Parameters[$name] = $value;
    }

    //      Returns the request payload
    function payload()
    {
        $root = new \SoapXmlCreator("soap:Envelope");
        $root->setAttribute("xmlns:soap", BX_SOAP_ENV);

        $root->setAttribute(BX_SOAP_XSI_PREFIX, BX_SOAP_SCHEMA_INSTANCE);
        $root->setAttribute(BX_SOAP_XSD_PREFIX, BX_SOAP_SCHEMA_DATA);
        $root->setAttribute(BX_SOAP_ENC_PREFIX, BX_SOAP_ENC);

        $header = new \SoapXmlCreator("soap:Header");
        $root->addChild($header);

        foreach ($this->Headers as $hx) {
            $header->addChild($hx);
        }

        // add the body
        $body = new \SoapXmlCreator("soap:Body");

        foreach ($this->BodyAttributes as $attribute => $value) {
            $body->setAttribute($attribute, $value);
        }

        // add the request
        $request = new \SoapXmlCreator($this->Name);
        $request->setAttribute("xmlns", $this->Namespace);

        // add the request parameters
        $param = null;
        foreach ($this->Parameters as $parameter => $value) {
            unset($param);
            $param = \SoapXmlCreator::encodeValueLight($parameter, $value);

            if ($param == false) {
                ShowError("Error enconding data for payload");
            }
            $request->addChild($param);
        }

        $body->addChild($request);
        $root->addChild($body);

        return \SoapXmlCreator::getXMLHeader().$root->getXML();
    }
}