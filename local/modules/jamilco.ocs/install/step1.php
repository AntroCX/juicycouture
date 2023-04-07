<?php
/**
 * Created by PhpStorm.
 * User: maxkrasnov
 * Date: 13.05.16
 * Time: 13:14
 */
if(!check_bitrix_sessid()) return;
IncludeModuleLangFile(__FILE__);
echo CAdminMessage::ShowNote(GetMessage('JAMILCO_OCS_INSTALL_SUCCESS'));
?>