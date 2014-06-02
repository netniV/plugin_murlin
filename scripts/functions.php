<?php

/*
  +-------------------------------------------------------------------------+
  | Copyright (C) 2004-2009 The Cacti Group                                 |
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
            if(read_config_option("log_verbosity") <= POLLER_VERBOSITY_DEBUG)
                cacti_log("mURLin: Table columns have changed!");
            if(read_config_option("log_verbosity") <= POLLER_VERBOSITY_DEBUG)
                cacti_log("mURLin: Altering column " . $columnname . " to " . $column_type . " from " . $result['column_type']);

            $sql = "ALTER TABLE $tablename MODIFY $columnname $column_type";
            db_execute($sql);
        }
    }
}

function mURLin_TableExist($tablename) {
    global $database_default;

    sql_sanitize($tablename);


    $sql = "SELECT * FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA='$database_default'
        AND TABLE_NAME='$tablename'
            ";

    $result = db_fetch_assoc($sql);

    if (count($result) == 0)
        return false;
    else
        return true;
}

function mURLin_ColumnExist($tablename, $columname) {
    global $database_default;

    sql_sanitize($tablename);
    sql_sanitize($columname);

    $sql = "SELECT * FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA='$database_default'
            AND TABLE_NAME='$tablename'
            AND COLUMN_NAME='$columname';
            ";

    $result = db_fetch_assoc($sql);

    if (count($result) == 0)
        return false;
    else
        return true;
}

function reindex($hostid) {

    input_validate_input_number($hostid);
    $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " . $hostid;

    $result = db_fetch_assoc($sql);

    if (!is_array($result)) {
        return;
    }

    // Init indexes
    $indexes = array();
    foreach ($result as $r) {
        $indexes[] = $r['id'];
    }

    return $indexes;
}

function get_ids($hostid)
{
    input_validate_input_number($hostid);
    $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " . $hostid;

    $result = db_fetch_assoc($sql);

    if (!is_array($result)) {
        return;
    }

    // Init indexes
    $indexes = array();
    foreach ($result as $r) {
        $indexes[] = $r['id'] . "!!" . $r['id'];
    }

    return $indexes;
}

function get_sites($hostid) {
    input_validate_input_number($hostid);
    $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " . $hostid;

    $result = db_fetch_assoc($sql);

    if (!is_array($result)) {
        return;
    }

    // Init indexes
    $indexes = array();
    foreach ($result as $r) {
        $indexes[] = $r['id'] . "!!" . $r['url'];
    }

    return $indexes;
}

function get_text($hostid) {
    input_validate_input_number($hostid);
    $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " . $hostid;

    $result = db_fetch_assoc($sql);

    if (!is_array($result)) {
        return;
    }

    // Init indexes
    $indexes = array();
    foreach ($result as $r) {
        $indexes[] = $r['id'] . "!!" . $r['text_match'];
    }

    return $indexes;
}

function get_values($hostid) {
    input_validate_input_number($hostid);
    $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " . $hostid;

    $result = db_fetch_assoc($sql);

    if (!is_array($result)) {
        return;
    }

    // Init indexes
    $indexes = array();
    foreach ($result as $r) {
        return mURLin_cache_page($r, "total_time");
    }
}

function get_value($id) {
    return mURLin_cache_page($id, "total_time");
}

function mURLin_getRedirect_Counts($hostname) {
    input_validate_input_number($hostname);
    $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " . $hostname;

    $result = db_fetch_assoc($sql);

    if (!is_array($result)) {
        return;
    }

    // Init indexes
    $indexes = array();
    foreach ($result as $r) {
        return mURLin_cache_page($r, "redirect_count");
    }
}

function mURLin_getRedirect_Count($id) {
    return mURLin_cache_page($id, "redirect_count");
}

function mURLin_getAvailabilities($hostname) {
    input_validate_input_number($hostname);
    $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " . $hostname;

    $result = db_fetch_assoc($sql);

    if (!is_array($result)) {
        return;
    }

    // Init indexes
    $indexes = array();
    foreach ($result as $r) {
        return mURLin_cache_page($r, "availability");
    }
}

function mURLin_getAvailability($id) {
    return mURLin_cache_page($id, "availability");
}

function mURLin_getNamelookup_Times($hostname) {
    input_validate_input_number($hostname);
    $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " . $hostname;

    $result = db_fetch_assoc($sql);

    if (!is_array($result)) {
        return;
    }

    // Init indexes
    $indexes = array();
    foreach ($result as $r) {
        return mURLin_cache_page($r, "namelookup_time");
    }
}

function mURLin_getNamelookup_Time($id) {
    return mURLin_cache_page($id, "namelookup_time");
}

function mURLin_getConnect_Times($hostname) {
    input_validate_input_number($hostname);
    $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " . $hostname;

    $result = db_fetch_assoc($sql);

    if (!is_array($result)) {
        return;
    }

    // Init indexes
    $indexes = array();
    foreach ($result as $r) {
        return mURLin_cache_page($r, "connect_time");
    }
}

function mURLin_getConnect_Time($id) {
    return mURLin_cache_page($id, "connect_time");
}

function mURLin_getPretransfer_Times($hostname) {
    input_validate_input_number($hostname);
    $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " . $hostname;

    $result = db_fetch_assoc($sql);

    if (!is_array($result)) {
        return;
    }

    // Init indexes
    $indexes = array();
    foreach ($result as $r) {
        return mURLin_cache_page($r, "pretransfer_time");
    }
}

function mURLin_getPretransfer_Time($id) {
    return mURLin_cache_page($id, "pretransfer_time");
}

function mURLin_getStarttransfer_Times($hostname) {
    input_validate_input_number($hostname);
    $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " . $hostname;

    $result = db_fetch_assoc($sql);

    if (!is_array($result)) {
        return;
    }

    // Init indexes
    $indexes = array();
    foreach ($result as $r) {
        return mURLin_cache_page($r, "starttransfer_time");
    }
}

function mURLin_getStarttransfer_Time($id) {
    return mURLin_cache_page($id, "starttransfer_time");
}

function mURLin_getRedirect_Times($hostname) {
    input_validate_input_number($hostname);
    $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " . $hostname;

    $result = db_fetch_assoc($sql);

    if (!is_array($result)) {
        return;
    }

    // Init indexes
    $indexes = array();
    foreach ($result as $r) {
        return mURLin_cache_page($r, "redirect_time");
    }
}

function mURLin_getRedirect_Time($id) {
    return mURLin_cache_page($id, "redirect_time");
}

function get_http_codes($hostname) {
    input_validate_input_number($hostname);
    $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " . $hostname;

    $result = db_fetch_assoc($sql);

    if (!is_array($result)) {
        return;
    }

    // Init indexes
    $indexes = array();
    foreach ($result as $r) {
        return mURLin_cache_page($r, "http_code");
    }
}

function get_http_code($id) {
    return mURLin_cache_page($id, "http_code");
}

function mURLin_getDownloadSizes($hostname) {
    input_validate_input_number($hostname);
    $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " . $hostname;

    $result = db_fetch_assoc($sql);

    if (!is_array($result)) {
        return;
    }

    // Init indexes
    $indexes = array();
    foreach ($result as $r) {
        return mURLin_cache_page($r, "size_download");
    }
}

function mURLin_getDownloadSize($id) {

    return mURLin_cache_page($id, "size_download");
}

function display_page_http($url, $timeout, $proxyaddress, $proxyident) {

    // Result
    $input = array();
    $input['url'] = $url;
    $input['text_match'] = ""; // No text match for this
    $input['timeout'] = $timeout;
    $input['proxyserver'] = ($proxyaddress != "") ? 1 : 0;
    $input['proxyaddress'] = $proxyaddress;

    if ($proxyident != "") {
        $proxyident = explode(":", $proxyident);
        $input['proxyusername'] = $proxyident[0];
        $input['proxypassword'] = $proxyident[1];
    }


    $result = mURLin_cache_page($input, "body");

    return $result;
}

function mURLin_getSite($id) {
    input_validate_input_number($id);
    $sql = "SELECT url FROM plugin_mURLin_index WHERE id = " . $id;

    return db_fetch_cell($sql);
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
        $url = $input['url'];
        $text_match = $input['text_match'];
        $timeout = $input['timeout'];
        $proxyserver = $input['proxyserver'];


        // Deal with a proxy if required
        if ($proxyserver != 0) {
            $proxyaddress = $input['proxyaddress'];

            $proxyusername = isset($input['proxyusername']) ? $input['proxyusername'] : "";
            $proxypassword = isset($input['proxypassword']) ? $input['proxypassword'] : "";

            // Check for proxy authentication
            if ($proxyusername == "" && $proxypassword == "")
                $proxyident = "";
            else
                $proxyident = $proxyusername . ':' . $proxypassword;
        } else
            $proxyaddress = "";
    }
    else {
        input_validate_input_number($input);

        // Check if there is already a cached version of this query
        $sql = "SELECT * FROM plugin_mURLin_cache WHERE id = $input";
        $db_result = db_fetch_row($sql);

        if (count($db_result) != 0) {
            // There is a cached result
            if(read_config_option("log_verbosity") <= POLLER_VERBOSITY_DEBUG)
                cacti_log("mURLin - INFO: Result has been accessed from Cache");

            // No need to recache
            $insert_into_cache = false;

            $accessed_from_cache = true;
            $info = $db_result;
        } else {
            
            if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) 
                cacti_log("mURLin - INFO: Cache Miss");
            
            
        }

        // Get DB info
        $sql = "SELECT * FROM plugin_mURLin_index WHERE id = $input";

        $db_result = db_fetch_row($sql);

        // Variables
        $host_id = $db_result['host_id'];
        $url = $db_result['url'];
        $text_match = $db_result['text_match'];
        $timeout = $db_result['timeout'];
        $proxyserver = $db_result['proxyserver'];


        // Deal with a proxy if required
        if ($proxyserver != 0) {
            $proxyaddress = $db_result['proxyaddress'];

            $proxyusername = $db_result['proxyusername'];
            $proxypassword = $db_result['proxypassword'];

            // Check for proxy authentication
            if ($proxyusername == "" && $proxypassword == "")
                $proxyident = "";
            else
                $proxyident = $proxyusername . ':' . $proxypassword;
        } else
            $proxyaddress = "";
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

        if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG)         
            cacti_log("mURLin - INFO: Getting URL Information");
        
        
        $body = curl_exec($page);
        
        if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) 
            cacti_log("mURLin - INFO: Getting URL Information ... Done");
        
        
        if ($body == "") {
            // Nothing came back in the return
            $curl_error = "ERROR - Nothing returned - CURL ERROR:";
            $curl_error .= curl_error($page);

            $curl_error .= "\n\nUsing Proxy Address: " . $proxyaddress;
        }

        // First check if we have successfully downloaded the webpage
        $info = curl_getinfo($page);

        curl_close($page);
    }

    $total_time = "";
    $http_code = "";
    $size_download = "";
    $redirect_count = "";
    $availability = "";
    $namelookup_time = "";
    $connect_time = "";
    $pretransfer_time = "";
    $starttransfer_time = "";
    $redirect_time = "";

    if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG)     
        cacti_log("mURLin - INFO: Doing Text Match");
    
    
    // Text Match
    if (@preg_match($text_match, $body)) {
        // Page Text matches

        $total_time = $info['total_time'];
        $http_code = $info['http_code'];
        $size_download = $info['size_download'];
        $redirect_count = $info['redirect_count'];

        // Ensure that if we accessing from the DB cache that the DB value 
        // is taken. This is due to the DB lookup having no text_match to perform
        if ($accessed_from_cache) {
            $availability = $info['availability'];
        } else {
            $availability = 100;
        }

        $namelookup_time = $info['namelookup_time'];
        $connect_time = $info['connect_time'];
        $pretransfer_time = $info['pretransfer_time'];
        $starttransfer_time = $info['starttransfer_time'];
        $redirect_time = $info['redirect_time'];
    } else {
        // Page text doesn't match
        $total_time = "0";
        $http_code = $info['http_code'];
        $size_download = $info['size_download'];
        $redirect_count = $info['redirect_count'];

        // Ensure that if we accessing from the DB cache that the DB value 
        // is taken. This is due to the DB lookup having no text_match to perform
        if ($accessed_from_cache) {
            $availability = $info['availability'];
        } else {
            $availability = 0;
        }

        $namelookup_time = "0";
        $connect_time = "0";
        $pretransfer_time = "0";
        $starttransfer_time = "0";
        $redirect_time = "0";
    }

    if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) 
        cacti_log("mURLin - INFO: Doing Text Match... Done");
    
    

    if ($insert_into_cache == true) {
        // Insert into database
        if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) 
            cacti_log("mURLin - INFO: Inserting into Cache");
        
        
        $sql = "INSERT INTO plugin_mURLin_cache 
            (id, total_time, http_code, size_download, redirect_count, availability, namelookup_time, connect_time, pretransfer_time, starttransfer_time, redirect_time) 
            VALUES ($input, $total_time, $http_code, $size_download, $redirect_count, $availability, $namelookup_time, $connect_time, $pretransfer_time, $starttransfer_time, $redirect_time);";

        db_execute($sql);
        
        if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG)     
            cacti_log("mURLin - INFO: Inserting into Cache... Done");
        
    }


    switch ($output) {
        case "NONE":
            // No value being returned
            return;
            break;

        case "body":
            // Return page body or error 
            if ($body == "")
                return $curl_error;
            else
                return $body;
            break;

        case "http_code":
            // Return the http_code variable
            return $http_code;
            break;

        case "total_time":
            return $total_time;
            break;

        case "size_download":
            return $size_download;
            break;

        case "redirect_count":
            return $redirect_count;
            break;

        case "availability":
            return $availability;
            break;

        case "namelookup_time":
            return $namelookup_time;
            break;

        case "connect_time":
            return $connect_time;
            break;

        case "pretransfer_time":
            return $pretransfer_time;
            break;

        case "starttransfer_time":
            return $starttransfer_time;
            break;

        case "redirect_time":
            return $redirect_time;
            break;
    }
}

?>
