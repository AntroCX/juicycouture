<?php
namespace Jamilco\OCS;

class System {
    static function block() {
        //if (\COption::SetOptionString("main", "ORACLE_OCS_BLOCK", true))
            echo '<result>OK</result>';
    }

    static function release() {
        //if (\COption::SetOptionString("main", "ORACLE_OCS_BLOCK", false))
            echo '<result>OK</result>';
    }
}