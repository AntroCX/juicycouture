<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("FAQ");

Jamilco\Blocks\Block::load(array('b-faq'));

?>

    <h1>FAQ</h1>
    <div class="b-faq">
        <div class="b-faq__questions">
            <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                <div class="panel panel-default">
                    <div class="panel-heading" role="tab" id="headingOne">
                        <h4 class="panel-title" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            Как я могу сделать заказ?
                        </h4>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
                        <div class="panel-body">
                            Добавьте понравившиеся товары в корзину и оформите заказ, заполнив все поля.
                            Если Вы не можете сделать заказ самостоятельно, Вы всегда можете позвонить по телефону 8 800 500 10 11. Наш консультант поможет приобрести товар и ответит на все Ваши вопросы.
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading" role="tab" id="headingTwo">
                        <h4 class="panel-title" class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            Зачем нужно указывать свой мобильный телефон?
                        </h4>
                    </div>
                    <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
                        <div class="panel-body">
                            Мобильный телефон необходим для того, чтобы наши консультанты могли с вами связаться и уточнить информацию по заказу. Уточнение адресных данных необходимо для корректной обработки Вашего заказа и доставки товаров по верному адресу.                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading" role="tab" id="headingThree">
                        <h4 class="panel-title" class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            Как оплатить заказанный товар?
                        </h4>
                    </div>
                    <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
                        <div class="panel-body">
                            Любой заказ, сделанный на нашем сайте, Вы можете оплатить при получении курьеру.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <h2 class="b-faq__form-title">Задай свой вопрос</h2>
        <form class="b-faq__form">
            <div class="form-group">
                <label for="exampleInputEmail1">Выберите тему</label>
                <div class="form-select">
                    <select class="form-control">
                        <option>Статус заказа</option>
                        <option>Доставка и оплата</option>
                        <option>Возврат</option>
                        <option>Работа в компании</option>
                        <option>Работа сайта</option>
                        <option>Сотрудничество</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="exampleInputEmail1">Введите вопрос</label>
                <textarea class="form-control"></textarea>
            </div>
            <div class="form-group">
                <label for="exampleInputEmail1">Представьтесь, пожалуйста</label>
                <input type="text" class="form-control">
            </div>
            <div class="form-group">
                <label for="exampleInputEmail1">Email</label>
                <input type="email" class="form-control">
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Задать вопрос">
                <div class="form-require">
                    <span class="pink">*</span> - поля обязательные для заполнения
                </div>
            </div>
        </form>
    </div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>