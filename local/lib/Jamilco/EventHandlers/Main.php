<?php

namespace Jamilco\EventHandlers;


class Main
{

    public static function OnAfterUserAddHandler(&$arFields)
    {
        if ($arFields['ID'] > 0) {
            global $ddlEvents;
            $ddlEvents['sendRegistrationFormSuccess'] = "window.dataLayer = window.dataLayer || []; window.dataLayer.push({'event':'sendRegistrationFormSuccess'});";
            echo "<script>".$ddlEvents['sendRegistrationFormSuccess']."</script>";
        }
    }

    public static function OnAfterUserAuthorizeHandler()
    {
        global $ddlEvents;
        $ddlEvents['SendLoginFormSuccess'] = "window.dataLayer = window.dataLayer || [];window.dataLayer.push({'event':'sendLoginFormSuccess'});";
        echo "<script>".$ddlEvents['SendLoginFormSuccess']."</script>";
    }

}
