<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("description", "Официальный интернет магазин Juicy Couture (Джуси Кутюр). -5% при оплате на сайте! Оригинальная продукция, купить одежду и аксессуары Juicy Couture.");
$APPLICATION->SetPageProperty("title", "Официальный интернет-магазин Juicy Couture (Джуси Кутюр) в России");
$APPLICATION->SetTitle("Официальный интернет магазин Juicycouture");
$APPLICATION->SetPageProperty('ddlPageType', 'home');
$APPLICATION->SetPageProperty('ddlPageCategory', 'Home');
Jamilco\Blocks\Block::load(array('b-mainpage-youstyle', 'b-mainpage-instagram', 'b-mainpage-video', 'b-seo'));

$GLOBALS['bannerFilter'] = ['!CODE' => 'site_closed'];
?><?$APPLICATION->IncludeComponent(
    "bitrix:news.list",
    "main-banner",
    Array(
        "ACTIVE_DATE_FORMAT" => "d.m.Y",
        "ADD_SECTIONS_CHAIN" => "N",
        "AJAX_MODE" => "N",
        "AJAX_OPTION_ADDITIONAL" => "",
        "AJAX_OPTION_HISTORY" => "N",
        "AJAX_OPTION_JUMP" => "N",
        "AJAX_OPTION_STYLE" => "Y",
        "CACHE_FILTER" => "Y",
        "CACHE_GROUPS" => "N",
        "CACHE_TIME" => "36000000",
        "CACHE_TYPE" => "A",
        "CHECK_DATES" => "Y",
        "DETAIL_URL" => "",
        "DISPLAY_BOTTOM_PAGER" => "N",
        "DISPLAY_DATE" => "N",
        "DISPLAY_NAME" => "Y",
        "DISPLAY_PICTURE" => "Y",
        "DISPLAY_PREVIEW_TEXT" => "N",
        "DISPLAY_TOP_PAGER" => "N",
        "FIELD_CODE" => array(0=>"NAME",1=>"PREVIEW_PICTURE",2=>"",),
        "FILTER_NAME" => "bannerFilter",
        "HIDE_LINK_WHEN_NO_DETAIL" => "N",
        "IBLOCK_ID" => "3",
        "IBLOCK_TYPE" => "periodic_content",
        "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
        "INCLUDE_SUBSECTIONS" => "N",
        "MESSAGE_404" => "",
        "NEWS_COUNT" => "10",
        "PAGER_BASE_LINK_ENABLE" => "N",
        "PAGER_DESC_NUMBERING" => "N",
        "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
        "PAGER_SHOW_ALL" => "N",
        "PAGER_SHOW_ALWAYS" => "N",
        "PAGER_TEMPLATE" => ".default",
        "PAGER_TITLE" => "Новости",
        "PARENT_SECTION" => "",
        "PARENT_SECTION_CODE" => "",
        "PREVIEW_TRUNCATE_LEN" => "",
        "PROPERTY_CODE" => [
            "BUTTONS",
            "BUTTON_TEXT_COLOR",
            "BUTTON_COLOR",
            "TITLE_TEXT",
            "TITLE_COLOR",
            "SUBTITLE_TEXT",
            "SUBTITLE_COLOR",
            "HREF",
            "TABLET",
            "MOBILE",
        ],
        "SET_BROWSER_TITLE" => "N",
        "SET_LAST_MODIFIED" => "N",
        "SET_META_DESCRIPTION" => "N",
        "SET_META_KEYWORDS" => "N",
        "SET_STATUS_404" => "N",
        "SET_TITLE" => "N",
        "SHOW_404" => "N",
        "SORT_BY1" => "SORT",
        "SORT_BY2" => "SORT",
        "SORT_ORDER1" => "ASC",
        "SORT_ORDER2" => "ASC"
    )
);?>
    <br><div data-retailrocket-markup-block="5bd1bca297a528207806f231" ></div>
<?

