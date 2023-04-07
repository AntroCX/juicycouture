/**
 * Created by maxkrasnov on 29.09.16.
 */
function addParamToURL(url, param, value) {
  (url.indexOf('?') == -1) ? url += '?' : url += '&';
  url += param + '=' + value;
  return url;
}

function changeURL(url) {
  var title = $(document).find("title").text();
  url = url.replace('ajax=Y', '');
  url = url.replace('filter/clear/apply/', '');
  history.pushState('', title, url);
}

function checkConfirm() {
  var _confirmInput = $('#confirmorder');
  var _confirm;

  if (_confirmInput.length > 0) {
    _confirm = _confirmInput.val();
  } else {
    _confirm = 'Y';
  }

  return (_confirm === 'Y');
}

$(function() {
  checkPlugins();

  // глобальные настройки для валидатора
  $.validator.setDefaults({
    errorClass     : 'error-notify',
    errorPlacement : function(error, element) {
      if (element.attr('type') == 'radio') {
        error.insertAfter(element.parents('.form-group'));
      } else if (element.attr('type') == 'checkbox') {
        error.insertAfter(element.parents('.checkbox'));
      } else if (element.hasClass('bx-ui-sls-fake') || element.hasClass('dropdown-field')) {
        error.insertAfter(element.parents('.bx-ui-sls-input-block').next());
      } else {
        error.insertAfter(element);
      }
    }
  });

  $.extend($.validator.messages, {
    required    : "Это поле необходимо заполнить",
    remote      : "Исправьте это поле чтобы продолжить",
    email       : "Введите правильный email адрес.",
    url         : "Введите верный URL.",
    date        : "Введите правильную дату.",
    dateISO     : "Введите правильную дату (ISO).",
    number      : "Введите число.",
    digits      : "Введите только цифры.",
    creditcard  : "Введите правильный номер вашей кредитной карты.",
    equalTo     : "Повторите ввод значения еще раз.",
    accept      : "Пожалуйста, введите значение с правильным расширением.",
    maxlength   : $.validator.format("Не более {0} символов."),
    minlength   : $.validator.format("Минимальное количество символов {0}."),
    rangelength : $.validator.format("Введите значения от {0} до {1}."),
    range       : $.validator.format("Введите значение между {0} и {1}."),
    max         : $.validator.format("Введите значение меньше {0}."),
    min         : $.validator.format("введите значение больше {0}."),
  });


  $.validator.addMethod("phoneFormat", function(value, element) {
    return ((value.indexOf('+7(89') == -1 && value.indexOf('+7 (89') == -1 && value.indexOf('+789') == -1));
  }, 'Проверьте корректность номера телефона');

  $.validator.addMethod("mailFormat", function(value, element) {
    return (!checkConfirm() || /^[a-z-0-9_/.]+@[a-z-0-9]+\.[a-z]{2,3}$/.test(value));
  }, 'Некорректный E-mail');

  $.validator.addMethod("numFormat", function(value, element) {
    return (!checkConfirm() || /^[0-9]+$/.test(value));
  }, 'Некорректный номер');

  $.validator.addMethod("nameFormat", function(value, element) {
    return (!checkConfirm() || /^[a-zA-Zа-яА-Я ]+$/.test(value));
  }, 'Вводите только буквы');

  addDateMask($('.mask-date'));
  maskPhone($('.mask-phone'));
  initTopSearch();
  fillFormFields();

  // логика работы радио звездочек

  $('.btn-star').on('click', function() {
    var $this = $(this), val = $(this).find('input:radio').attr('title');
    $this.prevAll().addClass('selected');
    $this.nextAll().removeClass('selected');
    $this.parent().next('.btn-star__value').html(val);
  });

  /* footer menu - castom */
  $('.b-page__footer-wrapper').on('click', '.b-page__footer-menu-category a', function() {
    var aLink = $(this), ulBlock = aLink.parent().parent(), liBlock = ulBlock.find('li').not('.b-page__footer-menu-category');

    if (aLink.attr('aria-expanded') == 'false') {
      aLink.attr('aria-expanded', true);
      liBlock.show("slow");
    } else {
      aLink.attr('aria-expanded', false);
      liBlock.hide("slow");
    }
  });
});

function checkPlugins() {
  if (!$.isFunction($.fn.validate)) {
    console.log('validate plugin error');
    $.getScript('/bitrix/templates/redesign/js/validate/jquery.validate.min.js');
  }

  if (!$.isFunction($.fn.validate)) {
    console.log('plugins error');
    location.href = location.pathname; // reload page to load plugins
  }
}

