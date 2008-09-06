// HttpRequestor.as (ActionScript 3.0 code)
// Flash addon for phpbb2 mod Proxy Revealer 2.0.1 (Original Author: TerraFrost < terrafrost@phpbb.com >)
// Author: Jasmine < jasmine.aura@yahoo.com >

import flash.display.LoaderInfo;
import flash.errors.*;
import flash.events.*;
import flash.net.sendToURL;
import flash.net.URLRequest;
import flash.net.XMLSocket;
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
var sock:XMLSocket = new XMLSocket();

sock.addEventListener(Event.CONNECT, connectHandler);
sock.addEventListener(DataEvent.DATA, dataHandler);

sock.connect(dhost,dport);

function connectHandler(event:Event):void {
	var xmlRequest:String = "<data><request>getmyip</request></data>";
	sock.send(new XML(xmlRequest));
}

function dataHandler(event:DataEvent):void {
	var myXML:XML = new XML(event.data);
	var myIP:String = myXML.ip[0];
	myURL += "&xml_ip=" + myIP;
	var request:URLRequest = new URLRequest(myURL);
	sendToURL(request);
	sock.close();
}
