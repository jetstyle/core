<?php
	$this->class_name = 'ListAdvanced';
	$this->table_name = $this->rh->project_name.'_texts';
	$this->SELECT_FIELDS = array('id','title');
	$this->order_by = 'BINARY title ASC';
	$this->HIDE_CONTROLS['exchange'] = true;
	$this->outpice = 20;
	
?>