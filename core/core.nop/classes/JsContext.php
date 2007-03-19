<?php

// константы для работы со __store
define ('_JS_CONTEXT_METHOD_GET', 1);
define ('_JS_CONTEXT_METHOD_SET', 2);
define ('_JS_CONTEXT_METHOD_FREE', 3);
define ('_JS_CONTEXT_METHOD_IS_SET', 4);

require_once 'JsConfig.php';

class JsContext
{

	/**
	 * Вернуть контроллер приложения
	 *
	 */
	function &getController($cfg)
	{
		$app_name = $cfg->get('app_name');
		$app_dir =	$cfg->get('app_dir');

		$o =& JsContext::__store(_JS_CONTEXT_METHOD_GET, $app_name);

		if (!isset($o))
		{
			if ($cfg->hasKey('app_controller_cls'))
			{
				$cls = $cfg->get('app_controller_cls');
			}
			else
			{
				$cls = ucfirst($app_name) . 'RequestHandler';
				require_once 'RequestHandler.php'; // HACK: lucky
				require_once $app_dir.'/classes/controllers/'.$cls.'.php';
			}
			//site controller, builds site environment
			$o =& new $cls($cfg);
			JsContext::__store(_JS_CONTEXT_METHOD_SET, $app_name, &$o);
		}

		return $o;
	}

	/**
	 * Создать конфиг приложения
	 *
	 * @param array $config (
	 *		'project_dir' : dir				-- путь к инсталяции проекта
	 *		'app_dir'		: dir				-- путь к директории приложения
	 *		'app_name'		: string			-- имя приложения
	 *		'environment	: string			-- имя окружения
	 *		'debug'			: int				-- уровень отладки
	 * )
	 */
	function &buildConfig($config)
	{
		$c =& new JsConfig();
		$c->fromArray($config);
		$c->set_if_free('cache_dir',	$c->get('project_dir').'/cache/'.$c->get('app_name').'/');
		$environment = $c->get('environment');

		require_once 'JsFileCache.php';
		$cache =& new JsFileCache();
		$cache->initialize(array(
			'file_path' => $c->get('cache_dir').$environment.'_config'.'.php',
		));

		if ($cache->isValid())
		{
			// берем из кеша
			$f = $cache->getFileName();
			$data  = include $f;
			$c->fromArray(unserialize($data));
		}
		else
		{
			// строим конфиг

			// путь к конфигам проекта
			$c->set_if_free('project_config_dir',	$c->get('project_dir').'config/');
			// путь к конфигам приложения
			$c->set_if_free('app_config_dir',		$c->get('app_dir').'config/');
			// путь к конфигам ядра
			$c->set_if_free('core_config_dir',		JS_CORE_DIR.'config/');

			// список загружаемых файлов конфигурации по умолчанию
			// (!) порядок записей определяет порядок загрузки конфигов
			$configs = array();
			$vars = array('project_config_dir', 'app_config_dir', 'core_config_dir');

			foreach ($vars as $var)
				$configs[] = array('$c->get("'.$var.'")', 'config');

			JsContext::__store(_JS_CONTEXT_METHOD_SET, 'cache', &$cache);
			JsContext::__store(_JS_CONTEXT_METHOD_SET, 'configs', &$configs);
			// в конфигах доступны две переменные
			// $c -- собственно конфиг
			// $configs -- список еще не загруженных конфигов
			//			по мере необходимости конфиги могут изменять этот список,
			//			добавляя дополнительные конфиги в начало 
			//			array_unshift($configs, array($evaled_path, $cfg_name))
			//			или в конец array_push($configs, array($evaled_path, $cfg_name))
			//			списка.
			while ($script_info = array_shift($configs)) 
			{
				list($source_php, $config_name) = $script_info;
				$dir = eval('return '.$source_php.';');
				JsContext::loadConfig($c, $dir, $config_name);
			}
			$str = "return '".serialize($c->toArray())."';";
			$cache->save($str);
			JsContext::__store(_JS_CONTEXT_METHOD_FREE, 'cache');
		}

		return $c;
	}

	function loadConfig(&$c, $dir, $config_name)
	{
		$environment = $c->get('environment');
		// путь к конфигов
		// возможные имена файлов конфигов (имя, расширение)
		$choises = array(
			array($config_name.'_'.$environment, 'php'),
			array($config_name, 'yml'),
			array($config_name, 'php'),
		);
		foreach ($choises as $choise)
		{
			list($name, $type) = $choise;
			$source = $dir.'/'.$name.'.'.$type;
			if (@is_readable($source))
			{
				$cache =& JsContext::__store(_JS_CONTEXT_METHOD_GET, 'cache');
				$cache->addSource($source);
				$configs =& JsContext::__store(_JS_CONTEXT_METHOD_GET, 'configs');
				switch ($type)
				{
				case 'php':
					include $source;
					break;
				case 'yml':
					if (!class_exists('Spyc'))
					{
						$project_dir = $c->get('project_dir');
						require_once $project_dir.'libs/spyc/spyc.php';
					}
					$data = Spyc::YAMLLoad($source);
					foreach ($data as $k=>$v) // для каждого environment
					{
						if ($k == $environment || $k == 'all')
							foreach ($v as $kk=>$vv) 
								$c->set_if_free($kk, $vv);
					}
					break;
				}
				break; // foreach
			}
		}
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
		case _JS_CONTEXT_METHOD_SET:
			$m[$name] =& $value;
			$res =& $m[$name];
			break;
		case _JS_CONTEXT_METHOD_GET:
			$res =& $m[$name];
			break;
		case _JS_CONTEXT_METHOD_FREE:
			unset($m[$name]);
			$res = NULL;
			break;
		case _JS_CONTEXT_METHOD_IS_SET:
			$res = array_key_exists($name, $m);
			break;
		}
		return $res;
	}

}
