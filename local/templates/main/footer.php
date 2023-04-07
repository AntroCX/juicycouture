<?$APPLICATION->IncludeComponent(
    "bitrix:main.include",
    "seo",
    Array(
        "AREA_FILE_SHOW"   => "sect",
        "AREA_FILE_SUFFIX" => "seo_post",
        "EDIT_TEMPLATE"    => "",
        "PLACE"            => "footer",
    )
);?>

<?if($APPLICATION->GetCurPage() != '/' && !$GLOBALS['JC_SITE_CLOSED']):?>
    </div>
<?endif?>
</div>

<?
$is_main_page = ($APPLICATION->GetCurPage(false) == '/' || $GLOBALS['JC_SITE_CLOSED'])  ? 1 : 0;
?>
<?
//canonical
$serverParams = \Bitrix\Main\Application::getInstance()->getContext()->getServer()->toArray();
if(http_response_code() != 404 && strpos($serverParams['REAL_FILE_PATH'], 'taggedpages.php') === false) {
    Bitrix\Main\Loader::includeModule('jamilco.taggedpages');
    $rsTaggedPages = Jamilco\TaggedPages\PagesTable::getList(
        array(
            'filter' => array(
                'RULE_URL' => $GLOBALS['APPLICATION']->GetCurPage(false)
            ),
            'limit' => 1
        )
    );
    if ($arTaggedPage = $rsTaggedPages->Fetch()) {
        $str = $arTaggedPage["URL"];
        if($arTaggedPage['SEO_TITLE']) {
            $GLOBALS['APPLICATION']->SetPageProperty("title", $arTaggedPage['SEO_TITLE']);
            $GLOBALS['APPLICATION']->SetPageProperty("og:title", $arTaggedPage['SEO_TITLE']);
        }
        if($arTaggedPage['SEO_KEYWORDS'])
            $GLOBALS['APPLICATION']->SetPageProperty("keywords", $arTaggedPage['SEO_KEYWORDS']);
        if($arTaggedPage['SEO_DESCRIPTION'])
            $GLOBALS['APPLICATION']->SetPageProperty("description", $arTaggedPage['SEO_DESCRIPTION']);
    }
    else{
        $str = str_replace('index.php', '', $APPLICATION->GetCurPage(true));
    }
    $GLOBALS['APPLICATION']->AddHeadString('<link rel="canonical" href="'.$serverParams['REQUEST_SCHEME'].'://'.$serverParams['SERVER_NAME'].$str.'" />');
}
?>

<? if ($GLOBALS['APPLICATION']->GetDirProperty('minimalFooter') == 'Y' || $GLOBALS['JC_SITE_CLOSED']): ?>
    <footer class="b-page__footer-minimal">
        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <div class="b-page__footer-copyright"></div>
                </div>
            </div>
        </div>
    </footer>
    <script>
      $(function(){
        var d = new Date();
        var y = d.getFullYear();
        $('.b-page__footer-copyright').html('© '+ y + ' АО «МФК Джамилько»');
      });
    </script>