$APPLICATION->IncludeComponent(
    "bitrix:news.list",
    "whats-jc",
    Array(
        "ACTIVE_DATE_FORMAT" => "d.m.Y",
        "ADD_SECTIONS_CHAIN" => "N",
        "AJAX_MODE" => "N",
        "AJAX_OPTION_ADDITIONAL" => "",
        "AJAX_OPTION_HISTORY" => "N",
        "AJAX_OPTION_JUMP" => "N",
        "AJAX_OPTION_STYLE" => "Y",
        "CACHE_FILTER" => "N",
        "CACHE_GROUPS" => "Y",
        "CACHE_TIME" => "36000000",
        "CACHE_TYPE" => "A",
        "CHECK_DATES" => "Y",
        "DETAIL_URL" => "",
        "DISPLAY_BOTTOM_PAGER" => "Y",
        "DISPLAY_DATE" => "Y",
        "DISPLAY_NAME" => "Y",
        "DISPLAY_PICTURE" => "Y",
        "DISPLAY_PREVIEW_TEXT" => "Y",
        "DISPLAY_TOP_PAGER" => "N",
        "FIELD_CODE" => array(0=>"NAME",1=>"PREVIEW_TEXT",2=>"PREVIEW_PICTURE",3=>"",),
        "FILTER_NAME" => "",
        "HIDE_LINK_WHEN_NO_DETAIL" => "N",
        "IBLOCK_ID" => "4",
        "IBLOCK_TYPE" => "periodic_content",
        "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
        "INCLUDE_SUBSECTIONS" => "Y",
        "MESSAGE_404" => "",
        "NEWS_COUNT" => "4",
        "PAGER_BASE_LINK_ENABLE" => "N",
        "PAGER_DESC_NUMBERING" => "N",
        "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
        "PAGER_SHOW_ALL" => "N",
        "PAGER_SHOW_ALWAYS" => "N",
        "PAGER_TEMPLATE" => ".default",
        "PAGER_TITLE" => "Новости",
        "PARENT_SECTION" => "",
        "PARENT_SECTION_CODE" => "",
        "PREVIEW_TRUNCATE_LEN" => "",
        "PROPERTY_CODE" => array(0=>"BUTTON",1=>"",),
        "SET_BROWSER_TITLE" => "N",
        "SET_LAST_MODIFIED" => "N",
        "SET_META_DESCRIPTION" => "N",
        "SET_META_KEYWORDS" => "N",
        "SET_STATUS_404" => "N",
        "SET_TITLE" => "N",
        "SHOW_404" => "N",
        "SORT_BY1" => "SORT",
        "SORT_BY2" => "SORT",
        "SORT_ORDER1" => "ASC",
        "SORT_ORDER2" => "ASC"
    )
);

?>
    <?/*?>
    <div class="b-mainpage-youstyle hidden-xs" style="height:auto;">
        <div class="container">
            <a href="https://www.instagram.com/juicycouture_russia/" class="all-link">
                <img src="/images/JC-inst-09.jpg" alt="ссылка на instagram" title="ссылка на instagram">
            </a>
        </div>
    </div>

    <div class="b-mainpage-youstyle b-mainpage-youstyle_mobile visible-xs">

    </div>
    <div class="b-mainpage-video">
        <div class="container">
            <h4 class="b-mainpage-video__title">#IMSOJUICY</h4>
            <div class="b-mainpage-video__description">
                <!--THISISJUICY-->
            </div>
            <div class="b-mainpage-video__youtube">
                <div class="video_box">
                    <video loop="" style="height: auto; max-width: 100%;" id="video_box_video">
                        <source src="/images/JC_CR_06.mp4" type="video/mp4">
                    </video>
                    <div class="video_box_button"></div>
                </div>
            </div>

        </div>
    </div>

    <style>
        .video_box{position: relative;}
        .video_box_button{
            background-image: url('/images/play.png');
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-repeat: no-repeat;
            background-size: 25%;
            background-position: center center;
            opacity: 0.7;
            transition: 0.5s;
        }
        .video_box_button:hover{opacity: 1;cursor: pointer;}
        .video_box_button_pause{background-image: url('/images/pause.png');opacity: 0;}
    </style>

    <script>
        $(document).ready(function(){

            $('.video_box_button').click(function() {
                var videoEl = document.getElementById('video_box_video');
                if (videoEl.paused) {
                    videoEl.play();
                    $(this).addClass('video_box_button_pause');
                } else {
                    videoEl.pause();
                    $(this).removeClass('video_box_button_pause');
                }
            })
        });
    </script>
    <?*/?>
    <!-- открываем контейнер -->

    <div class="container">
        <?/*?>
        <div class="b-mainpage-instagram">
            <h4 class="b-mainpage-instagram__title">#IMSOJUICY</h4>
            <div class="b-mainpage-instagram__description">Следи за нами в Instagram @juicycouture_russia</div>
            <?$APPLICATION->IncludeComponent(
                "innova:instagram",
                ".default",
                array(
                    "AUTOPLAY" => "false",
                    "AUTOPLAY_SPEED" => "3000",
                    "CACHE_TYPE" => "A",
                    "COUNT_COLS_SLIDE" => "1",
                    "COUNT_IMAGE" => "9",
                    "GRID_CLICK" => "img",
                    "GRID_MAX_WIDTH" => "400",
                    "REFRESH_TIME" => "360",
                    "SPEED" => "500",
                    "TOKEN" => "IGQVJVS2JtRUdhcUNDZA3lHX3B0cUEwZA1JsSVJ2cE5YcGVuQTVyaENLMFM4TlNXZA19XOW1YOU5aWWV6LVp5cXl1OE1NanRqUVJkY3d5WjUyOWM1cjBuQnlZAcmhzZAWppeWN3c3F5U2lSSWZAnNm9hSXJtbQZDZD",
                    "VIEW_TYPE" => "grid_slider",
                    "COMPONENT_TEMPLATE" => ".default"
                ),
                false
            );?>
        </div>
        <?*/?>
        <?/*?>
        <div class="b-seo text-center">
            <h5 class="b-seo__title">Juicy Couture</h5>
            <div class="b-seo__text">
                <p>
                    Легендарный калифорнийский бренд, в одежде и аксессуарах которого притягательным образом сочетаются дерзость, женственность и абсолютная бескомпромиссность для настоящей, уверенной в себе модницы.

                </p>
                <p>
                    Сайт JuicyCouture.ru позволит Вам без труда совершать покупки и подобрать модный гардероб в духе последних тенденций, чтобы всегда выглядеть восхитительно, свежо, кокетливо и нескучно! Начните шопинг, подобрав что-то из многообразия категорий нашей продукции. На сегодняшний день это женская одежда, костюмы в спортивном стиле, бижутерия и аксессуары, сумки и многое другое, одним словом все, во что можно влюбиться без оглядки!
                </p>
            </div>
        </div>
        <?*/?>
    </div>
