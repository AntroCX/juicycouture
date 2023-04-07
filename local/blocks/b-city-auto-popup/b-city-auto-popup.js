$(function() {
  var $popup = $('.b-city-auto-popup'), $popupClose = $('.b-city-auto-popup__close');

  // открытие попапа выбора города
  if ($popup.hasClass('b-city-auto-popup_hide')) {
    $popup.removeClass('b-city-auto-popup_hide');
  }

  // нажатие на кнопку закрытия попапа
  $popupClose.on('click', function() {
    $popup.addClass('b-city-auto-popup_hide');
  })

  $(document).click(function(event) {
    if (!$(event.target).is(".b-city-auto-popup")) {
      $popup.addClass('b-city-auto-popup_hide');
    }
  });

  $('.b-city-auto-popup__select-yes').on('click', function() {
    var city = $('.b-city-auto-popup__you-city-name').text();
    var cityId = $('.b-city-auto-popup__you-city-name').data('id');
    $.cookie('city_name', city, {
      expires : 365,
      path    : '/'
    });
    $.cookie('city_id', parseInt(cityId), {
      expires : 365,
      path    : '/'
    });
    $.cookie('city_user', 'Y', {
      expires : 365,
      path    : '/'
    });
  })
})