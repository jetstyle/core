var linkField;
function selectLink(field)
{
    linkField = field;
    popup_href = base_url+"do/Content/jetcontent?cm=1&replace=solutions";
    var d = getWindowSize(640, 480);
    var preview = window.open(popup_href, '_blank', 'width='+d.width+',height='+d.height+',top='+d.top+',left='+d.left+',resizable=1,toolbar=0,directories=0,location=0,menubar=0,personalbar=0,scrollbars=yes,status=1');
    preview.focus(); 
}

function insertLink(link)
{
    $("#"+linkField).val(link);
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
/*
var popup_window;
function pictwnd(url,name,format){
  if(name==null) name='_blank';
  popup_window = window.open(url,name,format);
}
*/
