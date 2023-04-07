<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Grid\Declension;

/**@var array $arResult */

if (!function_exists("showFilePropertyField"))
{
	function showFilePropertyField($name, $property_fields, $values, $max_file_size_show=50000)
	{
		$res = "";

		if (!is_array($values) || empty($values))
			$values = array(
				"n0" => 0,
			);

		if ($property_fields["MULTIPLE"] == "N")
		{
			$res = "<label for=\"\"><input type=\"file\" size=\"".$max_file_size_show."\" value=\"".$property_fields["VALUE"]."\" name=\"".$name."[0]\" id=\"".$name."[0]\"></label>";
		}
		else
		{
			$res = '
			<script type="text/javascript">
				function addControl(item)
				{
					var current_name = item.id.split("[")[0],
						current_id = item.id.split("[")[1].replace("[", "").replace("]", ""),
						next_id = parseInt(current_id) + 1;

					var newInput = document.createElement("input");
					newInput.type = "file";
					newInput.name = current_name + "[" + next_id + "]";
					newInput.id = current_name + "[" + next_id + "]";
					newInput.onchange = function() { addControl(this); };

					var br = document.createElement("br");
					var br2 = document.createElement("br");

					BX(item.id).parentNode.appendChild(br);
					BX(item.id).parentNode.appendChild(br2);
					BX(item.id).parentNode.appendChild(newInput);
				}
			</script>
			';

			$res .= "<label for=\"\"><input type=\"file\" size=\"".$max_file_size_show."\" value=\"".$property_fields["VALUE"]."\" name=\"".$name."[0]\" id=\"".$name."[0]\"></label>";
			$res .= "<br/><br/>";
			$res .= "<label for=\"\"><input type=\"file\" size=\"".$max_file_size_show."\" value=\"".$property_fields["VALUE"]."\" name=\"".$name."[1]\" id=\"".$name."[1]\" onChange=\"javascript:addControl(this);\"></label>";
		}

		return $res;
	}
}

