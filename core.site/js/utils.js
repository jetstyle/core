// logging
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