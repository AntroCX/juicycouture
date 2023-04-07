$(function () {
    var $body = $('body');

    /**
     * scroll page top menu
     */

    var $menu = $('.b-header__menu'),
        $page = $('.b-page'),
        $logo = $('.b-header__menu-logo'),
        $add_menu = $('.b-header__menu-add'),
        $fix_header = $('.b-header__top'),
        $mob_menu = $('.b-page__mobile-menu'),
        $block_search = $('.b-header__search-block');

    $(window).scroll(function(){
        var scrollTop = $(window).scrollTop();
        if(scrollTop > 140) {
            $page.addClass('b-page_scrolled');
            $menu.addClass('b-header__menu_fixed');
            $logo.addClass('b-header__menu-logo_show');
            $add_menu.addClass('b-header__menu-add_show');
        } else {
            $page.removeClass('b-page_scrolled');
            $menu.removeClass('b-header__menu_fixed');
            $logo.removeClass('b-header__menu-logo_show');
            $add_menu.removeClass('b-header__menu-add_show');
        }
        if(scrollTop > 50) {
            $fix_header.addClass('b-header__mobile_fixed');
            $mob_menu.addClass('b-header__mobile_menu_fixed');
            $block_search.addClass('b-header__search-block_fix');
        } else {
            $fix_header.removeClass('b-header__mobile_fixed');
            $mob_menu.removeClass('b-header__mobile_menu_fixed');
            $block_search.removeClass('b-header__search-block_fix');
        }
    });

    /**
     * search btn menu and block
     */
    var $search_link = $('.b-header__top-profile-menu-search'),
        $search_block = $('.b-header__search-block');

    $search_link.on('click', function(e) {
        e.preventDefault();
        $search_block.toggleClass('b-header__search-block_show');
        changeBlock('search');
    });


    /**
     * mobile btn menu
     */

    var $mobile_btn = $('.b-header__mobile-btn'),
        $mobile_menu = $('.b-page__mobile-menu'),
        $page_content = $('.b-page__content');

    $mobile_btn.on('click', function() {
        $mobile_menu.toggleClass('b-page__mobile-menu_show');
        $page_content.toggleClass('open-menu');
        changeBlock('menu');
    });

    function changeBlock(action) {
        if(action == 'search') {
                $page_content.removeClass('open-menu');
                $mob_menu.removeClass('b-page__mobile-menu_show');
        }

        if(action == 'menu') {
                $search_block.removeClass('b-header__search-block_show');
        }
    }

  // block zoom on devices
  document.addEventListener('touchmove', function(event) {
    event = event.originalEvent || event;
    if (event.scale !== 1) event.preventDefault();
  }, false);
});