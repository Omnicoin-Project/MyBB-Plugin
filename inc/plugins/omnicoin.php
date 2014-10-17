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
}

function omnicoin_activate()
{
	//Called whenever a plugin is activated via the Admin CP. This should essentially make a plugin "visible" by adding templates/template changes, language changes etc.
	global $db, $mybb;
	
	require MYBB_ROOT."/inc/adminfunctions_templates.php";
	$template = array(
		"title"		=> "omc_address_profile",
		"template"	=> '<tr>
		<td class="trow2"><strong>Omnicoin address:</strong></td>
		<td class="trow2">{$address}&nbsp;<a href="omchistory.php?uid={$memprofile[\\\'uid\\\']}">[History]</a></td>
		</tr>',
		"sid"		=> -1
	);
	$db->insert_query("templates", $template);
	find_replace_templatesets("member_profile", "#".preg_quote('{$warning_level}')."#i", '{\$warning_level}{\$omc_address_profile}');
}

function omnicoin_deactivate()
{
	//Called whenever a plugin is deactivated. This should essentially "hide" the plugin from view by removing templates/template changes etc. It should not, however, remove any information such as tables, fields etc - that should be handled by an _uninstall routine. When a plugin is uninstalled, this routine will also be called before _uninstall() if the plugin is active.
	global $db, $mybb;
	
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("member_profile", "#".preg_quote('{$omc_address_profile}')."#i", '', 0);
	$db->query("DELETE FROM ".TABLE_PREFIX."templates WHERE title = 'omc_address_profile'");
}

$plugins->add_hook('member_profile_start', 'omnicoinprofile');
$plugins->add_hook("member_profile_end", "omnicoinprofile");

function omnicoinprofile
{
	//called whenever someone opens there profile.
	global $db, $mybb, $memprofile, $templates, $omc_address;
	
	$query = $db->query("SELECT address FROM ".TABLE_PREFIX."threads WHERE uid='".$memprofile['uid']."'");
	if(num_rows($query) > 1)
	{
		//add history button	
	}
	//display current address on profile
	eval("\$omc_address_profile = \"".$templates->get("omc_address_profile")."\";");
}

$plugins->add_hook("showthread_start", "omnicointhread");
$plugins->add_hook("forumdisplay_thread", "omnicointhread");

function omnicointhread
{
	//called when a thread is viewed.
}

function verifyaddress($address,$message,$signature)
{
	$response = json_decode(file_get_contents("https://omnicha.in/api?method=verifymessage&address=". $address . "&message=" . $message . "&signature=". $signature), true);
	if ($response != null) {
		if ($response['error']) {
			echo "Error occurred: " . $response['error_info'];
		} else {
			$info = $response['response'];
			echo "Verified: " . $info[0];
		}
	}
}
