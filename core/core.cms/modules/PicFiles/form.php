<?php
	$this->class_name = 'FormFiles';
	$this->table_name = 'picfiles';
	$this->SELECT_FIELDS = array('id','title','descr','_state');
	$this->INSERT_FIELDS = array( 'topic_id' => intval($this->rh->ri->get('topic_id')));

	$this->RENDER = array( array('_state','checkbox') );
    $this->max_file_size = $this->rh->max_file_size;

    include($this->rh->findScript_('modules', $this->moduleName.'/_files'));
?>