$(function() {

  checkPlugins();
  var $form = $('#ORDER_FORM'), $body = $('body');

  $.validator.addMethod('regex', function(value, element, regexp) {
    var re = new RegExp(regexp);
    return this.optional(element) || re.test(value);
  }, 'Проверьте правильность заполнения');

  $.validator.addMethod("requiredConfirm", function(value, element) {
    value = $.trim(value);
    if (checkConfirm() && !value) return false;
    return true;
  }, 'Поле должно быть заполнено');

  $.validator.addMethod("requiredCourier", function(value, element) {

    if ($(element).attr('name') == 'courierStreet' && window.locationType != 'CITY') return true; // улица обязательна только для городов

    value = $.trim(value);
    var deliveryType = getDeliveryType();
    if ((deliveryType == 'courier' || deliveryType == 'day') && checkConfirm() && !value) return false;
    return true;
  }, 'Поле должно быть заполнено');

  var rules = {
    'ORDER_PROP_1'  : {requiredConfirm : true},
    'ORDER_PROP_4'  : {
      requiredConfirm : true,
      phoneFormat     : true
    },
    'ORDER_PROP_3'  : {
      requiredConfirm : true,
      email           : true,
      mailFormat      : true
    },
    'ORDER_PROP_5'  : {requiredConfirm : true},
    'ORDER_PROP_6'  : {requiredConfirm : true},
    'i-agree'       : {requiredConfirm : true},
    'courierStreet' : {requiredCourier : true},
    'courierHouse'  : {requiredCourier : true}
  };

  $form.validate({
    rules          : rules,
    submitHandler  : function(e, validator) {
      //console.log('submitHandler');
      var _confirm = checkConfirm();
      var deliveryType = getDeliveryType();
      var storeId = $('#ORDER_PROP_' + window.orderProps['STORE_ID']).val();
      var errors = [];

      window.preStoreType = deliveryType;
      window.preStoreId = storeId;

      if (_confirm) {
        if (!deliveryType) {
          errors.push('Не выбрана служба доставки');
          $('html, body').animate({scrollTop : $('.b-order__props-delivery-title').offset().top - 20}, 600);
        } else {
          if (deliveryType == 'ozon' && !storeId.length) errors.push('Не выбран пункт выдачи заказов');
          if (deliveryType == 'pickup' && !storeId.length) errors.push('Не выбран магазин для самовывоза товаров');
        }
      }

      if (errors.length) {
        orderPopup('Обратите внимание', errors.join('</p><p class="center">'));
        $('html, body').animate({scrollTop : $('.b-order__props-delivery-title').offset().top - 20}, 600);
      } else {
        if ($form.data('submit') != 'Y') {
          BX.showWait();
          $form.data('submit', 'Y');

          var _params = {};
          var _formParams = $form.serializeArray();
          for (var _i in _formParams) {
            if (_formParams[_i]['name'] == "DELIVERY_ID") {
              _formParams[_i]['value'] = parseInt(_formParams[_i]['value']);
              if (_formParams[_i]['value'] == window.deliveryId.courier && $('#fast_delivery').prop('checked')) {
                _formParams[_i]['value'] = parseInt($('#fast_delivery').data('delivery'));
              }
            }

            _params[_formParams[_i]['name']] = _formParams[_i]['value'];
          }

          $.post($form.attr('action'), _params, function(e) {
            BX.closeWait();
            $form.data('submit', 'N');
            ajaxResult(e);
          });
        }
      }

      return false;
    },
    invalidHandler : function(e, validator) {
      //console.log('invalidHandler');
      for (var i = 0; i < validator.errorList.length; i++) {
        var element = validator.errorList[i].element;
        $('html, body').animate({scrollTop : $(element).offset().top - 20}, 600);
        break;
      }

      BX.closeWait();
    }
  });

  $body.on('click', '#ORDER_CONFIRM_BUTTON', function(e) {
    e.preventDefault();

    submitForm('Y');
  });

  // удалить товар
  $body.on('click', '.b-order__basket-item-delete', function() {
    var $this = $(this), id = $this.attr('data-id'), skuId = $this.data('product-id'), product = null, quantityInBasket = 0;

    /** DigitalDataLayer start */
    if (typeof window.digitalData.events !== 'undefined' && typeof window.digitalData.cart !== 'undefined') {

      for (var i = 0; i < window.digitalData.cart.lineItems.length; i++) {
        if (window.digitalData.cart.lineItems[i].product.skuCode == skuId) {
          product = window.digitalData.cart.lineItems[i].product;
          quantityInBasket = window.digitalData.cart.lineItems[i].quantity;
          break;
        }
      }
      window.digitalData.events.push({
        "category" : "Ecommerce",
        "name"     : "Removed Product",
        "product"  : {
          "id"      : product.id,
          "skuCode" : product.skuCode
        },
        "quantity" : quantityInBasket
      });


      $.post('/local/ajax/remove_from_basket.php', {
        sessid : $('#sessid').val(),
        id     : id
      }).done(function() {
        submitForm();
      });
      /** DigitalDataLayer end */
    }
  });

  // купон
  $body.on('click', '.btn-set-coupon', function(e) {
    e.preventDefault();
    var coupon = $('.b-order__promo-code-input').val();
    if (coupon) {
      $.post('/local/ajax/set_coupon.php', {
        sessid : $('#sessid').val(),
        coupon : coupon
      }).done(function(data) {
        submitForm();
      });
    }
  });

  $body.on('click', '.b-order-basket__side-promo-coupon-remove', function() {
    $.post('/local/ajax/remove_coupon.php', {
      sessid : $('#sessid').val(),
      coupon : $(this).data('coupon')
    }).done(function(data) {
      submitForm();
    });
  });

  $body.on('click', '#accept_loyalty', function(e) {
    e.preventDefault();
    var $validator = $('.b-order__bonus-card').validate(), $this = $(this), number = $('input[name=LOYALTY_CARD]').val();
    if (number) {
      var check_num = Math.floor(number / 1000000);
      if (check_num == 79) {
        $this.addClass('btn_load');
        $.post('/local/ajax/set_bonus.php', {
          number : number,
          sessid : $('#sessid').val()
        }).done(function(data) {
          var card = JSON.parse(data);
          if (card.BALANCE > 0) {
            $('input[name=LOYALTY_VALID]').val('Y');
            $('.b-order__bonus-card-balance').html(card.BALANCE_FORMATTED);
            $('.b-order__bonus-card-calculation').html(card.ADD_TO_CARD_FORMATTED);
            if (card.WRITE_OFF_FORMATTED != -1) {
              $('.b-order__bonus-card-writeoff').html(card * 0.2);
            }
          } else {
            errors = {LOYALTY_CARD : "Данная карта не относится к бонусной программе Juicy Couture"};
            $validator.showErrors(errors);
          }
          $('.b-order__bonus-card-info').removeClass('hidden');
          $this.removeClass('btn_load');
        });
      } else {
        errors = {LOYALTY_CARD : "Данная карта не относится к бонусной программе Juicy Couture"};
        $validator.showErrors(errors);
      }
    } else {
      errors = {LOYALTY_CARD : "Введите номер бонусной карты"};
      $validator.showErrors(errors);
    }
  });

  $body.on('click', '.b-order__props-delivery label.btn', function() {
    /** DigitalDataLayer start */
    if (typeof window.digitalData.events !== 'undefined') {
      var delName = $(this).find('div').text(), shippingCost = $(this).find('div').attr('data-delivery');
      window.digitalData.cart.shippingMethod = $.trim(delName);
      window.digitalData.cart.shippingCost = shippingCost;
      window.digitalData.events.push({
        'name' : 'Selected Shipping Method',
        'cart' : window.digitalData.cart
      });
    }
    /** DigitalDataLayer end */
    setTimeout(function() {submitForm();}, 10);
  });

  $body.on('click', '.b-order__props-payment label', function() {
    /** DigitalDataLayer start */
    if (typeof window.digitalData.events !== 'undefined') {
      var payName = $(this).find('div').text();
      window.digitalData.cart.paymentMethod = $.trim(payName);
      window.digitalData.events.push({
        'name' : 'Selected Payment Method',
        'cart' : window.digitalData.cart
      });
    }
    /** DigitalDataLayer end */
    setTimeout(function() {submitForm();}, 10);
  });

  $body.on('click', '.mobile-hidden-form .hidden-block-title', function() {
    $(this).closest('.mobile-hidden-form').toggleClass('show-form');
  });

  $('#closeQuickViewProduct').on('click', function(e) {
    $('#quickViewProduct').modal('hide');
  });

  initOrderActions();

  $('#payOnlineHelp').tooltip({
    html      : true,
    title     : $('#payOnlineHelpText').html(),
    placement : 'right',
    template  : '<div class="tooltip tooltip-online" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>',
    trigger   : 'click'
  });

  $('#js-popup-error').on('hidden.bs.modal', function(e) {
    submitForm();
  });

  BX.closeWait();
});


