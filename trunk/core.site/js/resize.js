function imageTools(containerId)
{
	// основной див
	this.div = document.getElementById(containerId);
	this.div.style.position = 'relative';

	this.image = null;
	var total = this.div.childNodes.length;
	for(var i = 0; i < total; i++)
	{
		if(this.div.childNodes[i].tagName == 'IMG')
		{
			this.image = this.div.childNodes[i];
			this.image.style.zIndex = 100;
			break;
		}
	}

	this.resizeState = null;
	this.resizeType = '';

	this.boxMouseDownState  = false;

	this.mouseDownPos = null;
	
	this.saveProportions = true;
	//this.saveProportions = false;
	
	this.proportionX = 1;
	this.proportionY = 1;
		
	this.thumbSize = null;
	
	this.createBox();
}

imageTools.prototype.setResponseFunc = function(func)
{
	this.responseFunc = func;
}

imageTools.prototype.setThumbSize = function(x,y)
{
	this.thumbSize = {'x' : x, 'y' : y};
	
	if(this.saveProportions)
	{
		this.proportionX = this.thumbSize.x / this.thumbSize.y;
		this.proportionY = this.thumbSize.y / this.thumbSize.x;
	}
	
	this.updateBox();
	
}

imageTools.prototype.handle = function()
{
	if(this.thumbSize.x <= 0 && this.thumbSize.y <= 0)
	{
		this.setThumbSize(100, 100);
	}
	
	this.setEvents();
}

imageTools.prototype.updateBox = function()
{
	
	this.box.style.width = this.thumbSize.x + 'px';
	this.box.style.height = this.thumbSize.y + 'px';

	if(this.image.offsetWidth)
	{
		if(parseInt(this.box.style.left, 10) + parseInt(this.thumbSize.x, 10) > this.image.offsetWidth)
		{
			this.box.style.left = (this.image.offsetWidth - this.thumbSize.x) + 'px';
		}

		if(parseInt(this.box.style.top, 10) + parseInt(this.thumbSize.y, 10) > this.image.offsetHeight)
		{
			this.box.style.top = (this.image.offsetHeight - this.thumbSize.y) + 'px';
		}
	}
	this.boxZones['u'].style.width = (this.thumbSize.x - 10) + 'px';
	
	this.boxZones['d'].style.width = (this.thumbSize.x - 10) + 'px';
	this.boxZones['d'].style.top =  (this.thumbSize.y - 5) + 'px';
	
	this.boxZones['l'].style.height = (this.thumbSize.y - 10) + 'px';
	
	this.boxZones['r'].style.height = (this.thumbSize.y - 10) + 'px';
	this.boxZones['r'].style.left = (this.thumbSize.x - 5) + 'px';
	
	this.boxZones['ur'].style.left = (this.thumbSize.x - 5) + 'px';
	
	this.boxZones['dl'].style.top =  (this.thumbSize.y - 5) + 'px';
	
	this.boxZones['dr'].style.left = (this.thumbSize.x - 5) + 'px';
	this.boxZones['dr'].style.top =  (this.thumbSize.y - 5) + 'px';
}


