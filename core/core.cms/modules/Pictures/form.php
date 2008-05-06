<?php
  $rh =& $this->rh;

  $this->class_name = 'FormFiles';
  $this->table_name = $this->rh->project_name.'_pictures';
  $this->SELECT_FIELDS = array('id','title','descr','_state');
  $this->INSERT_FIELDS = array( 'topic_id'=>$this->rh->state->Keep( 'topic_id', 'integer') );

  include($this->rh->findScript('modules', $this->module_name.'/_files'));
  
  $this->RENDER = array( array('_state','checkbox') );

//  $rh->tpl->set('max_width',$rh->pictures->max_width);
//  $rh->tpl->set('max_height',$rh->pictures->max_height);
//  $rh->tpl->set( "_strict", $rh->pictures->strict ? "строго" : "до" );
  
?>