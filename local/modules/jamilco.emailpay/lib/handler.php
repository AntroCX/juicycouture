<?php

namespace Jamilco\EmailPay;

use Bitrix\Main\Context;

/**
 * Class Handler
 * @package Jamilco\EmailPay
 */
class Handler
{
    const ONLINE_PAY_SYSTEM_ID = 7;

    const EMAIL_PROPERTY_CODE = 'EMAIL';

    const NAME_PROPERTY_CODE = 'NAME';

    function onSaleStatusOrderMail($ID, $val)
    {
        $emailStatus = \COption::GetOptionString("jamilco.emailpay", "action_status_id");

        //если выбран статус для отправки email
        if ($val == $emailStatus) {
            $context = Context::getCurrent();
            $scheme = $context->getRequest()->isHttps() ? 'https' : 'http';
            $domain = $context->getServer()->getServerName();
            $messageType = \COption::GetOptionString("jamilco.emailpay", "mail_event_name");

            $arOrder = \CSaleOrder::GetByID($ID);

            if ($arOrder['PAY_SYSTEM_ID'] == self::ONLINE_PAY_SYSTEM_ID) {
                $arUser = \CUser::GetByID($arOrder['USER_ID'])->Fetch();

                $dbProps = \CSaleOrderPropsValue::GetOrderProps($ID);
                $EMAIL = $NAME = "";
                while ($arProp = $dbProps->Fetch()) {
                    if ($arProp["CODE"] == self::EMAIL_PROPERTY_CODE) {
                        $EMAIL = $arProp["VALUE"];
                    } elseif ($arProp["CODE"] == self::NAME_PROPERTY_CODE) {
                        $NAME = $arProp["VALUE"];
                    }
                }

                $arEventFields = array(
                    "ORDER_ID"    => $arOrder["ACCOUNT_NUMBER"],
                    "EMAIL_TO"    => $EMAIL,
                    "NAME"        => $NAME,
                    "CUR_DATE"    => $arOrder['DATE_INSERT_FORMAT'],
                    "LINK_TO_PAY" => $scheme . "://". $domain . "/personal/emailpay.php?ORDER_ID=" . $arOrder["ACCOUNT_NUMBER"]
                );

                \CEvent::Send($messageType, 's1', $arEventFields);
            }
        }
    }
}
