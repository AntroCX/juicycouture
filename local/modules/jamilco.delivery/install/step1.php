<?php
if(!check_bitrix_sessid()) return;
IncludeModuleLangFile(__FILE__);
echo CAdminMessage::ShowNote(GetMessage('JAMILCO_DELIVERY_INSTALL_SUCCESS'));
?>