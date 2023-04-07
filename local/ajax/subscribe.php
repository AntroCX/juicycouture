<?
use \Bitrix\Main\Context;
use \Jamilco\Main\Manzana;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

$request = Context::getCurrent()->getRequest();

/* driveBack req */
$isDriveBackReq = ($_REQUEST["driveback"] == "905fwasfr8oyyki5") ? true : false;

$email = filter_var($request->getQuery('SUBSCRIBE_EMAIL'), FILTER_SANITIZE_EMAIL);


if( (check_bitrix_sessid() || $isDriveBackReq) && $_REQUEST['SUBSCRIBE_EMAIL'] ) {
    if(CModule::IncludeModule('subscribe')) {
        $sub = new \CSubscription();
        $res = $sub->Add(array(
            'EMAIL' => $_REQUEST['SUBSCRIBE_EMAIL'],
            'ACTIVE' => 'Y',
            'RUB_ID' => array(1),
            'SEND_CONFIRM' => 'N',
            'USER_ID' => $GLOBALS['USER']->GetID()
        ));
        if ($res) {

            if (method_exists('\Jamilco\Loyalty\Common', 'discountsAreMoved') && \Jamilco\Loyalty\Common::discountsAreMoved()) {
                // купон создается в Манзане
                $couponNum = Manzana::getInstance()->generateCoupon('', 'coupon500');
            } else {
                $activeFrom = new \Bitrix\Main\Type\DateTime;
                $activeTo = clone $activeFrom;

                $activeTo = $activeTo->add('30 days');

                \CModule::IncludeModule('sale');
                $couponNum = "JC-SUB".rand(10000, 99999)."-".rand(100, 999);

                $result = \Bitrix\Sale\Internals\DiscountCouponTable::add(
                    array(
                        "DISCOUNT_ID" => 12, // скидка на 500руб
                        "ACTIVE"      => "Y",
                        "ACTIVE_FROM" => $activeFrom,
                        "ACTIVE_TO"   => $activeTo,
                        "COUPON"      => $couponNum, // конечно могут совпасть, но вероятность мала, оставляю пока так, не обесудьте ;)
                        "TYPE"        => \Bitrix\Sale\Internals\DiscountCouponTable::TYPE_ONE_ORDER,
                        //"MAX_USE" => 1,
                        "DESCRIPTION" => $_REQUEST['SUBSCRIBE_EMAIL']
                    )
                );
                if (!$result->isSuccess()) $couponNum = false;
            }

            /* генерация купона по drive back reg */
            if($couponNum && $isDriveBackReq) {
                \CEvent::SendImmediate(
                    'SUBSCRIBE_COUPON',
                    's1',
                    array(
                        'EMAIL' => $_REQUEST['SUBSCRIBE_EMAIL'],
                        'COUPON' => $couponNum,
                        'DATE_FROM' => $activeFrom,
                        'DATE_TO' => $activeTo
                    ),
                    'N',
                    98);

                if ($isDriveBackReq) {
                    echo "ok";
                    die();
                }
            }

            ?>
            <h5 class="text-center">Спасибо. <br> Вы успешно подписались на рассылку</h5>
            <?
        } else {

            if($isDriveBackReq){
                echo $sub->LAST_ERROR;
                die();
            }

            ?>
            <h5 class="text-center">Спасибо. <br> Вы уже подписаны</h5>
            <?
        }
        ?>

        <?php /** DigitalDataLayer start */
        $request = Context::getCurrent()->getRequest();
        $email = filter_var($request->getQuery('SUBSCRIBE_EMAIL'), FILTER_SANITIZE_EMAIL);
        ?>
        <script>
            if (typeof window.digitalData.events !== 'undefined') {
                window.digitalData.events.push({
                    'category': 'Email',
                    'name': 'Subscribed',
                    'label': 'Popup subscription',
                    'user': {
                        'email': '<?=$email?>'
                    }
                });
            }
        </script>
        <?php /** DigitalDataLayer end */ ?>
        <?
    }
}
