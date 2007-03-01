/*
 * OSTreeControl class
 */

function OSTreeControl (tree) {
	this.tree = tree;
	this.current_id = false;
	this.state = 0; //0 - �����, 1 - ���� ���������, 2 - ��� �������� ������ �� ������
	this.new_node = null;
	//������
	this.STRINGS = new Array();
	this.STRINGS['move as'] = '���������...';
	this.STRINGS['add child'] = '�������� ����������';
	this.STRINGS['add brother'] = '�������� �����';
	this.STRINGS['move as child'] = '��������� ��� ����������';
	this.STRINGS['move as brother'] = '��������� �����';
	this.STRINGS['cansel'] = '��������';
	//������ �� ������� ������������
	webFXTreeConfig.control = this;
	//������ ������ �����
	this.connect = new OSTreeConnect(this);
	document.write(this.connect);
}

//��������� ������ ����� ����
//���������� �� WebFXTreeItem.prototype.toString (xtree.js)
OSTreeControl.prototype.renderButtonsNode = function (node) {
	var ban = webFXTreeConfig.buttons_ban[node.db_id];
	var controls = '';
	//������� ����������
	if( node.db_state==2)
		controls += "<font color='red'>�����</font>&nbsp;";
	//������ �����������
	if( !webFXTreeConfig.ban_all_buttons && (ban==null || !ban[0]) )
		controls += "<img id=\"" + node.id + "-control_1\" src=\"" + webFXTreeConfig.Control1Icon + "\" onclick=\"webFXTreeConfig.control.Touch_1('" + node.id + "');\" title=\""+this.STRINGS['move as']+"\">&nbsp;";
	else controls += "<img src=\"" + webFXTreeConfig.blankIcon + "\">&nbsp;";
	//���������� �������, ����������� ��� �������
	if( node._level<webFXTreeConfig.level_limit-1 && !webFXTreeConfig.ban_all_buttons && (ban==null || !ban[1]) )
		controls += "<img id=\"" + node.id + "-control_2\" src=\"" + webFXTreeConfig.Control2Icon + "\" onclick=\"webFXTreeConfig.control.Touch_2('" + node.id + "');\" title=\""+this.STRINGS['add child']+"\">&nbsp;";
	else controls += "<img src=\"" + webFXTreeConfig.blankIcon + "\" >&nbsp;";
	//���������� ������, ����������� ��� ������
	if( !webFXTreeConfig.ban_all_buttons && (ban==null || !ban[2]) )
		controls += "<img id=\"" + node.id + "-control_3\" src=\"" + webFXTreeConfig.Control3Icon + "\" onclick=\"webFXTreeConfig.control.Touch_3('" + node.id + "');\" title=\""+this.STRINGS['add brother']+"\">";
	else controls += "<img src=\"" + webFXTreeConfig.blankIcon + "\">&nbsp;";
	return controls;
}

//��������� ������ ����� ����� ������
//���������� �� WebFXTreeItem.prototype.toString (xtree.js)
OSTreeControl.prototype.renderButtonsTree = function (node) {
	var ban = webFXTreeConfig.buttons_ban[0];
	var controls = '';
	if( !webFXTreeConfig.ban_all_buttons && (ban==null || !ban[0]) )
		controls = "<img id=\"" + node.id + "-control_2\" src=\"" + webFXTreeConfig.Control2Icon + "\" onclick=\"webFXTreeConfig.control.Touch_2('" + node.id + "');\" title=\""+this.STRINGS['add child']+"\">";
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
  //��������
  if( node.parentNode.id==id ) return false;
  var _node = webFXTreeHandler.all[id];
  while(_node.parentNode){
  	if(_node.id==this.current_id) return false;
  	_node = _node.parentNode;
  }
	//�������� �� ������� ������
	this._CutFrom(node,id);
  //�������� � ������ ������
  this.AddChild( id, node );
  //����������� ������ - ����������� ����� ������ � �������
//  this.ResetTree();
	return true;
}

