$(function() {
    $('.b-modal-review-form').validate({
        rules: {
            'totalRating' : {required : true},
            'name' : {required : true},
            'email' : {required : true}
        },
        messages: {
            'totalRating': {
                required : "Нужно поставить оценку для товара"
            },
            'name': {
                required : "Укажите ваше имя"
            },
            'email': {
                required : "Укажите ваш e-mail"
            }
        },
        submitHandler: function() {
            var $form = $('.b-modal-review-form'),
                data = $form.serialize(),
                $loader = $('.b-modal-review__loader');
            $loader.addClass('show');
            $.get('/local/ajax/writeReview.php?'+data)
                .done(function (data) {
                    $loader.addClass('ok');
                    setTimeout(function() {
                        $('.b-modal-review').modal('hide');
                    }, 500)
                    location.reload();
                })
        }
    });
})