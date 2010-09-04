<?php
/*
*
* @author TerraFrost < terrafrost@phpbb.com >,  jasmineaura < jasmine.aura@yahoo.com >
*
* @package acp
* @version $Id$
* @copyright (c) 2006 by TerraFrost (c) 2008 by Jasmine Hasan
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* @package acp
*/
class acp_proxy_revealer
{
	var $u_action;
	var $new_config = array();

	function main($id, $mode)
	{
		global $user;

		$user->add_lang('mods/proxy_revealer');
		$this->page_title = 'ACP_PROXY_REVEALER';

		switch($mode)
		{
			case 'internal':
			case 'external':

				$action = request_var('action', '');

				switch ($action) 
				{
					case 'whois':
						$this->popup_ip_whois();
					break;

					case 'flash':
					case 'java':
					case 'realplayer':
					case 'quicktime':
					case 'wmplayer':
						$this->popup_plugin_info($action);
					break;

					default:
						$this->display_ip_log($id, $mode);
					break;
				}

			break;

			case 'settings':
				$this->display_settings();
			break;

			case 'excludes':
				$this->display_exceptions();
			break;
		}
	}

	/**
	* Display External/Internal IP Log
	*/
	function display_ip_log($id, $mode = 'external')
	{
		global $config, $db, $user, $template;
		global $phpbb_admin_path, $phpEx;

		$this->tpl_name = 'acp_proxy_revealer';

		$form_key = 'acp_proxy_revealer';
		add_form_key($form_key);

		if ((isset($_POST['show']) || isset($_POST['order']) || isset($_POST['ip'])) && !check_form_key($form_key))
		{
			trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
		}

		// get starting position
		$start = request_var('start', 0);

		// get show amount
		if ( isset($_REQUEST['show']) )
		{
			$show = request_var('show', 0);
		}
		else
		{
			$show = $config['posts_per_page'];
		}

		// sort order
		if( isset($_REQUEST['order']) )
		{
			$sort_order = request_var('order', 'DESC');
		}
		else
		{
			$sort_order = 'DESC';
		}

		if ($mode != 'internal')
		{
			$mode = 'external';
		}

		$search_ip = '';
		$where_sql = ($mode == 'internal') ? 'WHERE method = ' . JAVA_INTERNAL : 'WHERE method <> ' . JAVA_INTERNAL;

		if( !empty($_REQUEST['ip']) )
		{
			$search_ip = request_var('ip', '');
			$where_sql.= " AND ip_address = '" . $db->sql_escape($search_ip) . "'";
		}

		$sql = 'SELECT * FROM ' . SPECULATIVE_TABLE . ' ' .
			$where_sql .
			' ORDER BY discovered ' . $db->sql_escape($sort_order);
		$result = $db->sql_query_limit($sql, $show, $start);

		$i = 0;
		while ( $row = $db->sql_fetchrow($result) )
		{
			$real_ip = $row['real_ip'];
			$real_ip_url = '<a href="' . $this->u_action . "&amp;action=whois&amp;ip=$real_ip" . '" '
				. 'title="' . sprintf($user->lang['IP_WHOIS_FOR'], $real_ip) . '" '
				. 'onclick="popup(this.href, 700, 500, \'_whois\'); return false;">' . $real_ip . '</a>';

			$ip_address = $row['ip_address'];

			switch ( $row['method'] )
			{
				case COOKIE:
					$method = $user->lang['COOKIE'];
				break;

				case FLASH:
					$method = '<a href="'
						. $this->u_action . "&amp;action=flash&amp;spoofed=$row[ip_address]&amp;real=$real_ip&amp;method=$row[method]"
						. '" title="' . sprintf($user->lang['PLUGIN_DESC'], $user->lang['FLASH']) . '" '
						. 'onclick="popup(this.href, 700, 300, \'_flash\'); return false;">' . $user->lang['FLASH'] . '</a>';
				break;

				case JAVA:
				case JAVA_INTERNAL:
					$method = '<a href="'
						. $this->u_action . "&amp;action=java&amp;spoofed=$row[ip_address]&amp;real=$real_ip&amp;method=$row[method]"
						. '" title="' . sprintf($user->lang['PLUGIN_DESC'], $user->lang['JAVA']) . '" '
						. 'onclick="popup(this.href, 700, 300, \'_java\'); return false;">' . $user->lang['JAVA'] . '</a>';
				break;

				case REALPLAYER:
					$method = '<a href="'
						. $this->u_action . "&amp;action=realplayer&amp;spoofed=$row[ip_address]&amp;real=$real_ip&amp;method=$row[method]"
						. '" title="' . sprintf($user->lang['PLUGIN_DESC'], $user->lang['REALPLAYER']) . '" '
						. 'onclick="popup(this.href, 700, 300, \'_realplayer\'); return false;">' . $user->lang['REALPLAYER'] . '</a>';
				break;

				case QUICKTIME:
					$method = '<a href="'
						. $this->u_action . "&amp;action=quicktime&amp;spoofed=$row[ip_address]&amp;real=$real_ip&amp;method=$row[method]"
						. '" title="' . sprintf($user->lang['PLUGIN_DESC'], $user->lang['QUICKTIME']) . '" '
						. 'onclick="popup(this.href, 700, 300, \'_quicktime\'); return false;">' . $user->lang['QUICKTIME'] . '</a>';
				break;

				case WMPLAYER:
					$method = '<a href="'
						. $this->u_action . "&amp;action=wmplayer&amp;spoofed=$row[ip_address]&amp;real=$real_ip&amp;method=$row[method]"
						. '" title="' . sprintf($user->lang['PLUGIN_DESC'], $user->lang['WMPLAYER']) . '" '
						. 'onclick="popup(this.href, 700, 300, \'_wmplayer\'); return false;">' . $user->lang['WMPLAYER'] . '</a>';
				break;

				case TOR_DNSEL:
					$method = $user->lang['TOR_DNSEL'];
					// Don't show a whois link since the real IP added by this method is always 0.0.0.0
					$real_ip_url = $real_ip;
				break;

				case PROXY_DNSBL:
					// Don't show a whois link since the real IP added by this method is always 0.0.0.0
					$real_ip_url = $real_ip;
					$urls = explode('<>', $row['info']);
					$count = count($urls);
					switch (true)
					{
						case $count == 1: // by default, there has to be at least one url (sorbs.net or spamhaus.org)
							$method =  '<a href="' . $urls[0] . '" title="' . $user->lang['PROXY_DNSBL_URL'] . '" '
								. 'onclick="popup(this.href, 700, 500, \'_proxy_dnsbl\'); return false;">' . $user->lang['PROXY_DNSBL'] . '</a>';
						break;

						case $count == 2: // eg. there can be up to two url's in $urls (sorbs.net and spamhaus.org).  if there is, represent the link to the second one with "(2)"
							$method =  '<a href="' . $urls[0] . '" title="' . $user->lang['PROXY_DNSBL_URL'] . '" '
								. 'onclick="popup(this.href, 700, 500, \'_proxy_dnsbl\'); return false;">' . $user->lang['PROXY_DNSBL'] . '</a>';
							$method.= '&nbsp;&nbsp;<a href="' . $urls[1] . '" title="' . $user->lang['PROXY_DNSBL_URL'] . '" '
								. 'onclick="popup(this.href, 700, 500, \'_proxy_dnsbl\'); return false;">(2)</a>';
						break;
					}
				break;

				case X_FORWARDED_FOR:
					$method = $user->lang['X_FORWARDED_FOR'];
				break;

				case XSS:
					$urls = explode('<>', $row['info']);
					$count = count($urls);
					switch (true)
					{
						case !$count:
							$method = $user->lang['XSS'];
						break;

						case $count == 1 || empty($urls[1]):
							$method =  !empty($urls[0]) ? '<a href="' . $urls[0] . '" title="' . $user->lang['XSS_URL'] . '" '
								. 'onclick="popup(this.href, 700, 500, \'_xss\'); return false;">' . $user->lang['XSS'] . '</a>' : $user->lang['XSS'];
						break;

						case $count == 2: // eg. default; there can be up to two url's in $urls.  if there is, represent the link to the second one with "(2)"
							$method =  !empty($urls[0]) ? '<a href="' . $urls[0] . '" title="' . $user->lang['XSS_URL'] . '" '
								. 'onclick="popup(this.href, 700, 500, \'_xss\'); return false;">' . $user->lang['XSS'] . '</a>' : $user->lang['XSS'];
							$method.= !empty($urls[1]) ? '&nbsp;&nbsp;<a href="' . $urls[1] . '" title="' . $user->lang['XSS_URL'] . '" '
								. 'onclick="popup(this.href, 700, 500, \'_xss\'); return false;">(2)</a>' : '';
						break;
					}
				break;
			}

			$template->assign_block_vars('speculativerow', array(
				'ROW_CLASS'		=> ( !($i % 2) ) ? "row1" : "row4",
				'SPOOFED_IP'	=> $ip_address,
				'METHOD'		=> $method,
				'REAL_IP'		=> ($mode == 'internal') ? $real_ip : $real_ip_url,
				'DATE'			=> $user->format_date($row['discovered'], "d M Y h:i a"),
			));
			$i++;
		}

		$count_sql = 'SELECT count(ip_address) AS total 
			FROM ' . SPECULATIVE_TABLE . ' ' .
			$where_sql;
		$count_result = $db->sql_query($count_sql);

		$total = $db->sql_fetchrow($count_result);
		$total_ips = $total['total'];

		$speculative_desc = ($mode == 'internal') ? 
				sprintf($user->lang['SPECULATIVE_IP_INTERNAL'],
					'<a href="' . append_sid("{$phpbb_admin_path}index.$phpEx", "i=$id&amp;mode=external") . '">', '</a>') : 
				sprintf($user->lang['SPECULATIVE_IP_EXTERNAL'],
					'<a href="' . append_sid("{$phpbb_admin_path}index.$phpEx", "i=$id&amp;mode=internal") . '">', '</a>',
					'<a href="' . append_sid("{$phpbb_admin_path}index.$phpEx", "i=$id&amp;mode=excludes") . '">', '</a>');

		$template->assign_vars(array(
			'L_SEARCH_FOR'			=> $user->lang['SEARCH_FOR'],
			'L_SUBMIT'				=> $user->lang['SUBMIT'],
			'L_SORT_BY'				=> $user->lang['SORT_BY'],
			'L_MOST_RECENTLY' 		=> $user->lang['MOST_RECENT'],
			'L_LEAST_RECENTLY' 		=> $user->lang['LEAST_RECENT'],
			'L_SHOW' 				=> $user->lang['SHOW'],
			'L_PROXY_REVEALER'		=> $user->lang['ACP_PROXY_REVEALER'],
			'L_PROXY_REVEALER_MODE' => ($mode == 'internal') ? $user->lang['ACP_PROXY_REVEALER_INTERNAL'] : $user->lang['ACP_PROXY_REVEALER_EXTERNAL'],
			'L_SPECULATIVE_IP_DESC'	=> $speculative_desc,
			'L_SPOOFED'				=> ($mode == 'internal') ? $user->lang['EXTERNAL_IP'] : $user->lang['SPOOFED_IP'],
			'L_METHOD'				=> $user->lang['METHOD_USED'],
			'L_REAL'				=> ($mode == 'internal') ? $user->lang['INTERNAL_IP'] : $user->lang['REAL_IP'],
			'L_METHOD_DESC'			=> $user->lang['METHOD_USED_EXPLAIN'],
			'L_REAL_DESC'			=> $user->lang['REAL_IP_EXPLAIN'],
			'L_DATE'				=> $user->lang['DATE'],
			'L_VIEW_LIST'			=> $user->lang['VIEW_LIST'],

			'S_SHOW'		=> $show,
			'S_ASC'			=> ( $sort_order == 'ASC' ) ? ' selected="selected"' : '',
			'S_DESC'		=> ( $sort_order == 'DESC' ) ? ' selected="selected"' : '',
			'S_SORT'		=> $user->lang['SORT'],

			'SEARCH'		=> $search_ip,
			'S_ON_PAGE'		=> on_page($total_ips, $show, $start),
			'PAGINATION'	=> generate_pagination($this->u_action . "&amp;order=$sort_order&amp;show=$show", $total_ips, $show, $start, true),

			'U_ACTION'		=> $this->u_action,
		));
	}

