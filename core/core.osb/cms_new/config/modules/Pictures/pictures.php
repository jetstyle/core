<?
	
	//module config
	$this->module_title = "Изображения: позиции";
	$this->class_name = "TreeForm";
	$this->WRAPPED = array("list","form");
	$this->template = "tree_form.html";
	$this->rh->state->Keep( "topic_id", "integer");
	$this->_href_template = $this->rh->path_rel."do/".$this->module_name."/pictures?".$this->rh->state->State();
	
?>