<?php
	$this->class_name = 'ListSimple';
	$this->table_name = 'texts';
	$this->SELECT_FIELDS = array('id','title');
	$this->order_by = 'title ASC';
	$this->HIDE_CONTROLS['exchange'] = true;
?>