imageTools.prototype.createBox = function()
{
	this.box = document.createElement('div');
	this.div.appendChild(this.box);
	this.box.style.position='absolute';
	
	this.box.style.zIndex = 200;
	this.box.style.left = 10 + 'px';
	this.box.style.top = 10 + 'px';
	this.box.style.backgroundColor = 'blue';
	this.box.style.opacity = 0.3;
	this.box.style.filter = 'alpha(opacity=30)';	
	
	
	this.boxZones = new Array(8);
	
	this.boxZones['u'] = document.createElement('div');
	this.boxZones['u'].style.height = '10px';
	this.boxZones['u'].style.left = '5px';
	this.boxZones['u'].style.top =  '-5px';
	this.boxZones['u'].style.cursor = 'n-resize';	
	//this.boxZones['u'].style.backgroundColor = 'red';

	this.boxZones['d'] = document.createElement('div');
	this.boxZones['d'].style.height = '10px';
	this.boxZones['d'].style.left = '5px';
	this.boxZones['d'].style.cursor = 's-resize';	
	//this.boxZones['d'].style.backgroundColor = 'red';

	this.boxZones['l'] = document.createElement('div');
	this.boxZones['l'].style.width = '10px';
	this.boxZones['l'].style.left = '-5px';
	this.boxZones['l'].style.top =  '5px';
	this.boxZones['l'].style.cursor = 'w-resize';	
	//this.boxZones['l'].style.backgroundColor = 'red';

	this.boxZones['r'] = document.createElement('div');
	this.boxZones['r'].style.width = '10px';
	this.boxZones['r'].style.top =  '5px';
	this.boxZones['r'].style.cursor = 'e-resize';	
	//this.boxZones['r'].style.backgroundColor = 'red';
	
	this.boxZones['ul'] = document.createElement('div');
	this.boxZones['ul'].style.width = '10px';
	this.boxZones['ul'].style.height = '10px';
	this.boxZones['ul'].style.left = '-5px';
	this.boxZones['ul'].style.top =  '-5px';
	this.boxZones['ul'].style.cursor = 'nw-resize';	
	//this.boxZones['ul'].style.backgroundColor = 'green';

	this.boxZones['ur'] = document.createElement('div');
	this.boxZones['ur'].style.width = '10px';
	this.boxZones['ur'].style.height = '10px';
	this.boxZones['ur'].style.top =  '-5px';
	this.boxZones['ur'].style.cursor = 'ne-resize';	
	//this.boxZones['ur'].style.backgroundColor = 'green';

	this.boxZones['dl'] = document.createElement('div');
	this.boxZones['dl'].style.width = '10px';
	this.boxZones['dl'].style.height = '10px';
	this.boxZones['dl'].style.left = '-5px';
	this.boxZones['dl'].style.cursor = 'sw-resize';	
	//this.boxZones['dl'].style.backgroundColor = 'green';

	this.boxZones['dr'] = document.createElement('div');
	this.boxZones['dr'].style.width = '10px';
	this.boxZones['dr'].style.height = '10px';
	this.boxZones['dr'].style.cursor = 'se-resize';	
	//this.boxZones['dr'].style.backgroundColor = 'green';
	
	for(i in this.boxZones)
	{
		this.box.appendChild(this.boxZones[i]);
		this.boxZones[i].style.position='absolute';
		this.boxZones[i].id = i + 'Zone';
		this.boxZones[i].setAttribute('z', 1);		
	}
}

imageTools.prototype.setEvents = function()
{
	Event.observe(this.box, 'mousedown', this.boxMouseDown.bindAsEventListener(this), false);
	Event.observe(document, 'mousemove', this.mouseMove.bindAsEventListener(this), false);
	
	Event.observe(this.box, 'dblclick', this.boxMouseDblClick.bindAsEventListener(this), false);
	
	Event.observe(document, 'mouseup', this.mouseUp.bindAsEventListener(this), false);
	
	for(i in this.boxZones)
	{
		//Event.observe(this.boxZones[i], 'mouseover', this.boxZonesMouseOver.bindAsEventListener(this), false);
		//Event.observe(this.boxZones[i], 'mouseout', this.boxZonesMouseOut.bindAsEventListener(this), false);
		Event.observe(this.boxZones[i], 'mousedown', this.boxZonesMouseDown.bindAsEventListener(this), false);
	}
}

