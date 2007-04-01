<?php
// ������ �������� ������
// (*) -- ������ ��������
// (+) -- �������� ����� ������, by-default ���������� � production single-site version
// (!) -- ����������� ������!

// lucky: ������������� index.php
$_base_dir = $self->project_dir;
$_app_dir = $self->app_dir;
$_core_dir = JS_CORE_DIR;
$_app_name = $self->app_name.'/';

// ��������� ���������� ������
config_set($self, 'tpl_markup_level',  0); // TPL_MODE_CLEAN     (*)
config_set($self, 'tpl_compile',  1); // TPL_COMPILE_SMART  (*)
//config_set($self, 'tpl_root_dir',  $_app_dir); // (+) or "" or "themes/" -- ��� ����� �����
//config_set($self, 'tpl_root_href',  "/");   // (+) or "/themes/"         -- ��� ��������� �� URL �� ���
// lucky@npj -- required in config/config.php
#config_set($self, 'tpl_skin',  "");    // (*) for no-skin-mode which is default
config_set($self, 'tpl_skin_dirs',  array( "css", "js", "images" )); // -- ����� �������� �������

// ��������� ���������� ������
config_set($self, 'tpl_action_prefix',  "rockette_action_");
config_set($self, 'tpl_template_prefix',  "rockette_template_");
config_set($self, 'tpl_template_sepfix',  "__");
config_set($self, 'tpl_action_file_prefix',  "@@"); 
config_set($self, 'tpl_template_file_prefix',  "@");
config_set($self, 'tpl_cache_prefix',  "@");  // � ����� �������� ���������� � ���� ��� ���������� TE
config_set($self, 'tpl_prefix',  "{{");
config_set($self, 'tpl_postfix',  "}}");
config_set($self, 'tpl_instant',  "~");
config_set($self, 'tpl_construct_action',  "!");    // {{!text Test}}
config_set($self, 'tpl_construct_action2',  "!!");   // {{!!text}}Test{{!!/text}}
config_set($self, 'tpl_construct_if',  "?");    // {{?var}} or {{?!var}}
config_set($self, 'tpl_construct_ifelse',  "?:");   // {{?:}} 
config_set($self, 'tpl_construct_ifend',  "?/");   // {{?/}} is similar to {{/?}}
config_set($self, 'tpl_construct_object',  "#.");   // {{#obj.property}}
config_set($self, 'tpl_construct_tplt',  "TPL:"); // {{TPL:Name}}...{{/TPL:Name}}
config_set($self, 'tpl_construct_tplt2',  ":"); // {{:Name}}...{{/:Name}}   -- ru@jetstyle ����� ����� TPL � �����
config_set($self, 'tpl_construct_comment',  "#");    // <!-- # persistent comment -->
// lucky: 
config_set($self, 'tpl_construct_standard_camelCase',  True);    
																  // True, ������ �������, ��� ����� � ��������� CamelCase
																  // �.�. ������ �������� ����� ���
																  // $o->SomeValue(), 
																  // ����� ����� �������, ��� ���������� ruby 
																  // $o->some_value()
																  // (�� ������, ���� ru ������ ���������� �����������)
config_set($self, 'tpl_construct_standard_getter_prefix',  'get');    // lucky: �������� ��� getter'�� 

// lucky+ru: ��������� ������ ������� {{!for do=[[pages]]
//config_set($self, 'tpl_arg_prefix',  "[[");
config_set($self, 'tpl_arg_prefix',  "");
config_set($self, 'tpl_arg_postfix',  "");
//config_set($self, 'tpl_arg_postfix',  "]]");

config_set($self, 'tpl_instant_plugins',  array( "dummy" )); // plugins that are ALWAYS instant

config_set($self, 'shortcuts', array(
	"=>" => array("=", " typografica=1"),
	"=<" => array("=", " strip_tags=1"),
	"+>" => array("+", " typografica=1"),
	"+<" => array("+", " strip_tags=1"),
	"*" => "#*.",
	"@" => "!include @",
	"=" => "!_ tag=",
	"+" => "!message ",
));

// message set defaults
config_set($self, 'msg_default',  "ru"); 

// ������ ���������
config_set($self, 'cache_dir',  $_base_dir.'cache/'.$self->project_name.'/'); // (+) or "../project.zcache/" -- ���� ������ ���

// ��� ��������� ���������: ���������� ��
config_set($self, 'lib_href_part',  "libs"); // ��� ������� �� ��������. ��� �����!
config_set($self, 'lib_dir',  $self->lib_href_part); 

config_set($self, 'magic_word',  "I luv rokket"); // ���������� ����� ��� ��������� ������ ������������������

// ������ ����
config_set($self, 'url_allow_direct_handling',  false); // �������� ���������� URL � ���� �� ��������

// ��������� ���
config_set($self, 'cookie_prefix',  ""); // (+) ������� ���� ���. ��������� ���������� ������ �� ����� ������ ���������� ������������ � �����
config_set($self, 'cookie_expire_days',  60); // ������� ���� ��������� ������������ ����

// ----- fROM config
// ��������� ����������:
// TODO: wtf
config_set($self, 'principal_storage_model',  "db"); 
config_set($self, 'principal_security_models',  array( "tree", "role", "noguests"));

// ��������� ������� ����
//TODO: wtf
config_set($self, 'url_reserved_words',  array( "edit", "add", "delete", "tree", "ajaxupload", "getfile","post" )); // ����� ������ ���� � ��������� ����� (�������)
config_set($self, 'url_site_handlers',  array( "login", "register", "activation","tagpages" )); // ����� ������ ���� � ������ �����

config_set($self, 'url_default_handler',  "show"); // ��� ���������� ������������� �������

// ����������� ���������, ������� �� ��������� ������ �����������
// TODO: wtf

config_set($self, 'tpl_root_href_part',  $_app_name."skins/");
config_set($self, 'tpl_clientside_part',  "petarde/clientside/");   

//  config_set($self, 'tpl_root_dir',  $c->get('base_url')."skins/");  // or "../" or "" -- ��� ����� �����
config_set($self, 'tpl_root_href',  $self->base_url.$_app_name."skins/"); // or "/"         -- ��� ��������� �� URL �� ���

config_set($self, 'tpl_root_dir',  $_base_dir.$self->tpl_root_href_part);

config_set($self, 'admin_email',  "nop@jetstyle.ru");
config_set($self, 'message_set',  "");
config_set($self, 'cookie_prefix',  $self->project_name.'_');

config_set($self, 'timezone',  0); // GMT+5
config_set($self, 'output_encoding',  'windows-1251');


// ----- end fROM config

// ���������� � �������� ���������� ������, relative to which we look for classes
$DIRS[] = $_app_dir;
//for templates FindScripting
$DIRS[] = $_app_dir.'skins/'.$self->tpl_skin."/";
// ���������� � �������� ���������� ������
$DIRS[] = $_core_dir;

config_set($self, 'DIRS', $DIRS);

config_seeConfig($config_loader, $self,
	$self->tpl_root_dir.$self->tpl_skin, 'site_map');

?>