function fillFormFields() {

  var _inputs = [
    '#ORDER_PROP_1', '#basket-fastbuy input[name="fio"]', '#reservedInStore input[name="RESERVED_NAME"]',   // name
    '#ORDER_PROP_4', '#basket-fastbuy input[name="phone"]', '#reservedInStore input[name="RESERVED_PHONE"]',  // phone
    '#ORDER_PROP_3', '#basket-fastbuy input[name="email"]', '#reservedInStore input[name="RESERVED_EMAIL"]',   // email
  ];

  var _selectedInputs = _inputs.join(', ');

  $(_selectedInputs).each(function() {
    var _val = $.trim($(this).val());
    $(this).data('prev', _val);
  });

  $(document).on('blur', _selectedInputs, function() {
    var _this = $(this);
    var _val = $.trim(_this.val());
    if (!_val || _val == _this.data('prev')) return false;
    _this.data('prev', _val);

    var _label = 'cart';
    if (_this.closest('#basket-fastbuy').length) _label = '1-step-cart';
    if (_this.closest('#reservedInStore').length) _label = 'reservation';

    var _name = 'Entered Name in Checkout Form';
    if (_this.attr('name') == 'phone' || _this.attr('name') == 'RESERVED_PHONE' || _this.attr('id') == 'ORDER_PROP_4') _name = 'Entered Phone in Checkout Form';
    if (_this.attr('name') == 'email' || _this.attr('name') == 'RESERVED_EMAIL' || _this.attr('id') == 'ORDER_PROP_3') _name = 'Entered Email in Checkout Form';

    if (typeof window.digitalData.events !== 'undefined') {
      setTimeout(function() {
        var _hasFieldError = (_this.closest('div').find('label.error-notify:visible').length > 0) ? true : false;
        if (_this.val().length > 0 && !_hasFieldError) {
          digitalData.events.push({
            category : "Behaviour",
            name     : _name,
            label    : _label
          });
        }
      }, 100, _this);
    }
  });
}

function initTopSearch() {
  var _block = $('.b-header__search-block');
  var _drop = _block.find('.search-dropdown');
  var _input = _block.find('.form-control');

  _input.on('focusout', function() {
    //_drop.html('').hide();
  });

  _input.on('keyup change focus', function() {

    if (_input.val().length >= 3) {
      var _ajax = _block.data('ajax');
      if (_ajax) _ajax.abort();

      _ajax = $.get('/local/ajax/search.php', {q : _input.val()}, function(_e) {
        _drop.html('').hide();
        if (_e.length) {
          for (_i in _e) {
            _drop.append('<a href="' + _e[_i]['LINK'] + '"><span>' + _e[_i]['PRICES']['SALE'] + '</span>' + _e[_i]['NAME'] + ' ' + _e[_i]['ARTICLE'] + '</a>');
          }
          _drop.show();
        }
      }, 'json');

      _block.data('ajax', _ajax);
    }
  });

  $(document).on('mouseup', function(e) {
    if (!_drop.is(e.target) && _drop.has(e.target).length === 0) _drop.html('').hide();
  });
}

var lastWait = [];
/* non-xhr loadings */
BX.showWait = function(node, msg) {
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

  lastWait[lastWait.length] = obMsg;
  return obMsg;
};

BX.closeWait = function(node, obMsg) {
  if (node && !obMsg) obMsg = node.bxmsg;
  if (node && !obMsg && BX.hasClass(node, 'bx-core-waitwindow')) obMsg = node;
  if (node && !obMsg) obMsg = BX('wait_' + node.id);
  if (!obMsg) obMsg = lastWait.pop();

  if (obMsg && obMsg.parentNode) {
    for (var i = 0, len = lastWait.length; i < len; i++) {
      if (obMsg == lastWait[i]) {
        lastWait = BX.util.deleteFromArray(lastWait, i);
        break;
      }
    }

    obMsg.parentNode.removeChild(obMsg);
    if (node) node.bxmsg = null;
    BX.cleanNode(obMsg, true);
  }
};//

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

function scrollToAnchor(aid) {
  var aTag = $("a[name='" + aid + "']");
  $('html,body').animate({scrollTop : aTag.offset().top}, 'slow');
}

function maskPhone(_maskPhone) {
  var isAndroid = navigator.userAgent.toLowerCase().indexOf("android") > -1;
  if (isAndroid) {
    _maskPhone.mask('+79999999999');
  } else {
    _maskPhone.mask('+7(999)999-99-99');
  }
}

function addDateMask(_input) {
  _input.mask('99.99.9999');
  _input.each(function() {
    var _oneInput = $(this);
    checkDateInput(_oneInput);
    _oneInput.on('blur change', function() {
      checkDateInput(_oneInput);
    });
  });
}

function checkDateInput(_input) {
  var _date = _input.val();
  _date = _date.replace(/\//g, '.');
  if (!checkDateNumber(_date)) _input.val('');
}

function checkDateNumber(_date) {
  var dateCheck = /^\d{2}.\d{2}.\d{4}$/i;
  return dateCheck.test(_date);
}

function showMessage(_title, _text) {
  var _popup = $('#messageModal');
  _popup.find('.modal-header h3').html(_title);
  _popup.find('.modal-body').html('<p>' + _text + '</p>');

  _popup.modal('show');
}

$(document).ready(function() {
  /**
   * При прокрутке страницы, показываем или срываем кнопку
   */
  $(window).scroll(function() {
    // Если отступ сверху больше 50px то показываем кнопку "Наверх"
    if ($(this).scrollTop() > 1500) {
      $('#button-up').fadeIn();
    } else {
      $('#button-up').fadeOut();
    }
  });

  /** При нажатии на кнопку мы перемещаемся к началу страницы */
  $('#button-up').click(function() {
    $('body,html').animate({
      scrollTop : 0
    }, 500);
    return false;
  });

});