function submitForm(val) {
  BX.closeWait();

  val = (val == 'Y') ? 'Y' : 'N';
  BX('confirmorder').value = val;

  //console.log('submit, ' + val);

  var orderForm = $('#ORDER_FORM');
  orderForm.submit();
}

function ajaxResult(res) {
  var orderForm = BX('ORDER_FORM');
  try {
    var json = JSON.parse(res);

    if (json.error) {
      $('#js-popup-error').modal('show');
      initActions();
      return;
    } else if (json.redirect) {
      window.top.location.href = json.redirect;
    }
  } catch (e) {
    // json parse failed, so it is a simple chunk of html
    $('#order_form_content').html(res);

    initActions();
  }

  BX.onCustomEvent(orderForm, 'onAjaxSuccess');
}

function SetContact(profileId) {
  BX("profile_change").value = "Y";
  submitForm();
}

function initOrderActions() {
  var body = $('body');

  if (!$('input[name="DELIVERY_ID"]:checked').val()) {
    $('input[name="DELIVERY_ID"]').first().click();
  }

  // адрес доставки курьером, все поля
  body.on('keyup blur', '.js-delivery-address', function(e) {
    checkCourierAddress();
  });

  // смотреть ПВЗ / РМ "на карте"
  body.on('click', '.js-pvz-on-map', function(e) {
    e.preventDefault();

    $(this).addClass('active');
    $('.js-pvz-in-list').removeClass('active');

    var $parent = $(this).parent().parent();

    $parent.find('.delivery-pvz-map').removeClass('hidden');
    $parent.find('.delivery-pvz-list').addClass('hidden');
  });

  // смотреть ПВЗ / РМ "списком"
  body.on('click', '.js-pvz-in-list', function(e) {
    e.preventDefault();

    // закрыть попап, если он открыт
    $('.delivery-pvz-side .b-page__collapse-top__close').click();

    $(this).addClass('active');
    $('.js-pvz-on-map').removeClass('active');

    var $parent = $(this).parent().parent();

    $parent.find('.delivery-pvz-list').removeClass('hidden');
    $parent.find('.delivery-pvz-map').addClass('hidden');
  });

  // выбрать ПВЗ \ РМ в всплывающей форме после клика на точку
  body.on('click', '.b-order-basket__main-contacts-text-form-btn', function(e) {
    e.preventDefault();

    var type = $(this).data('type');
    var id = $(this).data('id');
    var object = false;

    if (type == 'pvz') object = window.pvz[id];
    if (type == 'shop') object = window.shops[id];

    $(this).closest('.delivery-pvz-side').hide();
    setPickupPlace(type, object);
  });

  // выбрать ПВЗ / РМ из списка
  body.on('click', '.delivery-pvz-list-item', function(e) {
    e.preventDefault();

    var pvz = $(this).data('pvz');
    var shop = $(this).data('shop');
    var type = (pvz) ? 'pvz' : 'shop';
    var object = false;

    if (type == 'pvz') object = window.pvz[pvz];
    if (type == 'shop') object = window.shops[shop];

    setPickupPlace(type, object);
  });

  initActions();
}