<?$APPLICATION->IncludeComponent(
    "bitrix:news.list",
    "whats-jc",
    Array(
        "TEXT_LEFT" => "Y",
        "ACTIVE_DATE_FORMAT" => "d.m.Y",
        "ADD_SECTIONS_CHAIN" => "N",
        "AJAX_MODE" => "N",
        "AJAX_OPTION_ADDITIONAL" => "",
        "AJAX_OPTION_HISTORY" => "N",
        "AJAX_OPTION_JUMP" => "N",
        "AJAX_OPTION_STYLE" => "Y",
        "CACHE_FILTER" => "N",
        "CACHE_GROUPS" => "Y",
        "CACHE_TIME" => "36000000",
        "CACHE_TYPE" => "A",
        "CHECK_DATES" => "Y",
        "DETAIL_URL" => "",
        "DISPLAY_BOTTOM_PAGER" => "Y",
        "DISPLAY_DATE" => "Y",
        "DISPLAY_NAME" => "Y",
        "DISPLAY_PICTURE" => "Y",
        "DISPLAY_PREVIEW_TEXT" => "Y",
        "DISPLAY_TOP_PAGER" => "N",
        "FIELD_CODE" => array(0=>"NAME",1=>"PREVIEW_TEXT",2=>"PREVIEW_PICTURE",3=>"",),
        "FILTER_NAME" => "",
        "HIDE_LINK_WHEN_NO_DETAIL" => "N",
        "IBLOCK_ID" => "30",
        "IBLOCK_TYPE" => "periodic_content",
        "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
        "INCLUDE_SUBSECTIONS" => "Y",
        "MESSAGE_404" => "",
        "NEWS_COUNT" => "4",
        "PAGER_BASE_LINK_ENABLE" => "N",
        "PAGER_DESC_NUMBERING" => "N",
        "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
        "PAGER_SHOW_ALL" => "N",
        "PAGER_SHOW_ALWAYS" => "N",
        "PAGER_TEMPLATE" => ".default",
        "PAGER_TITLE" => "Новости",
        "PARENT_SECTION" => "",
        "PARENT_SECTION_CODE" => "",
        "PREVIEW_TRUNCATE_LEN" => "",
        "PROPERTY_CODE" => array(0=>"BUTTON",1=>"",),
        "SET_BROWSER_TITLE" => "N",
        "SET_LAST_MODIFIED" => "N",
        "SET_META_DESCRIPTION" => "N",
        "SET_META_KEYWORDS" => "N",
        "SET_STATUS_404" => "N",
        "SET_TITLE" => "N",
        "SHOW_404" => "N",
        "SORT_BY1" => "SORT",
        "SORT_BY2" => "SORT",
        "SORT_ORDER1" => "ASC",
        "SORT_ORDER2" => "ASC"
    )
);?>


<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "WebSite",
    "url": "http://www.juicycouture.ru/",
    "potentialAction": {
    "@type": "SearchAction",
    "target": "http://www.juicycouture.ru/search/?q={search_term}",
    "query-input": "required name=search_term" }
}
</script>
<div data-retailrocket-markup-block="5bd1bc9a97a528207806f230" ></div>
<? \Adspire\Manager::getInstance()->setContainerElement(['push' => ['TypeOfPage' => 'general']]); ?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
