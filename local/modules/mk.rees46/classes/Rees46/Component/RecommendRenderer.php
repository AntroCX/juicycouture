<?php

namespace Rees46\Component;

use CCatalogProduct;
use CFile;
use CModule;
use CCatalogDiscount;
use CCatalogSKU;
use Rees46\Options;
use CPrice;
use CCurrencyLang;
use CCurrency;
use CCurrencyRates;
use CIBlockElement;
use CIBlockPriceTools;
use COption;
use Rees46\Bitrix\Data;

IncludeModuleLangFile(__FILE__);

class RecommendRenderer
{
	/**
	 * handler for include/rees46-recommender.php, render recommenders
	 */
	public static function run()
	{
		CModule::IncludeModule('catalog');
		CModule::IncludeModule('sale');
		CModule::IncludeModule("iblock");

		global $USER;

		$recommended_by = '';

		// get recommender name
		if (isset($_REQUEST['recommended_by'])) {
			$recommender = strval($_REQUEST['recommended_by']);
			$recommended_by = '?recommended_by='. urlencode($recommender);

			switch ($recommender) {
				case 'buying_now':
					$recommender_title = GetMessage('REES_INCLUDE_BUYING_NOW');
					break;
				case 'see_also':
					$recommender_title = GetMessage('REES_INCLUDE_SEE_ALSO');
					break;
				case 'recently_viewed':
					$recommender_title = GetMessage('REES_INCLUDE_RECENTLY_VIEWED');
					break;
				case 'also_bought':
					$recommender_title = GetMessage('REES_INCLUDE_ALSO_BOUGHT');
					break;
				case 'similar':
					$recommender_title = GetMessage('REES_INCLUDE_SIMILAR');
					break;
				case 'interesting':
					$recommender_title = GetMessage('REES_INCLUDE_INTERESTING');
					break;
				case 'popular':
					$recommender_title = GetMessage('REES_INCLUDE_POPULAR');
					break;
				default:
					$recommender_title = '';
			}
		}

		$libCatalogProduct = new CCatalogProduct();
		$libFile = new CFile();

		// render items
		if (isset($_REQUEST['recommended_items']) && is_array($_REQUEST['recommended_items']) && count($_REQUEST['recommended_items']) > 0) {

			$found_items = 0;

			// Currency to display
			$sale_currency = Data::getSaleCurrency();

			$html = '';
			$html .= '<div class="recommender-block-title-wrapper"><div class="recommender-block-title">' . $recommender_title . '</div></div>';
			$html .= '<div class="recommended-items recommender-block__list_load">';

			foreach ($_REQUEST['recommended_items'] as $item_id) {
				$item_id = intval($item_id);
				$item = $libCatalogProduct->GetByIDEx($item_id);
				
				/* start - в массиве тп возвращается неверный product_id, приходится его находить */
				$rsOffers = CIBlockElement::GetList(
				    array(),
                    array('IBLOCK_ID' => $item['IBLOCK_ID'], 'ID' => $item['ID']),
                    false,
                    false,
                    array('PROPERTY_CML2_LINK')
                );
				if ($arOffer = $rsOffers->Fetch()) {
				    $res = CIBlockElement::GetByID($arOffer['PROPERTY_CML2_LINK_VALUE']);
				    if($ar_res = $res->GetNext()) {
				        $item['NAME'] = $ar_res['NAME'];
				        $item['DETAIL_PAGE_URL'] = $ar_res['DETAIL_PAGE_URL'];
				    }
				}
                /* end */
                
                // Get price
				$final_price = Data::getFinalPriceInCurrency($item_id, $sale_currency);

				// Check price
				if($final_price == false) {
					continue;
				}

				// Url to product with recommended_by attribute
				$link = $item['DETAIL_PAGE_URL'] . $recommended_by;

				// Get photo
				$ar_picture_id = Data::getProductPhotoId($item_id);
				if ($ar_picture_id[0] === null && $ar_picture_id[1] === null) {
					continue;
				}

                if($ar_picture_id[0]) {
                    $file1 = $libFile->ResizeImageGet($ar_picture_id[0], array(
                        'width' => Options::getImageWidth(),
                        'height' => Options::getImageHeight()
                    ), BX_RESIZE_IMAGE_PROPORTIONAL, true);
                }
				if($ar_picture_id[1]) {
                    $file2 = $libFile->ResizeImageGet(
                        $ar_picture_id[1],
                        array(
                            'width'  => Options::getImageWidth(),
                            'height' => Options::getImageHeight()
                        ),
                        BX_RESIZE_IMAGE_PROPORTIONAL,
                        true
                    );
                }

				$html .= '<div class="item-container">
                        <div class="b-catalog__goods-item">
                        <div class="b-catalog__goods-item-wrapper">
                        
                            <ul class="b-catalog__goods-item-wrapper-sku">
                              <li class="b-catalog__goods-item-wrapper-sku-element active" data-sku="12869">
                                    <div class="b-catalog__goods-item-wrapper-sku-element-wrapper">';
                                        if(!empty($file1))
                                        $html .= '<img src="'.$file1['src'].'" width="320" height="399">';
                                        if(!empty($file2))
                                        $html .='<img src="'.$file2['src'].'" width="320" height="399">';
                                        $html .=
                                      '<a class="b-catalog__goods-item-link" href="'.$link.'"></a>
                                    </div>
                                    <div class="b-catalog__goods-item-name">'.$item['NAME'].'</div>
                                    <div class="b-catalog__goods-item-price">
                                          <span class="price-sale-look"></span>
                                          <span class="price-base-look">'.( $final_price ? CCurrencyLang::CurrencyFormat($final_price, $sale_currency, true) : '').'</span>
                                    </div>
                              </li>
                            </ul>                        
                        					
                        </div>
				</div>';
                //$html .= '<div style="text-align: center;"><a class="btn btn-primary js-to-basket" href="'.$link.'" tabindex="0">Добавить в корзину</a></div>';
                $html .= '</div>';

				$found_items++;

			}

			$html .= '</div>';

			if($found_items > 0) {
				echo $html;
			}

		}
	}
}
