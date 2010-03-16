var debugCont = null;
function _log(txt)
{
	if(window.console)
	{
		window.console.log(txt);
	}
	else
	{
		if(!debugCont)
		{
			var els = document.getElementsByTagName('body');
			debugCont = document.createElement('div');
			debugCont.style.width = '100%';
			debugCont.style.height = '200px';
			debugCont.style.border = 'red 1px solid';
			els[0].appendChild(debugCont);
		}
		debugCont.innerHTML += txt + '<br />';	
	}
}

var Class = {
	  create: function() {
	    return function() {
	      this.initialize.apply(this, arguments);
	    }
	  }
};

var $A = Array.from = function(iterable) {
  if (!iterable) return [];
  if (iterable.toArray) {
    return iterable.toArray();
  } else {
    var results = [];
    for (var i = 0, length = iterable.length; i < length; i++)
      results.push(iterable[i]);
    return results;
  }
}

Function.prototype.prototypeBind = function() {
  var __method = this, args = $A(arguments), object = args.shift();
  return function() {
    return __method.apply(object, args.concat($A(arguments)));
  }
};

Function.prototype.prototypeBindAsEventListener = function(object) {
  var __method = this, args = $A(arguments), object = args.shift();
  return function(event) {
    return __method.apply(object, [event || window.event].concat(args));
  }
};

/*
function popupIcon (icon)
{
  alert( "Подсказка:\n\n" + icon.alt, "") ;
}

function refreshTicker()
{
  document.images[ "sessionTicker" ].src = "/cms/z.php?id="+Math.random();
  window.setTimeout( "refreshTicker()", 59000 );
}

function undef(param)
{ return param; }

function NewWindow(mypage,myname,w,h,scroll,resize)
{
LeftPosition=(screen.width)?(screen.width-w)/2:100;
TopPosition=(screen.height)?(screen.height-h)/2:100;
settings='width='+w+',height='+h+',top='+TopPosition+',left='+LeftPosition+',scrollbars='+scroll+',location=no,directories=no,status=no,menubar=no,toolbar=no,resizable='+resize;
win=window.open(mypage,myname,settings);
}

function sign(x)
{
  if (x > 0) return 1;
  if (x < 0) return -1;
  return 0;
}

function BrowserCheck()
{
  var b = navigator.appName;
  this.os = navigator.userAgent.toLowerCase(); //TM
  if (b == "Netscape") this.b = "ns";
  else if (b == "Microsoft Internet Explorer") this.b = "ie";
  else this.b = b;
  this.version = navigator.appVersion;
  this.v = parseInt(this.version);
  this.ns = (this.b == "ns" && this.v >= 4);
  this.ns4 = (this.ns && this.v == 4);
  this.ns5 = (this.ns && this.v == 5);
  this.ie = (this.b == "ie" && this.v >= 4);
  this.ie4 = (this.version.indexOf('MSIE 4') > 0);
  this.ie5 = (this.version.indexOf('MSIE 5') > 0);
  this.min = (this.ns || this.ie);
  this.opera = (this.os.indexOf("opera") != -1); //TM
  this.win = (this.os.indexOf("win") != -1); //TM
  this.win9 = ((this.os.indexOf("win 9") != -1) || (this.os.indexOf("win9") != -1) || (this.os.indexOf("windows 9") != -1)|| (this.os.indexOf("windows9") != -1)); //TM
  this.winnt = ((this.os.indexOf("win nt") != -1) || (this.os.indexOf("winnt") != -1)); //TM
  this.win2000 = ((this.os.indexOf("win nt 5.0") != -1) || (this.os.indexOf("windows nt 5.0") != -1)); //TM
  this.mac = (this.os.indexOf("mac") != -1); //TM
  this.unix = (this.os.indexOf("x11") != -1); //TM
}

is = new BrowserCheck();

function travelA( Aname, quick, noplus )
{
  if (!(is.ie && !is.opera)) return true;
  var value=10;
  if (noplus) value=0;
  z = document.all[Aname];
  var x=0,y=0;
  while (z != document.body)
  {
    x += parseInt(isNaN(parseInt(z.offsetLeft))?0:z.offsetLeft);
    y += parseInt(isNaN(parseInt(z.offsetTop))?0:z.offsetTop);
    z = z.offsetParent;
  }
  travelto( x,  y-value, quick );
  return false;
}

function travelto(x, y, quick )
{
  if (quick)
  {
      ox = document.body.scrollLeft;
      oy = document.body.scrollTop;
      dx = (x - ox);
      dx = sign(dx) * Math.ceil(Math.abs(dx));
      dy = (y - oy);
      dy = sign(dy) * Math.ceil(Math.abs(dy));
      window.scrollBy(dx, dy);
    return;
  }
  do
    {
      ox = document.body.scrollLeft;
      oy = document.body.scrollTop;
      dx = (x - ox) / 10;
      dx = sign(dx) * Math.ceil(Math.abs(dx));
      dy = (y - oy) / 10;
      dy = sign(dy) * Math.ceil(Math.abs(dy));
      window.scrollBy(dx, dy);
      cx = document.body.scrollLeft;
      cy = document.body.scrollTop;
    }
  while (( (ox-cx) != 0 ) || ( (oy-cy) != 0 ));
}

var ok = false;

function cI()
{
  if (document.images && (preloadFlag == true))
  {
    for (var i=0; i<cI.arguments.length; i+=2)
    {
      if (cI.arguments[i] && cI.arguments[i+1])
//            if ( document.images[ cI.arguments[i] ] && picArray[ cI.arguments[i+1] ] )
//              document.images[ cI.arguments[i] ].src = picArray[ cI.arguments[i+1] ].src;
        if ( findObj(cI.arguments[i]) && picArray[ cI.arguments[i+1] ] )
          findObj(cI.arguments[i]).src = picArray[ cI.arguments[i+1] ].src;
    }
  }
  return true;
}
function cC()
{
    for (var i=0; i<cC.arguments.length; i+=2)
    {
      if (cC.arguments[i] && cC.arguments[i+1])
        if (findObj(cC.arguments[i]))
          findObj(cC.arguments[i]).className = cC.arguments[i+1];
    }
  return true;
}
*/

