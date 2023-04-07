<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
$compositeStub = (isset($arResult['COMPOSITE_STUB']) && $arResult['COMPOSITE_STUB'] == 'Y');
?>

<ul class="b-header__top-profile-menu">

	<?$userLink = $arParams['PATH_TO_REGISTER'].'?login=yes'?>
	<?if($USER->IsAuthorized()):?>
		<?$userLink = $arParams['PATH_TO_PROFILE']?>
	<?endif?>
    <a class="b-header__top-profile-menu-user" href="<?=$userLink?>"></a>
    <a class="b-header__top-profile-menu-phone visible-xs hidden-sm" href="tel:88007707646"></a>
	<a class="b-header__top-profile-menu-search" href="#"></a>
	<div class="b-header__top-profile-menu-cart">
        <span class="b-header__top-profile-menu-cart-ico <? if ($arResult['NUM_PRODUCTS']): ?>not-empty-basket<? endif ?>"  data-pathToBasket="<?= $arParams['PATH_TO_BASKET'] ?>"></span>
            <span class="b-header__top-profile-menu-cart-count">
                <?if($arResult['NUM_PRODUCTS']):?>
                    <?=$arResult['NUM_PRODUCTS']?>
                <?endif?>
            </span>