	/**
	* IP Whois
	*/
	function popup_ip_whois()
	{
		global $user, $template;
		global $phpbb_root_path, $phpEx;

		include($phpbb_root_path . 'includes/functions_user.' . $phpEx);

		$this->page_title = 'WHOIS';
		$this->tpl_name = 'simple_body';

		$ip = request_var('ip', '');
		$ipwhois = user_ipwhois($ip);

		$template->assign_vars(array(
			'MESSAGE_TITLE'		=> sprintf($user->lang['IP_WHOIS_FOR'], $ip),
			'MESSAGE_TEXT'		=> nl2br($ipwhois))
		);
	}

	/**
	* Show Flash/Java Extended Plugin Information
	*/
	function popup_plugin_info($action = '')
	{
		global $db, $user, $template;

		$this->page_title = 'SPECULATIVE_IP_' . strtoupper($action);
		$this->tpl_name = 'acp_proxy_revealer_plugin';

		$spoofed_ip = request_var('spoofed', '');
		$real_ip = request_var('real', '');
		$method = request_var('method', 0);

		if ($method != JAVA && $method != JAVA_INTERNAL && $method != FLASH && $method != REALPLAYER && $method != QUICKTIME && $method != WMPLAYER)
		{
			trigger_error('NO_MODE', E_USER_ERROR);
		}

		// there should only be one response.
		$sql = 'SELECT * FROM ' . SPECULATIVE_TABLE . ' 
			WHERE method = ' . $db->sql_escape($method) . " 
				AND ip_address = '" . $db->sql_escape($spoofed_ip) . "' 
				AND real_ip = '" . $db->sql_escape($real_ip) . "'";
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);

