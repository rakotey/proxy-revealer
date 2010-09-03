/* PluginDetect v0.7.3 by Eric Gerds www.pinlady.net/PluginDetect [ onWindowLoaded isMinVersion QT WMP ] */var PluginDetect={handler:function(c,b,a){return function(){c(b,a)
}
},isDefined:function(b){return typeof b!="undefined"
},isArray:function(b){return(b&&b.constructor===Array)
},isFunc:function(b){return typeof b=="function"
},isString:function(b){return typeof b=="string"
},isNum:function(b){return typeof b=="number"
},isStrNum:function(b){return(typeof b=="string"&&(/\d/).test(b))
},getNumRegx:/[\d][\d\.\_,-]*/,splitNumRegx:/[\.\_,-]/g,getNum:function(b,c){var d=this,a=d.isStrNum(b)?(d.isDefined(c)?new RegExp(c):d.getNumRegx).exec(b):null;
return a?a[0].replace(d.splitNumRegx,","):null
},compareNums:function(h,f,d){var e=this,c,b,a,g=parseInt;
if(e.isStrNum(h)&&e.isStrNum(f)){if(e.isDefined(d)&&d.compareNums){return d.compareNums(h,f)
}c=h.split(e.splitNumRegx);
b=f.split(e.splitNumRegx);
for(a=0;
a<Math.min(c.length,b.length);
a++){if(g(c[a],10)>g(b[a],10)){return 1
}if(g(c[a],10)<g(b[a],10)){return -1
}}}return 0
},formatNum:function(b,c){var d=this,a,e;
if(!d.isStrNum(b)){return null
}if(!d.isNum(c)){c=4
}c--;
e=b.replace(/\s/g,"").split(d.splitNumRegx).concat(["0","0","0","0"]);
for(a=0;
a<4;
a++){if(/^(0+)(.+)$/.test(e[a])){e[a]=RegExp.$2
}if(a>c||!(/\d/).test(e[a])){e[a]="0"
}}return e.slice(0,4).join(",")
},$$hasMimeType:function(a){return function(d){if(!a.isIE){var c,b,e,f=a.isString(d)?[d]:d;
for(e=0;
e<f.length;
e++){if(/[^\s]/.test(f[e])&&(c=navigator.mimeTypes[f[e]])&&(b=c.enabledPlugin)&&(b.name||b.description)){return c
}}}return null
}
},findNavPlugin:function(l,e,c){var j=this,h=new RegExp(l,"i"),d=(!j.isDefined(e)||e)?/\d/:0,k=c?new RegExp(c,"i"):0,a=navigator.plugins,g="",f,b,m;
for(f=0;
f<a.length;
f++){m=a[f].description||g;
b=a[f].name||g;
if((h.test(m)&&(!d||d.test(RegExp.leftContext+RegExp.rightContext)))||(h.test(b)&&(!d||d.test(RegExp.leftContext+RegExp.rightContext)))){if(!k||!(k.test(m)||k.test(b))){return a[f]
}}}return null
},getMimeEnabledPlugin:function(a,e){var d=this,b,c=new RegExp(e,"i");
if((b=d.hasMimeType(a))&&(b=b.enabledPlugin)&&(c.test(b.description||"")||c.test(b.name||""))){return b
}return 0
},AXO:window.ActiveXObject,getAXO:function(b,a){var g=null,f,d=false,c=this;
try{g=new c.AXO(b);
d=true
}catch(f){}if(c.isDefined(a)){delete g;
return d
}return g
},convertFuncs:function(f){var a,g,d,b=/^[\$][\$]/,c={};
for(a in f){if(b.test(a)){c[a]=1
}}for(a in c){try{g=a.slice(2);
if(g.length>0&&!f[g]){f[g]=f[a](f)
}}catch(d){}}},initScript:function(){var c=this,a=navigator,d="/",h=a.userAgent||"",f=a.vendor||"",b=a.platform||"",g=a.product||"";
c.OS=(/win/i).test(b)?1:((/mac/i).test(b)?2:((/linux/i).test(b)?3:4));
c.convertFuncs(c);
c.isIE=new Function("return "+d+"*@cc_on!@*"+d+"false")();
c.verIE=c.isIE&&(/MSIE\s*(\d+\.?\d*)/i).test(h)?parseFloat(RegExp.$1,10):null;
c.ActiveXEnabled=false;
if(c.isIE){var e,i=["Msxml2.XMLHTTP","Msxml2.DOMDocument","Microsoft.XMLDOM","ShockwaveFlash.ShockwaveFlash","TDCCtl.TDCCtl","Shell.UIHelper","Scripting.Dictionary","wmplayer.ocx"];
for(e=0;
e<i.length;
e++){if(c.getAXO(i[e],1)){c.ActiveXEnabled=true;
break
}}c.head=c.isDefined(document.getElementsByTagName)?document.getElementsByTagName("head")[0]:null
}c.isGecko=(/Gecko/i).test(g)&&(/Gecko\s*\/\s*\d/i).test(h);
c.verGecko=c.isGecko?c.formatNum((/rv\s*\:\s*([\.\,\d]+)/i).test(h)?RegExp.$1:"0.9"):null;
c.isSafari=(/Safari\s*\/\s*\d/i).test(h)&&(/Apple/i).test(f);
c.isChrome=(/Chrome\s*\/\s*\d/i).test(h);
c.isOpera=(/Opera\s*[\/]?\s*(\d+\.?\d*)/i).test(h);
c.verOpera=c.isOpera&&((/Version\s*\/\s*(\d+\.?\d*)/i).test(h)||1)?parseFloat(RegExp.$1,10):null;
;
c.addWinEvent("load",c.handler(c.runWLfuncs,c));

},init:function(d,a){var c=this,b;
if(!c.isString(d)){return -3
}if(d.length==1){c.getVersionDelimiter=d;
return -3
}b=c[d.toLowerCase().replace(/\s/g,"")];
if(!b||!b.getVersion){return -3
}c.plugin=b;
if(!c.isDefined(b.installed)||a==true){b.installed=b.version=b.version0=b.getVersionDone=null;
b.$=c
}c.garbage=false;
if(c.isIE&&!c.ActiveXEnabled){if(b!==c.java){return -2
}}return 1
},fPush:function(b,a){var c=this;
if(c.isArray(a)&&(c.isFunc(b)||(c.isArray(b)&&b.length>0&&c.isFunc(b[0])))){a[a.length]=b
}},callArray:function(b){var c=this,a;
if(c.isArray(b)){for(a=0;
a<b.length;
a++){if(b[a]===null){return
}c.call(b[a]);
b[a]=null
}}},call:function(c){var b=this,a=b.isArray(c)?c.length:-1;
if(a>0&&b.isFunc(c[0])){c[0](b,a>1?c[1]:0,a>2?c[2]:0,a>3?c[3]:0)
}else{if(b.isFunc(c)){c(b)
}}},$$isMinVersion:function(a){return function(h,g,d,c){var e=a.init(h),f,b=-1;
if(e<0){return e
}f=a.plugin;
g=a.formatNum(a.isNum(g)?g.toString():(a.isString(g)?a.getNum(g):"0"));
if(!a.isStrNum(g)){return -3
}if(f.getVersionDone!=1){f.getVersion(d,c);
if(f.getVersionDone===null){f.getVersionDone=1
}}a.cleanup();
if(f.installed!==null){b=f.installed<=0.5?f.installed:(f.version===null?0:(a.compareNums(f.version,g,f)>=0?1:-1))
}return b
}
},cleanup:function(){
var a=this;
if(a.garbage&&a.isDefined(window.CollectGarbage)){window.CollectGarbage()
}
},isActiveXObject:function(b){var f=this,a=false,g,c="<",d=c+'object width="1" height="1" style="display:none" '+f.plugin.getCodeBaseVersion(b)+">"+f.plugin.HTML+c+"/object>";
if(!f.head){return a
}if(f.head.firstChild){f.head.insertBefore(document.createElement("object"),f.head.firstChild)
}else{f.head.appendChild(document.createElement("object"))
}f.head.firstChild.outerHTML=d;
try{f.head.firstChild.classid=f.plugin.classID
}catch(g){}try{if(f.head.firstChild.object){a=true
}}catch(g){}try{if(a&&f.head.firstChild.readyState<4){f.garbage=true
}}catch(g){}f.head.removeChild(f.head.firstChild);
return a
},codebaseSearch:function(c){var e=this;
if(!e.ActiveXEnabled){return null
}if(e.isDefined(c)){return e.isActiveXObject(c)
}var j=[0,0,0,0],g,f,b=e.plugin.digits,i=function(k,l){return e.isActiveXObject((k==0?l:j[0])+","+(k==1?l:j[1])+","+(k==2?l:j[2])+","+(k==3?l:j[3]))
},h,d,a=false;
for(g=0;
g<b.length;
g++){h=b[g]*2;
j[g]=0;
for(f=0;
f<20;
f++){if(h==1&&g>0&&a){break
}if(h-j[g]>1){d=Math.round((h+j[g])/2);
if(i(g,d)){j[g]=d;
a=true
}else{h=d
}}else{if(h-j[g]==1){h--;
if(!a&&i(g,h)){a=true
}break
}else{if(!a&&i(g,h)){a=true
}break
}}}if(!a){return null
}}return j.join(",")
},addWinEvent:function(d,c){var e=this,a=window,b;
if(e.isFunc(c)){if(a.addEventListener){a.addEventListener(d,c,false)
}else{if(a.attachEvent){a.attachEvent("on"+d,c)
}else{b=a["on"+d];
a["on"+d]=e.winHandler(c,b)
}}}},winHandler:function(d,c){return function(){d();
if(typeof c=="function"){c()
}}
},WLfuncs:[0],runWLfuncs:function(a){a.winLoaded=true;
a.callArray(a.WLfuncs);
if(a.onDoneEmptyDiv){a.onDoneEmptyDiv()
}},winLoaded:false,$$onWindowLoaded:function(a){return function(b){if(a.winLoaded){a.call(b)
}else{a.fPush(b,a.WLfuncs)
}}
},div:null,divWidth:50,pluginSize:1,emptyDiv:function(){var c=this,a,e,b,d=0;
if(c.div&&c.div.childNodes){for(a=c.div.childNodes.length-1;
a>=0;
a--){b=c.div.childNodes[a];
if(b&&b.childNodes){if(d==0){for(e=b.childNodes.length-1;
e>=0;
e--){b.removeChild(b.childNodes[e])
}c.div.removeChild(b)
}else{}}}}},onDoneEmptyDiv:function(){var a=this;
if(!a.winLoaded){return
}if(a.WLfuncs&&a.WLfuncs.length>0&&a.isFunc(a.WLfuncs[a.WLfuncs.length-1])){return
}if(a.java){if(a.java.OTF==3){return
}if(a.java.funcs&&a.java.funcs.length>0&&a.isFunc(a.java.funcs[a.java.funcs.length-1])){return
}}a.emptyDiv()
},getObject:function(c,a){var g,d=this,f=null,b=d.getContainer(c);
try{if(b&&b.firstChild){f=b.firstChild
}if(a&&f){f.focus()
}}catch(g){}return f
},getContainer:function(a){return(a&&a[0]?a[0]:null)
},instantiate:function(j,c,g,a,k){var m,n=document,i=this,r,q=n.createElement("span"),o,h,f="<";
var l=function(t,s){var v=t.style,d,u;
if(!v){return
}v.outline="none";
v.border="none";
v.padding="0px";
v.margin="0px";
v.visibility="visible";
if(i.isArray(s)){for(d=0;
d<s.length;
d=d+2){try{v[s[d]]=s[d+1]
}catch(u){}}return
}},b=function(){var t,u="pd33993399",s=null,d=(n.getElementsByTagName("body")[0]||n.body);
if(!d){try{n.write(f+'div id="'+u+'">o'+f+"/div>");
s=n.getElementById(u)
}catch(t){}}d=(n.getElementsByTagName("body")[0]||n.body);
if(d){if(d.firstChild&&i.isDefined(d.insertBefore)){d.insertBefore(i.div,d.firstChild)
}else{d.appendChild(i.div)
}if(s){d.removeChild(s)
}}else{}};
if(!i.isDefined(a)){a=""
}if(i.isString(j)&&(/[^\s]/).test(j)){r=f+j+' width="'+i.pluginSize+'" height="'+i.pluginSize+'" ';
for(o=0;
o<c.length;
o=o+2){if(/[^\s]/.test(c[o+1])){r+=c[o]+'="'+c[o+1]+'" '
}}r+=">";
for(o=0;
o<g.length;
o=o+2){if(/[^\s]/.test(g[o+1])){r+=f+'param name="'+g[o]+'" value="'+g[o+1]+'" />'
}}r+=a+f+"/"+j+">"
}else{r=a
}if(!i.div){i.div=n.createElement("div");
h=n.getElementById("plugindetect");
if(h){i.div=h
}else{i.div.id="plugindetect";
b()
}l(i.div,["width",i.divWidth+"px","height",(i.pluginSize+3)+"px","fontSize",(i.pluginSize+3)+"px","lineHeight",(i.pluginSize+3)+"px","verticalAlign","baseline","display","block"]);
if(!h){l(i.div,["position","absolute","right","0px","top","0px"])
}}if(i.div&&i.div.parentNode){i.div.appendChild(q);
l(q,["fontSize",(i.pluginSize+3)+"px","lineHeight",(i.pluginSize+3)+"px","verticalAlign","baseline","display","inline"]);
;
try{if(q&&q.parentNode){q.focus()
}}catch(m){}try{q.innerHTML=r
}catch(m){}if(q.childNodes.length==1&&!(i.isGecko&&i.compareNums(i.verGecko,"1,5,0,0")<0)){l(q.firstChild,["display","inline"])
}return[q]
}return[null]
},quicktime:{mimeType:["video/quicktime","application/x-quicktimeplayer","image/x-macpaint","image/x-quicktime"],progID:"QuickTimeCheckObject.QuickTimeCheck.1",progID0:"QuickTime.QuickTime",classID:"clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B",minIEver:7,HTML:("<")+'param name="src" value="" />'+("<")+'param name="controller" value="false" />',getCodeBaseVersion:function(a){return'codebase="#version='+a+'"'
},digits:[8,64,64,0],getVersion:function(){var d=this,b=d.$,a=null,c=null;
if(!b.isIE){if(b.hasMimeType(d.mimeType)){c=b.OS!=3?b.findNavPlugin("QuickTime.*Plug-?in",0):null;
if(c&&c.name){a=b.getNum(c.name)
}}}else{if(b.verIE>=d.minIEver){if(d.BIfuncs){b.callArray(d.BIfuncs)
}a=b.codebaseSearch()
}else{c=b.getAXO(d.progID);
if(c&&c.QuickTimeVersion){a=c.QuickTimeVersion.toString(16);
a=a.charAt(0)+"."+a.charAt(1)+"."+a.charAt(2)
}}}d.installed=a?1:(c?0:-1);
a=b.formatNum(a);
if(b.isIE&&a){if(b.compareNums(a,"7,50,0,0")>=0&&b.compareNums(a,"7,60,0,0")<0){p=a.split(b.splitNumRegx);
a=[p[0],p[1].charAt(0),p[1].charAt(1),p[2]].join(",")
}}d.version=b.formatNum(a,3)
}},windowsmediaplayer:{mimeType:["application/x-mplayer2","application/asx","application/x-ms-wmp"],progID:"wmplayer.ocx",classID:"clsid:6BF52A52-394A-11D3-B153-00C04F79FAA6",getVersion:function(){var b=this,a=null,e=b.$,d,f=null,c;
b.installed=-1;
if(!e.isIE){if(e.hasMimeType(b.mimeType)){f=e.findNavPlugin("Windows\\s*Media.*Plug-?in",0,"Totem")||e.findNavPlugin("Flip4Mac.*Windows\\s*Media.*Plug-?in",0,"Totem");
d=(e.isGecko&&e.compareNums(e.verGecko,e.formatNum("1.8"))<0);
d=d||(e.isOpera&&e.verOpera<10);
if(!d&&e.getMimeEnabledPlugin(b.mimeType[2],"Windows\\s*Media.*Firefox.*Plug-?in")){c=e.getObject(e.instantiate("object",["type",b.mimeType[2],"data",""],["src",""],"",b));
if(c){a=c.versionInfo
}}}}else{f=e.getAXO(b.progID);
if(f){a=f.versionInfo
}}b.installed=f&&a?1:(f?0:-1);
b.version=e.formatNum(a)
}},zz:0};
PluginDetect.initScript();
