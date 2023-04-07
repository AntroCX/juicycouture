$(function() {
  changeCancelReason();
});

function changeCancelReason() {
  var _textarea = $('#FORM_REASON_CANCELED');
  _textarea.after('<select id="reasonToCancel" class="amd-bus-select"></select>');
  var _select = $('#reasonToCancel');
  _select.append('<option value=""> - выберите причину отмены заказа - </option>');

  for (var _i in window.cancelReasons) {
    var _one = window.cancelReasons[_i];
    if (!_one['NAME']) continue;

    var _selected = (_one['NAME'] == window.cancelReasonIs) ? 'selected="selected"' : '';
    _select.append('<option value="' + _one['VALUE'] + '" title="' + (_one['DESCRIPTION'] ? _one['DESCRIPTION'] : '') + '" ' + _selected + '>' + _one['NAME'] + '</option>');
  }

  //if (window.isCanceled == 'Y') _select.prop('disabled', true);

  _select.on('change', function() {
    _textarea.val(_select.find('option:selected').text());
    if (!_select.val()) _textarea.val('');
  });

  _textarea.css({display : 'none'});
}