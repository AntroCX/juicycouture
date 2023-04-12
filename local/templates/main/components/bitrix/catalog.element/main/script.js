$(function () {
    $(document).on('click', '.js-toggle-colors', function (e) {
        let text = $(this).find('.all-colors').text();
        let newText = (text == 'Ещё цвета' ? 'Cкрыть' : 'Ещё цвета');
        $(this).find('.all-colors').text(newText);
        $('#colorList').find('.to-hide').toggleClass('hidden');
    });
});