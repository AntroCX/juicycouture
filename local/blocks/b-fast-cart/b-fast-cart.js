'use strict';

function BitrixSmallCart(){}

BitrixSmallCart.prototype = {

  activate: function ()
  {
    this.cartElement = BX(this.cartId);
    this.fixedPosition = this.arParams.POSITION_FIXED == 'Y';
    this.isCurrentVisible = true;
    this.action = null;
    if (this.fixedPosition)
    {
      this.cartClosed = true;
      this.maxHeight = false;
      this.itemRemoved = false;
      this.verticalPosition = this.arParams.POSITION_VERTICAL;
      this.horizontalPosition = this.arParams.POSITION_HORIZONTAL;
      this.topPanelElement = BX("bx-panel");

      this.fixAfterRender(); // TODO onready
      this.fixAfterRenderClosure = this.closure('fixAfterRender');

      var fixCartClosure = this.closure('fixCart');
      this.fixCartClosure = fixCartClosure;

      if (this.topPanelElement && this.verticalPosition == 'top')
        BX.addCustomEvent(window, 'onTopPanelCollapse', fixCartClosure);

      var resizeTimer = null;
      BX.bind(window, 'resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(fixCartClosure, 200);
      });
    }
    this.setCartBodyClosure = this.closure('setCartBody');
    BX.addCustomEvent(window, 'OnBasketChange', this.closure('refreshCart', {}));

    // перемещение меню - todo
    // bug - не работает модальное окно
    /*
    var containerBlock = '#small-basket-container',
        cartBlock = '#'+this.cartId,
        cloneBlock = '#basketBlockClone';

    // инициализация прилипшей шапки
    var cartObject = this;
    $(window).scroll(function(){
      var scrollTop = $(window).scrollTop();
      if(scrollTop > 140) {
        // перемещение содержимого меню в дублирующий блок
        $(containerBlock).find(cartBlock).detach().appendTo(cloneBlock);
      } else {
        // Перемещение содержимого меню в исх блок
        $(cloneBlock).find(cartBlock).detach().appendTo(containerBlock);
      }
    });
    */

    this.addJqueryActions();
  },

  fixAfterRender: function ()
  {
    this.statusElement = BX(this.cartId + 'status');
    if (this.statusElement)
    {
      if (this.cartClosed)
        this.statusElement.innerHTML = this.openMessage;
      else
        this.statusElement.innerHTML = this.closeMessage;
    }
    this.productsElement = BX(this.cartId + 'products');
    this.fixCart();
  },

  closure: function (fname, data)
  {
    var obj = this;
    return data
      ? function(){obj[fname](data)}
      : function(arg1){obj[fname](arg1)};
  },

  closeCart: function ()
  {
    $('body').find('.b-fast-cart').removeClass('b-fast-cart_show');
  },

  setVerticalCenter: function(windowHeight)
  {
    var top = windowHeight/2 - (this.cartElement.offsetHeight/2);
    if (top < 5)
      top = 5;
    this.cartElement.style.top = top + 'px';
  },

  fixCart: function()
  {
    // set horizontal center
    if (this.horizontalPosition == 'hcenter')
    {
      var windowWidth = 'innerWidth' in window
        ? window.innerWidth
        : document.documentElement.offsetWidth;
      var left = windowWidth/2 - (this.cartElement.offsetWidth/2);
      if (left < 5)
        left = 5;
      this.cartElement.style.left = left + 'px';
    }

    var windowHeight = 'innerHeight' in window
      ? window.innerHeight
      : document.documentElement.offsetHeight;

    // set vertical position
    switch (this.verticalPosition) {
      case 'top':
        if (this.topPanelElement)
          this.cartElement.style.top = this.topPanelElement.offsetHeight + 5 + 'px';
        break;
      case 'vcenter':
        this.setVerticalCenter(windowHeight);
        break;
    }

    // toggle max height
    if (this.productsElement)
    {
      var itemList = this.cartElement.querySelector("[data-role='basket-item-list']");
      if (this.cartClosed)
      {
        if (this.maxHeight)
        {
          BX.removeClass(this.cartElement, 'bx-max-height');
          if (itemList)
            itemList.style.top = "auto";
          this.maxHeight = false;
        }
      }
      else // Opened
      {
        if (this.maxHeight)
        {
          if (this.productsElement.scrollHeight == this.productsElement.clientHeight)
          {
            BX.removeClass(this.cartElement, 'bx-max-height');
            if (itemList)
              itemList.style.top = "auto";
            this.maxHeight = false;
          }
        }
        else
        {
          if (this.verticalPosition == 'top' || this.verticalPosition == 'vcenter')
          {
            if (this.cartElement.offsetTop + this.cartElement.offsetHeight >= windowHeight)
            {
              BX.addClass(this.cartElement, 'bx-max-height');
              if (itemList)
                itemList.style.top = 82+"px";
              this.maxHeight = true;
            }
          }
          else
          {
            if (this.cartElement.offsetHeight >= windowHeight)
            {
              BX.addClass(this.cartElement, 'bx-max-height');
              if (itemList)
                itemList.style.top = 82+"px";
              this.maxHeight = true;
            }
          }
        }
      }

      if (this.verticalPosition == 'vcenter')
        this.setVerticalCenter(windowHeight);
    }
  },

  refreshCart: function (data)
  {
    console.log('refreshCart');
    /*if (this.itemRemoved) {
      //this.itemRemoved = false;
      return;
    }*/
    data.sessid = BX.bitrix_sessid();
    data.siteId = this.siteId;
    data.templateName = this.templateName;
    data.arParams = this.arParams;
    BX.ajax({
      url: this.ajaxPath,
      method: 'POST',
      dataType: 'html',
      data: data,
      onsuccess: this.setCartBodyClosure
    });
  },

  updateCart: function (params)
  {
    $.ajax({
      type : 'POST',
      data: {update_small : 'Y'},
      url : '/local/ajax/update_small_cart.php',
      success: this.setCartBodyClosure
    });
  },

  setCartBody: function (result)
  {
    if (this.cartElement) {
      this.cartElement.innerHTML = result;
    }
    if (this.fixedPosition)
      setTimeout(this.fixAfterRenderClosure, 100);

    this.initBasketFastbuy(true);
    BX.closeWait();

    if(this.action === 'add'){
      this.action = null;
      $('.b-fast-cart__body-item').first().toggleClass('hl');

      setTimeout(function () {
        $('.b-fast-cart__body-item').first().toggleClass('hl');
        setTimeout(function () {
          $('.b-fast-cart').removeClass('b-fast-cart_show');
        }, 1000)
      }, 1000);
    }

  },

  removeItemFromCart: function (id, skuId)
  {
    this.closeCart();
    BX.showWait();
    this.refreshCart ({sbblRemoveItemFromCart: id});
    this.itemRemoved = true;
    //BX.onCustomEvent('OnBasketChange');

    /** DigitalDataLayer start */
    if (typeof window.digitalData.events !== 'undefined' && typeof window.digitalData.cart !== 'undefined') {

      var product = null,
        quantityInBasket = 0,
        params = {};

      for (var i = 0; i < window.digitalData.cart.lineItems.length; i++) {
        if (window.digitalData.cart.lineItems[i].product.skuCode == skuId) {
          product = window.digitalData.cart.lineItems[i].product;
          quantityInBasket = window.digitalData.cart.lineItems[i].quantity;
          break;
        }
      }

      window.digitalData.events.push({
        "category": "Ecommerce",
        "name": "Removed Product",
        "product": {
          "id": product.id,
          "skuCode": product.skuCode
        },
        "quantity": quantityInBasket
      });

      params.event = 'removed';
      params.product = product;
      this.updateCart(params);
    }
    /** DigitalDataLayer end */
  },

  addJqueryActions: function ()
  {
    var cartObject = this;
    var $cartBlock = $('#'+this.cartId);

    $(function(){
      $(window).scroll(function(){
        var window = $(this);
        var current = false;

        if (window.width() < 768) {
          current = true;
        } else {
          var scrollTop = window.scrollTop();

          if (scrollTop <= 140) {
            current = true;
          }
        }

        cartObject.isCurrentVisible = current;
      });

      //скрывание блока корзины
      $(document).mouseup(function(event) {
        if( !$(event.target).closest('.b-fast-cart').length ) {
          cartObject.closeCart();
        }
      });

      cartObject.initBasketFastbuy(false);
    });
  },

  initBasketFastbuy: function (open)
  {
    var cartObject = this;

    var $cartBlock = $('#'+this.cartId);
    var $basket = $cartBlock.find('.b-fast-cart');

    //копирование содержимого в дублирующий блок
    var $copy = $cartBlock.find('ul.b-header__top-profile-menu').clone(true);
    $('#basketBlockClone').html($copy[0]);

    //инициализация прилипшей шапки
    $(window).scroll();

    //обработка клика по иконке корзины
    $('body').on('click', '.b-header__top-profile-menu-cart-ico', function(e){
      if (!$(this).hasClass('not-empty-basket')) {
        return;
      }

      var $basket = $(this).parent('.b-header__top-profile-menu-cart').find('.b-fast-cart');

      if ($(window).width() < 768) {
        if ($basket) {
          /*$('.b-page__content').addClass('open-cart');*/
          $basket.addClass('b-fast-cart_show');
          /*$basket.show();*/
        }
      } else {
        location.href = $(this).attr('data-pathToBasket');
      }
    });

    // клик для показа быстрой корзины
    $('body').on('click', 'a[data-target="#basket-fastbuy"]', function() {
      /** DigitalDataLayer start */
      if (typeof window.digitalData.events !== 'undefined') {
        window.digitalData.cart.checkoutType = '1-step-cart';
        window.digitalData.events.push({
          'category' : 'Ecommerce',
          'name'     : 'Viewed Cart',
          'cart'  : window.digitalData.cart
        });
      }
      /** DigitalDataLayer end */
    });

    var $popup = $('body').find('#basket-fastbuy');

    if ($popup.length <= 0) {
      return;
    }

    var $cart = $('.b-fast-cart');
    var $popupForm = $popup.find('.js-basket-fast-buy-form');
    var $slider = $popup.find('.popup-swiper-container');
    var itemsCount = $slider.find('.basket-small-item.slick-slide').length;
    var initSlider = false;
    var isAndroid = navigator.userAgent.toLowerCase().indexOf("android") > -1;
    var _mask = (isAndroid) ? '+79999999999' : '+7 (999) 999-99-99';

    $popup.on('click', '.js-popup-close, .js-continue-shopping', function () {
      $popup.modal('hide');
      BX.onCustomEvent(window, 'OnBasketChange');
    });

    $popupForm.find('[name="phone"]').mask(_mask);

    // форма применения купона
    $popupForm.on('click', '.js-coupon-toggle', function() {
      $popupForm.find('.js-coupon-form').toggleClass('none');
    });
    $popupForm.on('click', '.js-coupon-apply', function() {
      var coupon = $popupForm.find('.js-coupon-value').val();
      if (coupon.trim()) {
        $.post('/local/ajax/buy_one_click_basket.php', {'coupon': coupon, sessid: $popupForm.find('#sessid').val(), type: 'applyCoupon'}, null, 'json')
          .done(function(result) {
            if (result.success) {
              $popupForm.find('.js-discount').text('-' + result.data.discountSum);
              $popupForm.find('.js-order-total').text(parseInt(result.data.totalDiscountSum) + parseInt($popupForm.find('.js-delivery-cost').text()));
            } else {
              $popupForm.find('.js-coupon-error').show().html(result.errors);
              setTimeout(function() {
                $popupForm.find('.js-coupon-error').hide();
              }, 2000);
            }
          })
      }
    });

    $popupForm.validate({
      rules: {
        policyAccept: {
          required: true
        },
        fio: {
          required: true
        },
        phone: {
          required: true,
          phoneFormat: true
        },
        email: {
          required: true,
          email: true,
          mailFormat: true
        }
      },
      errorClass: 'error-notify',
      errorPlacement: function (error, element) {
        $(element).filter(':not(.valid)').addClass('invalid');
      },
      submitHandler: function (form) {
        BX.showWait();
        var errors = [];
        $(form).find('.invalid').removeClass('invalid');
        $popup.addClass('load');

        $.ajax({
          url: '/local/ajax/buy_one_click_basket.php',
          data: $(form).serialize(),
          dataType: 'json',
          type: 'POST',
          success: function (result) {
            if (result.success) {

              // RROCKET
              var orderRr = result.SEND_RROCKET_ORDERS[result.orderId];
              window.GENERAL.order.rRocketOrderAdd(orderRr);
              // WINDOW DATA LAYER
              var orderWdl = result.SEND_DATA_LAYER_ORDERS[result.orderId];
              window.GENERAL.order.dataLayerOrderAdd(orderWdl);

              var $success = $popup.find('.js-basket-fast-buy-success');
              $popup.find('.js-basket-fast-buy-content').hide();
              $popup.find('.modal-header h4, .modal-header span').hide();
              $success.find('.js-order-number').text(result.orderId);
              $success.show();
              $popup.data('update', true);

              window.inBasket = [];
              $('#buyBtn').addClass('active').html('<span>добавить в корзину</span>');

              if (typeof window.digitalData.events !== 'undefined') {

                var userName = $('.popup-basket-fast-buy-form input[name="fio"]').val(),
                  userPhone = $('.popup-basket-fast-buy-form input[name="phone"]').val(),
                  userEmail = $('.popup-basket-fast-buy-form input[name="email"]').val();

                if (userName == '')
                  userName = window.digitalData.user.firstName;

                if (userEmail == '')
                  userEmail = window.digitalData.user.email;

                var transaction = {
                  "category": "Ecommerce",
                  "name": "Completed Transaction",
                  "transaction": {
                    "orderId": result.orderId,
                    "currency": window.digitalData.cart.currency,
                    "subtotal": result.DDL.subtotal,
                    "total": result.DDL.total,
                    "lineItems": result.DDL.lineItems,
                    "checkoutType": '1-step-cart',
                    "contactInfo": {
                      "name": userName,
                      "phone": userPhone,
                      "email": userEmail
                    },
                    "vouchers": []
                  }
                };
                // если бронирует незалогиненый пользователь, тогда нужно добавить объект user
                if (typeof window.digitalData.user.userId === 'undefined') {
                  transaction.user = {
                    'firstName' : userName,
                    'email'     : userEmail,
                    'phone'     : userPhone
                  };
                }
                window.digitalData.events.push(transaction);

                var arrProducts = [];
                for(var i=0; i<  window.digitalData.cart.lineItems.length; i++) {
                  arrProducts.push({
                    id: window.digitalData.cart.lineItems[i].product.id,
                    qnt: window.digitalData.cart.lineItems[i].quantity,
                    price: window.digitalData.cart.lineItems[i].product.unitPrice,
                  });
                }

                // adspire
                window.adspire_track = window.adspire_track || [];
                window.adspire_track.push({
                  TypeOfPage : 'confirm',
                  Order      : {
                    id         : result.adspire.orderId,
                    type       : 'oneclick',
                    totalprice : result.adspire.totalprice,
                    usermail   : 'old',
                    name       : result.adspire.name,
                    lastname   : '',
                    email      : result.adspire.email,
                    coupon     : result.adspire.coupon
                  },
                  OrderItems : result.adspire.OrderItems
                });

              }
            } else {
              $('.popup').modal('hide');
              $('#js-popup-error').modal('show');
            }
            $popup.removeClass('load');
            BX.closeWait();
          },
          error: function (result) {
            $popup.removeClass('load');
            BX.closeWait();
          }
        });
      },
      invalidHandler : function(e, validator) {
        //console.log('invalidHandler');
        /*for (var i = 0; i < validator.errorList.length; i++) {
          var element = validator.errorList[i].element;
          $('html, body').animate({scrollTop : $(element).offset().top - 20}, 600);
          break;
        }*/
        BX.closeWait();
      }
    });

    $popup.on('shown.bs.modal', function() {
      cartObject.closeCart();
      var $slider = $popup.find('.popup-swiper-container');
      setTimeout(function() {
        $(window).trigger('resize');
        $slider.css('visibility', 'visible');
      }, 300);

    });

    if ($(window).width() > 767 && itemsCount > 2) {
      initSlider = true;
    } else if ($(window).width() <= 767 && itemsCount > 1) {
      initSlider = true;
    }

    if (initSlider) {
      $slider.css('visibility', 'hidden');

      $slider.slick({
        autoplay: false,
        prevArrow: '<a class="i-slick__prev"></a>',
        nextArrow: '<a class="i-slick__next"></a>',
        responsive: [
          {
            breakpoint: 2000,
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
          }
          // You can unslick at a given breakpoint now by adding:
          // settings: "unslick"
          // instead of a settings object
        ]
      });
    }

    if (open) {
      this.openCurrentCart();
    }

    //закрывание блока на мобиле
    if ($(window).width() < 768) {
      $cart.on("swipe", function (event) {
        cartObject.closeCart();
      });
    }
  },

  openCurrentCart: function ()
  {
    var $cartBlock = (this.isCurrentVisible) ? $('#'+this.cartId) : $('#basketBlockClone');
    var $basket = $cartBlock.find('.b-fast-cart');

    /*$('.b-page__content').addClass('open-cart');*/
    $basket.addClass('b-fast-cart_show');
    /*$basket.show();*/
  }
};