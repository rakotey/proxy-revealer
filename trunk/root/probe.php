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
include($phpbb_root_path . 'includes/functions_user.' . $phpEx);

// Check minimum required parameters
if ( !isset($_GET['extra']) || !preg_match('/^[A-Za-z0-9,]*$/',trim($_GET['extra'])) )
{
	// since we're not user-facing, we don't care about debug messages
	die();
}

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup();

// Basic parameter data
$extra	= request_var('extra', '');
$mode = request_var('mode', '');
$user_agent = request_var('user_agent', '');
$vendor = request_var('vendor', '');
$version = request_var('version', '');
$orig_ip = request_var('ip', '');
$xml_ip = request_var('xml_ip', '');
$lan_ip = request_var('local', '');
$url = request_var('url', '');

// Get session id and associated key
list($sid,$key) = explode(',',trim($extra));

// Set Server URL that will be used to directly call this script later in various tests
$config['server_port'] = trim($config['server_port']);
$server_name = trim($config['server_name']);
$server_protocol = ($config['cookie_secure']) ? 'https://' : 'http://';
$server_port = ($config['server_port'] != 80) ? ':' .$config['server_port'] : '';
$path_name = '/' . preg_replace('/^\/?(.*?)\/?$/', '\1', trim($config['script_path']));
$path_name.= ($path_name != '') ? '/' : '';
$server_url = $server_protocol . $server_name . $server_port . $path_name;

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
* Log IPs and optionally block and/or ban the "fake' IP
*
* Inserts "real" and "fake" IPs in SPECULATIVE_TABLE, blocks and/or bans the "fake" IP session if configured to do so
* in admin_speculative.php, the first column shows the "fake IP address" and the third column shows the "real IP address".
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
	global $db, $user, $sid, $key, $config;

	/**
	* Validate IP address strings ... (Adapted from session_begin() in session.php)
	*
	* Check IPv4 first, the IPv6 is hopefully only going to be used very seldomly.
	* get_preg_expression() from includes/functions.php helps us match valid IPv4/IPv6 addresses only :)
	*/
	if ((!empty($ip_address) && !preg_match(get_preg_expression('ipv4'), $ip_address) && !preg_match(get_preg_expression('ipv6'), $ip_address)) ||
		(!empty($info) && !preg_match(get_preg_expression('ipv4'), $info) && !preg_match(get_preg_expression('ipv6'), $info)))
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

		// if neither the session_id or the session_speculative_key are valid (as would be revealed by $db->sql_numrows being 0),
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
			user_ban('ip', $ip_address, $ban_len, $ban_len_other, $ban_exclude, $ban_reason, $ban_give_reason);
		}
	}

	$sql = 'SELECT * FROM ' . SPECULATIVE_TABLE." 
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
* This pass concerns itself with the X-Forwarded-For header, which may be able to identify transparent http proxies.
* The X-Forwarded-For header might contain multiple addresses, comma+space separated, if the request was forwarded through multiple proxies.
* Example: "X-Forwarded-For: client1, proxy1, proxy2, proxy3"... For more info, see: http://en.wikipedia.org/wiki/X-Forwarded-For
*/ 
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
{
	// Adapted from session_begin() in includes/session.php
	$forwarded_for = (string) $_SERVER['HTTP_X_FORWARDED_FOR'];
	$forwarded_for = preg_replace('#, +#', ', ', $forwarded_for);

	// Split the list of IPs
	$ips = explode(', ', $forwarded_for);

	// Possible real address is the first IP in the $ips array ( $ips[0] ), the rest (if there are any) are most likely chained proxies
	if (!empty($ips[0]))
	{
		// We're only going to log the proxy IP from which the original request came ($user->ip), rather than loop through the list
		// of (possibly) chained proxies and log them if they don't match $ips[0], just to prevent possible abuse!
		if ($ips[0] != $user->ip)
		{
			insert_ip($user->ip,X_FORWARDED_FOR,$ips[0]);
		}
	}
}

