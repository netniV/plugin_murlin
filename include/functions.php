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
/* * *****************************************************************************

  Author ......... James Payne
  Contact ........ jamoflaw@gmail.com
  Home Site ...... http://withjames.co.uk
  Program ........ Cacti URL Monitoring Plugin
  Purpose ........ Creates URL Monitoring Structure

 * ***************************************************************************** */

include_once(dirname(__FILE__) . "/../../../include/global.php");

function mURLin_includejavascript($filepath) {
	print '<script type="text/javascript">';
	include("$filepath");
	print '</script>';
}

function mURLin_AddDBColumnIfNotExist($tablename, $columndata) {
	global $database_default;

	// Only add the column if it doesn't exist!
	if (mURLin_ColumnExist($tablename, $columndata['name']) != true) {
		api_plugin_db_add_column('mURLin', $tablename, $columndata);
	} else {
		// Check for column validity
		$columnname = $columndata['name'];
		$column_type = $columndata['type'];
		$cactidb = $database_default;

		$sql = "SELECT column_name, column_type
				FROM INFORMATION_SCHEMA.COLUMNS
				WHERE COLUMN_NAME='$columnname'
				AND TABLE_NAME='$tablename'
				AND TABLE_SCHEMA='$cactidb'";

		$result = db_fetch_row($sql);

		if ($result['column_type'] != $column_type) {
			// This means the column type has changed since the table was created
			if(read_config_option("log_verbosity") <= POLLER_VERBOSITY_DEBUG) {
				cacti_log("mURLin: Table columns have changed!");
			}

			if(read_config_option("log_verbosity") <= POLLER_VERBOSITY_DEBUG) {
				cacti_log("mURLin: Altering column " . $columnname . " to " . $column_type . " from " . $result['column_type']);
			}

			$sql = "ALTER TABLE $tablename MODIFY $columnname $column_type";
			db_execute($sql);
		}
	}
}

function mURLin_TableExist($tablename) {
	global $database_default;

	$sql = "SELECT * FROM information_schema.COLUMNS
		WHERE TABLE_SCHEMA='$database_default'
		AND TABLE_NAME=?";

	$result = db_fetch_assoc_prepared($sql, array($tablename));

	if (count($result) == 0) {
		return false;
	} else {
		return true;
	}
}

function mURLin_ColumnExist($tablename, $columname) {
	global $database_default;

	$sql = "SELECT * FROM information_schema.COLUMNS
		WHERE TABLE_SCHEMA='$database_default'
		AND TABLE_NAME=?
		AND COLUMN_NAME=?";

	$result = db_fetch_assoc_prepared($sql,array($tablename,$columname));

	if (count($result) == 0) {
		return false;
	} else {
		return true;
	}
}

function mURLin_Reindex($hostid) {

	input_validate_input_number($hostid);

	$sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = ?";

	$result = db_fetch_assoc_prepared($sql, array($hostid));

	// Init indexes
	$indexes = array();
	if (is_array($result)) {
		foreach ($result as $r) {
			$indexes[] = $r['id'];
		}
	}

	return $indexes;
}

/*
 * Function to get cURL to load a webpage and return information on the
 * request.
 *
 * $input will accept either of the following:
 *
 * URL Mapping ID
 * Array of URL Information
 *
 * $output will vary depending on the string passed
 *
 * $input requires:
 * url
 * text_match
 * timeout
 * proxyserver
 * proxyaddress
 * proxyusername
 * proxypassword
 *
 */