function initActions() {
  $('.mask-phone').mask("+7(999)999-99-99");
  //$('#ORDER_CONFIRM_BUTTON').removeClass('hidden');

  // показать карту, если выбрана доставка с картой
  var mapBlock = $('.b-order__props-delivery .delivery-block.active').find('.delivery-pvz-map');
  if (mapBlock.length > 0) {
    initMap(mapBlock.attr('id'));
  }

  $('.b-city-popup__search-btn').on('click', function() {
    var cityId = $('.b-city-popup__search input[name=LOCATION]').val();
    $('#ORDER_PROP_' + window.orderProps['TARIF_LOCATION']).val(cityId);
    $('#courierStreet').val('').data('selected', '');
    submitForm();
  });

  // скрыть попап после клика на точку
  $('.delivery-pvz-side .b-page__collapse-top__close').on('click', function() {
    $(this).closest('.delivery-pvz-side').hide();
  });

  // попап "как добраться" с полным текстом (на странице он весь не влез)
  $('.b-order__props-delivery .delivery-block .delivery-pvz-side-how-text').on('click', function() {
    var html = $(this).html();
    html = html.replace(/(Далее|Возле|Транспортом|Пешком|Расположение)/g, '</p><p>$1'); // каждый блок с новой строки
    orderPopup('Как нас найти', html);
  });

  //изменить способ доставки
  $('#deliveryPlaceChange').on('click', function() {
    $('#ORDER_PROP_' + window.orderProps['STORE_ID']).val('');
    $('#ORDER_PROP_' + window.orderProps['F_ADDRESS']).val('');

    //$('#basketItems').find('.basket-one').removeClass('no_delivered');
    $('.delivery-pvz-list-item.active').removeClass('active');

    $(this).parent().hide();
    $('.delivery-block.active').show();
  });

  var _deliveryType = getDeliveryType();
  if (_deliveryType == 'courier' || _deliveryType == 'day') {
    checkCourierAddress();
  } else {
    checkSelectedStore();
  }

  if ($('#fast_delivery').length) {
    $('#fast_delivery').on('change', function() {
      submitForm();
    });
  }

  $('.delivery-courier-fast .question').off('click');
  $('.delivery-courier-fast .question').on('click', function() {
    $(this).toggleClass('minus');
    $(this).parent().find('.delivery-courier-fast-description').toggleClass('hidden');
  });
  $('.delivery-courier-fast label').off('click');
  $('.delivery-courier-fast label').on('click', function() {
    $('#fast_delivery').click();
  });

  checkOmni();

  initStreetAutocomplete();
  initOnlinePayDiscountCheckbox();
  showOnlinePayDiscountInfo();

  showGifts(window.gifts);

  if (!window.dubbleReload) window.dubbleReload = false;
  console.log('window.needReload=' + window.needReload);
  console.log('window.dubbleReload=' + window.dubbleReload);
  if (window.needReload == 'Y' && !window.dubbleReload) {
    window.dubbleReload = true;
    window.needReload = 'N';
    submitForm();
    return false;
  } else if (window.dubbleReload) {
    window.dubbleReload = false;
  }
  
  $('#payOnlineHelp').tooltip({
    html      : true,
    title     : $('#payOnlineHelpText').html(),
    placement : 'right',
    template  : '<div class="tooltip tooltip-online" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>',
    trigger   : 'click'
  });
}

