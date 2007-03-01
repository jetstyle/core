<?
  //constants
  if( !defined("CURRENT_LEVEL") ) define( "CURRENT_LEVEL", 1 );
	
	//include previous
	include( dirname(__FILE__)."/../../core/config.php");
	
	$_dir = dirname(__FILE__);
	
	//level-based directories
	$this->DIRS["scripts"][] 			= $_dir.'/';
	$this->DIRS["classes"][] 			= $_dir.'/classes/';
	$this->DIRS["modules"][] 			= $_dir.'/modules/';
	$this->DIRS["libs"][] 				= $_dir.'/../libs/';
	$this->DIRS["actions"][] 			= $_dir.'/actions/';
	$this->DIRS["templates"][] 		= $_dir.'/templates/';
	$this->DIRS["handlers"][] 		= $_dir.'/handlers/';
	$this->DIRS["message_sets"][] = $_dir.'/message_sets/';
	
  //template engine
  $this->templates_cache_dir = $_dir.'/_templates/';
	
  //misc
  $this->default_page = "do";
	$this->path_class = "PathCMS";
	$this->project_name = 'esk';
	$this->project_title = 'супер КМС будущего';

	$this->pictures->max_width = 300;
	$this->pictures->max_height = 300;
  
  $this->toolbar_module_name = "ToolbarTree";

	//переводы разных режимов для подписей в обёртках
	$this->MODES_RUS = array(
		"tree"=>"рубрики",
		"topics"=>"рубрики",
		"list"=>"список",
		"form"=>"редактирование",
	);
	
?>