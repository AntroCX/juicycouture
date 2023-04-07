<?
\Bitrix\Main\Loader::registerAutoLoadClasses(
    "jamilco.loyalty",
    array(
        "Jamilco\\Loyalty\\Events"     => "lib/events.php",
        "Jamilco\\Loyalty\\Common"     => "lib/common.php",
        "Jamilco\\Loyalty\\Card"       => "lib/card.php",
        "Jamilco\\Loyalty\\Bonus"      => "lib/bonus.php",
        "Jamilco\\Loyalty\\BonusOrder" => "lib/bonusorder.php",
        "Jamilco\\Loyalty\\Log"        => "lib/log.php",
        "Jamilco\\Loyalty\\Gift"       => "lib/gift.php",
    )
);