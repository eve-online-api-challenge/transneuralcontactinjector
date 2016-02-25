<?php

/**************************************************[TRANSNEURAL.PHP]**
** This is the function script supporting the management of contacts
** in this system.
*********************************************************************/

if(!defined("INCLUDE_CHECK")) { header('HTTP/1.0 404 File Not Found'); exit; }

/************************************************[LOGIN TO CHARCTER]**
** This function logs a character into the system if it was
** successful. This function will always force a character update.
** If the user logged into their characters on separate occasions
** (sessions), then a merge function will be called.
*********************************************************************/
function loginEveCharacter($code) {

	$authorization = getAuthorization($code,"new");
	if($authorization === FALSE) { return FALSE; }
	$character = getCharacterInfo($authorization->access_token);
	if($character === FALSE) { return FALSE; }

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

	updateCharacter($character->CharacterID,$_SESSION['userID'],$character->CharacterName,$authorization->access_token,$authorization->expires_in,$authorization->refresh_token);

	if($findCharacterQuery->rowCount() != 0 AND $_SESSION['userID'] != $characterDB['userID']) {
		mergeAccounts($_SESSION['userID'],$characterDB['userID']);
	}

	updateContactList($character->CharacterID,$authorization->access_token);

	getPrimaryCharacter();

	return TRUE;
}

/**********************************************[ACCOUNT MANANGEMENT]**
** The following functions manage User Accounts (creation, deletion,
** and merging).
*********************************************************************/
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

/*****************************************[UPDATE CHARACTER DETAILS]**
** The following functions update the access tokens and contact list
** of the related character.
*********************************************************************/
function updateCharacter($characterID,$userID,$characterName,$accessToken,$expiration,$refreshToken) {
	$insertQuery = $GLOBALS['dbh']->prepare("INSERT INTO tci_Character (characterID,userID,characterName,accessToken,cacheTimer,refreshToken) VALUES (:characterID,:userID,:characterName,:accessToken,:cacheTimer,:refreshToken) ON DUPLICATE KEY UPDATE userID=:userID,characterName=:characterName,accessToken=:accessToken,cacheTimer=:cacheTimer,refreshToken=:refreshToken");
	$insertQuery->execute(array(":characterID" => $characterID,":userID" => $userID,":characterName" => $characterName,":accessToken" => $accessToken,":cacheTimer" => (time()+$expiration-20),":refreshToken" => $refreshToken));

	return;
}

