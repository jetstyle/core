/*
 * OSTreeControl class
 */

function OSTreeControl (tree) {
	this.tree = tree;
	this.current_id = false;
	this.state = 0; //0 - норма, 1 - куда перенести?
	//строки
	this.STRINGS = new Array();
	this.STRINGS['move as'] = 'Перенести...';
	this.STRINGS['add child'] = 'Добавить подрубрику';
	this.STRINGS['add brother'] = 'Добавить рядом';
	this.STRINGS['move as child'] = 'Перенести как подрубрику';
	this.STRINGS['move as brother'] = 'Перенести рядом';
	this.STRINGS['cansel'] = 'Отменить';
	//ссылка из объекта конфигурации
	webFXTreeConfig.control = this;
}

//отрисовка кнопок около узла
//вызывается из WebFXTreeItem.prototype.toString (xtree.js)
OSTreeControl.prototype.renderButtonsNode = function (node) {
	var controls = "<img id=\"" + node.id + "-control_1\" src=\"" + webFXTreeConfig.Control1Icon + "\" onclick=\"webFXTreeConfig.control.Touch_1('" + node.id + "');\" title=\""+this.STRINGS['move as']+"\">&nbsp;";
	controls += "<img id=\"" + node.id + "-control_2\" src=\"" + webFXTreeConfig.Control2Icon + "\" onclick=\"webFXTreeConfig.control.Touch_2('" + node.id + "');\" title=\""+this.STRINGS['add child']+"\">&nbsp;";
	controls += "<img id=\"" + node.id + "-control_3\" src=\"" + webFXTreeConfig.Control3Icon + "\" onclick=\"webFXTreeConfig.control.Touch_3('" + node.id + "');\" title=\""+this.STRINGS['add brother']+"\">";
	return controls;
}

//отрисовка кнопок около корня дерева
//вызывается из WebFXTreeItem.prototype.toString (xtree.js)
OSTreeControl.prototype.renderButtonsTree = function (node) {
	var controls = "<img id=\"" + node.id + "-control_2\" src=\"" + webFXTreeConfig.Control2Icon + "\" onclick=\"webFXTreeConfig.control.Touch_2('" + node.id + "');\" title=\""+this.STRINGS['add child']+"\">";
	return controls;
}

/*function OSTreeControl_ClickCancel () {
	alert(1);
}
window.onclick = OSTreeControl_ClickCancel();*/

OSTreeControl.prototype.MovePrepare = function (id) {
// alert('MovePrepare('+id+')');
	
	this.state = 1;
	this.current_id = id;
	this.RenderSelected(id);

	for( var _id in webFXTreeHandler.all )
		if(id!=_id) this.RenderAcceptor(_id);
	
//	window.onclick = OSTreeControl_ClickCancel();
}

OSTreeControl.prototype._CutFrom = function (node,id) {
	var parent = node.parentNode;
  for( i=0; i<parent.childNodes.length; i++ )
  	if( parent.childNodes[i].id==node.id ) break;
	if(i<parent.childNodes.length)
		parent.childNodes.splice(i,1);
	this._RenderNodeCont(parent);
	node.parentNode = null;
}

OSTreeControl.prototype.MoveAsChild = function (id) {
//	alert('MoveAsChild('+id+')');
//	return;
	var node = webFXTreeHandler.all[this.current_id];
  //проверки
  if( node.parentNode.id==id ) return;
  var _node = webFXTreeHandler.all[id];
  while(_node.parentNode){
  	if(_node.id==this.current_id) return;
  	_node = _node.parentNode;
  }
	//отрезать от старого предка
	this._CutFrom(node,id);
  //вставить в нового предка
  this.AddChild( id, node );
  //восстановиь дерево
  this.ResetTree();
}

OSTreeControl.prototype.MoveAsBrother = function (id) {
//	alert('MoveAsChild('+id+')');
//	return;
	var node = webFXTreeHandler.all[this.current_id];
  //проверки
	var parent = node.parentNode;
  for( i=1; i<parent.childNodes.length; i++ )
  	if( parent.childNodes[i].id==node.id &&
  	parent.childNodes[i-1].id==id ) return;
	//отрезать от старого предка
	this._CutFrom(node,id);
  //вставить в нового предка
  this.AddBrother( id, node );
  //восстановиь дерево
  this.ResetTree();
}

