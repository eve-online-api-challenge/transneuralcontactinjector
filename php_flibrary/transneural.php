<?php

function loginEveCharacter($code) {

	$authorization = getAuthorization($code);
	$character = getCharacterInfo($authorization->access_token);

	$findCharacterQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Character WHERE (characterID=:characterID) LIMIT 1");
	$findCharacterQuery->execute(array(":characterID" => $character->CharacterID));
	if($findCharacterQuery->rowCount()) {
		$characterDB = $findCharacterQuery->fetch();
	}

	if(!isset($_SESSION['userID'])) {
		if($findCharacterQuery->rowCount() == 0) {
			$_SESSION['userID'] = createNewAccount();
		} else {
			$_SESSION['userID'] = $characterDB['userID'];
			getPrimaryCharacter();
		}
	}

	updateCharacter($character->CharacterID,$_SESSION['userID'],$character->CharacterName,$authorization->access_token,$authorization->refresh_token);

	if($findCharacterQuery->rowCount() != 0 AND $_SESSION['userID'] != $characterDB['userID']) {
		mergeAccounts($_SESSION['userID'],$characterDB['userID']);
	}

	updateContactList($character->CharacterID,$authorization->access_token);

	return;
}

function createNewAccount() {
	$insertQuery = $GLOBALS['dbh']->prepare("INSERT INTO tci_User (userID) VALUES ('')");
	$insertQuery->execute();
	return $GLOBALS['dbh']->lastInsertId();
}

function deleteAccount() {
	if(!isset($_SESSION['userID'])) { error(3,"Not logged in."); return FALSE; }

	$deleteQuery = $GLOBALS['dbh']->prepare("DELETE FROM tci_User WHERE (userID=:userID) LIMIT 1");
	$deleteQuery->execute(array(":userID" => $_SESSION['userID']));

	return TRUE;
}

function mergeAccounts($toUID,$fromUID) {
	$updateQuery = $GLOBALS['dbh']->prepare("UPDATE tci_Character SET userID=:toUID WHERE (userID=:fromUID)");
	$updateQuery->execute(array(":toUID" => $toUID,":fromUID" => $fromUID));

	$deleteQuery = $GLOBALS['dbh']->prepare("DELETE FROM tci_User WHERE (userID=:fromUID) LIMIT 1");
	$deleteQuery->execute(array(":fromUID" => $fromUID));

	return;
}

function updateCharacter($characterID,$userID,$characterName,$accessToken,$refreshToken) {
	$insertQuery = $GLOBALS['dbh']->prepare("INSERT INTO tci_Character (characterID,userID,characterName,accessToken,refreshToken) VALUES (:characterID,:userID,:characterName,:accessToken,:refreshToken) ON DUPLICATE KEY UPDATE userID=:userID,characterName=:characterName,accessToken=:accessToken,refreshToken=:refreshToken");
	$insertQuery->execute(array(":characterID" => $characterID,":userID" => $userID,":characterName" => $characterName,":accessToken" => $accessToken,":refreshToken" => $refreshToken));

	return;
}

function updateContactList($characterID,$accessToken) {
	$contactData = getContactList($characterID,$accessToken);
	$pageCount = (int) $contactData->pageCount;

	$deleteQuery = $GLOBALS['dbh']->prepare("DELETE FROM tci_Contact WHERE (characterID=:characterID)");
	$deleteQuery->execute(array(":characterID" => $characterID));

	processContactPage($characterID,$contactData);

	for($n=1;$n<$pageCount;$n++) {
		$nextPage = $contactData->next->href;
		$contactData = getContactList($characterID,$accessToken,$nextPage);
		processContactPage($characterID,$contactData);
	}

	return;
}

function processContactPage($characterID,$contactPage) {
	for($n=0;$n<count($contactPage->items);$n++) {
		$contactID		= (int) $contactPage->items[$n]->contact->id;
		$contactName 	= $contactPage->items[$n]->contact->name;
		$contactType 	= $contactPage->items[$n]->contactType;
		$standing 		= (float) $contactPage->items[$n]->standing;
		$href 			= $contactPage->items[$n]->contact->href;

		if($contactType == "Character") {
			$watched = (int) $contactPage->items[$n]->watched;
		} else {
			$watched = 0;
		}

		$insertQuery = $GLOBALS['dbh']->prepare("INSERT INTO tci_Contact (contactID,characterID,contactName,contactType,standing,watched,href) VALUES (?,?,?,?,?,?,?)");

			$insertQuery->bindValue(1,$contactID,PDO::PARAM_INT);
			$insertQuery->bindValue(2,$characterID,PDO::PARAM_INT);
			$insertQuery->bindValue(3,$contactName,PDO::PARAM_STR);
			$insertQuery->bindValue(4,$contactType,PDO::PARAM_STR);
			$insertQuery->bindValue(5,$standing,PDO::PARAM_STR);
			$insertQuery->bindValue(6,$watched,PDO::PARAM_INT);
			$insertQuery->bindValue(7,$href,PDO::PARAM_STR);

		$insertQuery->execute();
	}
	return;
}

function refreshContactList($characterID) {
	$accessToken = refreshCharacterToken($characterID);
	if($accessToken) {
		updateContactList($characterID,$accessToken);
	}
	error(1,"Contact List refreshed on selected character.");
	return;
}

