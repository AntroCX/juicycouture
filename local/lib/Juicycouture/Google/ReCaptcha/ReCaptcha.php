<?php

namespace Juicycouture\Google\ReCaptcha;

use Bitrix\Main\Config\Option;


class ReCaptcha
{
    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    const SCORE = 0.5;

    private $secret;
    private $token;

    public function __construct($token)
    {
        if (getenv('RECAPTCHA.SITE_KEY')) {
            if (empty($token)) {
                throw new \RuntimeException('Пустой токен');
            }

            if (!is_string($token)) {
                throw new \RuntimeException('Токен должен быть строкой');
            }

            $this->token = $token;
            $this->secret = getenv('RECAPTCHA.SECRET_KEY');
        }
    }

    public function verify()
    {
        if (getenv('RECAPTCHA.SITE_KEY')) {
            $response = $this->send();
            $responseJson = $this->getResponseFromJson($response);
            $this->checkResponse($responseJson);
        }
    }

    private function send()
    {
        $curl = curl_init($this->getUrl());
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    private function getUrl(): string
    {
        return self::SITE_VERIFY_URL . '?secret=' . $this->secret . '&response=' . $this->token;
    }

    private function getResponseFromJson($response)
    {
        return json_decode($response);
    }

    private function checkResponse($response)
    {
        if (!$response->success) {
            throw new \RuntimeException('Что-то пошло не так. Обновите страницу');
        }

        if ($response->score < self::SCORE) {
            throw new \RuntimeException('Проверка не пройдена');
        }
    }
}