OSTreeControl.prototype.MoveAsBrother = function (id) {
//	alert('MoveAsChild('+id+')');
//	return;
	var node = webFXTreeHandler.all[this.current_id];
  //��������
	var parent = node.parentNode;
  for( i=1; i<parent.childNodes.length; i++ )
  	if( parent.childNodes[i].id==node.id &&
  	parent.childNodes[i-1].id==id ) return false;
	//�������� �� ������� ������
	this._CutFrom(node,id);
  //�������� � ������ ������
  this.AddBrother( id, node );
  //����������� ������ - ����������� ����� ������ � �������
//  this.ResetTree();
	return true;
}

OSTreeControl.prototype.AddChild = function (id,node) {
// alert('AddChild('+id+')');

	//��������� ����
	var parentNode = webFXTreeHandler.all[id];
	if(node==null){
		var node = new WebFXTreeItem('');
		node.text = 'node ['+node.id+']';
	}
	parentNode.add(node);

  //������ ���� �� ������ �����
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
	
	return node;
}

OSTreeControl.prototype.AddBrother = function (id,node) {
// alert('AddBrother('+id+')');
	
	//��������� ����
	var parentNode = webFXTreeHandler.all[id].parentNode;
	if(!node){
		var node = new WebFXTreeItem('');
		node.text = 'node ['+node.id+']';
	}
	parentNode.add(node);

  //������ ���� ����� ����
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
	
	return node;
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
 * ������� ��������� �����
 */

OSTreeControl.prototype.RenderNormal = function (id) {
// alert('RenderNormal('+id+')');
	var o;
	if( o = document.getElementById(id + '-control_1') ) {
  	o.style.visibility = 'visible';
		o.title = this.STRINGS['move as'];
		o.src = webFXTreeConfig.Control1Icon;
	}
	if( o = document.getElementById(id + '-control_2') ) {
  	o.style.visibility = 'visible';
		o.title = this.STRINGS['add child'];
		o.src = webFXTreeConfig.Control2Icon;
	}
	if( o = document.getElementById(id + '-control_3') ) {
  	o.style.visibility = 'visible';
		o.title = this.STRINGS['add brother'];
		o.src = webFXTreeConfig.Control3Icon;
	}
}

OSTreeControl.prototype.RenderSelected = function (id) {
// alert('RenderSelected('+id+')');
	var o;
	if( o = document.getElementById(id + '-control_1') ) {
		o.title = this.STRINGS['cansel'];
		o.src = webFXTreeConfig.Control4Icon;
		if( o = document.getElementById(id + '-control_2') ) 
      o.style.visibility = 'hidden';
		if( o = document.getElementById(id + '-control_3') ) 
  		o.style.visibility = 'hidden';
	}
}

OSTreeControl.prototype.RenderAcceptor = function (id) {
	//alert('RenderAcceptor('+id+')');
	var o;
	if( o = document.getElementById(id + '-control_1') )
		o.style.visibility = 'hidden';
	if( o = document.getElementById(id + '-control_2') ){
		o.title = this.STRINGS['move as child'];
		o.src = webFXTreeConfig.Control5Icon;
	}
	if( o = document.getElementById(id + '-control_3') ){
		o.title = this.STRINGS['move as brother'];
		o.src = webFXTreeConfig.Control6Icon;
	}
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
 * ��������� ������� �� ������ �����
 */

OSTreeControl.prototype.Touch_1 = function (id) {
	//��� ������ �������
	if(this.state==2) return;
	//������ ����
	var node = webFXTreeHandler.all[id];
	if(this.state==0) this.MovePrepare(id);
	else this.ResetTree();
}

OSTreeControl.prototype.Touch_2 = function (id) {
	//��� ������ �������
	if(this.state==2) return;
	//������ ����
	var node = webFXTreeHandler.all[id];
	if(this.state==0){
		this.new_node = this.AddChild(id);
		this.Save('&add=1&parent=' + ( node.db_id!=null ? node.db_id : 0) );
	}else{
		if( this.MoveAsChild(id) )
			this.Save();
	}
}

OSTreeControl.prototype.Touch_3 = function (id) {
	//��� ������ �������
	if(this.state==2) return;
	//������ ����
	var node = webFXTreeHandler.all[id];
	if(this.state==0){
		this.new_node = this.AddBrother(id);
		this.Save('&add=1&brother=' + ( node.db_id!=null ? node.db_id : 0) );
	}else{
		if( this.MoveAsBrother(id) )
			this.Save();
	}
}

/*
 * ������� ����������
 */

//��������.
//��������� get-������ �� �������� �������� ��� ������� ����
OSTreeControl.prototype.TreeDump = function (node) {
	//��������� ������ ��� ����� ����
	var id = ( node.db_id!=null ? node.db_id : 0 );
	var str = '';
	if( node.childNodes.length>0 ){
		str = '&ids[]=' + id + '&children_' + id + '=';
		var i;
		for(i=0;i<node.childNodes.length;i++)
			str += ( i ? ':' : '' ) + ( node.childNodes[i].db_id!=null ? node.childNodes[i].db_id : 0 );
	}
	//��������� ������ ��� �����
	for(i=0;i<node.childNodes.length;i++)
		str += this.TreeDump( node.childNodes[i], str );
	return str;
}

//��������� ��������� ������ �� ������
OSTreeControl.prototype.Save = function ( add_to ) {
	if(add_to==null) add_to = '';
	this.state = 2;
	//����������� ������
	var get_str = this.TreeDump(this.tree);
	//�������� ����� ������ �����
//	alert(add_to+'&'+get_str);
	this.connect.Send( add_to+get_str );
}

//����������� ID ��� ������ ����, ���� ����� ����
OSTreeControl.prototype.SetNewID = function ( new_id, new_action ) {
	if( this.new_node!=null ){
		this.new_node.db_id = new_id;
		this.new_node.text = 'node_'+new_id;
		this.new_node.action = new_action;
		this._RenderNodeCont(this.new_node.parentNode);
//		alert('New ID: '+this.new_node.db_id+', for '+this.new_node.id);
		this.new_node = null;
	}
}
/*
 * OSTreeConnect class,
 * ����� � ��������:
 * - �������� ����������� ��������
 * - ���������� ������ �� ����������, ���������� � �������
 */

function OSTreeConnect (control) {
	this.frame_id = "OSTreeConnect_iframe";
	this.bar_id = "OSTreeConnect_fltbar";
	//������� � ���������
	this.control = control;
}

OSTreeConnect.prototype.toString = function() {
	//������ �����
	var str = "<iframe id=\""+this.frame_id+"\" style=\"display:none;\"></iframe>";
//  var str = "<iframe id=\""+this.frame_id+"\" ></iframe>";
	//������ ��� � ���������� "����������"
	str += "<div id=\""+this.bar_id+"\" style=\"visibility:hidden; position:absolute; left:80px; top:59px; width:153px; height:27px; z-index:100; background-color: #CCCCCC; layer-background-color: #CCCCCC; border: 1px none #000000; color: #000000; font-family: Arial, Helvetica, sans-serif; text-align: center; vertical-align: middle;\" >����������...</div>";
	//���������� ���������
	return str;
};

OSTreeConnect.prototype.Send = function(get_str) {
//	alert('send');
	//�������� ������
	var frame = document.getElementById(this.frame_id);
	frame.src = webFXTreeConfig.url_connect + get_str;
//	alert(webFXTreeConfig.url_connect + get_str);
//	frame.document.write(webFXTreeConfig.url_connect + get_str);
	//���������� ��������
	this.RenderBar('visible');
};

OSTreeConnect.prototype.Receive = function() {
//	alert('�������');
	//����������� ID ������ ����, ���� ����� ���
	this.control.SetNewID( this.new_id, this.new_action );
	//������ ��������
	this.RenderBar('hidden');
	//������� ����� �� ������
//	var frame = document.getElementById(this.frame_id);
//	frame.src = '';
	//�������� ������ � ���������� ���������
	this.control.ResetTree();
};

OSTreeConnect.prototype.RenderBar = function(vis) {
	var bar = document.getElementById(this.bar_id);
 	bar.style.visibility = vis;
}



