<?

namespace Jamilco\Omni;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Web\Json;
use \Bitrix\Main\Grid\Declension;
use \Bitrix\Sale;
use \Bitrix\Sale\Internals;
use \Jamilco\Omni\Channel;
use \Jamilco\Delivery\Location;

class ChangeDelivery
{
    const PROP_CODE_CITY = 'TARIF_LOCATION';
    const PROP_CODE_ADDRESS = 'F_ADDRESS';
    const PROP_CODE_STORE_ID = 'STORE_ID';

    public static function onInit()
    {
        return [
            "BLOCKSET"        => "Jamilco\Omni\ChangeDelivery",
            "check"           => ["Jamilco\Omni\ChangeDelivery", "check"],
            "action"          => ["Jamilco\Omni\ChangeDelivery", "action"],
            "getScripts"      => ["Jamilco\Omni\ChangeDelivery", "getScripts"],
            "getBlocksBrief"  => ["Jamilco\Omni\ChangeDelivery", "getBlocksBrief"],
            "getBlockContent" => ["Jamilco\Omni\ChangeDelivery", "getBlockContent"],
        ];
    }

    public static function check($args)
    {
        // заказ еще не сохранен, делаем проверки
        // возвращаем True, если можно все сохранять, иначе False
        // в случае ошибки $GLOBALS["APPLICATION"]->ThrowException("Ошибка!!!", "ERROR");


    }

    public static function action($args)
    {
        // заказ сохранен, сохраняем данные пользовательских блоков
        // возвращаем True в случае успеха и False - в случае ошибки
        // в случае ошибки $GLOBALS["APPLICATION"]->ThrowException("Ошибка!!!", "ERROR");


    }

    public static function getBlocksBrief($args)
    {
        return [
            'changedelivery' => ["TITLE" => "Изменить доставку"],
        ];
    }

    public static function getScripts($args)
    {
        global $APPLICATION, $USER;
        $APPLICATION->addHeadScript('/local/modules/jamilco.omni/admin/change-delivery/script.js');
        $APPLICATION->SetAdditionalCSS('/local/modules/jamilco.omni/admin/change-delivery/style.css');

        $order = $args['ORDER'];
        $orderId = !empty($order) ? $order->getId() : 0;
        if (!$orderId) return '';

        return '
        <script type="text/javascript">
            window.orderId = \''.$orderId.'\';
            window.userId = \''.$order->getUserId().'\';
        </script>';
    }

