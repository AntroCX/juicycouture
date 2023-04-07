<?php
namespace Jamilco\Gtm;

use Jamilco\Main\Helper as MainHelper;

class Catalog
{
    public static function getProducts($basket)
    {
        $gtmProducts = [];
        $basket = $basket->getBasketItems();

        foreach ($basket as $item) {
            $pId = (int)$item->getField('PRODUCT_XML_ID');
            $gtmProducts[] = [
                'id' => $item->getField('PRODUCT_ID'),
                'name' => MainHelper::getProductName($pId),
                'price' => number_format($item->getPrice(), 2, '.', ''),
                'quantity' => (int)$item->getField('QUANTITY'),
                'brand' => MainHelper::getProductBrand($pId),
                'category' => MainHelper::getProductCategory($pId),
                'coupon' => $item->getField('DISCOUNT_COUPON'),
                'variant' => $item->getField('NAME'),
            ];
        }
        return $gtmProducts;
    }
}
