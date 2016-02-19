<?php

if(!defined("INCLUDE_CHECK")) { header('HTTP/1.0 404 File Not Found'); exit; }

session_start([
    'cookie_lifetime' => 86400,
]);

?>