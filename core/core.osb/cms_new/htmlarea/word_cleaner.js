function WordCleaner(){}

WordCleaner.prototype.checkWord = function( text ) {
 var found=-1;
 var i = 0;
 while (i<msCrap.length && found==-1) {
  re = new RegExp(msCrap[i], "ig");
  found = text.search(re);
  i++;
 };
 return (found!=-1);
}


var msCrap = new Array();
i = 0;

msCrap[++i] = "lang=[A-Za-z0-9\-]*";
msCrap[++i] = "style=\"[^\"]+\"";          
msCrap[++i] = "x\:\w+(=\"[^\"]*\"|)"
msCrap[++i] = "<(|\/)SPAN[^>]*>"        
msCrap[++i] = "<(|\/)FONT[^>]*>"        
msCrap[++i] = "<(|\/)OBJECT[^>]*>"
msCrap[++i] = "<(|\/)PARAM[^>]*>"
msCrap[++i] = "<(|\/)V\:[^>]*>"         
msCrap[++i] = "<(|\/)O\:[^>]*>"         
msCrap[++i] = "<(|\/)W\:[^>]*>"
msCrap[++i] = "<[^A-Za-z]xml[^>]*>"     
msCrap[++i] = "\s*class=(mso|xl)[^\s>]*"; 
msCrap[++i] = "<(|\/)st1:[^>]*>"; 
//msCrap[++i] = "<(|\/)[a-z]\:[^>]*>"; ALL namespaced tags

WordCleaner.prototype.makeCleanHTML = function(s) {

 for (i=1; i<=msCrap.length; i++) {
  re = new RegExp(msCrap[i], "ig");
  s = s.replace(re,"");              
 }

 //clean up tags
 s = s.replace(/<b [^>]*>/gi,'<b>').                            
   replace(/<i [^>]*>/gi,'<i>').
   replace(/<li [^>]*>/gi,'<li>');

 s = this.cleanUpAttributesAll(s, "table|thead|tbody|tr|td", "colspan|rowspan|align|border|width|class|valign|nowrap");
 s = this.cleanUpAttributesAll(s, "ul|ol", "class|type");

 // replace outdated tags
 s = s.replace(/<b>/gi,'<strong>').
   replace(/<\/b>/gi,'</strong>');

 // mozilla doesn't like <em> tags
 s = s.replace(/<em>/gi,'<i>').
   replace(/<\/em>/gi,'</i>');

 // remove strange DESIGNTIMESP strings
 re = new RegExp("\&lt\;([^\&]?([^\&][^g])*)[ ]DESIGNTIMESP=[0-9]*\&gt\;", "ig");
 s = s.replace(re, "&laquo;$1&raquo;");

 s = s.replace(/<\!--\[[^>]*-->/gi, '');

 //add &nbsp; in empty <td>s
 re = new RegExp("<tr[^>]*height=0([^<]*(<[^\/])*(<\/([^t]))*(<\/t([^r]))*(<\/tr([^>]))*)*<\/tr>", "ig")
 str = s.match(re);   
 s = s.replace(re, "<here>");   

 s = s.replace(/<td([^>]*)><\/td>/gi, "<td$1>&nbsp;</td>").
   replace("<here>", str);

 // nuke double tags
 oldlen = s.length + 1;
 while(oldlen > s.length) {
   oldlen = s.length;
   s = s.replace(/<([a-z][a-z]*)> *<\/\1>/gi,' ').
     replace(/<([a-z][a-z]*)> *<([a-z][^>]*)> *<\/\1>/gi,'<$2>');
 }
 s = s.replace(/<([a-z][a-z]*)><\1>/gi,'<$1>').
   replace(/<\/([a-z][a-z]*)><\/\1>/gi,'<\/$1>');
 
 s = s.replace(/[ ]\r\n/ig, " ").
   replace(/[ ]+/ig, " ").
   replace(/[ ]+>/ig, ">").
   replace(/<br[^>]*>/ig, "<br />\r\n");

 s = s.replace(/<a name=([^>]+)>/gi, "<a class=toc name=$1>");


 return s;

}

WordCleaner.prototype.cleanUpAttributesTagOne = function( tag, reAttr, reverseAttr )
{
 var re = /([a-z\:]+)=(("[^"]*")|([^ >]*))/i;
 var arr = re.exec(tag);
 if (arr === null) return tag;

 arrs = String(arr[0]);
 var eq = arrs.indexOf("=");
 var name  = arrs.substr(0,eq);

 var li = arr.index + arrs.length;

 var t = reAttr.test(name);
 if ((!reAttr.test(name) && !reverseAttr) || (reAttr.test(name) && reverseAttr)) 
  return tag.substr(0,arr.index) + tag.substr(li);

 var value = arrs.substr(eq+1);
 if (value.charAt(0) == '"') value = value.substr(1);
 if (value.charAt(value.length-1) == '"') value = value.substr(0,value.length-1);
 value = '"'+ value.replace('"',"\\\"") +'"';

 return tag.substr(0,arr.index) + name + "~=" + value + tag.substr(li);
}

WordCleaner.prototype.cleanUpAttributesTag = function( tag, reAttr, reverseAttr )
{
 var s1 = tag;
 var s2 = tag+"+";
 while (s1 != s2)
 { s2 = s1;
   s1 = this.cleanUpAttributesTagOne(s1, reAttr, reverseAttr);
 }
 s1 = s1.replace( /[ ]+/, " ");
 s1 = s1.replace( /[ ]+>/, ">");
 return s1.replace( /~=/g, "=" );
  
}

// standard MSIE s1-s2 tag walker
WordCleaner.prototype.cleanUpAttributesOne = function( s, reTag, reAttr, reverseTag, reverseAttr )
{
 var re = /<[^>~][^>]*>/i;
 arr = re.exec(s);
 if (arr === null) return s;

 arrs = String(arr);
 new_arrs = "<~" + arrs.substr(1);
 var li = arr.index + arrs.length;

 var t = reTag.test(arrs)
 if ((t && !reverseTag) || (!t &&reverseTag))
   s = s.substr(0,arr.index) + this.cleanUpAttributesTag( new_arrs, reAttr, reverseAttr ) + s.substr(li);
 else
   s = s.substr(0,arr.index) + new_arrs + s.substr(li);

 return s;
}

WordCleaner.prototype.cleanUpAttributesAll = function( s, restrTag, restrAttr, reverseTag, reverseAttr )
{
  // create res
  var reTag  = new RegExp( "<("+restrTag+")[ >]", "i" );
  var reAttr = new RegExp( "^("+restrAttr+")$", "i" );

  // s1-s2 standard MSIE5 workaround
  var s1 = s;
  var s2 = s+"+";
  while (s1 != s2)
  { s2 = s1;
    s1 = this.cleanUpAttributesOne(s1, reTag, reAttr, reverseTag, reverseAttr);
  }
  return s1.replace( /<~/g, "<" );
}
