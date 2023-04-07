<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(check_bitrix_sessid()&&$_POST['id']) {
    \CModule::IncludeModule('sale');
    \CSaleBasket::Delete($_POST['id']);
    \Jamilco\Main\Utils::deleteGifts(); // удаляем все подарки
}