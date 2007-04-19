	//какие шаблоны загрузить по умолчанию
	tpl.to_load = ['simple_block','pict','pict_preview'];
	
	//можно присваивать "быстрые шаблоны", например: 
	//tpl.templates['quick1'] = '<b>[text]</b>';

	//тулбар по умолчанию
	_editor_config.toolbar = [
		[  "formatblock", "space",
		  "bold", "italic", "underline", "strikethrough", "separator",
		  "subscript", "superscript", "separator",
		  "copy", "cut", "paste", "space", "undo", "redo" ],

		[ "insertorderedlist", "insertunorderedlist", "separator",
		  "inserthorizontalrule", "createlink", "button_insert_image", "inserttable", "button_simple_block", "separator",
		  "htmlmode", "showhelp", "about" ]
	];

	//русские название для типов текста	
	_editor_config.formatblock = {
		"Параграф": "p",
		"Заголовок 1": "h1",
		"Заголовок 2": "h2",
		"Заголовок 3": "h3",
		"Заголовок 4": "h4",
		"Заголовок 5": "h5",
		"Заголовок 6": "h6",
		"Адрес": "address",
		"Форматирование": "pre"
	};

	/*** регистрируем кнопки поумолчанию ***/
	
	//форматирование блоков
	_editor_config.registerButton({
	  id        : "button_simple_block",
	  tooltip   : "Форматировать блок",
	  image     : _editor_url + "images/ed_format_bold.gif",
	  textMode  : false,
	  action    : function(editor, id) { OSTFormatBlock( editor, 'simple_block' ) }
	});	

	//вставка иллюстраций
	_editor_config.registerButton({
	  id        : "button_insert_image",
	  tooltip   : "Вставить изображение",
	  image     : _editor_url + "images/ed_image.gif",
	  textMode  : false,
	  action    : function(editor, id) { OSTInsertImage( editor, 'pict', 'pict_preview' ) }
	});	

	
	//Мануальный фильтр
	_editor_config.ManualFilter = function(htmlarea){}