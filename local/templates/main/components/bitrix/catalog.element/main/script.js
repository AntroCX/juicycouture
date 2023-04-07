$(function () {
    $(document).on('click', '.js-toggle-colors', function (e) {
        $(this).toggleClass('shown');
        $('#colorList').find('.hidden').toggleClass('hidden');
        // $(this).siblings().addClass('shown hidden');
    });
});