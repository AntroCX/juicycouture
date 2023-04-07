<?php
namespace Jamilco\AdminOrderForm;
\Bitrix\Main\loader::IncludeModule('sale');
use \Bitrix\Main;

/**
 * Class Events
 * @package Jamilco\AdminOrderForm
 */
class Events
{

    public function onInit()
    {
        return array(
            "BLOCKSET" => "Jamilco\\AdminOrderForm\\Events",
            "check" => array("Jamilco\\AdminOrderForm\\Events", "mycheck"),
            "action" => array("Jamilco\\AdminOrderForm\\Events","myaction"),
            "getScripts" => array("Jamilco\\AdminOrderForm\\Events", "mygetScripts"),
            "getBlocksBrief" => array("Jamilco\\AdminOrderForm\\Events", "mygetBlocksBrief"),
            "getBlockContent" => array("Jamilco\\AdminOrderForm\\Events", "mygetBlockContent")
        );
    }

    public function myaction($args)
    {
        // заказ сохранен, сохраняем данные пользовательских блоков
        // возвращаем True в случае успеха и False - в случае ошибки
        // в случае ошибки $GLOBALS["APPLICATION"]->ThrowException("Ошибка!!!", "ERROR");
        return true;
    }

    public function mycheck($args)
    {
        // заказ еще не сохранен, делаем проверки
        // возвращаем True, если можно все сохранять, иначе False
        // в случае ошибки $GLOBALS["APPLICATION"]->ThrowException("Ошибка!!!", "ERROR");
        return true;
    }

    public function mygetBlocksBrief($args)
    {
        return array(
            'custom1' => array("TITLE" => "Уведомление о недозвоне"),
        );
    }

    public function mygetBlockContent($blockCode, $selectedTab, $args)
    {
        $result = '';
        if (($selectedTab == 'tab_order'))
        {
            if ($blockCode == 'custom1')
            {
                $arFilter = Array(
                    "TYPE_ID"       => array("SALE_CALLBACK_NOTICE"),
                    "ACTIVE"        => "Y",
                );
                $rsMess = \CEventMessage::GetList($by="site_id", $order="desc", $arFilter);
                if(!$arMess = $rsMess->GetNext())
                {
                    $errorMessage = new \CAdminMessage(
                        array(
                            'DETAILS' => '',
                            'TYPE' => 'ERROR',
                            'MESSAGE' => "Создайте шаблон письма для типа почтового события SALE_CALLBACK_NOTICE",
                            'HTML' => true
                        )
                    );
                    echo $errorMessage->Show();
                }
                else{
                    //получим шаблон письма
                    $messageBody = "";
                    $arFilter = Array(
                        "TYPE_ID"       => "SALE_CALLBACK_NOTICE",
                    );
                    $rsMess = \CEventMessage::GetList($by="site_id", $order="desc", $arFilter);
                    if($arMess = $rsMess->GetNext()) {
                        $messageBody = $arMess["~MESSAGE"];
                    }

                    // заказ
                    $accountNumber = !empty($args['ORDER']) ? $args['ORDER']->getField("ACCOUNT_NUMBER") : 0;
                    $order = \Bitrix\Sale\Order::loadByAccountNumber($accountNumber);
                    $price = $order->getPrice();
                    $arProps = $order->getPropertyCollection()->getArray();
                    $arValues = [];
                    foreach($arProps["properties"] as $arProp){
                        if($arProp["CODE"] == "IS_CALLBACK_DATE"){
                            $arValues[$arProp["CODE"]] = $arProp["VALUE"];
                        }
                        else {
                            $arValues[$arProp["CODE"]] = $arProp["VALUE"][0];
                        }
                    }

                    if($messageBody){
                        $messageBody = str_replace(
                            ['#ACCOUNT_NUMBER#', '#SITE_NAME#', '#TOTAL_SUM#', '#USER_PHONE#'],
                            [$accountNumber, '', $price, $arValues['PHONE']],
                            $messageBody
                        );

                        $result = '
                        <div class="adm-bus-table-container caption border sale-order-props-group">
                            <div class="adm-bus-table-caption-title">Текст письма</div>
                                <p>'.$messageBody.'</p>
                        </div>';
                    }


                    $result .= '<input type="button" name="j_callback" value="Отправить" id="j_callback"><br><br><br>';

                    // проверим, отправлялось ли уже письмо
                    if($arValues['IS_CALLBACK'] === "Y"){
                        $res = "<b>Письмо отправлено:</b><br>";
                        if(!empty($arValues['IS_CALLBACK_DATE'])){
                            foreach ($arValues['IS_CALLBACK_DATE'] as $arValue)
                            $res .= $arValue."<br>";
                        }
                    }
                    else{
                        $res = '<b>Письмо еще не было отправлено.</b>';
                    }
                    $result .= '
                    <div class="adm-bus-table-container caption border sale-order-props-group">
                            <div class="adm-bus-table-caption-title">Результат отправки</div>
                            <p id="j-adminorderform-callback-result" style="padding-left:20px;">'.$res.'</p>
                    </div>';
                }
            }
        }

        return $result;
    }

    public function mygetScripts($args)
    {
        $output = '';

        $accountNumber = !empty($args['ORDER']) ? $args['ORDER']->getField("ACCOUNT_NUMBER") : 0;
        $total_sum = !empty($args['ORDER']) ? $args['ORDER']->getPrice() : 0;
        $propertyCollection = !empty($args['ORDER']) ? $args['ORDER']->getPropertyCollection() : 0;

        if(!empty($propertyCollection)) {
            $userEmail = $propertyCollection->getUserEmail()->getValue();

            // ищем тел. в свойствах заказа
            $userPhone = '';
            foreach($propertyCollection->getGroups() as $group){
                foreach ($propertyCollection->getGroupProperties($group['ID']) as $property) {
                    $p = $property->getProperty();
                    if ($p['CODE'] == 'PHONE') {
                        if($p["ID"])
                            $userPhone = $propertyCollection->getItemByOrderPropertyId($p['ID'])->getValue();
                        break;
                    }
                }
            }
        }
        ?>
        <?if($accountNumber && $userEmail && $userPhone && $total_sum):?>
        <?$req = 'account_number='.$accountNumber.'&user_email='.$userEmail.'&user_phone='.$userPhone.'&total_sum='.$total_sum;?>
        <?
        ob_start();
        ?>
        <script type="text/javascript">
            BX.ready(function(){
                BX.bind(BX('j_callback'), 'click', function() {
                    var url = '/bitrix/admin/jamilco_admin_order_form_ajax.php';
                    var req = '<?=$req?>'+'&sessid='+BX.bitrix_sessid();
                    BX.ajax.post(
                        url,
                        req,
                        function (res) {
                          if(res === 'err')
                            var msg = 'Произошла ошибка!';
                          else
                            var msg = res;
                          var popup = BX.PopupWindowManager.create("popup-message", null, {
                            content: (res === 'err')? msg:'Письмо отправлено!',
                            darkMode: true,
                            autoHide: true
                          });
                          popup.show();
                          $('#j-adminorderform-callback-result').html(msg);
                        }
                    );
                });
            });
        </script>
        <?
        $output = ob_get_contents();
        ob_end_clean();
        ?>
    <?endif;?>
        <?
        return $output;
    }
}