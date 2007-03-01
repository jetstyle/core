function popupIcon( icon )
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

var picArray = new Array();
var preloadFlag = false;
var ok = false;

function cI()
{
  if (document.images && (preloadFlag == true))
  {
    for (var i=0; i<cI.arguments.length; i+=2)
    {
      if (cI.arguments[i] && cI.arguments[i+1]){      
//            if ( document.images[ cI.arguments[i] ] && picArray[ cI.arguments[i+1] ] )
//              document.images[ cI.arguments[i] ].src = picArray[ cI.arguments[i+1] ].src;
        if ( findObj(cI.arguments[i]) && picArray[ cI.arguments[i+1] ] )
          findObj(cI.arguments[i]).src = picArray[ cI.arguments[i+1] ].src;
      }
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
      if (ok)
      ok = confirm( "preload:" + dir + preloadPics.arguments[i] + ".gif" );
    }
}

function is_email( what )
{
  if (what == "") return true;
  if (what.match(/^[a-z0-9\._\-]+@[a-z0-9\._\-]+\.[a-z]+$/i, "")) return true;
  return false;
}



// ------------------------
var visibles = new Array();
function one_visible_register( id )
{ visibles[visibles.length] = id; }
function one_visible( id )
{
  var skipped = true;
  for (var i in visibles)
  {
    var el = document.getElementById( visibles[i] );
    if (el)
    {
      if (visibles[i] == id) { el.className="visible";    skipped=false; }
      else                   { el.className="invisible";  }
    }
  }
  return skipped;
}

function flip_visible( id )
{
  var el = document.getElementById( id );
  if (el)
  {
    if (el.className == "visible")   { el.className="invisible"; return false; }
    if (el.className == "invisible") { el.className="visible";   return false; }
  }
  return true;
}