<?
use \Bitrix\Main;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Error;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Iblock;
use \Bitrix\Iblock\Component\ElementList;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @global CUser            $USER
 * @global CMain            $APPLICATION
 * @global CIntranetToolbar $INTRANET_TOOLBAR
 */

Loc::loadMessages(__FILE__);

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    ShowError(Loc::getMessage('IBLOCK_MODULE_NOT_INSTALLED'));

    return;
}

CBitrixComponent::includeComponentClass("bitrix:catalog.section");

class JamilcoSectionComponent extends CatalogSectionComponent
{
    protected function getSort()
    {
        $sortFields = array();
        
        if (!isset($sortFields[$this->arParams['ELEMENT_SORT_FIELD']])) {
            $sortFields[$this->arParams['ELEMENT_SORT_FIELD']] = $this->arParams['ELEMENT_SORT_ORDER'];
        }
        if (!isset($sortFields[$this->arParams['ELEMENT_SORT_FIELD2']])) {
            $sortFields[$this->arParams['ELEMENT_SORT_FIELD2']] = $this->arParams['ELEMENT_SORT_ORDER2'];
        }
/*
        $sortFields['PROPERTY_AVAILABLE_SORT'] = 'DESC';    // сначала продаваемые, затем бронируемые (внутри сортировка по количеству доступных размеров)
        $sortFields['CATALOG_QUANTITY'] = 'DESC';           // по наличию на складе
        $sortFields['PROPERTY_RETAIL_QUANTITY'] = 'DESC';   // по наличию в РМ
*/
        //pr($sortFields);

        return $sortFields;
    }
}