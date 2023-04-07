$(function() {
  initSlide(true);

  initParentOpenClose(); // инициирует дерево выбора раздела

  $('.has-property').find('input[type="checkbox"], input[type="text"], select').on('change blur', function() {
    saveProperty();
  });
  $('.has-property').find('select option').on('click', function() {
    saveProperty();
  });

  $('.adm-detail-content-btns').html('\
    <input type="button" \
          name="reSaveAll" \
          value="Пересохранить флаги доставки" \
          class="adm-btn omni-resave-all"\
          >\
    <input type="button" \
          name="reSaveCities" \
          value="Пересохранить наличие по городам" \
          title="Переопределить наличие товаров по городам" \
          class="adm-btn omni-resave"\
          >\
    <div class="omni-loader"></div>\
    <div class="clear"></div>\
  ');

  $('.omni-resave, .omni-resave-all').on('click', function() {
    var _form = $('.channel');

    var _reSaveAll = ($(this).hasClass('omni-resave-all')) ? 'Y' : 'N';

    loader();
    var _ajax = _form.data('ajax');
    if (_ajax) _ajax.abort();
    _ajax = $.post('', {
      action : 'reSaveCities',
      all    : _reSaveAll
    }, function(_response) {
      loader(true);
      console.log('saved cities');
    }, 'json');

    _form.data('ajax', _ajax);
  });
});

// инициирует дерево разделов
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

    $('.section-select').find('input[name="sections"]').val(_allSections.join(','));
  });

  checkSelectedItem(); // открываем путь к заранее выбранному пункту
}

// открываем путь к заранее выбранному пункту
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

// прелоадер
function loader(_close, _text) {
  if (_close) {
    $('.omni-loader').html('Данные сохранены!');
  } else {
    $('.omni-loader').html('Сохраняем данные...');
  }

  if (_text) $('.omni-loader').html(_text);
}

// раскрытие элементов
function initSlide(_root) {
  var _form = $('.channel');
  _form.find('.adm-submenu-item-name').each(function() {
    if (!$(this).data('init')) {
      var _block = $(this);
      _block.data('init', 'Y');

      _block.on('click', function() {
        var _mainBlock = _block.closest('.adm-detail-content').attr('id');

        var _all = false;
        if (_mainBlock == 'catalog') _all = window.catalog;
        if (_mainBlock == 'shop') _all = window.shop;
        if (!_all) return false;

        var _td = _block.closest('td');
        var _tr = _td.closest('tr');
        var _trAdd = _tr;
        var _sectionId = _block.data('id');
        if (!_block.hasClass('adm-submenu-no-children')) {
          if (_sectionId in _all['CHILD'] || _sectionId in _all['ELEMENT']) {
            var _children = _all['CHILD'][_sectionId];
            var _elements = _all['ELEMENT'][_sectionId];

            if (_td.hasClass('adm-sub-submenu-open')) {
              // close
              _td.removeClass('adm-sub-submenu-open');

              // удалим все строки потомков
              _tr.closest('table').find('.sect-' + _sectionId).remove();
            } else {
              // open
              _td.addClass('adm-sub-submenu-open');

              // цепочка разделов до открытого
              var _chain = [_sectionId];
              var _parentId = _all['SECTIONS'][_sectionId]['IBLOCK_SECTION_ID'];
              while (_parentId > 0) {
                _chain.push(_parentId);
                _parentId = _all['SECTIONS'][_parentId]['IBLOCK_SECTION_ID'];
              }

              // добавим подразделы
              for (_i in _children) {
                var _subSectionId = _children[_i];
                var _subSection = _all['SECTIONS'][_subSectionId];

                var _append = getBlock('section', _subSectionId, _subSection, _chain, _all);

                _trAdd.after(_append);
                _trAdd = _trAdd.next();
              }

              // добавим элементы
              for (_i in _elements) {
                var _element = _elements[_i];

                var _append = getBlock('element', _element['ID'], _element, _chain, _all);

                _trAdd.after(_append);
                _trAdd = _trAdd.next();
              }
            }
          }
        }

        initCheckBoxes();
        initSlide();

        return false;
      });
    }
  });

  if (_root) {
    // добавим корневые разделы и элементы из корня
    addRootData('catalog', window.catalog);
    addRootData('shop', window.shop);
  }
}

// добавляет корневые разделы и элементы из корня
function addRootData(_type, _all) {
  var _tr = $('.channel tr.' + _type + '-head');
  var _trAdd = _tr;
  for (_i in _all['MAIN']) {
    var _subSectionId = _all['MAIN'][_i];
    var _subSection = _all['SECTIONS'][_subSectionId];

    var _append = getBlock('section', _subSectionId, _subSection, [], _all);

    _trAdd.after(_append);
    _trAdd = _trAdd.next();
  }

  if (0 in _all['ELEMENT']) {
    for (_i in _all['ELEMENT'][0]) {
      var _element = _all['ELEMENT'][0][_i];

      var _append = getBlock('element', _element['ID'], _element, [], _all);

      _trAdd.after(_append);
      _trAdd = _trAdd.next();
    }
  }

  initCheckBoxes();
  initSlide();
}