function showGifts(_gifts) {
  var _popup = $('#js-popup-gifts');
  var _giftList = _popup.find('.gift-list');
  _giftList.html('');

  _popup.find('.js-popup-close').click();

  if (!_gifts) return false;

  var _hasGift = false;
  for (var _i in _gifts) {
    var _gift = _gifts[_i];
    _hasGift = true;

    _giftList.append('\
    <div class="gift-one" data-id="' + _gift.ID + '" data-quantity=' + _gift.QUANTITY + ' data-rule=' + _gift.RULE_ID + '>\
      <div class="gift-image">\
        <img src="' + _gift.PHOTO + '" alt="' + _gift.NAME + '">\
      </div>\
      <div class="gift-data">\
        <div class="gift-name">' + _gift.NAME + '</div>\
        <div class="gift-article">' + _gift.ARTICLE + '</div>\
        <div class="gift-size">Размер: ' + _gift.SIZE + '</div>\
        <div class="gift-color">Цвет: ' + _gift.COLOR + '</div>\
        <div class="gift-price">' + _gift.PRICE + '</div>\
        <a class="btn btn-primary" href="#">Выбрать</a>\
      </div>\
    </div>\
    ');
  }

  _giftList.find('a').on('click', function() {
    _popup.modal('hide');

    BX.showWait();

    $.post('/local/ajax/update_small_cart.php', {
      'action'   : 'ADD2BASKETGIFT',
      'id'       : $(this).closest('.gift-one').data('id'),
      'quantity' : $(this).closest('.gift-one').data('quantity'),
      'rule'     : $(this).closest('.gift-one').data('rule'),
    }, function(response) {
      submitForm('N');
    }).fail(function(response) {
      //console.log(response);
      BX.closeWait();
    });

    return false;
  });

  if (_hasGift) _popup.modal('show');

  return true;
}

