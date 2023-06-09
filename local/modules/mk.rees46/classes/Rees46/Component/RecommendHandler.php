<?php

namespace Rees46\Component;

use Rees46\Functions;
use Rees46\Options;

class RecommendHandler
{
	public static function run($arParams)
	{
		if (isset($arParams['recommender'])) {
			$recommender = $arParams['recommender'];
		} else {
			error_log('recommender not specified');
			return;
		}

		$params = isset($arParams['params']) ? $arParams['params'] : array();

		// get current cart items
		$cart = Functions::getCartItemIds();

		if (isset($params['cart']) === false) {
			$params['cart'] = $cart;
		}

		$jsonParams = array(
				'limit' => Options::getRecommendCount(),
		);


		if (empty($params['modification']) === false) {
			$jsonParams['modification'] = $params['modification'];
		}

		// check required params for recommenders
		switch ($recommender) {
			case 'buying_now':
				if (isset($params['cart']) && is_array($params['cart'])) {
					$jsonParams['cart'] = array_values($params['cart']);
				}
				if (isset($params['item_id'])) {
                    $jsonParams['item'] = is_string($params['item_id']) ? $params['item_id'] : json_encode($params['item_id']);
				}
				break;
			case 'see_also':
				if (isset($params['cart']) && is_array($params['cart'])) {
					$jsonParams['cart'] = array_values($params['cart']);
				} else {
					error_log('recommender see_also requires cart');
					return;
				}
				break;

			case 'recently_viewed':
				if (isset($params['cart']) && is_array($params['cart'])) {
					$jsonParams['cart'] = array_values($params['cart']);
				}
				if (isset($params['item_id'])) {
                    $jsonParams['item'] = is_string($params['item_id']) ? $params['item_id'] : json_encode($params['item_id']);
				}
				break;

			case 'also_bought':
				if (isset($params['cart']) && is_array($params['cart'])) {
					$jsonParams['cart'] = array_values($params['cart']);
				}
				if (isset($params['item_id'])) {
                    $jsonParams['item'] = is_string($params['item_id']) ? $params['item_id'] : json_encode($params['item_id']);
				} else {
					error_log('recommender also_bought requires item_id');
					return;
				}
				break;

			case 'similar':
				if (isset($params['cart']) && is_array($params['cart'])) {
					$jsonParams['cart'] = array_values($params['cart']);
				}
				if (isset($params['item_id'])) {
                    $jsonParams['item'] = is_string($params['item_id']) ? $params['item_id'] : json_encode($params['item_id']);
				} else {
					error_log('recommender similar requires item_id');
					return;
				}
				break;

			case 'interesting': // no params
				if (isset($params['cart']) && is_array($params['cart'])) {
					$jsonParams['cart'] = array_values($params['cart']);
				}
				if (isset($params['item_id'])) {
                    $jsonParams['item'] = is_string($params['item_id']) ? $params['item_id'] : json_encode($params['item_id']);
				}
				break;

			case 'popular':
				if (isset($params['cart']) && is_array($params['cart'])) {
					$jsonParams['cart'] = array_values($params['cart']);
				}
				if (isset($params['category'])) {
					$jsonParams['category'] = intval($params['category']);
				}
				break;

			default:
				error_log('unknown recommender: ' . $recommender);
		}

		$uniqid = uniqid('rees46-recommend-');

		// render recommender placeholder and corresponding js
		?>
		<div id="<?= $uniqid ?>" class="rees46-recommend"></div>
		<script>
            window.slickOptions = {
                responsive : [ {
                    breakpoint : 480,
                    settings   : {
                        slidesToShow   : 1,
                        slidesToScroll : 1
                    }
                }
                    // You can unslick at a given breakpoint now by adding:
                    // settings: "unslick"
                    // instead of a settings object
                ],
                slidesToShow: 5,
                slidesToScroll: 1,
                autoplay: !1,
                prevArrow:'<a class="i-slick__prev"></a>',
                nextArrow:'<a class="i-slick__next"></a>'
            };

			$(function(){
              setTimeout(function() {
                r46('recommend', '<?= $recommender ?>', <?= json_encode($jsonParams) ?>, function(items) {
                  if (items.length > 0) {

                    var data_string = BX.ajax.prepareData({
                      action            : 'recommend',
                      recommended_by    : <?= json_encode($recommender) ?>,
                      recommended_items : items
                    });
                    BX.ajax({
                      url       : '<?= SITE_DIR ?>include/rees46-handler.php?' + data_string,
                      method    : 'GET',
                      dataType  : 'html',
                      async     : true,
                      onsuccess : function(html) {
                        BX('<?= $uniqid ?>').innerHTML = html;
                        var $slider = $("#<?= $uniqid ?> .recommended-items");
                        setTimeout(function() {
                          $slider.slick(window.slickOptions);
                          setTimeout(function(){
                            $(' .rees46-recommend .item-container').fadeIn(150, function(){
                              $('.recommended-items').removeClass('recommender-block__list_load');
                            });
                          }, 1000)
                        }, 1000);
                      }
                    });
                  }
                });
              }, 1500);
			});
		</script>
		<?php
	}
}
