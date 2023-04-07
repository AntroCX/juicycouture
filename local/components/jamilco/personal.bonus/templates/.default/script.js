$(function() {
  $('.popup-link').click(function() {
    var _popup = $(this).data('target');
    $('#' + _popup).modal('show');

    return false;
  });

  $('.load-check-data').on('click', function() {

    var _id = $(this).data('id');
    var _num = $(this).html();

    $.post('/local/ajax/bonuscard.php', {
      action : 'checkItems',
      id     : _id
    }, function(_data) {

      var _block = $('#historyCheckItemsModal');
      _block.find('.check-num').html(_num);

      var _noPage = false;
      var _list = '';
      for (_i in _data['DATA']) {
        var _one = _data['DATA'][_i];
        _one['OCS_ID'] = parseInt(_one['OCS_ID']);
        if (!_one['OCS_ID']) continue;
        if (!_one['PRODUCT']) {
          _noPage = true;
          continue;
        }
        _list += '\
        <tr>\
            <td><a href="' + _one['PRODUCT']['DETAIL_PAGE_URL'] + '">' + _one['PRODUCT']['NAME'] + '</a></td>\
            <td>' + _one['NAME'] + '</td>\
            <td>' + _one['QUANTITY'] + '</td>\
            <td>' + _one['DISCOUNTED_SUMM'] + '</td>\
            <td>' + _one['PAID_BY_BONUS'] + '</td>\
         </tr>';
      }
      if (_noPage) _list += '<tr><td colspan="4">Отображены не все товары заказа</td></tr>';
      if (!_list) _list += '<tr><td colspan="4">Не удалось загрузить информацию о заказе</td></tr>';

      _block.find('.items-list').html(_list);
      _block.modal('show');

    }, 'json');

    return false;
  });

  initAddCardNumber();
  initDeleteCard();
  initNewCardForm();
  initEditCardForm();
});

function initEditCardForm() {
  var _form = $('#editCardForm');
  _form.validate({
    submitHandler : function(form) {
      BX.showWait();

      $.post('/local/ajax/bonuscard.php', _form.serialize(), function(_e) {
        BX.closeWait();

        if (_e['RESULT'] == 'OK') {
          showMessage('Результат операции', 'Данные по карте успешно отредактированы', true);
        } else {
          alert(_e['MESSAGE']);
        }

      }, 'json');


      return false;
    }
  });
}

function initNewCardForm() {
  var _form = $('#newCardForm');
  _form.validate({
    submitHandler : function(form) {
      BX.showWait();
      $.post('/local/ajax/bonuscard.php', _form.serialize(), function(_e) {
        BX.closeWait();

        if (_e['RESULT'] == 'find') {

          // нашли карту по телефону \ емейлу
          var _firstCard = _e['CARD'][0];
          $('#addCard').find('#cardNumber').val(_firstCard);
          var _mess = 'По введенным контактным данным мы нашли Вашу карту:<div style="text-align: center; font-weight: bold; font-size: 16px; margin: 12px 0 16px;">№' + _firstCard + '</div><br />' + '<a class="btn btn-gold" href="#" onClick="$(\'#addCardLink\').click(); return false;">Привяжите ее к своему аккаунту</a>'
          if (_e['CARD'].length > 1) {
            _mess += '<br /><br />Найдено несколько карт: ' + _e['CARD'].join(', ') + '. Чтобы объединить карты и бонусы на них, ' + window.helpText;
          }
          showMessage('У Вас уже есть карта!', _mess, true);

        } else {

          // карту не нашли, надо проверить телефон + емейл
          $('#newCardForm').addClass('hidden');
          $('#newCardCheck').removeClass('hidden');
          $('#newCardCheck').find('.check-phone').text(_e['PROPS']['PHONE']);
          $('#newCardCheck').find('.check-email').text(_e['PROPS']['EMAIL']);

          $('#newCardCheck #reSetProps').on('click', function() {
            $('#newCardForm').removeClass('hidden');
            $('#newCardCheck').addClass('hidden');

            return false;
          });

          $('#newCardCheckSubmit').off('click');
          $('#newCardCheckSubmit').on('click', function() {
            var _codePhone = $('#newCardCheckPhone').val();
            var _codeEmail = $('#newCardCheckEmail').val();

            var _phoneError = $('#newCardCheckPhone').closest('.form-field--label').find('.label-error');
            var _emailError = $('#newCardCheckEmail').closest('.form-field--label').find('.label-error');

            _phoneError.html('');
            _emailError.html('');

            var _error = false;
            if (_codePhone.length < 5) {
              _phoneError.html('Неверный код подтверждения');
              _error = true;
            }
            if (_codeEmail.length < 5) {
              _emailError.html('Неверный код подтверждения');
              _error = true;
            }

            if (!_error) {
              BX.showWait();
              $.post('/local/ajax/bonuscard.php', {
                action : 'newCardCheck',
                phone  : _codePhone,
                email  : _codeEmail
              }, function(_e) {
                BX.closeWait();

                if (_e['RESULT'] == 'OK') {
                  // все верно, карта создана и привязана
                  showMessage('Карта создана!', 'Ваша карта - <b>№' + _e['CARD'] + '</b>.<br /><br />Страница перезагрузится через 3 секунды.', true);
                  setTimeout(function() {reloadPage();}, 3000);

                } else {
                  if ($.inArray('phone', _e['ERROR']) != -1) _phoneError.html('Неверный код подтверждения');
                  if ($.inArray('email', _e['ERROR']) != -1) _emailError.html('Неверный код подтверждения');
                  if (_e['MESSAGE'] > '') {
                    $('#reSetProps').click();
                    alert(_e['MESSAGE']);
                  }
                }

              }, 'json');
            }

          });

        }
      }, 'json');

      return false;
    }
  });
}

