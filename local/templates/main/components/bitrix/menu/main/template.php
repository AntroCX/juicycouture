<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (empty($arResult)) return;

$picCount = [
    1 => 3,
    2 => 2,
    3 => 2,
    4 => 1,
];
?>

<ul class="b-header__menu-ul">
    <?
    foreach ($arResult['MENU'] as $key => $arItem) {
        $countShow = 0;
        if($arItem['PARAMS']['SEL'])
            $selected = 1;
        ?>
        <li class="<?=$arItem["SELECTED"] ? 'active' : '' ?><?=$selected ? ' sel' : '' ?>">
            <a <?= ($arItem['PARAMS']['COLOR']) ? 'style="color: '.$arItem['PARAMS']['COLOR'].'!important;"' : '' ?> href="<?= $arItem["LINK"] ?>"><?= $arItem["TEXT"] ?></a>
            <?= ($arItem['IS_PARENT']) ? '<div class="b-header__menu-dropdown"><div class="container">' : '' ?>
            
            <?
            foreach ($arResult['VIEW_TYPE'] as $arViewType) {
                if ($arItem[$arViewType['XML_ID']]) {
                    if ($arViewType['XML_ID'] == 'OWN') {
                        foreach ($arItem[$arViewType['XML_ID']] as $subDir) {
                            $countShow++;
                            ?>
                            <ul>
                                <li class="b-header__menu-dropdown-category"><a href="<?= $subDir['LINK'] ?>"><?= $subDir['TEXT'] ?></a></li>
                                <? foreach ($subDir['OWN'] as $arSubItem) { ?>
                                    <li><a href="<?= $arSubItem['LINK'] ?>"><?= $arSubItem['TEXT'] ?></a></li>
                                <? } ?>
                                <? if (count($subDir['OWN'])) { ?>
                                <li class="see-all-link"><a href="<?= $subDir['LINK'] ?>">Смотреть все</a></li>
                                <? } ?>
                            </ul>
                        <?
                        }
                    } else {
                        $countShow++;
                        $showSeeAll = ($arViewType['XML_ID'] == 'MAIN');
                        ?>
                        <ul>
                            <li class="b-header__menu-dropdown-category"><?= ($showSeeAll) ? '<a href="'.$arItem['LINK'].'">' : '' ?><?= $arViewType['VALUE'] ?><?= ($showSeeAll) ? '</a>' : '' ?></li>
                            <? foreach ($arItem[$arViewType['XML_ID']] as $arSubItem) { ?>
                                <li><a href="<?= $arSubItem['LINK'] ?>"><?= $arSubItem['TEXT'] ?></a></li>
                            <? } ?>
                            <? if ($showSeeAll && count($arItem[$arViewType['XML_ID']])) { ?>
                                <li class="see-all-link"><a href="<?= $arItem['LINK'] ?>">Смотреть все</a></li>
                            <? } ?>
                        </ul>
                    <?
                    }
                }
            }
            // изображение для конкретного пункта меню
            if($arItem['PARAMS']['ADD_INC_PICTURE']) {?>
                <div class="ul ul-picture">
                    <a class="b-header__menu-dropdown-picture" href="<?= $arItem['LINK'] ?>">
                        <?$APPLICATION->IncludeFile(SITE_DIR . "local/images/menu/".$arItem['PARAMS']['ADD_INC_PICTURE'].".php", array(), array("MODE"=>"html", "TEMPLATE" => "standard_inc.php", "NAME" => "картинку для пункта ".$arItem['TEXT']));?>
                        <span class="b-header__menu-dropdown-name"><?= $arItem['TEXT'] ?></span>
                    </a>
                </div>
            <?}
            // несколько изображений для пункта меню
            if($arItem['PARAMS']['ADD_INC_ALL_PICTURE']) {?>
                <div class="ul ul-picture">
                        <?$APPLICATION->IncludeFile(SITE_DIR . "local/images/menu/".$arItem['PARAMS']['ADD_INC_ALL_PICTURE'].".php", array(), array("MODE"=>"html", "TEMPLATE" => "standard_inc.php", "NAME" => "картинку для пункта ".$arItem['TEXT']));?>
                </div>
            <?}
            if ($picCount[$countShow] > 0 && count($arItem['PICTURES'])) {
                echo '<div class="b-header__menu-dropdown-pictures">';
                foreach ($arItem['PICTURES'] as $key => $arOne) {
                    if (($key + 1) > $picCount[$countShow]) break;
                    ?>
                    <a href="<?= $arOne['LINK'] ?>" class="b-header__menu-dropdown-picture">
                        <img src="<?= $arOne['SRC'] ?>">
                        <span class="b-header__menu-dropdown-name"><?= $arOne['TEXT'] ?></span>
                    </a>
                <?
                }
                echo '</div>';
            }
            ?>

            <?= ($arItem['IS_PARENT']) ? '</div></div>' : '' ?>
        </li>
    <? } ?>
</ul>