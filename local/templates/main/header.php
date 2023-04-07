<?

use Juicycouture\Google\ReCaptcha\ReCaptchaAsset;

$assets = \Bitrix\Main\Page\Asset::getInstance();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?$GLOBALS['APPLICATION']->ShowTitle()?></title>


    <?$GLOBALS['APPLICATION']->AddHeadString('<link rel="apple-touch-icon" sizes="57x57" href="/apple-icon-57x57.png">')?>
    <?$GLOBALS['APPLICATION']->AddHeadString('<link rel="apple-touch-icon" sizes="60x60" href="/apple-icon-60x60.png">')?>
    <?$GLOBALS['APPLICATION']->AddHeadString('<link rel="apple-touch-icon" sizes="72x72" href="/apple-icon-72x72.png">')?>
    <?$GLOBALS['APPLICATION']->AddHeadString('<link rel="apple-touch-icon" sizes="76x76" href="/apple-icon-76x76.png">')?>
    <?$GLOBALS['APPLICATION']->AddHeadString('<link rel="apple-touch-icon" sizes="114x114" href="/apple-icon-114x114.png">')?>
    <?$GLOBALS['APPLICATION']->AddHeadString('<link rel="apple-touch-icon" sizes="120x120" href="/apple-icon-120x120.png">')?>
    <?$GLOBALS['APPLICATION']->AddHeadString('<link rel="apple-touch-icon" sizes="144x144" href="/apple-icon-144x144.png">')?>
    <?$GLOBALS['APPLICATION']->AddHeadString('<link rel="apple-touch-icon" sizes="152x152" href="/apple-icon-152x152.png">')?>
    <?$GLOBALS['APPLICATION']->AddHeadString('<link rel="apple-touch-icon" sizes="180x180" href="/apple-icon-180x180.png">')?>
    <?$GLOBALS['APPLICATION']->AddHeadString('<link rel="icon" type="image/png" sizes="192x192"  href="/android-icon-192x192.png">')?>
    <?$GLOBALS['APPLICATION']->AddHeadString('<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">')?>
    <?$GLOBALS['APPLICATION']->AddHeadString('<link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">')?>
    <?$GLOBALS['APPLICATION']->AddHeadString('<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">')?>
    <?$assets->addJs('/local/templates/.default/scripts/vendor/swiper.js');?>
    <?$assets->addJs('/local/templates/.default/scripts/main.bundle.js');?>
    <?php $assets->addJs('/local/templates/.default/scripts/general.js'); ?>


    <?php
    /** DigitalDataLayer start */
    \DigitalDataLayer\Manager::getInstance()->showData();
    \DigitalDataLayer\Manager::getInstance()->showSnippet();
    /** DigitalDataLayer end */
    ?>

    <?php
    /** Adspire start */
    \Adspire\Manager::getInstance()->setContainerElement(['push' => ['TypeOfPage' => 'other']]);
    /** Adspire end */
    ?>

    <?
    Jamilco\Blocks\Block::load(array(
        'i-jquery',
        'g-twbs',
        'i-validate',
        'i-slick',
        'i-cookie',
        'i-mask',
        'i-mobile',
        'b-page',
        'b-subscribe',
        'b-header',
        'b-subscription',
        'b-social-links',
        'b-city-auto-popup',
        'b-city-popup',
        'b-fast-cart',
        'b-breadcrumb',
        'b-media',
    ));

    $arLoc = \Jamilco\Delivery\Location::getCurrentLocation();
    ?>
    <?$GLOBALS['APPLICATION']->AddHeadString('<link rel="stylesheet" type="text/css" href="/local/blocks/g-font-awesome/g-font-awesome.css">')?>
    <?$GLOBALS['APPLICATION']->SetAdditionalCSS('/local/fonts/google_fonts.css')?>
    <?$GLOBALS['APPLICATION']->SetAdditionalCSS('/local/templates/main/styles/main.css')?>
    <?$GLOBALS['APPLICATION']->SetAdditionalCSS('/local/templates/main/styles/main.css')?>
    <?$assets->addCss('/local/templates/.default/styles/vendor/swiper-bundle.min.css');?>
    <?$assets->addCss('/local/templates/.default/styles/main.css');?>
    <?$GLOBALS['APPLICATION']->ShowHead()?>

    <?
    $assets->addJs(ReCaptchaAsset::getScriptPath());
    ReCaptchaAsset::addJs();
    ?>

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','GTM-M2WJQNZ');</script>
    <!-- End Google Tag Manager -->
    
    <!-- Chatra {literal} -->
    <?/*?>
    <script>
        (function(d, w, c) {
            w.ChatraID = 'TwAhe9d9iRqGaPght';
            var s = d.createElement('script');
            w[c] = w[c] || function() {
                (w[c].q = w[c].q || []).push(arguments);
            };
            s.async = true;
            s.src = 'https://call.chatra.io/chatra.js';
            if (d.head) d.head.appendChild(s);
        })(document, window, 'Chatra');
    </script>
    <!-- /Chatra {/literal} -->
    <?*/?>
    <script type="text/javascript" data-skip-moving="true">
        var rrPartnerId = "5bae217d97a5251900824910";
        var rrApi = {};
        var rrApiOnReady = rrApiOnReady || [];
        rrApi.addToBasket = rrApi.order = rrApi.categoryView = rrApi.view =
            rrApi.recomMouseDown = rrApi.recomAddToCart = function() {};
        (function(d) {
            var ref = d.getElementsByTagName('script')[0];
            var apiJs, apiJsId = 'rrApi-jssdk';
            if (d.getElementById(apiJsId)) return;
            apiJs = d.createElement('script');
            apiJs.id = apiJsId;
            apiJs.async = true;
            apiJs.src = "//cdn.retailrocket.ru/content/javascript/tracking.js";
            ref.parentNode.insertBefore(apiJs, ref);
        }(document));
    </script>
