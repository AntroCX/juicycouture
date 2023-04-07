$(function () {
    var artnumber = $('.b-catalog-detail__artnum-data').text();
    var deniedReservationItem = $('#deniedReservation');

    if (deniedReservationItem.length > 0 && deniedReservationItem.val() === 'Y') {
        return false;
    }

    loadRetailShops(artnumber);
});

function loadRetailShops(artnumber, deniedReservation) {
    if (artnumber) {
        deniedReservation = (deniedReservation == 'Y') ? 'Y' : 'N';
        if (deniedReservation == 'Y') $('.b-catalog-detail__count').addClass('hidden');

        var $stores = $('.b-shops__list');
        if ($stores) {
            $stores.addClass('b-shops__list_load');
        }
        $.post('/local/ajax/get_shops.php', {
            artnumber: artnumber,
            skuId: $('#offerId').val(),
            sessid: $('#sessid').val(),
            denied: deniedReservation
        })
            .done(function (data) {
                if ($stores) {
                    $stores.removeClass('b-shops__list_load');
                    $stores.html(data.html);
                }
                $('.b-catalog-detail__count-stores-num').text(data.count);
                $('.b-catalog-detail__count-to-shops').removeClass('hidden');
                var $reservedBtn = $('.b-catalog-detail__reservation-all'),
                    $buyBtn = $('.b-catalog-detail__add2basket'),
                    $emptyField = $('.b-catalog-detail__empty');
                if (data.count == 0) {
                    $reservedBtn.attr('disabled', 'disabled');
                } else {
                    $reservedBtn.removeAttr('disabled');
                    $buyBtn.removeAttr('disabled'); // купить при наличии в РМ
                }

                // блок информации о сроках доставки на детальной странице
                if (typeof window.selectedSKU !== 'undefined') {
                    $('.b-catalog-detail__delivery, .b-catalog-detail__delivery-delivery, .b-catalog-detail__delivery-pickup').hide();
                    if (window.selectedSKU['DENIED_DELIVERY'] == 'N') {
                      $('.b-catalog-detail__delivery, .b-catalog-detail__delivery-delivery').show();
                      var _deliveryPrice = (data['delivery']['deliveryPrice'] > 0) ? data['delivery']['deliveryPrice'] + ' <span>₽</span>' : 'бесплатно';
                      var _deliveryPriceText = (data['delivery']['deliveryPrice'] > 0) ? ' или бесплатно при онлайн-оплате' : '';
                      $('.b-catalog-detail__delivery-delivery span.price').html(_deliveryPrice);
                      $('.b-catalog-detail__delivery-delivery span.period').html(_deliveryPriceText+', ' + data['delivery']['deliveryPeriod']);
                    }
                    if (data.count > 0) {
                        $('.b-catalog-detail__delivery, .b-catalog-detail__delivery-pickup').show();
                        $('.b-catalog-detail__delivery-pickup span.period').html(', ' + data['delivery']['pickupPeriod']);
                    }
                }
            });
    }
}