if (!function_exists("PrintPropsForm"))
{
	function PrintPropsForm($arSource = array(), $locationTemplate = ".default")
	{
		if (!empty($arSource))
		{
			?>
			<?$inc = 0?>
				<?
				foreach ($arSource as $arProperties)
				{
					$inc++;
					?>
					<?if($inc % 3 == 0):?>
						</div>
						<div class="row">
					<?endif?>
                    <?php
                    if ($arProperties['CODE'] === 'STORE_ID' || $arProperties['CODE'] === 'F_ADDRESS' || $arProperties['CODE'] === 'CHEQUE') {
                        ?><input type="hidden" name="<?=$arProperties["FIELD_NAME"]?>" id="<?=$arProperties["FIELD_NAME"]?>" value="<?=$arProperties["VALUE"]?>"><?
                        continue;
                    }
                    ?>
					<div class="<?if($arProperties['FIELD_NAME'] == 'ORDER_PROP_6' || $arProperties["TYPE"] == "LOCATION"):?>col-sm-12<?else:?>col-sm-6<?endif?>">
						<div class="form-group">
							<div data-property-id-row="<?=(int)$arProperties["ID"]?>">

								<?
								if ($arProperties["TYPE"] == "CHECKBOX")
								{
									?>
									<div class="bx_block r1x3 pt8">
										<input type="hidden" name="<?=$arProperties["FIELD_NAME"]?>" value="">
										<input type="checkbox" name="<?=$arProperties["FIELD_NAME"]?>" id="<?=$arProperties["FIELD_NAME"]?>" value="Y"<?if ($arProperties["CHECKED"]=="Y") echo " checked";?>>
										<?if (strlen(trim($arProperties["DESCRIPTION"])) > 0):?>
											<div class="bx_description"><?=$arProperties["DESCRIPTION"]?></div>
										<?endif?>
									</div>
									<?
								}
								elseif ($arProperties["TYPE"] == "TEXT")
								{

                                    $type = 'text';
                                    $addClass = '';
                                    if ($arProperties['CODE'] == 'PHONE') {
                                        $type = 'tel';
                                        $addClass = 'mask-phone';
                                    }
                                    if ($arProperties['CODE'] == 'EMAIL') $type = 'email';
									?>
									<div class="bx_block r3x1">
										<input class="form-control <?=$addClass?>"
											   type="<?=$type?>"
											   maxlength="250"
											   size="<?=$arProperties["SIZE1"]?>"
											   value="<?=$arProperties["VALUE"]?>"
											   name="<?=$arProperties["FIELD_NAME"]?>"
											   id="<?=$arProperties["FIELD_NAME"]?>"
											   placeholder="<?if($arProperties["REQUIED_FORMATED"]=="Y"):?>*<?endif?> <?=$arProperties["NAME"]?>" />
										<?if (strlen(trim($arProperties["DESCRIPTION"])) > 0):?>
											<div class="bx_description"><?=$arProperties["DESCRIPTION"]?></div>
										<?endif?>
									</div>
									<?
								}
								elseif ($arProperties["TYPE"] == "SELECT")
								{
									?>
									<div class="bx_block r3x1">
										<select name="<?=$arProperties["FIELD_NAME"]?>" id="<?=$arProperties["FIELD_NAME"]?>" size="<?=$arProperties["SIZE1"]?>">
											<?foreach($arProperties["VARIANTS"] as $arVariants):?>
												<option value="<?=$arVariants["VALUE"]?>"<?=$arVariants["SELECTED"] == "Y" ? " selected" : ''?>><?=$arVariants["NAME"]?></option>
											<?endforeach?>
										</select>
										<?if (strlen(trim($arProperties["DESCRIPTION"])) > 0):?>
											<div class="bx_description"><?=$arProperties["DESCRIPTION"]?></div>
										<?endif?>
									</div>
									<?
								}
								elseif ($arProperties["TYPE"] == "MULTISELECT")
								{
									?>
									<div class="bx_block r3x1">
										<select multiple name="<?=$arProperties["FIELD_NAME"]?>" id="<?=$arProperties["FIELD_NAME"]?>" size="<?=$arProperties["SIZE1"]?>">
											<?foreach($arProperties["VARIANTS"] as $arVariants):?>
												<option value="<?=$arVariants["VALUE"]?>"<?=$arVariants["SELECTED"] == "Y" ? " selected" : ''?>><?=$arVariants["NAME"]?></option>
											<?endforeach?>
										</select>
										<?if (strlen(trim($arProperties["DESCRIPTION"])) > 0):?>
											<div class="bx_description"><?=$arProperties["DESCRIPTION"]?></div>
										<?endif?>
									</div>
									<?
								}
								elseif ($arProperties["TYPE"] == "TEXTAREA")
								{
									$rows = ($arProperties["SIZE2"] > 10) ? 4 : $arProperties["SIZE2"];
									?>
									<div class="bx_block r3x1">
										<textarea rows="<?=$rows?>" cols="<?=$arProperties["SIZE1"]?>" name="<?=$arProperties["FIELD_NAME"]?>" id="<?=$arProperties["FIELD_NAME"]?>"><?=$arProperties["VALUE"]?></textarea>
										<?if (strlen(trim($arProperties["DESCRIPTION"])) > 0):?>
											<div class="bx_description"><?=$arProperties["DESCRIPTION"]?></div>
										<?endif?>
									</div>
									<?
								}
								elseif ($arProperties["TYPE"] == "LOCATION")
								{
									?>

									<div class="bx_block r3x1">
										<?
										$value = 0;
										if (is_array($arProperties["VARIANTS"]) && count($arProperties["VARIANTS"]) > 0)
										{
											foreach ($arProperties["VARIANTS"] as $arVariant)
											{
												if ($arVariant["SELECTED"] == "Y")
												{
													$value = $arVariant["ID"];
													break;
												}
											}
										}

										// here we can get '' or 'popup'
										// map them, if needed
										if(CSaleLocation::isLocationProMigrated())
										{
											$locationTemplateP = $locationTemplate == 'popup' ? 'search' : 'steps';
											$locationTemplateP = $_REQUEST['PERMANENT_MODE_STEPS'] == 1 ? 'steps' : $locationTemplateP; // force to "steps"
										}
										?>

										<?if($locationTemplateP == 'steps'):?>
											<input type="hidden" id="LOCATION_ALT_PROP_DISPLAY_MANUAL[<?=(int)$arProperties["ID"]?>]" name="LOCATION_ALT_PROP_DISPLAY_MANUAL[<?=(int)$arProperties["ID"]?>]" value="<?=($_REQUEST['LOCATION_ALT_PROP_DISPLAY_MANUAL'][(int)$arProperties["ID"]] ? '1' : '0')?>" />
										<?endif?>

										<?$value = 19?>
										<?if($_REQUEST['ORDER_PROP_5']) {
											$value = $_REQUEST['ORDER_PROP_5'];
										} elseif($_COOKIE['city_id']) {
											$value = $_COOKIE['city_id'];
										}?>

										<?CSaleLocation::proxySaleAjaxLocationsComponent(array(
											"AJAX_CALL" => "N",
											"COUNTRY_INPUT_NAME" => "COUNTRY",
											"REGION_INPUT_NAME" => "REGION",
											"CITY_INPUT_NAME" => $arProperties["FIELD_NAME"],
											"CITY_OUT_LOCATION" => "Y",
											"LOCATION_VALUE" => $value,
											"ORDER_PROPS_ID" => $arProperties["ID"],
											"ONCITYCHANGE" => ($arProperties["IS_LOCATION"] == "Y" || $arProperties["IS_LOCATION4TAX"] == "Y") ? "submitForm()" : "",
											"SIZE1" => $arProperties["SIZE1"],
										),
											array(
												"ID" => $value,
												"CODE" => "",
												"SHOW_DEFAULT_LOCATIONS" => "Y",

												// function called on each location change caused by user or by program
												// it may be replaced with global component dispatch mechanism coming soon
												"JS_CALLBACK" => "submitFormProxy",

												// function window.BX.locationsDeferred['X'] will be created and lately called on each form re-draw.
												// it may be removed when sale.order.ajax will use real ajax form posting with BX.ProcessHTML() and other stuff instead of just simple iframe transfer
												"JS_CONTROL_DEFERRED_INIT" => (int)$arProperties["ID"],

												// an instance of this control will be placed to window.BX.locationSelectors['X'] and lately will be available from everywhere
												// it may be replaced with global component dispatch mechanism coming soon
												"JS_CONTROL_GLOBAL_ID" => (int)$arProperties["ID"],

												"DISABLE_KEYBOARD_INPUT" => "Y",
												"PRECACHE_LAST_LEVEL" => "Y",
												"PRESELECT_TREE_TRUNK" => "Y",
												"SUPPRESS_ERRORS" => "Y"
											),
											$locationTemplateP,
											true,
											'location-block-wrapper'
										)?>

										<?if (strlen(trim($arProperties["DESCRIPTION"])) > 0):?>
											<div class="bx_description"><?=$arProperties["DESCRIPTION"]?></div>
										<?endif?>
									</div>
									<?
								}
								elseif ($arProperties["TYPE"] == "RADIO")
								{
									?>
									<div class="bx_block r3x1">
										<?
										if (is_array($arProperties["VARIANTS"]))
										{
											foreach($arProperties["VARIANTS"] as $arVariants):
											?>
												<input
													type="radio"
													name="<?=$arProperties["FIELD_NAME"]?>"
													id="<?=$arProperties["FIELD_NAME"]?>_<?=$arVariants["VALUE"]?>"
													value="<?=$arVariants["VALUE"]?>" <?if($arVariants["CHECKED"] == "Y") echo " checked";?> />

												<label for="<?=$arProperties["FIELD_NAME"]?>_<?=$arVariants["VALUE"]?>"><?=$arVariants["NAME"]?></label></br>
											<?
											endforeach;
										}
										?>
										<?if (strlen(trim($arProperties["DESCRIPTION"])) > 0):?>
											<div class="bx_description"><?=$arProperties["DESCRIPTION"]?></div>
										<?endif?>
									</div>
									<?
								}
								elseif ($arProperties["TYPE"] == "FILE")
								{
									?>
									<div class="bx_block r3x1">
										<?=showFilePropertyField("ORDER_PROP_".$arProperties["ID"], $arProperties, $arProperties["VALUE"], $arProperties["SIZE1"])?>
										<?if (strlen(trim($arProperties["DESCRIPTION"])) > 0):?>
											<div class="bx_description"><?=$arProperties["DESCRIPTION"]?></div>
										<?endif?>
									</div>
									<?
								}
								elseif ($arProperties["TYPE"] == "DATE")
								{
									?>
									<div>
										<?
										global $APPLICATION;

										$APPLICATION->IncludeComponent('bitrix:main.calendar', '', array(
											'SHOW_INPUT' => 'Y',
											'INPUT_NAME' => "ORDER_PROP_".$arProperties["ID"],
											'INPUT_VALUE' => $arProperties["VALUE"],
											'SHOW_TIME' => 'N'
										), null, array('HIDE_ICONS' => 'N'));
										?>
										<?if (strlen(trim($arProperties["DESCRIPTION"])) > 0):?>
											<div class="bx_description"><?=$arProperties["DESCRIPTION"]?></div>
										<?endif?>
									</div>
									<?
								}
								?>
							</div>
						</div>
					</div>

					<?if(CSaleLocation::isLocationProEnabled()):?>

					<?
					$propertyAttributes = array(
						'type' => $arProperties["TYPE"],
						'valueSource' => $arProperties['SOURCE'] == 'DEFAULT' ? 'default' : 'form' // value taken from property DEFAULT_VALUE or it`s a user-typed value?
					);

					if((int)$arProperties['IS_ALTERNATE_LOCATION_FOR'])
						$propertyAttributes['isAltLocationFor'] = (int)$arProperties['IS_ALTERNATE_LOCATION_FOR'];

					if((int)$arProperties['CAN_HAVE_ALTERNATE_LOCATION'])
						$propertyAttributes['altLocationPropId'] = (int)$arProperties['CAN_HAVE_ALTERNATE_LOCATION'];

					if($arProperties['IS_ZIP'] == 'Y')
						$propertyAttributes['isZip'] = true;
					?>
					<?endif?>

					<?
				}
				?>
			<?
		}
	}
}