</head>
<body class="b-page">
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-M2WJQNZ"
                  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<?$GLOBALS['APPLICATION']->ShowPanel()?>
<div class="b-page__content">
    <div class="b-page__wrapper">
        <? //информационный баннер об изменении времени работы на НГ
        $now = time();
        $startTime = strtotime("21-04-2021"); //дата начала показа сообщения
        $endTime = strtotime("25-04-2021"); //с этой даты показываться не будет
        if (($now > $startTime && $now < $endTime) || $_GET["ny"] == "y"){?>

            <style>
                .ny-warn{
                    padding: 10px 50px;
                    color: white;
                    background: black;
                    font-size: 15px;
                    text-align: center;
                }
            </style>
            <div class="ny-warn"></div>
            <script>
                $(function () {
                    if ($(".bx-core").hasClass("bx-touch")){
                        $(".ny-warn").css({"fontSize" : "13px"});
                        $(".ny-warn").html("Уважаемые клиенты! В связи с большим количеством заказов, в период с 21.04.21 по 24.04.21, сроки доставки могут быть увеличены.");
                    } else {
                        $(".ny-warn").html("Уважаемые клиенты! В связи с большим количеством заказов, в период с 21.04.21 по 24.04.21, сроки доставки могут быть увеличены.");
                    }
                })
            </script>
        
        <?php /** DigitalDataLayer class="ddl_campaign" data-campaign-id="free-delivery", class="ddl_campaign_link" */?>
        <?/*
        <div class="b-page__banner ddl_campaign" data-campaign-id="free-delivery">
            <?$APPLICATION->IncludeFile(SITE_DIR . "/local/includes/information-text.php", array(), array("MODE" => "html","NAME" => "информационный текст","TEMPLATE" => "standard_inc.php"));?>
        </div>
        */?>
        <?php
        /** DigitalDataLayer start */
        // ! Если будет удалена/изменена кампания бесплатная доставка 10000 рублей, то нужно удалить/изменить и этот код
        $ddm = \DigitalDataLayer\Manager::getInstance();
        $digitalData = $ddm->getData();
        $digitalData->add('campaigns', [
            'id' => 'free-delivery',
            'name' => 'Free delivery',
            'description' => 'Бесплатная доставка заказов на сумму свыше 10000р',
            'category' => 'Juicy Couture',
            'subcategory' => 'topnavbanner',
            'design' => 'Черный баннер',
            'position' => 'Верх сайта по центру'
        ]);
        /** DigitalDatalayer end */
        ?>
        <?}?>
        <? if (CModule::IncludeModule('jamilco.omni') && CModule::IncludeModule('iblock') && $arShop = Jamilco\Omni\Tablet::getCurrentShopData()) { ?>
            <div class="b-page__banner">
                <?= $arShop['NAME'] ?>. <?= $arShop['ADDRESS'] ?>
            </div>
        <? } ?>
        <header class="b-header">
            <div class="b-header__top">
                <div class="container">
                    <div class="row b-header__top__container_wr">
                        <div class="col-xs-2 col-sm-4 col-lg-3 hidden-xs hidden-sm">
                            <div class="b-header__top-location hidden-xs hidden-sm">
                                <div class="row">
                                    <div class="col-xs-6 hide-in-order">
                                        <span class="b-header__top-location-city" data-toggle="modal" data-target="#b-city-popup">
                                            <?= $arLoc['NAME_RU'] ?>
                                        </span>
                                    </div>
                                    <?/*
                                    <div class="col-xs-6 b-header__top-location-delimiter">
                                        <a href="/stores/">Карта магазинов</a>
                                    </div>
                                    */?>
                                </div>
                                <? if($_COOKIE['city_user'] != 'Y') { ?>
                                    <div class="b-city-auto-popup b-city-auto-popup_hide">
                                        <a class="b-city-auto-popup__close"></a>
                                        <div class="b-city-auto-popup__you-city">Ваш город <span class="b-city-auto-popup__you-city-name" data-id="<?=$arLoc['ID']?>"><?= $arLoc['NAME_RU'] ?></span>?</div>
                                        <div class="b-city-auto-popup__select">
                                            <button class="b-city-auto-popup__select-yes">Да</button>
                                            <button data-toggle="modal" data-target="#b-city-popup">Выбрать другой город</button>
                                        </div>
                                        <div class="b-city-auto-popup__description">От выбранного города зависит стоимость доставки и наличие товара</div>
                                    </div>
                                <? } ?>
                            </div>
                            <div class="row">
                                <div class="col-xs-6 hide-in-order">
                                  <div class="b-header__top-phone-cc text-center">8 800 770 76 46</div>
                                </div>
                                <div class="col-xs-6">
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-6 col-sm-4 col-lg-6">
                            <a class="b-header__top-logo" href="/"></a>
                        </div>
                        <div class="col-xs-4 col-sm-4 col-lg-3 mobile-menu-padding">
                            <?$APPLICATION->IncludeComponent("bitrix:sale.basket.basket.line","main",Array(
                                    "HIDE_ON_BASKET_PAGES" => "Y",
                                    "PATH_TO_BASKET" => SITE_DIR."order/",
                                    "PATH_TO_ORDER" => SITE_DIR."order/",
                                    "PATH_TO_PERSONAL" => SITE_DIR."personal/",
                                    "PATH_TO_PROFILE" => SITE_DIR."personal/",
                                    "PATH_TO_REGISTER" => SITE_DIR."personal/",
                                    "POSITION_FIXED" => "Y",
                                    "POSITION_HORIZONTAL" => "right",
                                    "POSITION_VERTICAL" => "top",
                                    "SHOW_AUTHOR" => "Y",
                                    "SHOW_DELAY" => "N",
                                    "SHOW_EMPTY_VALUES" => "Y",
                                    "SHOW_IMAGE" => "N",
                                    "SHOW_NOTAVAIL" => "N",
                                    "SHOW_NUM_PRODUCTS" => "Y",
                                    "SHOW_PERSONAL_LINK" => "N",
                                    "SHOW_PRICE" => "Y",
                                    "SHOW_PRODUCTS" => "Y",
                                    "SHOW_SUBSCRIBE" => "Y",
                                    "SHOW_SUMMARY" => "Y",
                                    "SHOW_TOTAL_PRICE" => "Y"
                                )
                            );?>
                        </div>
                        <div class="col-xs-2 col-sm-4 col-lg-3 visible-xs visible-sm hidden-md hidden-lg">
                           <?if(!$GLOBALS['JC_SITE_CLOSED']){?>
                            <div class="b-header__mobile-btn visible-xs visible-sm">
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                                <span class="fa fa-times" aria-hidden="true"></span>
                            </div>
                            <?}?>
                        </div>
                    </div>
                </div>
            </div>
            <??>
            <div class="b-header__menu hidden-xs hidden-sm">
                <div class="container">
                    <a class="b-header__menu-logo" href="/"></a>

                    <?$APPLICATION->IncludeComponent("bitrix:menu", "main", Array(
                        "ALLOW_MULTI_SELECT" => "Y",	// Разрешить несколько активных пунктов одновременно
                        "CHILD_MENU_TYPE" => "top",	// Тип меню для остальных уровней
                        "DELAY" => "N",	// Откладывать выполнение шаблона меню
                        "MAX_LEVEL" => "3",	// Уровень вложенности меню
                        "MENU_CACHE_GET_VARS" => "",	// Значимые переменные запроса
                        "MENU_CACHE_TIME" => "10800",	// Время кеширования (сек.)
                        "MENU_CACHE_TYPE" => "Y",	// Тип кеширования
                        "MENU_CACHE_USE_GROUPS" => "Y",	// Учитывать права доступа
                        "ROOT_MENU_TYPE" => $GLOBALS['JC_SITE_CLOSED']? "top_site_closed": "top",	// Тип меню для первого уровня
                        "USE_EXT" => $GLOBALS['JC_SITE_CLOSED']? "N": "Y",	// Подключать файлы с именами вида .тип_меню.menu_ext.php
                    ),
                        false
                    );?>

                </div>
                <div class="container b-header__menu-add-container">
                    <div class="b-header__menu-add">
                        <div id="basketBlockClone"></div>
                    </div>
                </div>
            </div>
