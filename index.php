<?php

ob_start();

ini_set("display_errors",TRUE);
error_reporting(E_ALL);
define("INCLUDE_CHECK",1);

require_once("../private/defines_eve.php");
require_once("php_global/defines.php");
require_once("php_global/globalfunctions.php");
require_once("php_global/dbasecontrol.php");
require_once("php_global/sessioncontrol.php");
require_once("php_global/pagedirectory.php");

ob_end_flush();

?>