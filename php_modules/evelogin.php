<?php

if(!defined("INCLUDE_CHECK")) { header('HTTP/1.0 404 File Not Found'); exit; }
require_once("php_flibrary/transneural.php");

$code = getInput("code");

if($code == "") {
	$_SESSION['randState'] = hash('sha512',$_SERVER['REMOTE_ADDR'].time().rand(100000,99999999));
	redirect("https://login.eveonline.com/oauth/authorize/?response_type=code&redirect_uri=https://www.wowreports.com/charcopy/index.php?p=evelogin&client_id=fdb3c22c77fc4b9ba2828e9c364f1f8d&scope=publicData characterContactsRead characterContactsWrite&state=".$_SESSION['randState']);
} else {
	$state = getInput("state");
	if(!isset($_SESSION['randState'])) {
		error(3,"Log-in Failed. There was a session error - Could not obtain your session, likely due to cookie mismatch. Please ensure you are accessing this page with the www subdomain prefix (e.g., www.wowreports.com and not wowreports.com).");
		echo "There was a session error - Could not obtain your session, likely due to cookie mismatch. Please ensure you are accessing this page with the www subdomain prefix (e.g., www.wowreports.com and not wowreports.com).";
	} elseif($_SESSION['randState'] != $state) {
		error(3,"Log-in Failed. There was a session error - Could not confirm validity of the return message from CCP.");
		echo "Log-in Failed. There was a session error - Could not confirm validity of the return message from CCP.";
	} else {
		loginEveCharacter($code);
		error(1,"Log-in succeeded!");
		echo "Log-in succeeded, please close this window.";
	}
}

?>
<script type="text/javascript" src="ui_javascript/jquery.js"></script>
<script type="text/javascript">
$(document).ready(function(){
	window.opener.location.reload(false);
	window.close();
});
</script>