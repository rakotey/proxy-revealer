<?php
/*
*
* @author TerraFrost < terrafrost@phpbb.com >,  jasmineaura < jasmine.aura@yahoo.com >
*
* @package proxy_revealer
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
			'version' => '0.3.4',
			'modes' => array(
				'external'	=> array('title' => 'ACP_PROXY_REVEALER_EXTERNAL', 'auth' => 'acl_a_board', 'cat' => array('ACP_PROXY_REVEALER')),
				'internal'	=> array('title' => 'ACP_PROXY_REVEALER_INTERNAL', 'auth' => 'acl_a_board', 'cat' => array('ACP_PROXY_REVEALER')),
				'settings'	=> array('title' => 'ACP_PROXY_REVEALER_SETTINGS', 'auth' => 'acl_a_board', 'cat' => array('ACP_PROXY_REVEALER')),
				'excludes'	=> array('title' => 'ACP_PROXY_REVEALER_EXCLUDES', 'auth' => 'acl_a_board', 'cat' => array('ACP_PROXY_REVEALER')),
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