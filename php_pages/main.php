<?php

if(!defined("INCLUDE_CHECK")) { header('HTTP/1.0 404 File Not Found'); exit; }

$pageTitle = "Transneural Contact Injector";
$breadcrumb = array($pageTitle);
if(isset($_SESSION['userID'])) {
	redirect("index.php?p=merger");
}

?>
<script type="text/javascript" src="ui_javascript/transneural.js"></script>
<table style="width: 100%;" cellpadding="0" cellspacing="0">
<tr>
<td valign="top">

	<table class="tOuter"><tr><td class="tOuter">
		<table class="tInner">
			<thead><tr class="tInnerHeader">
			<td class="tInnerHeaderCell">

				<span class="tInnerHeaderText">Login</span>

			</td></tr>
			</thead>
			<tbody>
			<tr><td style="text-align: center;">

				<h3>Read Me First!</h3>
				<p style="text-align: justify;">This tool is a submission for the <a href="http://community.eveonline.com/news/dev-blogs/the-eve-online-api-challenge-1/">EvE Online API Challenge</a>. This tool utilizes CREST functionality to enable management of Capsuleer Contacts outside of the EvE game. A recommendation on Reddit was to have a tool that can synchronize one capsuleer's contacts to their alts. This tool performs that function.</p><br />
				<p style="text-align: justify;">In order to visually review contacts as well as troubleshoot issues, I've engineered this tool to function as an address book: displaying contacts across each logged in account and where they align. A user will initially see disparate contacts across their capsuleers (maybe some overlap). When they perform the synchronize operation, the contact list will be mirrored from the capsuleer they set as primary to their other capsuleers. After a couple of minutes to account for CREST caching, the contact list grid will be identical for each capsuleer.</p><br />
				<p style="text-align: justify;">WARNING! This tool has only been tested for capsuleers with minimal contacts. As there are no BULK contact management operations enabled in CREST (at time of writing), it takes a number of calls to fully perform the operation. A number of things can occur with this (such as the app crashing or an unforeseen bug popping up) and it is NOT recommended to try this out with huge contact lists... yet. <b>Use of this tool to manage your contacts is done at YOUR OWN RISK.</b></p><br />
				<br />
				<br />
				<a href="javascript:eveLogin();"><img src="ui_img/eve_sso_icon.png" /></a>

			</td></tr>
			</tbody>
		</table>
	</td></tr></table>

</td>
</tr>
</table>