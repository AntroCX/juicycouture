<?php

use Bitrix\Main\Context;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

$request = Context::getCurrent()->getRequest();
$email = filter_var($request->get('email'), FILTER_SANITIZE_EMAIL);
$productName = filter_var($request->get('notify_product_name'), FILTER_SANITIZE_STRING);
$sku_id = $request->get('sku_id');

if($email && $sku_id) {
    Bitrix\Main\Loader::includeModule('catalog');

    $subscribeManager = new \Bitrix\Catalog\Product\SubscribeManager;
    $contactTypes = $subscribeManager->contactTypes;

    $result = $subscribeManager->addSubscribe(
        array(
            'USER_CONTACT' => $email,
            'ITEM_ID'      => $sku_id,
            'SITE_ID'      => 's1',
            'CONTACT_TYPE' => 1,
            'USER_ID'      => ($USER->IsAuthorized()) ? $USER->GetID() : false
        )
    );


    if($result) {
        ?>

        <div class="text-center modal-body">
            <h3>О поступлении товара мы оповестим вас по указанному email</h3>
        </div>

        <?
    } else {
        ?>
        <div class="text-center modal-body">
            <h3>Вы уже подписаны </h3>
            <br>
            <div>
                Вы оставляли этот e-mail при подписке на уведомление появления данного товара<br>
            </div>
        </div>
        <?
    }


} else {
    ?>

    <div class="text-center modal-body">
        <h4>Ошибка при бронировании</h4>
    </div>

    <?
}
