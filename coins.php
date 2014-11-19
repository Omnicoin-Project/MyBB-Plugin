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

require_once "./global.php";
$lang->load("ranks");
add_breadcrumb($lang->listranks);

if($mybb->user['uid'] == 0)
{
	error_no_permission();
}

//require_once MYBB_ROOT."inc/plugins/omnicoin.php";

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
		<form action="misc.php?action=omcsearch" method="post">
			<input class="textbox" type="text" name="search">
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
		

function omnicoin_misc_start() {
	//Handle misc.php funtionality
	global $mybb, $db, $templates, $headerinclude, $header, $footer, $username, $entries, $search, $username;
	
	//Check to see if the user viewing the page is logged in, otherwise return.
	if (!($mybb->user['uid'])) {
		return;
	}

	if (isset($mybb->input['action'])) {
		if ($mybb->input['action'] == "history") {
			if (isset($mybb->input['uid'])) {
				$uid = $mybb->input['uid']; 
			} else {
				$uid = $mybb->user[uid];
			}
			$uid = intval(preg_replace("/[^0-9]/", "", $uid));
			
			// get the username corresponding to the UID passed to the miscpage
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
			if (isset($mybb->input['search'])) {
				$search = $db->escape_string(preg_replace("/[^A-Za-z0-9]/", "", $mybb->input['search']));
				
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
		}
	}
}

?>
