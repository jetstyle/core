/*
 * реализация сабмита формы без перезагрузки
 */
 
var osfl_form_id = false;
 
function OSFormSend(form_id){
	document.getElementById(form_id+'_iframe').onload = OSFormRecieve;
	var form = document.getElementById(form_id);
	if( form==null ){
		alert('OSFL: Не найдена форма ['+form_id+'].');
		return;
	}	
	osfl_form_id = form_id;
	form.target = form_id + '_iframe';
	form.no_response.value = 1;
	form.update_2.click();
	form.update_1.disabled = true;
	form.update_2.disabled = true;
//	form.submit();
}

function OSFormRecieve(){
	if( !osfl_form_id )	return;
	var form = document.getElementById(osfl_form_id);
	form.no_response.value = 0;
	form.update_1.disabled = false;
	form.update_2.disabled = false;
	form.target = '';
}

