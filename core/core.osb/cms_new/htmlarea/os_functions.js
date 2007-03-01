	//форматирование блоков
	function OSTFormatBlock(editor, tpl_name) {
		tpl.Assign('text',editor.getSelectedHTML());
		editor.insertHTML(tpl.Parse(tpl_name));
	}
	
	//вставка иллюстраций
	function OSTInsertImage(editor, tpl_pict, tpl_preview ) {
//		alert('Insert image: ' + tpl_pict + ', ' + tpl_preview );
		editor._popupDialog("../../pictures/", function(param) {
			//нажаили "отмена"
			if (!param)	return false;
			//вставляем картинку
			var tpl_name;
			if( param['f_url_big']!='' ){
				tpl_name = tpl_preview;
				tpl.Assign('HREF',"javascript:pictwnd('"+param['f_url_big']+"','pict_view','top=100,left=100,width="+param['f_big_width']+",height="+param['f_big_height']+"')" );				
			}else{
				tpl_name = tpl_pict;
				tpl.Assign('HREF','');
			}
			tpl.Assign('IMAGE',param['f_url']);
			tpl.Assign('ALIGN',param['f_align']);
			tpl.Assign('WIDTH',param['f_width']);
			tpl.Assign('HEIGHT',param['f_height']);
			tpl.Assign('ALT',param['f_alt']);
//			editor.insertHTML(tpl.Parse(tpl_name));
			editor.insertHTML( '</p>'+tpl.Parse(tpl_name)+'<p>' );
      //иногда полезно фильтровать весь текст после операции
//      editor.config.ManualFilter(editor); //прибиваем пустые параграфы
		});
	}
	
