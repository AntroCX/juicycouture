<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Размерная сетка");
?>
<style>
    .table.table-striped.table-hover th{
        padding-left: 20px;
        padding-right: 20px;
        vertical-align: middle;
        border-right: 1px solid #ccc;
    }
    tbody td:nth-child(1), tbody td:nth-child(2), tbody td:nth-child(3) {
        font-weight: bold;
    }
    tbody tr:nth-child(3) td:nth-child(3) {
        font-weight: normal;
    }
    tbody tr:nth-child(5) td:nth-child(3) {
        font-weight: normal;
    }
    tbody tr:nth-child(7) td:nth-child(3) {
        font-weight: normal;
    }
</style>

    <h1>Размерная сетка</h1>
    <div class="row">
        <div class="col-xs-12 col-md-12">
            <?$APPLICATION->IncludeComponent(
                "bitrix:main.include",
                "",
                Array(
                    "AREA_FILE_SHOW" => "file",
                    "AREA_FILE_SUFFIX" => "inc",
                    "EDIT_TEMPLATE" => "",
                    "PATH" => "/local/includes/sizeChart.php"
                )
            );?>
        </div>
    </div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>