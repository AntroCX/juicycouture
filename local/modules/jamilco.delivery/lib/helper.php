<?php

namespace Jamilco\Delivery;

use Bitrix\Main\Grid\Declension;

class Helper
{
    /**
     * Срок доставки в днях
     *
     * @param string $text - срок доставки от служб доставки
     *
     * @return string
     */
    public static function deliveryTimeDays($text): string
    {
        $text = trim($text);

        if (!$text) {
            return '';
        }

        $countDeclension = new Declension('день', 'дня', 'дней');

        if (substr_count($text, '-')) {
            list($min, $max) = explode('-', $text);

            $min = (int)$min;
            $max = (int)$max;

            if ($min == $max) {
                return $min.' '.$countDeclension->get($min);
            } else {
                return $min.'-'.$max.' '.$countDeclension->get($max);
            }
        } else {
            $min = (int)$text;

            if ($min > 0) {
                return $min.' '.$countDeclension->get($min);
            }
        }

        return '';
    }

    /**
     * Срок доставки в виде конкретной даты
     *
     * @param int    $deliveryId
     * @param string $text - срок доставки от служб доставки
     *
     * @return string
     */
    public static function deliveryTimeDate($deliveryId, $text = ''): string
    {
        $dayCount = (int)$text;
        if (substr_count($text, '-')) {
            $dayCount = explode('-', $text);
            if ((int)$dayCount[1] > 0) {
                $dayCount = (int)$dayCount[1];
            }
        }

        $serverDateTime = getdate();
        if (
            ($deliveryId == KCE_DELIVERY )
            //|| ($deliveryId == SDEK_DELIVERY)
        ) {
            // Если время на сервере больше чем 16.00, то нужно прибавить 1 день к сроку доставки
            if ($serverDateTime['hours'] >= 16) {
                $dayCount++;
            }

        } else if ($deliveryId == PICKUP_DELIVERY) {
            // Если время на сервере больше чем 20.00, то нужно прибавить 1 день к сроку доставки
            if ($serverDateTime['hours'] >= 20) {
                $dayCount++;
            }
        }

        if ($dayCount == 0) {
            $dayCountFormat = 'Сегодня';
        } elseif ($dayCount == 1) {
            $dayCountFormat = 'Завтра';
        } else {
            $dayCountFormat = date('d.m.y', time() + 86400 * $dayCount);
        }

        return $dayCountFormat;
    }

