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
  
  $this->debug->Trace("ADODB: before");
  
  //ADODB
  /* nop
  $this->UseLib("ADODB/adodb.inc");
  $this->db = ADONewConnection("mysql");
  $this->db->debug = false;
  $this->db->SetFetchMode(ADODB_FETCH_ASSOC);
  $this->db->Connect($this->db_server, $this->db_user, $this->db_password, $this->db_database);

  //очень полезная переменная для формиования имён таблиц
  if(!isset($this->db_prefix))
    $this->db_prefix = $this->project_name."_";
  $this->db->prefix = $this->db_prefix;

  */

  $this->UseClass("DBAL");
  $this->db =& new DBAL( $this, true );


  //обработчик ошибок для ADOdb
/*
  $this->UseClass("ADODB_Error");
  $this->db->raiseErrorFn = "ADODB_Error";
  
  $this->debug->Trace("ADODB: after");
  */
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
  
  if( $this->trace_logs )
    {
		$this->UseClass('Logs');
		$this->logs =& new Logs($this);
	}else{
		$this->UseClass('LogsDummy');
		$this->logs =& new LogsDummy($this);
	}
    
	$this->UseClass('Trash');
	$this->trash =& new Trash($this);  
?>
