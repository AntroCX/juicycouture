$(function() {
    mapInit([61.698653, 99.505405]);
})

function mapInit(coords) {
    ymaps.ready(function () {
        var $map = $('#YMapsID'),
            myGeoObjects = [];
        var myMap = new ymaps.Map('YMapsID', {
            controls: [],
            center: [coords[0], coords[1]],
            zoom: 3
        }, {
            searchControlProvider: false
        });

        myMap.controls.add(
            new ymaps.control.ZoomControl()
        );


        var arrShops = [];

        $('.i-yandex-map__item').each(function() {
            var $this = $(this),
                shop = {}
                coordsShop = $this.attr('data-coords').split(','),
                nameShop = $this.attr('data-name'),
                cityShop = $this.attr('data-city');

            shop.nameShop = nameShop;
            shop.cityShop = cityShop;
            shop.coordsShop = coordsShop;

            arrShops.push(shop);

            var myPlacemark = new ymaps.Placemark([coordsShop[0], coordsShop[1]], {
                hintContent:nameShop,
                balloonContent: '<b>'+cityShop+'</b><br>'+nameShop
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
                iconImageOffset: [-3, -42]
            });
            myGeoObjects.push(myPlacemark);
            myMap.geoObjects.add(myPlacemark);
        });
        var clusterer = new ymaps.Clusterer({
            clusterIcons: [{
                href: '/local/images/klaster@x2.png',
                size: [42, 42],
                offset: [-3, -42],
            }]
        });
        clusterer.add(myGeoObjects);
        myMap.geoObjects.add(clusterer);
        $('.b-retail-shops__select-city').on('change', function() {
            myMap.geoObjects.removeAll();
            clusterer.removeAll();
            var $this = $(this),
                city = $this.val(),
                myGeoObjects = [],
                last_coords = [];

            if(city == -1) {
                myMap.setCenter([coords[0], coords[1]], 3, {
                    checkZoomRange: true
                });
            }
            for (var i=0, len=arrShops.length; i<len; i++) {

                var shop = arrShops[i];
                    $item = $('body .i-yandex-map__item[data-name="'+shop.nameShop+'"]');

                if (city == shop.cityShop || city == -1) {
                    var email = shop.emailShop,
                        coordsShop = shop.coordsShop,
                        nameShop = shop.nameShop,
                        cityShop = shop.cityShop;

                    last_coords = coordsShop;
                    $item.parent().removeClass('hidden');

                    var myPlacemark = new ymaps.Placemark([coordsShop[0], coordsShop[1]], {
                        hintContent: nameShop,
                        balloonContent: '<b>'+cityShop+'</b><br>'+nameShop
                    }, {//
                        // Опции.
                        // Необходимо указать данный тип макета.
                        iconLayout: 'default#image',
                        // Своё изображение иконки метки.
                        iconImageHref: '/local/images/mapmarker@x2.png',
                        // Размеры метки.
                        iconImageSize: [42, 42],
                        // Смещение левого верхнего угла иконки относительно
                        // её "ножки" (точки привязки).
                        iconImageOffset: [-3, -42]
                    });
                    myGeoObjects.push(myPlacemark);
                } else {
                    $item.parent().addClass('hidden');
                }
            }

            if(city != -1) {
                myMap.setCenter([last_coords[0], last_coords[1]], 8, {
                    checkZoomRange: true
                });
            }
            clusterer.add(myGeoObjects);
            myMap.geoObjects.add(clusterer);
        });

        $('.b-retail-shops__to-map').on('click', function () {
            var coords = $(this).parent().attr('data-coords').split(',');
            myMap.setCenter([coords[0], coords[1]], 18, {
                checkZoomRange: true
            });
        });

    });
}