imageTools.prototype.boxZonesMouseDown = function(ev)
{
	ev = ev || window.event;
	var target = ev.target || ev.srcElement;

	this.resizeState = 1;
	this.resizeType = target.id;
	this.mouseDownPos = this.mouseCoords(ev);

	_log(this.resizeType + 'down');

	Event.stop(ev);
}
/*
imageTools.prototype.boxZonesMouseOver = function(ev)
{
	ev = ev || window.event;
	var target = ev.target || ev.srcElement;

	switch(target.id)
	{
		case 'uZone':
			this.div.style.cursor = 'n-resize';	
		break;
		
		case 'dZone':
			this.div.style.cursor = 's-resize';	
		break;

		case 'lZone':
			this.div.style.cursor = 'w-resize';	
		break;

		case 'rZone':
			this.div.style.cursor = 'e-resize';	
		break;
		
		case 'ulZone':
			this.div.style.cursor = 'nw-resize';	
		break;

		case 'urZone':
			this.div.style.cursor = 'ne-resize';	
		break;
		
		case 'dlZone':
			this.div.style.cursor = 'sw-resize';	
		break;

		case 'drZone':
			this.div.style.cursor = 'se-resize';	
		break;
	}
	
	return false;
}

imageTools.prototype.boxZonesMouseOut = function(ev)
{
	ev = ev || window.event;
	var target = ev.relatedTarget || ev.toElement;
	
	if(target.z == 1)
	{
		return false;
	}
	else
	{
		this.div.style.cursor = 'auto';
	}	

	
}
*/

imageTools.prototype.boxMouseDblClick = function(ev)
{
	var params = cloneObj(this.standartParams);
	params.tlx = parseInt(this.box.style.left, 10);
	params.tly = parseInt(this.box.style.top, 10);
	params.rw = parseInt(this.box.style.width, 10);
	params.rh = parseInt(this.box.style.height, 10);
	params.w = this.thumbSize.x;
	params.h = this.thumbSize.y;
	params.url = '';
	params.resize = '1';
			
	jQuery.post(this.standartParams.url, params, this.responseFunc, "json");
}

imageTools.prototype.mouseCoords = function(ev)
{
	if(ev.pageX || ev.pageY){
		return {x:ev.pageX, y:ev.pageY};
	}
	return {
		x:ev.clientX + document.body.scrollLeft - document.body.clientLeft,
		y:ev.clientY + document.body.scrollTop  - document.body.clientTop
	};
}

imageTools.prototype.getMouseOffset = function(target, ev){
	ev = ev || window.event;
	var docPos    = this.getPosition(target);
	var mousePos  = this.mouseCoords(ev);
	return {x:mousePos.x - docPos.x, y:mousePos.y - docPos.y};
}

imageTools.prototype.getPosition = function(e){

	var left = 0;
	var top  = 0;

	while (e.offsetParent){
		left += e.offsetLeft + (e.currentStyle?(parseInt(e.currentStyle.borderLeftWidth)).NaN0():0);
		top  += e.offsetTop  + (e.currentStyle?(parseInt(e.currentStyle.borderTopWidth)).NaN0():0);
		e     = e.offsetParent;
	}

	left += e.offsetLeft + (e.currentStyle?(parseInt(e.currentStyle.borderLeftWidth)).NaN0():0);
	top  += e.offsetTop  + (e.currentStyle?(parseInt(e.currentStyle.borderTopWidth)).NaN0():0);

	return {x:left, y:top};
}


imageTools.prototype.mouseMove = function(ev)
{
	if (this.boxMouseDownState)
	{
		ev         = ev || window.event;	
		var mousePos = this.mouseCoords(ev);

		this.boxMove(mousePos.x - this.mouseDownPos.x, mousePos.y - this.mouseDownPos.y);
		this.mouseDownPos = mousePos;
	}
	else if (this.resizeState)
	{
		ev         = ev || window.event;	
		var mousePos = this.mouseCoords(ev);

		this.handleResize(mousePos.x - this.mouseDownPos.x, mousePos.y - this.mouseDownPos.y);
		this.mouseDownPos = mousePos;
	}

	// this helps prevent items on the page from being highlighted while dragging
	return false;
}

