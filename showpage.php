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

include_once("./scripts/functions.php");

chdir('../../');
include_once("./include/config.php");



$_SESSION['custom']=false;

/* set default action */
if (!isset($_REQUEST["page"]))
{
    print "No page selected";
}

$proxyident = "";

if (isset($_REQUEST["proxy"]))
{
    $proxy = $_REQUEST["proxy"];
    
    // Check for proxy authentication
    if ($_REQUEST['proxyusername'] == "" && $_REQUEST['proxypassword'] == "")
        $proxyident = "";
    else
        $proxyident = $_REQUEST['proxyusername'].':'.$_REQUEST['proxypassword'];
}
else
    $proxy = "";


print "<PRE>";
print htmlspecialchars(display_page_http(urldecode($_REQUEST["page"]), $_REQUEST["timeout"], $proxy, $proxyident));
print "</PRE>";


?>
