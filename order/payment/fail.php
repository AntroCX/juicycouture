<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

global $MESS;
include(GetLangFileName(dirname(__FILE__)."/", "/fail.php"));


echo "<div style = 'text-align: center; font-size: 19px;'>";
$APPLICATION->SetTitle("Неудача");
echo "Извините, произошла ошибка при оплате, попробуйте еще раз<br>при повторе ошибки свяжитесь с Банком-Эмитентом Вашей карты";
echo "</div>";

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");