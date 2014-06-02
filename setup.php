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
//chdir('../../');
include_once(dirname(__FILE__) . "/../../lib/import.php");
include_once(dirname(__FILE__) . "/scripts/functions.php");

// Perform upgrade if needed
plugin_mURLin_upgrade();

function plugin_mURLin_install()
{
    plugin_mURLin_check_hooks();
	
    mURLin_setup_tables();
    import_xml_data(mURLin_returnXML(), true);
}

function plugin_mURLin_uninstall()
{
    // nothing to do
}

function plugin_mURLin_check_config()
{
    plugin_mURLin_check_upgrade();
    return true;
}

function plugin_mURLin_upgrade() 
{
    // Check if we need to upgrade
    plugin_mURLin_check_upgrade();
    return true;
}

function plugin_mURLin_check_upgrade()
{
    $installed_version = GetInstalledVersion(); // As reported by DB
    $new_version = GetNewVersion();             // As reported by install files
         
    if ($installed_version != $new_version)
    {
        
        // We need to do install
        mURLin_setup_tables();
        import_xml_data(mURLin_returnXML(), true);
        
        plugin_mURLin_check_hooks();
        
        db_execute("UPDATE plugin_config SET version='$new_version' WHERE directory='mURLin'");
    }
}

function plugin_mURLin_check_hooks()
{
    api_plugin_register_hook('mURLin', 'top_header_tabs','mURLin_show_tab', 'setup.php');
    api_plugin_register_hook('mURLin', 'top_graph_header_tabs', 'mURLin_show_tab', 'setup.php');
    api_plugin_register_hook('mURLin', 'draw_navigation_text', 'mURLin_draw_navigation_text', 'setup.php');
    api_plugin_register_hook('mURLin', 'poller_bottom', 'mURLin_poller_bottom', 'setup.php');
    
    api_plugin_register_realm('mURLin', 'mURLin.php,url_edit.php', 'Edit URL to Host Mappings', 1);
}

function mURLin_poller_bottom()
{
    cacti_log("mURLin - INFO: Reached Poller Bottom, clearing cache");
    // Remove all entries from the mURLin Poller cache as the poller cycle is complete
    $sql = "TRUNCATE TABLE plugin_mURLin_cache";
    db_execute($sql);
}

function GetInstalledVersion()
{
    return db_fetch_cell("SELECT version FROM plugin_config WHERE directory='mURLin'");
}

function GetNewVersion()
{
    $result = plugin_mURLin_version();
    return $result['version'];
}

function mURLin_version()
{
    return plugin_mURLin_version();
}

function plugin_mURLin_version() 
{
    return array(       'name'          => 'mURLin',
                        'version' 	=> '0.2.4',
			'longname'	=> 'URL Monitoring Agent',
			'author'	=> 'James Payne',
			'homepage'	=> 'http://www.withjames.co.uk',
			'email'         => 'jamoflaw@gmail.com',
			'url'		=> 'http://www.withjames.co.uk/'
			);
}

function mURLin_show_tab () 
{
	global $config;
        
        if (api_user_realm_auth('mURLin.php'))
        {
            if (substr_count($_SERVER["REQUEST_URI"], "mURLin.php") || substr_count($_SERVER["REQUEST_URI"], "url_edit.php"))
                print '<a href="' . $config['url_path'] . 'plugins/mURLin/mURLin.php"><img src="' . $config['url_path'] . 'plugins/mURLin/images/tab_mURLin_down.png" align="absmiddle" border="0" alt="mURLin"></a>';
            else
                print '<a href="' . $config['url_path'] . 'plugins/mURLin/mURLin.php"><img src="' . $config['url_path'] . 'plugins/mURLin/images/tab_mURLin.png" align="absmiddle" border="0" alt="mURLin"></a>';
        }
        
        
}

function mURLin_setup_tables()
{
    // Create database tables
    if (!mURLin_TableExist('plugin_mURLin_index'))
    {
        $data = array();
        $data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'auto_increment' => true);
        $data['primary'] = 'id';

        $data['type'] = 'MyISAM';
        $data['comment'] = 'Table of URL to Host Mappings';
        api_plugin_db_table_create ('mURLin', 'plugin_mURLin_index', $data);
    }
    
    // Create mURLin Host Table
    $data = array();
    $data['columns'][] = array('name' => 'host_id', 'type' => 'int(11)');
    $data['columns'][] = array('name' => 'url', 'type' => 'varchar(2048)');
    $data['columns'][] = array('name' => 'text_match', 'type' => 'varchar(2048)'); 
    $data['columns'][] = array('name' => 'timeout', 'type' => 'int(3)'); 
    $data['columns'][] = array('name' => 'proxyserver', 'type' => 'int(1)');
    $data['columns'][] = array('name' => 'proxyaddress', 'type' => 'varchar(256)');
    $data['columns'][] = array('name' => 'proxyusername', 'type' => 'varchar(256)');
    $data['columns'][] = array('name' => 'proxypassword', 'type' => 'varchar(256)');
    
    foreach ($data['columns'] as $d)
    {
        mURLin_AddDBColumnIfNotExist('plugin_mURLin_index', $d);
    }

    if (mURLin_TableExist('plugin_mURLin_cache') == true && GetInstalledVersion() == '0.2.0')
    {
        // There was a db regression bug here we need to drop and recreate the cache table...
        $sql = "DROP TABLE plugin_mURLin_cache";
        db_execute($sql);
        
        // The table will be recreated by the next step
    }
    

    // Cache Cache Table
    if (mURLin_TableExist('plugin_mURLin_cache') != true)
    {
        $data = array();
        $data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false);
        $data['primary'] = 'id';
    
        $data['type'] = 'MyISAM';
        $data['comment'] = 'mURLin Cache Table';
        api_plugin_db_table_create ('mURLin', 'plugin_mURLin_cache', $data);
    }
    
    $data = array();
    $data['columns'][] = array('name' => 'total_time', 'type' => 'decimal(10,6)', 'NULL' => false);
    $data['columns'][] = array('name' => 'http_code', 'type' => 'int(11)', 'NULL' => false);
    $data['columns'][] = array('name' => 'size_download', 'type' => 'int(11)', 'NULL' => false);
    $data['columns'][] = array('name' => 'redirect_count', 'type' => 'int(11)', 'NULL' => false);
    
    // Calculated values (based on download of successful data, including regex)
    $data['columns'][] = array('name' => 'availability', 'type' => 'int(11)', 'NULL' => false);
        
    // Performance Values
    $data['columns'][] = array('name' => 'namelookup_time', 'type' => 'decimal(10,6)', 'NULL' => false);
    $data['columns'][] = array('name' => 'connect_time', 'type' => 'decimal(10,6)', 'NULL' => false);
    $data['columns'][] = array('name' => 'pretransfer_time', 'type' => 'decimal(10,6)', 'NULL' => false);
    $data['columns'][] = array('name' => 'starttransfer_time', 'type' => 'decimal(10,6)', 'NULL' => false);
    $data['columns'][] = array('name' => 'redirect_time', 'type' => 'decimal(10,6)', 'NULL' => false);
    
    foreach ($data['columns'] as $d)
    {
        mURLin_AddDBColumnIfNotExist('plugin_mURLin_cache', $d);
    }
    
}


