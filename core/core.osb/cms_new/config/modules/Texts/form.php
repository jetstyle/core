<?
	$this->class_name = 'TextsForm';
	$this->table_name = $this->rh->project_name.'_texts';
	$this->SELECT_FIELDS = array('id','title','text','_state','_supertag','type');
	$this->RENDER = array(
		array('_state','checkbox'),
		array('type','select', array('текст с оформлением','плоский текст') ),
	);
	$this->PRE_FILTERS = array(
		'typo_outlink' => array('title','text'),
	);
	
?>