imageTools.prototype.handleResize = function(x,y)
{

	if(this.saveProportions)
	{
		switch(this.resizeType)
		{
			case 'lZone':
				var y = Math.floor((parseInt(this.box.style.width, 10) - x) * this.proportionY  - parseInt(this.box.style.height, 10));
			break;
			
			case 'uZone':
				var x = Math.floor((parseInt(this.box.style.height, 10) - y) * this.proportionX  - parseInt(this.box.style.width, 10));
			break;
						
			case 'dZone':
				var x = Math.floor((parseInt(this.box.style.height, 10) + y) * this.proportionX  - parseInt(this.box.style.width, 10));
			break;
			
			case 'rZone':
				var y = Math.floor((parseInt(this.box.style.width, 10) + x) * this.proportionY  - parseInt(this.box.style.height, 10));
			break;
			
			case 'urZone':
				if(Math.abs(y) > Math.abs(x))
				{
					var x = (Math.floor((parseInt(this.box.style.height, 10) - y) * this.proportionX  - parseInt(this.box.style.width, 10)));
				}
				else
				{
					var y = -(Math.floor((parseInt(this.box.style.width, 10) + x) * this.proportionY  - parseInt(this.box.style.height, 10)));
				}
			break;
			
			case 'ulZone':
				if(Math.abs(y) > Math.abs(x))
				{
					var x = -(Math.floor((parseInt(this.box.style.height, 10) - y) * this.proportionX  - parseInt(this.box.style.width, 10)));
				}
				else
				{
					var y = -(Math.floor((parseInt(this.box.style.width, 10) - x) * this.proportionY  - parseInt(this.box.style.height, 10)));
				}
			break;
			
			case 'dlZone':
				if(Math.abs(y) > Math.abs(x))
				{
					var x = -(Math.floor((parseInt(this.box.style.height, 10) + y) * this.proportionX  - parseInt(this.box.style.width, 10)));
				}
				else
				{
					var y = (Math.floor((parseInt(this.box.style.width, 10) - x) * this.proportionY  - parseInt(this.box.style.height, 10)));
				}
			break;
				
			case 'drZone':
				if(Math.abs(y) > Math.abs(x))
				{
					var x = Math.floor((parseInt(this.box.style.height, 10) + y) * this.proportionX  - parseInt(this.box.style.width, 10));
				}
				else
				{
					var y = Math.floor((parseInt(this.box.style.width, 10) + x) * this.proportionY  - parseInt(this.box.style.height, 10));
				}
			break;
		}
		
		switch(this.resizeType)
		{
			case 'rZone':
			case 'dZone':
			case 'drZone':
				var of = this.checkResizeDim(x, y);
				if(of._x != x)
				{
					of._y = Math.floor(this.proportionY * of._x);
				}
				else if(of._y != y)
				{
					of._x = Math.floor(this.proportionX * of._y);			
				}
			break;
			
			case 'dlZone':
			case 'lZone':
				var of2 = this.checkResizeDim(0, y);
				var of1 = this.checkResizeCoords(x, 0);
				if(of1._x != x)
				{
					of2._y = -Math.floor(this.proportionY * of1._x);
				}
				else if(of2._y != y)
				{
					of1._x = -Math.floor(this.proportionX * of2._y);			
				}
			break;
			
			case 'urZone':
			case 'uZone':
				var of2 = this.checkResizeCoords(0, y);
				var of1 = this.checkResizeDim(x, 0);
				if(of1._x != x)
				{
					of2._y = -Math.floor(this.proportionY * of1._x);
				}
				else if(of2._y != y)
				{
					of1._x = -Math.floor(this.proportionX * of2._y);			
				}
			break;
			
			case 'ulZone':
				var of = this.checkResizeCoords(x, y);
				if(of._x != x)
				{
					of._y = Math.floor(this.proportionY * of._x);
				}
				else if(of._y != y)
				{
					of._x = Math.floor(this.proportionX * of._y);			
				}
			break;
		}

		switch(this.resizeType)
		{
			case 'drZone':
			case 'rZone':
			case 'dZone':
				this.resizeRight(of._x);
				this.resizeDown(of._y);
			break;
			
			case 'urZone':
			case 'uZone':
				this.resizeRight(of1._x);
				this.resizeUp(of2._y);
			break;
			
			case 'dlZone':
			case 'lZone':
				this.resizeDown(of2._y);
				this.resizeLeft(of1._x);
			break;
			
			case 'ulZone':
				this.resizeLeft(of._x);
				this.resizeUp(of._y);
			break;
		}
	}
	else
	{
		switch(this.resizeType)
		{
			case 'drZone':
			case 'rZone':
			case 'dZone':
				var of = this.checkResizeDim(x, y);
			break;
			
			case 'ulZone':
			case 'uZone':
			case 'lZone':
				var of = this.checkResizeCoords(x, y);
			break;
			
			case 'dlZone':
				var of1 = this.checkResizeCoords(x, 0);
				var of2 = this.checkResizeDim(0, y);
			break;
		
			case 'urZone':
				var of1 = this.checkResizeCoords(x, 0);
				var of2 = this.checkResizeDim(0, y);
			break;
		}
	
		switch(this.resizeType)
		{
			case 'dZone':
				this.resizeDown(of._y);
			break;
			
			case 'rZone':
				this.resizeRight(of._x);
			break;
			
			case 'drZone':
				this.resizeDown(of._y);
				this.resizeRight(of._x);
			break;
			
			case 'ulZone':
				this.resizeUp(of._y);
				this.resizeLeft(of._x);
			break;
			
			case 'uZone':
				this.resizeUp(of._y);
			break;
			
			case 'lZone':
				this.resizeLeft(of._x);
			break;
			
			case 'dlZone':
				this.resizeLeft(of1._x);
				this.resizeDown(of2._y);
			break;
			
			case 'urZone':
				this.resizeRight(of1._x);
				this.resizeUp(of2._y);
			break;
		}
	}

	//_log(parseInt(this.box.style.width, 10) / parseInt(this.box.style.height, 10));
}

