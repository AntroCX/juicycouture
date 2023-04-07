/**
 * Created by maxkrasnov on 22.09.16.
 */
$(function () {
    var $body = $('body'),
        catalog = '.b-catalog',
        filter = '.b-filter',
        loader = '.b-page__loader',
        filterElements = '.b-filter__block:not(.b-sort__block) input',
        sefUrl = 'input[name="SEF_SET_FILTER_URL"]',
        sortCheckboxes = '.b-sort__block-checkbox';

    $('body').on('click', '.collapsed', function() {
        //if($(window).width() < 768) {
        console.log($(this).attr('aria-controls'));
        $.cookie('filter_select', $(this).attr('aria-controls'));
        //$('body').find('.collapse, #collapseSorting').collapse('hide');

       // }
    });

    // сброс фильтра
    $body.on('click', '.b-filter__reset-btn', function (e) {
        e.preventDefault();
        var href = addParamToURL($(filter).attr('action'), 'del_filter', 'Y');
        href = addParamToURL(href, 'ajax', 'Y');
        $(loader).addClass('show');
        $.get(href)
            .done(function (data) {
                $(catalog).replaceWith(data);
                $(loader).removeClass('show');
                var urlForBrowser = $(sefUrl).val();
                changeURL(urlForBrowser);
            })
    });

    // вкл-выкл фильтра для моб. версии
    $body.on('click', '.b-filter__block-switch', function () {
        $('.b-filter__block:not(.b-sort__block)').toggle();
    });

    // выбор параметров фильтра
    $body.on('click', filterElements, function () {
        var data = $(filter).serialize() + '&set_filter=Показать',
            action = $(filter).attr('action');
        $(loader).addClass('show');
        $(filter).addClass('deactivate');
        $.ajax({
            type: 'GET',
            url: action,
            data: data+'&ajax=Y',
            success: function(data) {
                $(catalog).replaceWith(data);
                $(loader).removeClass('show');
                $(filter).removeClass('deactivate');
                var urlForBrowser = $(sefUrl).val();
                if($(sortCheckboxes+':checked').length === 1) {
                  var sort = $(sortCheckboxes + ':checked').data("sort");
                  urlForBrowser = addParamToURL(urlForBrowser, 'sort', sort);
                }
                changeURL(urlForBrowser);
            },
            error: function (xhr, str) {
                console.log(xhr.responseCode);
                $(loader).removeClass('show');
                $(filter).removeClass('deactivate');
            }
        });
    });

    // сортировка в блоке фильтр
    $body.on('change', sortCheckboxes, function () {
        var $this = $(this),
            val = $this.val();
        $(loader).addClass('show');
        $(sortCheckboxes).not($this).each(function () {
            var $self = $(this);
            $self.prop('checked', false);
        });
        if(!$this.prop('checked')) {
            val = '';
        }
        var url = val;
        url = addParamToURL(url, 'ajax', 'Y');
        $.get(url)
            .done(function (data) {
                $(loader).removeClass('show');
                $(catalog).replaceWith(data);
                var urlForBrowser = $(sefUrl).val();
                var sort = '';
                if( $(sortCheckboxes+':checked').length === 1){
                  sort = $this.data("sort");
                }
                if(sort !== '')
                    urlForBrowser = addParamToURL(urlForBrowser, 'sort', sort);
                changeURL(urlForBrowser);
            })
    })

});