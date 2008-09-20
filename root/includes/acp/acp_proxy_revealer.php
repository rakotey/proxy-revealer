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
		global $db, $user, $auth, $cache, $template;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;

		$user->add_lang('mods/proxy_revealer');
		$this->page_title = 'ACP_PROXY_REVEALER';

		$action	= request_var('action', '');
		$submit = (isset($_POST['submit'])) ? true : false;

		// Whois (special case)
		if ($action == 'whois')
		{
			include($phpbb_root_path . 'includes/functions_user.' . $phpEx);

			$this->page_title = 'WHOIS';
			$this->tpl_name = 'simple_body';

			$ip = request_var('ip', '');
			$ipwhois = user_ipwhois($ip);

			$template->assign_vars(array(
				'MESSAGE_TITLE'		=> sprintf($user->lang['IP_WHOIS_FOR'], $ip),
				'MESSAGE_TEXT'		=> nl2br($ipwhois))
			);

			return;
		}

		// Flash & Java plugins' extended information popups
		if ($action == 'flash' || $action == 'java')
		{
			$this->page_title = 'SPECULATIVE_IP_' . strtoupper($action);
			$this->tpl_name = 'acp_proxy_revealer_plugin';

			$spoofed_ip = request_var('spoofed', '');
			$real_ip = request_var('real', '');
			$method = request_var('method', 0);

			if ($method != JAVA && $method != JAVA_INTERNAL && $method != FLASH)
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
				'L_PLUGIN_DESC'		=> ($method == FLASH) ? $user->lang['SPECULATIVE_IP_FLASH'] : $user->lang['SPECULATIVE_IP_JAVA'],
				'L_USER_AGENT' 		=> $user->lang['USER_AGENT'],
				'L_PLUGIN_VERSION'	=> ($method == FLASH) ? $user->lang['FLASH_VERSION'] : $user->lang['JAVA_VERSION'],

				'PLUGIN_VERSION'	=> $plugin_version,
				'USER_AGENT'		=> $info[0],
			));

			return;
		}

		switch($mode)
		{
			case 'internal':
			case 'external':
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
					$real_ip = preg_replace('#(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})#','$1',$row['real_ip']);
					$real_ip_url = '<a href="' . $this->u_action . "&amp;action=whois&amp;ip=$real_ip" . '" '
						. 'title="' . sprintf($user->lang['IP_WHOIS_FOR'], $real_ip) . '" '
						. 'onclick="popup(this.href, 700, 500, \'_whois\'); return false;">' . $real_ip . '</a>';

					$ip_address = $row['ip_address'];

					switch ( $row['method'] )
					{
						case FLASH:
							$method = '<a href="'
								. $this->u_action . "&amp;action=flash&amp;spoofed=$row[ip_address]&amp;real=$real_ip&amp;method=$row[method]"
								. '" title="' . $user->lang['SPECULATIVE_IP_FLASH'] . '" '
								. 'onclick="popup(this.href, 700, 300, \'_flash\'); return false;">' . $user->lang['FLASH'] . '</a>';
							break;
						case JAVA:
						case JAVA_INTERNAL:
							$method = '<a href="'
								. $this->u_action . "&amp;action=java&amp;spoofed=$row[ip_address]&amp;real=$real_ip&amp;method=$row[method]"
								. '" title="' . $user->lang['SPECULATIVE_IP_JAVA'] . '" '
								. 'onclick="popup(this.href, 700, 300, \'_java\'); return false;">' . $user->lang['JAVA'] . '</a>';
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
									$method =  !empty($urls[0]) ? '<a href="' . $urls[0]
										. '" title="' . $user->lang['XSS_URL'] . '" '
										. 'onclick="popup(this.href, 700, 500, \'_xss\'); return false;">' . $user->lang['XSS'] . '</a>'
										: $user->lang['XSS'];
									break;
								case $count == 2: // eg. default; there can be up to two url's in $urls.  if there is, represent the link to the second one with "(2)"
									$method =  !empty($urls[0]) ? '<a href="' . $urls[0]
										. '" title="' . $user->lang['XSS_URL'] . '" '
										. 'onclick="popup(this.href, 700, 500, \'_xss\'); return false;">' . $user->lang['XSS'] . '</a>'
										: $user->lang['XSS'];
									$method.= !empty($urls[1]) ? '&nbsp;&nbsp;<a href="' . $urls[1]
										. '" title="' . $user->lang['XSS_URL'] . '" '
										. 'onclick="popup(this.href, 700, 500, \'_xss\'); return false;">(2)</a>'
										: '';
							}
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
			break;

			case 'settings':
				// We add 'acp/ban' language file because we reuse the ban-length options' names in our local function ipbanlength_select()
				// and we also reuse 'BAN_LENGTH', 'BAN_REASON' and 'BAN_GIVE_REASON' in our $display_vars below
				$user->add_lang('acp/ban');

				$this->tpl_name = 'acp_proxy_revealer_settings';

				$form_key = 'acp_proxy_revealer_settings';
				add_form_key($form_key);

				$display_vars = array(
					'title'	=> 'ACP_PROXY_REVEALER_SETTINGS',
					'vars'	=> array(
						'legend1'				=> 'ACP_PROXY_REVEALER_SETTINGS',
						'flash_mask'			=> array('lang' => 'IP_MASK_BLOCK',		'validate' => 'int',	'type' => 'custom', 'method' => 'ipmaskblock_select', 'explain' => true),
						'java_mask'				=> array('lang' => 'IP_MASK_BLOCK',		'validate' => 'int',	'type' => false, 'method' => false, 'explain' => false,),
						'xss_mask'				=> array('lang' => 'IP_MASK_BLOCK',		'validate' => 'int',	'type' => false, 'method' => false, 'explain' => false,),
						'x_forwarded_for_mask'	=> array('lang' => 'IP_MASK_BLOCK',		'validate' => 'int',	'type' => false, 'method' => false, 'explain' => false,),
						'require_javascript'	=> array('lang' => 'IP_REQUIRE_JS',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'ip_ban'				=> array('lang' => 'IP_MASK_BAN',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'ip_ban_length'			=> array('lang' => 'BAN_LENGTH',		'validate' => 'int',	'type' => 'custom', 'method' => 'ipbanlength_select', 'explain' => false),
						'ip_ban_length_other'	=> array('lang' => 'BAN_LENGTH',		'validate' => 'string',	'type' => false, 'method' => false, 'explain' => false),
						'ip_ban_reason'			=> array('lang' => 'BAN_REASON',		'validate' => 'string',	'type' => 'text:40:255', 'explain' => false),
						'ip_ban_give_reason'	=> array('lang' => 'BAN_GIVE_REASON',	'validate' => 'string',	'type' => 'text:40:255', 'explain' => false),
						'ip_prune'				=> array('lang' => 'IP_MASK_PRUNE',		'validate' => 'int',	'type' => 'text:3:4', 'explain' => true, 'append' => ' ' . $user->lang['DAYS']),
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
						// Reset 'ip_block' to ZERO before we proceed to loop through *_mask checkboxes
						$this->new_config['ip_block'] = 0;
						set_config('ip_block', 0);

						foreach ($display_vars['vars'] as $config_name => $null)
						{
							// Special case for the *_mask checkboxes :-)
							if (isset($cfg_array[$config_name]) && strpos($config_name, '_mask') !== false)
							{
								// This sets the bits that are set in either $this->new_config['ip_block'] or $cfg_array[$config_name]
								// this is so we can increment ip_block as we loop through *_mask
								$ip_block_new_val = (int) $this->new_config['ip_block'] | (int) $cfg_array[$config_name];

								$this->new_config['ip_block'] = $ip_block_new_val;
								set_config('ip_block', $ip_block_new_val);

								continue;
							}

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
			break;

			case 'excludes':
				$this->tpl_name = 'acp_proxy_revealer_excludes';

				$form_key = 'acp_proxy_revealer_excludes';
				add_form_key($form_key);

				$add_excludes_submit = (isset($_POST['add_excludes_submit'])) ? true : false;
				$del_excludes_submit = (isset($_POST['del_excludes_submit'])) ? true : false;

				if (($add_excludes_submit || $del_excludes_submit) && !check_form_key($form_key))
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
				// Output relevant page
				//
				$sql = 'SELECT ip_address 
					FROM ' . SPECULATIVE_EXCLUDE_TABLE;
				$result = $db->sql_query($sql);

				$current_ip_list = $db->sql_fetchrowset($result);
				$db->sql_freeresult($result);

				$select_iplist = '';

				for ($i = 0; $i < count($current_ip_list); $i++)
				{
					$ip_address = $current_ip_list[$i]['ip_address'];
					$select_iplist .= '<option value="' . $ip_address . '">' . $ip_address . '</option>';
				}

				$template->assign_vars(array(
					'L_PROXY_REVEALER'				=> $user->lang['ACP_PROXY_REVEALER'],
					'L_PROXY_REVEALER_EXCLUDES'		=> $user->lang['ACP_PROXY_REVEALER_EXCLUDES'],
					'L_SPECULATIVE_IP_EXCLUDE_DESC'	=> $user->lang['SPECULATIVE_IP_EXCLUDE'],
					'L_ADD_IP' 						=> $user->lang['ADD_IP'],
					'L_ADD_IP_EXPLAIN'				=> $user->lang['ADD_IP_EXPLAIN'],
					'L_REMOVE_IP'					=> $user->lang['REMOVE_IP'],
					'L_REMOVE_IP_EXPLAIN'			=> $user->lang['REMOVE_IP_EXPLAIN'],
					'L_IP_OR_HOSTNAME'				=> $user->lang['IP_HOSTNAME'],
					'L_SUBMIT'						=> $user->lang['SUBMIT'],
					'L_RESET'						=> $user->lang['RESET'],
					'L_NO_IP'						=> $user->lang['NO_IP'],

					'S_REMOVE_IPS'					=> ($select_iplist) ? true : false,
					'S_REMOVE_IPLIST_SELECT'		=> $select_iplist,

					'U_ACTION'						=> $this->u_action,
				));
			break;

			default:
				trigger_error('NO_MODE', E_USER_ERROR);
			break;
		}
	}

	/**
	* Select IP Masking Block methods
	*/
	function ipmaskblock_select($value, $key = '')
	{
		global $user, $config;

		$flash_enabled = ( $this->new_config['ip_block'] & FLASH ) ? 'checked="checked"' : "";
		$java_enabled = ( $this->new_config['ip_block'] & JAVA ) ? 'checked="checked"' : "";
		$xss_enabled = ( $this->new_config['ip_block'] & XSS ) ? 'checked="checked"' : "";
		$x_forwarded_for_enabled = ( $this->new_config['ip_block'] & X_FORWARDED_FOR ) ? 'checked="checked"' : "";

		$flash = '<input id="' . $key . '" type="checkbox" name="config[flash_mask]" value="' . FLASH . '" ' . $flash_enabled . ' /> '
			. $user->lang['FLASH'] . '&nbsp;&nbsp;';
		$java = '<input type="checkbox" name="config[java_mask]" value="' . JAVA . '" ' . $java_enabled . ' /> '
			. $user->lang['JAVA'] . '&nbsp;&nbsp;';
		$xss = '<input type="checkbox" name="config[xss_mask]" value="' . XSS . '" ' . $xss_enabled . ' /> '
			. $user->lang['XSS'] . '&nbsp;&nbsp;';
		$x_forwarded_for = '<input type="checkbox" name="config[x_forwarded_for_mask]" value="' . X_FORWARDED_FOR . '" ' . $x_forwarded_for_enabled . ' /> '
			. $user->lang['X_FORWARDED_FOR'] . '&nbsp;&nbsp;';

		return $flash . $java . $xss . $x_forwarded_for;
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