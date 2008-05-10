<?php
	$this->class_name = 'ListSimple';
	$this->table_name = 'picfiles';
	$this->SELECT_FIELDS = array('id','title');
	$this->where = "topic_id='".intval($this->rh->ri->get('topic_id'))."'";
?>