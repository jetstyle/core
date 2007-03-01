<?

  // прошиваем локали, чтобы у нас всё работало с case-sensitivity
  setlocale(LC_CTYPE, array("ru_RU.CP1251","ru_SU.CP1251","ru_RU.KOI8-r","ru_RU","russian","ru_SU","ru"));

  //constants
  if( !defined("CURRENT_LEVEL") ) define( "CURRENT_LEVEL", 0 );
	
	
	//include previous
	//include(  dirname(__FILE__)."/../core/config.php");
	
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
	
	//database
	$this->db_server = "localhost";
	$this->db_user = "nop";
	$this->db_password = "123456";
	$this->db_database = "dummy";
	
  //template engine
  $this->templates_cache_dir = $_dir.'/_templates/';
  $this->PRE_FILTERS = array("strip_comments");//,"pack_spaces","gather_css",
  $this->POST_FILTERS = array();
  $this->auto_css_temp_dir = "css/_auto/";
  $this->auto_css_filename = "css/auto.css";
	
  //misc
  $this->default_page = 'test';
  $this->hide_errors = false;
	$this->path_class = 'Path';
	$this->project_name = 'OSB6';
	$this->show_logs = true;

?>