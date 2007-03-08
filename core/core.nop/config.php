<?php
// Конфиг нулевого уровня
// (*) -- иногда меняется
// (+) -- меняется почти всегда, by-default выставлено в production single-site version
// (!) -- обязательно менять!

$_basedir = realpath(dirname(__FILE__).'../../../').'/';

include( $_basedir."config/config.php" ); // подцепляем конфиг
include( $_basedir."config/config_db.php" ); // подцепляем конфиг БД
include( $_basedir."config/config_handlers.php" );

// настройки шаблонного движка
$this->tpl_markup_level  = 0; // TPL_MODE_CLEAN     (*)
$this->tpl_compile       = 1; // TPL_COMPILE_SMART  (*)
$this->tpl_root_dir      = "../"; // (+) or "" or "themes/" -- где лежат шкуры
$this->tpl_root_href     = "/";   // (+) or "/themes/"         -- как добраться по URL до них
// lucky@npj -- required in config/config.php
#$this->tpl_skin          = "";    // (*) for no-skin-mode which is default
$this->tpl_skin_dirs     = array( "css", "js", "images" ); // -- какие каталоги типовые

// стандарты шаблонного движка
$this->tpl_action_prefix      = "rockette_action_";
$this->tpl_template_prefix    = "rockette_template_";
$this->tpl_template_sepfix    = "__";
$this->tpl_action_file_prefix   = "@@"; 
$this->tpl_template_file_prefix = "@";
$this->tpl_cache_prefix = "@";  // с этого значения начинаются в кэше все переменные TE
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
$this->tpl_construct_tplt2    = ":"; // {{:Name}}...{{/:Name}}   -- ru@jetstyle бесят буквы TPL в капсе
$this->tpl_construct_comment  = "#";    // <!-- # persistent comment -->
// lucky: 
$this->tpl_construct_standard_camelCase  = True;    
																  // True, значит считаем, что кодим в стандарте CamelCase
																  // т.е. методы объектов имеют вид
																  // $o->SomeValue(), 
																  // иначе будем считать, что обкурились ruby 
																  // $o->some_value()
																  // (на случай, если ru станет заведовать разработкой)
$this->tpl_construct_standard_getter_prefix  = 'get';    // lucky: префиксы для getter'ов 

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

// прочие настройки
$this->cache_dir              = $_basedir."cache/web/"; // (+) or "../project.zcache/" -- куда сложен кэш

// для сторонних библиотек: размещение их
$this->lib_href_part          = "libs"; // как правило не меняется. БЕЗ СЛЭША!
$this->lib_dir                = $this->lib_href_part; 

$this->magic_word             = "I luv rokket"; // магическое слово для генерации разных псевдослучайностей

// разбор урла
$this->url_allow_direct_handling = false; // напрямую переводить URL в путь до хандлера

// параметры кук
$this->cookie_prefix      = ""; // (+) префикс всех кук. Позволяет нескольким сайтам на одном домене независимо существовать в куках
$this->cookie_expire_days = 60; // сколько дней держаться перманентные куки

// ----- fROM config
// информация о корневой директории уровня, relative to which we look for classes
$this->DIRS[] = $_basedir.'web/';
//for templates FindScripting
$this->DIRS[] = $_basedir.'web/skins/'.$this->tpl_skin."/";

// параметры принципала:
// TODO: wtf
$this->principal_storage_model = "db"; 
$this->principal_security_models = array( "tree", "role", "noguests");

// параметры разбора урла
//TODO: wtf
$this->url_reserved_words    = array( "edit", "add", "delete", "tree", "ajaxupload", "getfile","post" ); // какие методы есть у сущностей сайта (страниц)
$this->url_site_handlers     = array( "login", "register", "activation","tagpages" ); // какие методы есть у самого сайта

$this->url_default_handler   = "show"; // как называется умолчательный хандлер

// каталоговая структура, которую не захочется менять настройками
// TODO: wtf

$this->tpl_root_href_part  = "web/skins/";
$this->tpl_clientside_part = "petarde/clientside/";   

//  $this->tpl_root_dir      = $this->base_url."skins/";  // or "../" or "" -- где лежат шкуры
$this->tpl_root_href     = $this->base_url."web/skins/"; // or "/"         -- как добраться по URL до них

$this->tpl_root_dir  = $_basedir.$this->tpl_root_href_part;

$this->admin_email = "nop@jetstyle.ru";
$this->message_set = "";
$this->cookie_prefix = $this->project_name.'_';

$this->timezone = 0; // GMT+5


include( $this->tpl_root_dir.$this->tpl_skin."/site_map.php" );

// ----- end fROM config

// информация о корневой директории уровня
$this->DIRS[] = dirname(__FILE__).'/';
?>
