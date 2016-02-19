<?php

if(!defined("INCLUDE_CHECK")) { header('HTTP/1.0 404 File Not Found'); exit; }
require_once("php_flibrary/transneural.php");

$pageTitle = "Transneural Contact Injector";
$breadcrumb = array($pageTitle);
if(!isset($_SESSION['userID'])) {
	redirect("index.php?p=main");
}

if($usrAction == "set_primary") {
	$characterID = getInput("characterID");
	setPrimaryCharacter($characterID);
	redirect("index.php?p=merger");
} elseif($usrAction == "remove_character") {
	$characterID = getInput("characterID");
	removeCharacterFromAccount($characterID);
	redirect("index.php?p=merger");
} elseif($usrAction == "refresh_contacts") {
	$characterID = getInput("characterID");
	refreshContactList($characterID);
	redirect("index.php?p=merger");
} elseif($usrAction == "merge_contacts") {
	$confirm_merge = getInput("confirm_merge");
	mergeCharacterContacts();
	redirect("index.php?p=merger");
} elseif($usrAction == "logout") {
	$confirm_logout = getInput("confirm_logout");
	if($confirm_logout) {
		session_unset();
		session_destroy();
		redirect("index.php?p=main");
	} else {
		error(3,"Please check off the checkbox to confirm your logout.");
		redirect("index.php?p=merger");
	}
} elseif($usrAction == "wipe") {
	$confirm_wipe = getInput("confirm_wipe");
	if($confirm_wipe) {
		if(deleteAccount()) {
			session_unset();
			session_destroy();
			redirect("index.php?p=main");
		} else {
			redirect("index.php?p=merger");
		}
	} else {
		error(3,"Please check off the checkbox to confirm wiping your data on this site.");
		redirect("index.php?p=merger");
	}
}

$characterListQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Character WHERE (userID=:userID)");
$characterListQuery->execute(array(":userID" => $_SESSION['userID']));
$characterList = $characterListQuery->fetchAll();

$characterContactGrid = array();
foreach($characterList AS $character) {
	$contactListQuery = $GLOBALS['dbh']->prepare("SELECT * FROM tci_Contact WHERE (characterID=:characterID)");
	$contactListQuery->execute(array(":characterID" => $character['characterID']));
	if($contactListQuery->rowCount()) {
		while($contact = $contactListQuery->fetch()) {
			$contactID = $contact['contactID'];
			if(!isset($characterContactGrid[$contactID])) { $characterContactGrid[$contactID] = $contact; }
			$characterContactGrid[$contactID][$contact['characterID']] = 1;
		}
	}
}

?>
<script type="text/javascript" src="ui_javascript/transneural.js"></script>
<table style="width: 100%;" cellpadding="0" cellspacing="0">
<tr>
<td valign="top" colspan="5">

	<table class="tOuter"><tr><td class="tOuter">
		<table class="tInner">
			<thead><tr class="tInnerHeader">
			<td class="tInnerHeaderCell">

				<span class="tInnerHeaderText">Capsuleer Contacts Replication Tool</span>

			</td></tr>
			</thead>
			<tbody>
			<tr><td style="text-align: center;">

				<table class="tData">
				<thead>
				<tr class="tDataHeader">
				<td class="tDataHeader">Contact</td>
				<?php foreach($characterList AS $character) { ?>
					<td class="tDataHeader"><?php echo $character['characterName'] ?></td>
				<?php } ?>
				</tr>
				<tr class="tDataRow2">
				<td class="tData">Select Primary:</td>
				<?php foreach($characterList AS $character) { ?>
					<td class="tData">
						<?php if(isset($_SESSION['primaryCharID']) AND $_SESSION['primaryCharID'] == $character['characterID']) { ?>
							Current Primary
						<?php } else { ?>
							<a href="index.php?p=merger&amp;usrAction=set_primary&amp;characterID=<?php echo $character['characterID'] ?>">Set Primary</a>
						<?php } ?>
					</td>
				<?php } ?>
				</tr>
				<tr class="tDataRow2">
				<td class="tData">Refresh Contacts:</td>
				<?php foreach($characterList AS $character) { ?>
					<td class="tData">
						<a href="index.php?p=merger&amp;usrAction=refresh_contacts&amp;characterID=<?php echo $character['characterID'] ?>">Refresh</a>
					</td>
				<?php } ?>
				</tr>
				</thead>
				<tbody>
				<tr class="tDataSubHeader">
				<td class="tDataSubHeader" colspan="<?php echo count($characterList)+1; ?>">Alliances</td>
				</tr>
				<?php foreach($characterContactGrid AS $contact) {
					if($contact['contactType'] == "Alliance") { ?>
						<tr class="tDataRow1">
						<td class="tData"><?php echo $contact['contactName']; ?></td>
						<?php foreach($characterList AS $character) { ?>
							<td class="tData">
								<?php if(isset($contact[$character['characterID']])) { echo $contact['standing']; } ?>
							</td>
						<?php } ?>
						</tr>
				<?php }
				} ?>
				<tr class="tDataSubHeader">
				<td class="tDataSubHeader" colspan="<?php echo count($characterList)+1; ?>">Corporations</td>
				</tr>
				<?php foreach($characterContactGrid AS $contact) {
					if($contact['contactType'] == "Corporation") { ?>
						<tr class="tDataRow1">
						<td class="tData"><?php echo $contact['contactName']; ?></td>
						<?php foreach($characterList AS $character) { ?>
							<td class="tData">
								<?php if(isset($contact[$character['characterID']])) { echo $contact['standing']; } ?>
							</td>
						<?php } ?>
						</tr>
				<?php }
				} ?>
				<tr class="tDataSubHeader">
				<td class="tDataSubHeader" colspan="<?php echo count($characterList)+1; ?>">Characters</td>
				</tr>
				<?php foreach($characterContactGrid AS $contact) {
					if($contact['contactType'] == "Character") { ?>
						<tr class="tDataRow1">
						<td class="tData"><?php echo $contact['contactName']; ?></td>
						<?php foreach($characterList AS $character) { ?>
							<td class="tData">
								<?php if(isset($contact[$character['characterID']])) { echo $contact['standing']; if($contact['watched']) { echo "*"; } } ?>
							</td>
						<?php } ?>
						</tr>
				<?php }
				} ?>
				<tr class="tDataRow0">
				<td class="tData">Remove Character:</td>
				<?php foreach($characterList AS $character) { ?>
					<td class="tData">
						<a href="index.php?p=merger&amp;usrAction=remove_character&amp;characterID=<?php echo $character['characterID'] ?>">Remove</a>
					</td>
				<?php } ?>
				</tr>
				</tbody>
				</table>

			</td></tr>
			</tbody>
		</table>
	</td></tr></table>

