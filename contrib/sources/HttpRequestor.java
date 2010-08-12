// httpRequestor.java
// Copyright (c) MMVI TerraFrost (c) 2010 jasmineaura
// Licensed under http://opensource.org/licenses/gpl-license.php GNU Public License
// $Id$

import java.applet.*;
import java.io.*;
import java.net.*;

public class HttpRequestor extends Applet
{
	public void start()
	{
		try
		{
			String javaVendor = System.getProperty("java.vendor");
			String javaVersion = javaVendor.startsWith("Microsoft") ? System.getProperty("java.version") : System.getProperty("java.vm.version");

			Socket sock = new Socket(Proxy.NO_PROXY);
			InetSocketAddress sockAddress = new InetSocketAddress(getParameter("domain"), Integer.parseInt(getParameter("port")));
			sock.connect(sockAddress);

			String localAddr = sock.getLocalAddress().getHostAddress();
			String path = getParameter("path")+"&local="+localAddr+
				"&vendor="+URLEncoder.encode(javaVendor, "UTF-8")+
				"&version="+URLEncoder.encode(javaVersion, "UTF-8")+
				"&user_agent="+URLEncoder.encode(getParameter("user_agent"), "UTF-8");

			String httpRequest = "GET "+path+" HTTP/1.0\r\nHost: "+getParameter("domain")+"\r\n\r\n";
			sock.getOutputStream().write(httpRequest.getBytes());
			sock.getInputStream();
		}
		catch (Exception e)
		{
			e.printStackTrace();
		}
	}
}