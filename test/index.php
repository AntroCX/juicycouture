<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("test");
?>

<?
$arFilter = Array(
 "IBLOCK_ID"=>1,
 "ACTIVE"=>"Y",
"ID" => 46766
 );
$res = CIBlockElement::GetList(Array("SORT"=>"ASC", "PROPERTY_PRIORITY"=>"ASC"), $arFilter);
while($ar_fields = $res->GetNext())
{
 pr($ar_fields);
}


?>

<?$APPLICATION->IncludeComponent(
	"innova:instagram", 
	".default", 
	array(
		"AUTOPLAY" => "false",
		"AUTOPLAY_SPEED" => "3000",
		"CACHE_TYPE" => "N",
		"COUNT_COLS_SLIDE" => "1",
		"COUNT_IMAGE" => "10",
		"GRID_CLICK" => "img",
		"GRID_MAX_WIDTH" => "300",
		"REFRESH_TIME" => "1440",
		"SPEED" => "500",
		"TOKEN" => "8642661830.fa1a575.10ab3ef3446a4a71ac6aaea1a114cfdf",
		"VIEW_TYPE" => "grid_slider",
		"COMPONENT_TEMPLATE" => ".default"
	),
	false
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>