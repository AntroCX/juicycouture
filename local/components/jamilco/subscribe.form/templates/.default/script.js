$(function () {
    $.fn.owOpenModal = function () {
        var openBlockId = $(this).data("block"),
            openBlock = $("#" + openBlockId);
        if (openBlock.length > 0) {
            var addClasses = openBlock.data("class");
            openBlock.addClass("show");
            if (addClasses !== undefined)
                openBlock.addClass(addClasses);

            openBlock.owCloseModal();
            $("body").addClass('freeze');
        }
    };

    $.fn.owCloseModal = function () {

        var openedBlock = $(this);

        openedBlock.on("click", ".js-popup-close", function (e) {
            openedBlock.removeClass('show ' + openedBlock.data("class"));
            $("body").removeClass('freeze');
            e.preventDefault();
        });
    };
    openSubscribeCouponPopup();
    sendSubscribeCouponForm();
});

function openSubscribeCouponPopup() {
  $('.subscribe-block a.btn').on('click', function() {
    $(this).owOpenModal();
    return false;
  });
  $('.js-subscribe-block_btn').on('click', function() {
      $(this).owOpenModal();
      return false;
  });
}

function sendSubscribeCouponForm() {
  var _check = $('#popup-oferta');
  _check.on('change', function() {
    if ($(this).prop('checked') == true) $(this).parent().find('label').removeClass('error');
  });

  var _form = $('.popup-subscribe-coupon');
  _form.find('a.btn').on('click', function() {
    $(this).closest('form').submit();
    return false;
  });

  $.validator.setDefaults({
    errorClass   : 'error',
    errorElement : 'div'
  });
  var validator = _form.validate({
    rules         : {
      "email" : {
        required : true,
        email    : true
      }
    },
    messages      : {
      "email" : {
        required : "Введите e-mail",
        email    : "Неверно введен e-mail"
      }
    },
    submitHandler : function() {
      _check.parent().find('label').removeClass('error');
      if (_check.prop('checked') != true) {
        _check.parent().find('label').addClass('error');
      } else {
        var _ajax = _form.data('ajax');
        if (_ajax) _ajax.abort();
        _ajax = $.post('/local/ajax/subscr_coupon.php', _form.serialize(), function(_e) {
          if (_e['RESULT'] == 'ok') {
            _form.closest('.popup__box').find('.popup-step1').addClass('none');
            _form.closest('.popup__box').find('.popup-step2').removeClass('none');
          } else {
            if ($('.popup-email .error').length <= 0) $('.popup-email').append('<div id="email-error" class="error" style="display: none;"></div>');
            $('.popup-email .error').text(_e['MESSAGE']).css({display : 'block'});
          }
        }, 'json');
        _form.data('ajax', _ajax);
      }
      return false;
    }
  });
}