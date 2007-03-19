<?
  //constants
  //if( !defined("CURRENT_LEVEL") ) define( "CURRENT_LEVEL", 2 );
	
	//include previous
	$_dir = dirname(__FILE__);
	JsContext::loadConfig($c, realpath($_dir.'/../../core/'), 'config');
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
	$c->set('DIRS', $DIRS);
	
  //template engine
  $c->set_if_free('templates_cache_dir',  $_dir.'/_templates/');
	
  //misc
  $c->set('default_page',  "do");
	$c->set('path_class',  "PathCMS");
	$c->set_if_free('project_name',  'esk');
	$c->set_if_free('project_title',  '����� ��� ��������');

	$pictures = $c->get_or_default('pictures', new stdClass());
	$pictures->max_width = 300;
	$pictures->max_height = 300;
	$c->set('pictures', $pictures);
  
  $c->set('toolbar_module_name',  "ToolbarTree");

	//�������� ������ ������� ��� �������� � �������
	$c->set('MODES_RUS', array(
		"tree"=>"�������",
		"topics"=>"�������",
		"list"=>"������",
		"form"=>"��������������",
	));
	
?>
