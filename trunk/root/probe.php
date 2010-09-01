<?php
/**
*
* @author TerraFrost < terrafrost@phpbb.com >
* @author jasmineaura < jasmine.aura@yahoo.com >
*
* @package phpBB3
* @version $Id$
* @copyright (c) 2006 TerraFrost (c) 2008 jasmineaura
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/


/**
* @ignore
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// Check minimum required parameters
if ( !isset($_GET['extra']) || !preg_match('/^[A-Za-z0-9,]*$/',trim($_GET['extra'])) )
{
	// since we're not user-facing, we don't care about debug messages
	die();
}

// Start session management
$user->session_begin();
$auth->acl($user->data);

// Basic parameter data
$extra	= request_var('extra', '');
$mode = request_var('mode', '');

// Get session id and associated key
list($sid,$key) = explode(',',trim($extra));

// Set Some commonly used variables (Adapted from generate_board_url() in functions.php)
if ($config['force_server_vars'] || !($user->host))
{
	$server_protocol = ($config['server_protocol']) ? $config['server_protocol'] : (($config['cookie_secure']) ? 'https://' : 'http://');
	$server_name = $config['server_name'];
	$server_port = (int) $config['server_port'];
	$path_name = $config['script_path'];
}
else
{
	$server_protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
	$server_name = $user->host;
	$server_port = (!empty($_SERVER['SERVER_PORT'])) ? (int) $_SERVER['SERVER_PORT'] : (int) getenv('SERVER_PORT');
	$path_name = $user->page['root_script_path'];
}

// Add / to the end of $path_name if needed
$path_name .= (substr($path_name, -1, 1) != '/') ? '/' : '';
// Set Server URL
$server_url = generate_board_url() . '/';

/**
* Convert ISO 8859-1 (Latin-1) to UTF16
*
* according to <http://www.cl.cam.ac.uk/~mgk25/unicode.html>, "the [Universal Character Set] characters U+0000 to U+007F are identical to those in 
* US-ASCII (ISO 646 IRV) and the range U+0000 to U+00FF is identical to ISO 8859-1 (Latin-1)", where "ISO-8859-1 is (according to the standards at least)
* the default encoding of documents delivered via HTTP with a MIME type beginning with "text/"" <ref: http://en.wikipedia.org/wiki/ISO_8859-1#ISO-8859-1>
* (ie. the charset with which chr(0x80 - 0xFF) are most likely to be interpreted with).  since <http://tools.ietf.org/html/rfc2781#section-2> defines each character
* whose Universal Character Set value is equal to and lower than U+FFFF to be a "single 16-bit integer with a value equal to that of the character number",
* adding a chr(0x00) before each character should be sufficient to convert any string to UTF-16 (assuming the byte order mark is U+FEFF).
*/
function iso_8859_1_to_utf16($str)
{
	// the first two characters represent the byte order mark
	return chr(0xFE).chr(0xFF).chr(0).chunk_split($str, 1, chr(0));
}

/**
* Convert ISO 8859-1 (Latin-1) to UTF7
*
* according to <http://en.wikipedia.org/wiki/Base64#UTF-7>, "[UTF-7] is used to encode UTF-16 as ASCII characters for use in 7-bit transports such as SMTP".
* <http://betterexplained.com/articles/unicode/> provides more information.  in a departure from the method described there, everything, regardless of whether or
* not it's within the allowed U+0000 - U+007F range is encoded to base64.
*/
function iso_8859_1_to_utf7($str)
{
	return '+'.preg_replace('#=+$#','',base64_encode(substr(iso_8859_1_to_utf16($str),2))).'-';
}