function updateContactList($characterID,$accessToken) {
	$contactData = manageContactList($characterID,$accessToken,"GET");
	if($contactData === FALSE) { return FALSE; }
	$pageCount = (int) $contactData->pageCount;

	$deleteQuery = $GLOBALS['dbh']->prepare("DELETE FROM tci_Contact WHERE (characterID=:characterID)");
	$deleteQuery->execute(array(":characterID" => $characterID));

	processContactPage($characterID,$contactData);

	for($n=1;$n<$pageCount;$n++) {
		$nextPage = $contactData->next->href;
		$contactData = manageContactList($characterID,$accessToken,"GET",$nextPage);
		processContactPage($characterID,$contactData);
	}

	$updateQuery = $GLOBALS['dbh']->prepare("UPDATE tci_Character SET contactCacheTimer=:contactCacheTimer WHERE (characterID=:characterID)");
	$updateQuery->execute(array(":contactCacheTimer" => (time()+300),":characterID" => $characterID));

	return TRUE;
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

/****************************************[PRIMARY CHARACTER SETTING]**
** The following functions obtain and set the primary character which
** is used for the contact list mirroring function.
*********************************************************************/
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

/*********************************************[REFRESH CONTACT LIST]**
** The following function will refresh the character contact list.
** Can occur on demand or preceding a major contact list operation.
*********************************************************************/
function refreshContactList($characterID,$verbose = FALSE) {
	$checkQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Character WHERE (userID=:userID AND characterID=:characterID) LIMIT 1");
	$checkQuery->execute(array(":userID" => $_SESSION['userID'],":characterID" => $characterID));
	if($checkQuery->rowCount() == 0) { return FALSE; }
	$character = $checkQuery->fetch();

	if(time() <= $character['contactCacheTimer']) {
		if($verbose) { error(2,"Contact List cannot be refreshed as CREST is still cached."); }
		return FALSE;
	}

	if(time() > $character['cacheTimer']) {
		$accessToken = refreshCharacterToken($characterID);
	} else {
		$accessToken = $character['accessToken'];
	}
	if(updateContactList($characterID,$accessToken) === FALSE) {
		error(3,"Latest contact list could not be pulled.");
		return FALSE;
	}

	if($verbose) { error(1,"Contact List refreshed on selected character."); }
	return TRUE;
}

function refreshAllContacts($verbose = FALSE) {
	$characterQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Character WHERE (userID=:userID)");
	$characterQuery->execute(array(":userID" => $_SESSION['userID']));
	if($characterQuery->rowCount() == 0) { return FALSE; }

	while($character = $characterQuery->fetch()) {
		if(time() > $character['contactCacheTimer']) {
			if(time() > $character['cacheTimer']) {
				$accessToken = refreshCharacterToken($character['characterID']);
			} else {
				$accessToken = $character['accessToken'];
			}
			updateContactList($character['characterID'],$accessToken);
		}
	}

	if($verbose) { error(1,"All Contact Lists updated (for character contact lists NOT cached from CREST)."); }
	return TRUE;
}

/******************************************[CONTACT LIST MANAGEMENT]**
** The following functions enable management of the user's contact
** list(s). There are functions to add, update, delete individual
** contacts. There are also mass update operations available.
*********************************************************************/
function addContact($contactName,$contactType,$standing,$watchList,$toCharacters) {
	$characterID = searchCharacterAPI($contactName,$contactType);
	if($characterID === FALSE) { error(3,"Contact could not be found. Please try again."); return FALSE; }

	if(!in_array($standing,array(-10,-5,0,5,10))) { error(3,"An invalid standing was selected. Please try again."); return FALSE; }

	$contact = array();
	$contact['contactID'] = (int) $characterID;
	$contact['contactType'] = $contactType;
	$contact['contactName'] = $contactName;
	$contact['href'] = "https://crest-tq.eveonline.com/".strtolower($contactType)."s/".$characterID."/";
	$contact['standing'] = $standing;
	if($watchList == "on" AND $contactType == "Character") { $contact['watched'] = 1; } else { $contact['watched'] = 0; }

	$characterListQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Character WHERE (userID=:userID)");
	$characterListQuery->execute(array(":userID" => $_SESSION['userID']));
	$characterList = $characterListQuery->fetchAll();

	foreach($toCharacters AS $toCharacterID) {
		foreach($characterList AS $character) {
			if($character['characterID'] == $toCharacterID) {
				if(time() > $character['cacheTimer']) {
					$accessToken = refreshCharacterToken($character['characterID']);
				} else {
					$accessToken = $character['accessToken'];
				}
				addCharacterContact($character,$contact,$accessToken);
			}
		}
	}

	error(2,"Contact successfully pushed to CREST (excepting errors above). There may be a 5 minute delay until it is reflected in your Contact List.");
	return;
}

function editContact($characterID,$contactID,$standing,$watchList) {
	if(!in_array($standing,array(-10,-5,0,5,10))) { error(3,"An invalid standing was selected. Please try again."); return FALSE; }

	$contactQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Contact WHERE (contactID=:contactID AND characterID=:characterID) LIMIT 1");
	$contactQuery->execute(array(":contactID" => $contactID,":characterID" => $characterID));
	if($contactQuery->rowCount() == 0) { error(3,"Could not locate the contact for that character."); return; }
	$contact = $contactQuery->fetch();

	$contact['standing'] = $standing;
	if($watchList == "on" AND $contact['contactType'] == "Character") { $contact['watched'] = 1; } else { $contact['watched'] = 0; }

	$characterQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Character WHERE (characterID=:characterID) LIMIT 1");
	$characterQuery->execute(array(":characterID" => $characterID));
	if($characterQuery->rowCount() == 0) { error(3,"System error- nag the administrator."); return; }
	$character = $characterQuery->fetch();

	if(time() > $character['cacheTimer']) {
		$accessToken = refreshCharacterToken($character['characterID']);
	} else {
		$accessToken = $character['accessToken'];
	}
	$retr = addCharacterContact($character,$contact,$accessToken);

	if($retr !== FALSE) { error(2,"Contact successfully updated to CREST. There may be a 5 minute delay until it is reflected in your Contact List."); }
	return;
}

function operateContact($characterID,$contactID,$operation) {
	switch($operation) {
		case 1:
			$contactQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Contact WHERE (contactID=:contactID AND characterID=:characterID) LIMIT 1");
			$contactQuery->execute(array(":contactID" => $contactID,":characterID" => $characterID));
			if($contactQuery->rowCount() == 0) { error(3,"Could not locate the contact for that character."); return; }
			$contact = $contactQuery->fetch();

			$characterQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Character WHERE (characterID=:characterID) LIMIT 1");
			$characterQuery->execute(array(":characterID" => $characterID));
			if($characterQuery->rowCount() == 0) { error(3,"System error- nag the administrator."); return; }
			$character = $characterQuery->fetch();

			if(time() > $character['cacheTimer']) {
				$accessToken = refreshCharacterToken($character['characterID']);
			} else {
				$accessToken = $character['accessToken'];
			}
			deleteCharacterContact($character,$contact,$accessToken);

			break;
		case 2:
			$characterListQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Character WHERE (userID=:userID)");
			$characterListQuery->execute(array(":userID" => $_SESSION['userID']));
			if($characterListQuery->rowCount() == 0) { error(3,"System error- nag the administrator."); return; }
			$characterList = $characterListQuery->fetchAll();

			foreach($characterList AS $character) {
				$contactQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Contact WHERE (contactID=:contactID AND characterID=:characterID) LIMIT 1");
				$contactQuery->execute(array(":contactID" => $contactID,":characterID" => $character['characterID']));
				if($contactQuery->rowCount() > 0) {
					$contact = $contactQuery->fetch();

					if(time() > $character['cacheTimer']) {
						$accessToken = refreshCharacterToken($character['characterID']);
					} else {
						$accessToken = $character['accessToken'];
					}
					deleteCharacterContact($character,$contact,$accessToken);
				}
			}

			break;
		case 3:
			$contactQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Contact WHERE (contactID=:contactID AND characterID=:characterID) LIMIT 1");
			$contactQuery->execute(array(":contactID" => $contactID,":characterID" => $characterID));
			if($contactQuery->rowCount() == 0) { error(3,"Could not locate the contact for that character."); return; }
			$contact = $contactQuery->fetch();

			$characterListQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Character WHERE (userID=:userID AND characterID!=:characterID)");
			$characterListQuery->execute(array(":userID" => $_SESSION['userID'],":characterID" => $characterID));
			if($characterListQuery->rowCount() == 0) { error(3,"You do not have other characters to copy that contact to."); return; }
			$characterList = $characterListQuery->fetchAll();

			foreach($characterList AS $character) {
				if(time() > $character['cacheTimer']) {
					$accessToken = refreshCharacterToken($character['characterID']);
				} else {
					$accessToken = $character['accessToken'];
				}
				addCharacterContact($character,$contact,$accessToken);
			}

			break;
		default: error(3,"Invalid operation selected.");
	}

	error(2,"Contact Operation successfully updated to CREST (excepting errors noted above). There may be a 5 minute delay until it is reflected in your Contact List.");
	return;
}

function addContactMass($contactList,$toCharacters) {
	set_time_limit(0);
	$contactList = explode("\n",$contactList);
	if(trim($contactList[0]) == "\"ContactID\",\"Contact Name\",\"Contact Type\",\"Link\",\"Standing\",\"Watched\"") { array_shift($contactList); }

	$characterListQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Character WHERE (userID=:userID)");
	$characterListQuery->execute(array(":userID" => $_SESSION['userID']));
	$characterList = $characterListQuery->fetchAll();

	$contactListImport = array();

	foreach($contactList AS $contactLine) {
		$contactLineParse = str_getcsv($contactLine);
		if(count($contactLineParse) == 6) {
			$contact = array();
			$contact['contactID'] = (int) $contactLineParse[0];
			$contact['contactType'] = $contactLineParse[2];
			$contact['contactName'] = $contactLineParse[1];
			$contact['href'] = $contactLineParse[3];
			$contact['standing'] = (float) $contactLineParse[4];
			$contact['watched'] = (int) $contactLineParse[5];
			$contactListImport[] = $contact;
		}
	}

	foreach($toCharacters AS $toCharacterID) {
		foreach($characterList AS $character) {
			if($character['characterID'] == $toCharacterID) {
				if(time() > $character['cacheTimer']) {
					$accessToken = refreshCharacterToken($character['characterID']);
				} else {
					$accessToken = $character['accessToken'];
				}
				foreach($contactListImport AS $contact) {
					addCharacterContact($character,$contact,$accessToken);
				}
			}
		}
	}

	error(2,"All contacts successfully pushed to CREST (excepting errors noted above). There may be a 5 minute delay until it is reflected in your Contact List.");
	return;
}

function addCharacterContact($toCharacter,$contact,$accessToken) {
	$characterJsonStr = prepareCharacterJSON($contact);
	$retr = manageContactList($toCharacter['characterID'],$accessToken,"PUSH",$characterJsonStr);

	return $retr;
}

function deleteCharacterContact($toCharacter,$toContact,$accessToken) {
	$retr = manageContactList($toCharacter['characterID'],$accessToken,"DELETE",$toContact['contactID']);
	return $retr;
}

function prepareCharacterJSON($contact) {
	$characterJson = new stdClass();
	$characterJson->standing = (float) $contact['standing'];
	$characterJson->contactType = $contact['contactType'];
	$characterJson->contact = new stdClass();
	$characterJson->contact->id_str = sprintf($contact['contactID']);
	$characterJson->contact->href = $contact['href'];
	$characterJson->contact->name = $contact['contactName'];
	$characterJson->contact->id = (int) $contact['contactID'];
	if($contact['watched']) { $characterJson->watched = true; } else { $characterJson->watched = false; }

	$characterJsonStr = json_encode($characterJson);
	unset($characterJson);
	return $characterJsonStr;
}

function mergeCharacterContacts() {
	set_time_limit(0);
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

		if(time() > $toCharacter['cacheTimer']) {
			$accessToken = refreshCharacterToken($toCharacter['characterID']);
		} else {
			$accessToken = $toCharacter['accessToken'];
		}

		refreshContactList($toCharacter['characterID']);

		// Remove Existing Contacts
		$toContactListQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Contact WHERE (characterID=:characterID)");
		$toContactListQuery->execute(array(":characterID" => $toCharacter['characterID']));
		if($toContactListQuery->rowCount()) {
			while($toContact = $toContactListQuery->fetch()) {
				deleteCharacterContact($toCharacter,$toContact,$accessToken);
			}
		}

		// Add New Contacts
		foreach($contactList AS $contact) {
			addCharacterContact($toCharacter,$contact,$accessToken);
		}

	}

	error(2,"Character contacts mirroring successful (excepting errors noted above). Please note that there is a 5 minute delay in CREST updates so the contact lists you currently see may not be accurate. Please refresh the lists in 5 minutes to confirm update or just check in game.");
	return;
}



?>