function getPrimaryCharacter() {
	$checkQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_User WHERE (userID=:userID) LIMIT 1");
	$checkQuery->execute(array(":userID" => $_SESSION['userID']));

	if($checkQuery->rowCount() == 0) { return; }
	$c = $checkQuery->fetch();

	if((int) $c['primaryCharID'] > 0) { $_SESSION['primaryCharID'] = $c['primaryCharID']; }
	return;
}

function setPrimaryCharacter($characterID) {
	if(!isset($_SESSION['userID'])) { return; }

	$checkQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Character WHERE (userID=:userID AND characterID=:characterID) LIMIT 1");
	$checkQuery->execute(array(":userID" => $_SESSION['userID'],":characterID" => $characterID));

	if($checkQuery->rowCount() == 0) { error(3,"Character not found. If you're seeing this error, there was a glitch."); return; }

	$updateQuery = $GLOBALS['dbh']->prepare("UPDATE tci_User SET primaryCharID=:characterID WHERE (userID=:userID) LIMIT 1");
	$updateQuery->execute(array(":userID" => $_SESSION['userID'],":characterID" => $characterID));
	$_SESSION['primaryCharID'] = $characterID;

	return;
}

function removeCharacterFromAccount($characterID) {
	if(!isset($_SESSION['userID'])) { return; }

	$checkQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Character WHERE (userID=:userID AND characterID=:characterID) LIMIT 1");
	$checkQuery->execute(array(":userID" => $_SESSION['userID'],":characterID" => $characterID));

	if($checkQuery->rowCount() == 0) { error(3,"Character not found. If you're seeing this error, there was a glitch."); return; }

	$deleteQuery = $GLOBALS['dbh']->prepare("DELETE FROM tci_Character WHERE (userID=:userID AND characterID=:characterID) LIMIT 1");
	$deleteQuery->execute(array(":userID" => $_SESSION['userID'],":characterID" => $characterID));

	if($_SESSION['primaryCharID'] == $characterID) {
		$updateQuery = $GLOBALS['dbh']->prepare("UPDATE tci_User SET primaryCharID=NULL WHERE (userID=:userID) LIMIT 1");
		$updateQuery->execute(array(":userID" => $_SESSION['userID'],":characterID" => $characterID));
		unset($_SESSION['primaryCharID']);
	}

	return;
}

function mergeCharacterContacts() {
	$checkQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Character WHERE (userID=:userID AND characterID=:characterID) LIMIT 1");
	$checkQuery->execute(array(":userID" => $_SESSION['userID'],":characterID" => $_SESSION['primaryCharID']));
	if($checkQuery->rowCount() == 0) { error(3,"Primary Character not found on Account. May need to set Primary Character."); return; }

	refreshContactList($_SESSION['primaryCharID']);

	$contactListQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Contact WHERE (characterID=:characterID)");
	$contactListQuery->execute(array(":characterID" => $_SESSION['primaryCharID']));
	if($contactListQuery->rowCount() == 0) { error(3,"Character does not have contacts to be mirrored."); return; }
	$contactList = $contactListQuery->fetchAll();

	$toCharacterListQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Character WHERE (userID=:userID AND characterID!=:characterID)");
	$toCharacterListQuery->execute(array(":userID" => $_SESSION['userID'],":characterID" => $_SESSION['primaryCharID']));
	if($toCharacterListQuery->rowCount() == 0) { error(3,"No other characters to mirror your contact list to."); return; }
	$toCharacterList = $toCharacterListQuery->fetchAll();

	foreach($toCharacterList AS $toCharacter) {

		$accessToken = refreshCharacterToken($toCharacter['characterID']);

		// Remove Existing Contacts
		$toContactListQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Contact WHERE (characterID=:characterID)");
		$toContactListQuery->execute(array(":characterID" => $toCharacter['characterID']));
		if($toContactListQuery->rowCount()) {
			while($toContact = $toContactListQuery->fetch()) {
				deleteCharacterContactDB($toCharacter,$toContact,$accessToken);
			}
		}

		// Add New Contacts
		foreach($contactList AS $contact) {
			addCharacterContactDB($toCharacter,$contact,$accessToken);
		}

	}

	error(2,"Character contacts mirroring successful. Please note that there is a 15 minute delay in CREST updates so the contact lists you currently see may not be accurate. Please refresh the lists in 5 minutes to confirm update or just check in game.");
	return;
}

function addCharacterContactDB($toCharacter,$contact,$accessToken) {

	$characterJson = new stdClass();
	$characterJson->standing = (float) $contact['standing'];
	$characterJson->contactType = $contact['contactType'];
	$characterJson->contact = new stdClass();
	$characterJson->contact->id_str = $contact['contactID'];
	$characterJson->contact->href = $contact['href'];
	$characterJson->contact->name = $contact['contactName'];
	$characterJson->contact->id = (int) $contact['contactID'];
	if($contact['watched']) { $characterJson->watched = true; } else { $characterJson->watched = false; }

	$characterJsonStr = json_encode($characterJson);
	unset($characterJson);

	$retr = addCharacterContact($toCharacter['characterID'],$contact['contactID'],$accessToken,$characterJsonStr);

	return;
}

function deleteCharacterContactDB($toCharacter,$toContact,$accessToken) {
	deleteCharacterContact($toCharacter['characterID'],$toContact['contactID'],$accessToken);
	return;
}

?>