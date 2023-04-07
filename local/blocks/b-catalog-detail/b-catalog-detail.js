$(function() {
  /**
   * раскрытие подробного описания на детальной странице
   * @type {*|jQuery|HTMLElement}
   */

  window.skuInCart = {};

  setSKU();
  initComplect();

  var $more_description = $('.b-catalog-detail__description-more'), $body = $('body'), btn2basket = '.b-catalog-detail__add2basket',
      $detail_description = $('.b-catalog-detail__description-detail');
  $more_description.on('click', function() {
    $(this).hide();
    $detail_description.fadeIn();
  });

  /**
   * селектор размера
   */

  var size_select = '.b-catalog-detail__sizes-list-item', $size_select = $(size_select);
  $body.on('click', size_select, function() {
    var $this = $(this);
    $this.addClass('active');
    $size_select.parent().find(size_select).not($this).removeClass('active');
    setSKU();
  });

  /**
   * селектор цвета
   */

  var color_select = '.b-catalog-detail__colors-list-item', $color_select = $(color_select);
  $body.on('click', color_select, function() {
    var $this = $(this);
    $this.addClass('active');
    $color_select.parent().find(color_select).not($this).removeClass('active');
    setSKU();
  });

  /**
   * просомтр изображений
   */

  var photos = '.b-catalog-detail__photos-list';

  $body.find(photos).each(function() {
    var $self = $(this);
    $self.slick({
      infinite : true,
      arrows   : false,
      speed    : 500,
      fade     : true,
      cssEase  : 'linear'
    });

    $self.parents('.b-catalog-detail__photos-current').find('.b-catalog-detail__photos-list-nav-item').on('click', function() {
      var $this = $(this);
      $self.slick('slickGoTo', $this.index());
    })
  });


  $body.on('click', '.b-catalog-detail__social-item', function(e) {
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
  $body.on('click', btn2basket, function(e) {
    var $this = $(this), offerId = $('#offerId').val(), quantity = $('.b-catalog-detail__count-selector').val(), url = $this.attr('data-url');

    if (quantity === undefined) { // JC-97
      quantity = 1;
    }
    e.preventDefault();

    if (!$this.hasClass('added') && !$this.hasClass('btn_load')) {
      $this.addClass('btn_load');
      $this.attr('disabled', 'disabled');

      url = '/local/ajax/update_small_cart.php';
      $.get(url, {
        action      : 'ADD2BASKET',
        quantity    : quantity,
        id          : offerId,
        ajax_basket : 'Y'
      })
          .done(function() {
            var $cart = $('.b-fast-cart');

            /** DigitalDataLayer start */
            if (typeof window.digitalData.events !== 'undefined' && typeof selectedSKU !== 'undefined') {
              var product = null;
              var productId = $this.data('product-id');

              // Добавление может быть на детальной странице и на странице раздела (быстрый просмотр)
              // а также из блока рекомендаций (быстрый просмотр)
              // нужно пройтись по всем трем объектам, чтобы найти подходящий product

              if (typeof window.digitalData.product !== 'undefined') {
                if (window.digitalData.product.id == productId) {
                  product = window.digitalData.product;
                }
              } else if (!product && typeof window.digitalData.listing !== 'undefined') {
                for (var i = 0; i < window.digitalData.listing.items.length; i++) {
                  if (window.digitalData.listing.items[i].id == productId) {
                    product = window.digitalData.listing.items[i];
                    break;
                  }
                }
              } else if (!product && typeof window.digitalData.recommendation !== 'undefined') {
                // если объектов recommendation несколько
                for (var i = 0; i < window.digitalData.recommendation.length; i++) {
                  for (var j = 0; j < window.digitalData.recommendation[i].items.length; j++) {
                    if (window.digitalData.recommendation[i].items[j].id == productId) {
                      product = window.digitalData.recommendation[i].items[j];
                      break;
                    }
                  }
                }
              }

              if (product) {
                var params = {};
                params.event = 'added';
                params.product = product;
                if (typeof window.digitalData.events !== 'undefined' && typeof selectedSKU !== 'undefined') {
                  if (params.event == 'added') {
                    var quantity = 1;

                    for (var key in window.digitalData.cart.lineItems) {
                      if (params.product.id == window.digitalData.cart.lineItems[key]) {
                        quantity = window.digitalData.cart.lineItems[key].quantity + 1;
                      }
                    }

                    params.product.skuCode = offerId;
                    window.digitalData.events.push({
                      "category" : "Ecommerce",
                      "name"     : "Added Product",
                      "product"  : {
                        "id"      : params.product.id,
                        "skuCode" : params.product.skuCode
                      },
                      "quantity" : 1 * quantity
                    });
                  }

                }

              }
            }
            /** DigitalDataLayer end */

            // передадим action в параметр корзины
            window.cartId.action = 'add';

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

  $body.on('click', '.b-catalog-detail__add2basket.added', function() {
    location.href = '/order/';
  });

  $body.on('click', '.b-catalog-detail__count-to-shops', function(e) {
    scrollToAnchor('stores');
  });

  // Подсказка о скидке
  $body.on('click', '.js-discount-tip', function() {
    $(this).closest('.b-catalog-detail__price-discount-tip').find('.b-catalog-detail__price-discount-tip-text').toggleClass('showed');
  });

  /**
   * Раскрашиваем таблицу с размерами
   */
  $("#modalSizeChart tr td:not([rowspan])").on({
    mouseenter : function() {
      var _this = $(this), tbody = _this.closest('tbody'), tr = _this.closest('tr'), numTd = _this.index(), numRowTd = 0;

      if (tr.find('td').attr("rowspan") > 0) {
        numRowTd = numTd - 1;
      } else {
        numRowTd = numTd;
        numTd = numTd + 1;
      }

      tbody.find('tr').each(function() {
        if ($(this).find('td').attr("rowspan") > 0) {
          $(this).find('td').eq(numTd).addClass('light-bg');
        } else {
          $(this).find('td').eq(numRowTd).addClass('light-bg');
        }
      });

      tr.addClass('light-tr');
      _this.addClass('dark-bg');

    },
    mouseleave : function() {
      var _this = $(this), tbody = _this.closest('tbody'), tr = _this.closest('tr');

      _this.removeClass('dark-bg');
      tr.removeClass('light-tr');
      tbody.find('td.light-bg').removeClass('light-bg');
    }
  });

  // Предзаказ

  var _form = $('.reservationForm');

  _form.validate({
    rules         : {
      'email' : {
        email       : true,
        mailFormat  : true,
      }
    },
    submitHandler : function() {

      BX.showWait();

      $.post('/local/ajax/subscribeProduct.php', _form.serialize()).done(function(html) {
        $('#product_subscriptionModal').find('.modal-body').html(html);
        BX.closeWait();
      });

      return false;
    }
  });
  return false;

});

/**
 * функция для выбора ску по размеру и цвету
 */

var selectedSKU;

function setSKU() {
  // color_available - глаобальная переменная в template.php определена
  // sku_tree - глаобальная переменная в template.php определена

  if(preorder == 'Y'){
    if($('.b-catalog-detail__sizes-list-item.active').length == 0) $('.b-catalog-detail__sizes-list-item').first().addClass('active');

    $('.b-catalog-detail__add2basket').addClass('hidden');
    $('.b-catalog-detail__reservation-all').addClass('hidden');

    var tree = sku_tree, size = $('.b-catalog-detail__sizes-list-item.active').attr('data-id');

    var keys = Object.keys(tree); //получаем ключи объекта в виде массива
    var selected = tree[keys[0]][size];
    $('.b-catalog-detail__sizes-select').html(selected.SIZE);
    $('.b-modal-reserved__product-size').html(selected.SIZE);
    $('.b-catalog-detail__colors-select').html(selected.COLOR);
    $('.b-catalog-detail__artnum-data').html(selected.ARTNUMBER);
    $('.sku-id').val(selected.ARTNUMBER);
    $('.b-modal-reserved__product-price').find('.price-base').html(selected.PRICE);
    if (selected.DISCOUNT_PRICE) {
      $('.b-catalog-detail__price').find('.price-sale_no').attr('class', 'price-sale');
      $('.b-catalog-detail__price').find('.price-sale').html(selected.DISCOUNT_PRICE);
      $('.b-modal-reserved__product-price').find('.price-sale_no').attr('class', 'price-sale');
      $('.b-modal-reserved__product-price').find('.price-sale').html(selected.DISCOUNT_PRICE);
    } else {
      $('.b-catalog-detail__price').find('.price-sale').attr('class', 'price-sale_no');
      $('.b-modal-reserved__product-price').find('.price-sale').attr('class', 'price-sale_no');
      $('.b-modal-reserved__product-price').find('.price-base').html(selected.PRICE);
    }
    return false;
  }

  var tree = sku_tree, size = $('.b-catalog-detail__sizes-list-item.active').attr('data-id'),
      $sizes = $('.b-catalog-detail__sizes .b-catalog-detail__sizes-list-item'), $colors = $('.b-catalog-detail__colors-list-item'), availableKeys = [],
      color = $('.b-catalog-detail__colors-list-item.active').attr('data-id');
  var shopsBlock = $('.b-shops');
  var firstSize;
  var firstBuy;
  var firstDelivery;

  if (hide_retail == 'Y') $('.b-shops').addClass('hide-btn');

  if (typeof color == 'undefined') {
    var $first = $('.b-catalog-detail__colors-list-item :first-child');
    $first.addClass('active');
    color = $first.attr('data-id');
  }

  $.each(tree[color], function(key, value) {
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

  $('.b-modal-reserved__product-size').text($('.b-catalog-detail__sizes-list-item[data-id="' + size + '"]').text());

  var selected = tree[color][size];
  selectedSKU = selected;

  $('.sku-id').val(selectedSKU['ID']);

  if (shopsBlock.length) shopsBlock.toggle(selectedSKU['DENIED_RESERVATION'] === 'N');

  // деактивация отсутствующих размеров
  $sizes.each(function() {
    var $this = $(this), id = $this.attr('data-id');
    if ($.inArray(id, availableKeys) == -1) {
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

  if (selected.DISCOUNT_PRICE) {
    $('.b-catalog-detail__price').find('.price-sale_no').attr('class', 'price-sale');
    $('.b-catalog-detail__price').find('.price-sale').html(selected.DISCOUNT_PRICE);
    $('.b-modal-reserved__product-price').find('.price-sale_no').attr('class', 'price-sale');
    $('.b-modal-reserved__product-price').find('.price-sale').html(selected.DISCOUNT_PRICE);
  } else {
    $('.b-catalog-detail__price').find('.price-sale').attr('class', 'price-sale_no');
    $('.b-modal-reserved__product-price').find('.price-sale').attr('class', 'price-sale_no');
  }
  $('.b-catalog-detail__price').find('.price-base').html(selected.PRICE);
  $('.b-modal-reserved__product-price').find('.price-base').html(selected.PRICE);
  $('#offerId').val(selected.ID);


  var $qu_selector = $('.b-catalog-detail__count-selector');
  $qu_selector.find('option').remove();
  var $basketBtn = $('.b-catalog-detail__add2basket'), $emptyField = $('.b-catalog-detail__empty');

  if (selected.QUANTITY > 0 && selected['DENIED_DELIVERY'] !== 'Y') {
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

  var $currentColor = $('.b-catalog-detail__photos-current[data-color=' + selected.COLOR_CODE + ']');
  $currentColor.addClass('active');
  $('.b-catalog-detail__photos-current').not($currentColor).removeClass('active');

  // деактивация цвета, если его нет в наличии
  $colors.each(function() {
    var $this = $(this), id = parseInt($this.attr('data-id'));
    if ($.inArray(id, color_available[size]) == -1) {
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

}

function initComplect() {
  if (!$('.complect-block').length) return false;

  var _block = $('.complect-block');

  _block.find('.slide-sizes').on('click', function() {

    $(this).closest('.detail-sizes').toggleClass('opened');
    return false;
  });

  _block.find('input.add').on('change', function() {

    $(this).closest('.complect-item-block').toggleClass('unselected', !$(this).prop('checked'));

    /*
    var _labelText = ($(this).prop('checked')) ? 'Убать из комплекта' : 'Добавить в комплект';
    $(this).parent().find('label').html(_labelText);
    */

    checkTotalComplectPrice();
    return false;
  });

  _block.find('.complect-item').each(function() {
    var _one = $(this);

    _one.find('.list-sizes').find('input[type="radio"]').change(function() {
      if ($(this).prop('checked')) {
        var _size = $(this);
        var _item = _size.closest('.complect-item-block');
        var _id = _item.data('id');

        var _article = _size.data('article');
        var _pricebase = _size.data('pricebase');
        var _price = _size.data('price');

        _one.find('#' + _id + '-article').html('<span>Артикул: </span>' + _article);
        if (_pricebase > _price) {
          _one.find('#' + _id + '-price').html('<div class="price-sale">' + _size.data('priceformat') + '</div><div class="price-base">' + _size.data('pricebaseformat') + '</div>');
        } else {
          _one.find('#' + _id + '-price').html('<div class="price-base">' + _size.data('priceformat') + '</div>');
        }

        checkTotalComplectPrice();
      }
    });

    _one.find('.b-catalog-detail__sizes-list-item').first().click();
  });

  _block.find('#buyComplect').on('click', function() {
    var _offers = [];
    _block.find('.complect-item-block').each(function() {
      if ($(this).find('input.add:checked').length) {
        var _offer = $(this).find('.detail-sizes').find('input:checked').val();
        _offers.push(_offer);
      }
    });

    buyComplect(_block, _offers);

    return false;
  });

  return true;
}

function checkTotalComplectPrice() {
  var _block = $('.complect-block');
  var _total = 0;

  _block.find('.complect-item-block').each(function() {
    if ($(this).find('input.add:checked').length) {
      _total += parseInt($(this).find('.detail-sizes').find('input:checked').data('price'));
    }
  });

  _block.find('.complect-price span').first().html(_total);
  _block.find('#buyComplect').toggleClass('none', !_total);
}

function buyComplect(_block, _ids) {

  _block.find('#buyComplect').addClass('btn_load');

  $.post('/local/ajax/update_small_cart.php', {
    action      : 'ADD2BASKETLIST',
    ajax_basket : 'Y',
    ids         : _ids
  }, function() {

    /** DigitalDataLayer start */
    if (typeof window.digitalData.events !== 'undefined') {

      _block.find('.complect-item-block').each(function() {
        var _productId = $(this).data('id');

        if ($(this).find('input.add:checked').length) {
          var _offerId = $(this).find('.detail-sizes').find('input:checked').val();

          window.digitalData.events.push({
            "category" : "Ecommerce",
            "name"     : "Added Product",
            "product"  : {
              "id"      : _productId,
              "skuCode" : _offerId
            },
            "quantity" : 1
          });
        }
      });

    }
    /** DigitalDataLayer end */

    BX.onCustomEvent('OnBasketChange');
    _block.find('#buyComplect').removeClass('btn_load');

  }).fail(function(response) {
    console.log(response);
  });
}