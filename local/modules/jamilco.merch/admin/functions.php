<?

function printProps($arProps = [], $propGroup = 'SETTINGS')
{
    foreach ($arProps as $propertyCode => $arProp) {
        ?>
        <? if ($arProp['GROUP'] != $propGroup) continue; ?>
        <tr class="has-property">
            <td>
                <div class="adm-submenu-item-name">
                    <span class="adm-submenu-item-name-link-text" style="white-space: nowrap; padding-right: 10px;"><?= $arProp['TITLE'] ?></span>
                </div>
            </td>
            <td>
                <? if ($arProp['INPUT'] == 'checkbox') { ?>
                    <input type="checkbox" autocomplete="off" name="<?= $propertyCode ?>" <?= ($arProp['VALUE']) ? 'checked="checked"' : '' ?>>
                <? } elseif ($arProp['INPUT'] == 'date') { ?>
                    <?= CAdminCalendar::CalendarDate($propertyCode, $arProp['VALUE']) ?>
                <? } elseif ($arProp['INPUT'] == 'select') { ?>
                    <select name="<?= $propertyCode ?>">
                        <?= ($arProp['LIST_TYPE'] == 'ITEMS_PROPS') ? '<option value=""> - не используется - </option>' : '' ?>
                        <? foreach ($arProp['LIST'] as $key => $value) { ?>
                            <option value="<?= $key ?>" <?= ($key == $arProp['VALUE']) ? 'selected="selected"' : '' ?>><?= $value ?></option>
                        <? } ?>
                    </select>
                <? } ?>
            </td>
        </tr>
    <?
    }
}