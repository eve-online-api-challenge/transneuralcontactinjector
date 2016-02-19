<?php

if(!defined("INCLUDE_CHECK")) { header('HTTP/1.0 404 File Not Found'); exit; }

$p = getInput("p");
$usrAction = getInput("usrAction");

ob_start();
$layoutType = 0;
switch($p) {

	// Modules
	case "evelogin": require_once("php_modules/evelogin.php"); break;

	// Pages
	case "merger": require_once("php_pages/merger.php"); $layoutType = 1; break;

	// Default Page
	default: require_once("php_pages/main.php"); $layoutType = 1; break;
}
$htmlBody = ob_get_clean();

switch($layoutType) {
	// Display the Core Layout
	case 1:
		print generateCoreLayout($pageTitle,$breadcrumb,$htmlBody);
		break;
	// Case 0/Default: Display just the HTML/Output. No formatting.
	case 0:
	default:
		print $htmlBody;
		break;
}

?>