<? else: ?>
<footer class="b-page__footer">
    <div class="container">
        <div class="b-page__footer-wrapper">
            <div class="row">
                <div class="col-sm-3 col-md-3">
                    <ul class="b-page__footer-menu">
                        <li class="b-page__footer-menu-category"><a href="#footer_1" aria-expanded="false">Заказы</a></li>
                        <?$APPLICATION->IncludeComponent("bitrix:menu", "footer_sec", Array(
                            "ALLOW_MULTI_SELECT" => "N",	// Разрешить несколько активных пунктов одновременно
                                "CHILD_MENU_TYPE" => "footer_sub",	// Тип меню для остальных уровней
                                "DELAY" => "N",	// Откладывать выполнение шаблона меню
                                "MAX_LEVEL" => "2",	// Уровень вложенности меню
                                "MENU_CACHE_GET_VARS" => array(	// Значимые переменные запроса
                                    0 => "",
                                ),
                                "MENU_CACHE_TIME" => "10800",	// Время кеширования (сек.)
                                "MENU_CACHE_TYPE" => "Y",	// Тип кеширования
                                "MENU_CACHE_USE_GROUPS" => "Y",	// Учитывать права доступа
                                "ROOT_MENU_TYPE" => "footer_1",	// Тип меню для первого уровня
                                "USE_EXT" => "Y",	// Подключать файлы с именами вида .тип_меню.menu_ext.php
                            ),
                            false
                        );?>
                    </ul>
                </div>
                <div class="col-sm-3 col-md-3">
                    <ul class="b-page__footer-menu">
                        <li class="b-page__footer-menu-category"><a href="#footer_2" aria-expanded="false">Справка</a></li>
                        <?$APPLICATION->IncludeComponent(
                            "bitrix:menu",
                            "footer_sec",
                            array(
                                "ALLOW_MULTI_SELECT" => "N",
                                "CHILD_MENU_TYPE" => "footer_sub",
                                "DELAY" => "N",
                                "MAX_LEVEL" => "2",
                                "MENU_CACHE_GET_VARS" => array(
                                ),
                                "MENU_CACHE_TIME" => "10800",
                                "MENU_CACHE_TYPE" => "Y",
                                "MENU_CACHE_USE_GROUPS" => "Y",
                                "ROOT_MENU_TYPE" => "footer_2",
                                "USE_EXT" => "Y",
                                "COMPONENT_TEMPLATE" => "footer_sec"
                            ),
                            false
                        );?>
                    </ul>
                </div>
                <div class="col-sm-3 col-md-3">
                    <ul class="b-page__footer-menu">
                        <li class="b-page__footer-menu-category"><a href="#footer_3" aria-expanded="false">О Juicy Couture</a></li>
                        <?$APPLICATION->IncludeComponent("bitrix:menu", "footer_sec", Array(
                            "ALLOW_MULTI_SELECT" => "N",	// Разрешить несколько активных пунктов одновременно
                            "CHILD_MENU_TYPE" => "footer_sub",	// Тип меню для остальных уровней
                            "DELAY" => "N",	// Откладывать выполнение шаблона меню
                            "MAX_LEVEL" => "2",	// Уровень вложенности меню
                            "MENU_CACHE_GET_VARS" => array(	// Значимые переменные запроса
                                0 => "",
                            ),
                            "MENU_CACHE_TIME" => "10800",	// Время кеширования (сек.)
                            "MENU_CACHE_TYPE" => "Y",	// Тип кеширования
                            "MENU_CACHE_USE_GROUPS" => "Y",	// Учитывать права доступа
                            "ROOT_MENU_TYPE" => "footer_3",	// Тип меню для первого уровня
                            "USE_EXT" => "Y",	// Подключать файлы с именами вида .тип_меню.menu_ext.php
                        ),
                            false
                        );?>
                    </ul>
                </div>
                <div class="col-sm-12 col-md-3">
                    <div class="b-subscription">
                        <div class="b-subscription__title">Подписаться на новостную рассылку</div>
                        <div class="b-subscription__reg_title">Зарегистрируйтесь сейчас!</div>
                        <?$APPLICATION->IncludeComponent("jamilco:subscribe.form", "footer", Array(
                            "AJAX_MODE" => "Y",	// Включить режим AJAX
                            "AJAX_OPTION_ADDITIONAL" => "",	// Дополнительный идентификатор
                            "AJAX_OPTION_HISTORY" => "N",	// Включить эмуляцию навигации браузера
                            "AJAX_OPTION_JUMP" => "N",	// Включить прокрутку к началу компонента
                            "AJAX_OPTION_STYLE" => "N",	// Включить подгрузку стилей
                            "CACHE_TIME" => "3600",	// Время кеширования (сек.)
                            "CACHE_TYPE" => "N",	// Тип кеширования
                            "CONFIRMATION" => "N",	// Запрашивать подтверждение подписки по email
                            "SET_TITLE" => "N",	// Устанавливать заголовок страницы
                            "SHOW_HIDDEN" => "N",	// Показать скрытые рассылки для подписки
                            "USE_PERSONALIZATION" => "Y",	// Определять подписку текущего пользователя
                        ),
                            false
                        );?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
<? endif ?>
</div>

