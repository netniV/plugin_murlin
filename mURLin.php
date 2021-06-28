<?php

/*******************************************************************************

	Original Author .... James Payne
	Current Author ..... Mark Brugnoli-Vinten
	Contact ............ netniv@hotmail.com

	Program ............ Cacti URL Monitoring Plugin
	Purpose ............ Creates URL Monitoring Structure
	Copyright .......... 2004-2019

*******************************************************************************/

chdir('../../');
include_once("./include/auth.php");
include_once($config['base_path'] . "/include/config.php");
include_once($config['base_path'] . "/lib/functions.php");
include_once($config['base_path'] . "/lib/data_query.php");
include_once($config['base_path'] . "/plugins/mURLin/include/constants.php");
include_once($config['base_path'] . "/plugins/mURLin/include/arrays.php");
include_once($config['base_path'] . "/plugins/mURLin/include/functions.php");

set_default_action();

switch (get_request_var('action')) {
	case 'actions':
		plugin_mURLin_actions();
		break;

	case 'duplicate':
		// Duplicate a report
		$id = $_POST['urlselect'];

				foreach ($id as $i)
				{
					input_validate_input_number($i);

				}
				// Redirect
				header( "Location: mURLin.php" );
				break;

	default:
		// Show Cacti top
		top_header();
		mURLin_ShowURLs();
		// Show cacti bottom
		bottom_footer();
		break;
}

function plugin_mURLin_actions() {
	// Delete the Host Mapping
	if (isset_request_var('selected_items')) {
		$selected_items = sanitize_unserialize_selected_items(get_nfilter_request_var('selected_items'));

		if ($selected_items != false) {
			switch(get_nfilter_request_var('drp_action')) {
				case MURLIN_URL_DELETE:
					for ($i=0; $i<count($selected_items); $i++) {
						// What host is mapped to $i
						$sql = "SELECT host_id FROM plugin_mURLin_index WHERE id = ?";
						$hostid = db_fetch_cell_prepared($sql,array($selected_items[$i]));

						$sql = "DELETE FROM plugin_mURLin_index WHERE id = ?";
						db_execute_prepared($sql,array($selected_items[$i]));

						// ReIndex the Data Query
						$sql = "SELECT id FROM snmp_query WHERE name='mURLin - URL Agent'";
						$snmpid = db_fetch_cell($sql);

						data_query_update_host_cache_from_buffer($hostid, $snmpid, $tmp);
					}
					break;

				case MURLIN_URL_DUPLICATE:
					for ($i=0; $i<count($selected_items); $i++) {
						$sql = "SELECT * FROM plugin_mURLin_index WHERE id = ?";

						$row = db_fetch_row_prepared($sql, $selected_items[$i]);

						$host_id = $row['host_id'];
						$url = $row['url'];
						$text_match = $row['text_match'];
						$timeout = $row['timeout'];

						// Duplicate this row
						$sql = "INSERT INTO plugin_mURLin_index
							(host_id, url, text_match, timeout)
							VALUES (?, ?, ?, ?)";
						db_execute_prepared($sql,array($host_id, $url, $text_match, $timeout));
					}
					break;
			}

			// Redirect
			header( "Location: mURLin.php" );
		}
	}

	/* loop through each of the devices selected on the previous page and get more info about them */
	foreach ($_POST as $var => $val) {
		if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$sql = "
				SELECT
					m.id as id, m.host_id as host_id, m.url as url,
					m.timeout as timeout, m.text_match as text_match,
					h.description as hostname, h.hostname as dns
				FROM plugin_mURLin_index m
				INNER JOIN host h
				ON m.host_id = h.id
				WHERE m.id = ?";

			$row = db_fetch_row_prepared($sql,array($i));

			if (cacti_sizeof($row)) {
				$host = $row['hostname'];
				$url = $row['url'];

				$url_list .= "<li>$host ---> <strong>$url</strong></li>";
				$url_array[] = $matches[1];
			}
		}
	}

	top_header();

	form_start('mURLin.php');

	if (get_nfilter_request_var('drp_action') > 0) {
		html_start_box($rc_device_actions{get_nfilter_request_var('drp_action')}, '60%', '', '3', 'center', '');
	} else {
		html_start_box('', '60%', '', '3', 'center', '');
	}

	if (sizeof($url_array)) {
		switch (get_nfilter_request_var('drp_action')) {
			case MURLIN_URL_DELETE:
				print "<tr>
					<td colspan='2' class='textArea'>
						<p>" . __('Click \'Continue\' to Delete the following url(s).', 'mURLin') . "</p>
						<p><ul>$url_list</ul></p>
					</td>
				</tr>";
				$save_html = "<input type='button' value='" . __esc('Cancel', 'mURLin') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'mURLin') . "' title='" . __esc('Delete URL(s)', 'mURLin') . "'>";
				break;

			case MURLIN_URL_ENABLE:
				print "<tr>
					<td colspan='2' class='textArea'>
						<p>" . __('Click \'Continue\' to Enable the following URL(s).', 'mURLin') . "</p>
						<p><ul>$url_list</ul></p>
					</td>
				</tr>";
				$save_html = "<input type='button' value='" . __esc('Cancel', 'mURLin') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'mURLin') . "' title='" . __esc('Enable URL(s)', 'mURLin') . "'>";
				break;

			case MURLIN_URL_DISABLE:
				print "<tr>
					<td colspan='2' class='textArea'>
						<p>" . __('Click \'Continue\' to Disable the following URL(s).', 'mURLin') . "</p>
						<p><ul>$url_list</ul></p>
					</td>
				</tr>";
				$save_html = "<input type='button' value='" . __esc('Cancel', 'mURLin') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'mURLin') . "' title='" . __esc('Disable URL(s)', 'mURLin') . "'>";
				break;
		}
	} else {
		print "<tr><td class='even'><span class='textError'>" . __('You must select at least URL.', 'mURLin') . "</span></td></tr>\n";

		$save_html = "<input type='button' value='" . __esc('Return', 'mURLin') . "' onClick='cactiReturnTo()'>";
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($url_array) ? serialize($url_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

function mURLin_url_validate_vars() {
	/* ================= input validation and session storage ================= */
	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'hostname',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			),
	);

	validate_store_request_vars($filters, 'sess_mURLin_urls');
	/* ================= input validation ================= */
}

