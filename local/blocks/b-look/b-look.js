$(function() {

    setSKULook();

    /** селектор размера */
    var size_select = '.b-catalog-detail__sizes-list-item.look';

    $('.look-popup').on('click', size_select, function(event) {
        event.stopPropagation();
        var $this = $(this);
        $this.addClass('active');
        $(size_select).parent().find(size_select).not($this).removeClass('active');
        setSKULook();
    });

    /** селектор цвета */
    var color_select = '.b-catalog-detail__colors-list-item.look';

    $('.look-popup').on('click', color_select, function(event) {
        event.stopPropagation();
        var $this = $(this);
        $this.addClass('active');
        $(color_select).parent().find(color_select).not($this).removeClass('active');
        setSKULook();
    });

    // добавление в корзину
    $('.look-popup').on('click', '.b-catalog-detail__add2basket.look', function (event) {
        event.preventDefault();
        var $this = $(this);
        var offerId = $('#offerIdLook').val();
        var quantity = $('.b-catalog-detail__count-selector.look').val();
        var url = $this.attr('data-url');

        if (!$this.hasClass('added') && !$this.hasClass('btn_load')) {
            $this.addClass('btn_load');
            $this.attr('disabled', 'disabled');

            url = '/local/ajax/update_small_cart.php';
            $.get(url, {action: 'ADD2BASKET', quantity: quantity, id: offerId, ajax_basket: 'Y'})
              .done(function() {
                var $cart = $('.b-fast-cart');
                BX.onCustomEvent('OnBasketChange');
                $this.removeClass('btn_load');
                $this.addClass('added');
                $this.removeAttr('disabled');

                /** DigitalDataLayer start */
                if (typeof window.digitalData.events !== 'undefined' && typeof selectedSKU !== 'undefined') {
                  var productId = $this.data('product-id');
                  var productName = $this.closest('.item-container').find('.b-catalog__goods-item-name').text();

                  if (typeof window.digitalData.product !== 'undefined') {
                    var product = window.digitalData.product;
                    window.digitalData.events.push({
                      'category': 'Ecommerce',
                      'name': 'Added Product',
                      'product': {
                        'id': '' + productId,
                        'skuCode': '' + selectedSKU.ID,
                        'article': selectedSKU.DDL.article,
                        'name': productName,
                        'currency': product.currency,
                        'unitPrice': selectedSKU.DDL.unitPrice,
                        'unitSalePrice': selectedSKU.DDL.unitSalePrice,
                        'category': selectedSKU.DDL.category,
                        'categoryId': selectedSKU.DDL.categoryId
                      },
                      'quantity': 1 * quantity
                    });
                  }
                }
                /** DigitalDataLayer end */
              });
        }
    });

    $('.look-popup').on('click', '.b-catalog-detail__add2basket.added', function () {
        location.href = '/order/';
    });

  /**
   * функция для выбора ску по размеру и цвету
   */

  var selectedSKU;

  function setSKULook() {
    // color_available_look - выставляется в template.php
    // sku_tree_look - выставляется в template.php

    var tree = sku_tree_look;
    var size = $('.b-catalog-detail__sizes-list-item.active.look').attr('data-id');
    var $sizes = $('.b-catalog-detail__sizes-list-item.look');
    var $colors = $('.b-catalog-detail__colors-list-item.look');
    var color = $('.b-catalog-detail__colors-list-item.active.look').attr('data-id');

    if (typeof color == 'undefined') {
      var $first = $('.look.b-catalog-detail__colors-list-item:first-child');
      $first.addClass('active');
      color = $first.attr('data-id');
    }

    var availableKeys = [];
    $.each(tree[color], function (key, value) {
      if (value.QUANTITY > 0) {
        availableKeys.push(key);
      }
    });

    if (typeof size == 'undefined') {
      var $first = $('.look.b-catalog-detail__sizes-list-item[data-id="' + availableKeys.slice(0, 1)[0] + '"]');
      $first.addClass('active');
      size = $first.attr('data-id');
    }

    var selected = tree[color][size] || {ID: 0, QUANTITY: 0};
    selectedSKU = selected;

    // деактивация отсутствующих размеров
    $sizes.each(function () {
      var $this = $(this);
      var id = $this.attr('data-id');
      if($.inArray(id, availableKeys) == -1) {
        $this.addClass('notavailable');
        $this.removeClass('active');
      } else {
        $this.removeClass('notavailable');
      }
    });

    // установка значений ску
    $('#offerIdLook').val(selected.ID);

    var $qu_selector = $('.b-catalog-detail__count-selector.look');
    $qu_selector.find('option').remove();

    var $basketBtn = $('.b-catalog-detail__add2basket.look');

    $basketBtn.removeClass('added');

    if(selected.QUANTITY > 0 && selected['DENIED_BUY'] !== 'Y') {
      $basketBtn.removeAttr('disabled');
      for (var i = 1; i <= selected.QUANTITY; i++) {
        var option = $('<option></option>').attr("value", i).text(i);
        $qu_selector.append(option);
      }
    } else {
      var option = $('<option></option>').attr("value", 1).text(1);
      $qu_selector.append(option);
      $basketBtn.attr('disabled', 'disabled');
    }

    // деактивация цвета, если его нет в наличии
    $colors.each(function () {
      var $this = $(this);
      var id = parseInt($this.attr('data-id'));
      if($.inArray(id, color_available_look[size]) == -1) {
        $this.addClass('notavailable');
      } else {
        $this.removeClass('notavailable');
      }
    });
  }
});
