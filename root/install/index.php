<?php
/**
*
* @package phpBB3
* @version $Id$
* @copyright (c) 2008 by jasmineaura
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
*/

/**
* @ignore
*/
define('IN_PHPBB', true);
define('IN_INSTALL_PROXY_REVEALER', true);
define('DEBUG', true);
define('DEBUG_EXTRA', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup(array('acp/common', 'install', 'mods/proxy_revealer_install'));

// Have they authenticated (again) as an admin for this session?
if (!isset($user->data['session_admin']) || !$user->data['session_admin'])
{
	login_box('', $user->lang['LOGIN_ADMIN_CONFIRM'], $user->lang['LOGIN_ADMIN_SUCCESS']);
}

// Is user any type of admin? No, then stop here, each script needs to
// check specific permissions but this is a catchall
if (!$auth->acl_get('a_'))
{
	trigger_error('NO_ADMIN');
}

// some more includes
include($phpbb_root_path . 'install/functions_install.' . $phpEx);
include($phpbb_root_path . 'includes/db/db_tools.' . $phpEx);

// Create a $db_tools object that is needed to be passed to process_install() (for doing 'schema_changes')
$db_tools = new phpbb_db_tools($db);

// Set custom template for admin area
$template->set_custom_template($phpbb_root_path . 'install/style', 'install');
$template->assign_var('T_TEMPLATE_PATH', $phpbb_root_path . 'install/style');

$mode = request_var('mode', '');

$proxyrevealer_version = '0.3.3';

// init
$error = array();

$page_body = '';

switch ($mode)
{
	case 'delete':

	    if (!is_writable("{$phpbb_root_path}install/"))
	    {
	        $page_body .= $user->lang['CANNOT_DELETE'] . '<br /><br />';
			$page_body .= sprintf($user->lang['RETURN_INSTALL'], '<a href="' . append_sid("{$phpbb_root_path}install/index.$phpEx") . '">', '</a>');
			break;
		}

		// attempt to delete the file itself
		delete_dir($phpbb_root_path . 'install/');
		redirect("{$phpbb_root_path}index.$phpEx");

	break;

	case 'install':

		if (isset($config['proxyrevealer_version']))
		{
			$page_body .= $user->lang['ALREADY_INSTALLED'] . '<br /><br />';
			$page_body .= sprintf($user->lang['RETURN_INSTALL'], '<a href="' . append_sid("{$phpbb_root_path}install/index.$phpEx") . '">', '</a>');
			break;
		}

		$install_data = array(
			'load_schema'		=> $phpbb_root_path . 'install/schemas/',
			'schema_changes'	=> array(
				'add_columns'		=> array(
					SESSIONS_TABLE		=> array(
						'session_speculative_test'	=> array('TINT:1', -1),
						'session_speculative_key'	=> array('CHAR:10', NULL),
					),
				),
			),
			'install_modules'	=> array(
				array('acp', 'acp_proxy_revealer', 'ACP_CAT_DOT_MODS'),
			),
			'set_config'		=> array(
				array('proxy_revealer_on', 1),
				array('ip_block', 238),
				array('ip_block_defer', 0),
				array('ip_ban', 0),
				array('ip_ban_length', 0),
				array('ip_ban_length_other', ''),
				array('ip_ban_reason', 'Auto-banned by Proxy Revealer'),
				array('ip_ban_give_reason', 'Your IP address is banned because it appears to be a Proxy'),
				array('ip_cookie_age', 2),
				array('ip_prune', 0),
				array('require_javascript', 1),
			),
		);

		process_install($install_data, $error, $db_tools);

		$page_body .= '<br /><br />';
		$page_body .= $user->lang['PROXY_MOD_INSTALLED'] . '<br /><br />';
		$page_body .= sprintf($user->lang['RETURN_INSTALL'], '<a href="' . append_sid("{$phpbb_root_path}install/index.$phpEx") . '">', '</a>');

		set_config('proxyrevealer_version', $proxyrevealer_version);

		// purge cache
		$cache->purge();
		$auth->acl_clear_prefetch();

	break;

	case 'update':

		$update_data = array(
			'0.3.3'	=> array(
				'schema_changes'	=> array(
					'change_columns'	=> array(
						SPECULATIVE_TABLE	=> array(
							'method'		=> array('USINT', 0),
						),
					),
				),
				'install_modules'	=> array(
					array('acp', 'acp_proxy_revealer', 'ACP_CAT_DOT_MODS'),
				),
				'set_config'		=> array(
					array('proxy_revealer_on', 1),
					array('ip_block', 238),
					array('ip_block_defer', 0),
					array('ip_ban', 0),
					array('ip_ban_length', 0),
					array('ip_ban_length_other', ''),
					array('ip_ban_reason', 'Auto-banned by Proxy Revealer'),
					array('ip_ban_give_reason', 'Your IP address is banned because it appears to be a Proxy'),
					array('ip_cookie_age', 2),
					array('ip_prune', 0),
					array('require_javascript', 1),
				),
			),
		);

		if (!isset($config['proxyrevealer_version']) || !version_compare($config['proxyrevealer_version'], $proxyrevealer_version, '<'))
		{
			$page_body .= $user->lang['ALREADY_NOT_INSTALLED'] . '<br /><br />';
			$page_body .= sprintf($user->lang['RETURN_INSTALL'], '<a href="' . append_sid("{$phpbb_root_path}install/index.$phpEx") . '">', '</a>');
			break;
		}

		foreach ($update_data as $update_version => $update_ary)
		{
			// if our version is bigger, skip
			if (!version_compare($config['proxyrevealer_version'], $update_version, '<'))
			{
				continue;
			}

			process_install($update_ary, $error, $db_tools);
		}

		set_config('proxyrevealer_version', $proxyrevealer_version);

		$page_body .= '<br /><br />';
		$page_body .= sprintf($user->lang['PROXY_MOD_UPDATED'], $config['proxyrevealer_version']) . '<br /><br />';
		$page_body .= sprintf($user->lang['RETURN_INSTALL'], '<a href="' . append_sid("{$phpbb_root_path}install/index.$phpEx") . '">', '</a>');

		// purge cache
		$cache->purge();
		$auth->acl_clear_prefetch();

	break;

	default:

		$page_body .= '<br /><br />';

		if (!isset($config['proxyrevealer_version']))
		{
			$page_body .= '<a href="' . append_sid("{$phpbb_root_path}install/index.$phpEx", 'mode=install') . '">&raquo; ' . $user->lang['INSTALL'] . '</a><br />';
		}
		else if (version_compare($config['proxyrevealer_version'], $proxyrevealer_version, '<'))
		{
			$page_body .= '<a href="' . append_sid("{$phpbb_root_path}install/index.$phpEx", 'mode=update') . '">&raquo; ' . $user->lang['UPDATE'] . '</a><br />';
		}
		else
		{
			$page_body .= $user->lang['NOTHING_TO_INSTALL'] . '<br /><br />';
			$page_body .= sprintf($user->lang['RETURN_INDEX'], '<a href="' . append_sid("{$phpbb_root_path}index.$phpEx") . '">', '</a>');

			if (is_writable("{$phpbb_root_path}install/"))
			{
				$page_body .= '<br /><br />' . sprintf($user->lang['DELETE_SELF'], '<a href="' . append_sid("{$phpbb_root_path}install/index.$phpEx", 'mode=delete') . '">', '</a>');
			}
			else
			{
				$page_body .= '<br /><br />' . $user->lang['CANNOT_DELETE'];
			}
		}

	break;
}

// Assign index specific vars
$template->assign_vars(array(
	'TITLE'			=> $user->lang['PROXY_REVEALER_OLYMPUS'],
	'TITLE_EXPLAIN'	=> $user->lang['PROXY_REVEALER_EXPLAIN'],
	'BODY'			=> $page_body,
));

// Output page
page_header($user->lang['INSTALL_PANEL']);

$template->set_filenames(array(
	'body' => 'install_main.html')
);

page_footer();

?>