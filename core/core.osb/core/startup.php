<?
  
  //debug
  if(!$this->show_logs) $this->show_logs = $this->GetVar('logs')=='show';
  if( $this->show_logs ){
    $this->UseClass("Debug");
    $this->debug =& new Debug();
  //  $this->old_error_handler = set_error_handler( array($this->debug,"ErrorHandler") ); 
    //возможно, скрываем сообщения об ошибках
  //  if($this->hide_errors) error_reporting (FATAL | ERROR | WARNING); 
  }else{
    $this->UseClass("DebugDummy");
    $this->debug =& new DebugDummy();
  }
  $GLOBALS['debug_hook'] =& $this->debug;
  
  //state set
  $this->UseClass("StateSet");
  $this->state =& new StateSet($this);
  
  //classes
  $this->UseClass("Module");
  
  //libs
  $this->debug->Trace("DBAL: before");

  $this->UseClass("DBAL");
  $this->db =& new DBAL( $this, true );
if($this->db_set_encoding)
{
	$this->db->query("SET CHARACTER SET ".$this->db_set_encoding);
}


  //template engine
  $this->UseClass("OSFastTemplateWrapper");
  $this->tpl =& new OSFastTemplateWrapper($this);
  $this->tpl->CFG['mark'] = $this->GetVar('mark');
  
  //кэш объектов
  $this->UseClass('ObjectCache');
  $this->cache =& new ObjectCache($this);
  
  //principal
  if( !$this->pincipal_class ) $this->pincipal_class = 'PrincipalHash';
  $this->UseClass( $this->pincipal_class );

  eval('$this->prp =& new '.$this->pincipal_class.'($this);');
  
  //predefined template variables
  $this->tpl->assign('/',$this->path_rel);
  $this->tpl->assign('images',$this->path_rel.'images/');
  $this->tpl->assign('css',$this->path_rel.'css/');
  $this->tpl->assign('js',$this->path_rel.'js/');
  
  $this->tpl->assign('project_title',$this->project_title);
  
  $this->debug->Trace("startup done");


?>
