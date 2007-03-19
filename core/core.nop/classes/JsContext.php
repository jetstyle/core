<?php

// ��������� ��� ������ �� __store
define ('_JS_CONTEXT_METHOD_GET', 1);
define ('_JS_CONTEXT_METHOD_SET', 2);
define ('_JS_CONTEXT_METHOD_FREE', 3);
define ('_JS_CONTEXT_METHOD_IS_SET', 4);

require_once 'JsConfig.php';

class JsContext
{

	/**
	 * ������� ���������� ����������
	 *
	 */
	function &getController($cfg)
	{
		$app_name = $cfg->app_name;
		$app_dir =	$cfg->app_dir;

		$o =& JsContext::__store(_JS_CONTEXT_METHOD_GET, $app_name);

		if (!isset($o))
		{
			if (isset($cfg->app_controller_cls))
			{
				$cls = $cfg->app_controller_cls;
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
	 * ������� ������ ����������
	 *
	 * @param array $config (
	 *		'project_dir' : dir				-- ���� � ���������� �������
	 *		'app_dir'		: dir				-- ���� � ���������� ����������
	 *		'app_name'		: string			-- ��� ����������
	 *		'environment	: string			-- ��� ���������
	 *		'debug'			: int				-- ������� �������
	 * )
	 */
	function &buildConfig($config)
	{
		$self =& new StdClass();
		$self->ctx =& $self;


		JsConfig::mergeConfigs($self, $config);
		JsConfig::set($self, 
			'cache_dir', 
							$self->project_dir
							.'cache/'
							.$self->app_name
							.'/');

		$environment = $self->ctx->environment;

		require_once 'JsFileCache.php';
		$cache =& new JsFileCache();
		$cache->initialize(array(
			'file_path' => $self->cache_dir.$environment.'_config'.'.php',
		));

		if ($cache->isValid())
		{
			// ����� �� ����
			$f = $cache->getFileName();
			$data  = include $f;
			//JsConfig::mergeConfigs($self, unserialize($data));
			$self = unserialize($data);
			$self->ctx =& $self;
		}
		else
		{
			// ������ ������

			// ���� � �������� �������
			JsConfig::set($self, 'project_config_dir', $self->project_dir .'config/');
			// ���� � �������� ����������
			JsConfig::set($self, 'app_config_dir', $self->app_dir .'config/');
			// ���� � �������� ����
			JsConfig::set($self, 'core_config_dir', JS_CORE_DIR.'config/');

			// ������ ����������� ������ ������������ �� ���������
			// (!) ������� ������� ���������� ������� �������� ��������
			$configs = array();
			$vars = array(
				'project_config_dir', 
				'app_config_dir', 
				'core_config_dir',
				);

			$loader =& new JsConfigLoader();
			foreach ($vars as $var)
			{
				JsConfig::chainConfig($loader, $self, 
					'"'.$self->$var.'"', 'config');
			}

			foreach ($loader->getSources() as $source)
				$cache->addSource($source);

			unset($self->ctx);
			$str = "return '".serialize($self)."';";
			$cache->save($str);
			$self->ctx =& $self;
		}

		return $self;
	}

	/**
	 * ������ ��������� ������
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
