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
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

// Hooks
$plugins->add_hook('misc_start','OmnicoinMisc');

$plugins->add_hook('member_profile_start', 'OmnicoinProfile');
//$plugins->add_hook('member_profile_end', 'OmnicoinProfile');

$plugins->add_hook('showthread_start', 'OmnicoinThread');
$plugins->add_hook('forumdisplay_thread', 'OmnicoinThread');



function omnicoin_info()
{
	return array(
		"name"		=> "Omnicoin integration",
		"description"	=> "This plugin integrates omnicoin addresses with user profiles",
		"website"	=> "http://www.omnicoin.org",
		"author"	=> "MeshCollider",
		"authorsite"	=> "https://github.com/MeshCollider",
		"version"	=> "1.0",
		"guid" 		=> "",
		"compatibility" => "*"
	);
}

function omnicoin_install()
{
	//Called whenever a plugin is installed by clicking the "Install" button in the plugin manager.
	//It is common to create required tables, fields and settings in this function.
	
	global $mybb, $db, $cache;

  	if(!$db->table_exists("omcaddresses"))
	{
		//create the omcaddress table here.
		// columns needed: uid, address
	}
}

function omnicoin_is_installed()
{
	//Called on the plugin management page to establish if a plugin is already installed or not.
	//This should return TRUE if the plugin is installed (by checking tables, fields etc) or FALSE if the plugin is not installed.
	
	//Check if the address table is created
	global $mybb, $db;
  	if($db->table_exists("omcaddresses"))
	{
		return true;
	}
}

function omnicoin_uninstall()
{
	//Called whenever a plugin is to be uninstalled. This should remove ALL traces of the plugin from the installation (tables etc). If it does not exist, uninstall button is not shown.
	
	//Delete the address table
	global $mybb, $db, $cache;

  	if($db->table_exists("omcaddresses"))
	{
		//delete the table here
	}
}

function omnicoin_activate()
{
	//Called whenever a plugin is activated via the Admin CP. This should essentially make a plugin "visible" by adding templates/template changes, language changes etc.
	global $db, $mybb;
	
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

	$template = array(
		"title"		=> "omc_address_profile",
		"template"	=> '<tr>
		<td class="trow2"><strong>Omnicoin address:</strong></td>
		<td class="trow2">{$address}&nbsp;<a href="misc.php?action=omchistory&uid={$memprofile[\\\'uid\\\']}">[History]</a></td>
		</tr>',
		"sid"		=> -1
	);
	$db->insert_query("templates", $template);
	find_replace_templatesets("member_profile", "#".preg_quote('{$warning_level}')."#i", '{\$warning_level}{\$omc_address_profile}');

    	// create a setting group to house our setting
    	$OmnicoinPluginSettings = array(
        "name"            	=> "OmnicoinPluginSettings",
        "title"         	=> "Omnicoin integration",
        "description"    	=> "Enable or disable the omnicoin plugin.",
        "disporder"     	=> "0",
        "isdefault"        	=> "no",
    	);
    
	 // insert the setting group into the database
    	$db->insert_query("settinggroups", $OmnicoinPluginSettings);
    
    	// grab insert ID of the setting group
    	$gid = intval($db->insert_id());
    
    	// we're only going to insert 1 setting
    	$setting = array(
        "name"            	=> "OmnicoinPlugin_enabled",
        "title"            	=> "Enabled",
        "description"    	=> "Determine if you want to enable this plugin",
        "optionscode"   	=> "yesno",
        "value"            	=> "1",
        "disporder"        	=> 1,
        "gid"           	=> $gid
        );
    
    	$db->insert_query("settings", $setting);
    	rebuildsettings();
    	
    	$AddressHistoryTemplate = array(
        "tid"        	=> NULL,
        "title"        	=> "OmnicoinAddress_History",
        "template"    	=> '
	<html>
    	<head>
        	<title>Address history</title>
        	{$headerinclude}
    	</head>
    	<body>
        	{$header}
        	<h2>Address history for: Member</h2>
        	<br />
        	<table class="tborder">
            		<tr class="thead">
                		<th><strong>Omnicoin address:</strong></th>
                		<th><strong>Date added:</strong></th>
            		</tr>
            	{$omcaddresses}
        	</table>
        	{$footer}
    	</body>
	</html>',
        "sid"        => "-1"
    	);
}

function omnicoin_deactivate()
{
	//Called whenever a plugin is deactivated. This should essentially "hide" the plugin from view by removing templates/template changes etc. It should not, however, remove any information such as tables, fields etc - that should be handled by an _uninstall routine. When a plugin is uninstalled, this routine will also be called before _uninstall() if the plugin is active.
	global $db, $mybb;
	
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("member_profile", "#".preg_quote('{$omc_address_profile}')."#i", '', 0);
	$db->query("DELETE FROM ".TABLE_PREFIX."templates WHERE title = 'omc_address_profile'");
}

function OmnicoinProfile
{
	//called whenever someone opens there profile.
	global $db, $templates, $mybb, $memprofile, $templates, $omc_address, $details;
	
	// if the plugin setting isn't enabled then exit
    	if($mybb->settings['OmnicoinPlugin_enabled'] != 1)
        	return;
	
	$query = $db->query("SELECT address FROM ".TABLE_PREFIX."omcaddresses WHERE uid='".$memprofile['uid']."'");
	if(num_rows($query) > 1)
	{
		//add history button	
	}
	
	$details = " <a href=\"misc.php?action=omchistory&uid=".$mybb->input['uid']."\">[History]</a>";
	
	//display current address on profile
	eval("\$omcaddresses = \"".$templates->get("omc_address_profile")."\";");
}


function OmnicoinThread
{
	//called when a thread is viewed.
}

function OmnicoinMisc
{
	//called on opening misc.php
	//This is where the address add code and the history list will be displayed
}

function verifyAddress($address, $message, $signature) {
	//Returns whether or not the signature is valid for the message for this address (boolean).
	//Assumes address is already validated.
	
	$response = json_decode(file_get_contents("https://omnicha.in/api?method=verifymessage&address=" . urlencode($address) . "&message=" . urlencode($message) . "&signature=" . urlencode($signature)), true);
	if ($response != null) {
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

	$response = json_decode(file_get_contents("https://omnicha.in/api?method=checkaddress&address=" . urlencode($address)), true);
	if ($response != null) {
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
	
	$response = json_decode(file_get_contents("https://omnicha.in/api/?method=getbalance"), true);
	if ($response != null) {
		if (!$response['error']) {
			return $response['response']['balance'];
		} else {
			return false;
		}
	} else {
		return false;
	}
}


