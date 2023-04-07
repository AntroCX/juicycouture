//init colorpicker:
$(document).ready(function() {
  $('.colorpickerField').each(function() {
    $(this).ColorPicker({
      onSubmit     : function(hsb, hex, rgb, el) {
        $(el).val(hex);
        $(el).ColorPickerHide();
      },
      onBeforeShow : function() {
        $(this).ColorPickerSetColor(this.value);
      }
    }).bind('keyup', function() {
      $(this).ColorPickerSetColor(this.value);
    });
  })
});