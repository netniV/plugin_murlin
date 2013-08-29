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
/*******************************************************************************

    Author ......... James Payne
    Contact ........ jamoflaw@gmail.com
    Home Site ...... http://withjames.co.uk
    Program ........ Cacti URL Monitoring Plugin
    Purpose ........ Creates URL Monitoring Structure
           
*******************************************************************************/

function mURLin_includejavascript($filepath)
{
    print '<script type="text/javascript">';
    include("$filepath");
    print '</script>';
}

function reindex($hostid) 
{
        $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " .$hostid;
        
        $result = db_fetch_assoc($sql);
        
        if (!is_array($result))
        {
            return;
        }
        
        // Init indexes
        $indexes = array();
        foreach($result as $r)
        {
            $indexes[] = $r['id'];
        }
        
        return $indexes;
}


function get_sites($hostid)
{
        $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " .$hostid;
        
        $result = db_fetch_assoc($sql);
        
        if (!is_array($result))
        {
            return;
        }
        
        // Init indexes
        $indexes = array();
        foreach($result as $r)
        {
            $indexes[] = $r['id']. "!!" . $r['url'];
        }
        
        return $indexes;
}

function get_text($hostid)
{
    $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " .$hostid;

    $result = db_fetch_assoc($sql);

    if (!is_array($result))
    {
        return;
    }

    // Init indexes
    $indexes = array();
    foreach($result as $r)
    {
        $indexes[] = $r['id']. "!!" . $r['text_match'];
    }

    return $indexes;
}

function get_values($hostid)
{
    $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " .$hostid;

    $result = db_fetch_assoc($sql);
    
    if (!is_array($result))
    {
        return;
    }
    
    // Init indexes
    $indexes = array();
    foreach($result as $r)
    {
        //$indexes[] = $r['id']. "!!" . load_page($r['url'], $r['text_match']);
        
        if ($r['proxyserver'] > 0)
            $proxy = $r['proxyaddress'];
        else
            $proxy = "";
        
        $indexes[] = $r['id']. "!!" . mURLin_getPage($r['url'], $r['timeout'], $r['text_match'], $proxy, "TOTALTIME");
    }
}

function get_value($index)
{
    $sql = "SELECT * FROM plugin_mURLin_index WHERE id = " .$index;
    
    $result = db_fetch_row($sql);
    
    if ($result['proxyserver'] > 0)
        $proxy = $r['proxyaddress'];
    else
        $proxy = "";
    
    return mURLin_getPage($result['url'], $result['timeout'], $result['text_match'], $proxy, "TOTALTIME");
}

//// Our function to get the results
//function load_page($url, $text_match)
//{
//    // Result
//    $result = 0;
//    
//    // If no regex is supplied default allow all is assumed
//    if ($text_match == "")
//        $text_match="//";
//    
//    $page = curl_init($url);
//    
//    curl_setopt($page, CURLOPT_RETURNTRANSFER, 1);
//    curl_setopt($page, CURLOPT_SSL_VERIFYPEER, false);
//    
//    // Set a sensible timeout
//    curl_setopt($page, CURLOPT_TIMEOUT,2);
//    curl_setopt($page, CURLOPT_CONNECTTIMEOUT ,2);
//
//    
//    $body = curl_exec($page);
//    
//    // First check if we have successfully downloaded the webpage
//    $info = curl_getinfo($page);
//   
//    if ($body == "")
//    {
//        // Nothing came back in the return
//        $result = -0.001;
//    }
//    
//    // See if the text is in the webpage
//    if (@preg_match($text_match, $body) !== false)
//    {
//        // Page Text matches
//        $result = $info['total_time'];
//    }
//    else
//    {
//        // Page text doesn't match
//        $result = -0.002;
//    }
//
//    curl_close($page);
//    
//    return $result;
//}