OSTreeControl.prototype.AddChild = function (id,node) {
// alert('AddChild('+id+')');

	//добавляем узел
	var parentNode = webFXTreeHandler.all[id];
	if(node==null){
		var node = new WebFXTreeItem('');
		node.text = 'node ['+node.id+']';
	}
	parentNode.add(node);

  //ставим узел на первое место
	if( parentNode.childNodes!=null && parentNode.childNodes.length > 1 ){
  	var tmp;
  	for(j=parentNode.childNodes.length-1;j>0;j--){
  		tmp = parentNode.childNodes[j];
			parentNode.childNodes[j] = parentNode.childNodes[j-1];
			parentNode.childNodes[j-1] = tmp;
		}	
	}
	this._RenderNodeCont(parentNode);

	if(!parentNode.open) parentNode.expand();     
}

OSTreeControl.prototype.AddBrother = function (id,node) {
// alert('AddBrother('+id+')');
	
	//добавляем узел
	var parentNode = webFXTreeHandler.all[id].parentNode;
	if(!node){
		var node = new WebFXTreeItem('');
		node.text = 'node ['+node.id+']';
	}
	parentNode.add(node);

  //ставим узел после себя
  for( i=0; i<parentNode.childNodes.length-1 && parentNode.childNodes[i].id!=id; i++ );
  if(i<parentNode.childNodes.length-1){
  	var tmp;
  	for(j=parentNode.childNodes.length-1;j>i+1;j--){
  		tmp = parentNode.childNodes[j];
			parentNode.childNodes[j] = parentNode.childNodes[j-1];
			parentNode.childNodes[j-1] = tmp;
		}	
		this._RenderNodeCont(parentNode);
	}
}

OSTreeControl.prototype.ResetTree = function () {
//	alert('ResetTree()');
	this.state = 0;
	this.current_id = false;
	for( var _id in webFXTreeHandler.all )
		this.RenderNormal(_id);
//	window.onclick = '';
}

/*
 * Функции отрисовки узлов
 */

OSTreeControl.prototype.RenderNormal = function (id) {
// alert('RenderNormal('+id+')');
	var o;
	if( o = document.getElementById(id + '-control_1') ) {
  	o.style.visibility = 'visible';
		o.title = this.STRINGS['move as'];
	}
	if( o = document.getElementById(id + '-control_2') ) {
  	o.style.visibility = 'visible';
		o.title = this.STRINGS['add child'];
	}
	if( o = document.getElementById(id + '-control_3') ) {
  	o.style.visibility = 'visible';
		o.title = this.STRINGS['add brother'];
	}
}

OSTreeControl.prototype.RenderSelected = function (id) {
// alert('RenderSelected('+id+')');
	var o;
	if( o = document.getElementById(id + '-control_1') ) {
		o.title = this.STRINGS['cansel'];
		document.getElementById(id + '-control_2').style.visibility = 'hidden';
		document.getElementById(id + '-control_3').style.visibility = 'hidden';
	}
}

OSTreeControl.prototype.RenderAcceptor = function (id) {
	//alert('RenderAcceptor('+id+')');
	var o;
	if( o = document.getElementById(id + '-control_1') )
		o.style.visibility = 'hidden';
	if( o = document.getElementById(id + '-control_2') )
		o.title = this.STRINGS['move as child'];
	if( o = document.getElementById(id + '-control_3') )
		o.title = this.STRINGS['move as brother'];
}

OSTreeControl.prototype._RenderNodeCont = function (node) {
//	alert('_RenderNodeCont:'+node.id);
	this._ResetAttrsSubtree(node);//._last = false;
	var str = '';
	for (var i = 0; i < node.childNodes.length; i++) {
		str += node.childNodes[i].toString(i,node.childNodes.length);
	}
	document.getElementById(node.id + '-cont').innerHTML = str;
}

OSTreeControl.prototype._ResetAttrsSubtree = function (node) {
//	alert(node.id+":"+node.childNodes.length);
	node._last = false;
	for (var i = 0; i < node.childNodes.length; i++)
		this._ResetAttrsSubtree(node.childNodes[i]);
}

/*
 * Обработка нажатий на кнопки узлов
 */

OSTreeControl.prototype.Touch_1 = function (id) {
	var node = webFXTreeHandler.all[id];
	if(this.state==0) this.MovePrepare(id);
	else this.ResetTree();
}

OSTreeControl.prototype.Touch_2 = function (id) {
	var node = webFXTreeHandler.all[id];
	if(this.state==0) this.AddChild(id);
	else this.MoveAsChild(id);
}

OSTreeControl.prototype.Touch_3 = function (id) {
	var node = webFXTreeHandler.all[id];
	if(this.state==0) this.AddBrother(id);
	else this.MoveAsBrother(id);
}


