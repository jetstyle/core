<?php

// константы для работы со __store
define ('_JS_SITE_METHOD_GET', 1);
define ('_JS_SITE_METHOD_SET', 2);
define ('_JS_SITE_METHOD_FREE', 3);
define ('_JS_SITE_METHOD_IS_SET', 4);

require_once 'Config.php';
require_once 'Configurable.php';
require_once 'Context.php';

class Site
{

	/**
	 * Создать приложение
	 *
	 *
	 * Найти и загрузить свои конфиги (создать контекст приложения)
	 * Создать приложение (RH)
	 *
	 * @param array $config (
	 *		'project_dir' : dir				-- путь к инсталяции проекта
	 *		'app_dir'		: dir				-- путь к директории приложения
	 *		'app_name'		: string			-- имя приложения
	 *		'environment	: string			-- имя окружения
	 *		'debug'			: int				-- уровень отладки
	 * )
	 *
	 * @static
	 */
	function &buildApplication($config)
	{
		if ($ctx =& Site::buildContext($config))
			$app =& Site::getApplication($ctx);
		return $app;
	}

	/**
	 * Создать контекст приложения.
	 *
	 * @static
	 */
	function &buildContext($config)
	{

		$ctx = NULL;

		/* если директории кеша нет в конфиге, посчитаем */
		if (!isset($config['cache_dir']))
			$config['cache_dir']	= $config['project_dir']
										. 'cache/'
										. $config['app_name']
										. '/';


		$cache_dir = $config['cache_dir'];
		$environment = $config['environment'];

		require_once 'FileCache.php';
		$cache =& new FileCache($cache_dir.$environment.'_config'.'.php');
		
		// если кеш валидный
		if ($cache->isValid())
		{
			// берем из кеша
			$f = $cache->getFileName();
			$data  = include $f;
			$ctx = unserialize($data);
			$ctx->ctx =& $ctx;
		}
		// иначе создадим контекст и наполним его из конфигов
		else
		{
			$ctx =& new Context();
			$ctx->initialize($ctx, $config);

			// строим конфиг

			// путь к конфигам проекта
			config_set($ctx, 'project_config_dir', $ctx->project_dir .'config/');
			// путь к конфигам приложения
			config_set($ctx, 'app_config_dir', $ctx->app_dir .'config/');
			// путь к конфигам ядра
			config_set($ctx, 'core_config_dir', JS_CORE_DIR.'config/');

			// список загружаемых файлов конфигурации по умолчанию
			// (!) порядок записей определяет порядок загрузки конфигов
			$configs = array();
			$vars = array(
				'project_config_dir', 
				'app_config_dir', 
				'core_config_dir',
				);

			$loader =& new ConfigLoader();
			$loader->loading = True;
			foreach ($vars as $var)
			{
				config_chainConfig($loader, $ctx, '"'.$ctx->$var.'"', 'config');
			}
			$loader->loading = False;
			$loader->load();
                
			foreach ($loader->getSources() as $source)
				$cache->addSource($source);
				
			unset($ctx->ctx);
			$str = "return '".str_replace("'", "\\'", serialize($ctx))."';";
			$cache->write($str);
			$ctx->ctx =& $ctx;
		}

		return $ctx;
	}

	/**
	 * Вернуть контроллер приложения (RH)
	 *
	 */
	function &getApplication(&$ctx)
	{
		$app_name = $ctx->app_name;

		$o =& Site::__store(_JS_SITE_METHOD_GET, $app_name);

		if (!isset($o))
		{
			if (isset($ctx->app_controller_cls))
			{
				$cls = $ctx->app_controller_cls;
			}
			else
			{
				$app_dir = $ctx->app_dir;
				$cls = ucfirst($app_name) . 'RequestHandler';
								
				require_once 'RequestHandler.php'; // HACK: lucky
				require_once $app_dir.'/classes/controllers/'.$cls.'.php';
			}
			//site controller, builds site environment
			$o =& new $cls($ctx);
			Site::__store(_JS_SITE_METHOD_SET, $app_name, &$o);
		}

		return $o;
	}

	/**
	 * Хранит состояние класса
	 */
	function &__store($method, $name, $value=NULL)
	{
		static $m;
		if (!isset($m)) $m = array();
		$res = NULL;
		switch ($method)
		{
		case _JS_SITE_METHOD_SET:
			$m[$name] =& $value;
			$res =& $m[$name];
			break;
		case _JS_SITE_METHOD_GET:
			$res =& $m[$name];
			break;
		case _JS_SITE_METHOD_FREE:
			unset($m[$name]);
			$res = NULL;
			break;
		case _JS_SITE_METHOD_IS_SET:
			$res = array_key_exists($name, $m);
			break;
		}
		return $res;
	}
}

?>
