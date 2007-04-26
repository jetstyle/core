<?
	$this->upload_dir = "picfiles";

	$this->class_name = 'FormFiles';
	$this->table_name = $this->rh->project_name.'_picfiles';
	$this->SELECT_FIELDS = array('id','title','descr','_state');
	$this->INSERT_FIELDS = array( 'topic_id'=>$this->rh->state->Keep( 'topic_id', 'integer') );
    
	//$this->_FILES = array( array('picfile_*','file',array()) ); //префикс имени файла, имя инпута
	$this->RENDER = array( array('_state','checkbox') );
    $this->max_file_size = $this->rh->max_file_size;

    $this->_FILES = array
    (
        'file' => array
        (
        	array
            (
    		'filename' => 'picfile_*',
    		'show' => 1,
    		),
   	    ),
    );

?>