function initStreetAutocomplete() {

  // Выпадающая панель с поисковыми подсказками у поля ввода улицы.
  ymaps.ready(function() {
    var suggestView = new ymaps.SuggestView('courierStreet', {
      provider : {
        suggest : (function(request, options) {
          return ymaps.suggest($('.b-header__top-location-city').text() + ", " + request);
        })
      }
    });
    suggestView.events.add("select", function(e) {
      checkCourierAddress();
    })
  });
  /*
  if (window.locationType != 'CITY') return false; // подстановка только для городов
  if (!window.locationStreets || !window.locationStreets.length) return false;

  $('#courierStreet').autocomplete({
    lookup      : window.locationStreets,
    onSelect    : function(suggestion) {
      $(this).data('selected', 'selected');
      $(this).removeAttr('aria-invalid');
      $(this).removeClass('error');
      $(this).siblings('label.error').remove();
      $(this).trigger('blur');
    },
    lookupLimit : 10
  }).off('change').on('change', function() {
    if ($(this).data('selected') != 'selected') {
      // $(this).val('');
      //addInputError($(this), 'Такой улицы в г. ' + window.digitalData.website.region + ' нет');
    }
  }).on('click', function() {
    if ($(this).data('selected') == 'selected') {
      var _preCityName = $(this).val();
      $(this).val('').data('selected', '').focus().val(_preCityName).select();
    }
  });

  if (navigator.userAgent.toLowerCase().indexOf('chrome')) {
    $('#courierStreet').attr('autocomplete', 'new-password'); // блокирует автозаполнение в хроме
  }
  */
}

function checkCourierAddress() {
  var addressInput = $('#ORDER_PROP_' + window.orderProps['F_ADDRESS']);
  var sideAddress = $('.js-cart-address');

  var address = [];
  $('.js-delivery-address').each(function(i, el) {
    var text = $(this).val();
    if (text.length > 0) {
      address.push($(this).data('text') + text);
    }
  });

  address = address.join(', ');
  addressInput.val(address);
  sideAddress.html(address);
}

function orderPopup(title, text) {
  var popup = $('#quickViewProduct');
  var html = '<h4 class="modal-title text-center">' + title + '</h4>';
  html += '<p class="text-center" style="margin-top: 10px;">' + text + '</p>';

  popup.find('.modal-content').html(html);
  popup.modal('show');
}

function getDeliveryType() {
  var deliveryId = $('input[type="radio"][name="DELIVERY_ID"]:checked').val();
  for (var i in window.deliveryId) {
    if (window.deliveryId[i] == deliveryId) {
      return i;
    }
  }

  return false;
}

function getPaymentType() {
  var paySystemId = $('input[type="radio"][name="PAY_SYSTEM_ID"]:checked').val();
  for (var i in window.paySystemId) {
    if (window.paySystemId[i] == paySystemId) {
      return i;
    }
  }

  return false;
}