<??>
            <form class="b-header__search-block" action="/search/">
                <div class="container">
                    <div class="form-group">
                        <input type="text" name="q" value="<?=$_REQUEST['q']?>" class="form-control" placeholder="Поиск" autocomplete="off">

                        <div class="search-dropdown" style="display: none;"></div>
                    </div>
                </div>
            </form>
            <?$APPLICATION->IncludeComponent("bitrix:menu", "main-mobile", Array(
                "ALLOW_MULTI_SELECT" => "N",	// Разрешить несколько активных пунктов одновременно
                "CHILD_MENU_TYPE" => "left",	// Тип меню для остальных уровней
                "DELAY" => "N",	// Откладывать выполнение шаблона меню
                "MAX_LEVEL" => "2",	// Уровень вложенности меню
                "MENU_CACHE_GET_VARS" => array(	// Значимые переменные запроса
                    0 => "",
                ),
                "MENU_CACHE_TIME" => "10800",	// Время кеширования (сек.)
                "MENU_CACHE_TYPE" => "Y",	// Тип кеширования
                "MENU_CACHE_USE_GROUPS" => "Y",	// Учитывать права доступа
                "ROOT_MENU_TYPE" => "top",	// Тип меню для первого уровня
                "USE_EXT" => "Y",	// Подключать файлы с именами вида .тип_меню.menu_ext.php
            ),
                false
            );?>
        </header>
<?if(!$GLOBALS['JC_SITE_CLOSED']){?>
<?$APPLICATION->IncludeComponent(
    'jamilco:header.info_panel',
    '',
    [
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => 999999999
    ]
)
?>
<?}?>
        <?if($GLOBALS['APPLICATION']->GetCurPage() != '/' && !$GLOBALS['JC_SITE_CLOSED']):?>
        <?$APPLICATION->IncludeComponent("bitrix:breadcrumb", "main", Array(
            "PATH" => "",	// Путь, для которого будет построена навигационная цепочка (по умолчанию, текущий путь)
            "SITE_ID" => "s1",	// Cайт (устанавливается в случае многосайтовой версии, когда DOCUMENT_ROOT у сайтов разный)
            "START_FROM" => "0",	// Номер пункта, начиная с которого будет построена навигационная цепочка
        ),
            false
        );?>
        <div class="container">
            <?endif?>

            <?$APPLICATION->IncludeComponent(
                "bitrix:main.include",
                "seo",
                Array(
                    "AREA_FILE_SHOW"   => "sect",
                    "AREA_FILE_SUFFIX" => "seo_pre",
                    "EDIT_TEMPLATE"    => "",
                    "PLACE"            => "header",
                )
            );?>