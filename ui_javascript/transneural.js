function eveLogin() {
	window.open("index.php?p=evelogin", "PopupWindow", "width=600,height=600,scrollbars=yes,resizable=no");
}

function showEditBox(characterID,contactID,contactName,type,standing,watched) {
	$('#editContactName').html(contactName);

	document.getElementById('editContactForm').characterID.value = characterID;
	document.getElementById('editContactForm').contactID.value = contactID;
	document.getElementById('editContactForm').standing.value = standing;
	if(watched == 0) {
		document.getElementById('editContactForm').watch_list.checked = false;
	} else {
		document.getElementById('editContactForm').watch_list.checked = true;
	}

	document.getElementById('operateContactForm').characterID.value = characterID;
	document.getElementById('operateContactForm').contactID.value = contactID;

	appendAWBackground();
	$('#editContact').toggleClass('editBoxDivDisplay',true);
	$('#editContact').toggleClass('editBoxDivHide',false);
	$('#editContact').toggleClass('editBoxCurrentOverlay',true);
	return;
}

function appendAWBackground() {
	if($('.editBoxWindowBackground').length == 0) {
		$("body").append('<div class="editBoxWindowBackground"></div>');
		$('.editBoxWindowBackground').click(function() {
			closeAW();
		});
	} else {
		$('.editBoxWindowBackground').toggleClass('backgroundDivHide',false);
		$('.editBoxWindowBackground').toggleClass('backgroundDivDisplay',true);
	}
	$('.editBoxWindowBackground').animate({'opacity':'.4'},250);
}

function closeAW() {
	$('.editBoxWindowBackground').toggleClass('backgroundDivHide',true);
	$('.editBoxWindowBackground').toggleClass('backgroundDivDisplay',false);
	$('.editBoxWindowBackground').css('opacity',0);
	$('.editBoxCurrentOverlay').toggleClass('editBoxDivHide',true);
	$('.editBoxCurrentOverlay').toggleClass('editBoxDivDisplay',false);
	$('.editBoxCurrentOverlay').toggleClass('editBoxCurrentOverlay',false);
	return;
}
