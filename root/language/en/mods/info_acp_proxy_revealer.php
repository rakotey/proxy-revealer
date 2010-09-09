<?php
/**
*
* info_acp_proxy_revealer [English]
*
* @package language
* @version $Id$
* @copyright (c) 2006 by TerraFrost (c) 2008 by jasmineaura
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

// Proxy Revealer Olympus ACP Titles
$lang = array_merge($lang, array(
	'ACP_PROXY_REVEALER'				=> 'Proxy Revealer Olympus',
	'ACP_PROXY_REVEALER_EXTERNAL'		=> 'External IPs Log',
	'ACP_PROXY_REVEALER_INTERNAL'		=> 'Internal IPs Log',
	'ACP_PROXY_REVEALER_SETTINGS'		=> 'MOD Settings',
	'ACP_PROXY_REVEALER_EXCLUDES'		=> 'MOD Exceptions',
	'LOG_PROXY_REVEALER_SETTINGS'		=> '<strong>Altered Proxy Revealer settings</strong>',
	'LOG_PROXY_REVEALER_EXCLUDES_ADD'	=> '<strong>Excluded IP(s) from Proxy Revealer scanning</strong><br />» %1$s',
	'LOG_PROXY_REVEALER_EXCLUDES_DEL'	=> '<strong>Removed IP(s) from Proxy Revealer exceptions list</strong><br />» %s',
	'LOG_PROXY_REVEALER_UEXCLUDES_ADD'	=> '<strong>Excluded Username(s) from Proxy Revealer scanning</strong><br />» %1$s',
	'LOG_PROXY_REVEALER_UEXCLUDES_DEL'	=> '<strong>Removed Username(s) from Proxy Revealer exceptions list</strong><br />» %s',

	// For UMIL
	'PROXY_REVEALER_MOD'					=> '“Proxy Revealer Olympus” MOD',
	'INSTALL_PROXY_REVEALER_MOD'			=> 'Install “Proxy Revealer Olympus” MOD',
	'INSTALL_PROXY_REVEALER_MOD_CONFIRM'	=> 'Are you sure you want to install the “Proxy Revealer Olympus” MOD?',
	'UPDATE_PROXY_REVEALER_MOD'				=> 'Update “Proxy Revealer Olympus” MOD',
	'UPDATE_PROXY_REVEALER_MOD_CONFIRM'		=> 'Are you sure you want to update the “Proxy Revealer Olympus” MOD?',
	'UNINSTALL_PROXY_REVEALER_MOD'			=> 'Uninstall “Proxy Revealer Olympus” MOD',
	'UNINSTALL_PROXY_REVEALER_MOD_CONFIRM'	=> 'Are you sure you want to uninstall the “Proxy Revealer Olympus” MOD?',
));

?>