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

chdir('../../');
include_once("./include/auth.php");
include_once("./include/config.php");
include_once("./lib/data_query.php");
include_once("./plugins/mURLin/scripts/functions.php");

$_SESSION['custom']=false;

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch ($_REQUEST["action"]) 
{
	case '':
            // Show Cacti top
            include_once("./include/top_header.php");
            mURLin_ShowURLs();
            // Show cacti bottom
            include_once("./include/bottom_footer.php");
            break;
              
        case 'delete':
            // Delete the Host Mapping
            $id = $_SESSION['urlselect'];
            
            foreach($id as $i)
            {
                input_validate_input_number($i);
            }
            
            foreach($id as $i)
            {
                // What host is mapped to $i
                $sql = "SELECT host_id FROM plugin_mURLin_index WHERE id = $i";
                $hostid = db_fetch_cell($sql);
                
                
                $sql = "DELETE FROM plugin_mURLin_index WHERE id = $i";
                db_execute($sql);
                
                // ReIndex the Data Query
                $sql = "SELECT id FROM snmp_query WHERE name='mURLin - URL Agent'";
                $snmpid = db_fetch_cell($sql);
                data_query_update_host_cache_from_buffer($hostid, $snmpid, $tmp);
            }
                        
            // Redirect
            header( "Location: mURLin.php" );
            
            break;  
        
        case 'confirmdelete':
            // Confirm the delete
            $id = $_POST['urlselect'];
            
            $hostmappings = "";
            
            foreach($id as $i)
            {
                input_validate_input_number($i);
                
                $sql = "
                SELECT m.id as id, m.host_id as host_id, m.url as url, m.timeout as timeout, m.text_match as text_match, h.description as hostname, h.hostname as dns
                FROM plugin_mURLin_index m
                INNER JOIN host h
                ON m.host_id = h.id
                WHERE m.id = $i";
            
                $row = db_fetch_row($sql);

                $host = $row['hostname'];
                $url = $row['url'];
                
                $hostmappings .= "$host ---> <strong>$url</strong><br/><br/>";
            }
                       
            // Set details
            $_SESSION['urlselect'] = $id;
           
            mURLin_PostMessage("Confirm Delete" ,"Are you sure you want to delete the following host mapping(s)?<br/><br/><br/><div style='text-align:center;'>$hostmappings</div>", "mURLin.php?action=delete", "mURLin.php");
            break;
            
            case 'duplicate':
                
                // Duplicate a report
                $id = $_POST['urlselect'];

                foreach ($id as $i)
                {
                    input_validate_input_number($i);
                    
                    $sql = "SELECT * FROM plugin_mURLin_index WHERE id = $i";

                    $row = db_fetch_row($sql);
                    
                    $host_id = $row['host_id'];
                    $url = $row['url'];
                    $text_match = $row['text_match'];
                    $timeout = $row['timeout'];
                    
                    // Duplicate this row
                    $sql = "INSERT INTO plugin_mURLin_index (host_id, url, text_match, timeout) VALUES ($host_id, '$url', '$text_match', $timeout)";
                    db_execute($sql);
                }
                // Redirect
                header( "Location: mURLin.php" );
                break;
            
}

function mURLin_PostMessage($messagetitle, $message, $confirmaction, $cancelaction)
{
    // Include cacti top
    include_once("./include/top_header.php");
    
    print "<table align='center' width='60%' cellpadding='0' cellspacing='0' border='0' class='cactiTable' bgcolor='#6d88ad'>
		<tbody><tr>
			<td>";
    
    print '<table cellpadding="3" cellspacing="0" border="0" bgcolor="#6d88ad" width="100%"><tbody><tr><td class="textHeaderDark">';
    print "<strong>" . $messagetitle . "</strong>";
    print '</td></tr></tbody></table>';
    print '<table cellpadding="3" cellspacing="0" border="0" bgcolor="#f5f5f5" width="100%"><tbody><tr><td class="textArea">';
    print "<br/><br/>" . $message . "<br/><br/>";
    print '</td></tr></tbody></table>';
    print '<table cellpadding="3" cellspacing="0" border="0" bgcolor="#e1e1e1" width="100%"><tbody><tr><td>';
    print "<form method='post' style='float:right; margin:0 0 0 0;'>";
    print " <input type='button' title='Cancel' value='Cancel' onClick=\"window.location='$cancelaction'\"/>
            <input type='button' title='Confirm' value='Confirm' onClick=\"window.location='$confirmaction'\"/>";
    print '</td></tr></tbody></table>';
    print "             </td>
                       </tr>
                 </tbody>
           </table>";
    
    // Show cacti bottom
    include_once("./include/bottom_footer.php");
}

