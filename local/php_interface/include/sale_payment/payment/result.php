<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$asset = \Bitrix\Main\Page\Asset::getInstance();
$asset->addCss(SITE_TEMPLATE_PATH.'/css/HEAD-DEFAULT-donnakaran.css?minimize=true');
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rbs.payment/payment/result.php");?>
<div class="b-order__payment-result text-center">
        <div class="b-order__payment-result-status-ok"></div>
        <h3>Заказ оплачен!</h3>

        <p>Сипсок заказов и их статус смотрите на <a href="/personal/orders/">специальной странице</a> в личном кабинете.</p>
    </div>

    <style type="text/css">
        .b-order__payment-result{
            padding-top: 50px;
            padding-bottom: 50px;
            text-align: center;
        }
        .b-order__payment-result-status-ok:before{
            display: block;
            content: '\e804';
            font-family: 'tl';
            font-size: 70px;
            color: #4DAF7C;
            text-align: center;
        }
    </style>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>