function initDeleteCard() {
  $('#deleteCard').on('click', function() {
    if (confirm("Подтвердите удаление привязанной к аккаунту бонусной карты №" + window.bonusCard)) {
      BX.showWait();
      $.post('/local/ajax/bonuscard.php', {
        action : 'deleteCard',
        card   : window.bonusCard
      }, function() {
        reloadPage();
      });
    }

    return false;
  });
}

function initAddCardNumber() {
  var _block = $('#addCard');

  // в поле номера карты ввод только чисел
  _block.find('#cardNumber').on('keyup blur', function(e) {
    var _val = $(this).val();
    _val = _val.replace(/([^0-9]+)/g, '');
    $(this).val(_val);
  });

  // проверка карты
  $('#addCardSubmit').click(function() {
    var _card = _block.find('#cardNumber').val();
    var _errorLabel = _block.find('.first-block').find('.label-error');
    _errorLabel.html('');

    var _ajax = _block.data('ajax');
    if (_ajax) _ajax.abort();

    BX.showWait();
    _ajax = $.post('/local/ajax/bonuscard.php', {
      action : 'checkCard',
      card   : _card
    }, function(e) {
      BX.closeWait();
      if (e['RESULT'] == 'OK') {
        showCodeCard(_block, _card, e['CARD']);
      } else {
        if (e['SECURE'] == true) {
          _errorLabel.html(window.secureText);
        } else {
          _errorLabel.html('Карта не найдена');
        }
      }
    }, 'json');

    _block.data('ajax', _ajax);

    return false;
  });
}

function showCodeCard(_block, _card, _contact) {
  _block.find('.first-block').addClass('hidden');
  _block.find('.second-block').removeClass('hidden');

  if (_contact['PHONE'] > '') {
    _block.find('.card-phone-if').removeClass('hidden');
    _block.find('.card-phone').text(_contact['PHONE']);
  } else {
    _block.find('.card-phone-if').addClass('hidden');
  }

  if (_contact['EMAIL'] > '') {
    _block.find('.card-email-if').removeClass('hidden');
    _block.find('.card-email').text(_contact['EMAIL']);
  } else {
    _block.find('.card-email-if').addClass('hidden');
  }

  if (_contact['EMAIL'] > '' || _contact['PHONE'] > '') {
    _block.find('.card-contact-no').addClass('hidden');
    _block.find('.card-contact-yes').removeClass('hidden');
  } else {
    _block.find('.card-contact-no').removeClass('hidden');
    _block.find('.card-contact-yes').addClass('hidden');
  }

  _block.find('.card-confirm').off('click');
  _block.find('.card-confirm').on('click', function() {

    var _type = $(this).data('confirm');

    var _ajax = _block.data('ajax');
    if (_ajax) _ajax.abort();

    BX.showWait();
    _ajax = $.post('/local/ajax/bonuscard.php', {
      action : 'codeCard',
      card   : _card,
      type   : _type
    }, function(e) {
      BX.closeWait();
      if (e['RESULT'] == 'OK') {
        showConfirmCard(_block, _card, _type);
      } else {
        //
      }
    }, 'json');

    _block.data('ajax', _ajax);

    return false;
  });
}

function showConfirmCard(_block, _card, _type) {
  _block.find('.second-block').addClass('hidden');
  _block.find('.third-block').removeClass('hidden');

  _block.find('.card-' + _type + '-send').removeClass('hidden');
  var _error = _block.find('.third-block').find('.label-error');

  _block.find('#checkCardSubmit').on('click', function() {
    _error.html('');

    var _code = $('#loyaltyCardCode').val();
    if (_code.length < 5) {
      _error.html('Неверный код подтверждения');
    } else {

      var _ajax = _block.data('ajax');
      if (_ajax) _ajax.abort();

      BX.showWait();
      _ajax = $.post('/local/ajax/bonuscard.php', {
        action : 'confirmCard',
        card   : _card,
        code   : _code
      }, function(e) {
        BX.closeWait();
        if (e['RESULT'] == 'OK') {
          _error.html('<span class="card-success">Владение картой подтверждено.<br />Страница перезагрузится через 3 секунды.</span>');
          $('#checkCardSubmit').addClass('hidden');
          setTimeout(function() {
            reloadPage();
          }, 3000);
        } else {
          _error.html('Неверный код подтверждения');
        }
      }, 'json');

      _block.data('ajax', _ajax);
    }

    return false;
  });
}

function reloadPage() {
  var _url = location.href;
  _url = _url.split('#');
  _url = _url[0];
  location.href = _url;
}