function mURLin_ShowURLs()
{
    
    mURLin_includejavascript("./plugins/mURLin/functions.js");
    
    // Show the mapped URLs
    $sql = "SELECT * FROM plugin_mURLin_index";
    
    $results = db_fetch_assoc($sql);
    
    // Draw table header
    // Show Availability Reports
echo <<<HTMLBLOCK



<form method='POST'>  
  <div style="position:static;" id="main">
        <table align="center" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tbody>
                    <tr>
                        <td>
                            <table style="width:100%;" class="cactiTable" bgcolor="#00438C"><tbody>
                                <tr><td class="textHeaderDark" style="padding:3px; text-align:left; width:50%;">
                                        <strong>Map URLs to Hosts</strong>
                                    </td>
                                        <td class="textHeaderDark" style="padding:3px; text-align:right; width:50%;">
                                        <strong><a class='linkOverDark' href='url_edit.php?id=NEW'>Add</a></strong>
                                    </td>
                                </tr>
                            </tbody></table>
                        </td>
                    <tr bgcolor="#e5e5e5">
                        <td>
HTMLBLOCK;

    print mURLin_CreateURLTable($results);

echo <<<HTMLBLOCK
                        </td>
                    </tr>
                    <tr>
                        <td>
                                <table  style='float:right;'>
                                    <tbody>
                                        <tr>
                                            <td>
                                                Choose an Action:
                                            </td>
                                            <td>
                                                <select name="action">
                                                    <option value="confirmdelete">Delete</option>
                                                    <option value="duplicate">Duplicate</option>
                                                </select>
                                            </td>
                                            <td>
                                            <input type='submit' value='Go' title='Execute Action'/>
                                        </tr>
                                    </tbody>
                                </table>
                        </td>
                    </tr>
              </tbody>
        </table>
  </div>

</form>

HTMLBLOCK;


    
}

function mURLin_CreateURLTable($results)
{
    // Cycle through each result and draw the table
    print "
        
        <table width='100%'>
            <tbody>";
    
    // Print Title Row
    print "<tr width='100%' bgcolor='#d5d5d5'>";
    print "<td>";
    print "<strong>Hostname</strong>";
    print "</td>";
      
    print "<td>";
    print "<strong>DNS/IP</strong>";
    print "</td>";
    
    print "<td>";
    print "<strong>URL</strong>";
    print "</td>";
    
    print "<td>";
    print "<strong>Timeout (Seconds)</strong>";
    print "</td>";
    
    print "<td>";
    print "<strong>Regex Text Match</strong>";
    print "</td>";
    
    print "<td>";
    print "<strong>Proxy Address</strong>";
    print "</td>";
    
    print "<td width='1%' style='padding:1px;'>";
    print "<input type='checkbox' id='select_all' name='all' title='Select All' onclick='CheckAll(\"urlselect[]\", this.checked)'>";
    print "</td>";
    
    // Colour switch
    // One colour for odd one for even
    $bgswitch = 0;
    
    foreach ($results as $row)
    {
        // Colour Switch
        if (($bgswitch % 2) === 1)
              $bgcolor = "#f5f5f5";
          else
              $bgcolor = "#E7E9F2";
          
          
        // Host id to host name
        $sql = "SELECT description, hostname from host WHERE id = " . $row['host_id'];
        
        $temp = db_fetch_row($sql);
        $hostname = $temp['description'];
        $DNS = $temp['hostname'];
        
        $id = $row['id'];
        $url = $row['url'];
        $timeout = $row['timeout'];
        $text_match = $row['text_match'];
        $proxyserver = $row['proxyserver'];
        
        $proxystring = "";
        
        if ($proxyserver != 0)
        {
            $proxyaddress = $row['proxyaddress'];
            $proxystring = "&proxy=" . $row['proxyaddress'] . "&proxyusername=" . $row['proxyusername'] . "&proxypassword=" . $row['proxypassword'];
        }
        else
            $proxyaddress = "N/A";
        
        print "<tr width='100%' bgcolor='$bgcolor' id='row_$id' onMouseOver=\"this.bgColor='#F7EEC0';\" onMouseOut=\"this.bgColor='$bgcolor';\">
               <td onClick=\"CheckBox('chk_$id','select_all');\">";
        print "<a href='url_edit.php?id=$id'><strong>$hostname</strong></a> "; // Print Hostname and Link
        print "</td>";
        
        print "<td onClick=\"CheckBox('chk_$id','select_all');\">";
        print $DNS;
        print "</td>";
              
        print "<td onClick=\"CheckBox('chk_$id','select_all');\">";
        
        // Encode the URL so it doesn't inject extra information into the showpage.php page
        $urlenc = urlencode($url);
        
        print "<strong><a href='showpage.php?page=$url' onclick=\"window.open('showpage.php?page=$urlenc&timeout=$timeout$proxystring', 'myWin', 'toolbar=no, directories=no, location=no, status=yes, menubar=no, resizable=no, scrollbars=yes, width=600, height=400'); return false\">" . $url . "</a></strong>";
        print "</td>";
        
        print "<td onClick=\"CheckBox('chk_$id','select_all');\">";
        print $timeout;
        print "</td>";
        
        print "<td onClick=\"CheckBox('chk_$id','select_all');\" width='40%'>";
        print "<PRE style='margin:3px;'>" . $text_match . "</PRE>";
        print "</td>";
        
        print "<td onClick=\"CheckBox('chk_$id','select_all');\" >";
        print $proxyaddress;
        print "</td>";
        
        print "<td>";
        print "<input type='checkbox' id='chk_$id' name='urlselect[]' value='$id' onClick=\"CheckAllFunction(this.checked, 'select_all');\">";
        print "</td>";
        
        print "</tr>";
        
        $bgswitch += 1;
    }
    
    print "     </tbody>
        </table>";
                  
}

?>