function checkOmni() {
  var deliveryType = getDeliveryType();
  var items = false;
  var hasNoDelivered = false;

  if (deliveryType == 'courier' || deliveryType == 'day') {
    items = window.omni['DELIVERY'];
    if ($('#fast_delivery').prop('checked') || deliveryType == 'day') {
      items = window.omni['FAST_DELIVERY'];
    } else if (!items.length) {
      items = window.omni['OMNI_DELIVERY'];
    }
  }
  if (deliveryType == 'ozon') {
    items = window.omni['PICK_POINT'];
  }
  if (deliveryType == 'pickup') {
    items = window.omni['PICKUP']; // список разрешенных
    var shop = $('#ORDER_PROP_' + window.orderProps['STORE_ID']).val();
    if (shop) {
      // если выбран магазин, то список доступных товаров ограничивается
      var items = [];
      for (var id in window.omni['SHOPS'][shop]) {
        items.push(parseInt(id));
      }
    }
  }

  var itemsBlock = $('.bx_ordercart_order_table_container');
  var prices = {
    main     : 0,
    discount : 0,
    loyalty  : 0,
    delivery : parseInt($('.delivery-price').data('price'))
  };
  itemsBlock.find('.b-order__basket-item').removeClass('no_delivered').find('.no_delivered_block').remove();
  itemsBlock.find('.b-order__basket-item').each(function() {
    var id = $(this).data('id');
    var price = $(this).data('price');
    var priceBase = $(this).data('baseprice');
    var loyalty = $(this).data('loyalty');
    var q = $(this).data('quantity');

    if ($.inArray(id, items) > -1) {
      prices['main'] += price * q;
      if (priceBase > price) {
        prices['discount'] += (priceBase - price) * q;
      }
      if (loyalty > 0) {
        prices['loyalty'] += loyalty * q;
      }
    } else {
      $(this).addClass('no_delivered').find('.col-xs-8').append('<div class="no_delivered_block">Не доставляется выбранным способом. Товар можно заказать отдельно.</div>');
      hasNoDelivered = true;
    }
  });

  prices['main'] += prices['delivery'];
  prices['discount'] -= prices['loyalty'];

  var totalDiscountBlock = $('#totalDiscount');
  totalDiscountBlock.find('.discount-value').html('-' + priceFormat(prices['discount']));
  totalDiscountBlock.toggleClass('hidden', prices['discount'] <= 0);

  var totalLoyaltyBlock = $('#totalLoyalty');
  totalLoyaltyBlock.html('-' + priceFormat(prices['loyalty']));
  totalLoyaltyBlock.toggleClass('hidden', prices['loyalty'] <= 0);

  $('#totalPrice').html(priceFormat(prices['main']));
  /* отключение всплывающего  окна JC-77
   var noDeliveryBlock = $('.b-order-basket__side-delivery-notes-no_delivery');
   noDeliveryBlock.on('click', function() {
   orderPopup('Не все товары могут быть доставлены выбранным способом', 'Все товары попадут в заказ, но к оплате будут доступны только те товары, которые можно доставить выбранным способом.\n' + 'Наши менеджеры свяжутся с Вами и предложат способы доставки оставшихся товаров.');
   });
   if (hasNoDelivered) {
   noDeliveryBlock.click();
   $('.no_delivered_block').on('click', function() {
   noDeliveryBlock.click();
   });
   }
   noDeliveryBlock.toggleClass('hidden', !hasNoDelivered);
   */

  if ($('#fast_delivery').prop('checked')) {
    if ($('#ID_PAY_SYSTEM_ID_3').prop('checked')) {
      $('label[for="ID_PAY_SYSTEM_ID_7"]').click();
    } else {
      $('#ID_PAY_SYSTEM_ID_3').prop('disabled', true);
      $('label[for="ID_PAY_SYSTEM_ID_3"]').attr('title', 'Для данного способа доставки доступна только "Онлайн оплата"');
    }
  } else {
    $('#ID_PAY_SYSTEM_ID_3').prop('disabled', false);
    $('label[for="ID_PAY_SYSTEM_ID_3"]').attr('title', '');
  }
}

function priceFormat(price) {
  return number_format(price, 0, '.', ' ') + ' &#8381;';
}

function number_format(number, decimals, dec_point, thousands_sep) {
  var i, j, kw, kd, km;
  if (isNaN(decimals = Math.abs(decimals))) decimals = 2;
  if (dec_point == undefined) dec_point = ",";
  if (thousands_sep == undefined) thousands_sep = ".";
  i = parseInt(number = (+number || 0).toFixed(decimals)) + "";
  j = ((j = i.length) > 3) ? j % 3 : 0;
  km = (j ? i.substr(0, j) + thousands_sep : "");
  kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
  kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");
  return km + kw + kd;
}

function checkSelectedStore() {
  var storeId = window.preStoreId;
  window.preStoreId = false;

  var type = false;
  var object = false;

  if (storeId != '') {
    if ($('#shopDeliveryBlock').is(':visible') && storeId in window.shops) {
      type = 'shop';
      object = window.shops[storeId];
    }
    if ($('#pvzDeliveryBlock').is(':visible') && storeId in window.pvz) {
      type = 'pvz';
      object = window.pvz[storeId];
    }

    if (type && object) {
      setPickupPlace(type, object, true);
    } else {
      if (window.orderProps && window.orderProps['STORE_ID']) {
        $('#ORDER_PROP_' + window.orderProps['STORE_ID']).val('');
      }
    }
  }
}

