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
$plugins->add_hook("misc_start", "omnicoin_misc_start");
$plugins->add_hook("member_profile_start", "omnicoin_member_profile_start");
$plugins->add_hook("usercp_profile_start", "omnicoin_usercp_profile_start");
$plugins->add_hook("datahandler_user_update", "omnicoin_user_update");
$plugins->add_hook("usercp_start", "omnicoin_usercp_start");
$plugins->add_hook("postbit", "omnicoin_postbit");

function omnicoin_info() {
	return array(
		"name"		=> "Omnicoin integration",
		"description"	=> "This plugin integrates omnicoin addresses with user profiles",
		"website"	=> "http://www.omnicoin.org",
		"author"	=> "Omnicoin Team",
		"authorsite"	=> "https://github.com/Omnicoin-Project/Omnicoin/wiki/Omnicoin-Team",
		"version"	=> "1.0",
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
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
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
    	
    $AddressHistoryTemplate = array(
        "tid"        	=> NULL,
        "title"        	=> "Omnicoin Address History",
        "template"    	=> '<html>
	<head>
		<title>Address history</title>
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
			{$addresses}
		</table>
		{$footer}
	</body>
</html>',
        "sid"        => "-1");
		
    $db->insert_query("templates", $AddressHistoryTemplate);
    	
	find_replace_templatesets("member_profile", "#" . preg_quote('{$warning_level}') . "#", '{$warning_level}{$omcaddress}');
	find_replace_templatesets("usercp_profile", "#" . preg_quote('{$customfields}') . "#", '{$omcoptions}{$customfields}');
	find_replace_templatesets("usercp", "#" . preg_quote('{$referral_info}') . "#", '{$omcaddressusercp}{$referral_info}');
}

function omnicoin_deactivate() {
	//Called whenever a plugin is deactivated. This should essentially "hide" the plugin from view by removing templates/template changes etc. It should not, however, remove any information such as tables, fields etc - that should be handled by an _uninstall routine. When a plugin is uninstalled, this routine will also be called before _uninstall() if the plugin is active.
	
	global $db;
	
	include MYBB_ROOT."/inc/adminfunctions_templates.php";

	$db->delete_query("templates", "title LIKE 'Omnicoin Address History'");
	
	//Delete omnicoin address from profile template
	find_replace_templatesets("member_profile", "#" . preg_quote('{$omcaddress}') . "#", "");
	find_replace_templatesets("usercp_profile", "#" . preg_quote('{$omcoptions}') . "#", "");
	find_replace_templatesets("usercp", "#" . preg_quote('{$omcaddressusercp}') . "#", "");
}

function omnicoin_get_user_balance($uid) {
	global $db;

	$query = $db->simple_select("omcaddresses", "id, address, balance, lastupdate", "uid = '" . $uid . "'", array("order_by" => "date", "order_dir" => "DESC", "limit" => 1));

	if ($query->num_rows == 1) {
		$data = $db->fetch_array($query);
		if (time() - strtotime($data['lastupdate']) < 3600) { //Cache balances for 1 hour
			return $data['balance'];
		} else {
			$balance = getAddressBalance($data['address']);
			$db->update_query("omcaddresses", array("balance" => $balance, "lastupdate" => date("Y-m-d H:i:s")), "id = '" . $data['id'] . "'");
			return $balance;
		}
	}
	return -1;
}

function omnicoin_member_profile_start() {
	//called whenever someone opens their profile.
	
	global $db, $mybb, $omcaddress;

	$query = $db->simple_select("omcaddresses", "address", "uid = '" . $mybb->input['uid'] . "'", array("order_by" => "date", "order_dir" => "DESC", "limit" => 1));
	
	if ($query->num_rows == 1) {
		$returndata = $db->fetch_array($query);
		$omcaddress = $returndata['address'];
		$omcbalance = omnicoin_get_user_balance($mybb->input['uid']);
		
		$omcaddress = "<tr>
	<td class='trow1'>
		<strong>Omnicoin Address:</strong>
	</td>
	<td class='trow1'>
		<a target='_blank' href='https://omnicha.in?address=" . $omcaddress . "'>" . $omcaddress . "</a>&nbsp;[<a href='misc.php?action=omchistory&amp;uid=" . $mybb->input['uid'] . "'>History</a>]
	</td>
</tr>
<tr>
	<td class='trow1'>
		<strong>Omnicoin Balance:</strong>
	</td>
	<td class='trow1'>
		" . $omcbalance . " OMC
	</td>
</tr>";
	}
}


function omnicoin_postbit(&$post) {
	//Called when a post is displayed
	global $db;

	$balance = omnicoin_get_user_balance($post['uid']);

	if ($balance != -1) {
		$post['user_details'] .= "<br />OMC balance: " . $balance . " OMC";
	}
}

function omnicoin_usercp_profile_start() {
	//called when a user opens options page of usercp.
	
	global $db, $omcoptions, $mybb;

	$uid = $mybb->user[uid];
	session_start();

	$_SESSION['omc_signing_message'] = $mybb->settings['bbname'] . " Omnicoin Address Confirmation " . substr(md5(microtime()), rand(0, 26), 10) . " " . date("y-m-d H:i:s");
	
	$query = $db->simple_select("omcaddresses", "address", "uid='" . $mybb->user['uid'] . "'", array("order_by" => "date", "order_dir" => "DESC", "limit" => 1));
	if ($query->num_rows == 1) {
		$returndata = $db->fetch_array($query);
		$address = $returndata['address'];	
	} else {
		$address = "";
	}
	
	$omcoptions = "<br />
<fieldset class='trow2'>
	<legend>
		<strong>Omnicoin address</strong>
	</legend>
	<table cellspacing='0' cellpadding='2'>
		<tr>
			<td colspan=2>Add an omnicoin address to your profile</td>
		</tr>
		<tr>
			<td>Address:</td><td><input type='text' class='textbox' size='40' name='omc_address' value='" . ($address != "" ? $address : "") . "' /></td>
		</tr>
		<tr>
			<td>Signing message:</td><td>" . $_SESSION['omc_signing_message'] . "</td></td>
		</tr>
		<tr>
			<td>Signature:</td><td><input type='text' class='textbox' size='40' name='omc_signature' /></td>
		</tr>
	</table>
</fieldset>";
}

function omnicoin_usercp_start() {
	global $db, $omcaddressusercp, $mybb;
	
	$query = $db->simple_select("omcaddresses", "address", "uid = '" . $mybb->user['uid'] . "'", array("order_by" => "date", "order_dir" => "DESC", "limit" => 1));
	if ($query->num_rows) {
		$returndata = $db->fetch_array($query);
		$address = $returndata['address'];	
		
		$address = "<a target='_blank' href='https://omnicha.in?address=" . $address . "'>" . $address . "</a>&nbsp;[<a href='misc.php?action=omchistory&amp;uid=" . $mybb->user['uid'] . "'>History</a>]";

		$omcaddressusercp = "<strong>Omnicoin address: </strong>" . $address . "<br />";
	} else {
		$omcaddressusercp = "";
	}
}

function omnicoin_user_update($userhandler) {
	//this is where we will put the code to handle verification and storing of the addresses

	global $mybb, $db;
	
	if ($mybb->input['action'] == "do_profile") {
		session_start();
		$omcerrormessage = "";
		if (isset($mybb->input['omc_address']) && isset($mybb->input['omc_signature']) && !empty($mybb->input['omc_address'])) {
			//Whitelist address so user can't inject into DB or API calls
			$address = preg_replace("/[^A-Za-z0-9]/", "", $mybb->input['omc_address']);
			$signature = preg_replace("/[^A-Za-z0-9=+-\/]/", "", $mybb->input['omc_signature']);
			if (checkAddress($address)) {
				if (verifyAddress($address, $_SESSION['omc_signing_message'], $signature)) {
					$db->query("INSERT INTO ".TABLE_PREFIX."omcaddresses (uid, address, date) VALUES ('" . $mybb->user['uid'] . "', '" . $address . "', '" . date("Y-m-d H:i:s") . "')");
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

function omnicoin_misc_start() {
	//Handle misc.php funtionality
	global $mybb, $db, $templates, $headerinclude, $header, $footer;
	
	//Check to see if the user viewing the page is logged in, otherwise return.
	if (!($mybb->user['uid'])) {
		return;
	}

	if (isset($mybb->input['action'])) {
		if ($mybb->input['action'] == "omchistory") {
			if (isset($mybb->input['uid'])) {
				$uid = $mybb->input['uid']; 
			} else {
				$uid = $mybb->user[uid];
			}
			$uid = preg_replace("/[^0-9]/", "", $uid);
			// get the username corresponding to the UID passed to the miscpage
			$grabuser = $db->simple_select("users", "username", "uid = " . $uid);
			$user = $db->fetch_array($grabuser);
			$username = $user['username'];
	
			//get all past addresses from table
			$query = $db->simple_select("omcaddresses", "address,date", "uid = '" . $uid . "'", array("order_by" => "date", "order_dir" => "ASC"));
			$addresses = "";
	
			// loop through each row in the database that matches our query and create a table row to display it
			while($row = $db->fetch_array($query)){
				$addresses = $addresses . "<tr class='trow1'><td><a target='_blank' href='https://omnicha.in?address=" . $row['address'] . "'>" . $row['address'] . "</a></td><td>" . $row['date'] . "</td></tr>";
			}
			
			if ($addresses == "") {
				$addresses = "<tr class='trow1'><td>No address history</td><td></td></tr>";	
			}
	
			// grab our template
			$template = $templates->get("Omnicoin Address History");
			eval("\$page=\"" . $template . "\";");
			output_page($page);
		}
	}
}

function grabData($url){
	curl_setopt($ch = curl_init(), CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 2);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$output = curl_exec($ch);
	curl_close($ch);

	return $output;
}

function verifyAddress($address, $message, $signature) {
	//Returns whether or not the signature is valid for the message for this address (boolean).
	//Assumes address is already validated.
	
	//Fix for PHP thinking that + is multiple strings there are multiple strings
	$signature = preg_replace("/[+]/", "%2B", $signature);
	
	$response = json_decode(grabData("https://omnicha.in/api?method=verifymessage&address=" . urlencode($address) . "&message=" . urlencode($message) . "&signature=" . urlencode($signature)), TRUE);
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

function checkAddress($addr) {
	//Returns whether or not the address is valid (boolean).

	$addr = decodeBase58($addr);
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

function decodeBase58($base58) {
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

    $return = encodeHex($return);
	
    //leading zeros
    for ($i = 0; $i < strlen($origbase58) && $origbase58[$i] == "1"; $i++) {
      $return = "00" . $return;
    }

    if (strlen($return) % 2 != 0) {
      $return = "0" . $return;
    }

    return $return;
}

function encodeHex($dec) {
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

function format_num($val, $precision = 10) {
	$to_return = rtrim(rtrim(number_format(round($val, $precision), $precision), "0"), ".");
	return $to_return == "" ? "0" : $to_return;
}

function getAddressBalance($address) {
	//Returns the balance of the given address (double). 
	//Assumes address is already validated.
	
	$response = json_decode(grabData("https://omnicha.in/api/?method=getbalance&address=" . urlencode($address)), TRUE);
	if ($response) {
		if (!$response['error']) {
			return $response['response']['balance'];
		} else {
			return 0;
		}
	} else {
		return 0;
	}
}
