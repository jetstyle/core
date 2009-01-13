<?
  //constants
  if( !defined("CURRENT_LEVEL") ) define( "CURRENT_LEVEL", 2 );
  
  $_basedir = realpath(dirname(__FILE__).'../../../').'/';

  //include previous
  include( $_basedir."libs/core.osb/cms_new/config/config.php");
  // load frontend configs
  include( $_basedir."config/config.php");
  include( $_basedir."config/config_db.php");
  
  $_dir = dirname(__FILE__);
  
  //level-based directories
  $this->DIRS["scripts"][]      = $_dir."/";
  $this->DIRS["classes"][]      = $_dir."/classes/";
  $this->DIRS["modules"][]      = $_dir."/modules/";
  $this->DIRS["libs"][]         = $_dir."/../libs/";
  $this->DIRS["actions"][]      = $_dir."/actions/";
  $this->DIRS["templates"][]    = $_dir."/templates/";
  $this->DIRS["handlers"][]     = $_dir."/handlers/";
  $this->DIRS["message_sets"][] = $_dir."/message_sets/";
  
  //data-base settings
  $this->db_database = $this->db_name; // lucky@npj: из фронтенда
  $this->db_server = $this->db_host; // lucky@npj: из фронтенда

  
  if ($_GET['en']==1)
    $this->project_name.= "_eng";
  
  $this->project_title = "CMS: ".$this->project_title;
  
  $this->principal_cookie_domain = "dev.jetstyle.ru"; // FIXME: lucky@npj должен ставится не тут
  $this->pincipal_class = "PrincipalHash";
  $this->render_toolbar = true;
  $this->show_logs = false;

  $this->front_end->path_rel = $this->base_url; // lucky@npj: из фронтенда
	//
  $this->front_end->skin = "skins/site"; //для оберточных картинок в шаблонах вставки
  $this->front_end->file_dir = $_basedir.'files/'; 
  
  //настройки текстового контрола
  $this->htmlarea->style_sheets = "css/default.css,css/custom.css,css/inner.css,css/control.css";
  $this->htmlarea->body_class = "wrapper";//"wrapper wysiwyg";

//  $this->pictures->max_width = 170;
//  $this->pictures->max_height = 100;
  $this->pictures->strict = false;

  //template engine
  $this->templates_cache_dir = $_basedir."cache/cms/";

?>