function mURLin_ShowURLs()
{
	global $item_rows, $m_url_actions;

	mURLin_includejavascript("./plugins/mURLin/functions.js");

	mURLin_url_validate_vars();

	if (get_request_var('rows') == -1) {
		$num_rows = read_config_option('num_rows_table');
	} else {
		$num_rows = get_request_var('rows');
	}

	$page = 1;
	if (isset_request_var('page')) {
		$page = get_request_var('page');
	}

	load_current_session_value('page', 'sess_murlin_urls_current_page', '1');

	// Show the mapped URLs
	$sqlwhere = '';

	$total_rows = db_fetch_cell('SELECT COUNT(*) FROM plugin_mURLin_index '. $sqlwhere);

	$sort_column = get_request_var('sort_column');
	$sort_direction = get_request_var('sort_direction');
	$sort_limit = $num_rows*($page-1);

	$sql = "SELECT i.*, h.description as hostname, h.hostname as dns, h.disabled
		FROM plugin_mURLin_index i
		LEFT JOIN host h on h.id = i.host_id
		$sqlwhere
		ORDER BY $sort_column $sort_direction
		LIMIT $sort_limit, $num_rows";

	$results = db_fetch_assoc($sql);

	?>
	<script type='type/javsacript'>

	function applyFilter() {
		strURL  = 'mURLin.php?header=false';
		strURL += '&rfilter=' + $('#filter').val();
		strURL += '&rows=' + $('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = 'mURLin.php?clear=1&header=false';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#rows, #filter').change(function() {
			applyFilter();
		});

		$('#refresh').click(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});

		$('#form_devices').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
	});

	</script>
	<?php

	html_start_box(__('URL Management', 'mURLin'), '!00%', '', '4', 'center', 'url_edit.php?action=edit');

	?>
	<tr class='even noprint'>
		<td>
			<from id='form_urls' action='mURLin.php'>
				<table class='filterTable'>
					<tr>
						<td>
							<?php print __('Filter','mURLin'); ?>
						</td>
						<td>
							<input id='filter' type'text' size='25' value'<?php print html_escape_request_var('filter');?>'>
						</td>
						<td>
							<select id='rows'>
								option value='-1'<?php print (get_request_var('rows') == '-1' ? ' selected>':'>') . __('Default');?></option>

								<?php
								if (cacti_sizeof($item_rows)) {
									foreach ($item_rows as $key => $value) {
										print "option value='$key'";
										if (get_request_var('rows') == $key) {
											print ' selected';
										}
										print '>' . htmlspecialchars($value) . '</option>' . PHP_EOL;
									}
								}
								?>
							</select>
						</td>
						<td>
							<span>
								<input type='button' id='refresh' value='<?php print __('Go','mURLin');?>' title='<?php print __esc('Set/Refresh Filters','mURLin');?>'>
								<input type='button' id='clear' value='<?php print __('Clear','mURLin');?>' title='<?php print __esc('Clear Filters','mURLin');?>'>
							</span>
						</td>
					</tr>
				</table>
			</form>
		</td>
	</tr>
	<?php

	html_end_box();

	$display_text = array(
		'hostname' => array(
			'display' => __('Hostname', 'mURLin'),
			'align' => 'left',
			'sort' => 'ASC',
			'tip' => __('Either an IP address, or hostname.  If a hostname, it must be resolvable by either DNS, or from your hosts file.', 'mURLin')
		),
		'id' => array(
			'display' => __('ID','mURLin'),
			'align' => 'right',
			'sort' => 'ASC',
			'tip' => __('The internal database ID for this URL.  Useful when debugging.', 'mURLin')
		),
		'enabled' => array(
			'display' => __('Enabled', 'mURLin'),
			'align' => 'left',
			'sort' => 'ASC',
		),
		'dns' => array(
			'display' => __('DNS', 'mURLin'),
			'align' => 'left',
			'sort' => 'ASC',
		),
		'url' => array(
			'display' => __('URL', 'mURLin'),
			'align' => 'left',
			'sort' => 'ASC',
			'tip' => __('The URL to be tested', 'mURLin')
		),
		'timeout' => array(
			'display' => __('Timeout (Seconds)', 'mURLin'),
			'align' => 'right',
			'sort' => 'ASC',
			'tip' => __('The maximum amount of time to allow before stopping', 'mURLin')
		),
		'text_match' => array(
			'display' => __('Regex Match', 'mURLin'),
			'align' => 'left',
			'sort' => 'ASC',
			'tip' => __('The regular expression to match against', 'mURLin')
		),
		'proxyserver' => array(
			'display' => __('Proxy Address', 'mURLin'),
			'align' => 'left',
			'tip' => __('The address through which to proxy this connection', 'mURLin')
		),
	);

	form_start('mURLin.php', 'chk');

	$nav = html_nav_bar('mURLin.php', MAX_DISPLAY_PAGES, get_request_var('page'), $num_rows, $total_rows, 10, 'Devices', 'page', 'main');
	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');
	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($results)) {
		foreach ($results as $row) {

			if (!empty($row['proxyaddress']))
			{
				$proxyaddress = $row['proxyaddress'];
				$proxystring = "&proxy=" . $row['proxyaddress'] . "&proxyusername=" . $row['proxyusername'] . "&proxypassword=" . $row['proxypassword'];
			}
			else
				$proxyaddress = "N/A";

			form_alternate_row('line' . $row['id'], false);

			$enabled = ($row['disabled'] != 'on' ? '<span class="deviceUp">' . __('Yes', 'mURLin') . '</span>' : '<span class="deviceDown">' . __('No', 'mURLin') . '</span>');

			$cell = '';

			if (empty($row['hostname'])) {
				$row['hostname'] = __('N/A','mURLin');
			}

			if (empty($row['dns'])) {
				$row['dns'] = __('N/A','mURLin');
				$row['dnslink'] = '';
			} else {
				$row['dnslink'] = '../../host.php?&action=edit&id=' . $row['host_id'];
			}

			if (empty($row['url'])) {
				$row['url'] = __('N/A','mURLin');
				$row['urllink'] = '';
			} else {
				$urlenc = urlencode($row['url']);
				$row['urllink'] = 'showpage.php?page=' . $urlenc;
			}

			form_selectable_cell(filter_value($row['hostname'], get_request_var('filter'), 'url_edit.php?&action=edit&id=' . $row['id']), $row['id'],'10%');
			form_selectable_cell(filter_value($row['id'], get_request_var('filter'), 'url_edit.php?&action=edit&id=' . $row['id']), $row['id'], '1%', 'text-align:right');
			form_selectable_cell($enabled, $row['id'], '5%', 'text-align:center');
			form_selectable_cell(filter_value($row['dns'], get_request_var('filter'), $row['dnslink']), $row['id'],'10%');
			form_selectable_cell(filter_value($row['url'], get_request_var('filter'), $row['urllink']), $row['id'],'10%');
			form_selectable_cell($row['timeout'], $row['id'], '5%', 'text-align:right');
			form_selectable_cell(filter_value($row['text_match'], get_request_var('filter'), 'url_edit.php?&action=edit&id=' . $row['id']), $row['id'],'10%');
			form_selectable_cell($proxyaddress, $row['id'], '5%', 'text-align:center');

			form_checkbox_cell($row['hostname'], $row['id']);
			form_end_row();
		}
	}else{
		print "<tr class='even'><td colspan='13'>" . __('No URLs Found', 'mURLin') . "</td></tr>\n";
	}

	html_end_box(false);

	draw_actions_dropdown($m_url_actions);

	form_end();
}
