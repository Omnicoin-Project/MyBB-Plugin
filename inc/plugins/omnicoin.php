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
$plugins->add_hook('misc_start','OmnicoinMisc');

$plugins->add_hook('member_profile_start', 'OmnicoinProfile');

$plugins->add_hook('usercp_profile_start', 'OmnicoinUserCP');

$plugins->add_hook("datahandler_user_update", "omnicoin_user_update");

//We may need these when we add balance displays to posts. This may cause too many requests to the API though.
//$plugins->add_hook('showthread_start', 'OmnicoinThread');
//$plugins->add_hook('forumdisplay_thread', 'OmnicoinThread');

function omnicoin_info() {
	return array(
		"name"			=> "Omnicoin integration",
		"description"	=> "This plugin integrates omnicoin addresses with user profiles",
		"website"		=> "http://www.omnicoin.org",
		"author"		=> "MeshCollider",
		"authorsite"	=> "https://github.com/MeshCollider",
		"version"		=> "1.0",
		"guid" 			=> "",
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
			`id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
			`uid` varchar(10) NOT NULL DEFAULT '',
			`address` varchar(34) NOT NULL DEFAULT '',
			`date` DATETIME NOT NULL,
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
    	
	find_replace_templatesets("member_profile", '#' . preg_quote('{$warning_level}') . '#', '{$warning_level}<tr><td class="trow1"><strong>Omnicoin address:</strong></td><td class="trow1">{$address}</td></tr>');
	find_replace_templatesets("usercp_profile", '#' . preg_quote('{$customfields}') . '#', '{$omcoptions}{$customfields}');
}

function omnicoin_deactivate() {
	//Called whenever a plugin is deactivated. This should essentially "hide" the plugin from view by removing templates/template changes etc. It should not, however, remove any information such as tables, fields etc - that should be handled by an _uninstall routine. When a plugin is uninstalled, this routine will also be called before _uninstall() if the plugin is active.
	
	global $db;
	
	include MYBB_ROOT."/inc/adminfunctions_templates.php";

	$db->delete_query("templates", "title LIKE 'Omnicoin Address History'");
	
	//Delete omnicoin address from profile template
	find_replace_templatesets("member_profile", '#' . preg_quote('<tr><td class="trow1"><strong>Omnicoin address:</strong></td><td class="trow1">{$address}</td></tr>') . '#', '');
	find_replace_templatesets("usercp_profile", '#' . preg_quote('{$omcoptions}') . '#', '');
}

function OmnicoinProfile() {
	//called whenever someone opens their profile.
	
	global $db, $mybb, $address;

	$query = $db->simple_select("omcaddresses", "address", "uid = '" . $mybb->input['uid'] . "'", array("order_by" => "date", "order_dir" => "DESC", "limit" => 1));
	$returndata = $db->fetch_array($query);
	$address = $returndata['address'];	
	if ($address == "") {
		$address = "None specified";
	} else {
		$address = $address . '&nbsp;<a href="misc.php?action=omchistory&amp;uid=' . $mybb->input['uid'] . '">[History]</a>';
	}
}


function OmnicoinThread() {
	//called when a thread is viewed. This may be needed when we implement balances on posts.
}

function OmnicoinUserCP() {
	//called when a user opens options page of usercp.
	
	global $db, $omcoptions, $mybb;

	$uid = $mybb->user[uid];
	session_start();
	$_SESSION['omc_signing_message'] = "Omnicoin Address Confirmation " . substr(md5(microtime()), rand(0, 26), 10) . " " . date("y-m-d H:i:s");
	
	$query = $db->simple_select("omcaddresses", "address", "uid='" . $mybb->user['uid'] . "'", array("order_by" => "date", "order_dir" => "DESC", "limit" => 1));
	$returndata = $db->fetch_array($query);
	$address = $returndata['address'];	
	
	$omcoptions = '<br />
<fieldset class="trow2">
	<legend>
		<strong>Omnicoin address</strong>
	</legend>
	<table cellspacing="0" cellpadding="2">
		<tr>
			<td colspan=2>Add an omnicoin address to your profile</td>
		</tr>
		<tr>
			<td>Address:</td><td><input type="text" class="textbox" size="40" name="omc_address" value="' . ($address != "" ? $address : "") . '" /></td>
		</tr>
		<tr>
			<td>Signing message:</td><td>' . $_SESSION['omc_signing_message'] . '</td></td>
		</tr>
		<tr>
			<td>Signature:</td><td><input type="text" class="textbox" size="40" name="omc_signature" /></td>
		</tr>
	</table>
</fieldset>';
}

function omnicoin_user_update($userhandler) {
	//this is where we will put the code to handle verification and storing of the addresses

	global $mybb, $db;
	
	if ($mybb->input['action'] == "do_profile") {
		session_start();
		$omcerrormessage = "";
		if (isset($mybb->input['omc_address']) && isset($mybb->input['omc_signature']) && !empty($mybb->input['omc_address'])) {
			//Whitelist address so user can't inject into DB or API calls
			$address = preg_replace('/[^A-Za-z0-9]/', '', $mybb->input['omc_address']);
			$signature = preg_replace('/[^A-Za-z0-9=+-\/]/', '', $mybb->input['omc_signature']);
			if (checkAddress($address)) {
				if (verifyAddress($address, $_SESSION['omc_signing_message'], $signature)) {
					$db->query("INSERT INTO ".TABLE_PREFIX."omcaddresses (uid, address, date) VALUES ('" . $mybb->user['uid'] . "', '" . $address . "', '" . date("Y-m-d H:i:s") . "')");
					//Display success message
					//$omcerrormessage = 'Success!';
				} else {
					//Display signature invalid message
					$omcerrormessage = 'Error: Invalid Omnicoin address signature';
				}
			} else {
				//Display address invalid message
				$omcerrormessage = 'Error: Invalid Omnicoin address';
			}
		}
		
		if ($omcerrormessage != "") {
			echo '<script language="javascript">';
			echo 'alert("'. $omcerrormessage .'")';
			echo '</script>';
		}
	}
}

function OmnicoinMisc() {
	//Handle misc.php funtionality
	global $mybb, $db, $templates;
	
	//Check to see if the user viewing the page is logged in, otherwise return.
	if (!($mybb->user['uid'])) {
		echo "You are not logged in";
		return;
	}

	if (isset($mybb->input['action'])) {
		if ($mybb->input['action'] == "omchistory") {
			if (isset($mybb->input['uid'])) {
				$uid = $mybb->input['uid']; 
			} else {
				$uid = $mybb->user[uid];
			}
			$uid = preg_replace('/[^0-9]/', '', $uid);
			// get the username corresponding to the UID passed to the miscpage
			$grabuser = $db->simple_select("users", "username", "uid = " . $uid);
			$user = $db->fetch_array($grabuser);
			$username = $user['username'];
	
			//get all past addresses from table
			$query = $db->simple_select("omcaddresses", "address,date", "uid = '" . $uid . "'", array("order_by" => "date", "order_dir" => "ASC"));
			$addresses = '';
	
			// loop through each row in the database that matches our query and create a table row to display it
			while($row = $db->fetch_array($query)){
				$addresses = $addresses . "<tr class='trow1'><td>".$row['address']."</td><td>" . $row['date'] . "</td></tr>";
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
	$signature = preg_replace('/[+]/', '%2B', $signature);
	
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

function checkAddress($address) {
	//Returns whether or not the address is valid (boolean).

	$response = json_decode(grabData("https://omnicha.in/api?method=checkaddress&address=" . urlencode($address)), TRUE);
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

function getAddressBalance($address) {
	//Returns the balance of the given address (double). 
	//Assumes address is already validated.
	
	$response = json_decode(grabData("https://omnicha.in/api/?method=getbalance&address=" . urlencode($address)), TRUE);
	if ($response) {
		if (!$response['error']) {
			return $response['response']['balance'];
		} else {
			return false;
		}
	} else {
		return false;
	}
}