</td>
</tr>
<?php if(isset($_SESSION['primaryCharID'])) { ?>
<tr><td class="tHorDivider" colspan="5" /></tr>
<tr>
<td valign="top" colspan="5">

	<table class="tOuter"><tr><td class="tOuter">
		<table class="tInner">
			<thead><tr class="tInnerHeader">
			<td class="tInnerHeaderCell">

				<span class="tInnerHeaderText">Perform Contacts Mirroring</span>

			</td></tr>
			</thead>
			<tbody>
			<tr><td style="text-align: center;">

				<form enctype="multipart/form-data" action="index.php" method="POST">
				<input type="hidden" name="p"  value="merger" />
				<input type="hidden" name="usrAction" value="merge_contacts" />
				<table style="width: 75%; margin: auto;">
				<tr>
				<td><input type="checkbox" name="confirm_merge" /></td>
				<td><input type="submit" value="Copy and replace all contacts from primary character" /></td>
				</tr>
				</table>
				</form>

			</td></tr>
			</tbody>
		</table>
	</td></tr></table>

</td>
</tr>
<?php } ?>
<tr><td class="tHorDivider" colspan="5" /></tr>
<tr>
<td valign="top" style="width: 33%;">

	<table class="tOuter"><tr><td class="tOuter">
		<table class="tInner">
			<thead><tr class="tInnerHeader">
			<td class="tInnerHeaderCell">

				<span class="tInnerHeaderText">Log into Additional Character(s)</span>

			</td></tr>
			</thead>
			<tbody>
			<tr><td style="text-align: center;">

				<a href="javascript:eveLogin();"><img src="ui_img/eve_sso_icon.png" /></a>

			</td></tr>
			</tbody>
		</table>
	</td></tr></table>

</td>
<td class="tVerDivider" />
<td valign="top" style="width: 33%;">

	<table class="tOuter"><tr><td class="tOuter">
		<table class="tInner">
			<thead><tr class="tInnerHeader">
			<td class="tInnerHeaderCell">

				<span class="tInnerHeaderText">Log out of this Tool</span>

			</td></tr>
			</thead>
			<tbody>
			<tr><td style="text-align: center;">

				<form enctype="multipart/form-data" action="index.php" method="POST">
				<input type="hidden" name="p"  value="merger" />
				<input type="hidden" name="usrAction" value="logout" />
				<table style="width: 75%; margin: auto;">
				<tr>
				<td><input type="checkbox" name="confirm_logout" /></td>
				<td><input type="submit" value="Logout" /></td>
				</tr>
				</table>
				</form>

			</td></tr>
			</tbody>
		</table>
	</td></tr></table>

</td>
<td class="tVerDivider" />
<td valign="top">

	<table class="tOuter"><tr><td class="tOuter">
		<table class="tInner">
			<thead><tr class="tInnerHeader">
			<td class="tInnerHeaderCell">

				<span class="tInnerHeaderText">Logout and Wipe Data</span>

			</td></tr>
			</thead>
			<tbody>
			<tr><td style="text-align: center;">

				<form enctype="multipart/form-data" action="index.php" method="POST">
				<input type="hidden" name="p"  value="merger" />
				<input type="hidden" name="usrAction" value="wipe" />
				<table style="width: 75%; margin: auto;">
				<tr>
				<td><input type="checkbox" name="confirm_wipe" /></td>
				<td><input type="submit" value="Logout and Wipe my Records on this Site" /></td>
				</tr>
				</table>
				</form>

			</td></tr>
			</tbody>
		</table>
	</td></tr></table>

</td>
</tr>
</table>