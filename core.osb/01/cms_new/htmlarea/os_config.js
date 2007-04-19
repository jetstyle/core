	//����� ������� ��������� �� ���������
	tpl.to_load = ['simple_block','pict','pict_preview'];
	
	//����� ����������� "������� �������", ��������: 
	//tpl.templates['quick1'] = '<b>[text]</b>';

	//������ �� ���������
	_editor_config.toolbar = [
		[  "formatblock", "space",
		  "bold", "italic", "underline", "strikethrough", "separator",
		  "subscript", "superscript", "separator",
		  "copy", "cut", "paste", "space", "undo", "redo" ],

		[ "insertorderedlist", "insertunorderedlist", "separator",
		  "inserthorizontalrule", "createlink", "button_insert_image", "inserttable", "button_simple_block", "separator",
		  "htmlmode", "showhelp", "about" ]
	];

	//������� �������� ��� ����� ������	
	_editor_config.formatblock = {
		"��������": "p",
		"��������� 1": "h1",
		"��������� 2": "h2",
		"��������� 3": "h3",
		"��������� 4": "h4",
		"��������� 5": "h5",
		"��������� 6": "h6",
		"�����": "address",
		"��������������": "pre"
	};

	/*** ������������ ������ ����������� ***/
	
	//�������������� ������
	_editor_config.registerButton({
	  id        : "button_simple_block",
	  tooltip   : "������������� ����",
	  image     : _editor_url + "images/ed_format_bold.gif",
	  textMode  : false,
	  action    : function(editor, id) { OSTFormatBlock( editor, 'simple_block' ) }
	});	

	//������� �����������
	_editor_config.registerButton({
	  id        : "button_insert_image",
	  tooltip   : "�������� �����������",
	  image     : _editor_url + "images/ed_image.gif",
	  textMode  : false,
	  action    : function(editor, id) { OSTInsertImage( editor, 'pict', 'pict_preview' ) }
	});	

	
	//���������� ������
	_editor_config.ManualFilter = function(htmlarea){}