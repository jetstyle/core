<?
//constants
//if( !defined("CURRENT_LEVEL") ) define( "CURRENT_LEVEL", 2 );

//include previous
$_dir = dirname(__FILE__);
JsConfig::seeConfig($js_config_loader, $self, 
	realpath($_dir.'/../../core/'), 'config');
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
JsConfig::replace($self, 'DIRS', $DIRS);

//template engine
JsConfig::set($self, 'templates_cache_dir',  $_dir.'/_templates/');

//misc
JsConfig::replace($self, 'default_page',  "do");
JsConfig::replace($self, 'path_class',  "PathCMS");
JsConfig::set($self, 'project_name',  'esk');
JsConfig::set($self, 'project_title',  'супер КМС будущего');

$pictures = JsConfig::get($self, 'pictures', new stdClass());
$pictures->max_width = 300;
$pictures->max_height = 300;
JsConfig::replace($self, 'pictures', $pictures);

JsConfig::replace($self, 'toolbar_module_name',  "ToolbarTree");

//переводы разных режимов для подписей в обёртках
JsConfig::replace($self, 'MODES_RUS', array(
	"tree"=>"рубрики",
	"topics"=>"рубрики",
	"list"=>"список",
	"form"=>"редактирование",
));

?>
