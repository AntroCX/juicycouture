<?
$GLOBALS['JC_SITE_CLOSED'] = 1;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("robots", "noindex, nofollow");
$APPLICATION->SetPageProperty("description", "Официальный интернет магазин Juicy Couture (Джуси Кутюр). -5% при оплате на сайте! Оригинальная продукция, купить одежду и аксессуары Juicy Couture.");
$APPLICATION->SetPageProperty("title", "Официальный интернет-магазин Juicy Couture (Джуси Кутюр) в России");
$APPLICATION->SetTitle("Официальный интернет магазин Juicycouture. По техническим причинам сайт временно недоступен.");
$APPLICATION->SetPageProperty('ddlPageType', 'home');
$APPLICATION->SetPageProperty('ddlPageCategory', 'Home');

$GLOBALS['bannerFilter'] = ['CODE' => 'site_closed'];
?>
<noindex>
<?$APPLICATION->IncludeComponent(
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
        "CACHE_FILTER" => "N",
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
<div class="site-closed-mobile">
    <div class="">Контакт-центр&nbsp;</div>
    <a class="" href="tel:88007707646">+7 800 770-76-46</a>
</div>
</noindex>
<style>
  .site-closed-mobile{ display: none;}
  @media (max-width: 767px){
    .site-closed-mobile{
      display: flex;
      justify-content: center;
      margin: 10px 0;
      align-items: center;
      flex-direction: row;
      /*font-size: 22px;*/
    }
    .site-closed-mobile a {
      display: block;
      /*font-size: 22px;*/
    }
  }
    @media (max-width: 991px){
      .b-header__top__container_wr{
        display: flex;
        justify-content: center;
        align-items: center;
      }
      .b-header__top__container_wr .col-sm-4:nth-child(3),
      .b-header__top__container_wr .col-sm-4:nth-child(4){
        display: none!important;
      }
    }
    @media (max-width: 767px){
      .b-mainpage-banner-carousel .b-mainpage-banner{
        background-size: contain!important;
        background-repeat: no-repeat!important;
      }
      .b-mainpage-banner-carousel .carousel-inner, .b-mainpage-banner-carousel .item {
        height: 375px!important;
      }
    }
    @media (max-width: 375px){
      .b-mainpage-banner-carousel .carousel-inner, .b-mainpage-banner-carousel .item {
        height: 355px!important;
      }
    }
    @media (max-width: 320px){
      .b-mainpage-banner-carousel .carousel-inner, .b-mainpage-banner-carousel .item {
        height: 300px!important;
      }
    }

    .b-page__content .b-page__wrapper, .b-page__content .b-header{
      margin-bottom: 0!important;
    }
    .b-page__content{
      height: auto;
      overflow-y: initial;
    }
    @media (min-width: 992px){
      .b-page__content .b-page__wrapper, .b-page__content .b-header{
        margin-bottom: 0!important;
      }
    }
    @media (min-width: 992px){
      .b-page__wrapper:after{
        height: auto !important;
      }
    }
    .b-page__wrapper{
      min-height: auto;!important;
    }

    @media (min-width: 768px){
      .b-page__wrapper:after{
        height: auto!important;
      }
    }
    .b-page__footer-minimal {
      margin-top: 0!important;
    }

</style>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>