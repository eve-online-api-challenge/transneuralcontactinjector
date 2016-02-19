<?php

if(!defined("INCLUDE_CHECK")) { header('HTTP/1.0 404 File Not Found'); exit; }
require_once("php_flibrary/transneural.php");

$code = getInput("code");

if($code == "") {
	$_SESSION['randState'] = hash('sha512',$_SERVER['REMOTE_ADDR'].time().rand(100000,99999999));
	redirect("https://login.eveonline.com/oauth/authorize/?response_type=code&redirect_uri=YOURSITE&client_id=YOURCLIENTID&scope=publicData characterContactsRead characterContactsWrite&state=".$_SESSION['randState']);
} else {
	$state = getInput("state");
	if($_SESSION['randState'] != $state) {
		error(3,"Log-in Failed. There was a session error and your login could not be confirmed.");
	} else {
		loginEveCharacter($code);
		error(1,"Log-in succeeded!");
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
Log-in succeeded, please close this window.