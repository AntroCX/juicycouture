$(function() {
  var $city = $('.b-city-popup__city'), $btn = $('.b-city-popup__search-btn'), $modal = $('.b-city-popup');

  if (BX.getCookie('sendCityDDL') == 'Y') {
    sendCityDDL();
    BX.setCookie('sendCityDDL', false, {
      expires : 86400,
      path    : '/'
    });
  }

  $city.on('click', function() {
    $(this).addClass('active');
    $city.not($(this)).removeClass('active');
    sendCity($(this).data('id'), $(this).text());
  });

  $btn.on('click', function() {
    sendCity();
  });

  function sendCity(_city_id, _city) {
    var $form = $('.b-city-popup__search'), $validator = $form.validate();
    if ($modal.find('.bx-ui-sls-route').val()) {
      var city = (_city) ? _city : $('.b-city-popup__search .bx-ui-sls-fake').val();
      var city_id = (_city_id) ? _city_id : $form.find('input[name=LOCATION]').val();

      $.cookie('city_name', city, {
        expires : 365,
        path    : '/'
      });
      $.cookie('city_id', city_id, {
        expires : 365,
        path    : '/'
      });
      $.cookie('city_user', 'Y', {
        expires : 365,
        path    : '/'
      });
      $('.b-header__top-location-city').text(city);
      $modal.modal('hide');

      // если изменился город, очищаем поле адреса
      if($('[name="ORDER_PROP_5"]').val() != city_id){
        $('[name="courierStreet"]').val('');
        $('[name="courierHouse"]').val('');
        $('[name="courierApps"]').val('');
      }

      if (location.pathname != '/order/') {
        BX.setCookie('sendCityDDL', 'Y', {
          expires : 86400,
          path    : '/'
        });

        location.href = location.pathname;
      } else {
        // обновляем блок "Способ получения"
        //var $active_el = $('.b-order__props-delivery').find('label.active');
        //if ($active_el.length === 1) $active_el.trigger("click");

        //sendCityDDL();
      }

    } else {
      $validator.showErrors({LOCATION : "Нужно выбрать город из списка"});
    }
  }

  $modal.find('.bx-ui-sls-fake').on('keyup', function() {
    if ($validator) {
      $validator.resetForm();
    }
  })
});

function sendCityDDL() {
  if (typeof window.digitalData.events !== 'undefined') {
    window.digitalData.events.push({
      category : "Auth",
      name     : "Selected City"
    });
  }
}