function setPickupPlace(type, object, _hidePopup) {
  if (!object) return false;
  if (!_hidePopup) _hidePopup = false;

  var storeId = type == 'pvz' ? object['CODE'] : object['ID'];
  var address = object['ADDRESS'];
  var message = type == 'pvz' ? 'Забрать в пункте выдачи: ' : 'Получить в магазине: ';
  var deliveryPlaceSelect = $('#deliveryPlaceSelect');

  /** DigitalDataLayer event 'Selected Pickup Point' */
  if (typeof window.digitalData.events !== 'undefined') {
    window.digitalData.cart.shopName = address;
    window.digitalData.events.push({
      "category" : "Ecommerce",
      "name"     : "Selected Pickup Point",
      "cart"     : window.digitalData.cart
    });
  }
  /** DigitalDataLayer event */

  $('#ORDER_PROP_' + window.orderProps['STORE_ID']).val(storeId);
  $('#ORDER_PROP_' + window.orderProps['F_ADDRESS']).val(address);
  $('.js-cart-address').html(address);

  $('.delivery-pvz-list-item').removeClass('active');
  $('.delivery-pvz-list-item[data-' + type + '="' + storeId + '"]').addClass('active');

  checkOmni();

  // Проверка на возможность оплаты в данном ПВЗ наличными деньгами
  $('.delivery-place-select .delivery-pay-warning').toggleClass('hidden', !(type == 'pvz' && getPaymentType() == 'cash' && object['PROPERTIES']['FORBIDDEN_CASH'] == 'Y'));

  var popupText = message + ' <b>' + address + '</b>';

  if ('PROPERTIES' in object) {
    popupText += '<p>' + object['PROPERTIES']['HOW_TO_GET'] + '</p>';
  }

  // кнопка 'Ok' после текста
  popupText += '<span style="display: inline-block; width: 100%; text-align: center;"><a href="#" class="btn btn-default btn-popup-pickup-place-ok" style="display: inline-block; width: 100px; margin-top: 10px;">Ok</a></span>';

  $(document).off('click', '.btn-popup-pickup-place-ok').on('click', '.btn-popup-pickup-place-ok', function(event) {
    event.preventDefault();
    $(this).closest('.modal').find('.close').trigger('click');
  });

  if (!_hidePopup) orderPopup('Выбран способ доставки', popupText);

  deliveryPlaceSelect.text(message + address).parent().show();

  $('.delivery-block.active').hide();
}

function initMap(id) {
  ymaps.ready(function() {
    var mapZoom = 10;
    $('#' + id).html('');
    var pvz = window.pvz;
    var shops = window.shops;
    var pvzCenter = false;
    var shopCenter = false;

    var baloonOpts = {
      iconLayout      : 'default#image',
      iconImageHref   : window.templatePath + '/images/3.png',
      iconImageSize   : [40, 51],
      iconImageOffset : [-5, -38]
    };

    var type = (id == 'pvzmap') ? 'pvz' : 'shop';

    if (type == 'pvz') {
      // соберем точки
      var pvzObjects = [];
      var n = 0;
      for (var ip in pvz) {
        var pvzOne = pvz[ip];
        pvzOne['PROPERTIES']['GEO_LAT'] = parseFloat(pvzOne['PROPERTIES']['GEO_LAT']);
        pvzOne['PROPERTIES']['GEO_LON'] = parseFloat(pvzOne['PROPERTIES']['GEO_LON']);
        if (!pvzCenter) pvzCenter = [pvzOne['PROPERTIES']['GEO_LAT'], pvzOne['PROPERTIES']['GEO_LON']];

        pvzObjects[n] = getPlacemark(type, pvzOne, baloonOpts);
        n++;
      }

      // карта
      var pvzMap = new ymaps.Map(id, {
        center   : pvzCenter,
        zoom     : mapZoom,
        controls : []
      }, {
        searchControlProvider : 'yandex#search'
      });

      // кластер
      var clustererPVZ = new ymaps.Clusterer({clusterDisableClickZoom : false});
      clustererPVZ.add(pvzObjects);
      pvzMap.geoObjects.add(clustererPVZ);

      pvzMap.controls.add(new ymaps.control.ZoomControl({options : {size : "small"}}));
    }

    if (type == 'shop') {
      var shopObjects = [];

      n = 0;
      for (var is in shops) {
        var shopOne = shops[is];
        shopOne['GPS_N'] = parseFloat(shopOne['GPS_N']);
        shopOne['GPS_S'] = parseFloat(shopOne['GPS_S']);
        if (!shopCenter) shopCenter = [shopOne['GPS_N'], shopOne['GPS_S']];

        shopObjects[n] = getPlacemark(type, shopOne, baloonOpts);
        n++;
      }

      var shopMap = new ymaps.Map(id, {
        center   : shopCenter,
        zoom     : mapZoom,
        controls : []
      }, {
        searchControlProvider : 'yandex#search'
      });

      var clusterer = new ymaps.Clusterer({clusterDisableClickZoom : false});
      clusterer.add(shopObjects);
      shopMap.geoObjects.add(clusterer);

      shopMap.controls.add(new ymaps.control.ZoomControl({options : {size : "small"}}));
    }
  });
}