/**
* Log IPs and optionally block and/or ban the "fake" IP
*
* Inserts "real" and "fake" IPs in SPECULATIVE_TABLE, blocks and/or bans the "fake" IP session if configured to do so
* in External IPs log, the first column shows the "fake IP address" and the third column shows the "real IP address".
* the reason we do it in this way is because when you're looking at the IP address of a post, you're going to see the "fake IP address".
*
* We use $db->sql_escape() in all our SQL statements rather than str_replace("\'","''",$_REQUEST['var']) on each var as it comes in.
* This is to avoid confusion and to avoid escaping the same text twice and ending up with too many backslshes in the final result.
*
* @param string $ip_address		The "fake" IP address.
* @param int $mode			The test mode used (modes defined in constants.php).
* @param string $info			The "real" IP address.
* @param string $secondary_info	Additional info such as browser/plugin info or CGI-Proxy URL(s), optional.
*/
function insert_ip($ip_address,$mode,$info,$secondary_info = '')
{
	global $phpbb_root_path, $phpEx;
	global $db, $user, $sid, $key, $config;

	/**
	* Validate IP address strings ... (Adapted from session_begin() in session.php)
	*
	* Check IPv4 first, the IPv6 is hopefully only going to be used very seldomly.
	* get_preg_expression() from includes/functions.php helps us match valid IPv4/IPv6 addresses only :)
	*/
	if ((!preg_match(get_preg_expression('ipv4'), $ip_address) && !preg_match(get_preg_expression('ipv6'), $ip_address)) ||
		(!preg_match(get_preg_expression('ipv4'), $info) && !preg_match(get_preg_expression('ipv6'), $info)))
	{
		// contains invalid data, return and don't log anything
		return;
	}

	/**
	* Validate IP length according to admin ... ("Session IP Validation" in ACP->Security Settings)
	*
	* session_begin() looks at $config['ip_check'] to see which bits of an IP address to check and so shall we.
	* First, check if both addresses are IPv6, else we assume both are IPv4 ($f_ip is the fake, $r_ip is the real)
	*/
	if (strpos($ip_address, ':') !== false && strpos($info, ':') !== false)
	{
		// short_ipv6() from includes/functions.php
		$f_ip = short_ipv6($ip_address, $config['ip_check']);
		$r_ip = short_ipv6($info, $config['ip_check']);
	}
	else
	{
		$f_ip = implode('.', array_slice(explode('.', $ip_address), 0, $config['ip_check']));
		$r_ip = implode('.', array_slice(explode('.', $info), 0, $config['ip_check']));
	}

	// If "Session IP Validation" is NOT set to None, and the validated length matches, we return and log nothing
	//  (see "Select ip validation" in includes/acp/acp_board.php for more info)
	if ($config['ip_check'] != 0 && $r_ip === $f_ip)
	{
		return;
	}

	/**
	* In Java, at least, there's a possibility that the main IP we're recording and the "masked" IP address are the same.
	* the reason this function would be called, in those cases, is to log $lan_ip.   $lan_ip, however, isn't reliable enough
	* to block people over (assuming any blocking is taking place).  As such, although we log it, we don't update phpbb_sessions.
	*/
	if ( $mode != JAVA_INTERNAL )
	{
		/**
		* session_speculative_test will eventually be used to determine whether or not this session ought to be blocked.
		* This check is done by performing a bitwise "and" against $config['ip_block'].  If the bits that represent the various
		* modes 'and' with any of the bits in the bitwise representation of session_speculative_test, a block is done.
		* To guarantee that each bit is unique to a specific mode, powers of two are used to represent the modes (see constants.php).
		*/
		$sql = 'UPDATE ' . SESSIONS_TABLE . " 
			SET session_speculative_test = session_speculative_test | " . $db->sql_escape($mode) . " 
			WHERE session_id = '" . $db->sql_escape($sid) . "' 
				AND session_speculative_key = '" . $db->sql_escape($key) . "'";

		if ( !($result = $db->sql_query($sql)) )
		{
			die();
		}

		// if neither the session_id or the session_speculative_key are valid (as would be revealed by $db->sql_affectedrows being 0),
		// we assume the information is not trustworthy and quit.
		if ( !$db->sql_affectedrows($result) )
		{
			die();
		}

		// Ban if appropriate
		if ( $config['ip_ban'] && ($mode & $config['ip_block']) )
		{
			// $ban_len takes precedence over $ban_len_other unless $ban_len is set to "-1" (other - until $ban_len_other)
			// see function user_ban() in functions_user.php for more info
			$ban_len			= $config['ip_ban_length'];
			$ban_len_other		= $config['ip_ban_length_other'];
			$ban_exclude		= 0;
			$ban_reason			= $config['ip_ban_reason'];
			$ban_give_reason	= $config['ip_ban_give_reason'];

			// user_ban() function from includes/functions_user.php
			include($phpbb_root_path . 'includes/functions_user.' . $phpEx);
			user_ban('ip', $ip_address, $ban_len, $ban_len_other, $ban_exclude, $ban_reason, $ban_give_reason);
		}
	}

	$sql = 'SELECT * FROM ' . SPECULATIVE_TABLE . " 
		WHERE ip_address = '" . $db->sql_escape($ip_address) . "' 
			AND method = " . $db->sql_escape($mode) . " 
			AND real_ip = '" . $db->sql_escape($info) . "'";
	$result = $db->sql_query($sql);

	if ( !$row = $db->sql_fetchrow($result) )
	{
		$secondary_info = ( !empty($secondary_info) ) ? "$secondary_info" : 'NULL';

		$sql_ary = array(
				'ip_address'	=> $ip_address,
				'method'		=> $mode,
				'discovered'	=> time(),
				'real_ip'		=> $info,
				'info'			=> $secondary_info
		);

		$sql = 'INSERT INTO ' . SPECULATIVE_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary);
		$db->sql_query($sql);
	}
}

