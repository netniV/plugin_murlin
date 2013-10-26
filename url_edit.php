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

$_SESSION['custom']=false;

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch ($_REQUEST["action"]) 
{
	case '':
            // Show Cacti top
            include_once("./include/top_header.php");
             EditHost();
            // Show cacti bottom
            include_once("./include/bottom_footer.php");
            break;
        
        case 'save':    // save the data from the form
            SaveHost();
            break;         
}

function SaveHost()
{
    // Validate the input
    $indexid = $_POST['id'];
    $selected_host = $_POST['selected_host'];
    $url = $_POST['url'];
    $text_match = $_POST['text_match'];
    $timeout = $_POST['timeout'];
    $proxyserver = isset($_POST['proxyserver']) ? 1 : 0;
    $proxyaddress = $_POST['proxyaddress'];
    $proxyusername = $_POST['proxyusername'];
    $proxypassword = $_POST['proxypassword'];
        
    // $indexid can be NEW or a number
    if ($indexid != "NEW")
    {
        input_validate_input_number($indexid);
    }
    
    $proxyaddress = sql_sanitize($proxyaddress);
    $proxyusername = sql_sanitize($proxyusername);
    $proxypassword = sql_sanitize($proxypassword);
    
    $url = sql_sanitize($url);
    $text_match = sql_sanitize($text_match);
       
    // Check if its a number
    input_validate_input_number($selected_host);
    input_validate_input_number($timeout);
    
    if ($indexid == "NEW")
    {
        
        // NEW Entry
        $sql = "INSERT INTO plugin_mURLin_index (host_id, url, text_match, timeout, proxyserver, proxyaddress, proxyusername, proxypassword) VALUES ($selected_host, '$url', '$text_match', $timeout, $proxyserver, '$proxyaddress', '$proxyusername', '$proxypassword')";
        
        // Insert into the database
        db_execute($sql);
        $id = db_fetch_insert_id();
        header( "Location: mURLin.php?id=$indexid" );
    }
    else
    {
        
        // Existing entry
        $sql = "UPDATE plugin_mURLin_index SET host_id = $selected_host, url = '$url', text_match = '$text_match', timeout = $timeout, proxyserver = $proxyserver, proxyaddress = '$proxyaddress', proxyusername = '$proxyusername', proxypassword = '$proxypassword'  WHERE id = $indexid";
        
        // Insert into the database
        db_execute($sql);
               
        header( "Location: mURLin.php?id=$id" );
    }
    
}
//
//function mURLin_includejavascript($filepath)
//{
//    print '<script type="text/javascript">';
//    include("$filepath");
//    print '</script>';
//}