function getPlacemark(type, one, options) {
  if (type == 'pvz') {
    var obj = {'hintContent' : one['ADDRESS']};

    var place = new ymaps.Placemark([one['PROPERTIES']['GEO_LAT'], one['PROPERTIES']['GEO_LON']], obj, options);
    place.events.add('click', function() {
      var clickBlock = {
        'id'      : one['CODE'],
        'name'    : one['ADDRESS'],
        'metro'   : one['PROPERTIES']['METRO'],
        'time'    : '',
        'address' : one['PROPERTIES']['ADDRESS'],
        'phone'   : one['PROPERTIES']['PHONE'],
        'how'     : one['PROPERTIES']['HOW_TO_GET'],
        'cash'    : one['PROPERTIES']['FORBIDDEN_CASH']
      };
      setPlace(type, clickBlock);
    });
  }

  if (type == 'shop') {
    var obj = {'hintContent' : one['TITLE']};
    var place = new ymaps.Placemark([one['GPS_N'], one['GPS_S']], obj, options);
    place.events.add('click', function() {
      var clickBlock = {
        'id'      : one['ID'],
        'name'    : one['TITLE'],
        'metro'   : '',
        'time'    : one['SCHEDULE'],
        'address' : one['ADDRESS'],
        'phone'   : one['PHONE'],
        'how'     : '',
      };
      setPlace(type, clickBlock);
    });
  }

  return place;
}

function setPlace(type, arData) {
  var id = (type == 'pvz') ? '#pvzside' : '#shopside';
  var $block = $(id);
  var $name = $block.find('.delivery-pvz-side-title');
  var $phone = $block.find('.delivery-pvz-side-phone');
  var $metro = $block.find('.delivery-pvz-side-metro');
  var $time = $block.find('.delivery-pvz-side-time-text');
  var $how = $block.find('.delivery-pvz-side-how-text');
  var $address = $block.find('.delivery-pvz-side-address');
  var $button = $block.find('.b-order-basket__main-contacts-text-form-btn');

  $name.html(arData.name);
  $phone.html(arData.phone);

  if (arData.metro) {
    $metro.removeClass('hidden').html(arData.metro);
  } else {
    $metro.addClass('hidden').html('');
  }

  if (arData.time) {
    $time.parent().removeClass('hidden');
    $time.html(arData.time);
  } else {
    $time.parent().addClass('hidden');
    $time.html('');
  }

  if (!arData.how) arData.how = arData.address;
  if (arData.how) {
    $how.parent().removeClass('hidden');
    $how.html(arData.how);
  } else {
    $how.parent().addClass('hidden');
    $how.html('');
  }

  if (arData.address) {
    $address.removeClass('hidden').html(arData.address);
  } else {
    $address.addClass('hidden').html('');
  }

  $button.data('id', arData['id']);
  $button.data('type', type);

  // Проверка на возможность оплаты в данном ПВЗ наличными деньгами
  $block.find('.delivery-pay-warning').toggleClass('hidden', !(getPaymentType() == 'cash' && arData['cash'] == 'Y'));

  $block.show();
}

// смена системы оплаты чекбоксом
function initOnlinePayDiscountCheckbox() {
  $('#onlineDiscountCheckbox').on('change', function() {
    if ($(this).prop('checked')) {
      $('#ID_PAY_SYSTEM_ID_' + window.paySystemId['online']).trigger('click');
    } else {
      $('#ID_PAY_SYSTEM_ID_' + window.paySystemId['cash']).trigger('click');
    }
  });
}

// показать/скрыть информацию о скидке онлайн
function showOnlinePayDiscountInfo() {
  var deliveryType = getDeliveryType();
  // если самовывоз из магазина, то скрыть
  if (deliveryType == 'pickup') {
    $('.b-order__props-payment span.online-pay-discount-label').hide();
    $('.b-order__info-total .checkout__total_discount-info').hide();
  } else {
    $('.b-order__props-payment span.online-pay-discount-label').show();
    $('.b-order__info-total .checkout__total_discount-info').show();
  }
}

//проверка подключились ли все нужные плагины
function checkPlugins() {
  if (!$.isFunction($.fn.validate)) {
    console.log('validate plugin error');
    $.getScript('/local/blocks/i-validate/i-validate.min.js');
  }

  if (!$.isFunction($.fn.mask)) {
    console.log('mask plugin error');
    $.getScript('/local/blocks/i-mask/i-mask.min.js');
  }

  if (!$.isFunction($.fn.validate)) {
    console.log('plugins error');
    location.href = location.pathname; // reload page to load plugins
  }
}