/**
* Check IP address against DNS-based lists of Open HTTP/SOCKS Proxies
*
* This function only checks DNSBLs which list Open HTTP/SOCKS Proxies, not spammers or open smtp relays, etc..
* For more info, see: http://en.wikipedia.org/wiki/Comparison_of_DNS_blacklists
*
* @param string $check_ip		The IP address to check against the list or Tor exit-node IPs
*/
function proxy_dnsbl_check($check_ip)
{
	// proxies.dnsbl.sorbs.net is an aggregate zone for (http|socks|misc).dnsbl.sorbs.net
	$dnsbl_check = array(
		'proxies.dnsbl.sorbs.net'	=> 'http://www.de.sorbs.net/lookup.shtml?',
		'web.dnsbl.sorbs.net'		=> 'http://www.de.sorbs.net/lookup.shtml?',
		'xbl.spamhaus.org'			=> 'http://www.spamhaus.org/query/bl?ip=',
	);

	$reverse_ip = implode('.', array_reverse(explode('.', $check_ip)));
	$listed = false;
	$info_ary = array();

	foreach ($dnsbl_check as $dnsbl => $lookup)
	{
		if (phpbb_checkdnsrr($reverse_ip . '.' . $dnsbl . '.', 'A') === true)
		{
			$info_ary[] = $lookup . $check_ip;
			$listed = true;
		}
	}

	if ($listed)
	{
		$info = implode('<>', array_unique($info_ary));
		insert_ip($check_ip,PROXY_DNSBL,"0.0.0.0",$info);
	}
}

/**
* Check IP address against DNS-based list of Tor exit-nodes
*
* Since Tor supports exit policies, a network service's Tor exit list is a function of its IP address and port.
* Unlike with traditional DNSxLs, services need to provide that information in their queries.
* For more info, see: https://www.torproject.org/tordnsel/
*
* @param string $check_ip		The IP address to check against the list or Tor exit-node IPs
*/
function tor_dnsel_check($check_ip)
{
	global $config, $db, $sid, $key, $server_port;

	// See tordnsel link above and https://svn.torproject.org/svn/torstatus/trunk/web/index.php
	$tordnsel = "ip-port.exitlist.torproject.org";

	$server_ip = (string) $_SERVER['SERVER_ADDR'];
	$query_remote_ip = implode('.', array_reverse(explode('.', $check_ip)));
	$query_server_ip = implode('.', array_reverse(explode('.', $server_ip)));
	$tordnsel_check = gethostbyname("$query_remote_ip.$server_port.$query_server_ip.$tordnsel");

	if ($tordnsel_check == "127.0.0.2")
	{
		insert_ip($check_ip,TOR_DNSEL,"0.0.0.0");
	}
}

/**
* Check the X-Forwarded-For header contents, and log/block the possible "real" IP if different
*
* The X-Forwarded-For header might contain multiple addresses, comma+space separated, if the request was forwarded through multiple proxies.
* Example: "X-Forwarded-For: client1, proxy1, proxy2, proxy3"... For more info, see: http://en.wikipedia.org/wiki/X-Forwarded-For
*
* @param string $check_ip		The IP address to compare against the IP found in the HTTP_X_FORWARDED_FOR header
*/ 
function x_forwarded_check($check_ip)
{
	// Adapted from session_begin() in includes/session.php
	$forwarded_for = (string) $_SERVER['HTTP_X_FORWARDED_FOR'];
	$forwarded_for = preg_replace('#, +#', ', ', $forwarded_for);

	// Split the list of IPs
	$ips = explode(', ', $forwarded_for);

	// Possible real address is the first IP in the $ips array ( $ips[0] ), the rest (if there are any) are most likely chained proxies
	if (!empty($ips[0]))
	{
		// We're only going to log the proxy IP from which the original request came, rather than loop through the list
		// of (possibly) chained proxies and log them if they don't match $ips[0], just to prevent possible abuse!
		if ($ips[0] != $check_ip)
		{
			insert_ip($check_ip,X_FORWARDED_FOR,$ips[0]);
		}
	}
}