function get_http_codes($hostname)
{
    $sql = "SELECT * FROM plugin_mURLin_index WHERE host_id = " .$hostname;

    $result = db_fetch_assoc($sql);
    
    if (!is_array($result))
    {
        return;
    }
    
    // Init indexes
    $indexes = array();
    foreach($result as $r)
    {
        if ($r['proxyserver'] != 0)
            $proxy = $r['proxyaddress'];
        else
            $proxy = "";
    
        //$indexes[] = $r['id']. "!!" . load_page_http($r['url']);
        $indexes[] = $r['id']. "!!" . mURLin_getPage($r['url'], $r['timeout'], $r['text_match'], $proxy, "HTTPCODE");
    }
}

function get_http_code($id)
{
    $sql = "SELECT * FROM plugin_mURLin_index WHERE id = " .$id;
    
    $result = db_fetch_row($sql);
    
    if ($result['proxyserver'] > 0)
        $proxy = $r['proxyaddress'];
    else
        $proxy = "";
    
    //return load_page_http($result['url']);
    return mURLin_getPage($result['url'], $result['timeout'], $result['text_match'], $proxy, "HTTPCODE");
}

//function load_page_http($url)
//{
//    // Result
//    $result = 0;
//    
//    
//    $page = curl_init($url);
//    
//    curl_setopt($page, CURLOPT_RETURNTRANSFER, 1);
//    curl_setopt($page, CURLOPT_SSL_VERIFYPEER, false);
//    
//    // Set a sensible timeout
//    curl_setopt($page, CURLOPT_TIMEOUT,2);
//    curl_setopt($page, CURLOPT_CONNECTTIMEOUT ,2);
//
//    
//    $body = curl_exec($page);
//    
//    // First check if we have successfully downloaded the webpage
//    $info = curl_getinfo($page);
//   
//    if ($body == "")
//    {
//        // Nothing came back in the return
//        $result = 0;
//    }
//    else
//    {
//        $result = $info['http_code'];
//    }
// 
//    curl_close($page);
//    
//    return $result;
//}
//


function display_page_http($url, $timeout, $proxyaddress)
{
    // Result
    return mURLin_getPage($url, $timeout, "", $proxyaddress, "BODY");
}

function mURLin_getPage($url, $timeout, $text_match, $proxyaddress, $output)
{
    // Result
    $result = 0;
    
    // If no regex is supplied default allow all is assumed
    if ($text_match == "")
        $text_match="//";
    
    $page = curl_init($url);
    
    curl_setopt($page, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($page, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($page, CURLOPT_SSL_VERIFYHOST, false);
    
    // Set a sensible timeout
    curl_setopt($page, CURLOPT_TIMEOUT,$timeout);
    curl_setopt($page, CURLOPT_CONNECTTIMEOUT ,$timeout);
    
    // Set a proxy if required
    if ($proxyaddress != "")
        curl_setopt($page, CURLOPT_PROXY, $proxyaddress);

    
    $body = curl_exec($page);
    
    // First check if we have successfully downloaded the webpage
    $info = curl_getinfo($page);
   
    if ($body == "")
    {
        // Nothing came back in the return
        $body = "ERROR - Nothing returned - CURL ERROR:";
        $body .= curl_error($page);        
    }
 
    curl_close($page);
    
//    // Debug
//    print "BODY:" . $body;
//    print "HTTPCODE:" . $info['http_code'];
//    print "TIME:" . $info['total_time'];
    
    
    switch ($output)
    {
        case "BODY":
            return $body;
            break;
        
        case "HTTPCODE":
            if ($body == "")
            {
                // Nothing came back in the return
                $result = 0;
            }
            else
            {
                $result = $info['http_code'];
            }
            
            return $result;
            break;
        
        case "TOTALTIME":
            if ($body == "")
            {
                // Nothing came back in the return
                $result = -0.001;
            }

            // See if the text is in the webpage
            if (@preg_match($text_match, $body) !== false)
            {
                // Page Text matches
                $result = $info['total_time'];
            }
            else
            {
                // Page text doesn't match
                $result = -0.002;
            }
            
            return $result;
            break;
    }
    
}

?>
