/**
 * Created by maxkrasnov on 30.09.16.
 */
$(function () {
    $('.b-auth__reg').validate({
        rules : {
            'REGISTER[NAME]' : {required : true},
            'REGISTER[LAST_NAME]' : {required : true},
            'REGISTER[LOGIN]' : {required : true},
            'REGISTER[PASSWORD]' : {
                required : true,
                minlength : 6
            },
            'REGISTER[CONFIRM_PASSWORD]' : {
                required : true,
                minlength : 6,
                equalTo : "#password"
            },
            'i-agree' : {
                required: true
            }
        },
        messages : {
            'REGISTER[NAME]' : {
                required : "Введите ваше имя"
            },
            'REGISTER[LAST_NAME]' : {
                required: "Введите свою фамилию"
            },
            'REGISTER[LOGIN]' : {
                required: "Введите свой e-mail"
            },
            'REGISTER[PASSWORD]' : {
                required: "Введите пароль"
            },
            'REGISTER[CONFIRM_PASSWORD]' : {
                required: "Введите подтверждене пароля"
            },
            'i-agree' : {
                required: "Необходимо прочесть и согласиться с условиями"
            }
        }
    });
})