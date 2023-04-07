<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Context;

/**
 * @global CMain $APPLICATION
 * @var string $REQUEST_METHOD
 */

$module_id = 'jamilco.delivery';
$RIGHT = $APPLICATION->GetGroupRight($module_id);

Loader::includeModule($module_id);

if ($RIGHT != 'D') {
    IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');
    IncludeModuleLangFile(__FILE__);

    $request = Context::getCurrent()->getRequest();

    $backUrl = $request->get('back_url_settings');

    $aTabs = array(
        array(
            'DIV'   => 'edit1',
            'TAB'   => 'Курьер',
            'ICON'  => 'perfmon_settings',
            'TITLE' => 'Курьер',
        ),
    );

    $tabControl = new CAdminTabControl('tabControl', $aTabs);

    if (
        $request->isPost()
        && ($RIGHT == 'W')
        && check_bitrix_sessid()
    ) {
        COption::SetOptionString('jamilco.delivery', 'courier_holidays', $request->get('courier_holidays'));
        COption::SetOptionString('jamilco.delivery', 'courier_intervals_weekdays_moscow', $request->get('courier_intervals_weekdays_moscow'));
        COption::SetOptionString('jamilco.delivery', 'courier_intervals_holidays_moscow', $request->get('courier_intervals_holidays_moscow'));
        COption::SetOptionString('jamilco.delivery', 'courier_intervals_weekdays_mo_30', $request->get('courier_intervals_weekdays_mo_30'));
        COption::SetOptionString('jamilco.delivery', 'courier_intervals_holidays_mo_30', $request->get('courier_intervals_holidays_mo_30'));
        COption::SetOptionString('jamilco.delivery', 'courier_intervals_weekdays_spb', $request->get('courier_intervals_weekdays_spb'));
        COption::SetOptionString('jamilco.delivery', 'courier_intervals_deny_delivery', $request->get('courier_intervals_deny_delivery'));

        if (strlen($backUrl) > 0) {
            LocalRedirect($backUrl);
        } else {
            LocalRedirect($APPLICATION->GetCurPage().'?mid='.urlencode($module_id).'&lang='.urlencode(LANGUAGE_ID).'&'.$tabControl->ActiveTabParam());
        }
    }

    $courierHolidays = COption::GetOptionString('jamilco.delivery', 'courier_holidays', '');
    $courierIntervalsWeekdaysMoscow = COption::GetOptionString('jamilco.delivery', 'courier_intervals_weekdays_moscow', '');
    $courierIntervalsHolidaysMoscow = COption::GetOptionString('jamilco.delivery', 'courier_intervals_holidays_moscow', '');
    $courierIntervalsWeekdaysMo30 = COption::GetOptionString('jamilco.delivery', 'courier_intervals_weekdays_mo_30', '');
    $courierIntervalsWeekdaysMo30To100 = COption::GetOptionString('jamilco.delivery', 'courier_intervals_weekdays_mo_30_to_100', '');
    $courierIntervalsWeekdaysSpb = COption::GetOptionString('jamilco.delivery', 'courier_intervals_weekdays_spb', '');
    $courierIntervalsDenyDelivery = COption::GetOptionString('jamilco.delivery', 'courier_intervals_deny_delivery', '');

    ?>
    <form method="POST" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&lang=<?= LANGUAGE_ID ?>">
        <?= bitrix_sessid_post() ?>
        <?php
        $tabControl->Begin();
        $tabControl->BeginNextTab();

        ?>
        <tr>
            <td width="40%">Праздничные дни</td>
            <td width="60%">
                <textarea name="courier_holidays" cols="20" rows="15"><?= $courierHolidays ?></textarea>
            </td>
        </tr>
        <tr>
            <td width="40%">Доставка заказов по Москве в пределах МКАД ежедневно в рабочие дни и в дни с укороченным графиком работы осуществляется с 10:00 до 22:00.Временные интервалы:</td>
            <td width="60%">
                <textarea name="courier_intervals_weekdays_moscow" cols="20" rows="6"><?= $courierIntervalsWeekdaysMoscow ?></textarea>
            </td>
        </tr>
        <tr>
            <td width="40%">Доставка заказов по Москве в пределах МКАД в выходные и праздничные дни осуществляется в интервалы:</td>
            <td width="60%">
                <textarea name="courier_intervals_holidays_moscow" cols="20" rows="6"><?= $courierIntervalsHolidaysMoscow ?></textarea>
            </td>
        </tr>
        <tr>
            <td width="40%">Доставка заказов за пределами МКАД. По первым 2-м зонам (до 30 км. от МКАД) в следующих интервалах. Доставка осуществляется 7 дней в неделю без праздников и выходных.</td>
            <td width="60%">
                <textarea name="courier_intervals_weekdays_mo_30" cols="20" rows="6"><?= $courierIntervalsWeekdaysMo30 ?></textarea>
            </td>
        </tr>
        <tr>
            <td width="40%">По Московской области (от 30 до 100 км. от МКАД). Доставка осуществляется 7 дней в неделю без праздников и выходных.</td>
            <td width="60%">
                <textarea name="courier_intervals_weekdays_mo_30_to_100" cols="20" rows="6"><?= $courierIntervalsWeekdaysMo30To100 ?></textarea>
            </td>
        </tr>
        <tr>
            <td width="40%">Доставляем ежедневно: с 10:00 до 22:00 с трехчасовым интервалом, первый возможный интервал с 10:00 до 15:00. Далее любой удобный 3-х часовой интервал начиная с 15:00. (15:00 — 18:00,  16:00 — 19:00, 17:00 — 20:00, 18:00 — 21:00, 19:00 — 22:00). Курьерская доставка заказов по г. Санкт-Петербург осуществляется 7 дней в неделю.</td>
            <td width="60%">
                <textarea name="courier_intervals_weekdays_spb" cols="20" rows="6"><?= $courierIntervalsWeekdaysSpb ?></textarea>
            </td>
        </tr>
        <tr>
            <td width="40%">Не осуществлять доставку по дням</td>
            <td width="60%">
                <textarea name="courier_intervals_deny_delivery" cols="20" rows="6"><?= $courierIntervalsDenyDelivery ?></textarea>
            </td>
        </tr>
        <?php

        $tabControl->Buttons();

        ?>
        <input
            <?= ($RIGHT < 'W') ? 'disabled' : '' ?>
            type="submit"
            name="Update"
            value="<?= GetMessage('MAIN_SAVE') ?>"
            title="<?= GetMessage('MAIN_OPT_SAVE_TITLE') ?>"
            class="adm-btn-save">
        <?php

        if (strlen($backUrl) > 0) {
            ?>
            <input
                <?= ($RIGHT < 'W') ? 'disabled' : '' ?>
                type="button"
                name="Cancel"
                value="<?= GetMessage('MAIN_OPT_CANCEL') ?>"
                title="<?= GetMessage('MAIN_OPT_CANCEL_TITLE') ?>"
                onclick="window.location='<?= htmlspecialcharsbx(CUtil::addslashes($backUrl)) ?>'">

            <input
                type="hidden"
                name="back_url_settings"
                value="<?= htmlspecialcharsbx($backUrl) ?>">
            <?php
        }

        $tabControl->End();
        ?>
    </form>
    <?php
}
?>