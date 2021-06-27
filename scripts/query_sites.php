<?php

/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2021 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/
/*******************************************************************************

	Author ......... James Payne
	Contact ........ jamoflaw@gmail.com
	Home Site ...... http://withjames.co.uk
	Program ........ Cacti Availability Plugin
	Purpose ........ Creates Availability Statistics - Includes reporting structure

*******************************************************************************/

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
   die("<br><strong>This script is only meant to run at the command line.</strong>");
}

# include some cacti files for ease of use
if (file_exists(__DIR__ . "/../../../include/cli_check.php")) {
	include_once(__DIR__ . "/../../../include/cli_check.php");
} else {
	include_once(__DIR__ . "/../../../include/global.php");
}
include_once(__DIR__ . "/../include/functions.php");

$func_map = [
	'id'                 => 'id',
	'sites'              => 'url',
	'site'               => 'url',
	'text'               => 'text_match',
	'values'             => 'total_time',
	'http_code'          => 'http_code',
	'downloadsize'       => 'size_download',
	'redirect_count'     => 'redirct_count',
	'availability'       => 'availability',
	'namelookup_time'    => 'namelookup_time',
	'connect_time'       => 'connect_time',
	'pretransfer_time'   => 'pretransfer_time',
	'starttransfer_time' => 'starttransfer_time',
	'redirect_time'      => 'redirect_time',
];

$hostname = $_SERVER["argv"][1];		# hostname/IP@
$cmd      = $_SERVER["argv"][2];		# one of: index/query/get
$id       = '';
$values   = [];

if (isset($_SERVER["argv"][3])) {
	if (array_key_exists($_SERVER["argv"][3], $func_map)) {
		$values = [ $func_map[$_SERVER["argv"][3]] ];
	}
} else {
	$values = array_values($func_map);
}

if (isset($_SERVER["argv"][4])) {
	$id = $_SERVER["argv"][4];
}

switch ($cmd)
{
	case "count":
		$indexes = mURLin_Reindex($hostname);

		if (is_array($indexes))
		{
			print count($indexes);
		}

		break;

	case "index":
		$indexes = mURLin_Reindex($hostname);

		if (is_array($indexes))
		{
			foreach($indexes as $i)
			{
				print $i . "\n";
			}
		}

		break;

	case "query":
		$results = mURLin_gatherData($hostname, $values );

		if (count($results)) {
			foreach($results as $i => $r) {
				$spacer = '';

				print $i . '!!';
				foreach ($r as $k => $v) {
					if (count($values) > 1) {
						print $spacer . $k . ':';
					}
					print $v;
					$spacer = ' ';
				}
				print "\n";
			}
		}

		break;

	 case "get":
		$results = mURLin_gatherData($hostname, $values, $id );
		foreach($results as $i)
		{
			print implode(' ', $i) . "\n";
		}
		break;

	default:
		print "Invalid use of script query.\n\n";
		break;
}

?>