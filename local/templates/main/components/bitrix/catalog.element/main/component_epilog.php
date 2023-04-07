<?php

use Bitrix\Main\Loader;

/** @var array $templateData */
/** @var @global CMain $APPLICATION */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
  die();
}

global $APPLICATION;

global $Rees46_CatalogItemId;
$Rees46_CatalogItemId = $arResult["ID"];

if ($arParams['IS_LOOK_MODE']) {
    // код после вызова этого компонента в element.php выполнен не будет
    die();
}

Jamilco\Blocks\Block::load([
    'b-catalog-detail',
    'b-section',
    'b-catalog',
    'b-recommendation',
    'b-shops',
    'b-reviews',
    'b-modal-review',
    'b-modal-reserved',
    'i-imageviewer',
    'i-zoom'
]);

//$GLOBALS['APPLICATION']->AddHeadScript('/local/blocks/b-modal-reserved/b-modal-reserved.js', false);
?>

<div class="modal fade" id="modalSizeChart" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" id="closeModalSizeChart"></button>
                <h4 class="modal-title">Размерная сетка</h4>
            </div>
            <div class="modal-body">
                <?$APPLICATION->IncludeComponent(
                    "bitrix:main.include",
                    "",
                    Array(
                        "AREA_FILE_SHOW" => "file",
                        "AREA_FILE_SUFFIX" => "inc",
                        "EDIT_TEMPLATE" => "",
                        "PATH" => "/local/includes/sizeChart.php",
	                    "SIZES_TABLE_TAB"   => $arResult['SIZES_TABLE_TAB']
                    )
                );?>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
	<script>
        $(function () {
            $('#modalSizeChart').appendTo('.b-page__footer');


            $('#closeModalSizeChart').on('click', function (e) {
                $('#modalSizeChart').modal('hide');
            })
        });
	</script>
<?
// hot fix, сделать через глобалку
//if('https://juicycouture.ru'.$GLOBALS['APPLICATION']->GetCurPage() != $arResult['CANONICAL_PAGE_URL']) {
//    LocalRedirect($arResult['CANONICAL_PAGE_URL'], false, "301 Moved Permanently");
//}?>

<?php
/** DigitalDataLayer start */
$APPLICATION->SetPageProperty('ddlPageType', 'product');
$APPLICATION->SetPageProperty('ddlPageCategory', 'Product Detail');
$ddm = \DigitalDataLayer\Manager::getInstance();
$digitalData = $ddm->getData();
$digitalData->product = $arResult['DDL_PRODUCT_PROPERTIES'] ?: [];
/** DigitalDataLayer end */

// Adspire
\Adspire\Manager::getInstance()->setContainerElement(['push' => $arResult['ADSPIRE_PROPERTIES']]);
