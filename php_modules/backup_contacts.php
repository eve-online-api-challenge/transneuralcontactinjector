<?php

if(!defined("INCLUDE_CHECK")) { header('HTTP/1.0 404 File Not Found'); exit; }

$characterID = (int) getInput("characterID");

$checkQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Character WHERE (userID=:userID AND characterID=:characterID) LIMIT 1");
$checkQuery->execute(array(":userID" => $_SESSION['userID'],":characterID" => $characterID));
if($checkQuery->rowCount() == 0) { echo "Bad Request."; die(); }
$character = $checkQuery->fetch();

$contactListQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Contact WHERE (characterID=:characterID)");
$contactListQuery->execute(array(":characterID" => $characterID));

$csvRpt = "\"ContactID\",\"Contact Name\",\"Contact Type\",\"Link\",\"Standing\",\"Watched\"\n";
if($contactListQuery->rowCount()) {
	while($contact = $contactListQuery->fetch()) {
		$csvRpt .= "\"".$contact['contactID']."\",\"".$contact['contactName']."\",\"".$contact['contactType']."\",\"".$contact['href']."\",".$contact['standing'].",".$contact['watched']."\n";
	}
}

header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=\"EvE - ".$character['characterName']." - Contacts.csv\"");
header("Pragma: no-cache");
header("Expires: 0");

echo $csvRpt;

?>