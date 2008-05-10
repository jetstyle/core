<?php
  $this->class_name = 'FormFiles';
  $this->table_name = 'pictures';
  $this->SELECT_FIELDS = array('id','title','descr','_state');
  $this->INSERT_FIELDS = array( 'topic_id' => intval($this->rh->ri->get('topic_id')) );

  include($this->rh->findScript_('modules', $this->moduleName.'/_files'));
  
  $this->RENDER = array( array('_state','checkbox') );
?>