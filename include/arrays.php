<?php
/*******************************************************************************

	Original Author .... James Payne
	Current Author ..... Mark Brugnoli-Vinten
	Contact ............ netniv@hotmail.com

	Program ............ Cacti URL Monitoring Plugin
	Purpose ............ Creates URL Monitoring Structure
	Copyright .......... 2004-2019

*******************************************************************************/

require_once(__DIR__ . '/constants.php');

global $m_url_actions;

$m_url_actions = array(
	MURLIN_URL_DELETE => __('Delete','mURLin'),
	MURLIN_URL_DUPLICATE => __('Duplicate','mURLin'),
);

$m_url_edit_fields = array(
	'mURLin_host_header' => array(
		'friendly_name' => __('Host Detail', 'mURLin'),
		'method' => 'spacer',
	),
	'id' => array(
		'method' => 'hidden',
		'value' => '|arg1:id|',
	),
	'host_id' => array(
		'method' => 'drop_callback',
		'friendly_name' => __('Device'),
		'description' => __('Choose the Device that this Data Source belongs to.'),
		'none_value' => __('None'),
		'sql' => 'SELECT id, description as name FROM host ORDER by name',
		'action' => 'ajax_hosts_noany',
		'value' => '|arg1:hostname|',
		'id' => '|arg1:host_id|',
	),
	'mURLin_url_header' => array(
		'friendly_name' => __('URL Details', 'mURLin'),
		'method' => 'spacer',
	),
	'url' => array(
		'friendly_name' => __('URL (Website Address)', 'mURLin'),
		'description' => __('The address of the webpage to monitor. The address must be preceeded by the protocol, http or https and if required must include the port number.'),
		'method' => 'textbox',
		'value' => '|arg1:url|',
		'max_length' => '60',
	),
	'text_match' => array(
		'friendly_name' => __('Text to match', 'mURLin'),
		'description' => __('The text within a webpage to check for each time the scan is run. The match is done using the php function preg_match. Example: "/Welcome to the site/" <strong>Note that the expression should be enclosed in / as above.</strong>, see <a href="http://php.net/manual/en/function.preg-match.php" target="_blank">this regex tutorial</a> for more examples.', 'mURLin'),
		'method' => 'textarea',
		'value' => '|arg1:text_match|',
		'textarea_rows' => '10',
		'textarea_cols' => '50',
	),
	'timeout' => array(
		'friendly_name' => __('Timeout', 'mURLin'),
		'description' => __('Timeout value in seconds. This is the number of seconds to wait for the website to respond before declaring the site is unavailable. (Between 1 and 99 seconds)', 'mURLin'),
		'method' => 'textbox',
		'max_length' => '5',
		'value' => '|arg1:timeout|',
		'default' => '5',
	),
	'mURLin_proxy_header' => array(
		'friendly_name' => __('Proxy Details', 'mURLin'),
		'method' => 'spacer',
	),
	'proxyserver' => array(
		'friendly_name' => __('Use Proxy Server', 'mURLin'),
		'description' => __('Select if you require a proxy server to access this URL.', 'mURLin'),
		'method' => 'checkbox',
		'value' => '|arg1:proxyserver|',
		'default' => '',
		'form_id' => false,
	),
	'proxyaddress' => array(
		'friendly_name' => __('Proxy Server Address', 'mURLin'),
		'description' => __('Only applies if selected above. Proxy server address. Example: http://proxy.localnet:3128', 'mURLin'),
		'method' => 'textbox',
		'max_length' => '50',
		'value' => '|arg1:proxyaddress|',
	),
	'proxyusername' => array(
		'friendly_name' => __('Proxy Server Username', 'mURLin'),
		'description' => __('Only applies if selected above. Proxy server username (If the proxy requires authentication). Leave the server details blank if the proxy server requires no authentication.', 'mURLin'),
		'method' => 'textbox',
		'max_length' => '50',
		'value' => '|arg1:proxyusername|',
	),
	'proxypassword' => array(
		'friendly_name' => __('Proxy Server Password', 'mURLin'),
		'description' => __('Only applies if selected above. Proxy server password (If the proxy requires authentication). Leave the server details blank if the proxy server requires no authentication.', 'mURLin'),
		'method' => 'textbox_password',
		'value' => '|arg1:proxypassword|',
		'default' => '',
		'max_length' => '50',
	),
);
