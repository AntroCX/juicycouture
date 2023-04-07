<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use \Bitrix\Main\Type\DateTime;

CModule::IncludeModule('iblock');
CModule::IncludeModule("sale");

$arGroupsID = [1]; // изменения каких пользовательских групп выводятся в отчет
$percentToShow = 0.8;
$showBasketRemoved = ($_GET['show'] == 'all') ? true : false;

echo '<p></p><p><b>
        Выводятся заказы с изменной общей стоимостью более чем на 20% и со статусом "Выполнен".<br /> 
        Дополнительно проверяется изменение цены товара внутри заказа.<br />
        Изменения цены, связанные с удалением товаров из заказа, '.($showBasketRemoved ? 'отображены' : 'скрыты').' 
        (<a href="?show='.($showBasketRemoved ? 'no' : 'all').'">'.($showBasketRemoved ? 'скрыть' : 'отобразить').'</a>).
        </b></p>';
echo '<table border="1" cellpadding="5">
        <tr>
            <th>Номер заказа</th>
            <th>Дата изменения</th>
            <th>Кем изменено</th>
            <th>Старая сумма заказа</th>
            <th>Новая сумма заказа</th>
            <th>Комментарий менеджера</th>
        </tr>';

$orderChange = new CSaleOrderChange();

$history = $orderChange->GetList(
    ["ID" => "DESC"],
    ["TYPE" => "ORDER_PRICE_CHANGED"],
    false,
    false,
    ["ID", "ORDER_ID", "USER_ID", "DATE_MODIFY", "DATA"]
);

while ($ob = $history->GetNext()) {

    if (!$showBasketRemoved) {
        $start = new DateTime($ob['DATE_MODIFY']->toString());
        $end = new DateTime($ob['DATE_MODIFY']->toString());
        $start->add('-30 seconds');
        $end->add('30 seconds');

        $item = $orderChange->GetList(
            [],
            [
                "TYPE"          => "BASKET_REMOVED",
                "ORDER_ID"      => $ob['ORDER_ID'],
                ">=DATE_MODIFY" => $start,
                "<=DATE_MODIFY" => $end,
            ]
        );
        if ($arOne = $item->Fetch()) continue; // пропускаем строку, если она возникла при удалении товара из заказа
    }

    $data = htmlspecialchars_decode($ob['DATA']);
    $data = unserialize($data);

    if ($data['OLD_PRICE'] * $percentToShow <= $data['PRICE']) continue;

    $item = $orderChange->GetList([], ["TYPE" => "BASKET_PRICE_CHANGED", "ORDER_ID" => $ob["ORDER_ID"]], false, false, ["DATA"]);
    if ($item->SelectedRowsCount() != 0) {
        $user = CUser::GetByID($ob["USER_ID"])->Fetch();
        $groups = CUser::GetUserGroup($ob["USER_ID"]);
        $name = $user['NAME'].' '.$user['LAST_NAME'].' ('.$user['EMAIL'].')';
        $order = CSaleOrder::GetByID($ob["ORDER_ID"]);
        if ($order['STATUS_ID'] == 'F'
            && array_intersect($arGroupsID, $groups)
        ) {

            $commentAdd = getCommentsFromIblock($order['ID']);

            echo '<tr>
                    <td><a href="/bitrix/admin/sale_order_view.php?ID='.$ob["ORDER_ID"].'" target="_blank">'.$order['ACCOUNT_NUMBER'].'</a></td>
                    <td>'.$ob['DATE_MODIFY'].'</td>
                    <td>'.$name.'</td>
                    <td>'.$data['OLD_PRICE'].'</td>
                    <td>'.$data['PRICE'].'</td>
                    <td>
                        '.TxtToHTML($order['COMMENTS']).'
                        '.(($commentAdd) ? '<hr /><b>Изменения больше 30%:</b><hr />'.implode('<hr />', $commentAdd) : '').'
                </tr>';
        }
    }
}
echo '</table>';

/**
 * возвращает комментарии по изменению цен в заказах больше 30%
 *
 * @param int $orderId
 *
 * @return array|bool
 */
function getCommentsFromIblock($orderId = 0)
{
    if (!defined('IBLOCK_CHANGE_ORDER_ID') || !IBLOCK_CHANGE_ORDER_ID) return false;

    $arRes = [];
    $el = \CIblockElement::GetList(
        [],
        [
            'IBLOCK_ID'        => IBLOCK_CHANGE_ORDER_ID,
            'PROPERTY_ORDER'   => $orderId,
            '!PROPERTY_ACCEPT' => false,
        ],
        false,
        ['nTopCount' => 5],
        [
            'ID',
            'PROPERTY_COMMENT',
        ]
    );
    while ($arItem = $el->Fetch()) {
        $comment = trim($arItem['PROPERTY_COMMENT_VALUE']);
        if ($comment) $arRes[] = TxtToHTML($comment);
    }

    return $arRes;
}