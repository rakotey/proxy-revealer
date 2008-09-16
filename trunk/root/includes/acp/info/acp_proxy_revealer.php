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
* @package module_install
*/
class acp_proxy_revealer_info
{
	function module()
	{
		return array(
			'filename' => 'acp_proxy_revealer',
			'title' => 'ACP_PROXY_REVEALER',
			'version' => '0.0.1',
			'modes' => array(
				'default'	=> array('title' => 'ACP_PROXY_REVEALER', 'auth' => 'acl_a_board', 'cat' => array('ACP_DOT_MODS')),
				'external'	=> array('title' => 'ACP_PROXY_REVEALER_EXTERNAL', 'auth' => 'acl_a_board', 'cat' => array('ACP_DOT_MODS')),
				'internal'	=> array('title' => 'ACP_PROXY_REVEALER_INTERNAL', 'auth' => 'acl_a_board', 'cat' => array('ACP_DOT_MODS')),
				'settings'	=> array('title' => 'ACP_PROXY_REVEALER_SETTINGS', 'auth' => 'acl_a_board', 'cat' => array('ACP_DOT_MODS')),
				'excludes'	=> array('title' => 'ACP_PROXY_REVEALER_EXCLUDES', 'auth' => 'acl_a_board', 'cat' => array('ACP_DOT_MODS')),
			),
		);
	}

	function install()
	{
	}

	function uninstall()
	{
	}
}

?>