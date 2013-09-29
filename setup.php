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

function plugin_mURLin_install()
{
    api_plugin_register_hook('mURLin', 'top_header_tabs','mURLin_show_tab', 'setup.php');
    api_plugin_register_hook('mURLin', 'top_graph_header_tabs', 'mURLin_show_tab', 'setup.php');
    api_plugin_register_hook('mURLin', 'draw_navigation_text', 'mURLin_draw_navigation_text', 'setup.php');
    
    api_plugin_register_realm('mURLin', 'mURLin.php,url_edit.php', 'Edit URL to Host Mappings', 1);
	
    mURLin_setup_tables();
    import_xml_data(mURLin_returnXML(), true);
}

function plugin_mURLin_uninstall()
{
    // nothing to do
}

function plugin_mURLin_check_config()
{
    plugin_mURLin_CheckUpgrade();
    return true;
}

function plugin_mURLin_upgrade() 
{
    // Check if we need to upgrade
    plugin_mURLin_CheckUpgrade();
    return false;
}

function plugin_mURLin_CheckUpgrade()
{
    $installed_version = GetInstalledVersion(); // As reported by DB
    $new_version = GetNewVersion();             // As reported by install files
    
    if ($installed_version != $new_version)
    {
        // We need to do install
        mURLin_setup_tables();
        import_xml_data(mURLin_returnXML(), true);
        
        db_execute("UPDATE plugin_config SET version='$new_version' WHERE directory='mURLin'");
    }
}

function GetInstalledVersion()
{
    return db_fetch_cell("SELECT directory FROM plugin_config WHERE directory='mURLin'");
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
                        'version' 	=> '0.1.6',
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
                print '<a href="' . $config['url_path'] . 'plugins/mURLin/mURLin.php"><img src="' . $config['url_path'] . 'plugins/mURLin/images/tab_mURLin_down.png" align="absmiddle" border="0"></a>';
            else
                print '<a href="' . $config['url_path'] . 'plugins/mURLin/mURLin.php"><img src="' . $config['url_path'] . 'plugins/mURLin/images/tab_mURLin.png" align="absmiddle" border="0"></a>';
        }
        
        
}

function mURLin_setup_tables()
{
    // Create database tables
    $data = array();
    $data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'auto_increment' => true);
    $data['columns'][] = array('name' => 'host_id', 'type' => 'int(11)');
    $data['columns'][] = array('name' => 'url', 'type' => 'VARCHAR(256)');
    $data['columns'][] = array('name' => 'text_match', 'type' => 'VARCHAR(1024)'); 
    $data['columns'][] = array('name' => 'timeout', 'type' => 'int(3)'); 
    $data['columns'][] = array('name' => 'proxyserver', 'type' => 'int(1)');
    $data['columns'][] = array('name' => 'proxyaddress', 'type' => 'VARCHAR(256)');
    
    $data['primary'] = 'id';
    
    $data['type'] = 'MyISAM';
    $data['comment'] = 'Table of URL to Host Mappings';
    api_plugin_db_table_create ('mURLin', 'plugin_mURLin_index', $data);
   
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