<?

namespace Jamilco\Loyalty;

use \Bitrix\Main\Web\Json;
use \Bitrix\Main\Grid\Declension;
use \Bitrix\Sale;
use \Jamilco\Loyalty\Common;
use \Jamilco\Loyalty\Card;
use \Jamilco\Loyalty\Bonus;

class BonusOrder
{
    public static function onInit()
    {
        return [
            "BLOCKSET"        => "Jamilco\Loyalty\BonusOrder",
            "check"           => ["Jamilco\Loyalty\BonusOrder", "bonusCheck"],
            "action"          => ["Jamilco\Loyalty\BonusOrder", "bonusAction"],
            "getScripts"      => ["Jamilco\Loyalty\BonusOrder", "bonusGetScripts"],
            "getBlocksBrief"  => ["Jamilco\Loyalty\BonusOrder", "bonusGetBlocksBrief"],
            "getBlockContent" => ["Jamilco\Loyalty\BonusOrder", "bonusGetBlockContent"],
        ];
    }

    public static function bonusCheck($args)
    {
        // заказ еще не сохранен, делаем проверки
        // возвращаем True, если можно все сохранять, иначе False
        // в случае ошибки $GLOBALS["APPLICATION"]->ThrowException("Ошибка!!!", "ERROR");


    }

    public static function bonusAction($args)
    {
        // заказ сохранен, сохраняем данные пользовательских блоков
        // возвращаем True в случае успеха и False - в случае ошибки
        // в случае ошибки $GLOBALS["APPLICATION"]->ThrowException("Ошибка!!!", "ERROR");


    }

    public static function bonusGetBlocksBrief($args)
    {
        $id = !empty($args['ORDER']) ? $args['ORDER']->getId() : 0;

        return [
            'main' => ["TITLE" => "Бонусная карта"],
        ];
    }

    public static function bonusGetScripts($args)
    {
        global $APPLICATION;
        $APPLICATION->addHeadScript('/local/modules/jamilco.loyalty/admin/bonus-order/bonus-order.js');
        $APPLICATION->SetAdditionalCSS('/local/modules/jamilco.loyalty/admin/bonus-order/bonus-order.css');

        $order = $args['ORDER'];
        $orderId = !empty($order) ? $order->getId() : 0;
        if (!$orderId) return '';

        $arProps = self::getProps($order);

        return '
        <script type="text/javascript">
            window.orderId = \''.$orderId.'\';
            window.orderProps = '.Json::encode($arProps).';
        </script>';
    }

