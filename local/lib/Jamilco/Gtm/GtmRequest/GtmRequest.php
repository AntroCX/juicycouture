<?php

namespace Jamilco\Gtm\GtmRequest;

use Jamilco\Gtm\GtmRequest\Exceptions\GtmRequestException;

class GtmRequest
{
    /**
     * @param array $content
     * @return bool|string
     * @throws GtmRequestException
     */
    public static function request(array $content)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_URL, 'https://www.google-analytics.com/collect');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($content));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);

        if (curl_error($ch)) {
            throw (new GtmRequestException('Request exception'))
                ->setCurlHandle($ch)
                ->setResult($res);
        }
        return $res;
    }
}
