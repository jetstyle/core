function OSTemplates () {
	this.templates = new Array();
	this.values = new Array();
	this.to_load = new Array();
	this.loading_done = true;
	this.loading_index = -1;
	this.base_url = '';
	//render iframe
	this.iframe_id = "OST_loader";
	document.write("<iframe id=\""+ this.iframe_id + "\" style=\"display:none;\" onload=\"tpl.Receive()\"></iframe>");
}

//��������������� ���������� ������� �� ��������
//����������� �������, ����� �� ���������
OSTemplates.prototype.Load = function(){
	if( this.loading_index >= this.to_load.length-1 ){
		//�� ���������
		this.loading_done = true;
		window.status = "done";
	}else{
		//��� ����, ��� �������
		this.loading_index++;
		this.loading_done = false;
		var iframe = document.getElementById(this.iframe_id);
		iframe.src = this.base_url + 'parse/for_js/' + this.to_load[ this.loading_index ] + '?this=1';
		window.status = 'Loading templates: ' + this.to_load[ this.loading_index ];
	}
}

//��������� ����������� ������ �� iframe � ����� ��� � ������
//��������� �������� �������� ��������
//�������� � �������� �������� � body.onload
OSTemplates.prototype.Receive = function(){
		if(this.to_load[ this.loading_index ]==null) return;
		var iframe = document.getElementById(this.iframe_id);
		this.templates[ this.to_load[ this.loading_index ] ] = iframe.contentWindow.document.body.innerHTML;
		this.Load();
}

//������ �����
//���� ���� ���������� ���������� ��������
OSTemplates.prototype.Parse = function ( tpl_name, handler, append ){
	//��������� ��������
	if( !this.loading_done ){
	 	alert("��� �������� ��������.");
 		return;
	}
	if( tpl_name==null ){
		alert('OSTemplates::Parse - �� ������� ��� �������');
		return '';
	}
	//������ ������. �������� ��� ������������!!
	var re,template;

	template = this.templates[tpl_name];
	template = template.replace( /\%5B/g ,'[' );
	template = template.replace( /\%5D/g ,']' );
	if( template==null ) return '';
	for(key in this.values){
		eval('re = /\\\['+key+'\\\]/g;');
		template = template.replace( re, this.values[key] );
	}
	//��� ������ � �����������?
	if( handler!=null )
		this.values[handler] = ( append!=null && append ? this.values[handler] : '' ) + template;
	return template;
}

//����������� �������� ��������� ����������
OSTemplates.prototype.Assign = function ( handler, value, append ){
	//��������� ��������
	if( handler==null ){
		alert('OSTemplates::Assign - �� ������� ��� ����������');
		return '';
	}
	//������������
	if( value==null ) value = '';
	this.values[ handler ] = ( append!=null && append ? this.values[ handler ] : '' ) + value;
}

//��������� �������� ��������� ����������
OSTemplates.prototype.GetAssigned = function ( handler ){
	if( handler==null ) return '';
	else{
		var v = this.values[handler];
		return v!=null ? v : '';
	}
}

