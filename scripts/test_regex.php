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

include_once 'functions.php';

$regex = $_GET['regex'];
$url = $_GET['url'];
$timeout = $_GET['timeout'];

if (isset($_GET["proxy"]))
{
    $proxy = $_GET["proxy"];
    
    // Check for proxy authentication
    if ($_GET['proxyusername'] == "" && $_GET['proxypassword'] == "")
        $proxyident = "";
    else
        $proxyident = $_GET['proxyusername'].':'.$_GET['proxypassword'];
}
else
{
    $proxy = "";
    $proxyident = "";
}

// If no regex is supplied default allow all is assumed
if ($regex == "")
    $regex="//";

$pagetext = display_page_http(urldecode($url), $timeout, $proxy, $proxyident);

// See if the text is in the webpage
if (preg_match(urldecode($regex), $pagetext) != false)
{
    // Page Text matches
    print "Page text match has been found.";
}
else
{
    // Page text doesn't match
    print "Text match has FAILED.\n\nEnsure that you have used suitable delimiters such as /.";
}

?>