function getBlock(_type, _id, _data, _chain, _all) {
  var _append = '<tr class="has-data sect-' + _chain.join(' sect-') + '" data-id="' + _id + '" data-type="' + _type + '">'; // записаны все родители
  if (_type == 'section') {
    var _flags = (window.flags['section'] && _id in window.flags['section']) ? window.flags['section'][_id] : [];
    var _hasChild = (_id in _all['CHILD'] || _id in _all['ELEMENT']);
    _append += '\
    <td class="depth_level_' + _data['DEPTH_LEVEL'] + '  active-' + _data['ACTIVE'] + '">\
      <div class="adm-submenu-item-name ' + ((_hasChild) ? '' : 'adm-submenu-no-children') + '" data-id="' + _id + '">\
      <span class="adm-submenu-item-arrow"><span class="adm-submenu-item-arrow-icon"></span></span>\
      <span class="adm-submenu-item-link-icon iblock_menu_icon_sections"></span>\
        <span class="adm-submenu-item-name-link-text">' + _data['NAME'] + '</span>\
      </div>\
    </td>';
  } else if (_type == 'element') {
    var _flags = (window.flags['element'] && _id in window.flags['element']) ? window.flags['element'][_id] : [];

    var _depth = 1;
    if (_data['IBLOCK_SECTION_ID']) {
      var _mainSection = _all['SECTIONS'][_data['IBLOCK_SECTION_ID']];
      _depth = parseInt(_mainSection['DEPTH_LEVEL']) + 1;
    }

    var _name = ('PROPERTY_ARTNUMBER_VALUE' in _data) ? _data['PROPERTY_ARTNUMBER_VALUE'] : _data['NAME'];
    var _desc = ('PROPERTY_ADDRESS_VALUE' in _data) ? _data['PROPERTY_ADDRESS_VALUE'] : _data['NAME'];
    _append += '\
    <td class="depth_level_' + _depth + '">\
      <div class="adm-submenu-item-name adm-submenu-no-children" data-id="' + _id + '">\
      <span class="adm-submenu-item-arrow"><span class="adm-submenu-item-arrow-icon"></span></span>\
      <span class="adm-submenu-item-link-icon iblock_menu_icon_iblocks"></span>\
        <span class="adm-submenu-item-name-link-text" title="' + _desc + '">' + _name + '</span>\
      </div>\
    </td>';
  } else {
    return false;
  }

  if (!_flags.length) {
    var _parent = _data['IBLOCK_SECTION_ID'];
    while (!_flags.length && _parent) {
      _flags = (window.flags['section'] && _parent in window.flags['section']) ? window.flags['section'][_parent] : [];
      _parent = _all['SECTIONS'][_parent]['IBLOCK_SECTION_ID'];
    }
  }

  for (_f in _all['FLAGS']) {
    var _flag = _all['FLAGS'][_f].toLowerCase();
    var _checked = ($.inArray(_flag, _flags) != -1) ? 'checked="checked"' : '';
    _append += '<td><input type="checkbox" autocomplete="off" name="' + _type + '--' + _id + '--' + _flag + '" ' + _checked + '></td>';
  }

  if (_type == 'section') {
    _append += '<td><button class="remove-child-flags" title="Наследовать флаги раздела всем подразделам и товарам">&crarr;</button></td>';
  } else {
    _append += '<td></td>';
  }

  _append += '</tr>';

  return _append;
}

function initCheckBoxes() {
  var _form = $('.channel');
  _form.find('input[type="checkbox"]').each(function() {
    if (!$(this).hasClass('adm-designed-checkbox')) {
      var _check = $(this);
      _check.addClass('adm-designed-checkbox');
      _check.after('<label class="adm-designed-checkbox-label"></label>');
      _check.parent().find('.adm-designed-checkbox-label').on('click', function() {
        _check.prop('checked', !_check.prop('checked'));
        saveData();

        var _tr = _check.closest('tr.has-data');
        if (_tr.data('type') == 'section') {
          if (window.timerInfo) clearTimeout(window.timerInfo);

          $('tr.has-data.has-info').removeClass('has-info');
          $('tr.has-data td span.info').remove();
          _tr.addClass('has-info');
          _tr.find('td').last().append('<span class="info">вы изменили омни-флаги у раздела, чтобы эти флаги применились всем вложенным подразделам и товарам, нажмите <b>↑</b></span>');

          window.timerInfo = setTimeout(function() {
            $('tr.has-data.has-info').removeClass('has-info');
            $('tr.has-data td span.info').remove();
          }, 5000);
        }
      });
    }
  });

  _form.find('.remove-child-flags').each(function() {
    if ($(this).data('init') != 'Y') {
      $(this).data('init', 'Y');
      $(this).on('click', function() {
        var _tr = $(this).closest('tr');
        var _name = _tr.find('.adm-submenu-item-name-link-text').html();
        if (confirm('Подтвердите удаление собственных флагов для всех подразделов и товаров раздела "' + _name + '"')) {
          var _id = _tr.data('id');

          loader();
          $.post('', {
            action : 'clearChildFlags',
            id     : _id
          }, function(_flags) {
            loader(true);
            console.log('cleared for ' + _id);

            if (_tr.find('.adm-sub-submenu-open').length > 0) {
              _tr.find('.adm-submenu-item-name').click();
            }

            window.flags = _flags;

          }, 'json');
        }

        return false;
      });
    }
  });

}

