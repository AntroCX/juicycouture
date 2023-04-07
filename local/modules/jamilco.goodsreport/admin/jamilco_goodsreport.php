<?
/** @global CMain $APPLICATION */
use Bitrix\Main,
    Bitrix\Main\Loader;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$module_id = 'jamilco.goodsreport';
if ($APPLICATION->GetGroupRight($module_id) == 'D')
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

Loader::includeModule('iblock');

/** НАСТРОЙКИ */
/************************/
$upload_file_path = "/upload/jamilco_goodsreport/goods.csv";
CheckDirPath($_SERVER["DOCUMENT_ROOT"].$upload_file_path);
/************************/
// заголовки таблицы
$arFieldTitles = [
    "Артикул",
    "Модель",
    "Размер",
    "Активность",
    "Доступн. количество",
    "Цена активная",
    "Цена до скидки",
    "Название",
    "Описание",
    "Состав (материал)",
    "Технологии",
    "Раздел сайта",
    "Коллекция на сайте",
    "Цвет",
    "Сезон",
    "Год Коллекции"
];
/************************/
// размер выборки для пошагового исполнения
$max = 500;
/************************/

/** НАСТРОЙКИ */

    $aTabs = array(
        array("DIV" => "jamililco_goodsreport", "TAB" => "Выгрузить список товаров", "TITLE" => "Выгрузить список товаров")
    );

    $tabControl = new CAdminTabControl("tabControl", $aTabs);
    ?>
    <form id="j_goodsreport_form" method="post" action="" enctype="multipart/form-data" name="j_goodsreport_form">
        <?= bitrix_sessid_post() ?>
        <?
        $tabControl->Begin();
        $tabControl->BeginNextTab();
        ?>
        <?
        $tabControl->Buttons();
        ?>
        <input type="submit" name="save"
               value="Начать"
               title="" class="adm-btn-save">
        <?
        $tabControl->End();
        ?>
    </form>
    <?
    echo BeginNote();
    ?>
    Файл выгрузки будет содержать все товары на сайте.<br>
    Будут выгружены след. поля:
    <?$str = "";
    foreach($arFieldTitles as $title){
        $str .= ($str? ', ': '').'<'.$title.'>';
    }
    ?>
    <b><?=$str?></b>
    <?
    echo EndNote();
    ?>
<?
if(file_exists($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.goodsreport/admin/jamilco_goodsreport.php")) {
    $req = "/local/modules/jamilco.goodsreport/ajax.php";
} else {
    $req = "/bitrix/modules/jamilco.goodsreport/ajax.php";
}
?>
<?CJSCore::Init(array("jquery"));?>
<script>
  $(function(){
    var $container = $('#jamililco_goodsreport_edit_table tbody'),
        step = 1;

    function func_ajax(e, step) {
      e.preventDefault();
      $.ajax({
        method : "POST",
        url    : '<?=$req?>',
        dataType  : 'json',
        data   : {
          sessid : '<?=bitrix_sessid()?>',
          step   : step,
          path   : '<?=$upload_file_path?>',
          max    : <?=$max?>,
          titles : <?=json_encode($arFieldTitles, JSON_UNESCAPED_UNICODE)?>}
      }).done(function(data) {
        if(typeof data !== 'undefined') {
          if (typeof data.error !== 'undefined') {
            $container.html(data.error);
          } else if (typeof data.result !== 'undefined') {
            $container.html(data.result);
            $('.adm-btn-load').prop('disabled', false).removeClass('adm-btn-load');
            $('.adm-btn-load-img-green').remove();
          } else if (typeof data.step !== 'undefined') {
            $container.html('<tr><td>Обработка запроса... шаг: ' + data.step + ' из '+data.total+'</td></tr>');
            func_ajax(e, data.step);
          }
        }
      });
      return false;
    }

    $('body').on('submit', '#j_goodsreport_form', func_ajax);

  });
</script>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
