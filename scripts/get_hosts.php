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

//include Cacti stuff
//chdir('../../');
include_once(dirname(__FILE__) . "/../../../include/global.php");

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch ($_REQUEST["action"]) 
{
	case '':
            mURLin_GetHostTable();
            break;
        
        case 'info':
            $hid = $_GET['id'];
            mURLin_GetHostInfo($hid);
            break;
}


function mURLin_GetHostInfo($id)
{
    // Gets a part of the host table
    // Ensure we only have numbers
    $id = preg_replace('/[^0-9]/', '', $id);
    
    $sql = "SELECT * FROM host WHERE id = " . $id;
    
    $result = db_fetch_row($sql);
    
    // Create a comma separated list of host_id,hostname,description
    print $result['id'] . "," . $result['hostname'] . "," . $result['description'];
}

function mURLin_GetHostTable()
{
    // ID is currently selected
    if (isset($_GET['id'])) {$id = $_GET['id']; }
    $filter = $_GET['filter'];

    // Validate host ID
    $id = preg_replace('/[^0-9]/', '', $id);

    // Filter MUST NOT contain non alpha numeric characters
    $filter = preg_replace('/[^a-z0-9]/i', '', $filter);

    // We now have our clean filters
    $sql = "SELECT * FROM host WHERE hostname LIKE '%" . $filter . "%' OR description LIKE '%" . $filter . "%'";

    $results = db_fetch_assoc($sql);

    // Create a table and format the rows

    // TABLE HEADER
    print "<table width='100%' class='cactiTable' bgcolor='#00438C' cellpadding='3' cellspacing='0' border='0'><tbody>";
        print "<tr>
            <td class=\"textHeaderDark\" style=\"padding:3px; text-align:left;\">
                <strong>Hostname</strong>
            </td>
            <td class=\"textHeaderDark\" style=\"padding:3px; text-align:left;\">
                <strong>IP/DNS Name</strong>
            </td>
            <td class=\"textHeaderDark\" style=\"padding:3px; text-align:left;\">
                <strong>Selected</strong>
            </td>";   
    // END TABLE HEADER


    //TABLE BODY
    $bgswitch = true;

    foreach($results as $host)
    {
        $host_id = $host['id'];
        $host_DNS = $host['hostname'];        
        $host_hostname = $host['description'];

        if ($id == $host_id)
            $checked = "checked";
        else
            $checked = "";

        if ($bgswitch)
            $bgcolor = "#E5E5E5";
        else
            $bgcolor = "#F5F5F5";

        // Create a table row with checkbox
        print  "<tr onMouseOver=\"this.bgColor='#F7EEC0';\" onMouseOut=\"this.bgColor='$bgcolor';\" bgcolor='$bgcolor' onclick='mURLin_selectHost($host_id);' >
                    <td>
                    $host_hostname
                    </td>
                    <td>
                    $host_DNS
                    </td>
                    <td>
                    <input type='radio' id='$host_id' name='chkHost' value='$host_id' $checked />
                    </td>
                </tr>";

        $bgswitch = !$bgswitch;
    }

    // END TABLE BODY

    // TABLE FOOTER
    print "</tbody></table>";
    //END TABLE FOOTER
}

?>
