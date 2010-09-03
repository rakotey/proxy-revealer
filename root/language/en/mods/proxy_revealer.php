<?php
/**
*
* proxy_revealer [English]
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

// Proxy Revealer Olympus Internal and External IP Log
$lang = array_merge($lang, array(
	'SPECULATIVE_IP_EXTERNAL'	=> 'Someone who hasn’t taken care in masking their IP address risks revealing it through any number of mechanisms - mechanisms that although not entirely fool-proof, themselves, should be sufficient to “catch” the average IP-masker.  You can also view “%sInternal IP Addresses%s” or “%sDisable Scanning%s” for certain IP address (helpful if you’re running your own proxy and are already logging stuff through that).',

	'SPECULATIVE_IP_INTERNAL'	=> 'The IP addresses on this page are, in most cases, going to be ones that only appear on LANs (10.*.*.*, 192.168.*.*, etc).  Under rare circumstances, external IP addresses may show up (eg. someone is using a http proxy and is plugged directly into their cable modem).  You can also view (purely) “%sExternal IP Addresses%s”.',

	'PLUGIN_DESC'				=> 'Extended %s Plugin Information',
	'PLUGIN_VERSION'			=> '%s Version',

	'SPOOFED_IP'				=> 'Masked IP Address',
	'METHOD_USED'				=> 'Method Used',
	'REAL_IP'					=> 'Real IP Address',
	'METHOD_USED_EXPLAIN'		=> 'to detect the spoofing',
	'REAL_IP_EXPLAIN'			=> 'or atleast the best guess',
	'EXTERNAL_IP'				=> 'External IP Address',
	'INTERNAL_IP'				=> 'Internal IP Address',
	'VIEW_LIST'					=> 'View Complete List',
	'SEARCH_FOR'				=> 'Search For',
	'MOST_RECENT'				=> 'Most Recent',
	'LEAST_RECENT'				=> 'Least Recent',
	'SHOW'						=> 'Show',
	'SORT'						=> 'Sort',
	'DATE'						=> 'Date',
	'USER_AGENT'				=> 'User-Agent',
	'IP_WHOIS_FOR'				=> 'IP whois for %s',
	'PROXY_DNSBL_URL'			=> 'DNSBL IP Query URL',
	'XSS_URL'					=> 'Web Proxy URL',
));

// Proxy Revealer Olympus Settings
$lang = array_merge($lang, array(
	'PROXY_REVEALER_EXPLAIN'	=> 'Attempts to determine someone’s “real” IP address - whenever possible, using a myriad of techniques, and “blocks” such people. Blocking is done within the confines of the “Session IP Validation” setting (“Security Settings” -> “Server Configuation”). Please DO NOT block with X-Forwarded-For or Cookie unless you know what you’re doing! They’ll still log possible masked/real IPs which you can manually ban later if you like.',

	'PRO_MOD_ON'			=> 'Proxy Revealer Enabled',
	'PRO_MOD_ON_EXPLAIN'	=> 'Setting this to “no” will completely disable this MOD.',

	'DAYS'					=> 'Days',
	'HOURS'					=> 'Hours',
	'IP_COOKIE_AGE'			=> 'IP-tracking Cookie Age',
	'IP_COOKIE_AGE_EXPLAIN'	=> 'How long before cookie expires. Keep this low to avoid false positives, as some users’ IPs may change often. If you block with this method (not advised,) it’s wise to set “Session IP Validation” to A.B.C or even A.B only.',
	'IP_FLASH_ON'			=> 'Flash Method Enabled',
	'IP_FLASH_ON_EXPLAIN'	=> 'This enables the Flash detection method. If you’ll be unable to run the xmlsockd daemon script, you might as well disable it (to prevent unecessary loading of the Flash plugin).',
	'IP_FLASH_PORT'			=> 'Flash xmlsockd Port',
	'IP_FLASH_PORT_EXPLAIN'	=> 'This is needed to tell the Flash plugin what port to connect on, to <a href="http://www.adobe.com/devnet/flashplayer/articles/flash_player9_security_update.html#socket_policy">authorize a xmlsocket connection</a> back to the server. Default script port is 9999.',
	'IP_MASK_BAN'			=> 'IP Masking Ban',
	'IP_MASK_BAN_EXPLAIN'	=> 'Permanently bans the proxy IPs used by people who were blocked by the above detection methods.',
	'IP_MASK_BLOCK'			=> 'IP Masking Block',
	'IP_MASK_BLOCK_EXPLAIN'	=> 'Select which detection methods you would like to have block users. Users are blocked for the duration of their session.',
	'IP_MASK_PRUNE'			=> 'Masked IP Age Limit',
	'IP_MASK_PRUNE_EXPLAIN'	=> 'Determines when masked IPs will be automatically deleted.  Leave blank (or 0) to disable.',
	'IP_REQUIRE_JS'			=> 'Require Javascript enabled',
	'IP_REQUIRE_JS_EXPLAIN'	=> 'This ensures that Web-Proxy URLs get logged by XSS, Flash auto-loads in IE6/7 & Opera9+, old/no flash is detected, RealPlayer plugin detection/test occurs, and Firefox users with “NoScript” are forced to “Allow this site” (enables Java/Flash).',
	'IP_SCAN_DEFER'			=> 'Defer Scan Methods',
	'IP_SCAN_DEFER_EXPLAIN'	=> 'This will defer scanning methods till login (or registration) which allows for excluding certain users (ex. to allow a user to use Tor, check “Tor DNSEL” here and add them in “Exceptions”).',

));

// Proxy Revealer Olympus Exceptions - These are similar to 'IP_BAN', 'IP_UNBAN', 'IP_NO_BANNED', 'BAN_UPDATE_SUCCESSFUL', etc.
$lang = array_merge($lang, array(
	'SPECULATIVE_IP_EXCLUDE'	=> 'If you’re running your own proxies, you might prefer to use those proxies logs over the logs this MOD produces. So add them here to exclude them from scanning. Alternatively, you may wish to exclude certain users from scanning/blocking by certain tests. So add their username here and make sure the appropriate scanning method is set to deferred in “Settings”.',

	'ADD_IP'					=> 'Add one or more IP addresses or hostnames',
	'ADD_IP_EXPLAIN'			=> 'To specify several different IPs or hostnames enter each on a new line. To specify a range of IP addresses separate the start and end with a hyphen (-), to specify a wildcard use “*”.',
	'ADD_USER'					=> 'Add one or more usernames',
	'ADD_USER_EXPLAIN'			=> 'You can add/exclude multiple users in one go by entering each name on a new line. Use the <span style="text-decoration: underline;">Find a member</span> facility to look up and add one or more users automatically.',
	'EXCLUDE_UPDATE_SUCCESSFUL'	=> 'The exception list has been updated successfully',
	'NO_IP' 					=> 'No IP addresses',
	'NO_USER'					=> 'No usernames',
	'REMOVE_IP'					=> 'Remove one or more IP addresses',
	'REMOVE_IP_EXPLAIN'			=> 'You can remove multiple IP addresses in one go using the appropriate combination of mouse and keyboard for your computer and browser',
	'REMOVE_USER'				=> 'Remove one or more usernames',
	'REMOVE_USER_EXPLAIN'		=> 'You can remove multiple users in one go using the appropriate combination of mouse and keyboard for your computer and browser',
	'IP_HOSTNAME'				=> 'IP addresses or hostnames',
	'USERNAME'					=> 'Username',
));

// Proxy Revealer Olympus Common words - Not sure why anyone would want to translate the following, but whatever.
$lang = array_merge($lang, array(
	'COOKIE'			=> 'Cookie',
	'FLASH'				=> 'Flash',
	'JAVA'				=> 'Java',
	'PROXY_DNSBL'		=> 'Proxy-DNSBL',
	'QUICKTIME'			=> 'QuickTime',
	'REALPLAYER'		=> 'RealPlayer',
	'TOR_DNSEL'			=> 'Tor-DNSEL',
	'WMPLAYER'			=> 'WMPlayer',
	'X_FORWARDED_FOR'	=> 'X-Forwarded-For',
	'XSS'				=> 'XSS',
));

?>