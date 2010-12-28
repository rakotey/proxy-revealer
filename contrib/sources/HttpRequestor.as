// HttpRequestor.as (ActionScript 3.0 code)
// Flash addon for Proxy Revealer Olympus (phpBB3 MOD)
// Author: Jasmine < jasmine.aura@yahoo.com >
// Licensed under http://opensource.org/licenses/gpl-license.php GNU Public License
// $Id: HttpRequestor.as 23 2008-09-16 13:13:01Z jasmine.aura@yahoo.com $

import flash.display.LoaderInfo;
import flash.errors.*;
import flash.events.*;
import flash.net.sendToURL;
import flash.net.URLRequest;
import flash.net.Socket;
import flash.system.Capabilities;
import flash.system.Security;
import flash.xml.*;

// Retrieve passed FlashVars
var dhost:String = root.loaderInfo.parameters.dhost;
var dport:Number = root.loaderInfo.parameters.dport;
var flash_url:String = root.loaderInfo.parameters.flash_url;
var ip:String = root.loaderInfo.parameters.ip;
var extra:String = root.loaderInfo.parameters.extra;
var user_agent:String = root.loaderInfo.parameters.user_agent;

// Retrieve policy file from our XMLSocket server to allow socket connections
Security.loadPolicyFile("xmlsocket://" + dhost + ":" + dport);

// Get Flash Player version
var version:String = Capabilities.version;

// Our probe.php URL and the query string concatenated
var myURL:String = flash_url + "?mode=flash&ip=" + ip + "&extra=" + extra;
myURL += "&version=" + escape(version) + "&user_agent=" + escape(user_agent);

// Socket connection code
var sock:Socket = new Socket();

sock.addEventListener(Event.CONNECT, connectHandler);
sock.addEventListener(ProgressEvent.SOCKET_DATA, dataHandler);

sock.connect(dhost,dport);

function connectHandler(event:Event):void {
	sock.writeUTFBytes("getmyip");
	sock.writeByte(0);	// terminate with a nullbyte
	sock.flush();
}

function dataHandler(event:ProgressEvent):void {
	var myIP:String = sock.readUTFBytes(sock.bytesAvailable);
	myURL += "&xml_ip=" + myIP;
	var request:URLRequest = new URLRequest(myURL);
	sendToURL(request);
	sock.close();
}