function saveProperty() {
  var _form = $('.channel');
  var _flags = {};

  $('.has-property').each(function() {
    var _check = $(this).find('input[type="checkbox"]');
    var _checkName = _check.attr('name');
    if (_check.length) {
      _flags[_checkName] = (_check.prop('checked')) ? 1 : 0;
    }

    var _text = $(this).find('input[type="text"]');
    if (_text.length) {
      _flags[_text.attr('name')] = _text.val();
    }

    var _select = $(this).find('select');
    if (_select.length) {
      var _val = _select.val();
      if (_select.attr('multiple') && _val && $.isArray(_val)) _val = _val.join(',');
      _flags[_select.attr('name')] = _val;
    }
  });

  _flags['sections'] = $('.section-select input[name="sections"]').val();
  _flags['locations'] = [];
  $('.locations input[name="LOCATION[]"]').each(function() {
    var _val = $(this).val();
    if (_val) _flags['locations'].push(_val);
  });
  _flags['locations'] = _flags['locations'].join(',');

  loader();
  var _ajax = _form.data('ajax');
  if (_ajax) _ajax.abort();
  _ajax = $.post('', {
    action : 'saveProperty',
    data   : _flags
  }, function(_response) {
    loader(true);
    console.log('saved property');
  }, 'json');

  _form.data('ajax', _ajax);
}

function saveData() {
  var _form = $('.channel');
  var _data = {};
  _form.find('.edit-table tr.has-data').each(function() {
    var _id = $(this).data('id');
    var _type = $(this).data('type');
    var _flags = [];
    $(this).find('input[type="checkbox"]:checked').each(function() {
      var _name = $(this).attr('name');
      _name = _name.split('--');
      _flags.push(_name[2]);
    });
    if (!_flags.length) _flags.push('0');

    window.flags[_type][_id] = _flags;
  });

  // удаляем флаги, которые просто наследуются и не несут различий
  //deleteDubbleFlags();

  // сохраняем через задержку, чтоб при быстром изменении флагов, не увеличивать количество запросов
  loader(false, 'Ждём очередь на отправку...');
  if (window.timer) clearTimeout(window.timer);
  window.timer = setTimeout(function() {
    loader();

    var _ajax = _form.data('ajax');
    if (_ajax) _ajax.abort();
    _ajax = $.post('', {
      action : 'saveData',
      data   : window.flags
    }, function(_response) {
      // пройдем по всем флагам, и те записи, где есть dubble, удалим
      var _sectionFlags = {};
      $.each(window.flags['section'], function(key, value) {
        if ($.inArray('dubble', value) == -1) _sectionFlags[key] = value;
      });
      window.flags['section'] = _sectionFlags;

      var _elementFlags = {};
      $.each(window.flags['element'], function(key, value) {
        if ($.inArray('dubble', value) == -1) _elementFlags[key] = value;
      });
      window.flags['element'] = _elementFlags;

      loader(true);
      console.log('saved');
    }, 'json');

    _form.data('ajax', _ajax);
  }, 3000);
}

// удалить дубли из сохраняемых флагов
function deleteDubbleFlags() {
  // проставим флаг "dubble" везде, где нужно удалить

  // сначала товарам
  for (_i in window.catalog['ELEMENT']) {
    var _sect = window.catalog['ELEMENT'][_i];
    for (_j in _sect) {
      var _elem = _sect[_j];

      var elemFlag = window.flags['element'][_elem['ID']];
      var sectParentFlag = window.flags['section'][_elem['IBLOCK_SECTION_ID']];

      if (!elemFlag || !sectParentFlag) continue;
      if ($.compare(elemFlag, sectParentFlag)) {
        window.flags['element'][_elem['ID']].push('dubble');
      }
    }
  }

  // теперь разделам
  for (_i in window.catalog['SECTIONS']) {
    var _sect = window.catalog['SECTIONS'][_i];
    if (!_sect['IBLOCK_SECTION_ID']) continue;

    var sectFlag = window.flags['section'][_sect['ID']];
    var sectParentFlag = window.flags['section'][_sect['IBLOCK_SECTION_ID']];

    if (!sectFlag || !sectParentFlag) continue;
    if ($.compare(sectFlag, sectParentFlag)) {
      window.flags['section'][_sect['ID']].push('dubble');
    }
  }
}

jQuery.extend({
  compare : function(arrayA, arrayB) {
    if (arrayA.length != arrayB.length) { return false; }
    // sort modifies original array
    // (which are passed by reference to our method!)
    // so clone the arrays before sorting
    var a = jQuery.extend(true, [], arrayA);
    var b = jQuery.extend(true, [], arrayB);
    a.sort();
    b.sort();
    for (var i = 0, l = a.length; i < l; i++) {
      if (a[i] !== b[i]) {
        return false;
      }
    }
    return true;
  }
});