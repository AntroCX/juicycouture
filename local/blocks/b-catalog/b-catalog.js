$(function () {

  window.skuInCart = {};
  var selectedSKU;
  var sku_selector = '.b-catalog__goods-item-colors-item',
        item = '.b-catalog__goods-item',
        catalog = '.b-catalog',
        loader = '.b-page__loader',
        $body = $('body'),
        sku_elem = '.b-catalog__goods-item-wrapper-sku-element';

    /** добавление в корзину по клику по размеру --> */
    $('[data-toggle="tooltip"]').tooltip();
    $body.on('click', '.b-catalog__sizes-item', function (e) {
      var $this = $(this),
        offerId = $this.data('offerid'),
        selectedSKU = offerId,
        url = $this.data('url');
        quantity = 1;

     if($this.hasClass('added'))
        return;
     $this.addClass('added');

      e.preventDefault();
      BX.showWait();

      url = '/local/ajax/update_small_cart.php';
      $.get(url, {action: 'ADD2BASKET', quantity: quantity, id: offerId, ajax_basket: 'Y'})
        .done(function() {

          // передадим action в параметр корзины
          window.cartId.action = 'add';

          /** DigitalDataLayer start */
          if (typeof window.digitalData.events !== 'undefined' && typeof selectedSKU !== 'undefined') {

            var product = null;
            var productId = $this.parents('.b-catalog__goods-item').data('product-id');

            // Добавление на странице раздела (быстрый просмотр)
            if (!product && typeof window.digitalData.listing !== 'undefined') {
              for (var i = 0; i < window.digitalData.listing.items.length; i++) {
                if (window.digitalData.listing.items[i].id == productId) {
                  product = window.digitalData.listing.items[i];
                  break;
                }
              }
            }

            if (product) {
              var params = {};
              params.event = 'added';
              params.product = product;

              if (typeof window.digitalData.events !== 'undefined' && typeof selectedSKU !== 'undefined') {
                if(params.event == 'added') {
                  var quantity = 1;

                  for (var key in window.digitalData.cart.lineItems) {
                    if(params.product.id == window.digitalData.cart.lineItems[key]) {
                      quantity = window.digitalData.cart.lineItems[key].quantity+1;
                    }
                  }

                  window.digitalData.events.push({
                    "category": "Ecommerce",
                    "name": "Added Product",
                    "product": {
                      "id": params.product.id,
                      "skuCode": offerId + ''
                    },
                    "quantity": 1 * quantity
                  });
                }

              }

            }
          }
          /** DigitalDataLayer end */

          BX.onCustomEvent('OnBasketChange');

          // Добавление отметки о том, что данный SKU находится в корзине
          skuInCart[selectedSKU] = true;

        });
  });

  /** <-- добавление в корзину по клику по размеру */

  $body.on('click', sku_selector, function () {
        var $this = $(this),
            id = $this.attr('data-href');

        $this.addClass('active');
        
        $this.parent('.b-catalog__goods-item-colors').find('.b-catalog__goods-item-colors-item').not($this).removeClass('active');

        $this
            .parents(item)
            .find('.b-catalog__goods-item-wrapper-sku-element')
            .each(function () {
                var $el = $(this);
                if($el.attr('data-sku') == id) {
                    $el.addClass('active');
                } else {
                    $el.removeClass('active');
                }
            });
    })

    $body.on('click', '.b-catalog .b-pagination a', function(e) {
        var $this = $(this),
            href = $this.attr('href'),
            urlForBrowser = href;
        href = addParamToURL(href, 'ajax', 'Y');
        $(loader).addClass('show');
        $.get(href)
            .done(function (data) {
                $(loader).removeClass('show');
                $(catalog).replaceWith(data);

                //console.log(data.match(/#title#(.*?)#title#/ig));

                changeURL(urlForBrowser);
            });
        e.preventDefault();

    })

    $body.on('click', '.b-catalog__views a:not(.active)', function (e) {
        var $this = $(this),
            href = $this.attr('href')+'&ajax=Y';
        e.preventDefault();
        $(loader).addClass('show');
        $.get(href)
            .done(function (data) {
                $(loader).removeClass('show');
                $(catalog).replaceWith(data);
            })
    })

    $body.on('click', '.b-catalog__goods-item-quick', function(e) {
        var $this = $(this),
            href = $this.attr('href'),
            prodId = $this.data('product-id');

        e.preventDefault();

        var url = href,
            req = { prod_id: prodId },
            path = '/local/templates/main/components/bitrix/catalog/main/fastview_ajax.php';

        $.post(path, req)
            .done(function (data) {
                var $modal = $('#quickViewProduct');
                $modal.find('.modal-content').html(data);
                // slick тупит, приходится идти на такой колхоз, времени на разбор нет:(

              window.skuInCart = {};
              setSKU(runSlick);

              setTimeout(function() {
                    $('.b-catalog-detail__photos-list-nav-item:nth-child(2)').trigger('click');
                }, 200)
                
                $('.b-catalog-detail__reservation-all').on('click', function () {
                    window.location = url+'#stores';
                })

                $('.b-catalog-detail__count-to-shops a').attr('href', url+'#stores');


                $modal.modal('show');

                /** DigitalDataLayer event 'Fast view product' */
                if (typeof window.digitalData.events !== 'undefined') {
                    var ddlItemId = $this.parents('.b-catalog__goods-item').data('product-id');
                    var listId = $this.data('list-id');
                    var listing = null;

                    // быстрый просмотр может быть из блока recommendation и на странице раздела
                    // блок рекомендаций может быть на странице листинга, или их может быть несколько
                    // ориентируемся по listId
                    if (typeof window.digitalData.listing === 'undefined' || listId != 'main') {
                        for (var i = 0; i < window.digitalData.recommendation.length; i++) {
                          if (window.digitalData.recommendation[i].listId == listId) {
                              listing = window.digitalData.recommendation[i].items;
                              break;
                          }
                        }
                    } else {
                        listing = window.digitalData.listing.items;
                    }

                    if (listing) {
                        for (var i = 0; i < listing.length; i++) {
                            if (listing[i].id == ddlItemId) {
                                window.digitalData.events.push({
                                    'category': 'Ecommerce',
                                    'name': 'Viewed Product Detail',
                                    'product': listing[i]
                                });
                            }
                        }
                    }
                }
                /** DigitalDataLayer event */
            })

    })

    $body.on('hidden.bs.modal', '#quickViewProduct', function () {
        var $modal = $('#quickViewProduct');
        $modal.find('.modal-content').empty();
    })

    /** Прокрутка вверх при пагинации */

    $(document).on("click", ".b-pagination a",function(){
        var scrollCoordinate = $('.b-catalog').offset().top;
        $('html, body').delay(100).animate({
            scrollTop: scrollCoordinate
        }, 1000);
    });

})