<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $templateData */
/** @var @global CMain $APPLICATION */
Jamilco\Blocks\Block::load(array('b-auth'));
?>
<div class="col-sm-6">
    <h3>Регистрация</h3>
<?$APPLICATION->IncludeComponent(
    "bitrix:main.register",
    "main",
    Array(
        "AUTH" => "Y",
        "REQUIRED_FIELDS" => array("NAME"),
        "SET_TITLE" => "Y",
        "SHOW_FIELDS" => array("NAME","LAST_NAME"),
        "SUCCESS_PAGE" => "/personal/",
        "USER_PROPERTY" => array(),
        "USER_PROPERTY_NAME" => "",
        "USE_BACKURL" => "Y"
    )
);?>
    </div>
</div>
</div>
