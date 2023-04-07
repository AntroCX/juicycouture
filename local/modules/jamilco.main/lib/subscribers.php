<?php
namespace Jamilco\Main;

use \Bitrix\Main\Application;
use \Bitrix\Main\Loader;
use \Bitrix\Main\UserTable;
use \Bitrix\Main\Web\Json;
use \Bitrix\Sale\Fuser;
use \Bitrix\Sale\Order;

class Subscribers
{
    private static $instance;

    const COUPON_TYPE   = 'coupon500';
    const EVENT_TYPE    = 'SUBSCRIBE_COUPON';

    static $arrStatus = array(
        'A' => 'Спасибо за подписку! На ваш e-mail отправлен купон на скидку, пожалуйста, проверьте почту.',
        'Y' => 'Рады, что вы снова с нами, e-mail успешно подписан на рассылку!',
        'N' => 'Ваш email будет отключен от рассылки в течении 24ч.',
        'E' => 'Произошла ошибка, обратитесь к сотрудникам технической поддержки'
    );

    static $arResponse = array(
        'MESSAGE' => '',
        'RESULT'  => 'E',
    );

    private function __construct()
    {

    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * проверяем подписан ли e-mail на рассылку
     *
     * @param string $email
     *
     * @result array $subscribeUser
     *      ['ID']        - id записи в ИБ Подписчики
     *      ['STATUS']    - NEW | YES | NO
     *      ['COUPON']    - номер выданного купона
     *      ['UNIC_CODE'] - уникальный код проверки для отписки клиента
     */
    static public function checkSubcscribers($email = '')
    {
        $subscribeUser['ID'] = 0;
        $subscribeUser['STATUS'] = 'NEW'; // новый подписчик
        $subscribeUser['COUPON'] = ''; // новый подписчик
        $subscribeUser['UNIC_CODE'] = ''; // уникальный код для отписки

        $res_subscruber = \CIBlockElement::GetList(
            [],
            ["IBLOCK_ID" => SUBSCRIBE_IBLOCK, "=NAME" => $email],
            ["ID", "ACTIVE", "CODE", "PROPERTY_UNIC_CODE"]
        );
        while ($userData = $res_subscruber->GetNext())
            if($userData['ID'] > 0) {
                $subscribeUser['ID']        = $userData['ID'];
                $subscribeUser['COUPON']    = $userData['CODE'];
                $subscribeUser['UNIC_CODE'] = $userData['PROPERTY_UNIC_CODE_VALUE'];

                if ($userData['ACTIVE'] == 'N')
                    $subscribeUser['STATUS'] = 'NO'; // email отписался
                 else
                    $subscribeUser['STATUS'] = 'YES'; // email подписан
            }
        return $subscribeUser;
    }


    /**
     * подписываем по e-mail на рассылку
     *
     * @param string $email
     * @param string $sendCoupon
     *      N - не отправляем купон
     *      Y - отправляем купон
     *
     * @return array
     *      MESSAGE - сообщение для клиента
     *      STATUS  - стаус исполнения
     */
    static public function setSubcscribers($email = '', $sendCoupon = 'N', $setActiveSubscribe="Y")
    {
        $coupon = '';

        $el = new \CIBlockElement;
        $userSubscruber = self::checkSubcscribers($email);

        if($sendCoupon == 'Y' || empty($userSubscruber['COUPON'])) {
            $coupon = Manzana::getInstance()->generateCoupon('', self::COUPON_TYPE);
        }

        if($userSubscruber['STATUS'] == 'NEW') { // новый подписчик
            $PROP = array();
            $PROP[168] = substr(md5(microtime()), 0, 16);

            $userArray = Array(
                "IBLOCK_ID" => SUBSCRIBE_IBLOCK,
                "NAME" => $email,
                "ACTIVE" => $setActiveSubscribe,
                "CODE" => '',
                "PROPERTY_VALUES" => $PROP,
            );

            if($sendCoupon == 'Y') {
               // $userArray["ACTIVE"] = "Y";
                $userArray["CODE"] = $coupon;

                self::sendCouponSubscribers($email, $coupon);
            }

            $el->Add($userArray);


            self::$arResponse = array(
                'MESSAGE' => self::$arrStatus['A'],
                'RESULT' => 'A',
            );
        }
        else { // обновление подписки

            $userArray = Array("ACTIVE" => "Y");

            if(empty($userSubscruber['COUPON'])) {
                self::sendCouponSubscribers($email, $coupon);
                $userArray["CODE"] = $coupon;
            }

            $el->Update($userSubscruber['ID'], $userArray);

            self::$arResponse = array(
                'MESSAGE' => self::$arrStatus['Y'],
                'RESULT' => 'Y',
            );
        }


        return self::$arResponse;
    }

    /**
     * отписываемне по e-mail на рассылку
     *
     * @param string $email
     * @param string $comment
     */
    static public function unsetSubcscribers($email = '', $comment = '')
    {
        $el = new \CIBlockElement;

        $userSubscriber = self::checkSubcscribers($email);

        if($userSubscriber['STATUS'] == 'YES') {
            $el->Update($userSubscriber['ID'], Array("ACTIVE" => "N", "PREVIEW_TEXT" => $comment));

            self::$arResponse = array(
                'MESSAGE' => self::$arrStatus['N'],
                'RESULT' => 'N',
            );

        }
        else {
            self::$arResponse = array(
                'MESSAGE' => self::$arrStatus['E'],
                'RESULT' => 'E',
            );
        }

        return self::$arResponse;
    }

    /**
     * узнаем номер купона за подписку и его активность
     *
     * @param string $email
     *
     * @result array
     *      ORDER_ID - номер заказа где был использован купон;
     *      ORDER_DATE - дата заказа где был использован купон
     */
    static public function getCouponSubscribers($email = '')
    {
        $userSubscriber = self::checkSubcscribers($email);
        $arCoupon = [];

        if(!empty($userSubscriber["COUPON"])) {

            $rsOrders = Order::getList([
                'filter' => [
                    '=PROPERTY_VAL.CODE' => 'COUPONS',
                    '=PROPERTY_VAL.VALUE' => $userSubscriber["COUPON"],
                ],
                'runtime' => [
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PROPERTY_VAL',
                        '\Bitrix\sale\Internals\OrderPropsValueTable',
                        ['=this.ID' => 'ref.ORDER_ID'],
                        ['join_type' => 'left']
                    ),
                ],
                'order' => ['ID' => 'DESC'],
            ]);

            if ($arOrder = $rsOrders->fetch()) {
                $arCoupon["COUPON_USE"]['ORDER_ID'] = $arOrder['ACCOUNT_NUMBER'];
                $arCoupon["COUPON_USE"]['ORDER_DATE'] = $arOrder['DATE_INSERT']->toString();
            }
        }

        return $arCoupon;
    }


    /**
     * отправляем купон подписчику
     *
     * @param string $email
     * @param string $coupon
     *
     * @result bool
     */
    static public function sendCouponSubscribers($email = '', $coupon = '')
    {
        $result = false;
        // Если купон сгенерирован отправляем его пользователю
        if(!empty($email) && !empty($coupon)) {
            \CEvent::Send(self::EVENT_TYPE, SITE_ID, ['EMAIL' => $email, 'COUPON' => $coupon], 'N');
            $result = true;
        }

        return $result;
    }
}
