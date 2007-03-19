<?php

/**
 * Класс JsConfig - управляет конфигами
 */

class JsConfig_Array
{

	function keys(&$self)
	{
		return array_keys($self);
	}

	function replace(&$self, $name, $value)
	{
		$self[$name] = $value;
		return $self[$name];
	}

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

	function hasKey(&$self, $name)
	{
		return array_key_exists($name, $self);
	}

}


class JsConfig_Object
{

	function replace(&$self, $name, $value)
	{
		$self->$name =& $value;
		return $self->$name;
	}

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

	function _buildClassName(&$self)
	{
		$type = gettype($self);
		return 'JsConfig_'.ucfirst($type);
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

	function get(&$self, $name, $default=NULL)
	{
		$cls = JsConfig::_buildClassName($self);
		return call_user_func(array($cls, 'get'), &$self, $name, &$default);
	}

	function merge(&$self, &$other)
	{
		$cls = JsConfig::_buildClassName($self);
		$other_cls = JsConfig::_buildClassName($other);

		$keys = call_user_func(array($other_cls, 'keys'), $other);
		foreach ($keys as $k)
		{
			$value = JsConfig::get($other, $k);
			JsConfig::set($self, $k, $value);
		}


	}

	function chainConfig(&$loader, &$self, $expr, $name)
	{
		if (!isset($loader)) $loader =& new JsConfigLoader();
		$folder = eval('return '.$expr.';');
		$loader->chainConfig($self, $folder, $name);
	}

	function seeConfig(&$loader, &$self, $expr, $name)
	{
		if (!isset($loader)) 
		{
			$loader =& new JsConfigLoader();
		}
		$folder = eval('return '.$expr.';');
		$loader->loadConfig($self, $folder, $name);
	}

}


class JsConfigLoader
{
	var $chain = array();
	var $sources = array();

	function chainConfig(&$self, $folder, $name)
	{
		array_push($this->chain, array($self, $folder, $name));
		if (!isset($this->loading)) $this->load();
	}

	function load()
	{
		while ($config_info = array_shift($this->chain))
		{
			//						 $self, $folder, $name
			var_dump($config_info);
			$this->loadConfig(
				$config_info[0], 
				eval('return '.$config_info[1].';'), 
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
				JsConfig::merge($self, $v);
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
