$(function() {
  // инициирует дерево
  initParentOpenClose();

  // открываем путь к заранее выбранному пункту
  checkSelectedItem();
});

/**
 * инициирует дерево
 */
function initParentOpenClose() {
  $('.section-select .parent-yes').click(function() {
    var _id = $(this).data('id');
    var _open = $(this).data('open');

    if (_open && _open == 'Y') {
      // закрыть
      $('#sections-for-' + _id).removeClass('child-slide-show');

      $(this).removeClass('parent-opened');
      $(this).data('open', 'N');
    } else {
      // открыть
      $('#sections-for-' + _id).addClass('child-slide-show');

      $(this).addClass('parent-opened');
      $(this).data('open', 'Y');
    }
  });

  $('.section-select').find('input[type="checkbox"]').on('change', function() {
    var _allSections = new Array();
    $('.section-select').find('input[type="checkbox"]:checked').each(function() {
      _allSections.push($(this).val());
    });

    $('.section-select').find('input[name="SECTIONS"]').val(_allSections.join(','));
  });
}

/**
 * открываем путь к заранее выбранному пункту
 */
function checkSelectedItem() {

  $('.section-select .radio-in-tree:checked').each(function() {
    var _radio = $(this);

    _radio.parents('.child-slide').each(function() {
      var _id = $(this).attr('id');
      _id = _id.replace('sections-for-', '');
      if ($('#sections-' + _id).data('open') != 'Y') $('#sections-' + _id).click();
    });
  });

}