    /**
     * Интервалы доставки
     *
     * @param $locationId
     * @return array
     */
    public static function getDeliveryTimes($locationId) {
        $locationId = (int)$locationId;
        if ($locationId <= 0) {
            return [];
        }

        $isMoscowOrMO = false;
        $isSpb = false;
        $isAvailableTimes = false;
        if ($locationId === MOSCOW_LOCATION_ID) { //Москва
            $deliveryIntervalsWeekdaysRaw = explode("\n", \COption::GetOptionString('jamilco.delivery', 'courier_intervals_weekdays_moscow', ''));
            $deliveryIntervalsHolidaysRaw = explode("\n", \COption::GetOptionString('jamilco.delivery', 'courier_intervals_holidays_moscow', ''));
            $isAvailableTimes = true;
            $isMoscowOrMO = true;
        }
        if ($locationId === SPB_LOCATION_ID) { //Санкт-Петербург
            $deliveryIntervalsWeekdaysRaw = explode("\n", \COption::GetOptionString('jamilco.delivery', 'courier_intervals_weekdays_spb', ''));
            $deliveryIntervalsHolidaysRaw = explode("\n", \COption::GetOptionString('jamilco.delivery', 'courier_intervals_weekdays_spb', ''));
            $isAvailableTimes = true;
            $isSpb = true;
        }
        if (in_array($locationId, LOCATIONS_CITIES_MO_30)) {//Города МО в пределах до 30 км. от МКАД
            $deliveryIntervalsWeekdaysRaw = explode("\n", \COption::GetOptionString('jamilco.delivery', 'courier_intervals_weekdays_mo_30', ''));
            $deliveryIntervalsHolidaysRaw = explode("\n", \COption::GetOptionString('jamilco.delivery', 'courier_intervals_weekdays_mo_30', ''));
            $isAvailableTimes = true;
            $isMoscowOrMO = true;
        }
        if (!$isAvailableTimes) {
            $element = \CIBlockElement::GetList(
                [],
                [
                    'IBLOCK_ID'                         => IBLOCK_TARIFS_KCE,
                    '=PROPERTY_LOCATION_ID'             => $locationId,
                    '!PROPERTY_SHOW_DELIVERY_INTERVALS' => false,
                ],
                false,
                [
                    'nTopCount' => 1,
                ],
                [
                    'ID',
                ]
            )->Fetch();

            if ($element) {
                $isAvailableTimes = true;

                $deliveryIntervalsWeekdaysRaw = explode("\n", \COption::GetOptionString('jamilco.delivery', 'courier_intervals_weekdays_mo_30_to_100', ''));
                $deliveryIntervalsHolidaysRaw = explode("\n", \COption::GetOptionString('jamilco.delivery', 'courier_intervals_weekdays_mo_30_to_100', ''));
            }
        }

        if (!$isAvailableTimes) {
            return [];
        }

        $deliveryIntervalsWeekdays = [];
        $deliveryIntervalsHolidays = [];
        foreach ($deliveryIntervalsWeekdaysRaw as $deliveryInterval) {
            list($timeFrom, $timeTo) = explode('-', $deliveryInterval);
            $timeFrom = trim($timeFrom);
            $timeTo = trim($timeTo);

            $deliveryIntervalsWeekdays[] = [
                'value' => $timeFrom.'-'.$timeTo,
                'text' => $timeFrom.' - '.$timeTo,
            ];
        }
        foreach ($deliveryIntervalsHolidaysRaw as $deliveryInterval) {
            list($timeFrom, $timeTo) = explode('-', $deliveryInterval);
            $timeFrom = trim($timeFrom);
            $timeTo = trim($timeTo);

            $deliveryIntervalsHolidays[] = [
                'value' => $timeFrom.'-'.$timeTo,
                'text' => $timeFrom.' - '.$timeTo,
            ];
        }

        $currentYear = (int)date('Y');

        $courierHolidaysRaw = explode("\n", \COption::GetOptionString('jamilco.delivery', 'courier_holidays', ''));
        $courierHolidays = [];
        foreach ($courierHolidaysRaw as $holidayDate) {
            list($day, $month) = explode('.', $holidayDate);
            $day = (int)$day;
            $month = (int)$month;

            if (checkdate($month, $day, $currentYear)) {
                $courierHolidays[] = (($day < 10) ? '0' : '').$day.'.'.(($month < 10) ? '0' : '').$month.'.'.$currentYear;
            }
        }

        $denyDeliveryRaw = explode("\n", \COption::GetOptionString('jamilco.delivery', 'courier_intervals_deny_delivery', ''));
        $denyDelivery = [];
        foreach ($denyDeliveryRaw as $denyDeliveryDate) {
            list($day, $month) = explode('.', $denyDeliveryDate);
            $day = (int)$day;
            $month = (int)$month;

            if (checkdate($month, $day, $currentYear)) {
                $denyDelivery[] = (($day < 10) ? '0' : '').$day.'.'.(($month < 10) ? '0' : '').$month;
            }
        }

        $currentDay = new \DateTime();
        $isPlusDay = false;
        if ((int)$currentDay->format('H') >= 18) {
            $currentDay->modify('+1 day');
            $isPlusDay = true;
        }

        $deliveryTimes = [];

        $weekDays = [
            'Понедельник',
            'Вторник',
            'Среда',
            'Четверг',
            'Пятница',
            'Суббота',
            'Воскресенье',
        ];

        $months = [
            'Января',
            'Февраля',
            'Марта',
            'Апреля',
            'Мая',
            'Июня',
            'Июля',
            'Августа',
            'Сентября',
            'Октября',
            'Ноября',
            'Декабря',
        ];

        for ($i = 1; $i <= 7; $i++) {
            $day = $currentDay->modify('+1 day');
            $dayFormat = $day->format('d.m.Y');
            $dayFormatWithoutYear = $currentDay->format('d.m');
            if (in_array($dayFormatWithoutYear, $denyDelivery)) {
                $currentDay->modify('+1 day');
                continue;
            }
            $dayOfWeek = (int)$day->format('N');
            $month = (int)$day->format('n');

            $dayText =
                ((($i === 1) && !$isPlusDay) ? 'Завтра' : $weekDays[$dayOfWeek - 1]).', '
                .$day->format('j').' '.$months[$month - 1];

            $oneDeliveryTime = [
                'date' => [
                    'value' => $dayFormat,
                    'text' => $dayText,
                ],
                'time' => $deliveryIntervalsWeekdays,
            ];

            if (
                in_array($dayFormat, $courierHolidays)  //один из Праздничных дней
                || ($dayOfWeek >= 6)                    //суббота или воскресенье
            ) {
                $oneDeliveryTime['time'] = $deliveryIntervalsHolidays;
            }

            if ($dayFormat == '31.12.2022') {
                $oneDeliveryTime['time'] = [[
                    'value' => '09:00-14:00',
                    'text' => 'с 09:00 до 14:00',
                ]];
            }

            if ($dayFormat == '03.01.2023') {
                $deliveryUntil = false;
                if ($isMoscowOrMO) $deliveryUntil = \DateTime::createFromFormat('d.m.Y H:i:s', '30.12.2022 14:00:00');
                if ($isSpb) $deliveryUntil = \DateTime::createFromFormat('d.m.Y H:i:s', '29.12.2022 17:00:00');
                if (!$deliveryUntil || (new \DateTime()) > $deliveryUntil) {
                    $currentDay->modify('+1 day');
                    continue;
                }
            }

            $deliveryTimes[] = $oneDeliveryTime;
        }

        return $deliveryTimes;
    }
}