var picArray = new Array();
var preloadFlag = false;

function findObj(n, d) {
    var p,i,x;
    if (!d)
        d=document;
    if ((p=n.indexOf("?"))>0&&parent.frames.length) {
        d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);
    }
    if (!(x=d[n])&&d.all)
        x=d.all[n];
    for (i=0;!x&&i<d.forms.length;i++)
        x=d.forms[i][n];
    for (i=0;!x&&d.layers&&i<d.layers.length;i++)
        x=findObj(n,d.layers[i].document);
    if (!x && d.getElementById)
        x=d.getElementById(n);
    return x;
}


function preloadPics()
{
  if (preloadPics.arguments[0].lastIndexOf("/") == preloadPics.arguments[0].length-1)
   preloadPics.arguments[0] = preloadPics.arguments[0].substr(0, preloadPics.arguments[0].length-1);
  var dir = ""+preloadPics.arguments[0]+"/";

  for (var i=1; i<preloadPics.arguments.length; i++)
    {
      picArray[preloadPics.arguments[i]] = new Image();
      picArray[preloadPics.arguments[i]].src = dir + preloadPics.arguments[i] + ".gif";
    }
}

function popup_image(popup_href, img_width, img_height)
{
  var d = getWindowSize(img_width, img_height);
  var preview = window.open(popup_href, '_blank', 'width='+d.width+',height='+d.height+',top='+d.top+',left='+d.left+',resizable=0,toolbar=0,directories=0,location=0,menubar=0,personalbar=0,scrollbars='+(d.scroll ? 'yes' : 'no')+',status=0');
  preview.focus(); 
  return false;
}

function getWindowSize(width,height) 
{
	height = parseInt(height);
	width = parseInt(width);
	
	var scroll = 1;
	
	var paddingTop=29;
	var paddingBottom=3;
	var paddingLeft=3;
	var paddingRight=3;
	
	if (typeof screen.availWidth != 'undefined' && typeof screen.availHeight != 'undefined')
	{
		if (typeof screen.availLeft != 'undefined' && typeof screen.availTop != 'undefined')
		{
			var left = Math.max(0, Math.ceil(screen.availLeft + (screen.availWidth - width - paddingLeft - paddingRight) / 2));
			var top = Math.max(0, Math.ceil(screen.availTop + (screen.availHeight - height - paddingTop - paddingBottom) / 2));
		}
		else
		{
			var left = Math.max(0, Math.ceil((screen.availWidth - width - paddingLeft - paddingRight) / 2));
			var top = Math.max(0, Math.ceil((screen.availHeight - height - paddingTop - paddingBottom) / 2));
		}

		if(width < screen.availWidth && height < screen.availHeight)
		{
			scroll = 0;
		}
		else 
		{
			if(height > screen.availHeight)
			{
				height = screen.availHeight;
			}
			
			if(width > screen.availWidth)
			{
				width = screen.availWidth;
			}	
		}
	}
	else
	{
		var left = 0;
		var top = 0;
	}

	if(scroll == 1)
	{
		if (navigator.appVersion.match(/\bMSIE\b/))
		{
			width += 30;
			height += 30;
		}
	}
	
	return {'left' : left, 'top' : top, 'width' : width, 'height' : height, 'scroll' : scroll};
}