function mURLin_draw_navigation_text ($nav) 
{
   $nav['mURLin.php:'] = array('title' => 'mURLin', 'mapping' => '', 'url' => 'mURLin.php', 'level' => '1');
   $nav['mURLin.php:confirmdelete'] = array('title' => 'Confirm Delete', 'mapping' => 'mURLin.php:', 'url' => 'mURLin.php', 'level' => '2');
   $nav['url_edit.php:'] = array('title' => 'Edit URL Mapping', 'mapping' => 'mURLin.php:', 'url' => 'mURLin.php', 'level' => '2');   
   
   return $nav;
}


function mURLin_returnXML()
{
    $xml = "
    <cacti>	
	<hash_040024e404254402722ff686605143fb5a2f93>
		<name>mURLin - URL Agent</name>
		<description>Gets Monitored URLs Defined Through mURLin</description>
		<xml_path>&lt;path_cacti&gt;/plugins/mURLin/xml/mURLin.xml</xml_path>
		<data_input_id>hash_03002480e9e4c4191a5da189ae26d0e237f015</data_input_id>
		<graphs>
			<hash_11002454c40208a8969209397ebe6f3f726faf>
				<name>mURLin - URL Agent (Download Time)</name>
				<graph_template_id>hash_0000240579876ebc1060f81578da8f900584bc</graph_template_id>
				<rrd>
					<item_000>
						<snmp_field_name>values</snmp_field_name>
						<data_template_id>hash_010024e3f7d4fc8065f9938f775517950c1675</data_template_id>
						<data_template_rrd_id>hash_08002485a83480ebbd417c90a985256c812600</data_template_rrd_id>
					</item_000>
					<item_001>
						<snmp_field_name>http_code</snmp_field_name>
						<data_template_id>hash_010024e3f7d4fc8065f9938f775517950c1675</data_template_id>
						<data_template_rrd_id>hash_0800249c2f142153fa010ce4a0d7edb90105eb</data_template_rrd_id>
					</item_001>
				</rrd>
				<sv_graph>
					<hash_1200246a9a639b12874a4273c6b903747af87f>
						<field_name>name</field_name>
						<sequence>1</sequence>
						<text>|host_description| - |query_url|</text>
					</hash_1200246a9a639b12874a4273c6b903747af87f>
				</sv_graph>
				<sv_data_source>
					<hash_1300247928231cd013f829684bd204c2dd0aaf>
						<field_name>title</field_name>
						<data_template_id>hash_010024e3f7d4fc8065f9938f775517950c1675</data_template_id>
						<sequence>1</sequence>
						<text>|host_description| - |query_url|</text>
					</hash_1300247928231cd013f829684bd204c2dd0aaf>
				</sv_data_source>
			</hash_11002454c40208a8969209397ebe6f3f726faf>
			<hash_110024dd72b47444d72ad96b24f00598fbe55d>
				<name>mURLin - URL Agent (Page Size)</name>
				<graph_template_id>hash_000024325e99885b8879654630635659a8fe01</graph_template_id>
				<rrd>
					<item_000>
						<snmp_field_name>http_code</snmp_field_name>
						<data_template_id>hash_010024e3f7d4fc8065f9938f775517950c1675</data_template_id>
						<data_template_rrd_id>hash_0800249c2f142153fa010ce4a0d7edb90105eb</data_template_rrd_id>
					</item_000>
					<item_001>
						<snmp_field_name>downloadsize</snmp_field_name>
						<data_template_id>hash_010024e3f7d4fc8065f9938f775517950c1675</data_template_id>
						<data_template_rrd_id>hash_0800247cabdd905476594ddf297959beadb0a7</data_template_rrd_id>
					</item_001>
				</rrd>
				<sv_graph>
					<hash_120024d1bc99bb59088610c18e857a7c68be70>
						<field_name>name</field_name>
						<sequence>1</sequence>
						<text>|host_description| - |query_url|</text>
					</hash_120024d1bc99bb59088610c18e857a7c68be70>
				</sv_graph>
				<sv_data_source>
					<hash_1300242444a09ddb1c4088c3e729c74e3cf0bf>
						<field_name>title</field_name>
						<data_template_id>hash_010024e3f7d4fc8065f9938f775517950c1675</data_template_id>
						<sequence>1</sequence>
						<text>|host_description| - |query_url|</text>
					</hash_1300242444a09ddb1c4088c3e729c74e3cf0bf>
				</sv_data_source>
			</hash_110024dd72b47444d72ad96b24f00598fbe55d>
			<hash_11002448ba1c2b320819c32d21f0356fb8ca06>
				<name>mURLin - URL Agent (Time Breakdown)</name>
				<graph_template_id>hash_0000243b572808235cd9e9c98f7956c74f2d4f</graph_template_id>
				<rrd>
					<item_000>
						<snmp_field_name>values</snmp_field_name>
						<data_template_id>hash_010024e3f7d4fc8065f9938f775517950c1675</data_template_id>
						<data_template_rrd_id>hash_08002485a83480ebbd417c90a985256c812600</data_template_rrd_id>
					</item_000>
					<item_001>
						<snmp_field_name>namelookup_time</snmp_field_name>
						<data_template_id>hash_010024e3f7d4fc8065f9938f775517950c1675</data_template_id>
						<data_template_rrd_id>hash_0800240accad3b2c20624bbb908d9be77463a8</data_template_rrd_id>
					</item_001>
					<item_002>
						<snmp_field_name>connect_time</snmp_field_name>
						<data_template_id>hash_010024e3f7d4fc8065f9938f775517950c1675</data_template_id>
						<data_template_rrd_id>hash_08002458011232273f66e4b7d48c75b53fe7aa</data_template_rrd_id>
					</item_002>
					<item_003>
						<snmp_field_name>pretransfer_time</snmp_field_name>
						<data_template_id>hash_010024e3f7d4fc8065f9938f775517950c1675</data_template_id>
						<data_template_rrd_id>hash_08002452412e915a58bdaa6bd764b22721627b</data_template_rrd_id>
					</item_003>
					<item_004>
						<snmp_field_name>starttransfer_time</snmp_field_name>
						<data_template_id>hash_010024e3f7d4fc8065f9938f775517950c1675</data_template_id>
						<data_template_rrd_id>hash_080024f7a43d7b5fdc401c55427a373eed964c</data_template_rrd_id>
					</item_004>
					<item_005>
						<snmp_field_name>redirect_time</snmp_field_name>
						<data_template_id>hash_010024e3f7d4fc8065f9938f775517950c1675</data_template_id>
						<data_template_rrd_id>hash_080024949b2f129d89b73cb883da7f33e0dfa6</data_template_rrd_id>
					</item_005>
				</rrd>
				<sv_graph>
					<hash_12002479e513e03feadfd21c76751d46aded65>
						<field_name>name</field_name>
						<sequence>1</sequence>
						<text>|host_description| - |query_url|</text>
					</hash_12002479e513e03feadfd21c76751d46aded65>
				</sv_graph>
				<sv_data_source>
					<hash_1300246f9ec3ac7c2d9b4b1b9c1f7312bfe472>
						<field_name>title</field_name>
						<data_template_id>hash_010024e3f7d4fc8065f9938f775517950c1675</data_template_id>
						<sequence>2</sequence>
						<text>|host_description| - |query_url|</text>
					</hash_1300246f9ec3ac7c2d9b4b1b9c1f7312bfe472>
				</sv_data_source>
			</hash_11002448ba1c2b320819c32d21f0356fb8ca06>
			<hash_1100246c0989c4e35d145a734dd122b45ea2ee>
				<name>mURLin - URL Agent (Availability)</name>
				<graph_template_id>hash_000024f28a6a23f913af42864c51fcb1271ce2</graph_template_id>
				<rrd>
					<item_000>
						<snmp_field_name>availability</snmp_field_name>
						<data_template_id>hash_010024e3f7d4fc8065f9938f775517950c1675</data_template_id>
						<data_template_rrd_id>hash_08002485c20c9e47bc588e0f04b61aafe1148c</data_template_rrd_id>
					</item_000>
				</rrd>
				<sv_graph>
					<hash_120024d7d2b8b6c77db7e3ee3e0232453091d2>
						<field_name>name</field_name>
						<sequence>1</sequence>
						<text>|host_description| - |query_url|</text>
					</hash_120024d7d2b8b6c77db7e3ee3e0232453091d2>
				</sv_graph>
				<sv_data_source>
					<hash_130024a84a452fc8cb938a87a37e7e4998bfbe>
						<field_name>title</field_name>
						<data_template_id>hash_010024e3f7d4fc8065f9938f775517950c1675</data_template_id>
						<sequence>1</sequence>
						<text>|host_description| - |query_url|</text>
					</hash_130024a84a452fc8cb938a87a37e7e4998bfbe>
				</sv_data_source>
			</hash_1100246c0989c4e35d145a734dd122b45ea2ee>
		</graphs>
	</hash_040024e404254402722ff686605143fb5a2f93>
	<hash_03002480e9e4c4191a5da189ae26d0e237f015>
		<name>Get Script Data (Indexed)</name>
		<type_id>4</type_id>
		<input_string></input_string>
		<fields>
			<hash_070024d39556ecad6166701bfb0e28c5a11108>
				<name>Index Type</name>
				<update_rra></update_rra>
				<regexp_match></regexp_match>
				<allow_nulls></allow_nulls>
				<type_code>index_type</type_code>
				<input_output>in</input_output>
				<data_name>index_type</data_name>
			</hash_070024d39556ecad6166701bfb0e28c5a11108>
			<hash_0700243b7caa46eb809fc238de6ef18b6e10d5>
				<name>Index Value</name>
				<update_rra></update_rra>
				<regexp_match></regexp_match>
				<allow_nulls></allow_nulls>
				<type_code>index_value</type_code>
				<input_output>in</input_output>
				<data_name>index_value</data_name>
			</hash_0700243b7caa46eb809fc238de6ef18b6e10d5>
			<hash_07002474af2e42dc12956c4817c2ef5d9983f9>
				<name>Output Type ID</name>
				<update_rra></update_rra>
				<regexp_match></regexp_match>
				<allow_nulls></allow_nulls>
				<type_code>output_type</type_code>
				<input_output>in</input_output>
				<data_name>output_type</data_name>
			</hash_07002474af2e42dc12956c4817c2ef5d9983f9>
			<hash_0700248ae57f09f787656bf4ac541e8bd12537>
				<name>Output Value</name>
				<update_rra>on</update_rra>
				<regexp_match></regexp_match>
				<allow_nulls></allow_nulls>
				<type_code></type_code>
				<input_output>out</input_output>
				<data_name>output</data_name>
			</hash_0700248ae57f09f787656bf4ac541e8bd12537>
		</fields>
	</hash_03002480e9e4c4191a5da189ae26d0e237f015>
	<hash_0000240579876ebc1060f81578da8f900584bc>
		<name>mURLin - URL Agent (Download Time)</name>
		<graph>
			<t_title></t_title>
			<title>|host_description| - |query_url|</title>
			<t_image_format_id></t_image_format_id>
			<image_format_id>1</image_format_id>
			<t_height></t_height>
			<height>120</height>
			<t_width></t_width>
			<width>500</width>
			<t_slope_mode></t_slope_mode>
			<slope_mode></slope_mode>
			<t_auto_scale></t_auto_scale>
			<auto_scale>on</auto_scale>
			<t_auto_scale_opts></t_auto_scale_opts>
			<auto_scale_opts>4</auto_scale_opts>
			<t_auto_scale_log></t_auto_scale_log>
			<auto_scale_log></auto_scale_log>
			<t_scale_log_units></t_scale_log_units>
			<scale_log_units></scale_log_units>
			<t_auto_scale_rigid></t_auto_scale_rigid>
			<auto_scale_rigid></auto_scale_rigid>
			<t_auto_padding></t_auto_padding>
			<auto_padding>on</auto_padding>
			<t_export></t_export>
			<export>on</export>
			<t_upper_limit></t_upper_limit>
			<upper_limit></upper_limit>
			<t_lower_limit></t_lower_limit>
			<lower_limit></lower_limit>
			<t_base_value></t_base_value>
			<base_value>1000</base_value>
			<t_unit_value></t_unit_value>
			<unit_value></unit_value>
			<t_unit_exponent_value></t_unit_exponent_value>
			<unit_exponent_value></unit_exponent_value>
			<t_vertical_label></t_vertical_label>
			<vertical_label>Milliseconds</vertical_label>
		</graph>
		<items>
			<hash_10002490337d66de34a90cccf787b8f715414a>
				<task_item_id>hash_08002485a83480ebbd417c90a985256c812600</task_item_id>
				<color_id>FF3932</color_id>
				<alpha>FF</alpha>
				<graph_type_id>7</graph_type_id>
				<consolidation_function_id>1</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Page Load Time</text_format>
				<hard_return></hard_return>
				<sequence>1</sequence>
			</hash_10002490337d66de34a90cccf787b8f715414a>
			<hash_1000243d1db965e6c31142232c9d4b6519cf03>
				<task_item_id>hash_08002485a83480ebbd417c90a985256c812600</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>3</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Max:</text_format>
				<hard_return></hard_return>
				<sequence>2</sequence>
			</hash_1000243d1db965e6c31142232c9d4b6519cf03>
			<hash_100024d243010145bf7d12a1b4adb5603e1571>
				<task_item_id>hash_08002485a83480ebbd417c90a985256c812600</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>2</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Min:</text_format>
				<hard_return></hard_return>
				<sequence>3</sequence>
			</hash_100024d243010145bf7d12a1b4adb5603e1571>
			<hash_10002481734bf94ca1e2362c598a879b77ee32>
				<task_item_id>hash_08002485a83480ebbd417c90a985256c812600</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>1</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Avg:</text_format>
				<hard_return></hard_return>
				<sequence>4</sequence>
			</hash_10002481734bf94ca1e2362c598a879b77ee32>
			<hash_1000249dd6e2b7b6b063a49d7087a660210a10>
				<task_item_id>hash_08002485a83480ebbd417c90a985256c812600</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>4</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_06002419414480d6897c8731c7dc6c5310653e</gprint_id>
				<text_format>Last:</text_format>
				<hard_return>on</hard_return>
				<sequence>5</sequence>
			</hash_1000249dd6e2b7b6b063a49d7087a660210a10>
			<hash_1000246edff10c1c1a3a3ea94f5180a888537b>
				<task_item_id>hash_0800249c2f142153fa010ce4a0d7edb90105eb</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>4</consolidation_function_id>
				<cdef_id>0</cdef_id>
				<value></value>
				<gprint_id>hash_06002419414480d6897c8731c7dc6c5310653e</gprint_id>
				<text_format>HTTP Return Code:</text_format>
				<hard_return></hard_return>
				<sequence>6</sequence>
			</hash_1000246edff10c1c1a3a3ea94f5180a888537b>
		</items>
		<inputs>
			<hash_090024bccda3d864b0cf38ebb0234f987955ff>
				<name>Data Source [values]</name>
				<description></description>
				<column_name>task_item_id</column_name>
				<items>hash_00002490337d66de34a90cccf787b8f715414a|hash_0000243d1db965e6c31142232c9d4b6519cf03|hash_000024d243010145bf7d12a1b4adb5603e1571|hash_00002481734bf94ca1e2362c598a879b77ee32|hash_0000249dd6e2b7b6b063a49d7087a660210a10</items>
			</hash_090024bccda3d864b0cf38ebb0234f987955ff>
			<hash_0900242b24456a827cf28c5181728c3f4ebbaf>
				<name>Data Source [http_code]</name>
				<description></description>
				<column_name>task_item_id</column_name>
				<items>hash_0000246edff10c1c1a3a3ea94f5180a888537b</items>
			</hash_0900242b24456a827cf28c5181728c3f4ebbaf>
		</inputs>
	</hash_0000240579876ebc1060f81578da8f900584bc>
	<hash_000024325e99885b8879654630635659a8fe01>
		<name>mURLin - URL Agent (Page Size)</name>
		<graph>
			<t_title></t_title>
			<title>|host_description| - |query_url|</title>
			<t_image_format_id></t_image_format_id>
			<image_format_id>1</image_format_id>
			<t_height></t_height>
			<height>120</height>
			<t_width></t_width>
			<width>500</width>
			<t_slope_mode></t_slope_mode>
			<slope_mode></slope_mode>
			<t_auto_scale></t_auto_scale>
			<auto_scale>on</auto_scale>
			<t_auto_scale_opts></t_auto_scale_opts>
			<auto_scale_opts>4</auto_scale_opts>
			<t_auto_scale_log></t_auto_scale_log>
			<auto_scale_log></auto_scale_log>
			<t_scale_log_units></t_scale_log_units>
			<scale_log_units></scale_log_units>
			<t_auto_scale_rigid></t_auto_scale_rigid>
			<auto_scale_rigid></auto_scale_rigid>
			<t_auto_padding></t_auto_padding>
			<auto_padding>on</auto_padding>
			<t_export></t_export>
			<export>on</export>
			<t_upper_limit></t_upper_limit>
			<upper_limit></upper_limit>
			<t_lower_limit></t_lower_limit>
			<lower_limit></lower_limit>
			<t_base_value></t_base_value>
			<base_value>1000</base_value>
			<t_unit_value></t_unit_value>
			<unit_value></unit_value>
			<t_unit_exponent_value></t_unit_exponent_value>
			<unit_exponent_value></unit_exponent_value>
			<t_vertical_label></t_vertical_label>
			<vertical_label>Bytes</vertical_label>
		</graph>
		<items>
			<hash_10002496acac316e3fa6b904d8097737500c5d>
				<task_item_id>hash_0800247cabdd905476594ddf297959beadb0a7</task_item_id>
				<color_id>FF3932</color_id>
				<alpha>FF</alpha>
				<graph_type_id>7</graph_type_id>
				<consolidation_function_id>1</consolidation_function_id>
				<cdef_id>0</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Download Size</text_format>
				<hard_return></hard_return>
				<sequence>1</sequence>
			</hash_10002496acac316e3fa6b904d8097737500c5d>
			<hash_100024e848e8d79ebd3cbdf24c08e1bd4ad705>
				<task_item_id>hash_0800247cabdd905476594ddf297959beadb0a7</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>4</consolidation_function_id>
				<cdef_id>0</cdef_id>
				<value></value>
				<gprint_id>hash_06002419414480d6897c8731c7dc6c5310653e</gprint_id>
				<text_format>Last:</text_format>
				<hard_return>on</hard_return>
				<sequence>5</sequence>
			</hash_100024e848e8d79ebd3cbdf24c08e1bd4ad705>
			<hash_10002449f6c4f15321f16a683bccf57ca53245>
				<task_item_id>hash_0800249c2f142153fa010ce4a0d7edb90105eb</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>4</consolidation_function_id>
				<cdef_id>0</cdef_id>
				<value></value>
				<gprint_id>hash_06002419414480d6897c8731c7dc6c5310653e</gprint_id>
				<text_format>HTTP Return Code:</text_format>
				<hard_return></hard_return>
				<sequence>6</sequence>
			</hash_10002449f6c4f15321f16a683bccf57ca53245>
		</items>
		<inputs>
			<hash_090024b8ab51da82433ce3946ed26731b87cc1>
				<name>Data Source [http_code]</name>
				<description></description>
				<column_name>task_item_id</column_name>
				<items>hash_00002449f6c4f15321f16a683bccf57ca53245</items>
			</hash_090024b8ab51da82433ce3946ed26731b87cc1>
			<hash_09002445c696f4b35298b1d093623131e830cb>
				<name>Data Source [downloadsize]</name>
				<description></description>
				<column_name>task_item_id</column_name>
				<items>hash_00002496acac316e3fa6b904d8097737500c5d|hash_000024e848e8d79ebd3cbdf24c08e1bd4ad705</items>
			</hash_09002445c696f4b35298b1d093623131e830cb>
		</inputs>
	</hash_000024325e99885b8879654630635659a8fe01>
	<hash_0000243b572808235cd9e9c98f7956c74f2d4f>
		<name>mURLin - URL Agent (Time Breakdown)</name>
		<graph>
			<t_title></t_title>
			<title>|host_description| - |query_url|</title>
			<t_image_format_id></t_image_format_id>
			<image_format_id>1</image_format_id>
			<t_height></t_height>
			<height>120</height>
			<t_width></t_width>
			<width>500</width>
			<t_slope_mode></t_slope_mode>
			<slope_mode>on</slope_mode>
			<t_auto_scale></t_auto_scale>
			<auto_scale>on</auto_scale>
			<t_auto_scale_opts></t_auto_scale_opts>
			<auto_scale_opts>2</auto_scale_opts>
			<t_auto_scale_log></t_auto_scale_log>
			<auto_scale_log></auto_scale_log>
			<t_scale_log_units></t_scale_log_units>
			<scale_log_units></scale_log_units>
			<t_auto_scale_rigid></t_auto_scale_rigid>
			<auto_scale_rigid></auto_scale_rigid>
			<t_auto_padding></t_auto_padding>
			<auto_padding>on</auto_padding>
			<t_export></t_export>
			<export>on</export>
			<t_upper_limit></t_upper_limit>
			<upper_limit>100</upper_limit>
			<t_lower_limit></t_lower_limit>
			<lower_limit>0</lower_limit>
			<t_base_value></t_base_value>
			<base_value>1000</base_value>
			<t_unit_value></t_unit_value>
			<unit_value></unit_value>
			<t_unit_exponent_value></t_unit_exponent_value>
			<unit_exponent_value></unit_exponent_value>
			<t_vertical_label></t_vertical_label>
			<vertical_label></vertical_label>
		</graph>
		<items>
			<hash_100024b96ad119636a907cbe40ee3217dd14b6>
				<task_item_id>hash_08002485a83480ebbd417c90a985256c812600</task_item_id>
				<color_id>FFF200</color_id>
				<alpha>FF</alpha>
				<graph_type_id>7</graph_type_id>
				<consolidation_function_id>1</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_06002419414480d6897c8731c7dc6c5310653e</gprint_id>
				<text_format>Total Time (ms)</text_format>
				<hard_return></hard_return>
				<sequence>1</sequence>
			</hash_100024b96ad119636a907cbe40ee3217dd14b6>
			<hash_100024818948c473905c563ad75f0b4b5fc7e2>
				<task_item_id>hash_08002485a83480ebbd417c90a985256c812600</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>2</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Min:</text_format>
				<hard_return></hard_return>
				<sequence>2</sequence>
			</hash_100024818948c473905c563ad75f0b4b5fc7e2>
			<hash_10002441b47cd068f4216858a7fecda2946992>
				<task_item_id>hash_08002485a83480ebbd417c90a985256c812600</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>3</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Max:</text_format>
				<hard_return></hard_return>
				<sequence>3</sequence>
			</hash_10002441b47cd068f4216858a7fecda2946992>
			<hash_100024b185a95c0e62ec6f870c418ab91d547e>
				<task_item_id>hash_08002485a83480ebbd417c90a985256c812600</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>4</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Last:</text_format>
				<hard_return>on</hard_return>
				<sequence>4</sequence>
			</hash_100024b185a95c0e62ec6f870c418ab91d547e>
			<hash_100024bcfa50ff60b4d8c6d9e3ffccb5f53b45>
				<task_item_id>hash_080024f7a43d7b5fdc401c55427a373eed964c</task_item_id>
				<color_id>FFAB00</color_id>
				<alpha>FF</alpha>
				<graph_type_id>7</graph_type_id>
				<consolidation_function_id>1</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_06002419414480d6897c8731c7dc6c5310653e</gprint_id>
				<text_format>Start Transfer Time (ms)</text_format>
				<hard_return></hard_return>
				<sequence>5</sequence>
			</hash_100024bcfa50ff60b4d8c6d9e3ffccb5f53b45>
			<hash_100024bccd9d2985bb4e8a7bcb726bdfb7332f>
				<task_item_id>hash_080024f7a43d7b5fdc401c55427a373eed964c</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>2</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Min:</text_format>
				<hard_return></hard_return>
				<sequence>6</sequence>
			</hash_100024bccd9d2985bb4e8a7bcb726bdfb7332f>
			<hash_1000249b032bf477364b891fe9bcaf11b49580>
				<task_item_id>hash_080024f7a43d7b5fdc401c55427a373eed964c</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>3</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Max:</text_format>
				<hard_return></hard_return>
				<sequence>7</sequence>
			</hash_1000249b032bf477364b891fe9bcaf11b49580>
			<hash_10002460b8688fe3eb0e617844acdf51719181>
				<task_item_id>hash_080024f7a43d7b5fdc401c55427a373eed964c</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>4</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Last:</text_format>
				<hard_return>on</hard_return>
				<sequence>8</sequence>
			</hash_10002460b8688fe3eb0e617844acdf51719181>
			<hash_100024ce26661a9d992945202f22ed5063338f>
				<task_item_id>hash_08002452412e915a58bdaa6bd764b22721627b</task_item_id>
				<color_id>FF0000</color_id>
				<alpha>FF</alpha>
				<graph_type_id>7</graph_type_id>
				<consolidation_function_id>1</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_06002419414480d6897c8731c7dc6c5310653e</gprint_id>
				<text_format>Pre-transfer Time (ms)</text_format>
				<hard_return></hard_return>
				<sequence>9</sequence>
			</hash_100024ce26661a9d992945202f22ed5063338f>
			<hash_10002423cdc695d686da7a567cb5ca8041c371>
				<task_item_id>hash_08002452412e915a58bdaa6bd764b22721627b</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>2</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Min:</text_format>
				<hard_return></hard_return>
				<sequence>10</sequence>
			</hash_10002423cdc695d686da7a567cb5ca8041c371>
			<hash_100024f61288be6692a27d344e72dad15b0f85>
				<task_item_id>hash_08002452412e915a58bdaa6bd764b22721627b</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>3</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Max:</text_format>
				<hard_return></hard_return>
				<sequence>11</sequence>
			</hash_100024f61288be6692a27d344e72dad15b0f85>
			<hash_10002431dc2f0cdbe187dbb7f778e5d73ce68b>
				<task_item_id>hash_08002452412e915a58bdaa6bd764b22721627b</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>4</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Last:</text_format>
				<hard_return>on</hard_return>
				<sequence>12</sequence>
			</hash_10002431dc2f0cdbe187dbb7f778e5d73ce68b>
			<hash_100024bf22ddf91de753e6f0d6cf550cebaefc>
				<task_item_id>hash_08002458011232273f66e4b7d48c75b53fe7aa</task_item_id>
				<color_id>B90054</color_id>
				<alpha>FF</alpha>
				<graph_type_id>7</graph_type_id>
				<consolidation_function_id>1</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_06002419414480d6897c8731c7dc6c5310653e</gprint_id>
				<text_format>Connect Time (ms)</text_format>
				<hard_return></hard_return>
				<sequence>13</sequence>
			</hash_100024bf22ddf91de753e6f0d6cf550cebaefc>
			<hash_1000246bd71a4a4ec1078595c1c57d43bd22fa>
				<task_item_id>hash_08002458011232273f66e4b7d48c75b53fe7aa</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>2</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Min:</text_format>
				<hard_return></hard_return>
				<sequence>14</sequence>
			</hash_1000246bd71a4a4ec1078595c1c57d43bd22fa>
			<hash_1000243a81c6a619e99a22eb0f17a4d098561b>
				<task_item_id>hash_08002458011232273f66e4b7d48c75b53fe7aa</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>3</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Max:</text_format>
				<hard_return></hard_return>
				<sequence>15</sequence>
			</hash_1000243a81c6a619e99a22eb0f17a4d098561b>
			<hash_10002422068b0b8cb6342e79394599ee280074>
				<task_item_id>hash_08002458011232273f66e4b7d48c75b53fe7aa</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>4</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Last:</text_format>
				<hard_return>on</hard_return>
				<sequence>16</sequence>
			</hash_10002422068b0b8cb6342e79394599ee280074>
			<hash_100024f91e02e70a0aee4f646c9f4e256f5d91>
				<task_item_id>hash_0800240accad3b2c20624bbb908d9be77463a8</task_item_id>
				<color_id>562B29</color_id>
				<alpha>FF</alpha>
				<graph_type_id>7</graph_type_id>
				<consolidation_function_id>1</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_06002419414480d6897c8731c7dc6c5310653e</gprint_id>
				<text_format>DNS Lookup Time (ms)</text_format>
				<hard_return></hard_return>
				<sequence>17</sequence>
			</hash_100024f91e02e70a0aee4f646c9f4e256f5d91>
			<hash_1000248f61e834337cf49a82a765dfa7c7215d>
				<task_item_id>hash_0800240accad3b2c20624bbb908d9be77463a8</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>2</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Min:</text_format>
				<hard_return></hard_return>
				<sequence>18</sequence>
			</hash_1000248f61e834337cf49a82a765dfa7c7215d>
			<hash_10002422794566d4479d86d9c83b85359f88f4>
				<task_item_id>hash_0800240accad3b2c20624bbb908d9be77463a8</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>3</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Max:</text_format>
				<hard_return></hard_return>
				<sequence>19</sequence>
			</hash_10002422794566d4479d86d9c83b85359f88f4>
			<hash_10002457061efd978eff3164321a4011c76bf3>
				<task_item_id>hash_0800240accad3b2c20624bbb908d9be77463a8</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>4</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Last:</text_format>
				<hard_return>on</hard_return>
				<sequence>20</sequence>
			</hash_10002457061efd978eff3164321a4011c76bf3>
			<hash_10002424244014a565223eb127ed23ea3cfa5b>
				<task_item_id>hash_08002485a83480ebbd417c90a985256c812600</task_item_id>
				<color_id>000000</color_id>
				<alpha>FF</alpha>
				<graph_type_id>4</graph_type_id>
				<consolidation_function_id>1</consolidation_function_id>
				<cdef_id>hash_050024c4ae4deda2771ca7c8a3073b918fa177</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format></text_format>
				<hard_return></hard_return>
				<sequence>21</sequence>
			</hash_10002424244014a565223eb127ed23ea3cfa5b>
		</items>
		<inputs>
			<hash_090024c72edf76a2ac99a233a71db17f023f09>
				<name>Data Source [namelookup_time]</name>
				<description></description>
				<column_name>task_item_id</column_name>
				<items>hash_000024f91e02e70a0aee4f646c9f4e256f5d91|hash_0000248f61e834337cf49a82a765dfa7c7215d|hash_00002422794566d4479d86d9c83b85359f88f4|hash_00002457061efd978eff3164321a4011c76bf3</items>
			</hash_090024c72edf76a2ac99a233a71db17f023f09>
			<hash_0900246b0c1b5e1b90c90fa1ac18a719fbaf1d>
				<name>Data Source [connect_time]</name>
				<description></description>
				<column_name>task_item_id</column_name>
				<items>hash_000024bf22ddf91de753e6f0d6cf550cebaefc|hash_0000246bd71a4a4ec1078595c1c57d43bd22fa|hash_0000243a81c6a619e99a22eb0f17a4d098561b|hash_00002422068b0b8cb6342e79394599ee280074</items>
			</hash_0900246b0c1b5e1b90c90fa1ac18a719fbaf1d>
			<hash_090024e5c47f06f773688ff2479a4a3c3269bc>
				<name>Data Source [pretransfer_time]</name>
				<description></description>
				<column_name>task_item_id</column_name>
				<items>hash_000024ce26661a9d992945202f22ed5063338f|hash_00002423cdc695d686da7a567cb5ca8041c371|hash_000024f61288be6692a27d344e72dad15b0f85|hash_00002431dc2f0cdbe187dbb7f778e5d73ce68b</items>
			</hash_090024e5c47f06f773688ff2479a4a3c3269bc>
			<hash_090024a020e7bf8436e1ef6c5f54cb411ad95a>
				<name>Data Source [starttransfer_time]</name>
				<description></description>
				<column_name>task_item_id</column_name>
				<items>hash_000024bcfa50ff60b4d8c6d9e3ffccb5f53b45|hash_000024bccd9d2985bb4e8a7bcb726bdfb7332f|hash_0000249b032bf477364b891fe9bcaf11b49580|hash_00002460b8688fe3eb0e617844acdf51719181</items>
			</hash_090024a020e7bf8436e1ef6c5f54cb411ad95a>
			<hash_09002417f8bb59a114662d4fc51716d770751b>
				<name>Data Source [values]</name>
				<description></description>
				<column_name>task_item_id</column_name>
				<items>hash_000024b96ad119636a907cbe40ee3217dd14b6|hash_00002424244014a565223eb127ed23ea3cfa5b|hash_000024818948c473905c563ad75f0b4b5fc7e2|hash_00002441b47cd068f4216858a7fecda2946992|hash_000024b185a95c0e62ec6f870c418ab91d547e</items>
			</hash_09002417f8bb59a114662d4fc51716d770751b>
		</inputs>
	</hash_0000243b572808235cd9e9c98f7956c74f2d4f>
	<hash_000024f28a6a23f913af42864c51fcb1271ce2>
		<name>mURLin - URL Agent (Availability)</name>
		<graph>
			<t_title></t_title>
			<title>|host_description| - |query_url|</title>
			<t_image_format_id></t_image_format_id>
			<image_format_id>1</image_format_id>
			<t_height></t_height>
			<height>120</height>
			<t_width></t_width>
			<width>500</width>
			<t_slope_mode></t_slope_mode>
			<slope_mode>on</slope_mode>
			<t_auto_scale></t_auto_scale>
			<auto_scale>on</auto_scale>
			<t_auto_scale_opts></t_auto_scale_opts>
			<auto_scale_opts>4</auto_scale_opts>
			<t_auto_scale_log></t_auto_scale_log>
			<auto_scale_log></auto_scale_log>
			<t_scale_log_units></t_scale_log_units>
			<scale_log_units></scale_log_units>
			<t_auto_scale_rigid></t_auto_scale_rigid>
			<auto_scale_rigid>on</auto_scale_rigid>
			<t_auto_padding></t_auto_padding>
			<auto_padding>on</auto_padding>
			<t_export></t_export>
			<export>on</export>
			<t_upper_limit></t_upper_limit>
			<upper_limit>100</upper_limit>
			<t_lower_limit></t_lower_limit>
			<lower_limit>0</lower_limit>
			<t_base_value></t_base_value>
			<base_value>1000</base_value>
			<t_unit_value></t_unit_value>
			<unit_value></unit_value>
			<t_unit_exponent_value></t_unit_exponent_value>
			<unit_exponent_value></unit_exponent_value>
			<t_vertical_label></t_vertical_label>
			<vertical_label></vertical_label>
		</graph>
		<items>
			<hash_1000241307d2122271c6f82e48c9ce9acd2a31>
				<task_item_id>hash_08002485c20c9e47bc588e0f04b61aafe1148c</task_item_id>
				<color_id>7EE600</color_id>
				<alpha>FF</alpha>
				<graph_type_id>7</graph_type_id>
				<consolidation_function_id>1</consolidation_function_id>
				<cdef_id>0</cdef_id>
				<value></value>
				<gprint_id>hash_060024e9c43831e54eca8069317a2ce8c6f751</gprint_id>
				<text_format>Site Availability</text_format>
				<hard_return>on</hard_return>
				<sequence>1</sequence>
			</hash_1000241307d2122271c6f82e48c9ce9acd2a31>
			<hash_1000244fe49f3828b0178047373f56088d83fd>
				<task_item_id>hash_08002485c20c9e47bc588e0f04b61aafe1148c</task_item_id>
				<color_id>0</color_id>
				<alpha>FF</alpha>
				<graph_type_id>9</graph_type_id>
				<consolidation_function_id>1</consolidation_function_id>
				<cdef_id>0</cdef_id>
				<value></value>
				<gprint_id>hash_06002419414480d6897c8731c7dc6c5310653e</gprint_id>
				<text_format>Average Availability:</text_format>
				<hard_return></hard_return>
				<sequence>2</sequence>
			</hash_1000244fe49f3828b0178047373f56088d83fd>
		</items>
		<inputs>
			<hash_0900245aeea375e1b2f7cf4a3bee22b6ea7277>
				<name>Data Source [availability]</name>
				<description></description>
				<column_name>task_item_id</column_name>
				<items>hash_0000241307d2122271c6f82e48c9ce9acd2a31|hash_0000244fe49f3828b0178047373f56088d83fd</items>
			</hash_0900245aeea375e1b2f7cf4a3bee22b6ea7277>
		</inputs>
	</hash_000024f28a6a23f913af42864c51fcb1271ce2>
	<hash_010024e3f7d4fc8065f9938f775517950c1675>
		<name>mURLin - URL Agent</name>
		<ds>
			<t_name></t_name>
			<name>|host_description| - |query_url|</name>
			<data_input_id>hash_03002480e9e4c4191a5da189ae26d0e237f015</data_input_id>
			<t_rra_id></t_rra_id>
			<t_rrd_step></t_rrd_step>
			<rrd_step>300</rrd_step>
			<t_active></t_active>
			<active>on</active>
			<rra_items>hash_150024c21df5178e5c955013591239eb0afd46|hash_1500240d9c0af8b8acdc7807943937b3208e29|hash_1500246fc2d038fb42950138b0ce3e9874cc60|hash_150024e36f3adb9f152adfa5dc50fd2b23337e</rra_items>
		</ds>
		<items>
			<hash_08002485c20c9e47bc588e0f04b61aafe1148c>
				<t_data_source_name></t_data_source_name>
				<data_source_name>availability</data_source_name>
				<t_rrd_minimum></t_rrd_minimum>
				<rrd_minimum>0</rrd_minimum>
				<t_rrd_maximum></t_rrd_maximum>
				<rrd_maximum>100</rrd_maximum>
				<t_data_source_type_id></t_data_source_type_id>
				<data_source_type_id>1</data_source_type_id>
				<t_rrd_heartbeat></t_rrd_heartbeat>
				<rrd_heartbeat>600</rrd_heartbeat>
				<t_data_input_field_id></t_data_input_field_id>
				<data_input_field_id>0</data_input_field_id>
			</hash_08002485c20c9e47bc588e0f04b61aafe1148c>
			<hash_08002458011232273f66e4b7d48c75b53fe7aa>
				<t_data_source_name></t_data_source_name>
				<data_source_name>connect_time</data_source_name>
				<t_rrd_minimum></t_rrd_minimum>
				<rrd_minimum>0</rrd_minimum>
				<t_rrd_maximum></t_rrd_maximum>
				<rrd_maximum>U</rrd_maximum>
				<t_data_source_type_id></t_data_source_type_id>
				<data_source_type_id>1</data_source_type_id>
				<t_rrd_heartbeat></t_rrd_heartbeat>
				<rrd_heartbeat>600</rrd_heartbeat>
				<t_data_input_field_id></t_data_input_field_id>
				<data_input_field_id>0</data_input_field_id>
			</hash_08002458011232273f66e4b7d48c75b53fe7aa>
			<hash_0800247cabdd905476594ddf297959beadb0a7>
				<t_data_source_name></t_data_source_name>
				<data_source_name>downloadsize</data_source_name>
				<t_rrd_minimum></t_rrd_minimum>
				<rrd_minimum>U</rrd_minimum>
				<t_rrd_maximum></t_rrd_maximum>
				<rrd_maximum>U</rrd_maximum>
				<t_data_source_type_id></t_data_source_type_id>
				<data_source_type_id>1</data_source_type_id>
				<t_rrd_heartbeat></t_rrd_heartbeat>
				<rrd_heartbeat>600</rrd_heartbeat>
				<t_data_input_field_id></t_data_input_field_id>
				<data_input_field_id>0</data_input_field_id>
			</hash_0800247cabdd905476594ddf297959beadb0a7>
			<hash_0800249c2f142153fa010ce4a0d7edb90105eb>
				<t_data_source_name></t_data_source_name>
				<data_source_name>http_code</data_source_name>
				<t_rrd_minimum></t_rrd_minimum>
				<rrd_minimum>U</rrd_minimum>
				<t_rrd_maximum></t_rrd_maximum>
				<rrd_maximum>U</rrd_maximum>
				<t_data_source_type_id></t_data_source_type_id>
				<data_source_type_id>1</data_source_type_id>
				<t_rrd_heartbeat></t_rrd_heartbeat>
				<rrd_heartbeat>600</rrd_heartbeat>
				<t_data_input_field_id></t_data_input_field_id>
				<data_input_field_id>0</data_input_field_id>
			</hash_0800249c2f142153fa010ce4a0d7edb90105eb>
			<hash_0800240accad3b2c20624bbb908d9be77463a8>
				<t_data_source_name></t_data_source_name>
				<data_source_name>namelookup_time</data_source_name>
				<t_rrd_minimum></t_rrd_minimum>
				<rrd_minimum>0</rrd_minimum>
				<t_rrd_maximum></t_rrd_maximum>
				<rrd_maximum>0</rrd_maximum>
				<t_data_source_type_id></t_data_source_type_id>
				<data_source_type_id>1</data_source_type_id>
				<t_rrd_heartbeat></t_rrd_heartbeat>
				<rrd_heartbeat>600</rrd_heartbeat>
				<t_data_input_field_id></t_data_input_field_id>
				<data_input_field_id>0</data_input_field_id>
			</hash_0800240accad3b2c20624bbb908d9be77463a8>
			<hash_08002452412e915a58bdaa6bd764b22721627b>
				<t_data_source_name></t_data_source_name>
				<data_source_name>pretransfer_time</data_source_name>
				<t_rrd_minimum></t_rrd_minimum>
				<rrd_minimum>0</rrd_minimum>
				<t_rrd_maximum></t_rrd_maximum>
				<rrd_maximum>U</rrd_maximum>
				<t_data_source_type_id></t_data_source_type_id>
				<data_source_type_id>1</data_source_type_id>
				<t_rrd_heartbeat></t_rrd_heartbeat>
				<rrd_heartbeat>600</rrd_heartbeat>
				<t_data_input_field_id></t_data_input_field_id>
				<data_input_field_id>0</data_input_field_id>
			</hash_08002452412e915a58bdaa6bd764b22721627b>
			<hash_08002443cedc95e11e72a3aef208e26bb4bfc7>
				<t_data_source_name></t_data_source_name>
				<data_source_name>redirect_count</data_source_name>
				<t_rrd_minimum></t_rrd_minimum>
				<rrd_minimum>0</rrd_minimum>
				<t_rrd_maximum></t_rrd_maximum>
				<rrd_maximum>U</rrd_maximum>
				<t_data_source_type_id></t_data_source_type_id>
				<data_source_type_id>1</data_source_type_id>
				<t_rrd_heartbeat></t_rrd_heartbeat>
				<rrd_heartbeat>600</rrd_heartbeat>
				<t_data_input_field_id></t_data_input_field_id>
				<data_input_field_id>0</data_input_field_id>
			</hash_08002443cedc95e11e72a3aef208e26bb4bfc7>
			<hash_080024949b2f129d89b73cb883da7f33e0dfa6>
				<t_data_source_name></t_data_source_name>
				<data_source_name>redirect_time</data_source_name>
				<t_rrd_minimum></t_rrd_minimum>
				<rrd_minimum>0</rrd_minimum>
				<t_rrd_maximum></t_rrd_maximum>
				<rrd_maximum>U</rrd_maximum>
				<t_data_source_type_id></t_data_source_type_id>
				<data_source_type_id>1</data_source_type_id>
				<t_rrd_heartbeat></t_rrd_heartbeat>
				<rrd_heartbeat>600</rrd_heartbeat>
				<t_data_input_field_id></t_data_input_field_id>
				<data_input_field_id>0</data_input_field_id>
			</hash_080024949b2f129d89b73cb883da7f33e0dfa6>
			<hash_080024f7a43d7b5fdc401c55427a373eed964c>
				<t_data_source_name></t_data_source_name>
				<data_source_name>starttransfer_time</data_source_name>
				<t_rrd_minimum></t_rrd_minimum>
				<rrd_minimum>0</rrd_minimum>
				<t_rrd_maximum></t_rrd_maximum>
				<rrd_maximum>U</rrd_maximum>
				<t_data_source_type_id></t_data_source_type_id>
				<data_source_type_id>1</data_source_type_id>
				<t_rrd_heartbeat></t_rrd_heartbeat>
				<rrd_heartbeat>600</rrd_heartbeat>
				<t_data_input_field_id></t_data_input_field_id>
				<data_input_field_id>0</data_input_field_id>
			</hash_080024f7a43d7b5fdc401c55427a373eed964c>
			<hash_08002485a83480ebbd417c90a985256c812600>
				<t_data_source_name></t_data_source_name>
				<data_source_name>values</data_source_name>
				<t_rrd_minimum></t_rrd_minimum>
				<rrd_minimum>U</rrd_minimum>
				<t_rrd_maximum></t_rrd_maximum>
				<rrd_maximum>U</rrd_maximum>
				<t_data_source_type_id></t_data_source_type_id>
				<data_source_type_id>1</data_source_type_id>
				<t_rrd_heartbeat></t_rrd_heartbeat>
				<rrd_heartbeat>600</rrd_heartbeat>
				<t_data_input_field_id></t_data_input_field_id>
				<data_input_field_id>0</data_input_field_id>
			</hash_08002485a83480ebbd417c90a985256c812600>
		</items>
		<data>
			<item_000>
				<data_input_field_id>hash_07002474af2e42dc12956c4817c2ef5d9983f9</data_input_field_id>
				<t_value>on</t_value>
				<value></value>
			</item_000>
			<item_001>
				<data_input_field_id>hash_0700243b7caa46eb809fc238de6ef18b6e10d5</data_input_field_id>
				<t_value>on</t_value>
				<value></value>
			</item_001>
			<item_002>
				<data_input_field_id>hash_070024d39556ecad6166701bfb0e28c5a11108</data_input_field_id>
				<t_value>on</t_value>
				<value></value>
			</item_002>
		</data>
	</hash_010024e3f7d4fc8065f9938f775517950c1675>
	<hash_150024c21df5178e5c955013591239eb0afd46>
		<name>Daily (5 Minute Average)</name>
		<x_files_factor>0.5</x_files_factor>
		<steps>1</steps>
		<rows>600</rows>
		<timespan>86400</timespan>
		<cf_items>1|3</cf_items>
	</hash_150024c21df5178e5c955013591239eb0afd46>
	<hash_1500240d9c0af8b8acdc7807943937b3208e29>
		<name>Weekly (30 Minute Average)</name>
		<x_files_factor>0.5</x_files_factor>
		<steps>6</steps>
		<rows>700</rows>
		<timespan>604800</timespan>
		<cf_items>1|3</cf_items>
	</hash_1500240d9c0af8b8acdc7807943937b3208e29>
	<hash_1500246fc2d038fb42950138b0ce3e9874cc60>
		<name>Monthly (2 Hour Average)</name>
		<x_files_factor>0.5</x_files_factor>
		<steps>24</steps>
		<rows>775</rows>
		<timespan>2678400</timespan>
		<cf_items>1|3</cf_items>
	</hash_1500246fc2d038fb42950138b0ce3e9874cc60>
	<hash_150024e36f3adb9f152adfa5dc50fd2b23337e>
		<name>Yearly (1 Day Average)</name>
		<x_files_factor>0.5</x_files_factor>
		<steps>288</steps>
		<rows>797</rows>
		<timespan>33053184</timespan>
		<cf_items>1|3</cf_items>
	</hash_150024e36f3adb9f152adfa5dc50fd2b23337e>
	<hash_050024c4ae4deda2771ca7c8a3073b918fa177>
		<name>Multiply By 1000</name>
		<items>
			<hash_140024b09c6bca2a76dc3c8592fc29e9f79ba7>
				<sequence>1</sequence>
				<type>4</type>
				<value>CURRENT_DATA_SOURCE</value>
			</hash_140024b09c6bca2a76dc3c8592fc29e9f79ba7>
			<hash_14002483f48ee80324ade1ab34b233cfecd1b8>
				<sequence>2</sequence>
				<type>6</type>
				<value>1000</value>
			</hash_14002483f48ee80324ade1ab34b233cfecd1b8>
			<hash_14002460d6acaad341e423eb8eb61d0e043dca>
				<sequence>3</sequence>
				<type>2</type>
				<value>3</value>
			</hash_14002460d6acaad341e423eb8eb61d0e043dca>
		</items>
	</hash_050024c4ae4deda2771ca7c8a3073b918fa177>
	<hash_060024e9c43831e54eca8069317a2ce8c6f751>
		<name>Normal</name>
		<gprint_text>%8.2lf %s</gprint_text>
	</hash_060024e9c43831e54eca8069317a2ce8c6f751>
	<hash_06002419414480d6897c8731c7dc6c5310653e>
		<name>Exact Numbers</name>
		<gprint_text>%8.0lf</gprint_text>
	</hash_06002419414480d6897c8731c7dc6c5310653e>
</cacti>";
    
    return $xml;
}

?>