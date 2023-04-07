window.bonusOrderAjaxUrl = '/local/modules/jamilco.loyalty/admin/bonus-order/bonus-order.php';

$(function() {
  initBonusOrder();
  initCouponOrder();
  initManzanaOrder();
});

function initManzanaOrder() {
  var _block = $('#bonusOrder');

  // "Пересоздать заказ"
  _block.find('#reCreateInManzana').on('click', function() {

    BX.showWait('bonusOrder');

    $.post(window.bonusOrderAjaxUrl, {
      action : 'reCreateInManzana',
      order  : window.orderId,
      step   : 1,
    }, function(res) {

      $.post(window.bonusOrderAjaxUrl, {
        action : 'reCreateInManzana',
        order  : window.orderId,
        step   : 2,
      }, function(res) {
        location.reload();
      }, 'json');

    }, 'json');

    return false;
  });
}

function initCouponOrder() {
  var _block = $('#bonusOrder');
  var _couponInput = $('#couponManzana');
  var _buttons = _couponInput.closest('tr').find('td.adm-detail-content-cell-r div');

  if (_couponInput.val() > '') {
    _couponInput.prop('disabled', true);

    _buttons.append('<span id="changeCoupon" class="adm-btn">Изменить</span>');
    _buttons.find('#addCoupon').hide();
    _buttons.find('#changeCoupon').on('click', function() {
      $(this).remove();
      _buttons.find('#addCoupon').show();
      _couponInput.prop('disabled', false);
    });
  }

  // поле ввода купона, "Применить"
  _block.find('#addCoupon').on('click', function() {
    var _coupon = $('#couponManzana').val();
    _coupon = $.trim(_coupon);

    if (!_coupon) return false;

    var _ajax = _block.data('ajax');
    if (_ajax) _ajax.abort();

    BX.showWait('bonusOrder');

    _ajax = $.post(window.bonusOrderAjaxUrl, {
      action : 'addCoupon',
      order  : window.orderId,
      coupon : _coupon
    }, function(res) {
      //BX.closeWait();
      location.reload();
    }, 'json');

    _block.data('ajax', _ajax);

    return false;
  });
}

