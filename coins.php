<?php
/**
 * Omnicoin plugin for MyBB
 * Copyright (c) 2014 by the Omnicoin Team.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Visit https://github.com/Omnicoin-Project/Omnicoin/wiki for more details
 */

define("IN_MYBB", 1);
define("THIS_SCRIPT", "coins.php");

require_once "./global.php";

//check if plugin is enabled
if (!array_key_exists("omnicoin", $cache->read("plugins")['active'])) {
	die();
}

if (!$mybb->user['uid']) {
	error_no_permission();
}

if (isset($mybb->input['action'])) {
	if ($mybb->input['action'] == "history") {
		if (isset($mybb->input['uid'])) {
			$uid = $mybb->input['uid']; 
		} else {
			$uid = $mybb->user[uid];
		}
		$uid = intval(preg_replace("/[^0-9]/", "", $uid));
			
		// get the username corresponding to the UID passed to the page
		$grabuser = $db->simple_select("users", "username", "uid = '" . $uid . "'");
		$user = $db->fetch_array($grabuser);
		$username = $user['username'];
	
		//get all past addresses from table
		$query = $db->simple_select("omcaddresses", "address, date", "uid = '" . $uid . "'", array("order_by" => "date", "order_dir" => "ASC"));
		$entries = "";
			
		if ($query->num_rows > 0) {
			// loop through each row in the database that matches our query and create a table row to display it
			while($row = $db->fetch_array($query)){
				$address = $row['address'];
				$date = $row['date'];
				$template = $templates->get("Omnicoin Address History Entry");
				eval("\$entries .=\"" . $template . "\";");
			}
		} else {
			$message = "No address history";
			$template = $templates->get("Omnicoin Address History No Entry");
			eval("\$entries .=\"" . $template . "\";");
		}
		
		// grab our template
		$template = $templates->get("Omnicoin Address History");
		eval("\$page=\"" . $template . "\";");
		output_page($page);
	} else if ($mybb->input['action'] == "search") {
		if (isset($mybb->input['query'])) {
			$search = $db->escape_string(preg_replace("/[^A-Za-z0-9]/", "", $mybb->input['query']));
				
			//Get all addresses matching search term
			$query = $db->simple_select("omcaddresses", "address, date, uid", "address LIKE CONCAT('%', '" . $search . "', '%')", array("order_by" => "date", "order_dir" => "ASC"));
			$entries = "";
				
			if ($query->num_rows > 0) {
				//Loop through each row in the database that matches our query and create a table row to display it
				while($row = $db->fetch_array($query)){
					$grabuser = $db->simple_select("users", "username", "uid = '" . $row['uid'] . "'");
					$user = $db->fetch_array($grabuser);
						
					$username = $user['username'];
					$userid = $row['uid'];
					$address = $row['address'];
					$date = $row['date'];
					$template = $templates->get("Omnicoin Address Search Results Entry");
					eval("\$entries .=\"" . $template . "\";");
				}
			} else {
				$message = "No results found";
				$template = $templates->get("Omnicoin Address Search Results No Entry");
				eval("\$entries .=\"" . $template . "\";");
			}
				
			$template = $templates->get("Omnicoin Address Search Results");
			eval("\$page=\"" . $template . "\";");
			output_page($page);
		} else {			
			$template = $templates->get("Omnicoin Address Search");
			eval("\$page=\"" . $template . "\";");
			output_page($page);
		}
	} else if ($mybb->input['action'] == "newaddress") {
		//find_replace_templatesets("usercp_profile", "#" . preg_quote('{$customfields}') . "#", 		'{$omcaddform}{$customfields}');
	//find_replace_templatesets("usercp_profile", "#" . preg_quote('{$omcaddform}') . "#", "");
	
	} else {
		$template = $templates->get("Omnicoin Default Page");
		eval("\$page=\"" . $template . "\";");
		output_page($page);
	}
} else {
	$template = $templates->get("Omnicoin Default Page");
	eval("\$page=\"" . $template . "\";");
	output_page($page);	
}

function omnicoin_usercp_profile_start() {
	//called when a user opens options page of usercp.
	
	global $db, $omcaddform, $mybb;

	$signingmessage = $mybb->settings['bbname'] . " Omnicoin Address Confirmation - User: " . $mybb->user['username'] . " UID: " . $mybb->user['uid'];
	
	$query = $db->simple_select("omcaddresses", "address", "uid='" . $mybb->user['uid'] . "'", array("order_by" => "date", "order_dir" => "DESC", "limit" => 1));
	if ($query->num_rows == 1) {
		$returndata = $db->fetch_array($query);
		$address = $returndata['address'];	
	} else {
		$address = "";
	}
	
	$omcaddform = "<br />
<fieldset class='trow2'>
	<legend>
		<strong>Omnicoin address</strong>
	</legend>
	<table cellspacing='0' cellpadding='2'>
		<tr>
			<td colspan=2>Add an omnicoin address to your profile. Follow <a href='https://github.com/Omnicoin-Project/Omnicoin/wiki/Signing-a-message-using-Omnicoin'>this tutorial</a>.</td>
		</tr>
		<tr>
			<td>Address:</td><td><input type='text' class='textbox' size='40' name='omc_address' value='" . $address . "' /></td>
		</tr>
		<tr>
			<td>Signing message:</td><td>" . $signingmessage . "</td></td>
		</tr>
		<tr>
			<td>Signature:</td><td><input type='text' class='textbox' size='40' name='omc_signature' /></td>
		</tr>
	</table>
</fieldset>";
}

function omnicoin_user_update($userhandler) {
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
?>
