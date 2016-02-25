<?php

if(!defined("INCLUDE_CHECK")) { header('HTTP/1.0 404 File Not Found'); exit; }

/********************************************[BASIC INPUT FUNCTIONS]**
** Functions used to pull user input from $_POST and $_GET
*********************************************************************/

function getInput($fieldName) {
	if(isset($_POST[$fieldName])) { return $_POST[$fieldName]; }
	if(isset($_GET[$fieldName])) { return $_GET[$fieldName]; }
	return "";
}

function textFormat($input,$maxLen = 0) {
	$str = htmlspecialchars(trim($input),ENT_COMPAT,"UTF-8");
	if($maxLen > 0 AND strlen($str) > $maxLen) { return FALSE; } else { return $str; }
}

function reverseFormat($input) {
	return htmlspecialchars_decode($input,ENT_COMPAT);
}

function jsNameFormat($input) {
	return str_replace("'","\'",$input);
}

/**************************************[LAYOUT GENERATION FUNCTIONS]**
** Functions used to generate the layout and handle redirects.
*********************************************************************/

function generateCoreLayout($pageTitle,$breadcrumb,$htmlBody) {
	header("Cache-Control: no-cache");
	$htmlFull = file_get_contents("ui_layout/core_layout.html");
	$htmlFull = str_replace("<%%_PAGE_TITLE_%%>",$pageTitle,$htmlFull);
	$htmlFull = str_replace("<%%_BREADCRUMB_%%>",makeBreadcrumb($breadcrumb),$htmlFull);
	$htmlFull = str_replace("<%%_ERROR_LIST_%%>",displayErrors(),$htmlFull);
	$htmlFull = str_replace("<%%_PAGE_BODY_%%>",$htmlBody,$htmlFull);
	return $htmlFull;
}

function makeBreadcrumb($linkList) {
	$breadcrumbHtml =  "<a href=\"index.php\">EvE Custom Tools</a> &gt; ";
	for($n=0;$n<count($linkList);$n++) {
		if(  isset($linkList[$n+1])  ) {
			$breadcrumbHtml .= "<a href=\"".$linkList[$n]."\">".$linkList[$n+1]."</a> &gt; ";
			$n++;
		} else {
			$breadcrumbHtml .= $linkList[$n];
		}
	}
	return $breadcrumbHtml;
}

function redirect($link) {
	if($link == "") {
		header("Location: index.php"); die();
	} else {
		header("Location: ".$link); die();
	}
}

/**************************************************[ERROR FUNCTIONS]**
** These functions handle error/success messages. The following
** errorStatus are used:
** 1: Success  2: Warning  3: Fatal Error
** Since errors stack, the worst error code takes priority.
*********************************************************************/

function error($status,$reason) {
	if(  !isset($_SESSION['error']) ) { $_SESSION['error'] = array(); }
	$_SESSION['error'][] = array($status,$reason);
}

function displayErrors() {
	$errorHtml = "";
	if(  isset($_SESSION['error'])  ) {
		foreach($_SESSION['error'] AS $k => $eInfo) {
			$errorHtml .= "<tr><td colspan=\"2\" style=\"padding: 10px;\">";
			$errorHtml .= displaySingleError($eInfo);
			$errorHtml .= "</td></tr>";
		}
	}
	unset($_SESSION['error']);
	return $errorHtml;
}

function displaySingleError($eInfo) {
	$errorHtml = file_get_contents("ui_layout/error.html");
	$errorHtml = str_replace("<%%_ERROR_STATUS_%%>",$eInfo[0],$errorHtml);
	$errorHtml = str_replace("<%%_ERROR_STATUS_NAME_%%>",displayErrorStatus($eInfo[0]),$errorHtml);
	$errorHtml = str_replace("<%%_ERROR_%%>",$eInfo[1],$errorHtml);
	return $errorHtml;
}

function displayErrorStatus($eStatus) {
	switch($eStatus) {
		case 1: return "Completed"; break;
		case 2: return "Warning"; break;
		default: return "Error"; break;
	}
	return "";
}

/***************************************************[CURL FUNCTIONS]**
** These functions pull data from the API.
*********************************************************************/