    public static function bonusGetBlockContent($blockCode, $selectedTab, $args)
    {
        global $acceptOrderPriceChanging;
        $acceptOrderPriceChanging = true;

        $bonusPlural = new Declension('бонус', 'бонуса', 'бонусов');

        $result = '';
        $order = $args['ORDER'];
        $orderId = !empty($order) ? $order->getId() : 0;

        $arProps = self::getProps($order);
        if ($selectedTab == 'tab_order' && $blockCode == 'main') {
            if (!$orderId) return '';

            $result .= '<div id="bonusOrder">';
            $arMainData = [
                ['TITLE' => 'Email', 'TEXT' => $arProps['EMAIL']],
                ['TITLE' => 'Номер телефона', 'TEXT' => $arProps['PHONE']],
            ];
            if ($arProps['CARD']) {
                $arMainData[] = ['TITLE' => 'За заказ можно получить', 'TEXT' => $arProps['BONUS_BY_ORDER'].' '.$bonusPlural->get($arProps['BONUS_BY_ORDER'])];
            } else {
                $arMainData[] = ['TITLE' => 'За заказ можно получить', 'TEXT' => 'Станет известно после добавления карты'];
            }

            if ($arProps['CARD']) {
                if (!$arProps['BONUS']) {
                    $arMainData[] = [
                        'TITLE' => 'В счет заказа можно списать',
                        'TEXT'  => $arProps['BONUS_TO_ORDER'].' '.$bonusPlural->get($arProps['BONUS_TO_ORDER'])
                    ];
                }
            } else {
                $arMainData[] = [
                    'TITLE' => 'В счет заказа можно списать',
                    'TEXT'  => 'Станет известно после добавления карты',
                ];
            }

            $arProps['CARD_MAY'] = [];
            if ($arProps['CARD']) {
                $arMainData[] = ['TITLE' => '<b>Бонусная карта</b>', 'TEXT' => '<b>'.$arProps['CARD'].'</b>'];
                if ($arProps['BONUS']) {
                    $arMainData[] = ['TITLE' => '<b>Списано бонусов</b>', 'TEXT' => '<b>'.$arProps['BONUS'].'</b>'];
                } else {
                    // если бонусы не списаны, то получим данные по карте
                    $arProps['CARD_MAY'][] = $arProps['CARD'];
                }
            }
            if (!$arProps['BONUS']) {
                // если бонусы не списаны, то карту все еще можно заменить
                $arProps['CARD_MAY'] = array_merge($arProps['CARD_MAY'], Card::findCard($arProps['PHONE'], $arProps['EMAIL']));
                $arProps['CARD_MAY'] = array_unique($arProps['CARD_MAY']);
            }

            $isPaid = $order->isPaid();
            $isCanceled = $order->isCanceled();
            $canBonusAct = true;
            if ($isCanceled) {
                $arMainData[] = ['TITLE' => '<b>Заказ отменен</b>', 'TEXT' => '<b>Изменение бонусных данных невозможно</b>'];
                $canBonusAct = false;
            } elseif ($isPaid) {
                if ($arProps['BONUS']) {
                    $arMainData[] = ['TITLE' => '<b>Заказ оплачен</b>', 'TEXT' => '<b>Бонусы списаны из цены</b>'];
                } else {
                    $arMainData[] = ['TITLE' => '<b>Заказ оплачен</b>', 'TEXT' => '<b>Оплата бонусами невозможна</b>'];
                }
                $canBonusAct = false;
            } elseif (!Bonus::canChangeOrder($orderId)) {
                $arMainData[] = ['TITLE' => '<b>Заказ принят</b>', 'TEXT' => '<b>Изменение бонусных данных невозможно</b>'];
                $canBonusAct = false;
            } else {
                /*
                $discountData = $order->getDiscount()->getApplyResult(); // этот блок в админке не работает
                foreach ($discountData['DISCOUNT_LIST'] as $arDiscount) {
                    if ($arDiscount['USE_COUPONS'] == 'Y') {
                        $arMainData[] = ['TITLE' => '<b>Применен купон</b>', 'TEXT' => '<b>Оплата бонусами невозможна</b>'];
                        $canBonusAct = false;
                    }
                }
                */

                $couponUsed = false;

                if (Common::discountsAreMoved()) {
                    // купоны манзаны
                    if ($arProps['COUPONS']) $couponUsed = true;
                } else {
                    // купоны битрикса
                    $c = Sale\Internals\OrderCouponsTable::getList(['filter' => ['ORDER_ID' => $orderId]]);
                    while ($arCoupon = $c->Fetch()) {
                        if ($arCoupon['DATA']['ACTIVE'] == 'Y') $couponUsed = true;
                    }
                }

                if ($couponUsed) {
                    $arMainData[] = ['TITLE' => '<b>Применен купон</b>', 'TEXT' => '<b>Оплата бонусами невозможна</b>'];
                    $canBonusAct = false;
                }
            }

            $result .= self::printBlock('Данные по заказу', $arMainData);

            if ($canBonusAct) {
                TrimArr($arProps['CARD_MAY'], true);
                if (count($arProps['CARD_MAY'])) {
                    foreach ($arProps['CARD_MAY'] as $card) {
                        $arBalance = Card::getBalance($card, true);

                        $arCardData = [
                            ['TITLE' => 'Номер карты', 'TEXT' => $card],
                            ['TITLE' => 'Доступно', 'TEXT' => (int)$arBalance['AVAILABLE'].' '.$bonusPlural->get($arBalance['AVAILABLE'])],
                            ['TITLE' => 'Неподтверждено', 'TEXT' => (int)$arBalance['UNCONFIRMED'].' '.$bonusPlural->get($arBalance['UNCONFIRMED'])],
                            ['TITLE' => 'Использовано', 'TEXT' => (int)$arBalance['USED'].' '.$bonusPlural->get($arBalance['USED'])],
                        ];

                        $arCardAct = ['ACT' => 'Y'];
                        if (!$arProps['BONUS'] && $card != $arProps['CARD']) {
                            $arCardAct['TITLE'] = '<span data-card="'.$card.'" class="adm-btn addCard">Добавить в заказ</span>';
                        }
                        if (!$arProps['BONUS'] && $arProps['BONUS_TO_ORDER'] > 0 && $arBalance['AVAILABLE'] > 0) {
                            $arCardAct['TEXT'] = '<span data-card="'.$card.'" class="adm-btn useCard">Списать бонусы</span>';
                        }
                        if (count($arCardAct) > 1) $arCardData[] = $arCardAct;

                        $result .= self::printBlock('Бонусная карта №'.$card, $arCardData);
                    }
                }

                $arActionData = [
                    [
                        'TITLE' => '<input type="text" name="card" maxlength="13" placeholder="Номер карты" class="adm-bus-input" id="cardNumber">',
                        'TEXT'  => '<span id="addCard" class="adm-btn">Применить</span>',
                        'ACT'   => 'Y'
                    ],
                    ['TITLE' => '<span class="card-result"></span>', 'TEXT' => '', 'ACT' => 'Y'],
                    ['TITLE' => 'Email', 'TEXT' => '<span class="card-email"></span>', 'CLASS' => 'card-data none'],
                    ['TITLE' => 'Телефон', 'TEXT' => '<span class="card-phone"></span>', 'CLASS' => 'card-data none'],
                    ['TITLE' => 'Бонусов доступно', 'TEXT' => '<span class="card-bonus"></span>', 'CLASS' => 'card-data none'],
                    ['TITLE' => 'Можно списать', 'TEXT' => '<span class="card-bonus-pay"></span>', 'CLASS' => 'card-data none'],
                    [
                        'TITLE' => '<span class="adm-btn send-code send-email" data-type="email">Выслать код на Email</span>',
                        'TEXT'  => '
                                <span class="adm-btn send-code send-phone" data-type="phone">Выслать код на Телефон</span>
                                <span class="adm-btn send-card addCard" data-card="" title="Добавить карту в заказ без списания бонусов">Добавить карту в заказ</span>',
                        'ACT'   => 'Y',
                        'CLASS' => 'card-data none'
                    ],
                    [
                        'TITLE' => '<input type="text" name="card-code" maxlength="5" placeholder="Проверочный код" class="adm-bus-input" id="cardCheckCode">',
                        'TEXT'  => '<span id="checkCode" class="adm-btn">Применить</span>',
                        'ACT'   => 'Y',
                        'CLASS' => 'card-code-data none'
                    ],
                    [
                        'TITLE' => '<span class="card-code-result"></span>',
                        'TEXT'  => '',
                        'ACT'   => 'Y',
                        'CLASS' => 'card-code-data none'
                    ],
                ];

                if (!$arProps['BONUS']) $result .= self::printBlock('Применить карту', $arActionData);
            }

            if (Common::discountsAreMoved()) {
                if (!$arProps['BONUS'] && Bonus::canChangeOrder($orderId)) {
                    $arCouponData = [
                        [
                            'TITLE' => '<input type="text" name="coupon" maxlength="13" placeholder="Купон Манзаны" class="adm-bus-input" id="couponManzana" value="'.$arProps['COUPONS'].'">',
                            'TEXT'  => '<span id="addCoupon" class="adm-btn">Применить</span>',
                            'ACT'   => 'Y'
                        ]
                    ];

                    if ($arProps['COUPONS']) {
                        $arCouponData[] = [
                            'TITLE' => 'Купон уже применен',
                            'TEXT'  => '<b>'.$arProps['COUPONS'].'</b>',
                        ];
                    } else {
                        $arCouponData[] = [
                            'TITLE' => 'После применения купона',
                            'TEXT'  => 'невозможно использовать бонусы',
                        ];
                    }

                    $result .= self::printBlock('Применить купон', $arCouponData);
                }
            }

            if ($arProps['BONUS'] || $arProps['CARD'] || $arProps['COUPONS']) {
                if ($arProps['SEND'] == 'Y') {
                    $arManzanaData = [
                        [
                            'TITLE' => 'Заказ отправлен в Манзану.',
                            'TEXT'  => 'При нарушении цен, его можно пересоздать, пересчитав при этом связанные параметры.',
                            'ACT'   => 'Y'
                        ],
                        [
                            'TITLE' => '',
                            'TEXT'  => '<span id="reCreateInManzana" class="adm-btn">Пересоздать заказ</span>',
                            'ACT'   => 'Y'
                        ]
                    ];
                } else {
                    $arManzanaData = [
                        [
                            'TITLE' => 'Отправьте заказ в Манзану.',
                            'TEXT'  => 'Этот процесс применит все скидки из Манзаны заново',
                            'ACT'   => 'Y'
                        ],
                        [
                            'TITLE' => '',
                            'TEXT'  => '<span id="reCreateInManzana" class="adm-btn">Создать заказ</span>',
                            'ACT'   => 'Y'
                        ]
                    ];
                }

                $result .= self::printBlock('Заказ в Манзане', $arManzanaData);
            }

            $result .= '</div>';
        }

        $deliveryIds = $order->getDeliverySystemId();
        if (in_array(CURIER_DELIVERY, $deliveryIds) || in_array(KCE_DELIVERY, $deliveryIds)) {
            $locationCode = $arProps['TARIF_LOCATION'];
            $arLocation = \Jamilco\Delivery\Location::getLocationData(0, $locationCode);
            $kce = \Sale\Handlers\Delivery\KceHandler::getElementByLocationID($arLocation['ID'], ['PROPERTY_cash_payment']);
            if (!$kce['PROPERTY_CASH_PAYMENT_ENUM_ID']) {
                $result .= '
                <script type="text/javascript">
                    $(function(){
                        markCityAsNotCash();
                    });
                </script>
                ';
            }
        }

        return $result;
    }

