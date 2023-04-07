<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Покупайте с праздничным настроением!"); ?>
<style>
    ul.dashed {
        list-style-type: none;
        padding-left: 1em;
        margin: 8px 0;
    }
    
    ul.dashed li:before {
        content: "–";
        position: absolute;
        margin-left: -1em;
    }
    .sticky a img {
        width: 100%;
        height: auto;
    }
    
    ul.inline-block {
        margin: 0;
        padding: 0;
        display: block;
        list-style-image: none;
        text-align: center;
    }
    ul.inline-block li {
        display: inline-block;
        height: 150px;
        width: 150px;
    }
    ul.inline-block li::before {
        display: none !important;
    }
    ul.inline-block li a {
        border: none;
    }
</style>
 <h1>Покупайте с праздничным настроением!</h1>
<div class="container sticky">
    <div class="row">
        <div class="col-xs-12 col-md-6">
            <div>
                <div>
                    <p>
                        Специальное предложение для всех клиентов - скидка 10 % на следующий заказ в интернет-магазинах:</p>
                    <ul class="inline-block">
                        <li><a href="https://timberland.ru" target="_blanck"><img src="/new_year_2019/tim_mini.png"></a></li>
                        <li><a href="https://newbalance.ru" target="_blanck"><img src="/new_year_2019/nb_mini.png"></a></li>
                        <li><a href="https://marc-o-polo.ru" target="_blanck"><img src="/new_year_2019/mop_mini.png"></a></li>
                        <li><a href="https://dkny.ru" target="_blanck"><img src="/new_year_2019/dkny_mini.png"></a></li>
                        <li><a href="https://wolford.ru" target="_blanck"><img src="/new_year_2019/wol_mini.png"></a></li>
                        <li><a href="https://juicycouture.ru" target="_blanck"><img src="/new_year_2019/JC_mini.png"></a></li>
                    </ul>
                    <div class="title_text" style="text-align: justify;">
                        <p><b>Условия акции:</b></p>
                    </div>
                    <ul>
                        <li>Акция действует в период с 13 декабря 2018 года по 13 января 2019 года.</li>
                        <li>Для участия в акции необходимо оформить и выкупить заказ в период  с 13 декабря 2018 года по 13  января 2019 года.</li>
                        <li>Промокод со скидкой будет выслан в email-нотификации, после доставки заказа.</li>
                        <li>Промокод предоставляет скидку 10 % от общей стоимости товаров в корзине в интернет-магазинах, участвующих в акции.</li>
                        <li>Максимально возможная скидка по промокоду 5000 рублей.</li>
                        <li>Срок действия промокода до 20 января 2019 года включительно;</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-6">
                        <p><a href="https://timberland.ru" target="_blanck"><img src="/new_year_2019/tim.PNG"></a></p>
                        <p><a href="https://newbalance.ru" target="_blanck"><img src="/new_year_2019/NB.PNG"></a></p>
                        <p><a href="https://marc-o-polo.ru" target="_blanck"><img src="/new_year_2019/MoP.jpg"></a></p>
                        <p><a href="https://dkny.ru" target="_blanck"><img src="/new_year_2019/dkny.png"></a></p>
                        <p><a href="https://wolford.ru" target="_blanck"><img src="/new_year_2019/wol.png"></a></p>
                        <p><a href="https://juicycouture.ru" target="_blanck"><img src="/new_year_2019/jc.png"></a></p>
            
        </div>
    </div>
</div>
<? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); ?>
