<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * Компонент поставляется вместе с модулем jamilco.loyalty
 */
?>
<?if($arResult['ACTIVE'] == 'Y'):?>
<div class="b-loyalty">
    <div class="b-loyalty__content">
        <?if($_REQUEST['AJAX_LOYALTY'] == 'Y') {
            $GLOBALS['APPLICATION']->RestartBuffer();
        }?>
        <div class="form-group">
            <input class="form-control" type="number" maxlength="8" name="LOYALTY_CARD_NUMBER" value="<?=$_REQUEST['LOYALTY_CARD_NUMBER']?>">
            <?if($arResult['ADDITIONAL_SUM']):?>
                <div class="b-loyalty__annotation">
                    За покупку вам будет начислено до <?=CurrencyFormat($arResult['ADDITIONAL_SUM'], 'RUB')?> баллов на карту бонусной программы <?=$_REQUEST['LOYALTY_CARD_NUMBER']?>
                </div>
            <?else:?>
                <div class="b-loyalty__annotation">
                    Товары, которые находятся у вас в корзине, не участвуют в бонусной программе и не получится списать баллы с вашей карты
                </div>
            <?endif?>
            <? if (array_key_exists('CARD_BALANCE', $arResult['CARD_BALANCE'])): ?>
            <div class="row">
                <div class="col-sm-6">
                    <div>
                        <div>
                            Бонусы на карте: <?=CurrencyFormat($arResult['CARD_BALANCE'], 'RUB')?>
                        </div>
                        <div>
                            Бонусы к списанию: <?=CurrencyFormat($arResult['WRITEOFF_SUM'], 'RUB')?>
                        </div>
                    </div>
                </div>
                <?if($arResult['CARD_BALANCE'] > 0 && $arResult['ADDITIONAL_SUM']):?>
                <div class="col-sm-6">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox"
                                   name="APPLY_BONUS"
                                   id="APPLY_BONUS"
                                    <?if($_REQUEST['APPLY_BONUS'] == 'Y'):?>checked<?endif?>
                                   value="Y"> Использовать бонусы
                        </label>
                    </div>
                </div>
                <?endif?>
            </div>
            <?endif?>
        </div>

        <?/*
        \CModule::IncludeModule('sale');
        $rsBasket = \CSaleBasket::GetList(
            array(),
            array(
                'FUSER_ID' => \CSaleBasket::GetBasketUserID(),
                'LID' => 's1',
                'ORDER_ID' => 'NULL',
                'CAN_BUY' => 'Y'
            )

        );

        while ($arrBasket = $rsBasket->Fetch()) {

            echo '<pre>';
            print_r($arrBasket);
            echo '</pre>';

            $db_res = \CSaleBasket::GetPropsList(
                array(
                    "SORT" => "ASC",
                    "NAME" => "ASC"
                ),
                array("BASKET_ID" => $arrBasket['ID'])
            );
            while ($ar_res = $db_res->Fetch())
            {
                echo $ar_res["NAME"]."=".$ar_res["VALUE"]."<br>";
            }

        }*/

        ?>

        <?if($_REQUEST['AJAX_LOYALTY'] == 'Y') {
            die();
        }?>
    </div>
</div>
<?endif;?>