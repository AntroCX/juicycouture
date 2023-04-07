(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[1],{

/***/ "./app/js/common.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($, global) {/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var svg4everybody_dist_svg4everybody__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("./node_modules/svg4everybody/dist/svg4everybody.js");
/* harmony import */ var svg4everybody_dist_svg4everybody__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(svg4everybody_dist_svg4everybody__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var object_fit_images__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("./node_modules/object-fit-images/dist/ofi.common-js.js");
/* harmony import */ var object_fit_images__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(object_fit_images__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var lazyload_lazyload__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("./node_modules/lazyload/lazyload.js");
/* harmony import */ var lazyload_lazyload__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(lazyload_lazyload__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _scss_common_scss__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("./app/scss/common.scss");
/* harmony import */ var _scss_common_scss__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_scss_common_scss__WEBPACK_IMPORTED_MODULE_4__);

global.jQuery = $;
global.jquery = $;
global.$ = $;




$(document).ready(function () {
  // adds SVG External Content support to all browsers
  svg4everybody_dist_svg4everybody__WEBPACK_IMPORTED_MODULE_1___default()(); // Polyfill object-fit/object-position on <img>

  object_fit_images__WEBPACK_IMPORTED_MODULE_2___default()(); // lazyload

  var images = document.querySelectorAll('img.lazyload');
  new lazyload_lazyload__WEBPACK_IMPORTED_MODULE_3___default.a(images);
  window.LazyLoad = lazyload_lazyload__WEBPACK_IMPORTED_MODULE_3___default.a;
});
$(window).on('load', function () {
  $('body').addClass('loaded');
  appHeight();
  $(window).on('resize', function () {
    appHeight();
  }); // загрузка изображений методом LazyLoad

  setTimeout(function () {
    var img = document.querySelectorAll("img.load");
    var lazy = new lazyload_lazyload__WEBPACK_IMPORTED_MODULE_3___default.a(img);
    lazy.loadImages();
  }, 1000); // check cookie accept

  var checkCookies = {
    func: {
      check: function check() {
        var cookieDate = localStorage.getItem('cookieDate');
        var cookieNotification = $('#popup-cookie');
        var cookieBtnAccept = cookieNotification.find('[data-cookie-accept]');
        var cookieBtnClose = cookieNotification.find('[data-cookie-close]'); // if cookie acept missing - show notification

        if (!cookieDate || +cookieDate + 31536000000 < Date.now()) {
          cookieNotification.fadeIn('fast');
        } // cookie accept btn click


        cookieBtnAccept.click(function () {
          localStorage.setItem('cookieDate', Date.now());
          cookieNotification.fadeOut('fast');
        });
        cookieBtnClose.click(function () {
          cookieNotification.fadeOut('fast');
        });
      }
    }
  };
  $(window).on('scroll orientationchange', function () {
    var top = $(window).scrollTop();
    var up = $('[data-btn-up]');

    if (top > $(window).height()) {
      up.addClass('show');
    } else {
      up.removeClass('show');
    }
  });

  function appHeight() {
    $('html').css({
      '--app-height': "".concat($(window).height(), "px"),
      '--app-vh': "".concat($(window).height() / 100, "px")
    });
  }

  checkCookies.func.check();
  $('[data-more]').on('click', function (e) {
    var el = $(this);
    var srt = el.attr('data-more').split(',');
    var parentPos = el.parent().offset().top;
    var wh = $(window).height();
    el.prev().toggleClass('show');

    if (el.prev().hasClass('show')) {
      el.text(srt[1]);
    } else {
      $('html, body').animate({
        scrollTop: parentPos - wh / 2
      }, 1000);
      el.text(srt[0]);
    }

    e.preventDefault();
  });
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__("./node_modules/jquery/dist/jquery.js"), __webpack_require__("./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./app/scss/common.scss":
/***/ (function(module, exports, __webpack_require__) {

var api = __webpack_require__("./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
            var content = __webpack_require__("./node_modules/mini-css-extract-plugin/dist/loader.js!./node_modules/css-loader/dist/cjs.js?!./node_modules/postcss-loader/src/index.js?!./node_modules/sass-loader/dist/cjs.js?!./app/scss/common.scss");

            content = content.__esModule ? content.default : content;

            if (typeof content === 'string') {
              content = [[module.i, content, '']];
            }

var options = {};

options.insert = "head";
options.singleton = false;

var update = api(content, options);



module.exports = content.locals || {};

/***/ }),

/***/ "./node_modules/mini-css-extract-plugin/dist/loader.js!./node_modules/css-loader/dist/cjs.js?!./node_modules/postcss-loader/src/index.js?!./node_modules/sass-loader/dist/cjs.js?!./app/scss/common.scss":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ })

}]);
//# sourceMappingURL=common.js.map