<!-- subscribe pop-up -->
<form class="b-subscribe" name="SUBSCRIBE" action="">
    <div class="b-subscribe__close"></div>
    <div class="b-subscribe__title">Подписка на рассылку</div>
    <div class="b-subscribe__description">Получите эксклюзивные предложения и сообщения от JuicyCouture.</div>
    <div class="b-subscribe__inputs-wrapper">
        <div class="b-subscribe__input-wrapper">
            <div class="form-group">
                <input type="email" name="SUBSCRIBE_EMAIL" class="form-control required" placeholder="Электронный адрес">
            </div>
            <div class="checkbox" style="font-size:14px;">
                <label>
                    <input type="checkbox" name="SUBSCRIBE_AGREE" class="required"><span></span> я согласен с условиями <a href="/reference/contract-offer/" target="_blank">публичной оферты и обработкой моих персональных данных в порядке, предусмотренном публичной офертой</a>
                </label>
            </div>
        </div>
        <div class="b-subscribe__btn-wrapper">
            <button type="submit" class="btn btn-primary">Подписка</button>
        </div>
    </div>
    <div class="b-subscribe__result-html">

    </div>
</form>
<!-- !subscribe pop-up -->

<?$APPLICATION->IncludeComponent(
    "bitrix:sale.location.selector.search",
    "city-popup",
    Array(
        "COMPONENT_TEMPLATE" => ".default",
        "ID" => $_COOKIE['city_id'] ?: DEFAULT_CITY_ID,
        "CODE" => "",
        "INPUT_NAME" => "LOCATION",
        "PROVIDE_LINK_BY" => "id",
        "JSCONTROL_GLOBAL_ID" => "",
        "JS_CALLBACK" => "",
        "FILTER_BY_SITE" => "Y",
        "SHOW_DEFAULT_LOCATIONS" => "Y",
        "CACHE_TYPE" => "A",
        "CACHE_TIME" => "36000000",
        "FILTER_SITE_ID" => "s1",
        "INITIALIZE_BY_GLOBAL_EVENT" => "",
        "SUPPRESS_ERRORS" => "N"
    )
);?>

<div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel">
    <div class="modal-dialog" role="document">
        <form class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                <h3></h3>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <a type="button" data-dismiss="modal" aria-label="Close" class="btn btn-primary">ОК</a>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="js-popup-error" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel">
    <div class="modal-dialog" role="document">
        <form class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                <h3></h3>
            </div>
            <div class="modal-body">
                <p>Произошла ошибка при оформлении заказа.</p>
            </div>
            <div class="modal-footer">
                <a type="button" data-dismiss="modal" aria-label="Close" class="btn btn-primary">ОК</a>
            </div>
        </form>
    </div>
</div>
<?$APPLICATION->IncludeComponent(
    "bitrix:main.include",
    "",
    Array(
        "AREA_FILE_SHOW" => "file",
        "AREA_FILE_SUFFIX" => "inc",
        "EDIT_TEMPLATE" => "",
        "PATH" => "/local/includes/footer_cookie.php"
    )
);?>

<script>
    $(document).ready(function() {
        //let cookieDate = localStorage.getItem('cookieDate');
        let cookieDate =$.cookie('cookieDate');
        let cookieNotification = $('.js-cookie_notification');
        let cookieBtnAccept = cookieNotification.find('.js-cookie_accept');
        let cookieBtnClose = cookieNotification.find('.js-cookie_close');
// if cookie acept missing - show notification
        if( !cookieDate){
            cookieNotification.addClass('show');
        }
// cookie accept btn click
        cookieBtnAccept.click(function() {
            //localStorage.setItem( 'cookieDate', Date.now() );
            $.cookie('cookieDate', true, { expires: 7, path: '/' });
            cookieNotification.removeClass('show');
        })
        cookieBtnClose.click(function() {
            cookieNotification.removeClass('show');
        })

    });
</script>
<?if(substr_count($APPLICATION->GetCurDir(),"/new/") || substr_count($APPLICATION->GetCurDir(),"/brand/") || substr_count($APPLICATION->GetCurDir(),"/catalog/") || substr_count($APPLICATION->GetCurDir(),"/sale/")){?>
    <?/** Button up */?>
    <div id="button-up"></div>
<?}?>

<?php
/** DigitalDataLayer start */
$ddm = \DigitalDataLayer\Manager::getInstance();

$ddm->fillData();

/** Добавление события входа или регистрации пользователя, если оно было */
echo $ddm->getContent(['onLoginEvent', 'onRegisterEvent']);

/** DigitalDataLayer end */
?>

<?php
/** Adspire start */
$adspire = \Adspire\Manager::getInstance();
echo $adspire->showMainScript();
echo $adspire->getContainer();
/** Adspire end */
?>

<? \Oneway\FooterAsset::show(); ?>

</body>
</html>