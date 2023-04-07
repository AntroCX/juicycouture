$(function() {
  initBadOrders();
});

function initBadOrders() {
  $('.adm-list-table-row').each(function() {
    if ($(this).data('init') == 'Y') return;
    $(this).data('init', 'Y');

    var _colorBad = '#ffcccc'; // точно
    var _colorMark = '#ffcc66'; // предположительно
    var _colorMark = '';

    for (orderId in window.markOrders) {
      var _color = (window.markOrders[orderId] == 'Да') ? _colorBad : _colorMark;
      if (_color && $(this).html().indexOf(orderId) != -1) {
        $(this).find('td').css({
          'background-color' : _color
        });
      }
    }
  });

  setTimeout(initBadOrders, 1000);
}