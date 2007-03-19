<?php
// ������ �������� ������
// (*) -- ������ ��������
// (+) -- �������� ����� ������, by-default ���������� � production single-site version
// (!) -- ����������� ������!

// lucky: ������������� index.php
$_base_dir = $c->get('project_dir').'/';
$_app_dir = $c->get('app_dir').'/';
$_core_dir = JS_CORE_DIR;
$_app_name = $c->get('app_name').'/';

// ��������� ���������� ������
$c->set_if_free('tpl_markup_level',  0); // TPL_MODE_CLEAN     (*)
$c->set_if_free('tpl_compile',  1); // TPL_COMPILE_SMART  (*)
//$c->set_if_free('tpl_root_dir',  $_app_dir); // (+) or "" or "themes/" -- ��� ����� �����
//$c->set_if_free('tpl_root_href',  "/");   // (+) or "/themes/"         -- ��� ��������� �� URL �� ���
// lucky@npj -- required in config/config.php
#$c->set_if_free('tpl_skin',  "");    // (*) for no-skin-mode which is default
$c->set_if_free('tpl_skin_dirs',  array( "css", "js", "images" )); // -- ����� �������� �������

// ��������� ���������� ������
$c->set_if_free('tpl_action_prefix',  "rockette_action_");
$c->set_if_free('tpl_template_prefix',  "rockette_template_");
$c->set_if_free('tpl_template_sepfix',  "__");
$c->set_if_free('tpl_action_file_prefix',  "@@"); 
$c->set_if_free('tpl_template_file_prefix',  "@");
$c->set_if_free('tpl_cache_prefix',  "@");  // � ����� �������� ���������� � ���� ��� ���������� TE
$c->set_if_free('tpl_prefix',  "{{");
$c->set_if_free('tpl_postfix',  "}}");
$c->set_if_free('tpl_instant',  "~");
$c->set_if_free('tpl_construct_action',  "!");    // {{!text Test}}
$c->set_if_free('tpl_construct_action2',  "!!");   // {{!!text}}Test{{!!/text}}
$c->set_if_free('tpl_construct_if',  "?");    // {{?var}} or {{?!var}}
$c->set_if_free('tpl_construct_ifelse',  "?:");   // {{?:}} 
$c->set_if_free('tpl_construct_ifend',  "?/");   // {{?/}} is similar to {{/?}}
$c->set_if_free('tpl_construct_object',  "#.");   // {{#obj.property}}
$c->set_if_free('tpl_construct_tplt',  "TPL:"); // {{TPL:Name}}...{{/TPL:Name}}
$c->set_if_free('tpl_construct_tplt2',  ":"); // {{:Name}}...{{/:Name}}   -- ru@jetstyle ����� ����� TPL � �����
$c->set_if_free('tpl_construct_comment',  "#");    // <!-- # persistent comment -->
// lucky: 
$c->set_if_free('tpl_construct_standard_camelCase',  True);    
																  // True, ������ �������, ��� ����� � ��������� CamelCase
																  // �.�. ������ �������� ����� ���
																  // $o->SomeValue(), 
																  // ����� ����� �������, ��� ���������� ruby 
																  // $o->some_value()
																  // (�� ������, ���� ru ������ ���������� �����������)
$c->set_if_free('tpl_construct_standard_getter_prefix',  'get');    // lucky: �������� ��� getter'�� 

$c->set_if_free('tpl_instant_plugins',  array( "dummy" )); // plugins that are ALWAYS instant

$c->set_if_free('shortcuts', array(
	"=>" => array("=", " typografica=1"),
	"=<" => array("=", " strip_tags=1"),
	"+>" => array("+", " typografica=1"),
	"+<" => array("+", " strip_tags=1"),
	"*" => "#*.",
	"@" => "!include ",
	"=" => "!text ",
	"+" => "!message ",
));

// message set defaults
$c->set_if_free('msg_default',  "ru"); 

// ������ ���������
$c->set_if_free('cache_dir',  $_base_dir.'cache/'.$c->get('project_name').'/'); // (+) or "../project.zcache/" -- ���� ������ ���

// ��� ��������� ���������: ���������� ��
$c->set_if_free('lib_href_part',  "libs"); // ��� ������� �� ��������. ��� �����!
$c->set_if_free('lib_dir',  $c->get('lib_href_part')); 

$c->set_if_free('magic_word',  "I luv rokket"); // ���������� ����� ��� ��������� ������ ������������������

// ������ ����
$c->set_if_free('url_allow_direct_handling',  false); // �������� ���������� URL � ���� �� ��������

// ��������� ���
$c->set_if_free('cookie_prefix',  ""); // (+) ������� ���� ���. ��������� ���������� ������ �� ����� ������ ���������� ������������ � �����
$c->set_if_free('cookie_expire_days',  60); // ������� ���� ��������� ������������ ����

// ----- fROM config
// ��������� ����������:
// TODO: wtf
$c->set_if_free('principal_storage_model',  "db"); 
$c->set_if_free('principal_security_models',  array( "tree", "role", "noguests"));

// ��������� ������� ����
//TODO: wtf
$c->set_if_free('url_reserved_words',  array( "edit", "add", "delete", "tree", "ajaxupload", "getfile","post" )); // ����� ������ ���� � ��������� ����� (�������)
$c->set_if_free('url_site_handlers',  array( "login", "register", "activation","tagpages" )); // ����� ������ ���� � ������ �����

$c->set_if_free('url_default_handler',  "show"); // ��� ���������� ������������� �������

// ����������� ���������, ������� �� ��������� ������ �����������
// TODO: wtf

$c->set_if_free('tpl_root_href_part',  $_app_name."skins/");
$c->set_if_free('tpl_clientside_part',  "petarde/clientside/");   

//  $c->set_if_free('tpl_root_dir',  $c->get('base_url')."skins/");  // or "../" or "" -- ��� ����� �����
$c->set_if_free('tpl_root_href',  $c->get('base_url').$_app_name."skins/"); // or "/"         -- ��� ��������� �� URL �� ���

$c->set_if_free('tpl_root_dir',  $_base_dir.$c->get('tpl_root_href_part'));

$c->set_if_free('admin_email',  "nop@jetstyle.ru");
$c->set_if_free('message_set',  "");
$c->set_if_free('cookie_prefix',  $c->get('project_name').'_');

$c->set_if_free('timezone',  0); // GMT+5
$c->set_if_free('output_encoding',  'windows-1251');


// ----- end fROM config

// ���������� � �������� ���������� ������, relative to which we look for classes
$DIRS[] = $_app_dir;
//for templates FindScripting
$DIRS[] = $_app_dir.'skins/'.$c->get('tpl_skin')."/";
// ���������� � �������� ���������� ������
$DIRS[] = $_core_dir;

$c->set_if_free('DIRS', $DIRS);

array_push($configs,
	array('$c->get("tpl_root_dir").$c->get("tpl_skin")', 'site_map')
);

?>
