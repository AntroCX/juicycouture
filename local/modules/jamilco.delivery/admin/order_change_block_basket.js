$(function() {
  checkBasketViews();
});

function checkBasketViews() {
  var _block = $('.adm-container-draggable[data-id="basket"]');
  var _basketCount = $('tbody[id^="sale_order_basketsale-order-basket-product"]').length;
  if (!_basketCount) {
    setTimeout(checkBasketViews, 100);
  } else {
    $('tbody[id^="sale_order_basketsale-order-basket-product"]').each(function() {
      var _one = $(this);

    });

    $('.adm-container-draggable[data-id=basket] .adm-s-order-table-ddi-table').on('click', function() {
      alert('Запрещено изменение состава заказа');
    });
  }
}