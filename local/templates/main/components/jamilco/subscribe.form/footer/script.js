$(function() {
  $('#footerSubscribe').validate({
    rules          : {
      EMAIL           : {
        email      : true,
        mailFormat : true,
        required   : true
      },
      SUBSCRIBE_AGREE : {
        required : true,
      },
    },
    messages       : {
      SUBSCRIBE_AGREE : {
        required : "Необходимо прочесть и согласиться с условиями"
      },
      EMAIL           : {
        email      : "Введите ваш e-mail",
        mailFormat : "Введите ваш e-mail",
        required   : "Введите ваш e-mail"
      },
    },
    errorClass     : 'error-notify',
    errorPlacement : function(error, element) {
      if (element.attr('type') == 'radio') {
        error.insertAfter(element.parents('.form-group'));
      } else if (element.attr('type') == 'checkbox') {
        error.insertAfter(element.parents('.b-subscription__form_agree'));
      } else if (element.hasClass('bx-ui-sls-fake') || element.hasClass('dropdown-field')) {
        error.insertAfter(element.parents('.bx-ui-sls-input-block').next());
      } else {
        error.insertAfter(element);
      }
    },
    submitHandler  : function(form) {
      BX.closeWait();
      sendForm(form);
      return false;
    },
    invalidHandler : function(e, validator) {
      BX.closeWait();
    }
  });
});

function sendForm(form) {
  let $form = $(form), action = $form.attr('action'), path = $form.find('input[name="path"]').val(), dataForm = $form.serializeArray();
  console.log(dataForm);

  $.ajax({
    type     : "POST",
    url      : '/local/ajax/subscribe_new.php',
    data     : dataForm,
    dataType : "json",
    success  : function(msg) {
      console.log(msg);
      if (msg['RESULT'] === 'Y' || msg['RESULT'] === 'A') {
        $('.bx_subscribe_response_container__header img').attr('src', path + '/images/icon-ok.png');
        $('.bx_subscribe_response_container__title').text('Поздравляем!');
        $('.bx_subscribe_response_container__msg').text('Вы стали подписчиком');
        /** DigitalDataLayer */
        if (typeof window.digitalData.events !== 'undefined') {
          window.digitalData.events.push({
            'category' : 'Email',
            'name'     : 'Subscribed',
            'label'    : 'Footer subscription',
            'user'     : {
              'email' : dataForm['EMAIL']
            }
          });
        }
      }else{
        $('.bx_subscribe_response_container__header img').attr('src',path + '/images/icon-alert.png');
        $('.bx_subscribe_response_container__title').text('Ошибка!');
        $('.bx_subscribe_response_container__msg').text('Что-то пошло не так');
      }
      // popup
      let oPopup = BX.PopupWindowManager.create('sender_subscribe_component', window.body, {
        autoHide    : true,
        offsetTop   : 1,
        offsetLeft  : 0,
        lightShadow : true,
        closeIcon   : true,
        closeByEsc  : true,
        overlay     : {
          backgroundColor : 'rgba(57,60,67,0.82)',
          opacity         : '80'
        }
      });
      oPopup.setContent(BX('sender-subscribe-response-cont'));
      oPopup.show();
      $form.find('button[type="submit"]').prop("disabled", true);

    }
  });
}