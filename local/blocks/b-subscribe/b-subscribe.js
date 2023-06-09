$(function () {

    var subscribe = {
        elems: {
            form: '.b-subscribe',
            inputs: '.b-subscribe__inputs-wrapper',
            close: '.b-subscribe__close',
            result: '.b-subscribe__result-html'
        },
        classes: {
            show: 'b-subscribe_show'
        }
    }

    $(subscribe.elems.close).on('click', function () {
        $(subscribe.elems.form).removeClass(subscribe.classes.show);
        $.cookie('subscribe', 'Y', { expires: 7, path: '/' });
    });

    /*
    if($('body').width() > 768) {
        if ($.cookie('subscribe') != 'Y') {
            setTimeout(function () {
                $(subscribe.elems.form).addClass(subscribe.classes.show);
            }, 5000)
        }
    }
    */

    $(subscribe.elems.form).on('submit', function (e) {
        var $form = $(this);
        e.preventDefault();

        if($form.valid()) {
            var data = $form.serialize();

            $(subscribe.elems.inputs).hide();
            $(subscribe.elems.result).show();
            $.get('/local/ajax/subscribe.php?'+data, {sessid: BX.bitrix_sessid()})
                .done(function (html) {
                    $.cookie('subscribe', 'Y', { expires: 730, path: '/' });

                    $(subscribe.elems.result).html(html);

                    setTimeout(function () {
                        $(subscribe.elems.form).removeClass(subscribe.classes.show);
                    }, 3000)

                })

        }
    });



    /* === USER EVENT form === */

    $('body').on('submit', '#js-subscribe', function (e) {
        e.preventDefault();
        sendForm($(this));
    });

    function sendForm(formObj) {

        var $form = formObj,
            action = $form.attr('action'),
            dataForm = $form.serializeArray(),
            //block = $form.closest('.lk-user-block'),
            blockAlert = $form.find('.alert');

        console.log(dataForm);

        $.ajax({
            type: "POST",
            url: action,
            data: dataForm,
            dataType: "json",
            success: function (msg) {
                console.log(msg);
                if ($form.attr('id') == 'js-subscribe') {

                    console.log(msg);
                    console.log(msg['RESULT']);

                    if (msg['RESULT'] == 'Y' || msg['RESULT'] == 'A') {
                        alertForm(blockAlert, 'Y');
                        $form.find('input[name=ACTION]').val('unsubscribe');
                        $form.find('input[name=send]').val('Отменить подписку');
                    } else {
                        alertForm(blockAlert, 'N');
                        $form.find('input[name=ACTION]').val('subscribe');
                        $form.find('input[name=send]').val('Подписаться');
                    }
                } else if ($form.attr('id') == 'js-user-update') {
                    alertForm(blockAlert, msg['RESULT']);
                } else {
                    $('#form-ajax').html(msg['RESULT']);
                }
            }
        });
    }

    function alertForm(blockObj, alert = 'N') {

        var $block = blockObj,
            successText = blockObj.attr('data-success'),
            dangerText = blockObj.attr('data-danger'),
            errorText = blockObj.attr('data-error'),
            closeAlert = '<a href="#" class="alert-close js-alert-close"></a>';

        $block.removeClass();
        $block.html('');
        console.log(alert);
        if (alert == 'Y') {
            $block.addClass('alert alert-success');
            $block.html(successText);
            $block.append(closeAlert);
        } else if (alert == 'E') {
            $block.addClass('alert  alert-error');
            $block.html(errorText);
            $block.append(closeAlert);
        } else if (alert == 'CLOSE') {
            $block.addClass('alert');
        } else {
            $block.addClass('alert alert-danger');
            $block.html(dangerText);
            $block.append(closeAlert);
        }
    }

})