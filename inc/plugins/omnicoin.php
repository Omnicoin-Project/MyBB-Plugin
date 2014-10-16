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
	
	//Create address table here
	global $mybb, $db, $cache;

  	if(!$db->table_exists("omcaddresses"))
	{
		$db->query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."omcaddresses` (
	 	`omcid` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  		`uid` int(10) NOT NULL,
  		`address` varchar(34) NOT NULL,
  		PRIMARY KEY (`omcid`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
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
}

function omnicoin_deactivate()
{
	//Called whenever a plugin is deactivated. This should essentially "hide" the plugin from view by removing templates/template changes etc. It should not, however, remove any information such as tables, fields etc - that should be handled by an _uninstall routine. When a plugin is uninstalled, this routine will also be called before _uninstall() if the plugin is active.
}

$plugins->add_hook('member_profile_start', 'omnicoinprofile');

function omnicoinprofile
{
	//called whenever someone opens there profile. Omnicoin address display field and history button must be added here.
}

function verifyaddress($address,$message,$signature)
{
	//This is not finished!!!
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
