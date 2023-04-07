$(function() {
  loading(true);

  $('.has-property').find('input[type="checkbox"][name="new.up"]').on('change', function() {
    var _dateInput = $('input[name="new.time"]');
    if ($(this).prop('checked') == true) {
      _dateInput.val(window.nextDate);
    } else {
      _dateInput.val('');
    }
  });

  $('.has-property').find('input[type="checkbox"], select, input[type="text"]').on('change', function() {
    saveProperty();
  });

  $('#sortable').sortable({
    placeholder : "ui-state-highlight",
    stop        : function(a, b) {
      saveData();
    }
  });
});

function saveData() {
  var _data = {
    seasons : []
  };
  $('.season-sort .season-one').each(function() {
    _data['seasons'].push($(this).data('code'));
  });

  var _form = $('.merch');
  var _ajax = _form.data('ajax');
  if (_ajax) _ajax.abort();
  loading();
  _ajax = $.post('', {
    action : 'saveData',
    data   : _data
  }, function(_response) {
    loading(true);
  }, 'json');

  _form.data('ajax', _ajax);
}

function saveProperty() {
  var _form = $('.merch');
  var _flags = {};

  $('.has-property').each(function() {
    var _check = $(this).find('input[type="checkbox"]');
    if (_check.length) _flags[_check.attr('name')] = (_check.prop('checked')) ? 1 : 0;

    var _select = $(this).find('select');
    if (_select.length) _flags[_select.attr('name')] = _select.val();

    var _input = $(this).find('input[type="text"]');
    if (_input.length) _flags[_input.attr('name')] = _input.val();
  });

  var _ajax = _form.data('ajax');
  if (_ajax) _ajax.abort();
  loading();
  _ajax = $.post('', {
    action : 'saveProperty',
    data   : _flags
  }, function(_response) {
    loading(true);
  }, 'json');

  _form.data('ajax', _ajax);
}

function loading(_finish) {
  $('.adm-detail-content-btns').html((_finish) ? 'Данные сохранены' : 'Сохраняем данные...');
}