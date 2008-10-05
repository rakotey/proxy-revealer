<?php
/**
*
* proxy_revealer_install [English]
*
* @package language
* @version $Id$
* @copyright (c) 2008 by jasmineaura
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

$lang = array_merge($lang, array(
	// main title
	'PROXY_REVEALER_OLYMPUS'	=> 'Proxy Revealer Olympus MOD',
	'PROXY_REVEALER_EXPLAIN'	=> 'Proxy Revealer Olympus attempts to determine someone’s “real” IP address, using a myriad of techniques, and “blocks” such people. In some cases this may not be possible, such as with Tor (ex. Firefox + torbutton addon), so this MOD gives you the option to block Tor IPs as well. You can exclude Proxy IPs from the scanning/blocking in the “Exceptions” section. You can also exclude Usernames if you “defer” the appropriate scan methods.',

	// permission/login stuff
	'NO_ADMIN'				=> 'Access to the Installation Panel is not allowed as you do not have administrative permissions.',
	'LOGIN_ADMIN_CONFIRM'	=> 'To access the Installation Panel you must re-authenticate yourself.',
	'LOGIN_ADMIN_SUCCESS'	=> 'You have successfully authenticated and will now be redirected to the Installation Panel.',

	// installation stuff
	'PROXY_MOD_INSTALLED'	=> 'Proxy Revealer Olympus has been installed successfully. Please remove the install directory from your server.',
	'PROXY_MOD_UPDATED'		=> 'Proxy Revealer Olympus has been updated to version %s successfully.',
	'ALREADY_INSTALLED'		=> 'The MOD had already been installed.',
	'ALREADY_NOT_INSTALLED'	=> 'The MOD has not been installed yet or is up to date.',
	'NOTHING_TO_INSTALL'	=> 'There is nothing to do.',
	'CANNOT_DELETE'         => 'It´s not possible to delete the install folder, please delete it manually.',

	// return messages
	'DELETE_SELF'			=> '%sAttempt to delete the install folder%s',
	'RETURN_INSTALL'		=> '%sReturn to the Installation Panel%s',
));

?>