// Return Authorization Details from Login
function getAuthorization($code,$type = "new") {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,"https://login.eveonline.com/oauth/token");
	curl_setopt($ch, CURLOPT_POST, 1);
	if($type == "new") {
		curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=authorization_code&code=".$code);
	} else {
		curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=refresh_token&refresh_token=".$code);
	}
	$b64 = "Basic ".AUTH_TOKEN;
	curl_setopt($ch,CURLOPT_HTTPHEADER,array('Authorization: '.$b64,'Content-Type: application/x-www-form-urlencoded','Host: login.eveonline.com'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$serverOutput = curl_exec ($ch);
	curl_close ($ch);
	$serverJSON = json_decode($serverOutput);
	if(!isset($serverJSON->access_token) OR $serverJSON->access_token == null) {
		return FALSE;
	} else {
		return $serverJSON;
	}
}

// Return Character Info
function getCharacterInfo($accessToken) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,"https://login.eveonline.com/oauth/verify");
	$auth = "Bearer ".$accessToken;
	curl_setopt($ch,CURLOPT_HTTPHEADER,array('Authorization: '.$auth,'Host: login.eveonline.com'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$serverOutput = curl_exec ($ch);
	curl_close ($ch);
	$serverJSON = json_decode($serverOutput);
	if(!isset($serverJSON->CharacterID) OR $serverJSON->CharacterID == null) {
		return FALSE;
	} else {
		return $serverJSON;
	}

	return json_decode($serverOutput);
}

// Refresh Character Token - If Expired, get a New Access Token
function refreshCharacterToken($characterID) {
	$checkQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Character WHERE (userID=:userID AND characterID=:characterID) LIMIT 1");
	$checkQuery->execute(array(":userID" => $_SESSION['userID'],":characterID" => $characterID));

	if($checkQuery->rowCount() == 0) { return FALSE; }
	$character = $checkQuery->fetch();

	$authorization = getAuthorization($character['refreshToken'],"refresh");
	if($authorization === FALSE OR !isset($authorization->refresh_token)) { return FALSE; }

	$updateQuery = $GLOBALS['dbh']->prepare("UPDATE tci_Character SET accessToken=:accessToken,accessToken=:accessToken,cacheTimer=:cacheTimer,refreshToken=:refreshToken WHERE (characterID=:characterID) LIMIT 1");
	$updateQuery->execute(array(":accessToken" => $authorization->access_token,":cacheTimer" => (time()+$authorization->expires_in-20),":refreshToken" => $authorization->refresh_token,":characterID" => $characterID));

	return $authorization->access_token;
}

// Contacts Management Function (Performs all four types of functions for the Character Contacts Root
function manageContactList($characterID,$accessToken,$actionType,$sendInfo = FALSE) {
	$ch = curl_init();

	$rootPath = "https://crest-tq.eveonline.com/characters/".$characterID."/contacts/";
	$auth = "Bearer ".$accessToken;

	switch($actionType) {
		case "GET":
			if($sendInfo) {
				curl_setopt($ch, CURLOPT_URL,$sendInfo);
			} else {
				curl_setopt($ch, CURLOPT_URL,$rootPath);
			}
			curl_setopt($ch,CURLOPT_HTTPHEADER,array('Authorization: '.$auth,'Host: crest-tq.eveonline.com'));
			break;
		case "PUSH":
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $sendInfo);
			curl_setopt($ch, CURLOPT_URL,$rootPath);
			curl_setopt($ch,CURLOPT_HTTPHEADER,array('Authorization: '.$auth,'Host: crest-tq.eveonline.com','Content-Type: application/json'));
			break;
		case "DELETE":
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($ch, CURLOPT_URL,$rootPath.$sendInfo."/");
			curl_setopt($ch,CURLOPT_HTTPHEADER,array('Authorization: '.$auth,'Host: crest-tq.eveonline.com'));
			break;
		default: return FALSE;
	}

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$serverOutput = curl_exec ($ch);
	curl_close ($ch);

	$serverJSON = json_decode($serverOutput);
	if(isset($serverJSON->exceptionType) AND $serverJSON->exceptionType == "UnauthorizedError") { error(3,"Authentication Error. Nag the administrator to fix it."); return FALSE; }
	if(isset($serverJSON->exceptionType) AND $serverJSON->exceptionType == "UnsupportedMediaTypeError") { error(3,"Bad JSON Error. Nag the administrator to fix it."); return FALSE; }
	if(isset($serverJSON->exceptionType) AND $serverJSON->key == "ContactsAddFull") { error(3,"You have reached the Contact List cap of 1024 Contacts on that character. You must delete contacts in order to make room for more."); return FALSE; }

	return $serverJSON;
}

function searchCharacterAPI($contactName,$contactType) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,"https://api.eveonline.com/eve/OwnerID.xml.aspx?names=".urlencode($contactName));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$serverOutput = curl_exec ($ch);
	curl_close ($ch);
	$serverXML = simplexml_load_string($serverOutput);
	if(!isset($serverXML->result->rowset->row)) { return FALSE; }
	if(($contactType == "Character" AND $serverXML->result->rowset->row['ownerGroupID'] == "1") OR ($contactType == "Corporation" AND $serverXML->result->rowset->row['ownerGroupID'] == "2") OR ($contactType == "Alliance" AND $serverXML->result->rowset->row['ownerGroupID'] == "32")) {
		return $serverXML->result->rowset->row['ownerID'];
	}
	return FALSE;
}


?>