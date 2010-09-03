<?php
/**
 *
 * @author jasmineaura (Jasmine Hasan) jasmine.aura@yahoo.com
 * @version $Id$
 * @copyright (c) 2010 Jasmine Hasan, Jim Wigginton
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

/**
 * @ignore
 */
define('UMIL_AUTO', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);

if (!file_exists($phpbb_root_path . 'umil/umil_auto.' . $phpEx))
{
	trigger_error('Please download the latest UMIL (Unified MOD Install Library) from: <a href="http://www.phpbb.com/mods/umil/">phpBB.com/mods/umil</a>', E_USER_ERROR);
}

// The name of the mod to be displayed during installation.
$mod_name = 'PROXY_REVEALER_MOD';

/*
* The name of the config variable which will hold the currently installed version
* UMIL will handle checking, setting, and updating the version itself.
*/
$version_config_name = 'proxyrevealer_version';


// The language file which will be included when installing
$language_file = 'mods/info_acp_proxy_revealer';


/*
* Optionally we may specify our own logo image to show in the upper corner instead of the default logo.
* $phpbb_root_path will get prepended to the path specified
* Image height should be 50px to prevent cut-off or stretching.
*/
//$logo_img = 'styles/prosilver/imageset/site_logo.gif';

/*
* The array of versions and actions within each.
* You do not need to order it a specific way (it will be sorted automatically), however, you must enter every version, even if no actions are done for it.
*
* You must use correct version numbering.  Unless you know exactly what you can use, only use X.X.X (replacing X with an integer).
* The version numbering must otherwise be compatible with the version_compare function - http://php.net/manual/en/function.version-compare.php
*/
$versions = array(
	// Version 0.3.4
	'0.3.4'	=> array(
		'config_remove'	=> array(
			array('proxy_revealer_on'),
			array('ip_block_defer'),
		),

		'config_add'	=> array(
			array('pro_mod_on', true),
			array('ip_scan_defer', 0),
			array('ip_flash_on', true),
			array('ip_flash_port', 9999),
			array('ip_last_prune', 0, true),
		),

		'config_update'	=> array(
			array('ip_block', 1006),
		),
	),

	// Version 0.3.3
	'0.3.3' => array(
		'table_add' => array(
			array('phpbb_speculative_ips', array(
				'COLUMNS' => array(
					'ip_address'	=> array('VCHAR:40', ''),
					'method'		=> array('USINT', 0),
					'discovered'	=> array('TIMESTAMP', 0),
					'real_ip'		=> array('VCHAR:40', ''),
					'info'			=> array('TEXT', ''),
				),

				'PRIMARY_KEY'	=> 'ip_address',
			)),

			array('phpbb_speculative_excludes', array(
				'COLUMNS' => array(
					'exclude_id'	=> array('UINT', 0, 'auto_increment'),
					'user_id'		=> array('UINT', 0),
					'ip_address'	=> array('VCHAR:40', ''),
				),

				'PRIMARY_KEY'	=> 'exclude_id',

				'KEYS'	=> array(
					'user_id'		=> array('INDEX', 'user_id'),
					'ip_address'	=> array('INDEX', 'ip_address'),
				),
			)),
		),

		'table_column_add' => array(
			array('SESSIONS_TABLE', 'session_speculative_test', array('TINT:1', -1)),
			array('SESSIONS_TABLE', 'session_speculative_key', array('CHAR:10', '')),
		),

		'config_add' => array(
			array('proxy_revealer_on', true),
			array('ip_block', 238),
			array('ip_block_defer', 0),
			array('ip_ban', false),
			array('ip_ban_length', 0),
			array('ip_ban_length_other', '2012-12-31'),
			array('ip_ban_reason', 'Auto-banned by Proxy Revealer'),
			array('ip_ban_give_reason', 'Your IP address is banned because it appears to be a Proxy'),
			array('ip_cookie_age', 2),
			array('ip_prune', 0),
			array('require_javascript', true),
		),

		'module_add' => array(
			// First, lets add a new category named ACP_PROXY_REVEALER to ACP_CAT_DOT_MODS
			array('acp', 'ACP_CAT_DOT_MODS', 'ACP_PROXY_REVEALER'),

			// Now we will add the modes for the MOD using the "automatic" method.
			array('acp', 'ACP_PROXY_REVEALER', array(
					'module_basename'		=> 'proxy_revealer',
					'modes'					=> array('external', 'internal', 'settings', 'excludes')
				),
			),
		),

		// Clear the cache. Blank purges all of the forum cache ($cache->purge())
		'cache_purge'	=> array(''),
	),
);

// Include the UMIL Auto file, it handles the rest
include($phpbb_root_path . 'umil/umil_auto.' . $phpEx);

?>