function mURLin_cache_page($input, $output = "NONE") {
	// Default caching option
	$insert_into_cache = true;

	 // Have we accessed this from the DB cache
	$accessed_from_cache = false;

	 // Ensure $body is always defined
	$body = "";

	if (is_array($input)) {
		// We are dealing with a request rather than a DB id lookup
		// We can NEVER cache this
		$insert_into_cache = false;

		// Variables
		$url         = $input['url'];
		$text_match  = $input['text_match'];
		$timeout     = $input['timeout'];
		$proxyserver = $input['proxyserver'];


		// Deal with a proxy if required
		if ($proxyserver != 0) {
			$proxyaddress = $input['proxyaddress'];

			$proxyusername = isset($input['proxyusername']) ? $input['proxyusername'] : "";
			$proxypassword = isset($input['proxypassword']) ? $input['proxypassword'] : "";

			// Check for proxy authentication
			if ($proxyusername == "" && $proxypassword == "") {
				$proxyident = "";
			} else {
				$proxyident = $proxyusername . ':' . $proxypassword;
			}
		} else {
			$proxyaddress = "";
		}
	 } else {
		// Check if there is already a cached version of this query
		$sql = "SELECT * FROM plugin_mURLin_cache WHERE id = ?";
		$db_result = db_fetch_row_prepared($sql, array($input));

		if (count($db_result) != 0) {
			// There is a cached result
			if(read_config_option("log_verbosity") <= POLLER_VERBOSITY_DEBUG) {
				cacti_log("mURLin - INFO: Result has been accessed from Cache");
			}

			// No need to recache
			$insert_into_cache = false;

			$accessed_from_cache = true;
			$info = $db_result;
		} else {
			if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) {
				 cacti_log("mURLin - INFO: Cache Miss");
			}
		}

		// Get DB info
		$sql = "SELECT * FROM plugin_mURLin_index WHERE id = ?";
		$db_result = db_fetch_row_prepared($sql, array($input));

		// Variables
		$host_id     = $db_result['host_id'];
		$url         = $db_result['url'];
		$text_match  = $db_result['text_match'];
		$timeout     = intval($db_result['timeout']);
		$proxyserver = $db_result['proxyserver'];

		if ($timeout <= 0) {
			$timeout = 5;
		}

		// Deal with a proxy if required
		if ($proxyserver != 0) {
			$proxyaddress  = $db_result['proxyaddress'];
			$proxyusername = $db_result['proxyusername'];
			$proxypassword = $db_result['proxypassword'];

			// Check for proxy authentication
			if ($proxyusername == "" && $proxypassword == "") {
				 $proxyident = "";
			} else {
				 $proxyident = $proxyusername . ':' . $proxypassword;
			}
		} else {
			$proxyaddress = "";
		}
	}

	// Cache this page in the database for quicker lookups later
	// If no regex is supplied default allow all is assumed
	// if $info is already set here we can assume we are doing a DB lookup
	// which will mean the text match has already been applied
	if ($text_match == "" || isset($info)) {
		$text_match = "//";
	}

	if (!isset($info)) {
		// Info is not defined so the result is not cached!
		$page = curl_init($url);

		curl_setopt($page, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($page, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($page, CURLOPT_SSL_VERIFYHOST, false);

		// Set a sensible timeout
		curl_setopt($page, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($page, CURLOPT_TIMEOUT, $timeout + 3);

		// Set redirect options
		curl_setopt($page, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($page, CURLOPT_MAXREDIRS, 10);

		// Set a proxy if required
		if ($proxyaddress != "") {
			curl_setopt($page, CURLOPT_PROXY, $proxyaddress);
			curl_setopt($page, CURLOPT_HTTPPROXYTUNNEL, 0);

			if ($proxyident != "") {
				 curl_setopt($page, CURLOPT_PROXYUSERPWD, $proxyident);
			}
		}

		if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) {
			cacti_log("mURLin - INFO: Getting URL Information");
		}

		$body = curl_exec($page);

		if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) {
			cacti_log("mURLin - INFO: Getting URL Information ... Done");
		}

		if ($body == "") {
			// Nothing came back in the return
			$curl_error = "ERROR - Nothing returned - CURL ERROR:";
			curl_error($page);

			$curl_error .= "\n\nUsing Proxy Address: " . $proxyaddress;
		}

		// First check if we have successfully downloaded the webpage
		$info = curl_getinfo($page);

		curl_close($page);
	}

	$total_time         = "";
	$http_code          = "";
	$size_download      = "";
	$redirect_count     = "";
	$availability       = "";
	$namelookup_time    = "";
	$connect_time       = "";
	$pretransfer_time   = "";
	$starttransfer_time = "";
	$redirect_time      = "";

	 if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) {
		cacti_log("mURLin - INFO: Doing Text Match");
	}

	// Text Match
	if (@preg_match($text_match, $body)) {
		// Page Text matches

		$total_time     = $info['total_time'];
		$http_code      = $info['http_code'];
		$size_download  = $info['size_download'];
		$redirect_count = $info['redirect_count'];

		// Ensure that if we accessing from the DB cache that the DB value
		// is taken. This is due to the DB lookup having no text_match to perform
		if ($accessed_from_cache) {
			$availability = $info['availability'];
		} else {
			$availability = 100;
		}

		$namelookup_time    = $info['namelookup_time'];
		$connect_time       = $info['connect_time'];
		$pretransfer_time   = $info['pretransfer_time'];
		$starttransfer_time = $info['starttransfer_time'];
		$redirect_time      = $info['redirect_time'];
	} else {
		// Page text doesn't match
		$total_time     = "0";
		$http_code      = $info['http_code'];
		$size_download  = $info['size_download'];
		$redirect_count = $info['redirect_count'];

		// Ensure that if we accessing from the DB cache that the DB value
		// is taken. This is due to the DB lookup having no text_match to perform
		if ($accessed_from_cache) {
			$availability = $info['availability'];
		} else {
			$availability = 0;
		}

		$namelookup_time    = "0";
		$connect_time       = "0";
		$pretransfer_time   = "0";
		$starttransfer_time = "0";
		$redirect_time      = "0";
	}

	if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) {
		cacti_log("mURLin - INFO: Doing Text Match... Done");
	}

	if ($insert_into_cache == true) {
		// Insert into database
		if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) {
			cacti_log("mURLin - INFO: Inserting into Cache");
		}

		$sql = "INSERT INTO plugin_mURLin_cache (
				id, total_time, http_code, size_download, redirect_count,
				availability, namelookup_time, connect_time, pretransfer_time,
				starttransfer_time, redirect_time
			)
			VALUES (?, ?, ? ,? ,?, ? ,? ,? ,?, ? ,?)";

		db_execute_prepared($sql, array(
			$input, $total_time, $http_code, $size_download, $redirect_count,
			$availability, $namelookup_time, $connect_time, $pretransfer_time,
			$starttransfer_time, $redirect_time));

		if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) {
				cacti_log("mURLin - INFO: Inserting into Cache... Done");
		}
	}

	$value = false;
	switch ($output) {
		case "NONE":
			// No value being returned
			break;

		case "body":
			// Return page body or error
			$value = ($body == "") ? $curl_error : $body;
			break;

		case "http_code":
			// Return the http_code variable
			$value = $http_code;
			break;

		case "total_time":
			$value = $total_time;
			break;

		case "size_download":
			$value = $size_download;
			break;

		case "redirect_count":
			$value = $redirect_count;
			break;

		case "availability":
			$value = $availability;
			break;

		case "namelookup_time":
			$value = $namelookup_time;
			break;

		case "connect_time":
			$value = $connect_time;
			break;

		case "pretransfer_time":
			$value = $pretransfer_time;
			break;

		case "starttransfer_time":
			$value = $starttransfer_time;
			break;

		case "redirect_time":
			$value = $redirect_time;
			break;
	}

	return $value;
}

