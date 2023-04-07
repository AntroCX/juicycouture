<?php
global $APPLICATION;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Время покупать");

$APPLICATION->AddChainItem('Время покупать', '/landing_bf_2021/');

$realPath = realpath(dirname(__FILE__));
?>

    <div class="page" id="page">
        <main class="pb-0 main">
            <div class="time-to-buy__gif">
                <picture>
                    <source srcset="./assets/images/time-to-buy/01.gif " media="(min-width: 768px)">
                    <source srcset="./assets/images/time-to-buy/01-mob.gif " media="(min-width: 0px)">
                    <img class="lazyload" src="./assets/images/example/load.webp" alt="" width="1" height="1" data-src="./assets/images/time-to-buy/01.gif">
                </picture>
            </div>
            <div class="time-to-buy">
                <div class="time-to-buy__section time-to-buy__section--red">
                    <p class="time-to-buy__text">Поздравляем с наступающими <br> праздниками&nbsp;и&nbsp;ДАРИМ </p>
                    <p class="time-to-buy__text time-to-buy__text--big time-to-buy__text--red">ПОДАРОЧНЫЕ БОНУСЫ </p>
                    <p class="time-to-buy__text time-to-buy__text--red mb-0">ДЛЯ НОВОГОДНЕГО КРОСС-ШОПИНГА</p>
                </div>
                <div class="time-to-buy__section mb-2 mb-lg-5">
                    <p class="time-to-buy__text time-to-buy__text--sm time-to-buy__text--lh mb-0">ИСПОЛЬЗУЙТЕ ПОДАРОЧНЫЕ БОНУСЫ ДЛЯ&nbsp;ПОЛУЧЕНИЯ&nbsp;СКИДКИ</p>
                    <p class="time-to-buy__text time-to-buy__text--percent">-10%</p>
                    <p class="time-to-buy__text time-to-buy__text--sm time-to-buy__text--lh">В МАГАЗИНАХ ПРОГРАММЫ ЛОЯЛЬНОСТИ ВАШЕЙ&nbsp;КЛУБНОЙ&nbsp;КАРТЫ,</p>
                    <p class="time-to-buy__text time-to-buy__text--sm time-to-buy__text--bold">А ТАКЖЕ ДЛЯ УНИКАЛЬНОЙ СКИДКИ <br> ДО&nbsp;&nbsp;<span class="time-to-buy__text--big time-to-buy__text--red time-to-buy__text--span">-15%</span></p>
                    <p class="time-to-buy__text time-to-buy__text--sm time-to-buy__text--bold">В ДРУГИХ БРЕНДАХ КЛУБА JAMILCO</p>
                </div>
            </div>
            <div class="time-to-buy__gif time-to-buy__gif--2">
                <a href="/personal/bonuses/">
                    <picture>
                        <source srcset="./assets/images/time-to-buy/02.gif " media="(min-width: 768px)">
                        <source srcset="./assets/images/time-to-buy/02-mob.gif " media="(min-width: 0px)"><img class="lazyload" src="./assets/images/example/load.webp" alt="" width="1" height="1" data-src="./assets/images/time-to-buy/02.gif">
                    </picture>
                </a>
            </div>
            <div class="time-to-buy">
                <div class="time-to-buy__section time-to-buy__section--solo">
                    <p class="time-to-buy__text time-to-buy__text--solo">Предложение действует в&nbsp;магазинах розничной сети&nbsp;и&nbsp;в&nbsp;интернет-магазинах</p>
                </div>
            </div>
            <div class="time-to-buy">
                <div class="time-to-buy__section mb-0 time-to-buy__section--brands">
                    <div class="time-to-buy__title">Розничные и интернет-магазины, участвующие&nbsp;в&nbsp;акции:</div>
                    <div class="row align-items-center time-to-buy__row">
                        <div class="col-6 col-md-4 pr-0 time-to-buy__brand pr-md-6"><a href="https://timberland.ru/"><img class="lazyload" src="./assets/images/example/load.webp" width="1" height="1" data-src-swap="./assets/images/undefined" data-src="./assets/images/time-to-buy/brands/01.jpg"></a></div>
                        <div class="col-6 col-md-4 time-to-buy__brand px-md-6"><a href="https://wolford.ru/"><img class="lazyload" src="./assets/images/example/load.webp" width="1" height="1" data-src-swap="./assets/images/undefined" data-src="./assets/images/time-to-buy/brands/02.jpg"></a></div>
                        <div class="col-6 col-md-4 pr-0 time-to-buy__brand pr-md-2 pl-md-6"><a href="https://newbalance.ru/"><img class="lazyload" src="./assets/images/example/load.webp" width="1" height="1" data-src-swap="./assets/images/undefined" data-src="./assets/images/time-to-buy/brands/03.jpg"></a></div>
                        <div class="col-6 col-md-4 time-to-buy__brand pr-md-6"><a href="https://elenamiro.ru/"><img class="lazyload" src="./assets/images/example/load.webp" width="1" height="1" data-src-swap="./assets/images/undefined" data-src="./assets/images/time-to-buy/brands/04.jpg"></a></div>
                        <div class="col-6 col-md-4 pr-0 time-to-buy__brand px-md-2"><a href="https://ferragamo.ru/"><img class="lazyload" src="./assets/images/example/load.webp" width="1" height="1" data-src-swap="./assets/images/undefined" data-src="./assets/images/time-to-buy/brands/05.jpg"></a></div>
                        <div class="col-6 col-md-4 time-to-buy__brand pr-md-2 pl-md-6"><a href="https://kikocosmetics.ru/"><img class="lazyload" src="./assets/images/example/load.webp" width="1" height="1" data-src-swap="./assets/images/undefined" data-src="./assets/images/time-to-buy/brands/06.jpg"></a></div>
                        <div class="col-6 col-md-4 pr-0 time-to-buy__brand pr-md-6"><a href="https://dkny.ru/"><img class="lazyload" src="./assets/images/example/load.webp" width="1" height="1" data-src-swap="./assets/images/undefined" data-src="./assets/images/time-to-buy/brands/07.jpg"></a></div>
                        <div class="col-6 col-md-4 time-to-buy__brand px-md-6"><a href="https://vilebrequin.ru/"><img class="lazyload" src="./assets/images/example/load.webp" width="1" height="1" data-src-swap="./assets/images/undefined" data-src="./assets/images/time-to-buy/brands/08.jpg"></a></div>
                        <div class="col-6 col-md-4 pr-0 time-to-buy__brand pr-md-2 pl-md-6"><a href="https://stjames.ru/"><img class="lazyload" src="./assets/images/example/load.webp" width="1" height="1" data-src-swap="./assets/images/undefined" data-src="./assets/images/time-to-buy/brands/09.jpg"></a></div>
                        <div class="col-6 col-md-4 time-to-buy__brand pr-md-6 mb-md-0"><a href="https://juicycouture.ru/"><img class="lazyload" src="./assets/images/example/load.webp" width="1" height="1" data-src-swap="./assets/images/undefined" data-src="./assets/images/time-to-buy/brands/10.jpg"></a></div>
                        <div class="col-6 col-md-4 pr-0 time-to-buy__brand px-md-6 mb-0"><a href="https://lee.ru.com/"><img class="lazyload" src="./assets/images/example/load.webp" width="1" height="1" data-src-swap="./assets/images/undefined" data-src="./assets/images/time-to-buy/brands/11.jpg"></a></div>
                        <div class="col-6 col-md-4 time-to-buy__brand pr-md-2 pl-md-6 mb-0"><a href="https://wrangler.ru/"><img class="lazyload" src="./assets/images/example/load.webp" width="1" height="1" data-src-swap="./assets/images/undefined" data-src="./assets/images/time-to-buy/brands/12.jpg"></a></div>
                    </div>
                </div>
            </div>
            <div class="time-to-buy">
                <div class="time-to-buy__section--shops">
                    <div class="time-to-buy__title time-to-buy__title--2"> Розничные магазины:</div>
                    <div class="row align-items-center time-to-buy__row justify-content-md-center">
                        <div class="col-6 time-to-buy__brand--shop time-to-buy__brand col-md-4">
                            <a href="https://stjames.ru/brands_new/john_lobb/">
                            <img class="lazyload" src="./assets/images/example/load.webp" width="1" height="1" data-src-swap="./assets/images/undefined" data-src="./assets/images/time-to-buy/shops/01.jpg">
                            </a>
                        </div>
                        <div class="col-6 time-to-buy__brand--shop time-to-buy__brand col-md-4 pl-md-0">
                            <a href="https://jamilco.ru/brands/escada/">
                            <img class="lazyload" src="./assets/images/example/load.webp" width="1" height="1" data-src-swap="./assets/images/undefined" data-src="./assets/images/time-to-buy/shops/02.jpg">
                            </a>
                        </div>
                    </div><a class="time-to-buy__btn" href="https://jamilco.ru/ny2022/shops/">УЗНАТЬ АДРЕСА ВСЕХ МАГАЗИНОВ</a>
                </div>
            </div>
            <div class="time-to-buy time-to-buy--rules">
                <div class="time-to-buy__title time-to-buy__title--rules">Условия Акции НОВОГОДНИЙ КРОСС-ШОПИНГ</div>
                <ul class="time-to-buy__ul">
                    <li class="time-to-buy__li">Акция проводится с&nbsp;26&nbsp;ноября&nbsp;по&nbsp;31&nbsp;декабря 2021 года</li>
                    <li class="time-to-buy__li">
                        В&nbsp;Акции участвуют карты следующих Программ Лояльности (Клубы): JAMILCO, New Balance,  Timberland, Lee &amp;&nbsp;Wrangler, DKNY, Juicy Couture, KIKO KISSES, St-James, Wolford</li>
                    <li class="time-to-buy__li">
                        Подарочные бонусы в&nbsp;сумме от&nbsp;22&nbsp;000 до&nbsp;100&nbsp;000 начисляются на&nbsp;бонусные карты, имеющие 	 статус &laquo;Активная&raquo; на&nbsp;дату 25&nbsp;ноября 2021&nbsp;года. Сумма подарочных бонусов зависит от&nbsp;бренда 	 Программы и&nbsp;Уровня участия карты. Клиент-участник Программы лояльности получает 	 	 информацию о&nbsp;начисленных баллах в&nbsp;коммуникациях по&nbsp;сетям связи (в&nbsp;случае согласия на&nbsp;коммуникации), через Личный кабинет на&nbsp;сайтах соответствующих брендов, а&nbsp;также
                        при личном визите в&nbsp;любой из&nbsp;магазинов-участников Акции.
                    </li>
                    <li class="time-to-buy__li">
                        При совершении покупки для получения бонусной скидки используются любые бонусы, находящиеся на&nbsp;балансе Клубной карты&nbsp;&mdash; как подарочные бонусы, так и&nbsp;бонусы, накопленные ранее при совершении покупок.</li>
                    <li class="time-to-buy__li">
                        В&nbsp;период Акции бонусы списываются с&nbsp;Клубной карты бренда покупки в&nbsp;размере&nbsp;10% от&nbsp;суммы покупки вне зависимости от&nbsp;Уровня карты, с&nbsp;карты дружественного бренда&nbsp;&mdash; до&nbsp;15%
                        вне зависимости от&nbsp;Уровня карты.
                    </li>
                    <li class="time-to-buy__li">
                        Бонусная скидка предоставляется от&nbsp;цены товара, указанной на&nbsp;ценнике, не&nbsp;суммируется с&nbsp;другими акциями и&nbsp;предложениями, не&nbsp;распространяется на&nbsp;ассортимент сезонной распродажи.</li>
                    <li class="time-to-buy__li">
                        В&nbsp;период с&nbsp;26&nbsp;ноября по&nbsp;31&nbsp;декабря 2021 года во&nbsp;всех брендах-участниках акции устанавливается тройной бонусный cashback при совершении третьей и&nbsp;последующих покупок.</li>
                    <li class="time-to-buy__li">
                        Бонусы накапливаются на&nbsp;Клубную карту, предъявленную при покупке, по&nbsp;условиям того бренда, в&nbsp;котором совершается покупка.</li>
                    <li class="time-to-buy__li">
                        Бонусы, начисленные за&nbsp;покупку, будут продублированы и&nbsp;на&nbsp;Клубную карту бренда, где совершается покупка, если такая карта имеется у&nbsp;клиента (ранее оформленная или новая). Дублирование бонусов будет
                        произведено дважды в&nbsp;ходе акции (20&nbsp;декабря 2021&nbsp;г.&nbsp;и&nbsp;16&nbsp;января 2022&nbsp;г.).
                    </li>
                </ul>
            </div>
            <div class="time-to-buy__gif mb-0"><a href="#">
                    <picture>
                        <source srcset="./assets/images/time-to-buy/03.gif " media="(min-width: 768px)">
                        <source srcset="./assets/images/time-to-buy/03-mob.gif " media="(min-width: 0px)"><img class="lazyload" src="./assets/images/example/load.webp" alt="" width="1" height="1" data-src="./assets/images/time-to-buy/03.gif">
                    </picture></a>
            </div>
        </main>
    </div>

    <link href="./css/libs.css?<?=md5_file($realPath. '/css/libs.css')?>" rel="stylesheet">
    <link href="./css/common.css?<?=md5_file($realPath. '/css/common.css')?>" rel="stylesheet">
    <link href="./css/time-to-buy.css?<?=md5_file($realPath. '/css/time-to-buy.css')?>" rel="stylesheet">

    <style>
      @media (min-width: 1600px){
        .b-header__top .container{
            min-width: 1170px;
        }
      }
      @media (min-width: 1290px){
        .b-header__top .container{
          min-width: 1170px;
        }
      }
      @media (min-width: 1024px){
        .b-header__top .container{
          min-width: 970px;
        }
      }
      @media (min-width: 768px){
        .b-header__top .container{
          min-width: 750px;
        }
      }
      .b-header__top .container .row {
        margin-left: -15px;
        margin-right: -15px;
      }
      [class*="col-"] {
        padding-left: 15px;
        padding-right: 15px;
      }
      .b-header__top .container .row .col-xs-2{
        width: 16.66667%;
      }
      .b-header__top .container .row .col-xs-4 {
        width: 33.33333%;
      }
      .b-header__top .container .row .col-xs-6 {
        width: 50%;
      }

    </style>

    <script src="./js/lib/svg4everybody.min.js"></script>
    <script src="./js/lib/ofi.min.js"></script>
    <script src="./js/lib/lazyload.min.js"></script>

    <script>
      $(document).ready(function() {
        // adds SVG External Content support to all browsers
        svg4everybody();

        // Polyfill object-fit/object-position on <img>
        objectFitImages();
        // lazyload
        let images = document.querySelectorAll('img.lazyload');
        new LazyLoad(images);

        window.LazyLoad = LazyLoad;
      });

      $(window).on('load', function() {
        $('body').addClass('loaded');
        appHeight();

        $(window).on('resize', function () {
          appHeight();
        })

        // загрузка изображений методом LazyLoad
        setTimeout(() => {
          let img = document.querySelectorAll("img.load");
          let lazy = new LazyLoad(img);
          lazy.loadImages();
        }, 1000);

        // check cookie accept
        let checkCookies = {
          func : {
            check(){
              let cookieDate = localStorage.getItem('cookieDate');
              let cookieNotification = $('#popup-cookie');
              let cookieBtnAccept = cookieNotification.find('[data-cookie-accept]');
              let cookieBtnClose = cookieNotification.find('[data-cookie-close]');

              // if cookie acept missing - show notification
              if( !cookieDate || (+cookieDate + 31536000000) < Date.now() ){
                cookieNotification.fadeIn('fast');
              }

              // cookie accept btn click
              cookieBtnAccept.click(function() {
                localStorage.setItem( 'cookieDate', Date.now() );
                cookieNotification.fadeOut('fast')
              })

              cookieBtnClose.click(function() {
                cookieNotification.fadeOut('fast')
              })
            }
          }
        }

        $(window).on('scroll orientationchange', function() {
          let top = $(window).scrollTop();
          let up = $('[data-btn-up]');

          if (top > $(window).height()) {
            up.addClass('show');
          } else {
            up.removeClass('show');
          }
        });

        function appHeight() {
          $('html').css({'--app-height': `${$(window).height()}px`, '--app-vh': `${$(window).height() / 100}px`});
        }

        checkCookies.func.check();

        $('[data-more]').on('click', function(e) {
          let el = $(this);
          let srt = el.attr('data-more').split(',');
          let parentPos = el.parent().offset().top;
          let wh = $(window).height();

          el.prev().toggleClass('show');

          if (el.prev().hasClass('show')) {
            el.text(srt[1])
          } else {
            $('html, body').animate({scrollTop: parentPos - wh / 2 }, 1000);
            el.text(srt[0])
          }
          e.preventDefault();
        });
      });

    </script>
<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>