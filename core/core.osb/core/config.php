<?

// прошиваем локали, чтобы у нас всё работало с case-sensitivity
//setlocale(LC_CTYPE, array("ru_RU.CP1251","ru_SU.CP1251","ru_RU.KOI8-r","ru_RU","russian","ru_SU","ru"));

//constants
//if( !defined("CURRENT_LEVEL") ) define( "CURRENT_LEVEL", 0 );


//include previous
//include(  dirname(__FILE__)."/../core/config.php");

$_dir = dirname(__FILE__);

//level-based directories
$DIRS = $self->DIRS;
$DIRS["scripts"][] 			= $_dir.'/';
$DIRS["classes"][] 			= $_dir.'/classes/';
$DIRS["modules"][] 			= $_dir.'/modules/';
$DIRS["libs"][] 				= $_dir.'/../libs/';
$DIRS["actions"][] 			= $_dir.'/actions/';
$DIRS["templates"][] 		= $_dir.'/templates/';
$DIRS["handlers"][] 		= $_dir.'/handlers/';
$DIRS["message_sets"][] = $_dir.'/message_sets/';
$DIRS = JsConfig::replace($self, 'DIRS', $DIRS);

//database
JsConfig::set($self, 'db_server',  "localhost");
JsConfig::set($self, 'db_user',  "nop");
JsConfig::set($self, 'db_password',  "123456");
JsConfig::set($self, 'db_database',  "dummy");

//template engine
JsConfig::set($self, 'templates_cache_dir',  $_dir.'/_templates/');
JsConfig::set($self, 'PRE_FILTERS',  array("strip_comments"));//,"pack_spaces","gather_css",
JsConfig::set($self, 'POST_FILTERS',  array());
JsConfig::set($self, 'auto_css_temp_dir',  "css/_auto/");
JsConfig::set($self, 'auto_css_filename',  "css/auto.css");

//misc
JsConfig::set($self, 'default_page',  'test');
JsConfig::set($self, 'hide_errors',  false);
JsConfig::set($self, 'path_class',  'Path');
JsConfig::set($self, 'project_name',  'OSB6');
JsConfig::set($self, 'show_logs',  true);

?>
