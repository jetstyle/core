<?
//constants
//if( !defined("CURRENT_LEVEL") ) define( "CURRENT_LEVEL", 2 );

//include previous
$_dir = dirname(__FILE__);
config_seeConfig($config_loader, $self, 
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
config_replace($self, 'DIRS', $DIRS);

//template engine
config_set($self, 'templates_cache_dir',  $_dir.'/_templates/');

//misc
config_replace($self, 'default_page',  "do");
config_replace($self, 'path_class',  "PathCMS");
config_set($self, 'project_name',  'esk');
config_set($self, 'project_title',  'супер КМС будущего');

$pictures = config_get($self, 'pictures', new stdClass());
$pictures->max_width = 300;
$pictures->max_height = 300;
config_replace($self, 'pictures', $pictures);

config_replace($self, 'toolbar_module_name',  "ToolbarTree");

//переводы разных режимов для подписей в обёртках
config_replace($self, 'MODES_RUS', array(
	"tree"=>"рубрики",
	"topics"=>"рубрики",
	"list"=>"список",
	"form"=>"редактирование",
));

?>