switch ($mode)
{
	case 'reprobe':
		$sql = "UPDATE ".SESSIONS_TABLE." 
			SET session_speculative_test = -1 
			WHERE session_id = '" . $db->sql_escape($sid) . "' 
				AND session_speculative_key = '" . $db->sql_escape($key) . "'";
		$db->sql_query($sql);

	exit;
	// no break here

	case 'flash':
		$info = $user_agent .'<>'. $version;

		// $orig_ip represents our old "spoofed" address and $xml_ip represents our current "real" address.
		// if they're different, we've probably managed to break out of the HTTP proxy, so we log it.
		if ( $orig_ip != $xml_ip )
		{
			insert_ip($orig_ip,FLASH,$xml_ip,$info);
		}
		
	exit;
	// no break here

	case 'java':
		$info = $user_agent .'<>'. $vendor .'<>'. $version;

		// here, we're not trying to get the "real" IP address - we're trying to get the internal LAN IP address.
		if ( !empty($lan_ip) && $lan_ip != $user->ip )
		{
			insert_ip($user->ip,JAVA_INTERNAL,$lan_ip,$info);
		}

		// $orig_ip represents our old "spoofed" address and $user->ip represents our current "real" address.
		// if they're different, we've probably managed to break out of the HTTP proxy, so we log it.
		if ( $orig_ip != $user->ip )
		{
			insert_ip($orig_ip,JAVA,$user->ip,$info);
		}

	exit;
	// no break here

	case 'xss':
		header('Content-Type: text/html; charset=ISO-8859-1');

		$schemes = array('http','https'); // we don't want to save stuff like javascript:alert('test')

		$xss_info = $xss_glue = '';
		// we capture the url in the hopes that it'll reveal the location of the cgi proxy.  having the location gives us proof
		// that we can give to anyone (ie. it shows you how to make a post from that very same ip address)
		if ( !empty($_SERVER['HTTP_REFERER']) )
		{
			$parsed = parse_url($_SERVER['HTTP_REFERER']);
			// if one of the referers IP addresses are equal to the server, we assume they're the same.
			if ( !in_array($_SERVER['SERVER_ADDR'],gethostbynamel($parsed['host'])) && in_array($parsed['scheme'], $schemes) )
			{
				$xss_info = htmlspecialchars($_SERVER['HTTP_REFERER']);
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

		$java_url = $path_name . "probe.$phpEx?mode=java&amp;ip=$orig_ip&amp;extra=$sid,$key";

		// XML Socket Policy file server port
		$xmlsockd_port = 9999;
		// HttpRequest.swf is coded in AS3. Only in Flash 9.0.0 and newer is AS3 supported...
		// Note that swfobject only looks at the first three numbers (example: "9.0.124").
		// See: http://code.google.com/p/swfobject/wiki/api for more info
		$min_flash_ver = "9.0.0";
		$flash_vars = "dhost=$server_name&amp;dport=$xmlsockd_port&amp;flash_url=$server_url"."probe.$phpEx".
			"&amp;ip=$orig_ip&amp;extra=$sid,$key&amp;user_agent=".htmlspecialchars($_SERVER['HTTP_USER_AGENT']);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title></title>
<script type="text/javascript" src="swfobject.js"></script>
<script type="text/javascript">
swfobject.registerObject("flashContent", "<?php echo $min_flash_ver; ?>", "expressInstall.swf");
</script>
</head>

<body>
<div id="flashDIV">
  <object id="flashContent" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="1" height="1">
	<param name="movie" value="HttpRequestor.swf" />
    <param name="allowFullScreen" value="false" />
	<param name="loop" value="false" />
	<param name="menu" value="false" />
	<param name="FlashVars" value="<?php echo $flash_vars; ?>" />
	<!--[if !IE]>-->
	<object type="application/x-shockwave-flash" data="HttpRequestor.swf" width="1" height="1">
	<!--<![endif]-->
      <param name="allowFullScreen" value="false" />
	  <param name="loop" value="false" />
	  <param name="menu" value="false" />
	  <param name="FlashVars" value="<?php echo $flash_vars; ?>" />
	  <div>
		  <p align="center"><b>It is strongly recommended to install Adobe Flash Player for optimal browsing experience on this forum!</b></p>
		  <p align="center"><a href="http://www.adobe.com/go/getflashplayer">
		  <img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" />
		  </a></p>
		  <p align="center"><input type="submit" align="middle" value="Close" onClick='document.getElementById("flashPopup").style.display = "none"'></p>
	  </div>
	<!--[if !IE]>-->
	</object>
	<!--<![endif]-->
  </object>
</div>

<script type="text/javascript">
<?php // myPopupRelocate: Centers our popup to top window and keeps it centered on horizontal or vertical scrolling ?>
function myPopupRelocate() {
	var wt = window.top;
	var wtd = wt.document;
	var wtdb = wtd.body;
	var wtdde = wtd.documentElement;
	var myPopup = wtd.getElementById("flashPopup");
	<?php // sX = scrolledX and sY = scrolledY ?>
	var sX, sY;
	if( wt.pageYOffset ) { sX = wt.pageXOffset; sY = wt.pageYOffset; }
	else if( wtdde && wtdde.scrollTop ) { sX = wtdde.scrollLeft; sY = wtdde.scrollTop; }
	else if( wtdb ) { sX = wtdb.scrollLeft; sY = wtdb.scrollTop; }
	<?php // cX = centerX and cY = centerY ?>
	var cX, cY;
	if( wt.innerHeight ) { cX = wt.innerWidth; cY = wt.innerHeight; }
	else if( wtdde && wtdde.clientHeight ) { cX = wtdde.clientWidth; cY = wtdde.clientHeight; }
	else if( wtdb ) { cX = wtdb.clientWidth; cY = wtdb.clientHeight; }
	<?php // Calculate page center for our popup box size (size of flashPopup div in templates/subsilver/overall_footer.tpl) ?>
	var leftOffset = sX + (cX - 320) / 2; var topOffset = sY + (cY - 180) / 2;
	myPopup.style.top = topOffset + "px"; myPopup.style.left = leftOffset + "px";
}
<?php // Fire up our popup on window.top after our iframe finishes loading (if we don't have flash or have an older version of flash) ?>
window.onload = function() {
	var wt = window.top;
	var wtd = wt.document;
	var myPopup = wtd.getElementById("flashPopup");
	<?php // If we dont have $min_flash_ver (or at least at least 6.0.65), then we get expressInstall dialog to upgrade ?>
	<?php // If we don't have flash at all, then we get alternate content (prompt to install flash) ?>
	if ( !swfobject.hasFlashPlayerVersion("<?php echo $min_flash_ver; ?>") || !swfobject.hasFlashPlayerVersion("6.0.65") ) {
	  myPopup.innerHTML = document.getElementById("flashDIV").innerHTML;
	  myPopupRelocate();
	  myPopup.style.display = "block";
	  wtd.body.onscroll = myPopupRelocate;
	  wt.onscroll = myPopupRelocate;
	}
}
</script>

<applet width="0" height="0" code="HttpRequestor.class" codebase=".">
  <param name="domain" value="<?php echo $server_name; ?>">
  <param name="port" value="<?php echo $config['server_port']; ?>">
  <param name="path" value="<?php echo $java_url; ?>">
  <param name="user_agent" value="<?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT']); ?>">
</applet>
</body>
</html>
<?php

	exit;
	// no break here

	case 'utf16':
		header('Content-Type: text/html; charset=UTF-16');

		$javascript_url = $server_url . "probe.$phpEx?mode=xss&ip={$user->ip}&extra=$sid,$key";
		$iframe_url = htmlspecialchars($javascript_url);

		$str = <<<DEFAULT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <title></title>
</head>

<body>
<iframe src="$iframe_url" width="1" height="1" frameborder="0"></iframe>
<script>
  document.getElementsByTagName("iframe")[0].src = "$javascript_url&url="+escape(location.href);
</script>
</body>

</html>
DEFAULT;
		echo iso_8859_1_to_utf16($str);

	exit;
	// no break here

	case 'utf7':
		header('Content-Type: text/html; charset=UTF-7' . $$mode);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-7">
  <title></title>
</head>
<?php
		$javascript_url = $server_url . "probe.$phpEx?mode=xss&ip={$user->ip}&extra=$sid,$key";
		$iframe_url = htmlspecialchars($javascript_url);

		$str = <<<DEFAULT

<body>
<iframe src="$iframe_url" width="1" height="1" frameborder="0"></iframe>
<script>
  document.getElementsByTagName("iframe")[0].src = "$javascript_url&url="+escape(location.href);
</script>
</body>
</html>
DEFAULT;
		echo iso_8859_1_to_utf7($str);

	exit;
	// no break here
}

// Default page output when no $_GET vars other than "extra" is passed via URL
$base_url = $server_url . "probe.$phpEx?extra=$sid,$key&mode=";
$utf7_url = htmlspecialchars($base_url . 'utf7');
$utf16_url = htmlspecialchars($base_url . 'utf16');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <title></title>
</head>

<body>
<iframe src="<?php echo $utf7_url; ?>" width="1" height="1" frameborder="0"></iframe>
<iframe src="<?php echo $utf16_url; ?>" width="1" height="1" frameborder="0"></iframe>
</body>
</html>