// httpRequestor.java
// Copyright (c) MMVI TerraFrost (c) jasmineaura
// Licensed under http://opensource.org/licenses/gpl-license.php GNU Public License
// $Id$

import java.applet.*;
import java.net.*;

public class HttpRequestor extends Applet
{
	public void start()
	{
		try
		{
			String javaVendor = System.getProperty("java.vendor");
			String javaVersion = javaVendor.startsWith("Microsoft") ? System.getProperty("java.version") : System.getProperty("java.vm.version");

			String localAddr = InetAddress.getLocalHost().getHostAddress();

			String path = getParameter("path")+"&local="+localAddr+
				"&vendor="+URLEncoder.encode(javaVendor, "UTF-8")+
				"&version="+URLEncoder.encode(javaVersion, "UTF-8")+
				"&user_agent="+URLEncoder.encode(getParameter("user_agent"), "UTF-8");

			URL urlBase = this.getCodeBase();
			String proto = (urlBase.getProtocol().equals("https")) ? "https://" : "http://";
			String port = (urlBase.getPort() != -1) ? ":"+urlBase.getPort() : "";
			URL url = new URL(proto+urlBase.getHost()+port+path);
			URLConnection conn = url.openConnection(Proxy.NO_PROXY);
			conn.setUseCaches(false);
			conn.getInputStream();
			conn.setDoOutput(true);
			conn.getOutputStream();
		}
		catch (Exception e)
		{
			e.printStackTrace();
		}
	}
}