function getPageSize() {
	var xScroll, yScroll;
	if (window.innerHeight && window.scrollMaxY) {
		xScroll = window.innerWidth + window.scrollMaxX;
		yScroll = window.innerHeight + window.scrollMaxY;
	} else if (document.body.scrollHeight > document.body.offsetHeight){ // all but Explorer Mac
		xScroll = document.body.scrollWidth;
		yScroll = document.body.scrollHeight;
	} else { // Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari
		xScroll = document.body.offsetWidth;
		yScroll = document.body.offsetHeight;
	}
	var windowWidth, windowHeight;
	if (self.innerHeight) {	// all except Explorer
		if(document.documentElement.clientWidth){
			windowWidth = document.documentElement.clientWidth;
		} else {
			windowWidth = self.innerWidth;
		}
		windowHeight = self.innerHeight;
	} else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
		windowWidth = document.documentElement.clientWidth;
		windowHeight = document.documentElement.clientHeight;
	} else if (document.body) { // other Explorers
		windowWidth = document.body.clientWidth;
		windowHeight = document.body.clientHeight;
	}
	// for small pages with total height less then height of the viewport
	if(yScroll < windowHeight){
		pageHeight = windowHeight;
	} else {
		pageHeight = yScroll;
	}
	// for small pages with total width less then width of the viewport
	if(xScroll < windowWidth){
		pageWidth = xScroll;
	} else {
		pageWidth = windowWidth;
	}
	arrayPageSize = new Array(pageWidth,pageHeight,windowWidth,windowHeight);
	return arrayPageSize;
}

$(function(){
	//colorbox parameters
	var sizes = getPageSize();
	window.colorBoxParams = {
		maxWidth:  sizes[2]-50,
		maxHeight: sizes[3]-50,
		current:   '{current} из {total}',
		previous:  'предыдущая',
		next:      'следующая',
		onComplete: function() {
			if ($('#cboxPhoto').attr('src') && !$('#cboxLinkToOrigianl').get(0)) {
				$('#cboxCurrent').show().prepend('<a id="cboxLinkToOrigianl" target="_blank" href="'+$('#cboxPhoto').attr('src')+'">изображение</a>&nbsp;');
			} else if ($('#cboxPhoto').attr('src')) {
				$('#cboxLinkToOrigianl').attr('href', $('#cboxPhoto').attr('src'));
			}
		},
		onOpen: function() {
			$('object').css('visibility', 'hidden');
		},
		onClosed: function() {
			$('object').css('visibility', 'visible');
		}
	};
	window.colorBoxIframeParams = {
		width: '90%',
		height: '90%',
		iframe: true,
		rel: 'nofollow',
		onComplete: function() {
			$('#cboxTitle').hide();
			$('#cboxLoadedContent').css({
				'margin-bottom': 0,
				'height': $('#cboxLoadedContent').height() + 28 + 'px'
			});
		}
	};
	window.colorBoxInlineParams = {
		inline: true,
		onComplete: function() {
			$('#cboxTitle').hide();
			$('#cboxLoadedContent').css({
				'margin-bottom': 0,
				'height': $('#cboxLoadedContent').height() + 28 + 'px'
			});
		}
	};
	//login window on 403 error
	$(document).ajaxError(function(event, request, settings){
		if (request.status == 403) {
			var params = window.colorBoxIframeParams;
			params.href = (base_url.match(/.*cms\/$/) ? base_url : (base_url+'cms/'))+"u/login/ajax&onremove=true";
			params.width = false;
			params.height = false;
			params.innerWidth = 274;
			params.innerHeight = 100;
			params.onClosed = function() {
			  window.location.assign( window.location );
			}
			$.fn.colorbox(params);
		}
	});
})

/*
function isFlash(){
    
    var flashinstalled = 0;
		var flashversion = 0;
		MSDetect = "false";
		if (navigator.plugins && navigator.plugins.length)
		{
			x = navigator.plugins["Shockwave Flash"];
			if (x)
			{
				flashinstalled = 2;
				if (x.description)
				{
					y = x.description;
					flashversion = y.charAt(y.indexOf('.')-1);
				}
			}
			else	{
				flashinstalled = 1;
			}
			if (navigator.plugins["Shockwave Flash 2.0"])
			{
				flashinstalled = 2;
				flashversion = 2;
			}
		}
		else if (navigator.mimeTypes && navigator.mimeTypes.length)
		{
			x = navigator.mimeTypes['application/x-shockwave-flash'];
			if (x && x.enabledPlugin)
				flashinstalled = 2;
			else
				flashinstalled = 1;
		}
    
    if(flashinstalled == 2)	{
    	return true;
    }
    else if (flashinstalled == 1){
    	return false;
    }
    else{
    	// IE flash detection.
			for(var i=7; i>0; i--){
				flashVersion = 0;
				try{
					var flash = new ActiveXObject("ShockwaveFlash.ShockwaveFlash." + i);
					flashVersion = i;
					return true;
				}
				catch(e){}
			}
			
			return false;
    }

	}
	
var popup_window;
function pictwnd(url,name,format){
  if(name==null) name='_blank';
  popup_window = window.open(url,name,format);
}
*/