/**************************************
used in list simple (items exchange)
**************************************/

/*** NodePicker object ***/

function NodePicker(form_name) {
	this.form_name = form_name;
	this.source = null;
	this.target = null;
	this.mode = null;
};

NodePicker.prototype.Pick = function(id,mode) {
	if( this.source==null || this.source==id ){// || this.mode!=mode 
		this.source = id;
		this.mode = mode;
	}else{
		this.target = id;
		this.Execute();
		this.source = this.target = this.mode = null;
	}		
};

NodePicker.prototype.Execute = function() {
//	alert( this.source +" -> "+ this.target + "\nwith mode " + this.mode );
	var form = document.getElementById(this.form_name);
	form.id1.value = this.source;
	form.id2.value = this.target;
	if( this.mode==1 ){
		if(!this.source) return;
		form.action.value = "move_under";
	}
	else if( this.mode==2 ){
		if(!this.source || !this.target) return;
		form.action.value = "exchange";
	}
	form.submit();
}

/*** some functions ***/
/*function ZH_Restore(id){
	var form = document.getElementById(this.form_name);
	form.id1.value = id;
	form.action.value = "restore";
	form.submit();	
}

function ZH_Delete(id,hard){
	var form = document.getElementById(this.form_name);
	form._delete.value = id;
	form.hard.value = hard;
	form.action.value = "delete";
	var confirm_str = (hard)? "Удалить окончательно?" : "Переместить в корзину?";
	if(confirm(confirm_str)) form.submit();	
}
*/