<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

global $MESS;
include(GetLangFileName(dirname(__FILE__)."/", "/fail.php"));

$APPLICATION->SetTitle(GetMessage("RSB_ACQUIRING_NEUDACA"));

echo GetMessage("RSB_ACQUIRING_IZVINITE_PROIZOSLA");

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");