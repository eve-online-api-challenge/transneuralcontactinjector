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
	refreshContactList($characterID,TRUE);
	redirect("index.php?p=merger");
} elseif($usrAction == "refresh_all_contacts") {
	refreshAllContacts(TRUE);
	redirect("index.php?p=merger");
} elseif($usrAction == "add_contact") {
	$contact_name = getInput("contact_name");
	$contact_type = getInput("contact_type");
	$standing = (float) getInput("standing");
	$watch_list = getInput("watch_list");
	$to_characters = getInput("to_characters");
	addContact($contact_name,$contact_type,$standing,$watch_list,$to_characters);
	redirect("index.php?p=merger");
} elseif($usrAction == "edit_contact") {
	$characterID = (int) getInput("characterID");
	$contactID = (int) getInput("contactID");
	$standing = (float) getInput("standing");
	$watch_list = getInput("watch_list");
	editContact($characterID,$contactID,$standing,$watch_list);
	redirect("index.php?p=merger");
} elseif($usrAction == "operate_contact") {
	$characterID = (int) getInput("characterID");
	$contactID = (int) getInput("contactID");
	$operation = (int) getInput("operation");
	operateContact($characterID,$contactID,$operation);
	redirect("index.php?p=merger");
} elseif($usrAction == "add_contact_mass") {
	$contact_list = getInput("contact_list");
	$to_characters = getInput("to_characters");
	addContactMass($contact_list,$to_characters);
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
			$characterContactGrid[$contactID][$contact['characterID']] = array("standing" => $contact['standing'],"watched" => $contact['watched']);
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
							<a href="index.php?p=merger&amp;usrAction=set_primary&amp;characterID=<?php echo $character['characterID']; ?>">Set Primary</a>
						<?php } ?>
					</td>
				<?php } ?>
				</tr>
				<tr class="tDataRow2">
				<td class="tData">Refresh Contacts: (<a href="index.php?p=merger&amp;usrAction=refresh_all_contacts">All</a>)</td>
				<?php foreach($characterList AS $character) { ?>
					<td class="tData">
						<?php if(time() > $character['contactCacheTimer']) { ?>
							<a href="index.php?p=merger&amp;usrAction=refresh_contacts&amp;characterID=<?php echo $character['characterID']; ?>">Refresh</a>
						<?php } else { echo "Cached (".ceil(($character['contactCacheTimer']-time())/60)." Min)"; } ?>
					</td>
				<?php } ?>
				</tr>
				</thead>
				<tr class="tDataRow2">
				<td class="tData">Download List as a Backup (CSV):</td>
				<?php foreach($characterList AS $character) { ?>
					<td class="tData">
						<a href="index.php?p=backup_contacts&amp;characterID=<?php echo $character['characterID'] ?>">Download</a>
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
							<td class="tData" <?php if(isset($contact[$character['characterID']])) { echo " style=\"cursor: pointer\" onClick=\"javascript:showEditBox(".$character['characterID'].",".$contact['contactID'].",'".jsNameFormat($contact['contactName'])."','".$contact['contactType']."',".$contact[$character['characterID']]['standing'].",".$contact[$character['characterID']]['watched'].");\""; } ?>>
								<?php if(isset($contact[$character['characterID']])) { echo $contact[$character['characterID']]['standing']; } ?>
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
							<td class="tData"<?php if(isset($contact[$character['characterID']])) { echo " style=\"cursor: pointer\" onClick=\"javascript:showEditBox(".$character['characterID'].",".$contact['contactID'].",'".jsNameFormat($contact['contactName'])."','".$contact['contactType']."',".$contact[$character['characterID']]['standing'].",".$contact[$character['characterID']]['watched'].");\""; } ?>>
								<?php if(isset($contact[$character['characterID']])) { echo $contact[$character['characterID']]['standing']; } ?>
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
							<td class="tData"<?php if(isset($contact[$character['characterID']])) { echo " style=\"cursor: pointer\" onClick=\"javascript:showEditBox(".$character['characterID'].",".$contact['contactID'].",'".jsNameFormat($contact['contactName'])."','".$contact['contactType']."',".$contact[$character['characterID']]['standing'].",".$contact[$character['characterID']]['watched'].");\""; } ?>>
								<?php if(isset($contact[$character['characterID']])) { echo $contact[$character['characterID']]['standing']; if($contact[$character['characterID']]['watched']) { echo " (w)"; } } ?>
							</td>
						<?php } ?>
						</tr>
				<?php }
				} ?>
				<tr class="tDataRow2">
				<td class="tData">Logout Character:</td>
				<?php foreach($characterList AS $character) { ?>
					<td class="tData">
						<a href="index.php?p=merger&amp;usrAction=remove_character&amp;characterID=<?php echo $character['characterID'] ?>">Logout</a>
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
<tr><td class="tHorDivider" colspan="5" /></tr>
<tr>
<td valign="top" colspan="5">

	<table class="tOuter"><tr><td class="tOuter">
		<table class="tInner">
			<thead><tr class="tInnerHeader">
			<td class="tInnerHeaderCell">

				<span class="tInnerHeaderText">Manually Add Contact</span>

			</td></tr>
			</thead>
			<tbody>
			<tr><td style="text-align: center;">

				<form enctype="multipart/form-data" action="index.php" method="POST">
				<input type="hidden" name="p"  value="merger" />
				<input type="hidden" name="usrAction" value="add_contact" />
				<table style="width: 75%; margin: auto;">
				<tr>
				<td style="text-align: right;">Name</td>
				<td><input type="text" name="contact_name" /></td>
				<td style="text-align: left;">To Character(s):</td>
				<td rowspan="4" style="vertical-align: top;"><input type="submit" value="Add Contact" /></td>
				</tr>
				<tr>
				<td style="text-align: right;">Type</td>
				<td>
					<select name="contact_type">
					<option value="Character">Character</option>
					<option value="Corporation">Corporation</option>
					<option value="Alliance">Alliance</option>
					</select>
				</td>
				<td rowspan="3" style="vertical-align: top;">
					<select name="to_characters[]" multiple="multiple">
					<?php foreach($characterList AS $character) { ?>
						<option value="<?php echo $character['characterID']; ?>" selected="selected"><?php echo $character['characterName']; ?></option>
					<?php } ?>
					</select>
				</td>
				</tr>
				<tr>
				<td style="text-align: right;">Standings</td>
				<td>
					<select name="standing">
					<option value="10">Excellent (+10)</option>
					<option value="5">Good (+5)</option>
					<option value="0">Neutral (0)</option>
					<option value="-5">Bad (-5)</option>
					<option value="-10">Terrible (-10)</option>
					</select>
				</td>
				</tr>
				<tr>
				<td style="text-align: right;">Watch List?</td>
				<td style="text-align: left;"><input type="checkbox" name="watch_list" /></td>
				</tr>
				</table>
				</form>

			</td></tr>
			</tbody>
		</table>
	</td></tr></table>

</td>
</tr>
<tr><td class="tHorDivider" colspan="5" /></tr>
<tr>
<td valign="top" colspan="5">

	<table class="tOuter"><tr><td class="tOuter">
		<table class="tInner">
			<thead><tr class="tInnerHeader">
			<td class="tInnerHeaderCell">

				<span class="tInnerHeaderText">Mass Upload Contacts</span>

			</td></tr>
			</thead>
			<tbody>
			<tr><td style="text-align: center;">

				<form enctype="multipart/form-data" action="index.php" method="POST">
				<input type="hidden" name="p"  value="merger" />
				<input type="hidden" name="usrAction" value="add_contact_mass" />
				<table style="width: 75%; margin: auto;">
				<tr>
				<td style="text-align: left;" colspan="3">Paste your comma-delimited list of contacts below. You must follow the format of the CSV as exported from this site. You may include the header row but it will be ignored upon import.</td>
				</tr>
				<tr>
				<td style="vertical-align: top;"><textarea name="contact_list"></textarea></td>
				<td style="vertical-align: top;">
					<select name="to_characters[]" multiple="multiple">
					<?php foreach($characterList AS $character) { ?>
						<option value="<?php echo $character['characterID']; ?>" selected="selected"><?php echo $character['characterName']; ?></option>
					<?php } ?>
					</select>
				</td>
				<td style="vertical-align: top;"><input type="submit" value="Add Contacts (May Take Minutes!)" /></td>
				</tr>
				</table>
				</form>

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
				<td style="text-align: left;" colspan="2">Warning! This Operation will wipe out all contacts in your non-primary accounts before migrating over your primary character's account list. Do not interrupt this operation!</td>
				</tr>
				<tr>
				<td><input type="checkbox" name="confirm_merge" /></td>
				<td><input type="submit" value="Push Primary Character Contacts to Alts (May Take Minutes!)" /></td>
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
<div class="editBoxWindowForeground editBox editBoxDivHide" id="editContact">
<form enctype="multipart/form-data" action="index.php" method="post" id="editContactForm">
<table style="width: 100%;">
<input type="hidden" name="p" value="merger"></input>
<input type="hidden" name="usrAction" value="edit_contact"></input>
<input type="hidden" name="characterID" value="0"></input>
<input type="hidden" name="contactID" value="0"></input>
<tr>
	<td align="center" width="50%">Update Contact: </td>
	<td align="center" id="editContactName"></td>
</tr>
<tr>
	<td valign="middle">Standing</td>
	<td valign="top">
		<select name="standing">
		<option value="10">Excellent (+10)</option>
		<option value="5">Good (+5)</option>
		<option value="0">Neutral (0)</option>
		<option value="-5">Bad (-5)</option>
		<option value="-10">Terrible (-10)</option>
		</select>
	</td>
</tr>
<tr>
	<td valign="middle" width="50%">Watch List?</td>
	<td valign="top"><input type="checkbox" name="watch_list" /></td>
</tr>
<tr>
	<td colspan="2" align="center"></td>
</tr>
<tr>
	<td align="center" colspan="2"><input type="submit" style="width: auto;" value="Update Contact Details"></input></td>
</tr>
</table>
</form>
<hr />
<form enctype="multipart/form-data" action="index.php" method="post" id="operateContactForm">
<table style="width: 100%;">
<input type="hidden" name="p" value="merger"></input>
<input type="hidden" name="usrAction" value="operate_contact"></input>
<input type="hidden" name="characterID" value="0"></input>
<input type="hidden" name="contactID" value="0"></input>
<tr>
	<td valign="middle">Select Operation</td>
	<td valign="top">
		<select name="operation">
		<option value="0">---------------</option>
		<option value="1">Delete Contact</option>
		<option value="2">Delete Contact (on All Characters)</option>
		<option value="3">Copy Contact (to All Characters)</option>
		</select>
	</td>
</tr>
<tr>
	<td colspan="2" align="center"></td>
</tr>
<tr>
	<td align="center" colspan="2"><input type="submit" style="width: auto;" value="Perform Operation"></input></td>
</tr>
</table>
</form>
</div>