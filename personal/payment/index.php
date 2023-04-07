<? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle('Результат оплаты');
?>

    <div class="b-order__payment-result text-center">
        <div class="b-order__payment-result-status-ok glyphicon glyphicon-ok"></div>
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
            content: '\f00c';
            font-family: 'FontAwesome';
            font-size: 70px;
            color: #4DAF7C;
            text-align: center;
        }
    </style>


<? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); ?>