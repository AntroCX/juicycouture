<?php

use Bitrix\Main\Context;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$request = Context::getCurrent()->getRequest();
$response = Context::getCurrent()->getResponse();

if ($request->isAjaxRequest() && $request->isPost() && check_bitrix_sessid()) {

    \CBitrixComponent::includeComponentClass('jamilco:sale.order.loyalty');

    if (class_exists('CJamilcoLoyality')) {

        /** Создание экземпляра класса и его инициализация */
        $loyalty = new \CJamilcoLoyality();

        $loyalty->initComponent('jamilco:sale.order.loyalty');

        $loyalty->arParams['IS_AJAX_MODE'] = true;

        /** Действие в зависимости от операции */
        switch ($request->getPost('action')) {

            case 'info':
                $loyalty->arParams['CARD_NUMBER'] = $request->getPost('loyaltyCardNumber');
                if (isset($loyalty->arParams['CARD_NUMBER']) && !strlen($loyalty->arParams['CARD_NUMBER'])) {
                    unset($_SESSION['LOYALTY_CARD_NUMBER']);
                }
                break;

            case 'applyBonuses':
                $applyBonuses = $request->getPost('applyBonuses');
                $result = $loyalty->getData($request->getPost('loyaltyCardNumber'), $applyBonuses);

                $ajaxResult = [
                    'success' => true,
                    'info' => $result
                ];
                break;

            case 'sendCode':
                $ajaxResult = [
                    'success' => $loyalty->checkCode($request->getPost('type'))
                ];
                break;

            case 'confirmCode':
                $ajaxResult = [
                    'success' => $loyalty->confirmCode($request->getPost('code'))
                ];
                break;
        }

        if ($loyalty->arParams['CARD_NUMBER']) {
            $loyalty->executeComponent();
        }

        if (isset($ajaxResult)) {
            $response->addHeader('Content-type', 'application/json; charset=utf-8');
            echo json_encode($ajaxResult);
        }
        $response->flush();
    }
}