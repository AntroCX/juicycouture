/**
 * Created by maxkrasnov on 07.10.16.
 */
$(function () {

    var $body = $('body'),
        selectedShop = {
            id: '',
            address: '',
            coords: '',
            type: ''
        },
        $modal = $('#reservedInStore');

    $('input[name=RESERVED_PHONE]').mask('+7(999)999-99-99');
    var $form = $('.b-modal-reserved__form');

    $form.validate({
        rules: {
            RESERVED_NAME: {
                required: true
            },
            RESERVED_PHONE: {
                required: true,
                phoneFormat: true
            },
            RESERVED_EMAIL: {
                required: true
            }
        }
    });

    $modal.on('show.bs.modal', function() {
        changeShop();
    });

    function changeShop() {
        mapReservedInit(selectedShop.coords);

        $('.b-modal-reserved__shop-address').html(selectedShop.address);
        $('.b-modal-reserved__product-artnum').html(selectedSKU.ARTNUMBER);
        $('.b-modal-reserved__product-size').html(selectedSKU.SIZE);
        $('.b-modal-reserved__product-price').html(selectedSKU.PRICE);
        var src = $('.b-catalog-detail__photos-current.active')
            .find('.b-catalog-detail__photos-list-nav-item:first-child')
            .find('img')
            .attr('src');

        $('#b-modal-reserved__preview').attr('src', src);
    }


    $modal.on('hide.bs.modal', function() {
        $('#YMapsID').empty();
    });

    $body.on('click', '.btn-reserved', function () {
        var $this = $(this);
        if(!$this.hasClass('b-catalog-detail__reservation-all')) {
            $('.b-reservation-shop-list').addClass('hidden');
            selectedShop.id = $this.attr('data-id');
            selectedShop.coords = $this.attr('data-coords').split(',');
            selectedShop.address = $this.parents('.b-shops__list-item').find('.b-shops__list-item-address').text();
            selectedShop.type = $this.data('type');
            $modal.modal('show');
            /** DigitalDataLayer start */
            if (typeof window.digitalData.events !== 'undefined') {
                /* Для формы резервирования - подставляем значение продукта в корзину */
                var defaultCart = window.digitalData.cart; // корзина
                var reservItem = {
                    'product'   : window.digitalData.product,
                    'quantity'  : 1,
                    'subtotal'  : window.digitalData.product.unitSalePrice
                };

                window.digitalData.cart.checkoutType = 'reservation';
                window.digitalData.cart.lineItems = [];
                window.digitalData.cart.lineItems[0] = reservItem;
                window.digitalData.cart.subtotal = reservItem.product.unitSalePrice;
                window.digitalData.cart.total = reservItem.product.unitPrice;
                window.digitalData.events.push({
                    'category' : 'Ecommerce',
                    'name'     : 'Viewed Cart',
                    'cart'  : window.digitalData.cart
                });
            }
            /** DigitalDataLayer end */
        } else {
            $('.b-reservation-shop-list').removeClass('hidden');
            $.post('/local/ajax/get_shops.php', {
                artnumber: selectedSKU.ARTNUMBER,
                skuId: selectedSKU.ID,
                sessid: $('#sessid').val(),
                template: 'select'
            })
                .done(function (data) {
                    var $select = $('.b-reservation-shop-select');
                    $select.html(data.html);
                    var $selectedShop = $select.find('option:first-child');
                    selectedShop = {
                        id: $selectedShop.attr('data-id'),
                        address: $selectedShop.text(),
                        coords: $selectedShop.attr('data-coords').split(','),
                        type: $selectedShop.data('type')
                    };
                    $modal.modal('show');
                    /** DigitalDataLayer start */
                    if (typeof window.digitalData.events !== 'undefined') {
                        window.digitalData.cart.checkoutType = 'reservation';
                        window.digitalData.events.push({
                            'category' : 'Ecommerce',
                            'name'     : 'Viewed Cart',
                            'cart'  : window.digitalData.cart
                        });
                    }
                    /** DigitalDataLayer end */
                });
        }
    });

    $body.on('change', '.b-reservation-shop-select', function () {
        var $this = $(this),
            $selected = $this.find('option:selected');

        selectedShop = {
            id: $selected.attr('data-id'),
            address: $selected.text(),
            coords: $selected.attr('data-coords'),
            type: $selected.data('type')
        };

        changeShop();
    });

    $body.on('click', '.btn-send-reservation', function() {
        if($form.valid()) {
            $.post('/local/ajax/reservation.php', {
                store_id: selectedShop.id,
                omni_type: selectedShop.type,
                product_id: selectedSKU.ID,
                sessid: $('#sessid').val(),
                name: $('input[name=RESERVED_NAME]').val(),
                email: $('input[name=RESERVED_EMAIL]').val(),
                phone: $('input[name=RESERVED_PHONE]').val(),
                tablet: $('select[name=RESERVED_TABLET]').val()
            })
                .done(function (data) {
                    var $notify = $('#b-modal-notify');
                    $notify.find('.modal-body').html(data);
                    $modal.modal('hide');
                    $notify.modal('show');
                });
        }
    })
});

function mapReservedInit(coords) {
    ymaps.ready(function () {
        var $map = $('#YMapsID'),
            myGeoObjects = [];
        var myMap = new ymaps.Map('YMapsID', {
            controls: [],
            center: [coords[0], coords[1]],
            zoom: 14
        }, {
            searchControlProvider: false
        });

        myMap.controls.add(
            new ymaps.control.ZoomControl()
        );

        var myPlacemark = new ymaps.Placemark([coords[0], coords[1]], {

        }, {
            // Опции.
            // Необходимо указать данный тип макета.
            iconLayout: 'default#image',
            // Своё изображение иконки метки.
            iconImageHref: '/local/images/mapmarker@x2.png',
            // Размеры метки.
            iconImageSize: [42, 42],
            // Смещение левого верхнего угла иконки относительно
            // её "ножки" (точки привязки).
            iconImageOffset: [0, -42]
        });

        $('body').on('change', '.b-reservation-shop-select', function () {
            myMap.geoObjects.removeAll();
            var coords = $(this).find('option:selected').attr('data-coords').split(',');

            var myPlacemark = new ymaps.Placemark([coords[0], coords[1]], {

            }, {
                // Опции.
                // Необходимо указать данный тип макета.
                iconLayout: 'default#image',
                // Своё изображение иконки метки.
                iconImageHref: '/local/images/mapmarker@x2.png',
                // Размеры метки.
                iconImageSize: [42, 42],
                // Смещение левого верхнего угла иконки относительно
                // её "ножки" (точки привязки).
                iconImageOffset: [0, -42]
            });

            myGeoObjects.push(myPlacemark);
            myMap.geoObjects.add(myPlacemark);

            myMap.setCenter([coords[0], coords[1]], 18, {
                checkZoomRange: true
            });
        });

        myGeoObjects.push(myPlacemark);
        myMap.geoObjects.add(myPlacemark);

    });
}