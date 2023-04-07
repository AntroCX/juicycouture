<?php
use Bitrix\Main,
    Bitrix\Main\Loader,
    Bitrix\Main\Localization\Loc,
    Bitrix\Sale,
    Bitrix\Sale\Order,
    Bitrix\Sale\PaySystem,
    Bitrix\Sale\Payment;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @var $APPLICATION CMain
 * @var $USER CUser
 */

Loc::loadMessages(__FILE__);

if (!Loader::includeModule("sale"))
{
    ShowError(Loc::getMessage("SOA_MODULE_NOT_INSTALL"));
    return;
}

class EmailpayOrderPayment extends \CBitrixComponent
{
    protected $context;
    protected $checkSession;

    public function onPrepareComponentParams($arParams)
    {
        $this->arResult['AUTH_PAGE'] = (isset($arParams['AUTH_PAGE'])) ? $arParams['AUTH_PAGE'] : '/login/';

        return $arParams;
    }

    protected function showOrderAction()
    {
        //global $USER;
        $arResult =& $this->arResult;
        $arOrder = false;
        $arResult["USER_VALS"]["CONFIRM_ORDER"] = "Y";
        $orderId = urldecode($this->request->get('ORDER_ID'));

        if (!$orderId) {
            return false;
        }

        /** @var Order $order */
        if ($order = Order::loadByAccountNumber($orderId))
        {
            $arOrder = $order->getFieldValues();
            $arResult["ORDER_ID"] = $arOrder["ID"];
            $arResult["ACCOUNT_NUMBER"] = $arOrder["ACCOUNT_NUMBER"];
            $arOrder["IS_ALLOW_PAY"] = $order->isAllowPay()? 'Y' : 'N';
        }

        //$checkedBySession = is_array($_SESSION['SALE_ORDER_ID']) && in_array(intval($order->getId()), $_SESSION['SALE_ORDER_ID']);

        if (!empty($arOrder)/* && ($order->getUserId() == $USER->GetID() || $checkedBySession)*/)
        {
            foreach (GetModuleEvents("sale", "OnSaleComponentOrderOneStepFinal", true) as $arEvent)
                ExecuteModuleEventEx($arEvent, array($arResult["ORDER_ID"], &$arOrder, &$this->arParams));

            $arResult["PAYMENT"] = array();
            if ($order->isAllowPay())
            {
                $paymentCollection = $order->getPaymentCollection();
                /** @var Payment $payment */
                foreach ($paymentCollection as $payment)
                {
                    $arResult["PAYMENT"][$payment->getId()] = $payment->getFieldValues();

                    if (intval($payment->getPaymentSystemId()) > 0 && !$payment->isPaid())
                    {
                        $paySystemService = PaySystem\Manager::getObjectById($payment->getPaymentSystemId());
                        if (!empty($paySystemService))
                        {
                            $arPaySysAction = $paySystemService->getFieldsValues();

                            if ($paySystemService->getField('NEW_WINDOW') === 'N' || $paySystemService->getField('ID') == PaySystem\Manager::getInnerPaySystemId())
                            {
                                /** @var PaySystem\ServiceResult $initResult */
                                $initResult = $paySystemService->initiatePay($payment, null, PaySystem\BaseServiceHandler::STRING);
                                if ($initResult->isSuccess())
                                    $arPaySysAction['BUFFERED_OUTPUT'] = $initResult->getTemplate();
                                else
                                    $arPaySysAction["ERROR"] = $initResult->getErrorMessages();
                            }

                            $arResult["PAYMENT"][$payment->getId()]['PAID'] = $payment->getField('PAID');

                            $arOrder['PAYMENT_ID'] = $payment->getId();
                            $arOrder['PAY_SYSTEM_ID'] = $payment->getPaymentSystemId();
                            $arPaySysAction["NAME"] = htmlspecialcharsEx($arPaySysAction["NAME"]);
                            $arPaySysAction["IS_AFFORD_PDF"] = $paySystemService->isAffordPdf();

                            if ($arPaySysAction > 0)
                                $arPaySysAction["LOGOTIP"] = CFile::GetFileArray($arPaySysAction["LOGOTIP"]);

                            if ($this->arParams['COMPATIBLE_MODE'] == 'Y' && !$payment->isInner())
                            {
                                // compatibility
                                \CSalePaySystemAction::InitParamArrays($order->getFieldValues(), $order->getId(), '', array(), $payment->getFieldValues());
                                $map = CSalePaySystemAction::getOldToNewHandlersMap();
                                $oldHandler = array_search($arPaySysAction["ACTION_FILE"], $map);
                                if ($oldHandler !== false && !$paySystemService->isCustom())
                                    $arPaySysAction["ACTION_FILE"] = $oldHandler;

                                if (strlen($arPaySysAction["ACTION_FILE"]) > 0 && $arPaySysAction["NEW_WINDOW"] != "Y")
                                {
                                    $pathToAction = $this->context->getServer()->getDocumentRoot().$arPaySysAction["ACTION_FILE"];

                                    $pathToAction = str_replace("\\", "/", $pathToAction);
                                    while (substr($pathToAction, strlen($pathToAction) - 1, 1) == "/")
                                        $pathToAction = substr($pathToAction, 0, strlen($pathToAction) - 1);

                                    if (file_exists($pathToAction))
                                    {
                                        if (is_dir($pathToAction) && file_exists($pathToAction."/payment.php"))
                                            $pathToAction .= "/payment.php";

                                        $arPaySysAction["PATH_TO_ACTION"] = $pathToAction;
                                    }
                                }

                                $arResult["PAY_SYSTEM"] = $arPaySysAction;
                            }

                            $arResult["PAY_SYSTEM_LIST"][$payment->getPaymentSystemId()] = $arPaySysAction;
                        }
                        else
                            $arResult["PAY_SYSTEM_LIST"][$payment->getPaymentSystemId()] = array('ERROR' => true);
                    }
                }
            }

            $arResult["ORDER"] = $arOrder;
        } else {
            $arResult["ACCOUNT_NUMBER"] = $orderId;
        }
    }

    public function executeComponent()
    {
        global $USER, $APPLICATION;

        /*if (!$USER->IsAuthorized()) {
            LocalRedirect($this->arResult['AUTH_PAGE'] . '?login=yes&backurl=' . $APPLICATION->GetCurUri());
        }*/

        $this->setFrameMode(false);
        $this->context = Main\Application::getInstance()->getContext();
        $this->checkSession = check_bitrix_sessid();

        Sale\Compatible\DiscountCompatibility::stopUsageCompatible();
        $this->showOrderAction();
        Sale\Compatible\DiscountCompatibility::revertUsageCompatible();

        \CJSCore::Init(array('jquery'));

        $this->includeComponentTemplate();
    }
}
