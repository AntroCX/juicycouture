<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (empty($arResult)) return;
?>
<div class="b-page__mobile-menu">

<?
$previousLevel = 0;
foreach($arResult as $key => $arItem):?>
    <? if ($arItem["DEPTH_LEVEL"] > $arParams['MAX_LEVEL']) continue; ?>
	<?if ($previousLevel && $arItem["DEPTH_LEVEL"] < $previousLevel):?>
		<?=str_repeat("</div></div></div>", ($previousLevel - $arItem["DEPTH_LEVEL"]));?>
	<?endif?>

	<?if ($arItem["IS_PARENT"]):?>

		<?if ($arItem["DEPTH_LEVEL"] == 1):?>
			<div class="panel panel-default">
				<div class="panel-heading" role="tab" id="headingOne<?=$key?>">
					<h4 class="panel-title" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne<?=$key?>" aria-expanded="false" aria-controls="collapseOne<?=$key?>">
						<?=$arItem["TEXT"]?>
					</h4>
				</div>
				<div id="collapseOne<?=$key?>" class="panel-collapse collapse" role="tabpanel" aria-labelledby="collapseOne<?=$key?>">
					<div class="panel-body">
						<ul>
		<?else:?>
			<div class="panel panel-default">
				<div class="panel-heading" role="tab" id="headingOne<?=$key?>">
					<a href="<?=$arItem["LINK"]?>" class="panel-title" role="button" data-toggle="collapse" data-parent="#accordion" aria-expanded="false" aria-controls="collapseOne<?=$key?>">
						<?=$arItem["TEXT"]?>
					</a>
				</div>
			</div>
		<?endif?>

	<?else:?>

		<?if ($arItem["PERMISSION"] > "D"):?>

			<?if ($arItem["DEPTH_LEVEL"] == 1):?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<a href="<?=$arItem["LINK"]?>" class="panel-title" aria-expanded="false" aria-controls="collapseOne<?=$key?>">
							<?=$arItem["TEXT"]?>
						</a>
					</div>
				</div>
			<?else:?>
				<li><a href="<?=$arItem["LINK"]?>" <?if ($arItem["SELECTED"]):?> class="item-selected"<?endif?>><?=$arItem["TEXT"]?></a></li>
			<?endif?>

		<?else:?>

			<?if ($arItem["DEPTH_LEVEL"] == 1):?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<a href="<?=$arItem["LINK"]?>" class="panel-title" data-parent="#accordion" aria-expanded="false" aria-controls="collapseOne<?=$key?>">
							<?=$arItem["TEXT"]?>
						</a>
					</div>
				</div>
			<?endif?>

		<?endif?>

	<?endif?>

	<?$previousLevel = $arItem["DEPTH_LEVEL"];?>

<?endforeach?>

<?if ($previousLevel > 1)://close last item tags?>
	<?=str_repeat("</ul></div></div></div>", ($previousLevel-1) );?>
<?endif?>
    <div class="panel panel-default panel__phone-box">
        <div class="panel-heading">
            <a class="panel__phone" href="tel:88007707646" aria-expanded="false" aria-controls="collapseOne22">8 800 770-76-46</a>
        </div>
    </div>
</div>