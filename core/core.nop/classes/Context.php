<?php

// ��������� ��� ������ �� __store
define ('_JS_CONTEXT_METHOD_GET', 1);
define ('_JS_CONTEXT_METHOD_SET', 2);
define ('_JS_CONTEXT_METHOD_FREE', 3);
define ('_JS_CONTEXT_METHOD_IS_SET', 4);

require_once 'Config.php';
require_once 'Configurable.php';
/**
 * ����� Context - �������� ����� �� �����
 */
class Context extends Configurable
{

	/**
	 * �������������
	 *
	 * ����� � ��������� ���� ������� 
	 *
	 * @param array $config (
	 *		'project_dir' : dir				-- ���� � ���������� �������
	 *		'app_dir'		: dir				-- ���� � ���������� ����������
	 *		'app_name'		: string			-- ��� ����������
	 *		'environment	: string			-- ��� ���������
	 *		'debug'			: int				-- ������� �������
	 * )
	 */
	function initialize(&$ctx, $config=NULL)
	{
		$status = parent::initialize($ctx, $config);

		config_set($this, 
			'cache_dir', 
							$this->project_dir
							.'cache/'
							.$this->app_name
							.'/');

		$environment = $this->ctx->environment;

		require_once 'FileCache.php';
		$cache =& new FileCache();
		$cache->initialize(array(
			'file_path' => $this->cache_dir.$environment.'_config'.'.php',
		));

		if ($cache->isValid())
		{
			// ����� �� ����
			$f = $cache->getFileName();
			$data  = include $f;
			//config_mergeConfigs($this, unserialize($data));
			$this = unserialize($data);
			$this->ctx =& $this;
		}
		else
		{
			// ������ ������

			// ���� � �������� �������
			config_set($this, 'project_config_dir', $this->project_dir .'config/');
			// ���� � �������� ����������
			config_set($this, 'app_config_dir', $this->app_dir .'config/');
			// ���� � �������� ����
			config_set($this, 'core_config_dir', JS_CORE_DIR.'config/');

			// ������ ����������� ������ ������������ �� ���������
			// (!) ������� ������� ���������� ������� �������� ��������
			$configs = array();
			$vars = array(
				'project_config_dir', 
				'app_config_dir', 
				'core_config_dir',
				);

			$loader =& new ConfigLoader();
			foreach ($vars as $var)
			{
				config_chainConfig($loader, $this, 
					'"'.$this->$var.'"', 'config');
			}

			foreach ($loader->getSources() as $source)
				$cache->addSource($source);

			unset($this->ctx);
			$str = "return '".serialize($this)."';";
			$cache->save($str);
			$this->ctx =& $this;
		}

		return $status;
	}

	/**
	 * ������� ���������� ����������
	 *
	 */
	function &getController()
	{
		$app_name = $this->app_name;
		$app_dir =	$this->app_dir;

		$o =& Context::__store(_JS_CONTEXT_METHOD_GET, $app_name);

		if (!isset($o))
		{
			if (isset($this->app_controller_cls))
			{
				$cls = $this->app_controller_cls;
			}
			else
			{
				$cls = ucfirst($app_name) . 'RequestHandler';
				require_once 'RequestHandler.php'; // HACK: lucky
				require_once $app_dir.'/classes/controllers/'.$cls.'.php';
			}
			//site controller, builds site environment
			$o =& new $cls($this);
			Context::__store(_JS_CONTEXT_METHOD_SET, $app_name, &$o);
		}

		return $o;
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
