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
JsConfig::set($self, 'tpl_markup_level',  0); // TPL_MODE_CLEAN     (*)
JsConfig::set($self, 'tpl_compile',  1); // TPL_COMPILE_SMART  (*)
//JsConfig::set($self, 'tpl_root_dir',  $_app_dir); // (+) or "" or "themes/" -- ��� ����� �����
//JsConfig::set($self, 'tpl_root_href',  "/");   // (+) or "/themes/"         -- ��� ��������� �� URL �� ���
// lucky@npj -- required in config/config.php
#JsConfig::set($self, 'tpl_skin',  "");    // (*) for no-skin-mode which is default
JsConfig::set($self, 'tpl_skin_dirs',  array( "css", "js", "images" )); // -- ����� �������� �������

// ��������� ���������� ������
JsConfig::set($self, 'tpl_action_prefix',  "rockette_action_");
JsConfig::set($self, 'tpl_template_prefix',  "rockette_template_");
JsConfig::set($self, 'tpl_template_sepfix',  "__");
JsConfig::set($self, 'tpl_action_file_prefix',  "@@"); 
JsConfig::set($self, 'tpl_template_file_prefix',  "@");
JsConfig::set($self, 'tpl_cache_prefix',  "@");  // � ����� �������� ���������� � ���� ��� ���������� TE
JsConfig::set($self, 'tpl_prefix',  "{{");
JsConfig::set($self, 'tpl_postfix',  "}}");
JsConfig::set($self, 'tpl_instant',  "~");
JsConfig::set($self, 'tpl_construct_action',  "!");    // {{!text Test}}
JsConfig::set($self, 'tpl_construct_action2',  "!!");   // {{!!text}}Test{{!!/text}}
JsConfig::set($self, 'tpl_construct_if',  "?");    // {{?var}} or {{?!var}}
JsConfig::set($self, 'tpl_construct_ifelse',  "?:");   // {{?:}} 
JsConfig::set($self, 'tpl_construct_ifend',  "?/");   // {{?/}} is similar to {{/?}}
JsConfig::set($self, 'tpl_construct_object',  "#.");   // {{#obj.property}}
JsConfig::set($self, 'tpl_construct_tplt',  "TPL:"); // {{TPL:Name}}...{{/TPL:Name}}
JsConfig::set($self, 'tpl_construct_tplt2',  ":"); // {{:Name}}...{{/:Name}}   -- ru@jetstyle ����� ����� TPL � �����
JsConfig::set($self, 'tpl_construct_comment',  "#");    // <!-- # persistent comment -->
// lucky: 
JsConfig::set($self, 'tpl_construct_standard_camelCase',  True);    
																  // True, ������ �������, ��� ����� � ��������� CamelCase
																  // �.�. ������ �������� ����� ���
																  // $o->SomeValue(), 
																  // ����� ����� �������, ��� ���������� ruby 
																  // $o->some_value()
																  // (�� ������, ���� ru ������ ���������� �����������)
JsConfig::set($self, 'tpl_construct_standard_getter_prefix',  'get');    // lucky: �������� ��� getter'�� 

// lucky+ru: ��������� ������ ������� {{!for do=[[pages]]
JsConfig::set($self, 'tpl_arg_prefix',  "[[");
JsConfig::set($self, 'tpl_arg_postfix',  "]]");

JsConfig::set($self, 'tpl_instant_plugins',  array( "dummy" )); // plugins that are ALWAYS instant

JsConfig::set($self, 'shortcuts', array(
	"=>" => array("=", " typografica=1"),
	"=<" => array("=", " strip_tags=1"),
	"+>" => array("+", " typografica=1"),
	"+<" => array("+", " strip_tags=1"),
	"*" => "#*.",
	"@" => "!include @",
	"=" => "!text ",
	"+" => "!message ",
));

// message set defaults
JsConfig::set($self, 'msg_default',  "ru"); 

// ������ ���������
JsConfig::set($self, 'cache_dir',  $_base_dir.'cache/'.$self->project_name.'/'); // (+) or "../project.zcache/" -- ���� ������ ���

// ��� ��������� ���������: ���������� ��
JsConfig::set($self, 'lib_href_part',  "libs"); // ��� ������� �� ��������. ��� �����!
JsConfig::set($self, 'lib_dir',  $self->lib_href_part); 

JsConfig::set($self, 'magic_word',  "I luv rokket"); // ���������� ����� ��� ��������� ������ ������������������

// ������ ����
JsConfig::set($self, 'url_allow_direct_handling',  false); // �������� ���������� URL � ���� �� ��������

// ��������� ���
JsConfig::set($self, 'cookie_prefix',  ""); // (+) ������� ���� ���. ��������� ���������� ������ �� ����� ������ ���������� ������������ � �����
JsConfig::set($self, 'cookie_expire_days',  60); // ������� ���� ��������� ������������ ����

// ----- fROM config
// ��������� ����������:
// TODO: wtf
JsConfig::set($self, 'principal_storage_model',  "db"); 
JsConfig::set($self, 'principal_security_models',  array( "tree", "role", "noguests"));

// ��������� ������� ����
//TODO: wtf
JsConfig::set($self, 'url_reserved_words',  array( "edit", "add", "delete", "tree", "ajaxupload", "getfile","post" )); // ����� ������ ���� � ��������� ����� (�������)
JsConfig::set($self, 'url_site_handlers',  array( "login", "register", "activation","tagpages" )); // ����� ������ ���� � ������ �����

JsConfig::set($self, 'url_default_handler',  "show"); // ��� ���������� ������������� �������

// ����������� ���������, ������� �� ��������� ������ �����������
// TODO: wtf

JsConfig::set($self, 'tpl_root_href_part',  $_app_name."skins/");
JsConfig::set($self, 'tpl_clientside_part',  "petarde/clientside/");   

//  JsConfig::set($self, 'tpl_root_dir',  $c->get('base_url')."skins/");  // or "../" or "" -- ��� ����� �����
JsConfig::set($self, 'tpl_root_href',  $self->base_url.$_app_name."skins/"); // or "/"         -- ��� ��������� �� URL �� ���

JsConfig::set($self, 'tpl_root_dir',  $_base_dir.$self->tpl_root_href_part);

JsConfig::set($self, 'admin_email',  "nop@jetstyle.ru");
JsConfig::set($self, 'message_set',  "");
JsConfig::set($self, 'cookie_prefix',  $self->project_name.'_');

JsConfig::set($self, 'timezone',  0); // GMT+5
JsConfig::set($self, 'output_encoding',  'windows-1251');


// ----- end fROM config

// ���������� � �������� ���������� ������, relative to which we look for classes
$DIRS[] = $_app_dir;
//for templates FindScripting
$DIRS[] = $_app_dir.'skins/'.$self->tpl_skin."/";
// ���������� � �������� ���������� ������
$DIRS[] = $_core_dir;

JsConfig::set($self, 'DIRS', $DIRS);

JsConfig::seeConfig($js_config_loader, $self,
	$self->tpl_root_dir.$self->tpl_skin, 'site_map');

?>
