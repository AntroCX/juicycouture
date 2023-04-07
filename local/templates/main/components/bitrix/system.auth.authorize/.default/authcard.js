$(function() {
  window.cardNumber = false;

  $('#authCardLink').on('click', function() {
    $('#authCard').modal('show');
    return false;
  });

  $('.input--digit').on('keyup blur', function() {
    var _val = $(this).val();
    _val = _val.replace(/[^0-9]/ig, '');
    $(this).val(_val);
  });

  var _modal = $('#authCard');
  var _formAuth = $('#authCardForm');
  _formAuth.validate({
    rules         : {
      cardNumber : {
        required    : true,
        rangelength : [8, 13]
      },
      pinCode    : {
        required    : true,
        rangelength : [4, 10]
      }
    },
    submitHandler : function() {

      window.cardNumber = $.trim($('#cardNumber').val());

      BX.showWait();
      $.post('/local/ajax/authcard.php', _formAuth.serialize(), function(_e) {
        BX.closeWait();

        if (_e['RELOAD'] == 'Y') {
          //showMessage('Вы успешно авторизованы!', 'Страница будет перезагружена', true);
          if(_e['ddlEvents']){
              for(let i in _e['ddlEvents']){
                eval(_e['ddlEvents'][i]);
              }
          }
          location.href = location.href;
        } else if (_e['RESULT'] == 'ERROR' && _e['FIELD'] == 'CONTACT') {
          _modal.find('.auth-blocks').addClass('hidden');
          _modal.find('.third-block').removeClass('hidden');
        } else if (_e['RESULT'] == 'NOT_EMAIL') {
          _modal.find('.auth-blocks').addClass('hidden');
          _modal.find('.second-block').removeClass('hidden');
        } else if (_e['RESULT'] == 'ERROR') {
          var _errorField = false;
          var _errorMessage = false;
          if (_e['FIELD'] == 'PINCODE') {
            _errorField = 'pinCode';
            _errorMessage = 'Пин-код неверен';
          } else {
            _errorField = 'cardNumber';
            _errorMessage = 'Карта не найдена';
          }

          showValidateError(_errorField, _errorMessage);
        }

      }, 'json');

      return false;
    }
  });

  $('#authCardSubmit').on('click', function() {
    _formAuth.submit();
    return false;
  });

  var _formEmail = $('#authCardEmail');
  _formEmail.validate({
    rules         : {
      cardEmail     : {
        required   : true,
        mailFormat : true
      },
      cardEmailPass : {
        minlength : 6
      }
    },
    submitHandler : function() {
      if (!window.cardNumber) return false;

      var _params = _formEmail.serialize();
      _params += '&cardNumber=' + window.cardNumber;
      BX.showWait();
      $.post('/local/ajax/authcard.php', _params, function(_e) {
        BX.closeWait();

        if (_e['RELOAD']) {
          //showMessage('Вы успешно авторизованы!', 'Страница будет перезагружена', true);
          location.href = location.href;
        } else {
          if (_e['FIELD'] == 'EXIST') {
            $('#cardEmailPassBlock').removeClass('hidden');
          } else if (_e['FIELD'] == 'PASS') {
            showValidateError('cardEmailPass', 'Пароль неверен');
          }
        }

      }, 'json');

      return false;
    }
  });

  $('#cardEmail').on('keyup blur change', function() {
    $('#cardEmailPassBlock').addClass('hidden');
    $('#cardEmailPass').val('');
  });

  $('#authCardEmailSend').on('click', function() {
    _formEmail.submit();
    return false;
  });

  var _formContact = $('#authCardContact');
  _formContact.validate({
    rules         : {
      cardContactEmail : {
        mailFormat : true
      },
      cardContactPhone : {
        phoneFormat : true
      }
    },
    submitHandler : function() {
      if (!window.cardNumber) return false;

      var _params = _formContact.serialize();
      _params += '&cardNumber=' + window.cardNumber;
      BX.showWait();
      $.post('/local/ajax/authcard.php', _params, function(_e) {
        BX.closeWait();

        if (_e['RESULT'] == 'SEND') {
          // высланы коды подтверждения
          _modal.find('.auth-blocks').addClass('hidden');
          _modal.find('.fours-block').removeClass('hidden');

          _modal.find('.fours-block').find('.check-phone').text($('#cardContactPhone').val());
          _modal.find('.fours-block').find('.check-email').text($('#cardContactEmail').val());
        }

      }, 'json');

      return false;
    }
  });

  $('#reSetProps').on('click', function() {
    _modal.find('.auth-blocks').addClass('hidden');
    _modal.find('.third-block').removeClass('hidden');

    return false;
  });

  var _formConfirm = $('#authCardConfirm');
  _formConfirm.validate({
    rules         : {
      cardConfirmEmail : {
        minlength : 5,
        maxlength : 5
      },
      cardContactPhone : {
        minlength : 5,
        maxlength : 5
      }
    },
    submitHandler : function() {
      if (!window.cardNumber) return false;

      var _params = _formConfirm.serialize();
      _params += '&cardNumber=' + window.cardNumber;
      BX.showWait();
      $.post('/local/ajax/authcard.php', _params, function(_e) {
        BX.closeWait();

        if (_e['RESULT'] == 'ERROR') {
          if ($.inArray('phone', _e['ERROR']) != -1) showValidateError('cardConfirmPhone', 'Неверный код подтверждения');
          if ($.inArray('email', _e['ERROR']) != -1) showValidateError('cardConfirmEmail', 'Неверный код подтверждения');
        } else {
          // найден контакт, запустим процесс отправки формы ввода пин-кода заново
          _modal.find('.auth-blocks').addClass('hidden');
          _modal.find('.first-block').removeClass('hidden');
          _formAuth.submit();
        }

      }, 'json');

      return false;
    }
  });
});

function showValidateError(_errorField, _errorMessage) {
  if (!$('#' + _errorField).closest('.form-group').find('label.error-notify').length) {
    $('#' + _errorField).after('<label id="' + _errorField + '-error" class="error-notify" for="' + _errorField + '"></label>');
  }
  var _label = $('#' + _errorField).closest('.form-group').find('label.error-notify');

  _label.css('display', 'inline-block').html(_errorMessage);
}