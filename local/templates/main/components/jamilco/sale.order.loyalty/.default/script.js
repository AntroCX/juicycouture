$(function() {
  var loyalty = {
    blockSelector         : '.b-loyalty',
    popupSelector         : '.loyalty-popup',
    formSelector          : '.js-loyalty-form',
    cardInputSelector     : '.js-loyalty-card-input',
    confirmInputSelector  : '.js-loyalty-confirm-code',
    reloadContentSelector : '.b-loyalty__content',
    isLoading: false,

    showLoader : function() {
      $(this.blockSelector).addClass('b-loyalty_load');
      BX.showWait();
    },

    hideLoader : function() {
      $(this.blockSelector).removeClass('b-loyalty_load');
      BX.closeWait();
    },

    update : function(params) {
      if (this.isLoading) {
        return false;
      }

      this.isLoading = true;

      var self = this;
      var formData = $(self.formSelector).find(':input').serializeArray();

      self.showLoader();

      $.each(params, function(index, value) {
        formData.push({
          'name'  : index,
          'value' : value
        });
      });

      $.post($(self.formSelector).data('handler') + '/ajax.php', formData).done(function(response, status, xhr) {
          var ct = xhr.getResponseHeader('content-type') || '';
          var type;
          if (ct.indexOf('html') > -1) {
            type = 'html';
          } else if (ct.indexOf('json') > -1) {
            type = 'json';
          }

          if (type == 'html') { // контент в блоке с данными карты
            $(self.reloadContentSelector).html(response);

            if (params.action == 'info') {
              if (typeof submitForm == 'function') {
                submitForm();
                return true;
              }
            }

          } else if (type == 'json') { // запросы на отсылку кода и подтверждения
            var $popup = $(self.popupSelector);

            // успешный ответ
            if (response.success) {
              switch (params.action) {
                // отсылка проверочного кода
                case 'sendCode':
                  $popup.find('*[class^="js-loyalty-confirm-"]').hide();
                  $popup.find('.js-loyalty-code-info').hide();
                  $popup.find('.js-loyalty-code-confirm').show();
                  $popup.find('.js-loyalty-confirm-' + params.type).show();
                  $popup.find('.js-loyalty-confirm-error').hide();
                  break;

                // подтверждение проверочного кода
                case 'confirmCode':
                  var $checkbox = $(self.blockSelector).find('.js-loyalty-popup');
                  var htmlOk = $popup.find('.js-loyalty-confirm-ok').html();
                  $popup.find('.js-loyalty-code-confirm').html(htmlOk);
                  $checkbox.data('confirm', 'Y');
                  $checkbox.prop('checked', true);
                  $('#loyalty-popup').modal('hide');
                  self.isLoading = false;
                  self.update({
                    'action'       : 'applyBonuses',
                    'applyBonuses' : $checkbox.prop('checked') ? 'Y' : 'N'
                  });
                  break;

                case 'applyBonuses':
                  if (typeof submitForm == 'function') submitForm();
                  break;
              }
              // если ошибка
            } else {
              switch (params.action) {
                // подтверждение проверочного кода
                case 'confirmCode':
                  $popup.find('.js-loyalty-confirm-submit').prop('disabled', false);
                  $popup.find('.js-loyalty-confirm-error').show();
                  break;
              }
            }
          }
          self.hideLoader();
        })
        .always(function() {
          self.isLoading = false;
        });
    },

    // init
    initEvents : function() {
      var self = this;
      $('body').on('click', '.js-loyalty-submit', function(e) {
        if (!$(this).hasClass('disable')) {
          var val = $(self.cardInputSelector).val();
          if (val.length === 8 || val.length == 13 || !val.length) {
            if (val.length) {
              $(this).addClass('disable');
            }
            self.update({'action' : 'info'});
          } else {
            $(this).closest('.b-loyalty__content').find('.b-loyalty__content-info').addClass('hidden');
            $('#bonusError').removeClass('hidden').text('Неверный номер карты');
          }
        }
        return false;
      }).on('keyup', self.cardInputSelector, function(e) {
        var _val = $(this).val();
        _val = _val.replace(/([^0-9]+)/g, '');
        $(this).val(_val);
        if (!_val.length) {
          $('#bonusError').addClass('hidden')
        }
      }).on('change', '.js-loyalty-popup', function(e) {
        var $checkbox = $(this);
        var $popup = $(self.popupSelector);

        // если уже была отмечена галочка
        if ($checkbox.prop('checked')) {
          // если карта не подтверждена, тогда показать попап с вариантами отсылки проверочного кода
          if ($checkbox.data('confirm') != 'Y') {
            $checkbox.prop('checked', false);
            $popup.find('.js-loyalty-code-info').show();
            $popup.find('.js-loyalty-code-confirm').hide();
            $($checkbox.data('target')).modal('show');

          // в ином случае, карта уже подтверждена и нужно включить использование бонусов
          } else {
            self.update({
              'action'       : 'applyBonuses',
              'applyBonuses' : 'Y'
            });
          }
        } else {
          self.update({
            'action'       : 'applyBonuses',
            'applyBonuses' : 'N'
          });
        }
      });
    },

    initPopupEvents : function() {
      var self = this;
      $('body').on('click', '.js-loyalty-confirm-email, .js-loyalty-confirm-phone', function(e) {
          self.update({
            'action' : 'sendCode',
            'type'   : $(this).data('confirm')
          });
        }).on('click', '.js-loyalty-confirm-submit', function(e) {
          var code = $(this).siblings(self.confirmInputSelector).val();

          if (code.length > 4) {
            $(self.popupSelector).find('.js-loyalty-confirm-error').hide();
            $(this).prop('disabled', true); // отключение кнопки
            self.update({
              'action' : 'confirmCode',
              'code'   : code
            });
          }
          return false;
        });
    }
  };

  loyalty.initEvents();
  loyalty.initPopupEvents();
});