$(function() {
  $('.js-to-basket').on('click', function (event) {
    var $this = $(this);
    var href = $this.attr('href') + '?ajax=Y&look=Y';
    event.preventDefault();

    $this.addClass('btn_load');
    $('.look-popup').hide();

    $.get(href).done(function (data) {
       $this.removeClass('btn_load');
       $('.look-popup').html('');
       $this.closest('.item-container').find('.look-popup').show().html(data);
    });

    return false;
  });
  $('body').on('click', '.popup-look-close', function () {
    $(this).closest('.look-popup').html('').hide();
  });
  $('.js-look-slider').slick({
    slidesToShow: 5,
    slidesToScroll: 1,
    responsive: [
      {
        breakpoint: 1200,
        settings: {
          slidesToShow: 4,
          slidesToScroll: 1
        }
      },
      {
        breakpoint: 1024,
        settings: {
          slidesToShow: 3,
          slidesToScroll: 1
        }
      },
      {
        breakpoint: 768,
        settings: {
          slidesToShow: 2,
          slidesToScroll: 1
        }
      },
      {
        breakpoint: 480,
        settings: {
          slidesToShow: 1,
          slidesToScroll: 1
        }
      },
    ],
    autoplay: false,
    prevArrow: '<a class="i-slick__prev"></a>',
    nextArrow: '<a class="i-slick__next"></a>'
  });
});
