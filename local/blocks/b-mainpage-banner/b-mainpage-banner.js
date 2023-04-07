$(function () {
    imgSwitch();
    $('.b-mainpage-banner-carousel').carousel({
        interval: 3500
    });

    /**
     * mobile slider banner
     */

    $(window).on('resize', function() {
        imgSwitch();
    });

});

function imgSwitch() {
    var obj = $(".b-mainpage-banner"),
        wnd_w = $(window).width();

    obj.each(function () {
        var bg0 = $(this).attr('data-bg0'),
            bg1 = $(this).attr('data-bg1'),
            bg2 = $(this).attr('data-bg2');

        if (wnd_w < 720 && bg2.length > 1) {
            $(this).css({
                'background-image': 'url(' + bg2 + ')'
            });
        } else if (wnd_w < 1200 && wnd_w >= 720 && bg1.length > 1) {
            $(this).css({
                'background-image': 'url(' + bg1 + ')'
            });
        } else {
            $(this).css({
                'background-image': 'url(' + bg0 + ')'
            });
        }
    });
}