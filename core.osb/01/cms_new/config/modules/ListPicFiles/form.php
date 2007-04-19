<?
	$this->class_name = 'FormFiles';
	$this->table_name = $this->rh->project_name.'_picfiles_lists';
	$this->SELECT_FIELDS = array('id','title','descr','_state');
	$this->INSERT_FIELDS = array( 'topic_id'=>$this->rh->state->Keep( 'topic_id', 'integer') );
	$this->FILES = array( array('picfile_lists','file') ); //,array('gif','jpg','pdf','doc','xls','rtf') //префикс имени файла, имя инпута
	$this->RENDER = array( array('_state','checkbox') );
	
?>