<?

  // прошиваем локали, чтобы у нас всё работало с case-sensitivity
  //setlocale(LC_CTYPE, array("ru_RU.CP1251","ru_SU.CP1251","ru_RU.KOI8-r","ru_RU","russian","ru_SU","ru"));

  //constants
  //if( !defined("CURRENT_LEVEL") ) define( "CURRENT_LEVEL", 0 );
	
	
	//include previous
	//include(  dirname(__FILE__)."/../core/config.php");
	
	$_dir = dirname(__FILE__);
	
	//level-based directories
	$DIRS = $c->get('DIRS');
	$DIRS["scripts"][] 			= $_dir.'/';
	$DIRS["classes"][] 			= $_dir.'/classes/';
	$DIRS["modules"][] 			= $_dir.'/modules/';
	$DIRS["libs"][] 				= $_dir.'/../libs/';
	$DIRS["actions"][] 			= $_dir.'/actions/';
	$DIRS["templates"][] 		= $_dir.'/templates/';
	$DIRS["handlers"][] 		= $_dir.'/handlers/';
	$DIRS["message_sets"][] = $_dir.'/message_sets/';
	$DIRS = $c->set('DIRS', $DIRS);
	
	//database
	$c->set_if_free('db_server',  "localhost");
	$c->set_if_free('db_user',  "nop");
	$c->set_if_free('db_password',  "123456");
	$c->set_if_free('db_database',  "dummy");
	
  //template engine
  $c->set_if_free('templates_cache_dir',  $_dir.'/_templates/');
  $c->set_if_free('PRE_FILTERS',  array("strip_comments"));//,"pack_spaces","gather_css",
  $c->set_if_free('POST_FILTERS',  array());
  $c->set_if_free('auto_css_temp_dir',  "css/_auto/");
  $c->set_if_free('auto_css_filename',  "css/auto.css");
	
  //misc
  $c->set_if_free('default_page',  'test');
  $c->set_if_free('hide_errors',  false);
	$c->set_if_free('path_class',  'Path');
	$c->set_if_free('project_name',  'OSB6');
	$c->set_if_free('show_logs',  true);

?>
