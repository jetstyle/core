<?php
	$this->class_name = 'FormTexts';
	$this->table_name = 'texts';
	$this->SELECT_FIELDS = array('id','title','text','_state','_supertag','type');
	$this->RENDER = array(
		array('_state','checkbox'),
		array('type','select', array('текст с оформлением','плоский текст') ),
	);
	
	$this->PRE_FILTERS = array(
		'typografica' => array('title','text'),
	);
?>