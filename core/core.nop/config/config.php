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
JsConfig::set($self, 'tpl_markup_level',  0); // TPL_MODE_CLEAN     (*)
JsConfig::set($self, 'tpl_compile',  1); // TPL_COMPILE_SMART  (*)
//JsConfig::set($self, 'tpl_root_dir',  $_app_dir); // (+) or "" or "themes/" -- где лежат шкуры
//JsConfig::set($self, 'tpl_root_href',  "/");   // (+) or "/themes/"         -- как добраться по URL до них
// lucky@npj -- required in config/config.php
#JsConfig::set($self, 'tpl_skin',  "");    // (*) for no-skin-mode which is default
JsConfig::set($self, 'tpl_skin_dirs',  array( "css", "js", "images" )); // -- какие каталоги типовые

// стандарты шаблонного движка
JsConfig::set($self, 'tpl_action_prefix',  "rockette_action_");
JsConfig::set($self, 'tpl_template_prefix',  "rockette_template_");
JsConfig::set($self, 'tpl_template_sepfix',  "__");
JsConfig::set($self, 'tpl_action_file_prefix',  "@@"); 
JsConfig::set($self, 'tpl_template_file_prefix',  "@");
JsConfig::set($self, 'tpl_cache_prefix',  "@");  // с этого значения начинаются в кэше все переменные TE
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
JsConfig::set($self, 'tpl_construct_tplt2',  ":"); // {{:Name}}...{{/:Name}}   -- ru@jetstyle бесят буквы TPL в капсе
JsConfig::set($self, 'tpl_construct_comment',  "#");    // <!-- # persistent comment -->
// lucky: 
JsConfig::set($self, 'tpl_construct_standard_camelCase',  True);    
																  // True, значит считаем, что кодим в стандарте CamelCase
																  // т.е. методы объектов имеют вид
																  // $o->SomeValue(), 
																  // иначе будем считать, что обкурились ruby 
																  // $o->some_value()
																  // (на случай, если ru станет заведовать разработкой)
JsConfig::set($self, 'tpl_construct_standard_getter_prefix',  'get');    // lucky: префиксы для getter'ов 

// lucky+ru: аргументы внутри шаблона {{!for do=[[pages]]
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

// прочие настройки
JsConfig::set($self, 'cache_dir',  $_base_dir.'cache/'.$self->project_name.'/'); // (+) or "../project.zcache/" -- куда сложен кэш

// для сторонних библиотек: размещение их
JsConfig::set($self, 'lib_href_part',  "libs"); // как правило не меняется. БЕЗ СЛЭША!
JsConfig::set($self, 'lib_dir',  $self->lib_href_part); 

JsConfig::set($self, 'magic_word',  "I luv rokket"); // магическое слово для генерации разных псевдослучайностей

// разбор урла
JsConfig::set($self, 'url_allow_direct_handling',  false); // напрямую переводить URL в путь до хандлера

// параметры кук
JsConfig::set($self, 'cookie_prefix',  ""); // (+) префикс всех кук. Позволяет нескольким сайтам на одном домене независимо существовать в куках
JsConfig::set($self, 'cookie_expire_days',  60); // сколько дней держаться перманентные куки

// ----- fROM config
// параметры принципала:
// TODO: wtf
JsConfig::set($self, 'principal_storage_model',  "db"); 
JsConfig::set($self, 'principal_security_models',  array( "tree", "role", "noguests"));

// параметры разбора урла
//TODO: wtf
JsConfig::set($self, 'url_reserved_words',  array( "edit", "add", "delete", "tree", "ajaxupload", "getfile","post" )); // какие методы есть у сущностей сайта (страниц)
JsConfig::set($self, 'url_site_handlers',  array( "login", "register", "activation","tagpages" )); // какие методы есть у самого сайта

JsConfig::set($self, 'url_default_handler',  "show"); // как называется умолчательный хандлер

// каталоговая структура, которую не захочется менять настройками
// TODO: wtf

JsConfig::set($self, 'tpl_root_href_part',  $_app_name."skins/");
JsConfig::set($self, 'tpl_clientside_part',  "petarde/clientside/");   

//  JsConfig::set($self, 'tpl_root_dir',  $c->get('base_url')."skins/");  // or "../" or "" -- где лежат шкуры
JsConfig::set($self, 'tpl_root_href',  $self->base_url.$_app_name."skins/"); // or "/"         -- как добраться по URL до них

JsConfig::set($self, 'tpl_root_dir',  $_base_dir.$self->tpl_root_href_part);

JsConfig::set($self, 'admin_email',  "nop@jetstyle.ru");
JsConfig::set($self, 'message_set',  "");
JsConfig::set($self, 'cookie_prefix',  $self->project_name.'_');

JsConfig::set($self, 'timezone',  0); // GMT+5
JsConfig::set($self, 'output_encoding',  'windows-1251');


// ----- end fROM config

// информация о корневой директории уровня, relative to which we look for classes
$DIRS[] = $_app_dir;
//for templates FindScripting
$DIRS[] = $_app_dir.'skins/'.$self->tpl_skin."/";
// информация о корневой директории уровня
$DIRS[] = $_core_dir;

JsConfig::set($self, 'DIRS', $DIRS);

JsConfig::seeConfig($js_config_loader, $self,
	$self->tpl_root_dir.$self->tpl_skin, 'site_map');

?>