imageTools.prototype.resizeDown = function(offset)
{
	if(offset == 0) return;
	
	this.box.style.height = (parseInt(this.box.style.height, 10) + offset) + 'px';
	this.boxZones['l'].style.height = (parseInt(this.boxZones['l'].style.height, 10) + offset) + 'px';
	this.boxZones['r'].style.height = (parseInt(this.boxZones['r'].style.height, 10) + offset) + 'px';
	this.boxZones['dl'].style.top = (parseInt(this.boxZones['dl'].style.top, 10) + offset) + 'px';
	this.boxZones['d'].style.top = (parseInt(this.boxZones['d'].style.top, 10) + offset) + 'px';
	this.boxZones['dr'].style.top = (parseInt(this.boxZones['dr'].style.top, 10) + offset) + 'px';
}

imageTools.prototype.resizeRight = function(offset)
{
	if(offset == 0) return;
	
	this.box.style.width = (parseInt(this.box.style.width, 10) + offset) + 'px';
	this.boxZones['u'].style.width = (parseInt(this.boxZones['u'].style.width, 10) + offset) + 'px';
	this.boxZones['d'].style.width = (parseInt(this.boxZones['d'].style.width, 10) + offset) + 'px';
	this.boxZones['dr'].style.left = (parseInt(this.boxZones['dr'].style.left, 10) + offset) + 'px';
	this.boxZones['r'].style.left = (parseInt(this.boxZones['r'].style.left, 10) + offset) + 'px';
	this.boxZones['ur'].style.left = (parseInt(this.boxZones['ur'].style.left, 10) + offset) + 'px';
}

imageTools.prototype.resizeUp = function(offset)
{
	if(offset == 0) return;
	
	this.resizeDown(-offset);
	this.box.style.top = (parseInt(this.box.style.top, 10) + offset) + 'px';
}

imageTools.prototype.resizeLeft = function(offset)
{
	if(offset == 0) return;
	
	this.resizeRight(-offset);
	this.box.style.left = (parseInt(this.box.style.left, 10) + offset) + 'px';
}


