<?php

/**
 * Класс JsConfig - управляет конфигами
 */

class JsConfig_Array
{

	function get(&$self, $name, $default=NULL)
	{
		$res = NULL;
		if (JsConfig_Array::hasKey($self, $name)) $res = $self[$name];
		else $res = $default;
		return $res;
	}

	function set(&$self, $name, $value)
	{
		$res = NULL;
		if (!JsConfig_Array::hasKey($self, $name))
			$res = JsConfig_Array::replace($self, $name, $value);
		else
			$res = JsConfig_Array::get($self, $name);
		return $res;
	}

	function replace(&$self, $name, $value)
	{
		$self[$name] = $value;
		return $self[$name];
	}

	function keys(&$self)
	{
		return array_keys($self);
	}

	function hasKey(&$self, $name)
	{
		return array_key_exists($name, $self);
	}

}


class JsConfig_Object
{

	function get(&$self, $name, $default=NULL)
	{
		$res = NULL;
		if (JsConfig_Object::hasKey($self, $name)) 
			$res = $self->$name;
		else $res = $default;
		return $res;
	}

	function set(&$self, $name, $value)
	{
		$res = NULL;
		if (!JsConfig_Object::hasKey($self, $name))
			$res =& JsConfig_Object::replace($self, $name, $value);
		else
			$res =& JsConfig_Object::get($self, $name);
		return $res;
	}

	function replace(&$self, $name, $value)
	{
		$self->$name =& $value;
		return $self->$name;
	}

	function keys(&$self)
	{
		return array_keys(get_object_vars($self));
	}

	function hasKey(&$self, $name)
	{
		return array_key_exists($name, get_object_vars($self));
	}

}


class JsConfig
{

	function get(&$self, $name, $default=NULL)
	{
		$cls = JsConfig::_buildClassName($self);
		return call_user_func(array($cls, 'get'), &$self, $name, &$default);
	}

	function set(&$self, $name, $value)
	{
		$cls = JsConfig::_buildClassName($self);
		call_user_func(array($cls, 'set'), &$self, $name, &$value);
	}

	function replace(&$self, $name, $value)
	{
		$cls = JsConfig::_buildClassName($self);
		return call_user_func(array($cls, 'replace'), &$self, $name, &$value);
	}

	function hasKey(&$self, $name)
	{
		$cls = JsConfig::_buildClassName($self);
		return call_user_func(array($cls, 'hasKey'), &$self, $name);
	}

	function keys(&$self)
	{
		$cls = JsConfig::_buildClassName($self);
		return call_user_func(array($cls, 'keys'), &$self);
	}


	function _buildClassName(&$self)
	{
		$type = gettype($self);
		return 'JsConfig_'.ucfirst($type);
	}

	function merge(&$self, &$other, $name)
	{
		if (JsConfig::hasKey($other, $name))
		{
			$value = JsConfig::get($other, $name);
			JsConfig::set($self, $name, $value);
		}
	}

	function join(&$self, &$other, $name)
	{
		$value = JsConfig::get($other, $name);
		JsConfig::replace($self, $name, $value);
	}

	function mergeConfigs(&$self, &$other)
	{
		$names = JsConfig::keys($other);
		foreach ($names as $name) JsConfig::merge(&$self, &$other, $name);
	}

	function joinConfigs(&$self, &$other)
	{
		$names = JsConfig::keys($other);
		foreach ($names as $name) JsConfig::join(&$self, &$other, $name);
	}

	function chainConfig(&$loader, &$self, $expr, $name)
	{
		if (!isset($loader)) $loader =& new JsConfigLoader();
		$loader->chainConfig($self, $expr, $name);
	}

	function seeConfig(&$loader, &$self, $folder, $name)
	{
		if (!isset($loader)) 
		{
			$loader =& new JsConfigLoader();
		}
		$loader->loadConfig($self, $folder, $name);
	}

}


class JsConfigLoader
{
	var $chain = array();
	var $sources = array();

	function chainConfig(&$self, $folder, $name)
	{
		array_push($this->chain, array(&$self, $folder, $name));
		if (!isset($this->loading)) $this->load();
	}

	function load()
	{
		while ($config_info = array_shift($this->chain))
		{
			//						 $self, $folder, $name
			$this->loadConfig(
				$config_info[0], 
				eval('return '.str_replace('\\','\\\\',$config_info[1]).';'), 
				$config_info[2]);
		}
	}

	function addSource($source)
	{
		$this->sources[] = $source;
	}

	function getSources()
	{
		return $this->sources;
	}

	function loadPhp(&$self, $source)
	{
		$js_config_loader =& $this;
		@include $source;
	}

	function loadYaml(&$self, $source)
	{
		if (!class_exists('Spyc'))
		{
			$project_dir = $self->ctx->project_dir;
			require_once $project_dir.'libs/spyc/spyc.php';
		}
		$data = Spyc::YAMLLoad($source);
		foreach ($data as $k=>$v) // для каждого environment
			if ($k == $self->ctx->environment || $k == 'all')
				JsConfig::mergeConfigs($self, $v);
	}

	function loadConfig(&$self, $folder, $config_name)
	{
		$environment = $self->ctx->environment;
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
			$source = $folder.'/'.$name.'.'.$type;
			if (@is_readable($source))
			{
				$this->addSource($source);
				switch ($type)
				{
				case 'php':
					$this->loadPhp($self, $source);
					break;
				case 'yml':
					$this->loadYaml($self, $source);
					break;
				}
				break; // foreach
			}
		}
	}

}

?>