    public static function getBlockContent($blockCode, $selectedTab, $args)
    {
        $result = '';
        $order = $args['ORDER'];
        $orderId = !empty($order) ? $order->getId() : 0;

        if ($selectedTab == 'tab_order' && $blockCode == 'changedelivery') {
            if (!$orderId) return $result;

            $canChangeDelivery = ($order->getField('STATUS_ID') == 'N' && !$order->isPaid() && !$order->isCanceled());

            $arProps = self::getProps($order);

            $result .= '<div id="changeDelivery">';

            $arLocation = Location::getLocationData(false, $arProps[self::PROP_CODE_CITY]);

            $arDeliveryIDs = $order->getDeliverySystemId();

            $arBlock = [
                [
                    'TITLE' => 'Omni Channel',
                    'CLASS' => '',
                    'TEXT'  => '<b id="omniChannel" data-delivery="'.implode(',', $arDeliveryIDs).'">
                        '.$arProps['OMNI_CHANNEL'].'
                        </b> ('.Channel::getChannelName($arProps['OMNI_CHANNEL']).')',
                ],
                [
                    'TITLE' => 'Местоположение',
                    'CLASS' => '',
                    'TEXT'  => implode(', ', $arLocation['PATH']),
                ],
            ];

            $arStoreData = []; // остатки в текущем РМ
            if ($arProps['OMNI_CHANNEL'] == 'Pick_Point') {
                // выберем
                $arBlock[] = [
                    'TITLE' => 'Пункт выдачи',
                    'CLASS' => '',
                    'TEXT'  => $arProps[self::PROP_CODE_ADDRESS],
                ];
            } elseif ($arProps['OMNI_CHANNEL'] == 'Delivery' || $arProps['OMNI_CHANNEL'] == 'DayDelivery' || $arProps['OMNI_CHANNEL'] == 'OMNI_Delivery') {
                // курьер
                $arBlock[] = [
                    'TITLE' => 'Адрес доставки',
                    'CLASS' => '',
                    'TEXT'  => $arProps[self::PROP_CODE_ADDRESS],
                ];
            } else {
                // самовывоз
                $arBlock[] = [
                    'TITLE' => 'Розничный магазин',
                    'CLASS' => '',
                    'TEXT'  => $arProps[self::PROP_CODE_ADDRESS],
                ];

                if ($arProps['STORE_ID']) {
                    $arStore = false;

                    $st = \CCatalogStore::GetList([], ['ID' => $arProps['STORE_ID']]);
                    $arStore = $st->Fetch();

                    if (!$arStore) {
                        $st = \CCatalogStore::GetList([], ['XML_ID' => $arProps['STORE_ID']]);
                        $arStore = $st->Fetch();
                    }

                    if ($arStore) {
                        $arStore['TITLE'] = explode(',', $arStore['TITLE']);
                        array_shift($arStore['TITLE']);
                        $arStore['TITLE'] = implode('<br />', $arStore['TITLE']);
                        $arStoreData['STORE'] = $arStore;

                        // получим остатки по товарам
                        $ba = Internals\BasketTable::getList(
                            [
                                'filter' => ['ORDER_ID' => $orderId],
                                'select' => ['ID', 'PRODUCT_ID']
                            ]
                        );
                        while ($arBasket = $ba->Fetch()) {
                            $st = \CCatalogStoreProduct::GetList(
                                [],
                                [
                                    'STORE_ID'   => $arStore['ID'],
                                    'PRODUCT_ID' => $arBasket['PRODUCT_ID'],
                                    '>AMOUNT'    => 0,
                                ]
                            );
                            if ($arStoreProduct = $st->Fetch()) {
                                $arStoreData['AMOUNT'][$arBasket['ID']] = $arStoreProduct['AMOUNT'];
                            } else {
                                $arStoreData['AMOUNT'][$arBasket['ID']] = 0;
                            }
                        }
                    }
                }
            }

            if ($canChangeDelivery) {
                $arBlock[] = [
                    'TITLE' => '<span class="adm-btn changeLocation" data-location="'.$arProps[self::PROP_CODE_CITY].'">Сменить местоположение</span>',
                    'TEXT'  => '<span class="adm-btn changeDelivery">Загрузить доступные варианты доставки</span>',
                    'ACT'   => 'Y',
                    'CLASS' => '',
                ];
            }

            $result .= self::printBlock('Текущая доставка', $arBlock);

            if ($canChangeDelivery) {
                ob_start();
                global $APPLICATION;
                $APPLICATION->IncludeComponent(
                    'bitrix:sale.location.selector.search',
                    '',
                    [
                        "CACHE_TIME"                 => "36000000",
                        "CACHE_TYPE"                 => "A",
                        "CODE"                       => $arProps[self::PROP_CODE_CITY],
                        "FILTER_BY_SITE"             => "N",
                        "ID"                         => $arLocation['ID'],
                        "INITIALIZE_BY_GLOBAL_EVENT" => "",
                        "INPUT_NAME"                 => "NEW_LOCATION",
                        "JS_CALLBACK"                => "saveLocationFromField",
                        "JS_CONTROL_GLOBAL_ID"       => "",
                        "PROVIDE_LINK_BY"            => "id",
                        "SHOW_DEFAULT_LOCATIONS"     => "N",
                        "SUPPRESS_ERRORS"            => "N"
                    ],
                    false
                );

                $locationHtml = ob_get_contents();
                ob_end_clean();

                $result .= self::printBlock(
                    'Сменить местоположение',
                    [
                        [
                            'TITLE' => 'Текущее местоположение',
                            'CLASS' => '',
                            'TEXT'  => implode(', ', $arLocation['PATH']),
                        ],
                        [
                            'TITLE' => 'Новое местоположение',
                            'CLASS' => 'new-location',
                            'TEXT'  => $locationHtml,
                        ],
                        [
                            'TITLE' => '',
                            'CLASS' => 'change-location-btn none',
                            'TEXT'  => '<span class="adm-btn saveLocation">Сохранить</span>',
                            'ACT'   => 'Y',
                        ],
                    ],
                    'change-location none'
                );

                // сюда загружаются возможные варианты доставок
                $result .= '<div class="change-delivery-block"></div>';
            }

            $result .= '</div>';

            if ($arStoreData) {
                $result .= '<script type="text/javascript">window.storeAmount = '.Json::encode($arStoreData).';</script>';
            }
        }

        return $result;
    }

    private static function printBlock($title = '', $arData = [], $blockClass = '')
    {
        $result = '
            <div class="adm-bus-table-container caption border sale-order-props-group '.$blockClass.'">
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

    public static function getProps($order, $getArray = false)
    {
        $arProps = [];
        if ($getArray) {
            $pr = \CSaleOrderPropsValue::GetOrderProps($order->getId());
            while ($arProp = $pr->Fetch()) {
                $arProps[$arProp['CODE']] = $arProp;
            }
        } else {
            $arPropCollection = $order->getPropertyCollection();
            $arPropsData = $arPropCollection->getArray();
            $arProps = [
                'EMAIL'                     => '',
                'PHONE'                     => '',
                'PERSONAL_MOBILE'           => '',
                'PROGRAMM_LOYALTY_CARD'     => '',
                'PROGRAMM_LOYALTY_WRITEOFF' => '',
            ];
            foreach ($arPropsData['properties'] as $arProp) {
                $arProps[$arProp['CODE']] = $arProp['VALUE'][0];
            }

            $arProps['PHONE'] = ($arProps['PHONE']) ?: $arProps['PERSONAL_MOBILE'];
            $arProps['CARD'] = $arProps['PROGRAMM_LOYALTY_CARD'];
            $arProps['BONUS'] = (int)$arProps['PROGRAMM_LOYALTY_WRITEOFF'];
        }

        return $arProps;
    }

}