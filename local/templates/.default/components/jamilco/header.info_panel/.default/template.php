<?
/**
 * @var array $arResult
 */

$items = $arResult;

if (empty($items)) {
    return;
}
?>
<div class="promo-strip js-promo-strip swiper">
    <div class="swiper-wrapper">
        <? foreach ($items as  $item): ?>
        <div class="promo-strip__item js-promo-strip-item swiper-slide">
            <? if($item['link']): ?>
            <a class="promo-strip__link" href="<?= $item['link'] ?>"><?= $item['name'] ?> </a>
            <? else: ?>
            <span class="promo-strip__link" href="<?= $item['link'] ?>"><?= $item['name'] ?> </span>
            <? endif; ?>
        </div>
        <? endforeach ?>
    </div>
    <div class="swiper-button-prev"><svg width="7" height="12">
            <use xlink:href="/local/templates/.default/assets/img/icons/icons-sprite.svg#i-arrow-left"></use>
        </svg></div>
    <div class="swiper-button-next"><svg width="7" height="12">
            <use xlink:href="/local/templates/.default/assets/img/icons/icons-sprite.svg#i-arrow-right"></use>
        </svg></div>
</div>
