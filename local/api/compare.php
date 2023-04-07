<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule('catalog');
CModule::IncludeModule('iblock');

$data = CIBlockPriceTools::GetOffersArray(array("IBLOCK_ID" => 1),array(),array(),array("ID","IBLOCK_ID","NAME"));

$el = new CIBlockElement;

$SKU = array();

foreach($data as $item){
	$name = trim($item['NAME']);
	$SKU[$item['ID']] = $name;
    $SKU_id[$item['NAME']] = $item['ID'];
}

function get_xml($type){
	$stock_items[]=array();

	$files = scandir($_SERVER['DOCUMENT_ROOT'].'/api/log/'.$type, SCANDIR_SORT_DESCENDING);

	$stock = simplexml_load_file($_SERVER['DOCUMENT_ROOT'].'/api/log/'.$type.'/'.$files[0]);
    
	foreach ($stock->sku as $item) {
		$stock_items[] = (string)$item->attributes()->code;
	}
    
	return $stock_items;
}

$stock = array_unique(get_xml('sku_quantities') + get_xml('retail_sku_quantities'));
$result = array_diff($SKU,$stock);
$result2 = array_diff($stock,$SKU);

?>
SCU count = <?=count($SKU);?>
<details>
<summary>Нет в OCS(<?=count($result)?>)</summary>
<?
foreach(array_values($result) as $item){
    echo '<li>'.$item.' - '.$SKU_id[$item];
    #$res = $el->Update($SKU_id[$item], array("NAME"=> trim($item)));
}
?>
</details>
<details>
<summary>Нет на сайте(<?=count($result2)?>)</summary>
<?
foreach(array_values($result2) as $item){
    if(!is_array($item)) echo '<li>'.$item;
}
?>
</details>
