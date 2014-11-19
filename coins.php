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
		
eval("\$page = \"".$templates->get("ranks")."\";");
output_page($page);
?>
