<?php
/* Copyright (c) 2014 by the Omnicoin Team.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>. */

// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.");
}

// Hooks
$plugins->add_hook("member_profile_start", "omnicoin_member_profile_start");
$plugins->add_hook("usercp_start", "omnicoin_usercp_start");
$plugins->add_hook("postbit", "omnicoin_postbit");

function omnicoin_info() {
	return array(
		"name"		=> "Omnicoin integration",
		"description"	=> "This plugin integrates omnicoin addresses with user profiles",
		"website"	=> "http://www.omnicoin.org",
		"author"	=> "Omnicoin Team",
		"authorsite"	=> "https://github.com/Omnicoin-Project/Omnicoin/wiki/Omnicoin-Team",
		"version"	=> "v1.0.0",
		"guid" 		=> "",
		"compatibility" => "*"
	);
}

function omnicoin_install() {
	//Called whenever a plugin is installed by clicking the "Install" button in the plugin manager.
	//It is common to create required tables, fields and settings in this function.
	
	global $mybb, $db, $cache;

  	if (!$db->table_exists("omcaddresses")) {
		//create the omcaddress table here.
		$db->query("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "omcaddresses` (
			`id` smallint(10) unsigned NOT NULL AUTO_INCREMENT,
			`uid` varchar(10) NOT NULL DEFAULT '',
			`address` varchar(34) NOT NULL DEFAULT '',
			`date` DATETIME NOT NULL,
			`balance` decimal	NOT NULL DEFAULT 0,
			`lastupdate` DATETIME NOT NULL,
			PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
	}
}

function omnicoin_is_installed() {
	//Called on the plugin management page to establish if a plugin is already installed or not.
	//This should return TRUE if the plugin is installed (by checking tables, fields etc) or FALSE if the plugin is not installed.
	//Check if the address table is created
	
	global $mybb, $db;
	
  	if ($db->table_exists("omcaddresses")) {
		return true;
	}
}

function omnicoin_uninstall() {
	//Called whenever a plugin is to be uninstalled. This should remove ALL traces of the plugin from the installation (tables etc). If it does not exist, uninstall button is not shown.
	//Delete the address table
	
	global $mybb, $db;

	//Delete the address table
	if ($db->table_exists("omcaddresses")) {
		//delete the table here
		$db->query("DROP TABLE IF EXISTS `" . TABLE_PREFIX . "omcaddresses`");
	}
}

function omnicoin_activate() {
	//Called whenever a plugin is activated via the Admin CP. This should essentially make a plugin "visible" by adding templates/template changes, language changes etc.
	global $db;
	
	require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';

	$db->insert_query("templates", array(
		"tid"			=> NULL,
		"title"			=> "Omnicoin Address History",
		"template"		=> '<html>
	<head>
		<title>Omnicoin Address History</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<h2>Address history for: {$username}</h2>
		<br />
		<table class="tborder">
			<tr class="thead">
				<th><strong>Omnicoin address:</strong></th>
				<th><strong>Date added:</strong></th>
			</tr>
			{$entries}
		</table>
		{$footer}
	</body>
</html>',
		"sid"			=> "-1"));
		
	$db->insert_query("templates", array(
		"tid"			=> NULL,
		"title"			=> "Omnicoin Address History Entry",
		"template"		=> '<tr class="trow1"><td><a target="_blank" href="https://omnicha.in?address={$address}">{$address}</a></td><td>{$date}</td></tr>',
		"sid"			=> "-1"));
		
	$db->insert_query("templates", array(
		"tid"			=> NULL,
		"title"			=> "Omnicoin Address History No Entry",
		"template"		=> '<tr class="trow1"><td colspan=2>{$message}</td></tr>',
		"sid"			=> "-1"));
				
	$db->insert_query("templates", array(
		"tid"			=> NULL,
		"title"			=> "Omnicoin Address Search",
		"template"		=> '<html>
	<head>
		<title>Omnicoin Address Search</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<h2>Search for accounts with a matching Omnicoin address</h2>
		<br />
		<form action="coins.php?action=search" method="post">
			<input class="textbox" type="text" name="query">
			<input class="button" type="submit" value="Search">
		</form>
		{$footer}
	</body>
</html>',
		"sid"			=> "-1"));
		
	$db->insert_query("templates", array(
		"tid"			=> NULL,
		"title"			=> "Omnicoin Address Search Results",
		"template"		=> '<html>
	<head>
		<title>Omnicoin Address Search</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<h2>Search results for: {$search}</h2>
		<br />
		<table class="tborder">
			<tr class="thead">
				<th><strong>User:</strong></th>
				<th><strong>Omnicoin address:</strong></th>
				<th><strong>Date added:</strong></th>
			</tr>
			{$entries}
		</table>
		{$footer}
	</body>
</html>',
		"sid"			=> "-1"));
		
	$db->insert_query("templates", array(
		"tid"			=> NULL,
		"title"			=> "Omnicoin Address Search Results Entry",
		"template"		=> '<tr class="trow1"><td><a href="member.php?action=profile&uid={$userid}">{$username}</td><td><a target="_blank" href="https://omnicha.in?address={$address}">{$address}</a></td><td>{$date}</td></tr>',
		"sid"			=> "-1"));
		
	$db->insert_query("templates", array(
		"tid"			=> NULL,
		"title"			=> "Omnicoin Address Search Results No Entry",
		"template"		=> '<tr class="trow1"><td colspan=3>{$message}</td></tr>',
		"sid"			=> "-1"));
	
	$db->insert_query("templates", array(
		"tid"			=> NULL,
		"title"			=> "Omnicoin Default Page",
		"template"		=> '<html>
	<head>
		<title>Omnicoin</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<h2>Omnicoin</h2>
		<br />
		<a href="coins.php?action=search">Search Addresses</a><br />
<a href="coins.php?action=history">View all addresses</a><br />
<a href="coins.php?action=newaddress">Add a new address</a>
		{$footer}
	</body>
</html>',
		"sid"			=> "-1"));
		
	$db->insert_query("templates", array(
		"tid"			=> NULL,
		"title"			=> "Omnicoin Add Address Page",
		"template"		=> '<html>
	<head>
		<title>Add Omnicoin Address</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<h2>Add an Omnicoin address</h2>
<form method="post" action="coins.php">

	<table cellspacing="0" cellpadding="2">
		<tr>
			<td colspan=2>Add an omnicoin address to your profile. Follow <a href="https://github.com/Omnicoin-Project/Omnicoin/wiki/Signing-a-message-using-Omnicoin">this tutorial</a>.</td>
		</tr>
		<tr>
			<td>Address:</td><td><input type="text" class="textbox" size="40" name="omc_address" /></td>
		</tr>
		<tr>
			<td>Signing message:</td><td>' . $signingmessage . '</td></td>
		</tr>
		<tr>
			<td>Signature:</td><td><input type="text" class="textbox" size="40" name="omc_signature" /></td>
		</tr>
		<tr>
			<input type="submit" />Add Address</td>
		</tr>
	</table></form>
	{$footer}
	</body>
</html>';
				
	find_replace_templatesets("member_profile", "#" . preg_quote('{$warning_level}') . "#", 	'{$warning_level}{$omcaddress}{$omcbalance}');
	find_replace_templatesets("usercp", 		"#" . preg_quote('{$referral_info}') . "#",	 	'{$omcaddress}{$referral_info}');
	find_replace_templatesets("header", 		"#" . preg_quote('{$menu_memberlist}') . "#",	'{$menu_memberlist}<li><a href="{$mybb->settings[\'bburl\']}/coins.php?action=search" class="search">OMC Search</a></li>');
}

function omnicoin_deactivate() {
	//Called whenever a plugin is deactivated. This should essentially "hide" the plugin from view by removing templates/template changes etc. It should not, however, remove any information such as tables, fields etc - that should be handled by an _uninstall routine. When a plugin is uninstalled, this routine will also be called before _uninstall() if the plugin is active.
	
	global $db;
	
	include MYBB_ROOT."/inc/adminfunctions_templates.php";

	$db->delete_query("templates", "title LIKE 'Omnicoin Address History'");
	$db->delete_query("templates", "title LIKE 'Omnicoin Address History Entry'");
	$db->delete_query("templates", "title LIKE 'Omnicoin Address History No Entry'");
	$db->delete_query("templates", "title LIKE 'Omnicoin Address Search'");
	$db->delete_query("templates", "title LIKE 'Omnicoin Address Search Results'");
	$db->delete_query("templates", "title LIKE 'Omnicoin Address Search Results Entry'");
	$db->delete_query("templates", "title LIKE 'Omnicoin Address Search Results No Entry'");
	$db->delete_query("templates", "title LIKE 'Omnicoin Default Page'");
        $db->delete_query("templates", "title LIKE 'Omnicoin Add Address Page'");
	
	//Delete omnicoin address from profile template
	find_replace_templatesets("member_profile", "#" . preg_quote('{$omcaddress}{$omcbalance}') . "#", "");
	find_replace_templatesets("usercp", 		"#" . preg_quote('{$omcaddress}') . "#", "");
	find_replace_templatesets("header", 		"#" . preg_quote('<li><a href="{$mybb->settings[\'bburl\']}/coins.php?action=search" class="search">OMC Search</a></li>') . "#", "");
}

function omnicoin_get_user_balance($uid) {
	global $db;

	$query = $db->simple_select("omcaddresses", "id, address, balance, lastupdate", "uid = '" . $uid . "'", array("order_by" => "date", "order_dir" => "DESC", "limit" => 1));

	if ($query->num_rows == 1) {
		$data = $db->fetch_array($query);
		if (TIME_NOW - strtotime($data['lastupdate']) < 3600) { //Cache balances for 1 hour
			return omnicoin_formatNumber($data['balance'], 4);
		} else {
			$balance = omnicoin_getAddressBalance($data['address']);
			if ($balance >= 0) {
				$db->update_query("omcaddresses", array("balance" => $balance, "lastupdate" => date("Y-m-d H:i:s")), "id = '" . $data['id'] . "'");
				return omnicoin_formatNumber($balance, 4);
			} else {
				return omnicoin_formatNumber($data['balance'], 4);
			}
		}
	}
	return -1;
}

function omnicoin_member_profile_start() {
	//called whenever someone opens their profile.
	
	global $db, $mybb, $omcaddress, $omcbalance;

	$query = $db->simple_select("omcaddresses", "address", "uid = '" . intval($mybb->input['uid']) . "'", array("order_by" => "date", "order_dir" => "DESC", "limit" => 1));
	
	if ($query->num_rows == 1) {
		$returndata = $db->fetch_array($query);
		$address = $returndata['address'];
		$balance = omnicoin_get_user_balance(intval($mybb->input['uid']));
		
		$omcaddress = "<tr>
	<td class='trow1'>
		<strong>Omnicoin Address:</strong>
	</td>
	<td class='trow1'>
		<a target='_blank' href='https://omnicha.in?address=" . $address . "'>" . $address . "</a>&nbsp;[<a href='coins.php?action=history&amp;uid=" . intval($mybb->input['uid']) . "'>History</a>]
	</td>
</tr>";

		$omcbalance = "
	<td class='trow1'>
		<strong>Omnicoin Balance:</strong>
	</td>
	<td class='trow1'>
		" . $balance . " OMC
	</td>
</tr>";
	}
}


function omnicoin_postbit(&$post) {
	//Called when a post is displayed
	global $db;

	$balance = omnicoin_get_user_balance($post['uid']);

	if ($balance != -1) {
		$post['user_details'] .= "<br />OMC balance: " . $balance;
	}
}

function omnicoin_usercp_start() {
	global $db, $omcaddress, $mybb;
	
	$query = $db->simple_select("omcaddresses", "address", "uid = '" . $mybb->user['uid'] . "'", array("order_by" => "date", "order_dir" => "DESC", "limit" => 1));
	if ($query->num_rows == 1) {
		$returndata = $db->fetch_array($query);
		$address = $returndata['address'];	

		$omcaddress = "<strong>Omnicoin Address: </strong><a target='_blank' href='https://omnicha.in?address=" . $address . "'>" . $address . "</a>&nbsp;[<a href='coins.php?action=history&amp;uid=" . $mybb->user['uid'] . "'>History</a>]<br />";
	} else {
		$omcaddress = "";
	}
}

function omnicoin_formatNumber($val, $precision = 10) {
	$to_return = rtrim(rtrim(number_format(round($val, $precision), $precision), "0"), ".");
	return $to_return == "" ? "0" : $to_return;
}

function omnicoin_getAddressBalance($address) {
	//Returns the balance of the given address (double). 
	//Assumes address is already validated.
	
	$response = json_decode(fetch_remote_file("http://omnicha.in/api/?method=getbalance&address=" . urlencode($address)), TRUE);
	if ($response) {
		if (!$response['error']) {
			return $response['response']['balance'];
		} else {
			return -1;
		}
	} else {
		return -1;
	}
}

function omnicoin_add_address($userhandler) {
	//this is where we will put the code to handle verification and storing of the addresses

	global $mybb, $db;
	
	if ($mybb->input['action'] == "do_profile") {
		$omcerrormessage = "";
		if (isset($mybb->input['omc_address']) && isset($mybb->input['omc_signature']) && !empty($mybb->input['omc_address'])) {
			//Whitelist address so user can't inject into DB or API calls
			$address = $db->escape_string(preg_replace("/[^A-Za-z0-9]/", "", $mybb->input['omc_address']));
-			$signature = $db->escape_string(preg_replace("/[^A-Za-z0-9=+-\/]/", "", $mybb->input['omc_signature']));
			
			if (omnicoin_checkAddress($address)) {
				$signingmessage = $mybb->settings['bbname'] . " Omnicoin Address Confirmation - User: " . $mybb->user['username'] . " UID: " . $mybb->user['uid'];
				if (omnicoin_verifyAddress($address, $signingmessage, $signature)) {
					$db->insert_query("omcaddresses", array("uid" => $mybb->user['uid'], "address" => $address, "date" => date("Y-m-d H:i:s")));
					//Display success message
					//$omcerrormessage = "Success!";
				} else {
					//Display signature invalid message
					$omcerrormessage = "Error: Invalid Omnicoin address signature";
				}
			} else {
				//Display address invalid message
				$omcerrormessage = "Error: Invalid Omnicoin address";
			}
		}
		
		if ($omcerrormessage != "") {
			echo "<script language='javascript'>";
			echo "alert('" . $omcerrormessage . "')";
			echo "</script>";
		}
	}
}

function omnicoin_verifyAddress($address, $message, $signature) {
	//Returns whether or not the signature is valid for the message for this address (boolean).
	//Assumes address is already validated.
	
	//Fix for PHP thinking that + is multiple strings there are multiple strings
	$signature = preg_replace("/[+]/", "%2B", $signature);
	$response = json_decode(fetch_remote_file("http://omnicha.in/api?method=verifymessage&address=" . urlencode($address) . "&message=" . urlencode($message) . "&signature=" . urlencode($signature)), TRUE);
	if ($response) {
		if (!$response['error']) {
			return $response['response']['isvalid'];
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function omnicoin_checkAddress($addr) {
	//Returns whether or not the address is valid (boolean).

	$addr = omnicoin_decodeBase58($addr);
	if (strlen($addr) != 50) {
		return false;
	}
	$version = substr($addr, 0, 2);
	if (hexdec($version) != 115) {
		return false;
	}
	$check = substr($addr, 0, strlen($addr) - 8);
	$check = pack("H*", $check);
	$check = strtoupper(hash("sha256", hash("sha256", $check, true)));
	$check = substr($check, 0, 8);
	
	return $check == substr($addr, strlen($addr) - 8);
}

function omnicoin_decodeBase58($base58) {
	$base58chars = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";
	$origbase58 = $base58;
	
	//only valid chars allowed
	if (preg_match('/[^1-9A-HJ-NP-Za-km-z]/', $base58)) {
		return "";
	}
	
	$return = "0";
	for ($i = 0; $i < strlen($base58); $i++) {
		$current = (string) strpos($base58chars, $base58[$i]);
		$return = (string) bcmul($return, "58", 0);
		$return = (string) bcadd($return, $current, 0);
	}

	$return = omnicoin_encodeHex($return);

	//leading zeros
	for ($i = 0; $i < strlen($origbase58) && $origbase58[$i] == "1"; $i++) {
		$return = "00" . $return;
	}

	if (strlen($return) % 2 != 0) {
		$return = "0" . $return;
	}

	return $return;
}

function omnicoin_encodeHex($dec) {
	$hexchars = "0123456789ABCDEF";
	$return = "";
	while (bccomp($dec, 0) == 1) {
		$dv = (string) bcdiv($dec, "16", 0);
		$rem = (integer) bcmod($dec, "16");
		$dec = $dv;
		$return = $return . $hexchars[$rem];
	}
	return strrev($return);
}