imageTools.prototype.checkResizeDim = function(x, y)
{
	var _cur_x = parseInt(this.box.style.left, 10);
	var _cur_y = parseInt(this.box.style.top, 10);
	
	var _cur_w = parseInt(this.box.style.width, 10);
	var _cur_h = parseInt(this.box.style.height, 10);
	
	if((_cur_x + _cur_w + x) > this.image.offsetWidth)
	{
		x = this.image.offsetWidth - (_cur_x + _cur_w);
	}
	else if((_cur_w + x) < 20)
	{
		x = 20 - _cur_w;
	}
	
	if((_cur_y + _cur_h + y) > this.image.offsetHeight)
	{
		y = this.image.offsetHeight - (_cur_y + _cur_h);
	}
	else if((_cur_h + y) < 20)
	{
		y = 20 -_cur_h;
	}
	
	return {'_x' : x, '_y' : y};
}

imageTools.prototype.checkResizeCoords = function(x, y)
{
	var _cur_x = parseInt(this.box.style.left, 10);
	var _cur_y = parseInt(this.box.style.top, 10);
	
	var _cur_w = parseInt(this.box.style.width, 10);
	var _cur_h = parseInt(this.box.style.height, 10);
	
	var _new_x = _cur_x + x;
	var _new_y = _cur_y + y;
		
	if(_new_x < 0)
	{
		 x = -_cur_x;
	}
	else if((_cur_x + _cur_w - _new_x)< 20)
	{
		x = _cur_w - 20;
	}
	
	if(_new_y < 0)
	{
		 y = -_cur_y;
	}
	else if((_cur_y + _cur_h - _new_y)< 20)
	{
		y = _cur_h - 20;
	}
	
	return {'_x' : x, '_y' : y};
}

imageTools.prototype.checkBoxCoords = function(x, y)
{
	var _cur_x = parseInt(this.box.style.left, 10);
	var _cur_y = parseInt(this.box.style.top, 10);
	
	var _new_x = _cur_x + x;
	var _new_y = _cur_y + y;
	
	if(_new_x < 0)
	{
		 _new_x = 0;
		 x = -_cur_x;
	}
	else if(_new_x >= (this.image.offsetWidth - parseInt(this.box.style.width, 10)))
	{
		_new_x = (this.image.offsetWidth - parseInt(this.box.style.width, 10));
		x = _new_x - _cur_x;
	}
	
	if(_new_y < 0)
	{
		 _new_y = 0;
		 y = -_cur_y;
	}
	else if(_new_y >= (this.image.offsetHeight - parseInt(this.box.style.height, 10)))
	{	
		_new_y = (this.image.offsetHeight - parseInt(this.box.style.height, 10));
		y = _new_y - _cur_y;
	}
	
	return {'x' : _new_x, 'y' : _new_y, '_x' : x, '_y' : y};
	
}

imageTools.prototype.boxMove = function(x, y)
{
	
	var pos = this.checkBoxCoords(x, y);
	this.setBoxPosition(pos.x, pos.y);
	
}

imageTools.prototype.setBoxPosition = function(x,y)
{
	this.box.style.left = x + 'px';
	this.box.style.top = y + 'px';
}
/*
imageTools.prototype.setBoxGlobalPosition = function()
{
	var bc = this.getPosition(this.box);
	this.box.setAttribute('globalX') = bc.x;
	this.box.setAttribute('globalY') = bc.y;
}
*/
imageTools.prototype.mouseUp = function(ev)
{
//	this.curTarget  = null;
	//if(this.boxMouseDownState)
	//{
		//this.setBoxGlobalPosition();

//	}
	if(this.resizeState && this.resizeType == "drZone")
	{
		this.proportionX = parseInt(this.box.style.width, 10) / parseInt(this.box.style.height, 10);
		this.proportionY = parseInt(this.box.style.height, 10) / parseInt(this.box.style.width, 10);
	}

	this.boxMouseDownState = false;
	this.resizeState = false;
	
//	this.labelTarget = null;
}

imageTools.prototype.boxMouseDown = function(ev){
	
	ev = ev || window.event;

	this.mouseDownPos = this.mouseCoords(ev);
//	this.checkPoint.x = 0;
//	this.checkPoint.y = 0;
	
	_log( 'mouse down (' + this.mouseDownPos.x + ', ' + this.mouseDownPos.y + ')' ) ;
	this.boxMouseDownState = true;
	
	Event.stop(ev);
	
	return false;
}