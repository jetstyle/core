<?
  $rh =& $this->rh;

  $this->class_name = 'FormFiles';
  $this->table_name = $this->rh->project_name.'_pictures';
  $this->SELECT_FIELDS = array('id','title','descr','_state');
  $this->INSERT_FIELDS = array( 'topic_id'=>$this->rh->state->Keep( 'topic_id', 'integer') );
/*
  $this->FILES = array( 
    array('picture_small','file_small',array(),array($rh->pictures->max_width,$rh->pictures->max_height,$rh->pictures->strict)), 
    array('picture','file') 
  ); //префикс имени файла, имя инпута
*/
    $this->upload_dir = "pictures";

    $this->_FILES = array
    (
    'file_small' => array(
    	array(
    		'filename' => 'picture_preview_*',
    		'size' => array(100,100),
    		'crop' => false,
    		'base' => false,
    		'show' => 1,
            'take_from_if_empty' => array('file',0)
    		),
    	),
    'file' => array(
    	array(
    		'filename' => 'picture_*',
    		'size' => array(),
    		'crop' => false,
    		'base' => false,
    		'show' => 1,
    		),
    	)
    );
  $this->RENDER = array( array('_state','checkbox') );

  $rh->tpl->Assign('max_width',$rh->pictures->max_width);
  $rh->tpl->Assign('max_height',$rh->pictures->max_height);
  $rh->tpl->assign( "_strict", $rh->pictures->strict ? "строго" : "до" );
  
?>