if (!function_exists('printLocationProp')) {
    function printLocationProp(array $locationProp)
    {
        $cityId = (int)$_COOKIE['city_id'] ?: DEFAULT_CITY_ID;
        $cityName = (string)$_COOKIE['city_name'] ?: 'Москва';

        if (is_array($locationProp['VARIANTS']) && count($locationProp['VARIANTS']) > 0) {
            foreach ($locationProp['VARIANTS'] as $arVariant) {
                if ((int)$arVariant['ID'] === $cityId) {
                    $cityId = $arVariant['ID'];
                    $cityName = $arVariant['CITY_NAME'];
                    break;
                }
            }
        }
        ?>
        <div class="col-sm-12 location">
            <label><?= $locationProp["NAME"] ?></label>
            <input type="hidden" value="<?= $cityId ?>" name="<?= $locationProp['FIELD_NAME'] ?>" id="<?= $locationProp['FIELD_NAME'] ?>" />
            <span class="b-header__top-location-city btn" data-toggle="modal" data-target="#b-city-popup"><?= $cityName ?></span>
        </div>
        <?php
    }
}

if (!function_exists('PrintTabletProp')) {
    function PrintTabletProp($arTablet = array())
    {
        if ($arTablet['LIST']) {
            ?>
            </div>
            <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <div data-property-id-row="<?= $arTablet['PROP']['ID'] ?>">
                        <div class="bx_block r3x1">
                            <select class="form-control" name="ORDER_PROP_<?= $arTablet['PROP']['ID'] ?>" id="ORDER_PROP_<?= $arTablet['PROP']['ID'] ?>">
                                <option value=""> - табельный номер сотрудника -</option>
                                <? foreach ($arTablet['LIST'] as $arOne) { ?>
                                    <option value="<?= $arOne['UF_TABLET_ID'] ?>"><?= $arOne['UF_TABLET_ID'] ?>. <?= $arOne['NAME'] ?></option>
                                <? } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        <?
        }
    }
}

