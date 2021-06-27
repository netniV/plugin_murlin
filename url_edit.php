<?php

/*******************************************************************************

	Author ......... James Payne
	Contact ........ jamoflaw@gmail.com
	Home Site ...... http://withjames.co.uk
	Program ........ Cacti URL Monitoring Plugin
	Purpose ........ Creates URL Monitoring Structure

*******************************************************************************/

chdir('../../');
include_once('./include/auth.php');
include_once(__DIR__ . '/include/functions.php');
include_once($config['base_path'] . '/include/config.php');
include_once($config['base_path'] . '/plugins/mURLin/include/arrays.php');

set_default_action();

switch (get_request_var("action")) {
	case 'save':	// save the data from the form
		SaveHost();
		break;

	case 'ajax_hosts_noany':
		$sql_where = '';
		if (get_request_var('site_id') > 0) {
			$sql_where = 'site_id = ' . get_request_var('site_id');
		}

		get_allowed_ajax_hosts(false, 'applyFilter', $sql_where);
		break;

	default:
		// Show Cacti top
		top_header();
		EditHost();

		// Show cacti bottom
		bottom_footer();
		break;
}

function SaveHost() {
	/* ================= input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('host_id');
	get_filter_request_var('timeout');
	/* ==================================================== */

	if (isset_request_var('id')) {
		$save['id'] = get_request_var('id');
	} else {
		$save['id'] = '';
	}

	$fields = array(
		'host_id','url','text_match','timeout',
		'proxyaddress','proxyusername','proxypassword'
	);

	foreach ($fields as $field) {
		$save[$field] = get_request_var($field);
	}

	// Validate the input
	$save['proxyserver'] = isset_request_var('proxyserver') ? 1 : 0;

	$id = sql_save($save, 'plugin_mURLin_index');

	if (is_error_message()) {
		header('Location: url_edit.php?header=false&action=edit&id=' . (empty($id) ? get_request_var('id') : $id));
		exit;
	}

	header('Location: mURLin.php?header=false');
	exit;
}

function EditHost()
{
	global $m_url_edit_fields;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	$url = array();
	if (!isempty_request_var('id')) {
		$sql = 'SELECT i.*, h.description as hostname, h.hostname as dns
			FROM plugin_mURLin_index i
			LEFT JOIN host h on h.id = i.host_id
			WHERE i.id=?';
		$url = db_fetch_row_prepared($sql, array(get_request_var('id')));
		$header_label = __('Url: [edit: %s - %s]', $url['id'], $url['url'], 'mURLin');
	} else {
		$header_label = __('Url: [new]', 'mURLin');
	}

	form_start('url_edit.php', 'chk');

	html_start_box($header_label, '100%', '', '3', 'center', '');

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true, 'form_name' => 'chk'),
			'fields' => inject_form_variables($m_url_edit_fields, $url)
		)
	);

	html_end_box();

	mURLin_includejavascript("./plugins/mURLin/functions.js");

	form_save_button('url_edit.php');
}

