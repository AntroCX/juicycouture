$(function () {
    var $tabs = $('.b-tabs__content'),
        mobile = false;
        $tabs_nav = $('.b-tabs__nav-item');

    if($(window).width() < 768) {
        mobile = true;
    }

    $tabs.slick({
        arrows : false,
        //dots : mobile,
        draggable: false
    });

    $tabs_nav.on('click', function() {
        var $this = $(this);
        $this.addClass('active');
        $tabs_nav.not($this).removeClass('active');
        $tabs.slick('slickGoTo', $this.index());
    });

})