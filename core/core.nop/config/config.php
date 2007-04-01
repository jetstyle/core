<?php
// Конфиг нулевого уровня
// (*) -- иногда меняется
// (+) -- меняется почти всегда, by-default выставлено в production single-site version
// (!) -- обязательно менять!

// lucky: устанавливает index.php
$_base_dir = $self->project_dir;
$_app_dir = $self->app_dir;
$_core_dir = JS_CORE_DIR;
$_app_name = $self->app_name.'/';

// настройки шаблонного движка
config_set($self, 'tpl_markup_level',  0); // TPL_MODE_CLEAN     (*)
config_set($self, 'tpl_compile',  1); // TPL_COMPILE_SMART  (*)
//config_set($self, 'tpl_root_dir',  $_app_dir); // (+) or "" or "themes/" -- где лежат шкуры
//config_set($self, 'tpl_root_href',  "/");   // (+) or "/themes/"         -- как добраться по URL до них
// lucky@npj -- required in config/config.php
#config_set($self, 'tpl_skin',  "");    // (*) for no-skin-mode which is default
config_set($self, 'tpl_skin_dirs',  array( "css", "js", "images" )); // -- какие каталоги типовые

// стандарты шаблонного движка
config_set($self, 'tpl_action_prefix',  "rockette_action_");
config_set($self, 'tpl_template_prefix',  "rockette_template_");
config_set($self, 'tpl_template_sepfix',  "__");
config_set($self, 'tpl_action_file_prefix',  "@@"); 
config_set($self, 'tpl_template_file_prefix',  "@");
config_set($self, 'tpl_cache_prefix',  "@");  // с этого значения начинаются в кэше все переменные TE
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
config_set($self, 'tpl_construct_tplt2',  ":"); // {{:Name}}...{{/:Name}}   -- ru@jetstyle бесят буквы TPL в капсе
config_set($self, 'tpl_construct_comment',  "#");    // <!-- # persistent comment -->
// lucky: 
config_set($self, 'tpl_construct_standard_camelCase',  True);    
																  // True, значит считаем, что кодим в стандарте CamelCase
																  // т.е. методы объектов имеют вид
																  // $o->SomeValue(), 
																  // иначе будем считать, что обкурились ruby 
																  // $o->some_value()
																  // (на случай, если ru станет заведовать разработкой)
config_set($self, 'tpl_construct_standard_getter_prefix',  'get');    // lucky: префиксы для getter'ов 

// lucky+ru: аргументы внутри шаблона {{!for do=[[pages]]
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

// прочие настройки
config_set($self, 'cache_dir',  $_base_dir.'cache/'.$self->project_name.'/'); // (+) or "../project.zcache/" -- куда сложен кэш

// для сторонних библиотек: размещение их
config_set($self, 'lib_href_part',  "libs"); // как правило не меняется. БЕЗ СЛЭША!
config_set($self, 'lib_dir',  $self->lib_href_part); 

config_set($self, 'magic_word',  "I luv rokket"); // магическое слово для генерации разных псевдослучайностей

// разбор урла
config_set($self, 'url_allow_direct_handling',  false); // напрямую переводить URL в путь до хандлера

// параметры кук
config_set($self, 'cookie_prefix',  ""); // (+) префикс всех кук. Позволяет нескольким сайтам на одном домене независимо существовать в куках
config_set($self, 'cookie_expire_days',  60); // сколько дней держаться перманентные куки

// ----- fROM config
// параметры принципала:
// TODO: wtf
config_set($self, 'principal_storage_model',  "db"); 
config_set($self, 'principal_security_models',  array( "tree", "role", "noguests"));

// параметры разбора урла
//TODO: wtf
config_set($self, 'url_reserved_words',  array( "edit", "add", "delete", "tree", "ajaxupload", "getfile","post" )); // какие методы есть у сущностей сайта (страниц)
config_set($self, 'url_site_handlers',  array( "login", "register", "activation","tagpages" )); // какие методы есть у самого сайта

config_set($self, 'url_default_handler',  "show"); // как называется умолчательный хандлер

// каталоговая структура, которую не захочется менять настройками
// TODO: wtf

config_set($self, 'tpl_root_href_part',  $_app_name."skins/");
config_set($self, 'tpl_clientside_part',  "petarde/clientside/");   

//  config_set($self, 'tpl_root_dir',  $c->get('base_url')."skins/");  // or "../" or "" -- где лежат шкуры
config_set($self, 'tpl_root_href',  $self->base_url.$_app_name."skins/"); // or "/"         -- как добраться по URL до них

config_set($self, 'tpl_root_dir',  $_base_dir.$self->tpl_root_href_part);

config_set($self, 'admin_email',  "nop@jetstyle.ru");
config_set($self, 'message_set',  "");
config_set($self, 'cookie_prefix',  $self->project_name.'_');

config_set($self, 'timezone',  0); // GMT+5
config_set($self, 'output_encoding',  'windows-1251');


// ----- end fROM config

// информация о корневой директории уровня, relative to which we look for classes
$DIRS[] = $_app_dir;
//for templates FindScripting
$DIRS[] = $_app_dir.'skins/'.$self->tpl_skin."/";
// информация о корневой директории уровня
$DIRS[] = $_core_dir;

config_set($self, 'DIRS', $DIRS);

config_seeConfig($config_loader, $self,
	$self->tpl_root_dir.$self->tpl_skin, 'site_map');

?>
