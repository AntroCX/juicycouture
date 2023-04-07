$(function() {
  window.actionUrl = '/local/modules/jamilco.omni/admin/change-delivery/change.php';
  initChangeDeliveryActions();

  initReloadQuantities();
});

function initReloadQuantities() {
  var _btnHtml = '<div class="add-btns">' +
    '<input type="button" class="adm-order-block-add-button" id="reloadOrder" value="Пересчитать заказ">' +
    //'<input type="button" class="adm-order-block-add-button" id="reloadQuantities" value="Обновить остатки">' +
    '</div>';
  $('.adm-container-draggable[data-id="basket"]').find('.adm-bus-component-title-container').append(_btnHtml);

  if (typeof window.storeAmount == 'object') showStoreAmounts();

  $('#reloadQuantities').on('click', function() {
    BX.showWait();
    $.post(window.actionUrl, {
      action : 'reloadQuantities',
      order  : window.orderId
    }, function(e) {

      // получим номер ячейки, в которую нужно положить остаток
      var _table = $('#sale_order_basketsale_order_view_product_table');
      var _n = 0;
      var _stop = false;
      _table.find('thead').find('tr').first().find('td').each(function() {
        if (!_stop) _n++;
        if ($(this).html() == 'Остаток') _stop = true;
      });

      for (var _basketId in e) {
        $('#sale_order_basketsale-order-basket-product-' + _basketId).find('td:eq(' + (_n - 1) + ')').html('<span>' + e[_basketId] + '</span>');
      }

      BX.closeWait();
    }, 'json');
    return false;
  });

  $('#reloadOrder').on('click', function() {
    BX.showWait();
    $.post(window.actionUrl, {
      action : 'reloadOrder',
      order  : window.orderId
    }, function(e) {

      location.href = location.href;

      BX.closeWait();
    }, 'json');
    return false;
  });
}

function initChangeDeliveryActions() {
  // смена местоположения - старт
  $('.changeLocation').on('click', function() {
    $('.change-location').removeClass('none');
  });

  $('.saveLocation').on('click', function() {
    $.post(window.actionUrl, {
      action : 'changeLocation',
      order  : window.orderId,
      id     : $('[name="NEW_LOCATION"]').val()
    }, function(e) {
      location.reload();
    });
  });
  // смена местоположения - финиш

  // смена доставки - старт
  $('.changeDelivery').on('click', function() {
    var _block = $('.change-delivery-block');
    _block.html('');

    var _activeDelivery = $('#omniChannel').data('delivery').split(',');

    $.post(window.actionUrl, {
      action : 'getDelivery',
      order  : window.orderId
    }, function(_e) {
      var _existDelivery = false;
      for (_i in _e.DELIVERY) {
        _existDelivery = true;
        var _one = _e.DELIVERY[_i];
        if (!_one['PERIOD_TEXT'] || typeof _one['PERIOD_TEXT'] == 'undefined') _one['PERIOD_TEXT'] = '';

        var _deliveryActive = ($.inArray(_one['ID'], _activeDelivery) === 0);

        _block.append('\
          <div class="adm-bus-table-container caption border sale-order-props-group one-delivery" id="delivery' + _one['ID'] + '" data-type="' + _one['TYPE'] + '">\
            <div class="adm-bus-table-caption-title">' + _one['OWN_NAME'] + '</div>\
              <table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table">\
                <tr class="">\
                  <td class="adm-detail-content-cell-l" width="40%" valign="top"></td>\
                  <td class="adm-detail-content-cell-r">\
                    <b>' + _one['OWN_NAME'] + '</b>\
                    ' + (_deliveryActive ? '<div style="color:red; font-style: italic;">текущий вариант доставки</div>' : '') + '\
                  </td>\
                </tr>\
                <tr class="">\
                  <td class="adm-detail-content-cell-l" width="40%" valign="top">Стоимость:</td>\
                  <td class="adm-detail-content-cell-r">' + _one['PRICE_FORMATED'] + '</td>\
                </tr>\
                <tr class="' + (_one['PERIOD_TEXT'] ? '' : 'none') + '">\
                  <td class="adm-detail-content-cell-l" width="40%" valign="top">Срок:</td>\
                  <td class="adm-detail-content-cell-r">' + _one['PERIOD_TEXT'] + '</td>\
                </tr>\
              </table>\
            </div>\
          </div>\
          ');
        var _deliveryBlock = _block.find('#delivery' + _one['ID']).find('table');
        if (_one['TYPE'] == 'CURIER') {
          // добавляем адрес доставки
          _deliveryBlock.append('\
          <tr class="">\
            <td class="adm-detail-content-cell-l" width="40%" valign="top">Адрес доставки:</td>\
            <td class="adm-detail-content-cell-r" ><input type="text" name="delivery-address" placeholder="Адрес доставки" class="adm-bus-input"></td>\
          </tr>');
        }

        if (_one['TYPE'] == 'PVZ') {
          // добавим выбор ПВЗ

          var _list = '<option value=""> - выберите ПВЗ - </option>';
          for (_k in _one['LIST']) {
            _list += '<option value="' + _one['LIST'][_k]['CODE'] + '" title="' + _one['LIST'][_k]['NAME'] + '">' + _one['LIST'][_k]['ADDRESS'] + '</option>';
          }

          _deliveryBlock.append('\
          <tr class="">\
            <td class="adm-detail-content-cell-l" width="40%" valign="top">Пункт выдачи:</td>\
            <td class="adm-detail-content-cell-r"><select name="delivery-list" class="adm-bus-select">' + _list + '</select></td>\
          </tr>');
        }

        if (_one['TYPE'] == 'SHOP') {
          // добавим выбор РМ

          var _list = '<option value=""> - выберите РМ - </option>';
          for (_k in _one['LIST']) {
            _list += '<option value="' + _one['LIST'][_k]['ID'] + '" data-nodelivery="' + _one['LIST'][_k]['ITEMS_NO_DELIVERY'] + '">' + _one['LIST'][_k]['TITLE'] + '</option>';
          }

          _deliveryBlock.append('\
          <tr class="">\
            <td class="adm-detail-content-cell-l" width="40%" valign="top">Магазин:</td>\
            <td class="adm-detail-content-cell-r"><select name="delivery-list" class="adm-bus-select">' + _list + '</select></td>\
          </tr>');
        }

        var _nodelivery = '';
        if (_one['TYPE'] != 'SHOP') {
          _nodelivery = _one['ITEMS_NO_DELIVERY'];
        }

        // недоступные товары
        _deliveryBlock.append('\
        <tr class="no-delivery ' + ((_nodelivery > '') ? '' : 'none') + '">\
          <td class="adm-detail-content-cell-l" width="40%" valign="top">Недоступные товары:</td>\
          <td class="adm-detail-content-cell-r">' + _nodelivery + '</td>\
        </tr>');

        // кнопка "Выбрать"
        _deliveryBlock.append('\
        <tr class="">\
          <td class="adm-detail-content-cell-l" width="40%" valign="top"></td>\
          <td class="adm-detail-content-cell-r"><span class="adm-btn saveDelivery">Выбрать</span></td>\
        </tr>');
      }

      if (!_existDelivery) {
        _block.append('\
          <div class="adm-bus-table-container caption border sale-order-props-group one-delivery">\
            <div class="adm-bus-table-caption-title">Нет доступных вариантов доставки</div>\
              <table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table">\
                <tr class="">\
                  <td class="adm-detail-content-cell-l" width="40%" valign="top"></td>\
                  <td class="adm-detail-content-cell-r"><b>Нет доступных вариантов доставки</b></td>\
                </tr>\
              </table>\
            </div>\
          </div>\
          ');
      }

      initChangeDelivery();
    }, 'json');
  });

  // смена доставки - финиш
}