/**
* Track user's IP using a Cookie
*/
function ip_cookie_check()
{
	global $config, $user;

	if (isset($_COOKIE[$config['cookie_name'] . '_ipt']))
	{
		$cookie_ip = request_var($config['cookie_name'] . '_ipt', '', false, true);

		// $user->ip represents our current address and $cookie_ip represents our possibly "real" address.
		// if they're different, we've probably managed to break out of the proxy, so we log it.
		if ( $user->ip != $cookie_ip )
		{
			insert_ip($user->ip,COOKIE,$cookie_ip);
		}
	}
	else
	{
		$hours = (isset($config['ip_cookie_age'])) ? $config['ip_cookie_age'] : 6;
		$cookie_expire = time() + ($hours * 3600);
		$user->set_cookie('ipt', $user->ip, $cookie_expire);
	}
}

/**
* This is where all of the action happens.
*
* reprobe:		called via an iframe from overall_header if "require javascript" is enabled and used to restart tests when user enables javascript.
* flash:		called when the flash plugin connects back to the server with useful information such as xml_ip (detected real ip) and plugin info.
* java:		called when the java applet directly connects back to the server so we can log the IP of the direct connection (and perhaps lan_ip).
* misc:		called via an iframe from overall_header. Does Tor/X_FORWARDED_FOR/Cookie tests and embeds the Flash and Java applet.
* real_html:		called via an iframe from "misc" (above). Uses UTF-16 encoding method for the realplayer embed to avoid issues with CGI-Proxies.
* realplayer:	called when the realplayer plugin directly connects back to the server so we can log the IP (then redirect to a tiny rm file to play)
* utf7 & utf16	called via iframes from default page output here when no $_GET vars other than "extra" is passed (see the end of this script).
* xss:			called via an iframe from overall_header as well as the utf7 & utf16 iframes loaded from default page output here.
*/
switch ($mode)
{
	case 'reprobe':
		$sql = 'UPDATE ' . SESSIONS_TABLE . " 
			SET session_speculative_test = -1 
			WHERE session_id = '" . $db->sql_escape($sid) . "' 
				AND session_speculative_key = '" . $db->sql_escape($key) . "'";
		$db->sql_query($sql);
	exit;
	// no break here

	case 'flash':
		$orig_ip = request_var('ip', '');
		$user_agent = request_var('user_agent', '');
		$version = request_var('version', '');
		$xml_ip = request_var('xml_ip', '');
		$info = $user_agent .'<>'. $version;

		// $orig_ip represents our old "spoofed" address and $xml_ip represents our current "real" address.
		// if they're different, we've probably managed to break out of the proxy, so we log it.
		if ( $orig_ip != $xml_ip )
		{
			insert_ip($orig_ip,FLASH,$xml_ip,$info);
		}
	exit;
	// no break here

	case 'java':
		$lan_ip = request_var('local', '');
		$orig_ip = request_var('ip', '');
		$user_agent = request_var('user_agent', '');
		$vendor = request_var('vendor', '');
		$version = request_var('version', '');
		$info = $user_agent .'<>'. $vendor .'<>'. $version;

		// here, we're not trying to get the "real" IP address - we're trying to get the internal LAN IP address.
		if ( !empty($lan_ip) && $lan_ip != $user->ip )
		{
			insert_ip($user->ip,JAVA_INTERNAL,$lan_ip,$info);
		}

		// $orig_ip represents our old "spoofed" address and $user->ip represents our current "real" address.
		// if they're different, we've probably managed to break out of the proxy, so we log it.
		if ( $orig_ip != $user->ip )
		{
			insert_ip($orig_ip,JAVA,$user->ip,$info);
		}
	exit;
	// no break here

	case 'misc':

		/**
		* Flash, Java and RealPlayer plugins embedding begins here
		*/
		$defer = request_var('defer', 0);
		$java_url = $path_name . "probe.$phpEx?mode=java&amp;ip={$user->ip}&amp;extra=$sid,$key";
		// XML Socket Policy file server port (For Flash)
		$xmlsockd_port = 9999;
		$flash_vars = "dhost=$server_name&amp;dport=$xmlsockd_port&amp;flash_url=$server_url"."probe.$phpEx".
			"&amp;ip={$user->ip}&amp;extra=$sid,$key&amp;user_agent={$user->browser}";
		$real_html_url = $server_url . "probe.$phpEx?mode=real_html&amp;extra=$sid,$key";

		// If the buffer is not set to 0, there's no need to call ob_start(), because the buffer is started already.
		// Calling it again will cause a second level of buffering to start and the script won't work.
		// This is to avoid problems if output buffering is already enabled server-wide in php.ini
		if (ob_get_level() == 0)
		{
			ob_start();
		}

		echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
			<html>
			<head><title></title>';

		if (!((int) $defer & FLASH))
		{
			echo '
			<script type="text/javascript" src="swfobject.js"></script>
			<script type="text/javascript">
			swfobject.registerObject("flashContent", "9.0.0", "expressInstall.swf");
			</script>';
		}

		echo "\n</head>\n<body>\n";

		if (!((int) $defer & FLASH))
		{
			echo '
			<div id="flashDIV">
			  <object id="flashContent" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="1" height="1">
				<param name="movie" value="HttpRequestor.swf" /><param name="loop" value="false" /><param name="menu" value="false" />
				<param name="FlashVars" value="' . $flash_vars . '" />
				<!--[if !IE]>-->
				<object type="application/x-shockwave-flash" data="HttpRequestor.swf" width="1" height="1">
				<!--<![endif]-->
			      <param name="loop" value="false" /><param name="menu" value="false" />
				  <param name="FlashVars" value="' . $flash_vars . '" />
				  <div>
					<p align="center"><b>It is strongly recommended to install Adobe Flash Player for optimal browsing experience on this forum!</b></p>
					<p align="center"><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></p>
					<p align="center"><input type="submit" align="middle" value="Close" onClick=\'document.getElementById("flashPopup").style.display = "none"\'></p>
				  </div>
				<!--[if !IE]>-->
				</object>
				<!--<![endif]-->
			  </object>
			</div>
			<script type="text/javascript">
			function myPopupRelocate(){var wt=window.top;var wtd=wt.document;var wtdb=wtd.body;var wtdde=wtd.documentElement;var myPopup=wtd.getElementById("flashPopup");var sX, sY, cX, cY;if(wt.pageYOffset){sX=wt.pageXOffset;sY=wt.pageYOffset;}else if(wtdde&&wtdde.scrollTop){sX=wtdde.scrollLeft;sY=wtdde.scrollTop;}else if(wtdb){sX=wtdb.scrollLeft;sY=wtdb.scrollTop;}if(wt.innerHeight){cX=wt.innerWidth;cY=wt.innerHeight;}else if(wtdde&&wtdde.clientHeight){cX=wtdde.clientWidth;cY=wtdde.clientHeight;}else if(wtdb){cX=wtdb.clientWidth;cY=wtdb.clientHeight;}var leftOffset=sX+(cX-320)/2;var topOffset=sY+(cY-180)/2;myPopup.style.top=topOffset+"px";myPopup.style.left=leftOffset+"px";}window.onload=function(){var wt=window.top;var wtd=wt.document;var myPopup=wtd.getElementById("flashPopup");if(!swfobject.hasFlashPlayerVersion("9.0.0")||!swfobject.hasFlashPlayerVersion("6.0.65")){myPopup.innerHTML=document.getElementById("flashDIV").innerHTML;myPopupRelocate();myPopup.style.display="block";wtd.body.onscroll=myPopupRelocate;wt.onscroll=myPopupRelocate;}}
			</script>';
		}

		if (!((int) $defer & JAVA))
		{
			echo '
			<applet width="0" height="0" archive="HttpRequestor.jar" code="HttpRequestor.class">
			  <param name="proto" value="' . $server_protocol . '">
			  <param name="domain" value="' . $server_name . '">
			  <param name="port" value="' . $server_port . '">
			  <param name="path" value="' . $java_url . '">
			  <param name="user_agent" value="' . $user->browser . '">
			</applet>';
		}

		if (!((int) $defer & REALPLAYER))
		{
			// Detect RealPlayer Plugin in Netscape/Mozilla browsers using Javascript, or the ActiveX Control in IE using VBScript.
			// If found, we load it using javascript to avoid browsers that don't have the plugin intalled so they don't get prompted to install it :)
			echo '
			<script type="text/javascript">
			function detectReal(){var p="RealPlayer";var found=false;var np=navigator.plugins;if(np&&np.length>0){var length=np.length;for(cnt=0;cnt<length;cnt++){if((np[cnt].name.indexOf(p)>=0)||(np[cnt].description.indexOf(p)>=0)){found=true;break;}}}if(!found&&VB){found=(detectAX("rmocx.RealPlayer G2 Control")||detectAX("RealPlayer.RealPlayer(tm) ActiveX Control (32-bit)")||detectAX("RealVideo.RealVideo(tm) ActiveX Control (32-bit)"));}return found;}var VB=false;var nua=navigator.userAgent;var d=document;if((nua.indexOf("MSIE")!=-1)&&(nua.indexOf("Win")!=-1)){d.writeln(\'<script language="VBscript">\');d.writeln("VB=False");d.writeln("If ScriptEngineMajorVersion>=2 then");d.writeln("  VB=True");d.writeln("End If");d.writeln("Function detectAX(axName)");d.writeln(" on error resume next");d.writeln(" detectAX=False");d.writeln(" If VB Then");d.writeln("  detectAX=IsObject(CreateObject(axName))");d.writeln(" End If");d.writeln("End Function");d.writeln("</scr" + "ipt>");}
			var hasReal=detectReal();
			if(hasReal){
			  document.writeln(\'<iframe src="'. $real_html_url . '" width="1" height="1" frameborder="0"></iframe>\');
			}
			</script>';
		}

		echo '
			</body>
			</html>';

		// Immediately send the contents of the output buffer and turn it off, since we're done outputting data to the browser.
		ob_end_flush();

		/**
		* Check if user's IP is listed as an Open HTTP/SOCKS Proxy in DNSBL's
		*/
		if (!((int) $defer & PROXY_DNSBL))
		{
			proxy_dnsbl_check($user->ip);
		}

		/**
		* Check if user's IP is listed as a Tor exit-node IP in TorDNSEL
		*/
		if (!((int) $defer & TOR_DNSEL))
		{
			tor_dnsel_check($user->ip);
		}

		/**
		* Check the X-Forwarded-For header, which may be able to identify transparent http proxies.
		*/ 
		if (!((int) $defer & X_FORWARDED_FOR))
		{
			if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
			{
				x_forwarded_check($user->ip);
			}
		}

		/**
		* Check the IPT (IP Tracking) Cookie
		*/
		if (!((int) $defer & COOKIE))
		{
			ip_cookie_check();
		}

	exit;
	// no break here

	case 'real_html':
		// Firefox on *ubuntu w/ gecko-mediaplayer and/or realplayer doesn't load if rtsp:// link directly in src
		// so we start over http (to send .ram file that redirects realplayer to rtsp:// link, to guarantee it loads for everyone
		$src_url = $server_url . "probe.$phpEx?mode=$mode&amp;ram=1&amp;ip={$user->ip}&amp;extra=$sid,$key"
			. "&amp;user_agent={$user->browser}";

		// This will be sent as contents of the redirect.ram file (so don't html-entitize it - &'s remain &'s)
		$rtsp_url = "rtsp://$server_name:$server_port" . $path_name
			. "probe.$phpEx?mode=realplayer&ip={$user->ip}&extra=$sid,$key"
			. "&user_agent={$user->browser}";

		if (isset($_GET['ram']))
		{
			// On Linux, at least, official realplayer connects directly at this stage (http), so log it
			if (isset($_GET['ip']) && $_GET['ip'] != $user->ip)
			{
				$orig_ip = request_var('ip', '');
				$user_agent = request_var('user_agent', '');
				$info = $user_agent .'<>'. $user->browser;
				insert_ip($orig_ip,REALPLAYER,$user->ip,$info);
			}
			// Send redirect.ram file containing rtsp link
			header('Content-Type: application/octet-stream');
			header('Content-disposition: attachment; filename=redirect.ram');
			header('Content-Transfer-Encoding: binary');
			header('Cache-Control: no-cache');
			header('Pragma: no-cache');
			header('Content-Length: ' . strlen($rtsp_url));
			ob_clean();
			flush();
			echo $rtsp_url;
			exit;
		}

		// We use the UTF-16 encoding method so that CGI-Proxies can't load the object/embed and cause realplayer plugin errors,
		// and also so they can't see the rtsp:// URL and try to rewrite it and make Firefox trip out :p
		header('Content-Type: text/html; charset=UTF-16');

		$str = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
			<html>
			<head><title></title></head>
			<body>
			<object id="realplayer" classid="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA" height="1" width="1">
			  <param name="controls" value="ImageWindow">
			  <param name="autostart" value="true">
			  <param name="src" value="'.$src_url.'">
			  <embed height="1" width="1" controls="ImageWindow" src="'.$src_url.'" type="audio/x-pn-realaudio-plugin" autostart="true"></embed>
			</object>
			</body>
			</html>';
		echo iso_8859_1_to_utf16($str);
	exit;
	// no break here

	case 'realplayer':
		// Here, RealPlayer plugin is connecting directly to the server, thinking it's an RTSP server :-)
		$orig_ip = request_var('ip', '');
		$user_agent = request_var('user_agent', '');
		$info = $user_agent .'<>'. $user->browser;

		// $orig_ip represents our old "spoofed" address and $user->ip represents our current "real" address.
		// if they're different, we've probably managed to break out of the proxy, so we log it.
		if ( $orig_ip != $user->ip )
		{
			insert_ip($orig_ip,REALPLAYER,$user->ip,$info);
		}

		// Redirect back to http -  to a single-frame .rm (real video) file. This is to avoid plugin popping up an error that it couldn't play :-)
		$video_url = $server_url . "sample.rm";
		header("Location: $video_url");
	exit;
	// no break here

	case 'xss':
		$orig_ip = request_var('ip', '');
		$url = request_var('url', '');
		$schemes = array('http','https'); // we don't want to save stuff like javascript:alert('test')
		$xss_info = $xss_glue = '';

		header('Content-Type: text/html; charset=ISO-8859-1');

		// we capture the url in the hopes that it'll reveal the location of the cgi proxy.  having the location gives us proof
		// that we can give to anyone (ie. it shows you how to make a post from that very same ip address)
		if ( !empty($user->referer) )
		{
			$parsed = parse_url($user->referer);
			// if one of the referers IP addresses are equal to the server, we assume they're the same.
			if ( !in_array($_SERVER['SERVER_ADDR'],gethostbynamel($parsed['host'])) && in_array($parsed['scheme'], $schemes) )
			{
				$xss_info = $user->referer;
				$xss_glue = '<>';
			}
		}

		if ( !empty($url) )
		{
			$parsed = parse_url($url);
			// if one of the referers IP addresses are equal to the server, we assume they're the same.
			if ( !in_array($_SERVER['SERVER_ADDR'],gethostbynamel($parsed['host'])) && in_array($parsed['scheme'], $schemes) )
			{
				$xss_info2 = $url;
				$xss_info = ( $xss_info != $xss_info2 ) ? "{$xss_info}{$xss_glue}{$xss_info2}" : $xss_info;
			}
		}

		// $orig_ip represents our old "spoofed" address and $user->ip represents our current "real" address.
		// if they're different, we've probably managed to break out of the CGI proxy, so we log it.
		if ( $orig_ip != $user->ip )
		{
			insert_ip($orig_ip,XSS,$user->ip,$xss_info);
		}

	exit;
	// no break here

	// UTF-16 is a multibyte encoding. Two bytes represent one character. Web-proxies are not aware of this type of encoding.
	// Some (ex. Glype) prepend an ISO-8859-1 string to the top of the UTF-16 encoded page and then pass the original
	// Content-Type (UTF-16) to the browser. If the added string length is odd-numbered, all subsequent groupings will be off by one.
	// Also from testing (FF-3.6 & IE-8), the string length varies depending on the browser that the proxy sees, so it is very likely
	// that one method works for one browser and not the other, and vice-verse. Hence why we need to do both.
	// UTF16 for even-numbered headers and UTF16-2 for odd-numbered headers (Glype proxies header mess)
	case 'utf16':
		header('Content-Type: text/html; charset=UTF-16');

		$javascript_url = $server_url . "probe.$phpEx?mode=xss&ip={$user->ip}&extra=$sid,$key";
		$iframe_url = htmlspecialchars($javascript_url);

		$str = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
			<html>
			<head><title></title></head>
			<body>
			<iframe id="xss_probe" src="' . $iframe_url . '" width="1" height="1" frameborder="0"></iframe>
			<script type="text/javascript">
				document.getElementById("xss_probe").src = "'. $javascript_url . '&url="+escape(location.href);
			</script>
			</body>
			</html>';
		echo iso_8859_1_to_utf16($str);
	exit;
	// no break here

	// Works for Firefox-2.x/3.x, IE-8 (IE-7 doomed by framebug)
	case 'utf7':
		header('Content-Type: text/html; charset=UTF-7');

		$javascript_url = $server_url . "probe.$phpEx?mode=xss&ip={$user->ip}&extra=$sid,$key";
		$iframe_url = htmlspecialchars($javascript_url);

		echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
			<html>
			<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-7"><title></title></head>';

		$str = '
			<body>
			<iframe id="xss_probe" src="'. $iframe_url . '" width="1" height="1" frameborder="0"></iframe>
			<script type="text/javascript">
				document.getElementById("xss_probe").src = "' . $javascript_url . '&url="+escape(location.href);
			</script>
			</body>
			</html>';
		echo iso_8859_1_to_utf7($str);
	exit;
	// no break here

	case 'quirks':
		// "quirks" is loaded directly from overall_header.html (unlike utf*_iframe's which are loaded from within probe.php) with the
		// ip and url (which were used to get the header) in the url bringing us here.  This is to remedy an issue where a CGI proxy
		// doesn't convert over the URLs of the utf*_iframes (which are loaded inside main_iframe in the header) to make sure that
		// we pass the *masked* IP address to the "xss_probe" url, at least once.
		$orig_ip = request_var('ip', '');
		$orig_url = request_var('url', '');
		$javascript_url = $server_url."probe.$phpEx?mode=xss&ip=$orig_ip&extra=$sid,$key";
		$iframe_url = htmlspecialchars($javascript_url);
		$iframe_src = $iframe_url . !empty($orig_url) ? "&amp;url=$orig_url" : '';
		// -moz-binding only works in FireFox (and browsers using the gecko rendering engine?), and "expression" works only in IE.
		// Glype currently strips out the ending letter "l" from "xss.xml" causing a 404 request for "xss.xm", so the extra backslash
		// between 'xss.xml' and '#xss' is a workaround.
		$moz_binding_url = $server_url . 'xss.xml\#xss';
		// At this point, we don't really care about valid HTML, because here my friend are loads of *intentional* invalidities, lol :)
		// Think of these quirks as sort of like "CSS Hacks", except for evil purposes :)
?>
<html><head><title></title></head>
<body>
<iframe id="xss_probe" src="<?php echo $iframe_src; ?>" url="<?php echo $iframe_url; ?>" width="1" height="1" frameborder="0"></iframe>

<?php //This basic js is a backup plan, in case none of the "quirks" to follow will work, and if the CGI-proxy is not removing scripts ?>
<script type="text/javascript">
var xssObj = document.getElementById("xss_probe");
xssObj.src = "<?php echo $javascript_url; ?>&url="+escape(location.href);
</script>

<?php // Quirks - some quirky-sneaky stuff >:) ?>
<!--[if IE]>
<xss style="xss:expr/**/ession(if(this.x!='x'){document.getElementById('xss_probe').sr/**/c='<?php echo $iframe_url; ?>';this.x='x';})" x=""></xss>
<![endif]-->
<![if ! IE]>
<xss style="-moz-binding:url('<?php echo $moz_binding_url; ?>');"></xss>
<![endif]>
</body>
</html>
<?php
		// todo: add more quirkiness
		// end mode: quirks
	exit;
	// no break here
}

/**
* Default page output when no $_GET vars other than "extra" is passed via URL
*/
$base_url = $server_url . "probe.$phpEx?extra=$sid,$key&amp;mode=";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <title></title>
</head>
<body>
<iframe id="utf7_iframe" src="<?php echo $base_url . 'utf7'; ?>" width="1" height="1" frameborder="0"></iframe>
<iframe id="utf16_iframe" src="<?php echo $base_url . 'utf16'; ?>" width="1" height="1" frameborder="0"></iframe>
</body>
</html>