    private static function printBlock($title = '', $arData = [])
    {
        $result = '
            <div class="adm-bus-table-container caption border sale-order-props-group">
                <div class="adm-bus-table-caption-title">'.$title.'</div>
                <table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table">';

        foreach ($arData as $arOne) {
            $arOne['TITLE'] .= ($arOne['ACT'] == 'Y') ? '' : ':';
            $result .= '
                        <tr class="'.$arOne['CLASS'].'">
                            <td class="adm-detail-content-cell-l" width="40%" valign="top">'.$arOne['TITLE'].'</td>
                            <td class="adm-detail-content-cell-r" ><div>'.$arOne['TEXT'].'</div></td>
                        </tr>';
        }

        $result .= '</table></div>';

        return $result;
    }

    private static function getProps($order)
    {
        $arPropCollection = $order->getPropertyCollection();
        $arPropsData = $arPropCollection->getArray();
        $arProps = [
            'EMAIL'                     => '',
            'PHONE'                     => '',
            'PERSONAL_MOBILE'           => '',
            'PROGRAMM_LOYALTY_CARD'     => '',
            'PROGRAMM_LOYALTY_WRITEOFF' => '',
            'COUPONS'                   => '',
            'SEND'                      => '',
            'BLACK_LIST'                => '',
            'TARIF_LOCATION'            => '',
        ];
        foreach ($arPropsData['properties'] as $arProp) {
            if (!array_key_exists($arProp['CODE'], $arProps)) continue;
            $arProps[$arProp['CODE']] = $arProp['VALUE'][0];
        }

        $arProps['PHONE'] = ($arProps['PHONE']) ?: $arProps['PERSONAL_MOBILE'];
        $arProps['CARD'] = $arProps['PROGRAMM_LOYALTY_CARD'];
        $arProps['BONUS'] = (int)$arProps['PROGRAMM_LOYALTY_WRITEOFF'];

        if ($arProps['CARD']) {
            $bonus = new Bonus();
            $skipChangePrices = true;
            $bonusData = $bonus->getAdditionalSum($order->getID(), $skipChangePrices);
            $arProps['BONUS_BY_ORDER'] = $bonusData['ADDITIONAL_SUM']; // бонусы за заказ
            $arProps['BONUS_TO_ORDER'] = $bonusData['WRITEOFF_SUM']; // бонусы в счет заказа
        }

        return $arProps;
    }
}