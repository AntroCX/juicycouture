<?php
use Bitrix\Main\Web\Json;
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** DigitalDataLayer start */
$digitalData = \DigitalDataLayer\Manager::getInstance()->getData();
$digitalData->cart = $arResult['DDL_CART_PROPERTIES'] ?: [];

/** если ajax-запрос, обновляем корзину */
if (
    ($_SERVER["REQUEST_METHOD"] == "POST" && check_bitrix_sessid()
        && isset($_POST["siteId"])
        && ctype_alnum($_POST["siteId"])
        && strlen($_POST["siteId"]) == 2 )
    ||
    (check_bitrix_sessid() && $_POST['id'])
    ||
    $_POST['update_small']
){
    $digitalData->events = ['name' => 'Updated Cart', 'category' => 'Ecommerce', 'cart' => $arResult['DDL_CART_PROPERTIES'] ?: []];
    
    echo '<script> if (typeof window.digitalData.changes !== "undefined") {
    window.digitalData.changes.push(["cart", ' .
        Json::encode(
            $digitalData->cart,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ) .
        ']);
            setTimeout(function(){
               window.digitalData.events.push({
                    \'category\': \'Ecommerce\',
                    \'name\': \'Updated Cart\',
                    \'cart\': window.digitalData.cart
                  });
            }, 500);
        }
        </script>';
}
/** DigitalDataLayer end */
?>