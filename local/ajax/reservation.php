<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use \Bitrix\Main\Loader;
use \Bitrix\Main\Context;
use \Bitrix\Sale\Basket;
use \Bitrix\Sale\Fuser;
use \Bitrix\Sale\Order;
use \Bitrix\Sale\Delivery;
use \Jamilco\Omni\Channel;
use \Jamilco\Main;
use \Bitrix\Currency\CurrencyManager;

global $USER;

$request = Context::getCurrent()->getRequest();

$productId = $request->get('product_id');
if ($productId) {
    Loader::IncludeModule('iblock');
    Loader::IncludeModule('catalog');
    Loader::IncludeModule('sale');

    $deliveryId = 11;

    $arStore = \CCatalogStore::GetList(array(), array('XML_ID' => $request->get('store_id')))->Fetch();

    $orderUserId = 33; // 33 id спец аккаунт,
    if ($USER->isAuthorized()) {
        $orderUserId = $USER->GetID();
    } else {
        $rsUser = \CUser::GetByLogin($request->get('email'));
        if ($arUser = $rsUser->Fetch()) {
            $orderUserId = $arUser['ID'];
        } else {
            $password = randString(8);
            $arUser = $GLOBALS['USER']->Register($request->get('email'), $request->get('name'), '', $password, $password, $request->get('email'));
            $orderUserId = $arUser['ID'];
        }
    }

    $arProducts = \CCatalogProduct::GetByID($productId);
    \CCatalogProduct::Update(
        $arProducts['ID'],
        array(
            'QUANTITY' => $arProducts['QUANTITY'] + 1
        )
    );

    $siteId = Context::getCurrent()->getSite();
    $fUserId = Fuser::getId();

    $currencyCode = CurrencyManager::getBaseCurrency();

    // отложим все товары
    Main\Utils::delayAllBasket();

    $order = Order::create($siteId, $orderUserId);
    $order->isStartField();

    // Создаём корзину с одним товаром
    $basket = Basket::create($siteId);
    $item = $basket->createItem('catalog', $productId);
    $item->setFields(array(
        'QUANTITY' => 1,
        'CURRENCY' => $currencyCode,
        'LID' => $siteId,
        'PRODUCT_PROVIDER_CLASS' => '\CCatalogProductProvider',
    ));
    $checkBasketItemId = $item->getId();

    $order->setBasket($basket);

    $shipmentCollection = $order->getShipmentCollection();
    $shipment = $shipmentCollection->createItem();

    $service = Delivery\Services\Manager::getById($deliveryId);
    $shipment->setFields(
        [
            'DELIVERY_ID'   => $service['ID'],
            'DELIVERY_NAME' => $service['NAME'],
            'CURRENCY'      => $order->getCurrency(),
        ]
    );

    $shipmentItemCollection = $shipment->getShipmentItemCollection();
    foreach ($order->getBasket() as $item) {
        $shipmentItem = $shipmentItemCollection->createItem($item);
        $shipmentItem->setQuantity($item->getQuantity());
    }

    $paymentCollection = $order->getPaymentCollection();
    $payment = $paymentCollection->createItem(
        Bitrix\Sale\PaySystem\Manager::getObjectById(3) // 3 - ID платежной системы
    );

    $payment->setField("SUM", $order->getPrice());
    $payment->setField("CURRENCY", $order->getCurrency());

    $order->setField('USER_DESCRIPTION', 'Бронирование товара в магазине '.$arStore['TITLE']);

    // св-ва заказа
    $collection = $order->getPropertyCollection();
    $orderProps = $collection->getArray();
    $arPropsIdsToUpdate = [];

    foreach($orderProps['properties'] as $arProp)
    {
        switch($arProp['CODE']) {
            case 'OMNI_TABLET_ID':
                if ($request->get('tablet')) {
                $arPropsIdsToUpdate[$arProp['ID']] = $request->get('tablet');
                }
                break;
            case 'OMNI_CHANNEL':
                if (Loader::includeModule('jamilco.omni') && $omniType = $request->get('omni_type')) {
                    $arPropsIdsToUpdate[$arProp['ID']] = Channel::getTypeByShopType($omniType);
                }
                break;
            case 'STORE_ID':
                if ($request->get('store_id')) {
                    $arPropsIdsToUpdate[$arProp['ID']] = $request->get('store_id');
                }
                break;
            case 'F_ADDRESS';
                if ($arStore['ADDRESS']) {
                    //$arPropsIdsToUpdate[$arProp['ID']] = $arStore['ADDRESS'];
                }
                break;
            case 'NAME':
                if ($request->get('name')) {
                    $arPropsIdsToUpdate[$arProp['ID']] = $request->get('name');
                }
                break;
            case 'EMAIL':
                if ($request->get('email')) {
                    $arPropsIdsToUpdate[$arProp['ID']] = $request->get('email');
                }
                break;
            case 'PHONE':
                if ($request->get('phone')) {
                    $arPropsIdsToUpdate[$arProp['ID']] = $request->get('phone');
                }
                break;
            default:
                break;
        }
    }
    foreach($arPropsIdsToUpdate as $propId => $val)
    {
        $propValue = $collection->getItemByOrderPropertyId($propId);
        $r = $propValue->setValue($val);
        //var_dump($r->getErrorMessages());

    }
    $order->doFinalAction(true);
    $order->save();

    $orderId = $order->getId();

    // удалить добавленный товар и вернуть обратно отложенные товары
    \CSaleBasket::Delete($checkBasketItemId);
    Main\Utils::unDelayBasket();

    \CCatalogProduct::Update(
        $arProducts['ID'],
        array(
            'QUANTITY' => $arProducts['QUANTITY']
        )
    );

    echo '<h5>Спасибо за заказ!</h5> <br><br>Номер вашего бронирования <br><h5>№'.$order->getField('ACCOUNT_NUMBER').'</h5>';

    // срок резерва
    $untilDate = 3;
    $arEventFields = array(
        "STORE_TITLE"   => $arStore['TITLE'],
        "STORE_ADDRESS" => $arStore['ADDRESS'],
        "ORDER_NUM"     => $order->getField('ACCOUNT_NUMBER'),
        "EMAIL"         => $request->get('email'),
        "NAME"          => $request->get('name'),
        "PHONE"         => $request->get('phone'),
        "UNTIL_DATE"    => date("d.m.Y", AddToTimeStamp(array("DD" => ($untilDate)), MakeTimeStamp($order->getField("ORDER_DATE"))))
    );
    //\CEvent::Send("RESERVATION_IN_STORE", 's1', $arEventFields);

    /** DigitalDataLayer start */
    $ddlUserInfo = [
        'name' => filter_var($request->getPost('name'), FILTER_SANITIZE_STRING),
        'phone' => filter_var($request->getPost('phone'), FILTER_SANITIZE_NUMBER_INT),
        'email' => filter_var($request->getPost('email'), FILTER_SANITIZE_EMAIL)
    ];
    $ddlSkuId = (int)$request->getPost('sku_id');

    /** Данные о заказе */
    $ddlOrderInfo = [
        'accountNumber' => $order->getField('ACCOUNT_NUMBER'),
        'totalSum' => $order->getPrice() - $order->getDeliveryPrice(),
        'currency' => $order->getCurrency()
    ];

    /** Содержимое корзины */
    $ddlCartObject = \DigitalDataLayer\Manager::getInstance()->doProcessCartObject([], $orderId);

    ?>
    <script>
        if (typeof window.digitalData.events !== 'undefined') {
            var transaction = {
              'category': 'Ecommerce',
              'name': 'Completed Transaction',
              'transaction': {
                'orderId': '<?= $ddlOrderInfo['accountNumber'] ?>',
                'checkoutType': 'reservation',
                'currency': '<?= $ddlOrderInfo['currency'] ?>',
                'subtotal': <?= $ddlCartObject['subtotal'] ?>,
                'total': <?= $ddlOrderInfo['totalSum'] ?>,
                'lineItems': <?= json_encode($ddlCartObject['lineItems'], JSON_UNESCAPED_UNICODE) ?>,
                'contactInfo': {
                  'firstName': '<?=$ddlUserInfo['name']?>',
                  'email': '<?=$ddlUserInfo['email']?>',
                  'phone': '<?=$ddlUserInfo['phone']?>'
                },
                'vouchers': []
              }
            };
            // если бронирует незалогиненый пользователь, нужно добавить объект user
            if (typeof window.digitalData.user.userId === 'undefined') {
                transaction.user = {
                  'firstName':  '<?=$ddlUserInfo['name']?>',
                  'email': '<?=$ddlUserInfo['email']?>',
                  'phone': '<?=$ddlUserInfo['phone']?>'
                };
            }
            window.digitalData.events.push(transaction);
        }
    </script><?php
    /** DigitalDataLayer end */

    /** Adspire start */
    $adspire = \Adspire\Manager::getInstance();

    $adspireUserInfo = [
        'name' => filter_var($request->getPost('name'), FILTER_SANITIZE_STRING),
        'phone' => filter_var($request->getPost('phone'), FILTER_SANITIZE_NUMBER_INT),
        'email' => filter_var($request->getPost('email'), FILTER_SANITIZE_EMAIL)
    ];


        // Содержимое корзины
        $basket = $order->getBasket();

        $productIds = [];
        $products = [];
        foreach ($basket as $basketItem) {
            $productId = $basketItem->getProductId();
            $productIds[] = $productId;

            // необходимо сохранить id, кол-во, стоимость
            $products[$productId]['variantId'] = $productId;
            $products[$productId]['price'] = $basketItem->getPrice();
            $products[$productId]['quantity'] = $basketItem->getQuantity();
        }

        $productsInfo = $adspire->fillProductObject($productIds);

        $orderItems = [];
        foreach ($productsInfo as $arItem) {
            $orderItems[] = [
                'cid'        => $arItem['cid'],
                'cname'      => array_pop($arItem['cname']),
                'pid'        => $arItem['pid'],
                'pname'      => $arItem['pname'],
                'quantity'   => $products[$arItem['variant_id']]['quantity'],
                'price'      => (float)$products[$arItem['variant_id']]['price'],
                'currency'   => $arItem['currency'],
                'variant_id' => $arItem['variant_id'],
            ];
        }

        // тип email
        $rsOrders = Order::getList([
            'select' => [
                'ID'
            ],
            'filter' => [
                'USER_ID' => $order->getUserId(),
                'LID'     => SITE_ID
            ],
            'count_total' => true
        ]);
        if ($rsOrders->getSelectedRowsCount() > 1) {
            $userMailType = 'old';
        } else {
            $userMailType = 'new';
        }

        $arOrder = [
            'id'         => $orderId,
            'type'       => 'reserve',
            'totalprice' => (float)$order->getPrice(),
            'coupon'     => '',
            'usermail'   => $userMailType,
            'userphone'  => 'old',
            'name'       => $ddlUserInfo['name'],
            'lastname'   => '',
            'email'      => $ddlUserInfo['email'],
            'phone'      => $ddlUserInfo['phone'],
        ];

        $adspire->setContainerElement([
            'push' => [
                'TypeOfPage' => 'confirm',
                'Order'      => $arOrder,
                'OrderItems' => $orderItems
            ]
        ]);

        echo $adspire->getContainer();

    /** Adspire end */

} else {
    echo 'Ошибка';
}