if (!function_exists('formatTransitText')) {
    /**
     * форматирует срок доставки
     *
     * @param string $text - срок доставки от служб доставки
     * @param string $cityName - название города
     * @param bool $addComma - добавлять ли в конце запятую (если дальше будет цена - то нужно)
     *
     * @return string
     */
    function formatTransitText($text = '', $cityName = '', $addComma = false)
    {
        $cityName = ToLower($cityName);
        if (substr_count($text, '-')) {
            $dayCount = explode('-', $text);

            // если срок "2-2", то оставим просто "2"
            if (count($dayCount) == 2 && $dayCount[0] == $dayCount[1]) $text = $dayCount[0];

            $dayCount = array_pop($dayCount);
        } else {
            $dayCount = $text;
        }
        $dayCount = (int)$dayCount;
        if (!$dayCount) $dayCount = 5; // дней

        $arResult = [];

        if ($cityName == 'москва' || $cityName == 'санкт-петербург') {
            $currentHours = date('G');
            if ($currentHours >= 17) $dayCount++;

            if ($cityName == 'москва' && $dayCount == 1) {
                $arResult[] = 'завтра';
            } else {
                $time = time() + 86400 * $dayCount;
                $arResult[] = ToLower(FormatDate('d F', $time));
            }
        } else {
            $dayDeclension = new Declension('день', 'дня', 'дней');
            $arResult[] = $text . ' ' . $dayDeclension->get($dayCount);
        }

        return 'Срок доставки: <em>' . implode(',</em> <em>', $arResult) . (($addComma) ? ',' : '') . '</em>';
    }
}