<?php

use \Bitrix\Main;
use \Bitrix\Main\Error;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Iblock\Component\ElementList;
use \Bitrix\Catalog;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    ShowError(Loc::getMessage('IBLOCK_MODULE_NOT_INSTALLED'));

    return;
}

class JamilcoCatalogProductsViewedComponent extends ElementList
{
    public function __construct($component = null)
    {
        parent::__construct($component);
        $this->setExtendedMode(true)->setMultiIblockMode(true)->setPaginationMode(false);
    }

    public function onPrepareComponentParams($params)
    {
        $params['PRODUCT_DISPLAY_MODE'] = isset($params['PRODUCT_DISPLAY_MODE']) && $params['PRODUCT_DISPLAY_MODE'] === 'N' ? 'N' : 'Y';
        $params['IBLOCK_MODE'] = isset($params['IBLOCK_MODE']) && $params['IBLOCK_MODE'] === 'multi' ? 'multi' : 'single';

        if ($params['IBLOCK_MODE'] === 'single' && (int)$params['IBLOCK_ID'] > 0) {
            $params['SHOW_PRODUCTS'] = [(int)$params['IBLOCK_ID'] => true];
        }

        $params = parent::onPrepareComponentParams($params);

        if ($params['PAGE_ELEMENT_COUNT'] <= 0) {
            $params['PAGE_ELEMENT_COUNT'] = 9;
        }

        return $params;
    }

    protected function checkModules()
    {
        if ($success = parent::checkModules()) {
            if (!$this->useCatalog) {
                $this->abortResultCache();
                $this->errorCollection->setError(new Error(Loc::getMessage('CATALOG_MODULE_NOT_INSTALLED'), self::ERROR_TEXT));
                $success = false;
            }
        }

        return $success;
    }

    protected function getProductIds()
    {
        if (!Main\Loader::includeModule('sale')) return [];

        $skipUserInit = false;
        if (!Catalog\Product\Basket::isNotCrawler()) $skipUserInit = true;

        $basketUserId = (int)CSaleBasket::GetBasketUserID($skipUserInit);
        if ($basketUserId <= 0) return [];

        if ($this->arParams['IBLOCK_MODE'] === 'single') {
            $ids = array_values(
                Catalog\CatalogViewedProductTable::getProductSkuMap(
                    $this->arParams['IBLOCK_ID'],
                    $this->arParams['SECTION_ID'],
                    $basketUserId,
                    $this->arParams['SECTION_ELEMENT_ID'],
                    $this->arParams['PAGE_ELEMENT_COUNT'],
                    $this->arParams['DEPTH']
                )
            );
        } else {
            $ids = [];
            $filter = [
                '=FUSER_ID' => $basketUserId,
                '=SITE_ID'  => $this->getSiteId()
            ];

            if ($this->arParams['SECTION_ELEMENT_ID'] > 0) $filter['!=ELEMENT_ID'] = $this->arParams['SECTION_ELEMENT_ID'];

            $viewedIterator = Catalog\CatalogViewedProductTable::getList(
                [
                    'select' => ['ELEMENT_ID'],
                    'filter' => $filter,
                    'order'  => ['DATE_VISIT' => 'DESC'],
                    'limit'  => $this->arParams['PAGE_ELEMENT_COUNT'] * 10
                ]
            );
            while ($viewedProduct = $viewedIterator->fetch()) {
                $ids[] = (int)$viewedProduct['ELEMENT_ID'];
            }

            $this->filterFields = $this->getFilter();
            $this->filterFields['IBLOCK_ID'] = $this->arParams['IBLOCK_ID'];
            $this->initPricesQuery();

            if ($this->arParams['FILTER_NAME']) {
                $customFilter = $GLOBALS[$this->arParams['FILTER_NAME']];
            }

            $this->filterFields = array_merge($this->filterFields, $customFilter);

            $ids = $this->filterByParams($ids, [], false);
        }

        return $ids;
    }

    protected function filterByParams($ids, $filterIds = [], $useSectionFilter = true)
    {
        if (empty($ids)) return [];

        $ids = array_values(array_unique($ids));
        // remove duplicates of already showed items
        if (!empty($filterIds)) $ids = array_diff($ids, $filterIds);

        if (!empty($ids)) {
            $filter = $this->filterFields;
            $filter['ID'] = $ids;

            $correctIds = array();
            $elementIterator = \CIBlockElement::GetList([], $filter, false, ['nTopCount' => $this->arParams['PAGE_ELEMENT_COUNT']], ['ID']);
            while ($element = $elementIterator->Fetch()) {
                $correctIds[] = $element['ID'];
            }

            if ($useSectionFilter && !empty($correctIds) && $this->arParams['SHOW_FROM_SECTION'] === 'Y') {
                $correctIds = $this->filterIdBySection(
                    $correctIds,
                    $this->arParams['IBLOCK_ID'],
                    $this->arParams['SECTION_ID'],
                    $this->arParams['PAGE_ELEMENT_COUNT'],
                    $this->arParams['DEPTH']
                );
            }

            $correctIds = array_flip($correctIds);
            // remove invalid items
            foreach ($ids as $key => $id) {
                if (!isset($correctIds[$id])) unset($ids[$key]);
            }

            return array_values($ids);
        } else {
            return [];
        }
    }
}
