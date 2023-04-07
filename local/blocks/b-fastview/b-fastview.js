$(function() {
    /**
     * раскрытие подробного описания на детальной странице
     * @type {*|jQuery|HTMLElement}
     */

    $('body').on('click', '.b-catalog-detail__description-more', function() {
        $(this).hide();
        $('.b-catalog-detail__description-detail').fadeIn();
    });

    /**
     * селектор размера
     */

    $('body').on('click', '.b-catalog-detail__sizes-list-item', function() {
      var $this = $(this);
        $this.addClass('active');
        $('.b-catalog-detail__sizes-list-item').parent().find('.b-catalog-detail__sizes-list-item').not($this).removeClass('active');
        setSKU();
    });

    /**
     * селектор цвета
     */

    $('body').on('click', '.b-catalog-detail__colors-list-item', function() {
        var $this = $(this);
        $this.addClass('active');
        $('.b-catalog-detail__colors-list-item').parent().find('.b-catalog-detail__colors-list-item').not($this).removeClass('active');
        setSKU();
    });


    $('body').on('click', '.b-catalog-detail__social-item', function(e) {
        var $this = $(this);
        e.preventDefault();
        window.open($this.attr('href'), '_blank', 'height=400,width=400');
    });


    /*$('.b-catalog-detail__photos-list-nav').slick({
        slidesToShow: 5,
        slidesToScroll: 3,
        vertical: true,
        arrows: false,
        asNavFor: '.b-catalog-detail__photos-list',
        dots: false,
        centerMode: false
    });*/

    // добавление в корзину
    $('body').on('click', '.b-catalog-detail__add2basket', function (e) {
        var $this = $(this),
            offerId = $('#offerId').val(),
            quantity = $('.b-catalog-detail__count-selector').val(),
            url = $this.attr('data-url');

        if(quantity === undefined) quantity = 1; // JC-97

        e.preventDefault();

        if(!$this.hasClass('added') && !$this.hasClass('btn_load')) {
            $this.addClass('btn_load');
            $this.attr('disabled', 'disabled');
            
            url = '/local/ajax/update_small_cart.php';
            $.get(url, {action: 'ADD2BASKET', quantity: quantity, id: offerId, ajax_basket: 'Y'})
                .done(function() {
                  $('#quickViewProduct').modal('hide');
                  var $cart = $('.b-fast-cart');

                  // передадим action в параметр корзины
                  window.cartId.action = 'add';

                  /** DigitalDataLayer start */
                  if (typeof window.digitalData.events !== 'undefined' && typeof selectedSKU !== 'undefined') {

                    var product = null;
                    var productId = $this.data('product-id');

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

                    $this.removeClass('btn_load');
                    $this.addClass('added');
                    //$cart.addClass('show');
                    $this.removeAttr('disabled');
                    /*setTimeout(function() {
                        $cart.removeClass('show');
                    }, 2000)*/

                    // Добавление отметки о том, что данный SKU находится в корзине
                    skuInCart[selectedSKU.ID] = true;

                });
        }

    });
    
    $('body').on('click', '.b-catalog-detail__add2basket.added', function () {
        location.href = '/order/';
    });

    $('body').on('click', '.b-catalog-detail__count-to-shops', function (e) {
        scrollToAnchor('stores');
    });

    // Подсказка о скидке
    $('body').on('click', '.js-discount-tip', function() {
        $(this).closest('.b-catalog-detail__price-discount-tip').find('.b-catalog-detail__price-discount-tip-text').toggleClass('showed');
    });

    $('body').on('click', '#closeQuickViewProduct', function (e) {
      $('#quickViewProduct').modal('hide');
    });

    // Подсказка о скидке
    $('body').on('click', '.js-fast-view-discount-tip', function() {
      $(this).closest('.b-catalog-detail__price-discount-tip').find('.b-catalog-detail__price-discount-tip-text').toggleClass('showed');
    });


  /**
     * Раскрашиваем таблицу с размерами
     */
    $("#modalSizeChart tr td:not([rowspan])").on({
        mouseenter: function() {
            var _this = $(this),
                tbody = _this.closest('tbody'),
                tr = _this.closest('tr'),
                numTd = _this.index(),
                numRowTd = 0;

            if(tr.find('td').attr("rowspan") > 0) {
                numRowTd=numTd-1;
            }
            else {
                numRowTd=numTd;
                numTd = numTd+1;
            }

            tbody.find('tr').each(function(){
                if($(this).find('td').attr("rowspan") > 0) {
                    $(this).find('td').eq(numTd).addClass('light-bg');
                }
                else {
                    $(this).find('td').eq(numRowTd).addClass('light-bg');
                }
            });

            tr.addClass('light-tr');
            _this.addClass('dark-bg');

        },
        mouseleave: function() {
            var _this = $(this),
                tbody = _this.closest('tbody'),
                tr = _this.closest('tr');

            _this.removeClass('dark-bg');
            tr.removeClass('light-tr');
            tbody.find('td.light-bg').removeClass('light-bg');
        }
    });

});

/**
 * функция для выбора ску по размеру и цвету
 */

var selectedSKU;

function setSKU(callback) {
    // color_available - глаобальная переменная в template.php определена
    // sku_tree - глаобальная переменная в template.php определена

    var tree = sku_tree,
        size = $('.b-catalog-detail__sizes-list-item.active').attr('data-id'),
        $sizes = $('.b-catalog-detail__sizes-list-item'),
        $colors = $('.b-catalog-detail__colors-list-item'),
        availableKeys = [],
        color = $('.b-catalog-detail__colors-list-item.active').attr('data-id');
    var shopsBlock = $('.b-shops');
    var firstSize;
    var firstBuy;
    var firstDelivery;

    if (hide_retail == 'Y') $('.b-shops').addClass('hide-btn');

    if(typeof color == 'undefined') {
        var $first = $('.b-catalog-detail__colors-list-item :first-child');
        $first.addClass('active');
        color = $first.attr('data-id');
    }

    $.each(tree[color], function (key, value) {
        if (value['DENIED_DELIVERY'] == 'N' || value['DENIED_RESERVATION'] == 'N') availableKeys.push(key);

        // выбор размера первого доступного к добавлению в корзину
        if (!firstBuy && value['DENIED_DELIVERY'] == 'N') firstBuy = key;

        // выбор размера первого доступного к бронированию
        if (!firstDelivery && value['DENIED_RESERVATION'] == 'N') firstDelivery = key;

        // если не найдено ни одного активного размера, то страховка с первым элементом в sku_tree
        if (!firstSize) firstSize = key;

        if (value['DENIED_DELIVERY'] == 'Y') $('.b-catalog-detail__sizes-list-item[data-id="' + key + '"]').addClass('not-buy');
        if (value['DENIED_RESERVATION'] == 'Y') $('.b-catalog-detail__sizes-list-item[data-id="' + key + '"]').addClass('not-rezerv');
    });

    size = size || firstBuy || firstDelivery || firstSize;
    $('.b-catalog-detail__sizes-list-item[data-id="' + size + '"]').addClass('active');

    var selected = tree[color][size];
    selectedSKU = selected;

    if (shopsBlock.length) shopsBlock.toggle(selectedSKU['DENIED_RESERVATION'] === 'N');

    // деактивация отсутствующих размеров
    $sizes.each(function () {
        var $this = $(this),
            id = $this.attr('data-id');
        if($.inArray(id, availableKeys) == -1) {
            $this.addClass('notavailable');
        } else {
            $this.removeClass('notavailable');
        }
    });

    // установка значений ску
    $('.b-catalog-detail__sizes-select').html(selected.SIZE);
    $('.b-catalog-detail__colors-select').html(selected.COLOR);
    $('.b-catalog-detail__artnum-data').html(selected.ARTNUMBER);

    loadRetailShops(selected.ARTNUMBER, selected['DENIED_RESERVATION']);

    if(selected.DISCOUNT_PRICE) {
        $('.b-catalog-detail').find('.price-sale_no').attr('class', 'price-sale');
        $('.b-catalog-detail').find('.price-sale').html(selected.DISCOUNT_PRICE);
    } else {
        $('.b-catalog-detail').find('.price-sale').attr('class', 'price-sale_no');
    }
    $('.b-catalog-detail').find('.price-base').html(selected.PRICE);
    $('#offerId').val(selected.ID);


    var $qu_selector = $('.b-catalog-detail__count-selector');
    $qu_selector.find('option').remove();
    var $basketBtn = $('.b-catalog-detail__add2basket'),
        $emptyField = $('.b-catalog-detail__empty');

    if(selected.QUANTITY > 0 && selected['DENIED_DELIVERY'] !== 'Y') {
        $basketBtn.removeAttr('disabled');
        //$emptyField.addClass('hidden');
        for (var i = 1; i <= selected.QUANTITY; i++) {
            var option = $('<option></option>').attr("value", i).text(i);
            $qu_selector.append(option);
        }
    } else {
        var option = $('<option></option>').attr("value", 1).text(1);
        $qu_selector.append(option);
        $basketBtn.attr('disabled', 'disabled');
        //$emptyField.removeClass('hidden');
    }
    // ! установка значений ску

    var $currentColor = $('.b-catalog-detail__photos-current[data-color='+selected.COLOR_CODE+']');
    $currentColor.addClass('active');
    $('.b-catalog-detail__photos-current').not($currentColor).removeClass('active');

    // деактивация цвета, если его нет в наличии
    $colors.each(function () {
        var $this = $(this),
            id = parseInt($this.attr('data-id'));
        if($.inArray(id, color_available[size]) == -1) {
            $this.addClass('notavailable');
        } else {
            $this.removeClass('notavailable');
        }
    });

    // определение состояния кнопки 'Добавить в корзину'
    // в зависимости нахождения данного sku в корзин
    if (skuInCart[selected.ID]) {
      $basketBtn.addClass('added');
    } else {
      $basketBtn.removeClass('added');
    }

  var _hasSize = ($('.b-catalog-detail__sizes-list-item').length != $('.b-catalog-detail__sizes-list-item.notavailable').length);
  $('.b-catalog-detail__sizes-titles, .b-catalog-detail__add2basket, .b-catalog-detail__reservation-all').toggleClass('hidden', !_hasSize);
  $('.b-catalog-detail-no').toggleClass('hidden', _hasSize);

  if (typeof callback === "function") {
    callback();
  }

}

function  runSlick() {
  /**
   * просомтр изображений
   */

  var photos = '.b-catalog-detail__photos-list';

  $('body').find(photos).each(function() {
    var $self = $(this);
    $self.slick({
      infinite: true,
      arrows: false,
      speed: 500,
      fade: true,
      cssEase: 'linear'
    });

    $self.parents('.b-catalog-detail__photos-current').find('.b-catalog-detail__photos-list-nav-item').on('click', function() {
      var $this = $(this);
      $self.slick('slickGoTo', $this.index());
    })
  });

}
