<?php
$self->DIRS[] = dirname(__FILE__).'/';

//template engine
config_set($self, 'templates_cache_dir',  $_dir.'/_templates/');

//misc
config_set($self, 'hide_errors',  false);
config_set($self, 'path_class',  'Path');
config_set($self, 'project_name',  'OSB6');
config_set($self, 'project_title',  'CMS');
config_set($self, 'show_logs',  true);

config_replace($self, 'default_page',  "do");
config_replace($self, 'path_class',  "PathCMS");

config_replace($self, 'toolbar_module_name',  "ToolbarTree");

//�������� ������ ������� ��� �������� � �������
config_replace($self, 'MODES_RUS', array(
	"tree"=>"�������",
	"topics"=>"�������",
	"list"=>"������",
	"form"=>"��������������",
));


// template engine

// ��������� ���������� ������
config_set($self, 'tpl_markup_level',  0); // TPL_MODE_CLEAN     (*)
config_set($self, 'tpl_compile',  1); // TPL_COMPILE_SMART  (*)
//config_set($self, 'tpl_root_dir',  $_app_dir); // (+) or "" or "themes/" -- ��� ����� �����
//config_set($self, 'tpl_root_href',  "/");   // (+) or "/themes/"         -- ��� ��������� �� URL �� ���
config_set($self, 'tpl_skin',  "");    // (*) for no-skin-mode which is default
config_set($self, 'tpl_skin_dirs',  array( "css", "js", "images" )); // -- ����� �������� �������

//config_set($self, 'tpl_root_href_part',  $_app_name."skins/");

//  config_set($self, 'tpl_root_dir',  $c->get('base_url')."skins/");  // or "../" or "" -- ��� ����� �����
//config_set($self, 'tpl_root_href',  $self->base_url.$_app_name."skins/"); // or "/"         -- ��� ��������� �� URL �� ���
//config_set($self, 'tpl_root_dir',  $_base_dir.$self->tpl_root_href_part);

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


?>