function mURLin_gatherData($hostid, $fields, $id = false) {
	if (!defined('CACTI_CLI_ONLY')) {
		input_validate_input_number($hostid);
	} else {
		$host_id = intval($hostid);
	}

	$sql = "SELECT i.*, IFNULL(c.id,'0') AS cache_id
		FROM plugin_mURLin_index i
		LEFT JOIN plugin_mURLin_cache c
		ON c.id = i.id
		WHERE i.host_id = ?";

	$params = array($hostid);

	if (!empty($id)) {
		$sql     .= " AND i.id = ?";
		$params[] = $id;
	}

	$result = db_fetch_assoc_prepared($sql, $params);

	$sql2 = "SELECT * FROM plugin_mURLin_cache WHERE id = ?";

	// Init indexes
	$indexes = array();

	if (is_array($result)) {
		foreach ($result as $r) {
			if (empty($r['cache_id'])) {
				echo "Caching for ${r['id']}\n";
				mURLin_cache_page($r['id']);
			}

			$cache = db_fetch_row_prepared($sql2, array($r['id']));
			if (cacti_sizeof($cache)) {
				if (!is_array($fields)) {
					$fields = [ $fields ];
				}

				foreach ($fields as $field) {
					$indexes[$r['id']][$field] = (
						array_key_exists($field, $cache)?$cache[$field]:(
						array_key_Exists($field, $r)?$r[$field]:
						''));
				}
			}
		}
	}

	return $indexes;
}