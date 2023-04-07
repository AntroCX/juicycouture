<?php
namespace Jamilco\Ocs;

class ProductConfig
{
    public static $serverName = "juicycouture.ru";
    public static $pathToLog = "/local/api/log/product/";
    public static $tmpImagePath = "/upload/ocs_imgs_sync/"; // папка для фото

    /** общие настройки */
    public static $mode = ""; // test - карточка товара сохраняется, но не обрабатывается
    public static $activateSku = false; // Активировать торг. предл. при создании
    public static $updateItem = false; // обновлять уже заведенный товар
    public static $picturesLocation = "sku"; // расположение картинок: 'item' /'sku'
    public static $picturesIblock = ""; // картинки в отд. ИБ
    public static $picturesIblockProp = ""; // св-во картинки в отд. ИБ
    public static $updatePictures = false; // обновлять картинки товара
    public static $updatePicturesEvenIfOne = false; // обновлять доп. картинки, даже если всего одна в выгрузке
    public static $useCatalogBasePriceInitial = false; // устанавливтаь начальную цену
    public static $sendMail = true; // отправлять email
    public static $sendOnlyMailError = false; // отправлять только при ошибке
    public static $stepMode = false; // пошаговая обработка // TODO

    /** xml теги */
    public static $xmlArticulTag = "artnum"; // тег артикула в xml
    public static $xmlColorTag = ["color", "color_name_orig"]; // тег цвета в xml
    public static $xmlGenderTag = "sex"; // тег пола в xml
    public static $xmlAgeTag = "age"; // тег Возраст в xml
    public static $xmlSizeTypeTag = "department"; // тег для определения типа размера в xml
    public static $xmlPhotoTag = ["photos", "photo"]; // тег фото в xml
    public static $xmlTechnologyTag = "technology";

    /** коды свойств */
    public static $articulPropCode = "ARTNUMBER";
    public static $genderPropCode = "SEX";
    public static $picturePropCode = "MORE_PHOTO";
    public static $cml2PropCode = "CML2_LINK";
    public static $technologyPropCode = 'TECHNOLOGY';

    /** св-ва, значения которых должны быть заведены на сайте */
    public static $arMandatoryProps = [
        'COLOR'
    ];

    /** параметры торг. каталога */
    public static $catalogBasePriceId = 1;
    public static $catalogBasePriceInitial = 0.00;
    public static $catalogMeasureId = 5;
    public static $catalogMeasureRatio = 1;
    public static $catalogWeight = 0;

    /** соответствие полей xml -> Iblock */
    public static $arrFieldsMap = [
        "name" => "NAME",
        "preview_text" => "PREVIEW_TEXT",
        "description_text" => "DETAIL_TEXT",
        "active_from" => "ACTIVE_FROM",
        "active_to" => "ACTIVE_TO",
        "sections_id" => "IBLOCK_SECTION_ID"
    ];

    /** соответствие св-в xml -> Iblock */
    public static $arrPropsMap = [
        "product" => [
            "artnum"     => "ARTNUMBER",
            "department" => "TYPE",
            "subseason"  => "SEASON",
            "technology" => "TECHNOLOGY",
            "материал"   => "MATERIAL_STR",
            "sex"        => "SEX",
            "age"        => "AGE",
            "метка"      => ["NEWPRODUCT", "SALELEADER"],
            "name_rus"   => "PRODUCT_CATEGORY"
        ],
        "sku" => [
            "artnum"  => "ARTNUMBER",
            'size'    => ["SIZES_SHOES", "SIZES_CLOTHES", "SIZES_ACCESSORIES"], // маппинг определяется в Ocs\Product::checkSizeType()
            'barcode' => "BARCODE",
            'gtin'    => "GTIN",
            "color"   => "COLOR" // цвет добавляем в sku
        ]
    ];

    /** для определения метки по значению */
    public static $arIblock2XmlLabels = array(
        "NEWPRODUCT" => "Новинка",
        "SALELEADER" => "Хит продаж"
    );

    /** соответствие типа товара св-ву размера  */
    public static $arrSizePropMap = [
        "Обувь"      => "SIZES_SHOES",
        "Одежда"     => "SIZES_CLOTHES",
        "Аксессуары" => "SIZES_ACCESSORIES"
    ];

    /** соответствие типов товара осн. товарным категория сайта */
    public static $arrDepartmentMap = [
        "носки" => "Аксессуары",
        "чулки" => "Аксессуары",
        "Чулки, носки" => "Аксессуары"
    ];

    /** cв-ва, заданные в атрибутах */
    public static $arrPropsAttr = [
        "материал",
        "метка"
    ];

    /** cв-ва, заданные в атрибутах (конвертировать в HTML) */
    public static $arrPropsAttrHtml = [
        "материал"
    ];

    /** исключенные артикулы */
    public static $arExcludedArticuls = [
        "S3000",
        "S5000",
        "S7000",
        "S10000",
        "S15000",
        "S20000",
        "TIM010300",
        "TIM010500",
        "TIM010700",
        "TIM011000",
        "TIM011500",
        "TIM012000"
    ];

    /** получатели email-уведомлений */
    public static $arMailReceivers = [
        'galiev@jamilco.ru',
        'suvorova@jamilco.ru'
    ];

}