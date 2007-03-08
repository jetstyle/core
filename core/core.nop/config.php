<?php
// ������ �������� ������
// (*) -- ������ ��������
// (+) -- �������� ����� ������, by-default ���������� � production single-site version
// (!) -- ����������� ������!

$_basedir = realpath(dirname(__FILE__).'../../../').'/';

include( $_basedir."config/config.php" ); // ���������� ������
include( $_basedir."config/config_db.php" ); // ���������� ������ ��
include( $_basedir."config/config_handlers.php" );

// ��������� ���������� ������
$this->tpl_markup_level  = 0; // TPL_MODE_CLEAN     (*)
$this->tpl_compile       = 1; // TPL_COMPILE_SMART  (*)
$this->tpl_root_dir      = "../"; // (+) or "" or "themes/" -- ��� ����� �����
$this->tpl_root_href     = "/";   // (+) or "/themes/"         -- ��� ��������� �� URL �� ���
// lucky@npj -- required in config/config.php
#$this->tpl_skin          = "";    // (*) for no-skin-mode which is default
$this->tpl_skin_dirs     = array( "css", "js", "images" ); // -- ����� �������� �������

// ��������� ���������� ������
$this->tpl_action_prefix      = "rockette_action_";
$this->tpl_template_prefix    = "rockette_template_";
$this->tpl_template_sepfix    = "__";
$this->tpl_action_file_prefix   = "@@"; 
$this->tpl_template_file_prefix = "@";
$this->tpl_cache_prefix = "@";  // � ����� �������� ���������� � ���� ��� ���������� TE
$this->tpl_prefix = "{{";
$this->tpl_postfix = "}}";
$this->tpl_instant = "~";
$this->tpl_construct_action   = "!";    // {{!text Test}}
$this->tpl_construct_action2  = "!!";   // {{!!text}}Test{{!!/text}}
$this->tpl_construct_if       = "?";    // {{?var}} or {{?!var}}
$this->tpl_construct_ifelse   = "?:";   // {{?:}} 
$this->tpl_construct_ifend    = "?/";   // {{?/}} is similar to {{/?}}
$this->tpl_construct_object   = "#.";   // {{#obj.property}}
$this->tpl_construct_tplt     = "TPL:"; // {{TPL:Name}}...{{/TPL:Name}}
$this->tpl_construct_tplt2    = ":"; // {{:Name}}...{{/:Name}}   -- ru@jetstyle ����� ����� TPL � �����
$this->tpl_construct_comment  = "#";    // <!-- # persistent comment -->
// lucky: 
$this->tpl_construct_standard_camelCase  = True;    
																  // True, ������ �������, ��� ����� � ��������� CamelCase
																  // �.�. ������ �������� ����� ���
																  // $o->SomeValue(), 
																  // ����� ����� �������, ��� ���������� ruby 
																  // $o->some_value()
																  // (�� ������, ���� ru ������ ���������� �����������)
$this->tpl_construct_standard_getter_prefix  = 'get';    // lucky: �������� ��� getter'�� 

$this->tpl_instant_plugins = array( "dummy" ); // plugins that are ALWAYS instant

$this->shortcuts = array(
	"=>" => array("=", " typografica=1"),
	"=<" => array("=", " strip_tags=1"),
	"+>" => array("+", " typografica=1"),
	"+<" => array("+", " strip_tags=1"),
	"*" => "#*.",
	"@" => "!include ",
	"=" => "!text ",
	"+" => "!message ",
);

// message set defaults
$this->msg_default = "ru"; 

// ������ ���������
$this->cache_dir              = $_basedir."cache/web/"; // (+) or "../project.zcache/" -- ���� ������ ���

// ��� ��������� ���������: ���������� ��
$this->lib_href_part          = "libs"; // ��� ������� �� ��������. ��� �����!
$this->lib_dir                = $this->lib_href_part; 

$this->magic_word             = "I luv rokket"; // ���������� ����� ��� ��������� ������ ������������������

// ������ ����
$this->url_allow_direct_handling = false; // �������� ���������� URL � ���� �� ��������

// ��������� ���
$this->cookie_prefix      = ""; // (+) ������� ���� ���. ��������� ���������� ������ �� ����� ������ ���������� ������������ � �����
$this->cookie_expire_days = 60; // ������� ���� ��������� ������������ ����

// ----- fROM config
// ���������� � �������� ���������� ������, relative to which we look for classes
$this->DIRS[] = $_basedir.'web/';
//for templates FindScripting
$this->DIRS[] = $_basedir.'web/skins/'.$this->tpl_skin."/";

// ��������� ����������:
// TODO: wtf
$this->principal_storage_model = "db"; 
$this->principal_security_models = array( "tree", "role", "noguests");

// ��������� ������� ����
//TODO: wtf
$this->url_reserved_words    = array( "edit", "add", "delete", "tree", "ajaxupload", "getfile","post" ); // ����� ������ ���� � ��������� ����� (�������)
$this->url_site_handlers     = array( "login", "register", "activation","tagpages" ); // ����� ������ ���� � ������ �����

$this->url_default_handler   = "show"; // ��� ���������� ������������� �������

// ����������� ���������, ������� �� ��������� ������ �����������
// TODO: wtf

$this->tpl_root_href_part  = "web/skins/";
$this->tpl_clientside_part = "petarde/clientside/";   

//  $this->tpl_root_dir      = $this->base_url."skins/";  // or "../" or "" -- ��� ����� �����
$this->tpl_root_href     = $this->base_url."web/skins/"; // or "/"         -- ��� ��������� �� URL �� ���

$this->tpl_root_dir  = $_basedir.$this->tpl_root_href_part;

$this->admin_email = "nop@jetstyle.ru";
$this->message_set = "";
$this->cookie_prefix = $this->project_name.'_';

$this->timezone = 0; // GMT+5


include( $this->tpl_root_dir.$this->tpl_skin."/site_map.php" );

// ----- end fROM config

// ���������� � �������� ���������� ������
$this->DIRS[] = dirname(__FILE__).'/';
?>
