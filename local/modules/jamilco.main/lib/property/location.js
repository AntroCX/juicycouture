function addLocationProperty(element) {
  var _element = $(element);
  var _next_element_index = parseInt($(element).attr('data-next-prop-index'));
  var _input_name = $(element).attr('data-control-name') + '[n' + _next_element_index + ']';

  _element.attr('data-next-prop-index', _next_element_index + 1);
  $.ajax({
    type : "POST",
    url  : "/local/modules/jamilco.main/lib/property/location_ajax.php",
    data : {
      input_name : _input_name
    }
  })
    .success(function(data) {
      _element.parent().find('.admin-location-property-container').append(data);
    });
}
