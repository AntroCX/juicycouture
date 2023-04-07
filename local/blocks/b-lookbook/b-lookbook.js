$(function() {
    var $goods = $('.b-recommendation__goods');
    $('#modalLookbook').on('shown.bs.modal', function() {
        $goods.css('opacity', 1);
        $goods.slick({
            slidesToShow: 3,
            slidesToScroll: 1,
            autoplay: false,
            prevArrow: '<a class="i-slick__prev"></a>',
            nextArrow: '<a class="i-slick__next"></a>'
        });
        $goods.fadeIn();
    })

})