function EditHost()
{
    mURLin_includejavascript("./plugins/mURLin/jquery-1.10.2.min.js");
    mURLin_includejavascript("./plugins/mURLin/functions.js");
    
    // Get the index to load (if any)
    $id = $_GET['id'];
    
    // id must be a number only OR NEW
    if ($id != "NEW")
        input_validate_input_number($id);

    // format a query to return the results
    $sql = "
        SELECT m.id as id, m.host_id as host_id, m.url as url, m.timeout as timeout, m.text_match as text_match, h.description as hostname, h.hostname as dns, m.proxyserver as proxyserver, m.proxyaddress as proxyaddress, m.proxyusername as proxyusername, m.proxypassword as proxypassword
        FROM plugin_mURLin_index m
        INNER JOIN host h
        ON m.host_id = h.id
        WHERE m.id = $id";
    
    if ($id != "NEW")
        $result = db_fetch_row($sql);
    
    // $Result is our row display on the form
    if ($id != "NEW")
        $id = $result['id'];
   
    if ($id == "NEW")
    {
        $url = "";
        $timeout = "";
        $text_match = "";
        $host_id = "";
        $hostname = "";
        $DNS = "";
        $proxyserver = "";
        $proxyaddress = "";
        $proxyusername = "";
        $proxypassword = "";
    }
    else
    {
        $url = $result['url'];
        $timeout = $result['timeout'];
        $text_match = $result['text_match'];
        $host_id = $result['host_id'];
        $hostname = $result['hostname'];
        $DNS = $result['dns'];
        $proxyserver = $result['proxyserver'] > 0 ? "checked" : "";
        $proxyaddress = $result['proxyaddress'];
        $proxyusername = $result['proxyusername'];
        $proxypassword = $result['proxypassword'];
    }
    
    
    // Create the form
    echo "<form method=post onSubmit='return mURLin_ValidateURLForm()'>";
    
    mURLin_CreateHeader("Edit URL Mapping", $hostname . " (" . $url . ")");
    mURLin_CreateFormSubHeader("Host Details");
    
    // Generate form data

    $host_details[] = array('name' => 'Hostname',
                              'description' => 'This is the host which the URL will be mapped to.',
                              'form' => "<br/><strong>Hostname</strong> - <div id='hostname_text'>$hostname</div> <br/>
                                        <strong>IP/DNS Name</strong> - <div id='dns_text'>$DNS</div> <br/><br/>
                                        <input type='button' title='Select new host' onClick='mURLin_SelectHostClick(true);var selhost = getElementById(\"selected_host\").value;mURLin_RefreshHosts(selhost,\"\");' value='Select Host...' name='select_host' id='select_host' />
                                        <input type='hidden' title='Select new host' value='$host_id' name='selected_host' id='selected_host' />
                                        <input type='hidden' value='$id' name='id' id='id' />");

    mURLin_CreateFormDetails($host_details);
    
    mURLin_CreateFormSubHeader("URL Details");  
    
    $url_details[] = array('name' => 'URL (Website Address)',
                              'description' => 'The address of the webpage to monitor. The address must be preceeded by the protocol, http or https and if required must include the port number.',
                              'form' => "<br/><input type='text' name='url' id='url' value='$url' size='50' /> <br/><br/>
                                        <input type='button' title='Open URL as text' onclick=\"mURLin_open_url(getElementById('url').value, getElementById('timeout').value, getElementById('proxyserver').checked, getElementById('proxyaddress').value, getElementById('proxyusername').value, getElementById('proxypassword').value)\" value='Open URL...' name='open_url' id='open_url' />");
    
    mURLin_CreateFormDetails($url_details);
    
    mURLin_CreateFormSubHeader("Text Matching");
    
    $escaped = htmlspecialchars($text_match);
    $escapedurl = htmlspecialchars($url);
    $text_details[] = array('name' => 'Text to Match',
                              'description' => 'The text within a webpage to check for each time the scan is run. The match is done using the php function preg_match. <br/><br/>Example:<br/>/Welcome to the site/<br/><br/><strong>Note that the expression should be enclosed in / as above.</strong><br/><br/>See <a href="http://php.net/manual/en/function.preg-match.php" target="_blank">this regex tutorial</a> for more examples.',
                              'form' => "<br/><textarea name='text_match' id='text_match' rows='10' cols='50'>$text_match</textarea> <br/><br/>
                                        <input type='button' title='Text the regex function' onClick=\"mURLin_test_match(getElementById('text_match').value, getElementById('url').value, getElementById('timeout').value, getElementById('proxyserver').checked, getElementById('proxyaddress').value, getElementById('proxyusername').value, getElementById('proxypassword').value);\" value='Test' name='test_regex' id='test_regex' />");
                                      
    mURLin_CreateFormDetails($text_details);
    
    mURLin_CreateFormSubHeader("Timeout");
    
    $timeout_details[] = array('name' => 'Timeout',
                              'description' => 'Timeout value in seconds. <br/>This is the number of seconds to wait for the website to respond before declaring the site is unavailable. (Between 1 and 99 seconds)',
                              'form' => "<br/><input type='text' title='Timeout in seconds' name='timeout' id='timeout' value='$timeout'/>");
    
    mURLin_CreateFormDetails($timeout_details);
    
    mURLin_CreateFormSubHeader("Proxy");
    
    $proxy_details[] = array('name' => 'Use Proxy Server',
                              'description' => 'Select if you require a proxy server to access this URL.',
                              'form' => "<br/><input type='checkbox' title='Use Proxy Server' name='proxyserver' id='proxyserver' $proxyserver/>");
    $proxy_details[] = array('name' => 'Proxy Server Address',
                              'description' => '<br/><br/>Only applies if selected above. Proxy server address. <br/></br>Example:<br/>http://proxy.localnet:3128<br/><br/>',
                              'form' => "<br/><input type='text' title='Proxy Server Address' name='proxyaddress' id='proxyaddress' size='50' value='$proxyaddress'/>");
    $proxy_details[] = array('name' => 'Proxy Server Username',
                              'description' => '<br/>Only applies if selected above. Proxy server username (If the proxy requires authentication). <br/>Leave the server details blank if the proxy server requires no authentication.',
                              'form' => "<br/><input type='text' autocomplete='off' title='Proxy Server Username' name='proxyusername' id='proxyusername' size='50' value='$proxyusername'/>");
    $proxy_details[] = array('name' => 'Proxy Server Password',
                              'description' => '<br/>Only applies if selected above. Proxy server password (If the proxy requires authentication). <br/>Leave the server details blank if the proxy server requires no authentication.',
                              'form' => "<br/><input type='password' autocomplete='off' title='Proxy Server Username' name='proxypassword' id='proxypassword' size='50' value='$proxypassword'/>");
    mURLin_CreateFormDetails($proxy_details);
    
    
    
    // Create the form buttons
    echo "<table  style='float:right;'><tbody>";
    echo "<tr><td>";
    echo "<input type='hidden' name='action' value='save'/>";
    echo "<input type='button' value='Return' title='Return to URL Mappings' onClick='window.location.href = \"mURLin.php\";'/>";
    echo "<input type='submit' value='Save' title='Save Report'/>";
    echo "</tr></td>";
    echo "</tbody></table>";
    echo "</form>";
    
    mURLin_CreateHiddenHostTable($host_id);
}


function mURLin_CreateHeader($header_title, $host_name)
{
// Create the table header
echo <<< HTMLBLOCK
   <table style="width:100%;" class="cactiTable" bgcolor="#00438C" cellpadding="3" cellspacing="0" border="0"><tbody>
    <tr>
        <td class="textHeaderDark" style="padding:3px; text-align:left; width:50%;">
            <strong>$header_title - </strong> [Edit - $host_name]
        </td>
    </tr>
    </tbody></table>

HTMLBLOCK;
}

function mURLin_CreateFormSubHeader($text)
{
    // Create the editing form
echo <<< HTMLBLOCK

    
    <table width='100%' cellpadding="3" cellspacing="0" border="0">
        <tbody>
            <tr bgcolor='#6d88ad'>
                <td colspan='2' class='tableSubHeaderColumn' style='padding:3px;'>
                    $text
                </td>
            </tr>
        </tbody>
    </table>
    
    
    
HTMLBLOCK;
}

function mURLin_CreateFormDetails($data)
{
    // $data is an array of arrays
    echo "<table width='100%' cellspacing='0px'><tbody>"; //start table
    
    $bgswitch = true;
    $bgcolor = "#E5E5E5";
    
    foreach ($data as $row)
    {
        if ($bgswitch)
            $bgcolor = "#E5E5E5";
        else
            $bgcolor = "#E9E9E9";
        
        echo "<tr bgColor='#E5E5E5' onMouseOver=\"this.bgColor='#F7EEC0';\" onMouseOut=\"this.bgColor='$bgcolor';\" >"; // start row
        
        echo "<td style='padding:5px;' width='60%'>"; // start cell
        echo "<strong>" . $row['name'] . "</strong><br />";
        echo $row['description'];
        echo "</td>"; // end cell
        
        echo "<td style='padding:5px;' width='40%'>"; // start cell
        echo $row['form'];
        echo "</td>"; // end cell
        
        echo "</tr>"; //end row
        
        $bgswitch = !$bgswitch;
        
    }
    
    echo "</tbody></table>"; // end table
}

function mURLin_CreateHiddenHostTable($host_id)
{
    if($host_id == "")
            $host_id = "-1";
    
    // Create the DIV to show the select host window
    print "<div id='shade' style='display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-image:url(\"/cacti/plugins/mURLin/images/transparent.png\");'>";

    print "<div id='popup' style='display:none; background-color:white; padding:5px 5px 5px 5px; position:absolute; top:50%; left:50%; width:700px; height:700px; margin:-350px 0 0 -350px;'>";
    
    // Create the input buttons
    print "<div style='height:20px; width:100%; text-align:center;'><table bgcolor='blue' width='100%' class=\"textHeaderDark\"><tbody><tr><td><strong>Select Host</strong></td></tr></tbody></table></div>";
    print "<div id='buttons' style='background-color:white; width=100%;'>
            <strong>Filter Hosts - </strong>
            <input type='text' value='' name='filtertext' id='filtertext' onkeyup='$(\"#filtertext\").keyup(function(event){if(event.keyCode == 13){\$(\"#filter\").click();}});' />
            <input type='button' title='Filter' onclick=\"mURLin_RefreshHosts($host_id, getElementById('filtertext').value)\" value='Filter Hosts...' name='filter' id='filter' />
            </div>";
    print "<div id='hosttable' style='background-color:white; overflow-y: scroll; height:89%'></div>";
    
    print "<input type='button' title='Select' onclick=\"mURLin_SelectNewHost($('input[name=chkHost]:checked').val())\" value='Select Host...' name='select' id='select' style='float:right;'/>
        <input type='button' title='Return' onclick=\"mURLin_SelectHostClick(false)\" value='Return' name='return' id='return' style='float:right;'/>";
        
    print "</div>";
    
    print "</div>";
}

?>