function initChangeDelivery() {

  $('select[name="delivery-list"]').on('change', function() {
    var _block = $(this).closest('.one-delivery');
    var _nodelivery = $(this).find('option:selected').data('nodelivery');
    _block.find('.no-delivery').toggleClass('none', !_nodelivery);
    _block.find('.no-delivery .adm-detail-content-cell-r').html(_nodelivery);
  });

  $('.saveDelivery').on('click', function() {
    var _block = $(this).closest('.one-delivery');
    var _id = _block.attr('id').replace('delivery', '');
    var _type = _block.data('type');

    var _params = {};
    var _error = false;
    if (_type == 'CURIER') {
      _params['address'] = _block.find('input[name="delivery-address"]').val();
      if (!$.trim(_params['address'])) _error = 'Укажите адрес доставки';
    } else {
      _params['list'] = _block.find('select[name="delivery-list"]').val();
      if (!$.trim(_params['list'])) _error = 'Выберите место доставки';
    }

    if (_error) {
      alert(_error);
    } else {
      $.post(window.actionUrl, {
        action : 'changeDelivery',
        order  : window.orderId,
        id     : _id,
        type   : _type,
        params : _params
      }, function(e) {
        location.reload();
      });
    }
  });
}

function saveLocationFromField() {
  $('.change-location-btn').removeClass('none');
}

function showStoreAmounts() {
  var _table = $('#sale_order_basketsale_order_view_product_table');

  if (_table.find('tbody').length > 3) {
    for (_basketId in window.storeAmount['AMOUNT']) {
      $('#sale_order_basketsale-order-basket-product-' + _basketId)
        .find('td:eq(3) span').css('text-align', 'center')
        .html(window.storeAmount['AMOUNT'][_basketId] + '<br />' + window.storeAmount['STORE']['TITLE']);
    }
  } else {
    setTimeout(function() {
      showStoreAmounts();
    }, 300);
  }
}