		$info = explode('<>', $row['info']);

		if ($method == JAVA || $method == JAVA_INTERNAL)
		{
			$glue = '';
			if (!empty($info[1]))
			{
				$glue = '<br />';
				$plugin_version = $info[1];
			}
			$plugin_version = (!empty($info[2])) ? $plugin_version . $glue . $info[2] : $plugin_version;
		}
		else
		{
			$plugin_version = $info[1];
		}

		$template->assign_vars(array(
			'L_PROXY_REVEALER'	=> $user->lang['ACP_PROXY_REVEALER'],
			'L_PLUGIN_DESC'		=> sprintf($user->lang['PLUGIN_DESC'], $user->lang[strtoupper($action)]),
			'L_USER_AGENT' 		=> $user->lang['USER_AGENT'],
			'L_PLUGIN_VERSION'	=> sprintf($user->lang['PLUGIN_VERSION'], $user->lang[strtoupper($action)]),

			'PLUGIN_VERSION'	=> $plugin_version,
			'USER_AGENT'		=> $info[0],
		));
	}

	/**
	* Display Settings Page
	*/
	function display_settings()
	{
		global $config, $db, $user, $template;

		// We add 'acp/ban' language file because we reuse the ban-length options' names in our local function ipbanlength_select()
		// and we also reuse 'BAN_LENGTH', 'BAN_REASON' and 'BAN_GIVE_REASON' in our $display_vars below
		$user->add_lang('acp/ban');

		$this->tpl_name = 'acp_proxy_revealer_settings';

		$form_key = 'acp_proxy_revealer_settings';
		add_form_key($form_key);

		$submit = (isset($_POST['submit'])) ? true : false;

		$display_vars = array(
			'title'	=> 'ACP_PROXY_REVEALER_SETTINGS',
			'vars'	=> array(
				'legend1'				=> 'ACP_PROXY_REVEALER_SETTINGS',
				'pro_mod_on'			=> array('lang' => 'PRO_MOD_ON',		'validate' => 'bool',			'type' => 'radio:yes_no',		'explain' => true),
				'ip_block'				=> array('lang' => 'IP_MASK_BLOCK',		'validate' => 'int',			'type' => 'custom',			'method' => 'ip_block_select',	'explain' => true),
				'ip_scan_defer'			=> array('lang' => 'IP_SCAN_DEFER',		'validate' => 'int',			'type' => 'custom',			'method' => 'ip_block_select',	'explain' => true),
				'ip_cookie_age'			=> array('lang' => 'IP_COOKIE_AGE',		'validate' => 'int',			'type' => 'text:3:4',		'explain' => true,	'append' => ' ' . $user->lang['HOURS']),
				'ip_flash_on'			=> array('lang' => 'IP_FLASH_ON',		'validate' => 'bool',			'type' => 'radio:yes_no',	'explain' => true),
				'ip_flash_port'			=> array('lang' => 'IP_FLASH_PORT',		'validate' => 'int:1025:65535',	'type' => 'text:5:5',		'explain' => true),
				'require_javascript'	=> array('lang' => 'IP_REQUIRE_JS',		'validate' => 'bool',			'type' => 'radio:yes_no',	'explain' => true),
				'ip_ban'				=> array('lang' => 'IP_MASK_BAN',		'validate' => 'bool',			'type' => 'radio:yes_no',	'explain' => true),
				'ip_ban_length'			=> array('lang' => 'BAN_LENGTH',		'validate' => 'int',			'type' => 'custom',			'method' => 'ipbanlength_select',	'explain' => false),
				'ip_ban_length_other'	=> array('lang' => 'BAN_LENGTH',		'validate' => 'string',			'type' => false,			'method' => false,	'explain' => false),
				'ip_ban_reason'			=> array('lang' => 'BAN_REASON',		'validate' => 'string',			'type' => 'text:40:255',	'explain' => false),
				'ip_ban_give_reason'	=> array('lang' => 'BAN_GIVE_REASON',	'validate' => 'string',			'type' => 'text:40:255',	'explain' => false),
				'ip_prune'				=> array('lang' => 'IP_MASK_PRUNE',		'validate' => 'int',			'type' => 'text:3:4',		'explain' => true,	'append' => ' ' . $user->lang['DAYS']),
			)
		);

		$this->new_config = $config;

		if ($submit)
		{
			$cfg_array = (isset($_REQUEST['config'])) ? utf8_normalize_nfc(request_var('config', array('' => ''), true)) : $this->new_config;
			$error = array();

			validate_config_vars($display_vars['vars'], $cfg_array, $error);

			if (!check_form_key($form_key))
			{
				$error[] = $user->lang['FORM_INVALID'];
			}

			if (sizeof($error) == 0)
			{
				foreach ($display_vars['vars'] as $config_name => $null)
				{
					if (!isset($cfg_array[$config_name]) || strpos($config_name, 'legend') !== false)
					{
						continue;
					}

					$this->new_config[$config_name] = $cfg_array[$config_name];
					set_config($config_name, $cfg_array[$config_name]);
				}
				add_log('admin', 'LOG_PROXY_REVEALER_SETTINGS');
				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}
			else
			{
				$template->assign_vars(array(
					'S_ERROR' => (sizeof($error)) ? true : false,
					'ERROR_MSG' => implode('<br />', $error),
				));
			}
		}

		$template->assign_vars(array(
			'L_PROXY_REVEALER'				=> $user->lang['ACP_PROXY_REVEALER'],
			'L_PROXY_REVEALER_SETTINGS'		=> $user->lang['ACP_PROXY_REVEALER_SETTINGS'],
			'L_PROXY_REVEALER_DESC'			=> $user->lang['PROXY_REVEALER_EXPLAIN'],
			'S_ACP_PROXY_REVEALER_SETTINGS'	=> true,
			'U_ACTION'						=> $this->u_action,
		));

		//
		// Output relevant page
		//
		foreach ($display_vars['vars'] as $config_key => $vars)
		{
			if (!is_array($vars) && strpos($config_key, 'legend') === false)
			{
				continue;
			}

			if (strpos($config_key, 'legend') !== false)
			{
				$template->assign_block_vars('options', array(
					'S_LEGEND'		=> true,
					'LEGEND'		=> (isset($user->lang[$vars])) ? $user->lang[$vars] : $vars)
				);

				continue;
			}

			$type = explode(':', $vars['type']);

			$l_explain = '';
			if ($vars['explain'] && isset($vars['lang_explain']))
			{
				$l_explain = (isset($user->lang[$vars['lang_explain']])) ? $user->lang[$vars['lang_explain']] : $vars['lang_explain'];
			}
			else if ($vars['explain'])
			{
				$l_explain = (isset($user->lang[$vars['lang'] . '_EXPLAIN'])) ? $user->lang[$vars['lang'] . '_EXPLAIN'] : '';
			}
			
			$content = build_cfg_template($type, $config_key, $this->new_config, $config_key, $vars);

			if (empty($content))
			{
				continue;
			}

			$template->assign_block_vars('options', array(
				'KEY'			=> $config_key,
				'TITLE'			=> (isset($user->lang[$vars['lang']])) ? $user->lang[$vars['lang']] : $vars['lang'],
				'S_EXPLAIN'		=> $vars['explain'],
				'TITLE_EXPLAIN'	=> $l_explain,
				'CONTENT'		=> $content,
				)
			);
		
			unset($display_vars['vars'][$config_key]);
		}
	}

	/**
	* Display Exceptions List
	*/
	function display_exceptions()
	{
		global $db, $user, $cache, $template;
		global $phpbb_root_path, $phpEx;

		$this->tpl_name = 'acp_proxy_revealer_excludes';

		$form_key = 'acp_proxy_revealer_excludes';
		add_form_key($form_key);

		$add_excludes_submit	= (isset($_POST['add_excludes_submit'])) ? true : false;
		$del_excludes_submit	= (isset($_POST['del_excludes_submit'])) ? true : false;
		$add_users_submit		= (isset($_POST['add_users_submit'])) ? true : false;
		$del_users_submit		= (isset($_POST['del_users_submit'])) ? true : false;

		if (($add_excludes_submit || $del_excludes_submit || $add_users_submit || $del_users_submit) && !check_form_key($form_key))
		{
			trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
		}

		//
		// Adapted from acp_ban.php and user_ban() in functions_user.php
		//
		if ($add_excludes_submit)
		{
			$add_ip = request_var('add_ip', '');
			$ip_list = (!is_array($add_ip)) ? array_unique(explode("\n", $add_ip)) : $add_ip;
			$ip_list_log = implode(', ', $ip_list);

			$iplist_ary = array();

			foreach ($ip_list as $ip_item)
			{
				if (preg_match('#^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})[ ]*\-[ ]*([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$#', trim($ip_item), $ip_range_explode))
				{
					// This is an IP range
					// Don't ask about all this, just don't ask ... !
					$ip_1_counter = $ip_range_explode[1];
					$ip_1_end = $ip_range_explode[5];

					while ( $ip_1_counter <= $ip_1_end )
					{
						$ip_2_counter = ( $ip_1_counter == $ip_range_explode[1] ) ? $ip_range_explode[2] : 0;
						$ip_2_end = ( $ip_1_counter < $ip_1_end ) ? 254 : $ip_range_explode[6];

						if ( $ip_2_counter == 0 && $ip_2_end == 254 )
						{
							$ip_2_counter = 256;
							$ip_2_fragment = 256;

							$iplist_ary[] = "$ip_1_counter.*";
						}

						while ( $ip_2_counter <= $ip_2_end )
						{
							$ip_3_counter = ( $ip_2_counter == $ip_range_explode[2] && $ip_1_counter == $ip_range_explode[1] ) ? $ip_range_explode[3] : 0;
							$ip_3_end = ( $ip_2_counter < $ip_2_end || $ip_1_counter < $ip_1_end ) ? 254 : $ip_range_explode[7];

							if ( $ip_3_counter == 0 && $ip_3_end == 254 )
							{
								$ip_3_counter = 256;
								$ip_3_fragment = 256;

								$iplist_ary[] = "$ip_1_counter.$ip_2_counter.*";
							}

							while ( $ip_3_counter <= $ip_3_end )
							{
								$ip_4_counter = ( $ip_3_counter == $ip_range_explode[3] && $ip_2_counter == $ip_range_explode[2] && $ip_1_counter == $ip_range_explode[1] ) ? $ip_range_explode[4] : 0;
								$ip_4_end = ( $ip_3_counter < $ip_3_end || $ip_2_counter < $ip_2_end ) ? 254 : $ip_range_explode[8];

								if ( $ip_4_counter == 0 && $ip_4_end == 254 )
								{
									$ip_4_counter = 256;
									$ip_4_fragment = 256;

									$iplist_ary[] = "$ip_1_counter.$ip_2_counter.$ip_3_counter.*";
								}

								while ( $ip_4_counter <= $ip_4_end )
								{
									$iplist_ary[] = "$ip_1_counter.$ip_2_counter.$ip_3_counter.$ip_4_counter";
									$ip_4_counter++;
								}
								$ip_3_counter++;
							}
							$ip_2_counter++;
						}
						$ip_1_counter++;
					}
				}
				else if (preg_match('#^([0-9]{1,3})\.([0-9\*]{1,3})\.([0-9\*]{1,3})\.([0-9\*]{1,3})$#', trim($ip_item)) || preg_match('#^[a-f0-9:]+\*?$#i', trim($ip_item)))
				{
					// Normal IP address
					$iplist_ary[] = trim($ip_item);
				}
				else if (preg_match('#^\*$#', trim($ip_item)))
				{
					// Exclude all IPs
					$iplist_ary[] = '*';
				}
				else if (preg_match('#^([\w\-_]\.?){2,}$#is', trim($ip_item)))
				{
					// hostname
					$ip_ary = gethostbynamel(trim($ip_item));

					if (!empty($ip_ary))
					{
						foreach ($ip_ary as $ip)
						{
							if ($ip)
							{
								if (strlen($ip) > 40)
								{
									continue;
								}

								$iplist_ary[] = $ip;
							}
						}
					}
				}
				else
				{
					trigger_error('NO_IPS_DEFINED');
				}
			}

			// Fetch currently set excludes. Prevent duplicate excludes.
			$sql = 'SELECT ip_address 
				FROM ' . SPECULATIVE_EXCLUDE_TABLE;
			$result = $db->sql_query($sql);

			if ($row = $db->sql_fetchrow($result))
			{
				$iplist_ary_tmp = array();
				do
				{
					$iplist_ary_tmp[] = $row['ip_address'];
				}
				while ($row = $db->sql_fetchrow($result));

				$iplist_ary = array_unique(array_diff($iplist_ary, $iplist_ary_tmp));
				unset($iplist_ary_tmp);
			}
			$db->sql_freeresult($result);

			// We have some IPs to exclude
			if (sizeof($iplist_ary))
			{
				$sql_ary = array();

				foreach ($iplist_ary as $ip_entry)
				{
					$sql_ary[] = array('ip_address' => $ip_entry,);
				}

				$db->sql_multi_insert(SPECULATIVE_EXCLUDE_TABLE, $sql_ary);
			}

			// Add to moderator and admin log
			add_log('admin', 'LOG_PROXY_REVEALER_EXCLUDES_ADD', $ip_list_log);
			add_log('mod', 0, 0, 'LOG_PROXY_REVEALER_EXCLUDES_ADD', $ip_list_log);

			$cache->destroy('sql', SPECULATIVE_EXCLUDE_TABLE);

			trigger_error($user->lang['EXCLUDE_UPDATE_SUCCESSFUL'] . adm_back_link($this->u_action));
		}

		//
		// Adapted from acp_ban.php and user_unban() in functions_user.php
		//
		if ($del_excludes_submit)
		{
			$remove_ip = request_var('remove_ip', array(''));

			if (!is_array($remove_ip))
			{
				$remove_ip = array($remove_ip);
			}

			if (sizeof($remove_ip))
			{
				// Grab details of excludes for logging information later
				$sql = 'SELECT ip_address AS remove_info
					FROM ' . SPECULATIVE_EXCLUDE_TABLE . '
					WHERE ' . $db->sql_in_set('ip_address', $remove_ip);
				$result = $db->sql_query($sql);

				$l_remove_list = '';
				while ($row = $db->sql_fetchrow($result))
				{
					$l_remove_list .= (($l_remove_list != '') ? ', ' : '') . $row['remove_info'];
				}
				$db->sql_freeresult($result);

				$sql = 'DELETE FROM ' . SPECULATIVE_EXCLUDE_TABLE . '
					WHERE ' . $db->sql_in_set('ip_address', $remove_ip);
				$db->sql_query($sql);

				// Add to moderator and admin log
				add_log('admin', 'LOG_PROXY_REVEALER_EXCLUDES_DEL', $l_remove_list);
				add_log('mod', 0, 0, 'LOG_PROXY_REVEALER_EXCLUDES_DEL', $l_remove_list);

				$cache->destroy('sql', SPECULATIVE_EXCLUDE_TABLE);

				trigger_error($user->lang['EXCLUDE_UPDATE_SUCCESSFUL'] . adm_back_link($this->u_action));
			}
		}

		//
		// Adapted from acp_ban.php and user_ban() in functions_user.php
		//
		if ($add_users_submit)
		{
			$add_user = request_var('add_user', '');
			$user_list = (!is_array($add_user)) ? array_unique(explode("\n", $add_user)) : $add_user;
			$user_list_log = implode(', ', $user_list);

			$userlist_ary = array();

			// Select the relevant user_ids.
			$sql_usernames = array();

			foreach ($user_list as $username)
			{
				$username = trim($username);
				if ($username != '')
				{
					$clean_name = utf8_clean_string($username);
					$sql_usernames[] = $clean_name;
				}
			}

			// Make sure we have been given someone to exclude
			if (!sizeof($sql_usernames))
			{
				trigger_error('NO_USER_SPECIFIED');
			}

			$sql = 'SELECT user_id
				FROM ' . USERS_TABLE . '
				WHERE ' . $db->sql_in_set('username_clean', $sql_usernames);
			$result = $db->sql_query($sql);

			if ($row = $db->sql_fetchrow($result))
			{
				do
				{
					$userlist_ary[] = (int) $row['user_id'];
				}
				while ($row = $db->sql_fetchrow($result));
			}
			else
			{
				trigger_error('NO_USERS');
			}
			$db->sql_freeresult($result);

			// Fetch currently set excludes. Prevent duplicate excludes.
			$sql = 'SELECT user_id 
				FROM ' . SPECULATIVE_EXCLUDE_TABLE;
			$result = $db->sql_query($sql);

			if ($row = $db->sql_fetchrow($result))
			{
				$userlist_ary_tmp = array();
				do
				{
					$userlist_ary_tmp[] = $row['user_id'];
				}
				while ($row = $db->sql_fetchrow($result));

				$userlist_ary = array_unique(array_diff($userlist_ary, $userlist_ary_tmp));
				unset($userlist_ary_tmp);
			}
			$db->sql_freeresult($result);

			// We have some user_ids to exclude
			if (sizeof($userlist_ary))
			{
				$sql_ary = array();

				foreach ($userlist_ary as $user_entry)
				{
					$sql_ary[] = array('user_id' => $user_entry,);
				}

				$db->sql_multi_insert(SPECULATIVE_EXCLUDE_TABLE, $sql_ary);
			}

			// Add to moderator and admin log
			add_log('admin', 'LOG_PROXY_REVEALER_UEXCLUDES_ADD', $user_list_log);
			add_log('mod', 0, 0, 'LOG_PROXY_REVEALER_UEXCLUDES_ADD', $user_list_log);

			$cache->destroy('sql', SPECULATIVE_EXCLUDE_TABLE);

			trigger_error($user->lang['EXCLUDE_UPDATE_SUCCESSFUL'] . adm_back_link($this->u_action));
		}

		//
		// Adapted from acp_ban.php and user_unban() in functions_user.php
		//
		if ($del_users_submit)
		{
			$remove_user = request_var('remove_user', array(''));

			if (!is_array($remove_user))
			{
				$remove_user = array($remove_user);
			}

			if (sizeof($remove_user))
			{
				// Grab details of excludes for logging information later
				$sql = 'SELECT u.username AS remove_info
					FROM ' . USERS_TABLE . ' u, ' . SPECULATIVE_EXCLUDE_TABLE . ' e
					WHERE ' . $db->sql_in_set('e.user_id', $remove_user) . '
						AND u.user_id = e.user_id';
				$result = $db->sql_query($sql);

				$l_remove_list = '';
				while ($row = $db->sql_fetchrow($result))
				{
					$l_remove_list .= (($l_remove_list != '') ? ', ' : '') . $row['remove_info'];
				}
				$db->sql_freeresult($result);

				$sql = 'DELETE FROM ' . SPECULATIVE_EXCLUDE_TABLE . '
					WHERE ' . $db->sql_in_set('user_id', $remove_user);
				$db->sql_query($sql);

				// Add to moderator and admin log
				add_log('admin', 'LOG_PROXY_REVEALER_UEXCLUDES_DEL', $l_remove_list);
				add_log('mod', 0, 0, 'LOG_PROXY_REVEALER_UEXCLUDES_DEL', $l_remove_list);

				$cache->destroy('sql', SPECULATIVE_EXCLUDE_TABLE);

				trigger_error($user->lang['EXCLUDE_UPDATE_SUCCESSFUL'] . adm_back_link($this->u_action));
			}
		}

		//
		// Output relevant page
		//
		$sql = 'SELECT ip_address 
			FROM ' . SPECULATIVE_EXCLUDE_TABLE . " 
			WHERE ip_address <> ''";
		$result = $db->sql_query($sql);

		$current_ip_list = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);

		$select_iplist = '';

		for ($i = 0; $i < count($current_ip_list); $i++)
		{
			$ip_address = $current_ip_list[$i]['ip_address'];
			$select_iplist .= '<option value="' . $ip_address . '">' . $ip_address . '</option>';
		}

		$sql = 'SELECT e.user_id, u.user_id, u.username, u.username_clean 
			FROM ' . SPECULATIVE_EXCLUDE_TABLE . ' e, ' . USERS_TABLE . ' u 
			WHERE u.user_id = e.user_id 
			ORDER BY u.username_clean ASC';
		$result = $db->sql_query($sql);

		$current_user_list = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);

		$select_userlist = '';

		for ($i = 0; $i < count($current_user_list); $i++)
		{
			$user_id = $current_user_list[$i]['user_id'];
			$username = $current_user_list[$i]['username'];
			$select_userlist .= '<option value="' . $user_id . '">' . $username . '</option>';
		}

		$template->assign_vars(array(
			'L_PROXY_REVEALER'				=> $user->lang['ACP_PROXY_REVEALER'],
			'L_PROXY_REVEALER_EXCLUDES'		=> $user->lang['ACP_PROXY_REVEALER_EXCLUDES'],
			'L_SPECULATIVE_IP_EXCLUDE_DESC'	=> $user->lang['SPECULATIVE_IP_EXCLUDE'],
			'L_ADD_IP' 						=> $user->lang['ADD_IP'],
			'L_ADD_IP_EXPLAIN'				=> $user->lang['ADD_IP_EXPLAIN'],
			'L_ADD_USER' 					=> $user->lang['ADD_USER'],
			'L_ADD_USER_EXPLAIN'			=> $user->lang['ADD_USER_EXPLAIN'],
			'L_REMOVE_IP'					=> $user->lang['REMOVE_IP'],
			'L_REMOVE_IP_EXPLAIN'			=> $user->lang['REMOVE_IP_EXPLAIN'],
			'L_REMOVE_USER'					=> $user->lang['REMOVE_USER'],
			'L_REMOVE_USER_EXPLAIN'			=> $user->lang['REMOVE_USER_EXPLAIN'],
			'L_IP_OR_HOSTNAME'				=> $user->lang['IP_HOSTNAME'],
			'L_USERNAME'					=> $user->lang['USERNAME'],
			'L_SUBMIT'						=> $user->lang['SUBMIT'],
			'L_RESET'						=> $user->lang['RESET'],
			'L_NO_IP'						=> $user->lang['NO_IP'],
			'L_NO_USER'						=> $user->lang['NO_USER'],

			'S_REMOVE_IPS'					=> ($select_iplist) ? true : false,
			'S_REMOVE_IPLIST_SELECT'		=> $select_iplist,
			'S_REMOVE_USERS'				=> ($select_userlist) ? true : false,
			'S_REMOVE_USERLIST_SELECT'		=> $select_userlist,

			'U_ACTION'						=> $this->u_action,
			'U_FIND_USERNAME'				=> append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=searchuser&amp;form=add_users&amp;field=add_user'),
		));
	}

	/**
	* Select IP Masking Block methods (or scanning methods to defer)
	*
	* $key can be either 'ip_block' or 'ip_scan_defer', depending on which setting uses this function
	*/
	function ip_block_select($value, $key = '')
	{
		global $user, $config;

		$ip_block = '<input id="'.$key.'" name="config['.$key.']" type="hidden" value="'.$value.'" />';

		// We do a "Bitwise AND" against the methods defined in constants.php to see if they're enabled
		$cookie_on	= ( $value & COOKIE ) ? 'checked="checked"' : "";
		$dnsbl_on	= ( $value & PROXY_DNSBL ) ? 'checked="checked"' : "";
		$flash_on	= ( $value & FLASH ) ? 'checked="checked"' : "";
		$java_on	= ( $value & JAVA ) ? 'checked="checked"' : "";
		$qtime_on	= ( $value & QUICKTIME ) ? 'checked="checked"' : "";
		$realp_on	= ( $value & REALPLAYER ) ? 'checked="checked"' : "";
		$tor_el_on	= ( $value & TOR_DNSEL ) ? 'checked="checked"' : "";
		$wmp_on		= ( $value & WMPLAYER ) ? 'checked="checked"' : "";
		$xss_on		= ( $value & XSS ) ? 'checked="checked"' : "";
		$x_fwd_on	= ( $value & X_FORWARDED_FOR ) ? 'checked="checked"' : "";

		// The actual methods' checkboxes :-)
		$cookie = '<label><input id="'.$key.'_'.'cookie" type="checkbox" class="radio" value="'.COOKIE
				.'" '.$cookie_on.' onclick="calc'.$key.'();" /> ' . $user->lang['COOKIE'] . '</label>';
		$dnsbl = '<label><input id="'.$key.'_'.'dnsbl" type="checkbox" class="radio" value="'.PROXY_DNSBL
				.'" '.$dnsbl_on.' onclick="calc'.$key.'();" /> ' . $user->lang['PROXY_DNSBL'] . '</label>';
		$flash = '<label><input id="'.$key.'_'.'flash" type="checkbox" class="radio" value="'.FLASH
				.'" '.$flash_on.' onclick="calc'.$key.'();" /> ' . $user->lang['FLASH'] . '</label>';
		$java = '<label><input id="'.$key.'_'.'java" type="checkbox" class="radio" value="'.JAVA
				.'" '.$java_on.' onclick="calc'.$key.'();" /> ' . $user->lang['JAVA'] . '</label>';
		$qtime = '<label><input id="'.$key.'_'.'qtime" type="checkbox" class="radio" value="'.QUICKTIME
				.'" '.$qtime_on.' onclick="calc'.$key.'();" /> ' . $user->lang['QUICKTIME'] . '</label>';
		$realp = '<label><input id="'.$key.'_'.'realp" type="checkbox" class="radio" value="'.REALPLAYER
				.'" '.$realp_on.' onclick="calc'.$key.'();" /> ' . $user->lang['REALPLAYER'] . '</label>';
		$tor_el = '<label><input id="'.$key.'_'.'tor_el" type="checkbox" class="radio" value="'.TOR_DNSEL
				.'" '.$tor_el_on.' onclick="calc'.$key.'();" /> ' . $user->lang['TOR_DNSEL'] . '</label>';
		$wmp = '<label><input id="'.$key.'_'.'wmp" type="checkbox" class="radio" value="'.WMPLAYER
				.'" '.$wmp_on.' onclick="calc'.$key.'();" /> ' . $user->lang['WMPLAYER'] . '</label>';
		$xss = '<label><input id="'.$key.'_'.'xss" type="checkbox" class="radio" value="'.XSS
				.'" '.$xss_on.' onclick="calc'.$key.'();" /> ' . $user->lang['XSS'] . '</label>';
		$x_fwd = '<label><input id="'.$key.'_'.'x_fwd" type="checkbox" class="radio" value="'.X_FORWARDED_FOR
				.'" '.$x_fwd_on.' onclick="calc'.$key.'();" /> ' . $user->lang['X_FORWARDED_FOR'] . '</label>';

		$js_calc = '
			<script type="text/javascript">
			// <![CDATA[
			function calc'.$key.'(){
				ip_block = document.getElementById("'.$key.'");
				cookie = document.getElementById("'.$key.'_'.'cookie");
				dnsbl = document.getElementById("'.$key.'_'.'dnsbl");
				flash = document.getElementById("'.$key.'_'.'flash");
				java = document.getElementById("'.$key.'_'.'java");
				qtime = document.getElementById("'.$key.'_'.'qtime");
				realp = document.getElementById("'.$key.'_'.'realp");
				tor_el = document.getElementById("'.$key.'_'.'tor_el");
				wmp = document.getElementById("'.$key.'_'.'wmp");
				xss = document.getElementById("'.$key.'_'.'xss");
				x_fwd = document.getElementById("'.$key.'_'.'x_fwd");
				ip_block.value = 0;
				if(cookie.checked){ip_block.value = parseInt(ip_block.value) + parseInt(cookie.value);}
				if(dnsbl.checked){ip_block.value = parseInt(ip_block.value) + parseInt(dnsbl.value);}
				if(flash.checked){ip_block.value = parseInt(ip_block.value) + parseInt(flash.value);}
				if(java.checked){ip_block.value = parseInt(ip_block.value) + parseInt(java.value);}
				if(qtime.checked){ip_block.value = parseInt(ip_block.value) + parseInt(qtime.value);}
				if(realp.checked){ip_block.value = parseInt(ip_block.value) + parseInt(realp.value);}
				if(tor_el.checked){ip_block.value = parseInt(ip_block.value) + parseInt(tor_el.value);}
				if(wmp.checked){ip_block.value = parseInt(ip_block.value) + parseInt(wmp.value);}
				if(xss.checked){ip_block.value = parseInt(ip_block.value) + parseInt(xss.value);}
				if(x_fwd.checked){ip_block.value = parseInt(ip_block.value) + parseInt(x_fwd.value);}
			}
			// ]]>
			</script>
			';

		return $js_calc . '<div class="optgroup">' . $ip_block 
			. $xss . $java . $flash . '<br />'
			. $qtime . $realp . $wmp . '<br />'
			. $dnsbl . $tor_el . '<br />' . $cookie . $x_fwd . '<br /></div>';
	}

	/**
	* Select IP Masking Ban length
	*/
	function ipbanlength_select($value, $key = '')
	{
		global $user, $config;

		$l_other = $this->new_config['ip_ban_length_other'];

		// Ban length options (Adapted from acp_ban.php)
		$ban_end_text = array(0 => $user->lang['PERMANENT'], 30 => $user->lang['30_MINS'], 60 => $user->lang['1_HOUR'], 360 => $user->lang['6_HOURS'], 1440 => $user->lang['1_DAY'], 10080 => $user->lang['7_DAYS'], 20160 => $user->lang['2_WEEKS'], 40320 => $user->lang['1_MONTH'], -1 => $user->lang['UNTIL'] . ' -&gt; ');

		$ban_end_options = '';
		foreach ($ban_end_text as $length => $text)
		{
			$ban_end_options .= '<option value="' . $length . '"' . (($length == $value) ? ' selected="selected"' : '') . '>' . $text . '</option>';
		}

		return "<select name=\"config[$key]\" id=\"$key\" onchange=\"if(this.value==-1){document.getElementById('ipbanlengthother').style.display = 'block';}else{document.getElementById('ipbanlengthother').style.display='none';}\">$ban_end_options</select>
		<div id=\"ipbanlengthother\" style=\"display:none;\"><label><input type=\"text\" name=\"config[ip_ban_length_other]\" value=\"$l_other\" maxlength=\"10\" />
		<br /><span>" . $user->lang['YEAR_MONTH_DAY'] . "</span></label></div>";

	}
}

?>