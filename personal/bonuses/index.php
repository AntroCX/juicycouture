<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle('Бонусная программа');

Jamilco\Blocks\Block::load(['b-form']);

?>

<h1>Бонусная программа</h1>

<div class="row">
    <div class="col-sm-2">
        <?$APPLICATION->IncludeComponent(
            "bitrix:menu",
            "left",
            Array(
                "ALLOW_MULTI_SELECT"    => "N",    // Разрешить несколько активных пунктов одновременно
                "CHILD_MENU_TYPE"       => "left",    // Тип меню для остальных уровней
                "DELAY"                 => "N",    // Откладывать выполнение шаблона меню
                "MAX_LEVEL"             => "1",    // Уровень вложенности меню
                "MENU_CACHE_GET_VARS"   => array(    // Значимые переменные запроса
                    0 => "",
                ),
                "MENU_CACHE_TIME"       => "3600",    // Время кеширования (сек.)
                "MENU_CACHE_TYPE"       => "A",    // Тип кеширования
                "MENU_CACHE_USE_GROUPS" => "Y",    // Учитывать права доступа
                "ROOT_MENU_TYPE"        => "left",    // Тип меню для первого уровня
                "USE_EXT"               => "N",    // Подключать файлы с именами вида .тип_меню.menu_ext.php
            ),
            false
        );?>
    </div>
    <div class="col-sm-10">

        <? $APPLICATION->IncludeComponent("jamilco:personal.bonus", "", []); ?>
    </div>
</div>

<? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); ?>