function initBonusOrder() {
  var _block = $('#bonusOrder');
  var _table = _block.find('table').first();

  console.log(window.orderProps);

  // добавить карту в заказ
  $(document).on('click', '.addCard', function() {

    var _card = $(this).data('card');
    var _ajax = _block.data('ajax');
    if (_ajax) _ajax.abort();

    BX.showWait('bonusOrder');

    _ajax = $.post(window.bonusOrderAjaxUrl, {
      action : 'addCard',
      order  : window.orderId,
      card   : _card
    }, function(e) {
      location.reload();
    });

    _block.data('ajax', _ajax);

    return false;
  });

  // списать бонусы
  _block.find('.useCard').on('click', function() {
    var _card = $(this).data('card');
    $('#cardNumber').val(_card);
    $('#addCard').click();

    return false;
  });

  // поле ввода карты, "Применить"
  _block.find('#addCard').on('click', function() {
    var _card = $('#cardNumber').val();
    var _ajax = _block.data('ajax');
    if (_ajax) _ajax.abort();

    BX.showWait('bonusOrder');

    _ajax = $.post(window.bonusOrderAjaxUrl, {
      action : 'checkCard',
      order  : window.orderId,
      card   : _card
    }, function(res) {
      BX.closeWait();

      $('.card-result').text(res['MESSAGE']);
      $('.card-data').toggleClass('none', (res['RESULT'] != 'OK'));
      if (res['RESULT'] == 'OK') {
        var _cardData = res['CARD'];
        window.checkedCard = _cardData['CARD'];

        $('.card-email').text(_cardData['EMAIL']);
        $('.card-phone').text(_cardData['PHONE']);
        $('.card-bonus').text(parseInt(_cardData['BALANCE']['AVAILABLE']));
        var _canPayCount = (_cardData['BALANCE']['WRITEOFF_BONUS'] > window.orderProps['BONUS_TO_ORDER']) ? window.orderProps['BONUS_TO_ORDER'] : _cardData['BALANCE']['WRITEOFF_BONUS'];
        $('.card-bonus-pay').text(parseInt(_canPayCount));

        $('.send-card').data('card', _card).removeClass('none');
        if (_canPayCount > 0) {
          $('.send-email').toggleClass('none', !_cardData['EMAIL']);
          $('.send-phone').toggleClass('none', !_cardData['PHONE']);
        }
      }
    }, 'json');

    _block.data('ajax', _ajax);

    return false;
  });

  // ввод новой карты сбрасывает все открытые интерфейсы
  _block.find('input[name="card"]').on('keyup blur', function() {
    $('.card-data').addClass('none');
    $('.card-code-data').addClass('none');
    $('.send-email').addClass('none');
    $('.send-phone').addClass('none');
  });

  // ввод только цифр
  _block.find('input[name="card"], input[name="card-code"]').on('keyup blur', function() {
    var _val = $(this).val();
    _val = _val.replace(/([^0-9]+)/g, '');
    $(this).val(_val);
  });

  // отправка проверочного кода
  _block.find('.send-code').on('click', function() {

    var _type = $(this).data('type');
    var _ajax = _block.data('ajax');
    if (_ajax) _ajax.abort();

    BX.showWait('bonusOrder');

    _ajax = $.post(window.bonusOrderAjaxUrl, {
      action : 'sendCode',
      order  : window.orderId,
      card   : window.checkedCard,
      type   : _type
    }, function(e) {
      $('.card-code-data').removeClass('none');
      BX.closeWait();
    });

    _block.data('ajax', _ajax);


    return false;
  });

  // проверка введенного кода
  _block.find('#checkCode').on('click', function() {

    var _ajax = _block.data('ajax');
    if (_ajax) _ajax.abort();

    BX.showWait('bonusOrder');

    _ajax = $.post(window.bonusOrderAjaxUrl, {
      action : 'checkCode',
      order  : window.orderId,
      card   : window.checkedCard,
      check  : $('#cardCheckCode').val()
    }, function(res) {
      $('.card-code-result').text(res['MESSAGE']);
      if (res['RESULT'] == 'OK') location.reload();

      BX.closeWait();
    }, 'json');

    _block.data('ajax', _ajax);


    return false;
  });
}

function markCityAsNotCash() {
  if (window.cityMarked == true) return false;
  $('.adm-container-draggable[data-id="buyer"]').find('.sale-order-props-group').find('.adm-detail-content-cell-l').each(function() {
    var _inner = $(this).text();
    if (_inner == 'Город:') {
      $(this).next().append('<span style="color:red; font-size:12px; font-weight: bold;;">Доставка в данный н/п по предоплате</span>');
      window.cityMarked = true;
      return false;
    }
  });
}

// custom bitrix loader
BX.showWait = function(node, msg) {
  $('.bx-core-waitwindow').remove(); // delete all previos waitinings

  node = BX(node) || document.body || document.documentElement;
  msg = msg || BX.message('JS_CORE_LOADING');

  var container_id = node.id || Math.random();

  var obMsg = node.bxmsg = document.body.appendChild(BX.create('DIV', {
    props : {
      id        : 'wait_' + container_id,
      className : 'bx-core-waitwindow'
    },
    text  : msg
  }));

  setTimeout(BX.delegate(_adjustWait, node), 10);

  return obMsg;
};

BX.closeWait = function() {
  $('.bx-core-waitwindow').remove();
};

function _adjustWait() {
  if (!this.bxmsg) return;

  var arContainerPos = BX.pos(this), div_top = arContainerPos.top;
  if (div_top < BX.GetDocElement().scrollTop) div_top = BX.GetDocElement().scrollTop + 5;

  this.bxmsg.style.top = (div_top + 5) + 'px';

  if (this == BX.GetDocElement()) {
    this.bxmsg.style.right = '5px';
  } else {
    this.bxmsg.style.left = (arContainerPos.right - this.bxmsg.offsetWidth - 5) + 'px';
  }
}