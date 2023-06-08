<?php
/*******************************************************************************

	Original Author .... James Payne
	Current Author ..... Mark Brugnoli-Vinten
	Contact ............ netniv@hotmail.com

	Program ............ Cacti URL Monitoring Plugin
	Purpose ............ Creates URL Monitoring Structure
	Copyright .......... 2004-2023

*******************************************************************************/

//chdir('../../');
include_once(__DIR__ . "/../../lib/import.php");
include_once(__DIR__ . "/../../lib/utility.php");
include_once(__DIR__ . "/include/functions.php");

// Perform upgrade if needed
plugin_mURLin_upgrade();

function plugin_mURLin_install()
{
	plugin_mURLin_check_hooks();
	mURLin_setup_tables();
	mURLin_setup_templates();
}
function plugin_mURLin_uninstall()
{
	//The following occurs automatically via api_plugin_db_changes_remove()
	//api_plugin_db_table_remove ('mURLin', 'plugin_mURLin_index');
	//api_plugin_db_table_remove ('mURLin', 'plugin_mURLin_cache');
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
	$installed_version = mURLin_installed_version();	// As reported by DB
	$new_version = mURLin_current_version();		// As reported by install files

	if ($installed_version != $new_version)
	{
		// We need to do install
		mURLin_setup_tables();
		mURLin_setup_templates();

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
	// Remove all entries from the mURLin Poller cache as the poller cycle is complete
	cacti_log("mURLin - INFO: Reached Poller Bottom, clearing cache");
	db_execute("TRUNCATE TABLE plugin_mURLin_cache");
}

function mURLin_installed_version()
{
	return db_fetch_cell("SELECT version FROM plugin_config WHERE directory='mURLin'");
}

function mURLin_current_version()
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
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/mURLin/INFO', true);
	return $info['info'];
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
	}

	// Create mURLin Host Table
	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'host_id', 'type' => 'int(11)');
	$data['columns'][] = array('name' => 'url', 'type' => 'varchar(2048)');
	$data['columns'][] = array('name' => 'text_match', 'type' => 'varchar(2048)');
	$data['columns'][] = array('name' => 'timeout', 'type' => 'int(3)');
	$data['columns'][] = array('name' => 'proxyserver', 'type' => 'int(1)');
	$data['columns'][] = array('name' => 'proxyaddress', 'type' => 'varchar(256)');
	$data['columns'][] = array('name' => 'proxyusername', 'type' => 'varchar(256)');
	$data['columns'][] = array('name' => 'proxypassword', 'type' => 'varchar(256)');

	$data['primary'] = 'id';

	$data['type'] = 'MyISAM';
	$data['comment'] = 'Table of URL to Host Mappings';
	api_plugin_db_table_create ('mURLin', 'plugin_mURLin_index', $data);

	if (db_table_exists('plugin_mURLin_cache') && mURLin_installed_version() == '0.2.0')
	{
		// There was a db regression bug here we need to drop and recreate the cache table...
		// The table will be recreated by the next step
		db_execute("DROP TABLE plugin_mURLin_cache");

	}

	// Cache Cache Table
	$data = array();
	$data['columns'] = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false);
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

	$data['primary'] = 'id';
	$data['type'] = 'MyISAM';
	$data['comment'] = 'mURLin Cache Table';

	api_plugin_db_table_create ('mURLin', 'plugin_mURLin_cache', $data);
}

function mURLin_setup_templates() {
	$profile_id = db_fetch_cell('SELECT id FROM data_source_profiles ORDER BY `default` DESC LIMIT 1');
	$returnXML = mURLin_returnXML();
	import_xml_data($returnXML, true, $profile_id);
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
	$xml = file_get_contents(__DIR__ . '/xml/template.xml');
	return $xml;
}

