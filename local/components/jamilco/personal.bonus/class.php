<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Jamilco\Loyalty;

class CBitrixPersonalBonuses extends CBitrixComponent
{
    public function executeComponent()
    {
        global $USER;
        $this->arResult = [];
        if ($USER->isAuthorized()) {
            $this->arResult['USER'] = \CUser::GetByID($USER->GetID())->Fetch();
            if ($cardNumber = $this->arResult['USER']['UF_BONUS_CARD_NUMBER']) {
                $arCardData = Loyalty\Card::getBalance($cardNumber, true);
                $this->arResult['number'] = $cardNumber;
                $this->arResult['balance'] = $arCardData;

                $contactSkipCache = false; // контактные данные должны сразу измениться после изменений
                $this->arResult['data'] = Loyalty\Card::getClientData($cardNumber, false, $contactSkipCache, true);
                $arFio = explode(' ', $this->arResult['data']['FIO']);
                foreach ($arFio as $key => $val) {
                    $val = ToLower($val);
                    $val = ToUpper(substr($val, 0, 1)).substr($val, 1);

                    $arFio[$key] = $val;
                }
                $this->arResult['data']['LAST_NAME'] = $arFio[0];
                $this->arResult['data']['NAME'] = $arFio[1];
                $this->arResult['data']['SECOND_NAME'] = $arFio[2];

                $this->arResult['data']['BIRTHDAY'] = str_replace('.', '/', $this->arResult['data']['BIRTHDAY']);

                $this->arResult['data']['PHONE'] = '+'.$this->arResult['data']['PHONE'];

                $historySkipCache = false; // сбросить кеш истории по карте
                $arCardHistory = Loyalty\Card::getCardHistory($cardNumber, $historySkipCache);
                $this->arResult['history'] = $arCardHistory['HISTORY'];

                $arCheckHistory = Loyalty\Card::getCheckHistory($cardNumber, $historySkipCache);
                $this->arResult['check'] = $arCheckHistory['HISTORY'];
            } else {
                $this->arResult['SECURE'] = (Loyalty\Log::getInstance()->checkSecure()) ? 'Y' : 'N';
            }
        }

        $this->includeComponentTemplate();
    }
}