<?
	$this->class_name = 'ListAdvanced';
	$this->table_name = $this->rh->project_name.'_pictures';
	$this->SELECT_FIELDS = array('id','title');
	$this->where = "topic_id='".$this->rh->state->Keep( 'topic_id', 'integer')."'";
	
?>