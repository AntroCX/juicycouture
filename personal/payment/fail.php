<? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Ошибка оплаты");

?>


    <div class="b-order__payment-result text-center">
        <div class="b-order__payment-result-status-error"></div>
        <h3>Извините, произошла ошибка при оплате</h3>

        <p>Попробуйте еще раз, при повторе ошибки свяжитесь с Банком-Эмитентом Вашей карты.</p>
    </div>

    <style type="text/css">
        .b-order__payment-result{
            padding-top: 50px;
            padding-bottom: 50px;
            text-align: center;
        }
        .b-order__payment-result-status-error:before{
            display: block;
            content: '\f00d';
            font-family: 'FontAwesome';
            font-size: 70px;
            color: #e21836;
            text-align: center;
        }
    </style>

<? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); ?>