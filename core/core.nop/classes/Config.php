<?php

/**
* ����� config - ��������� ���������
*/
function &config_get(&$self, $name, $default=NULL)
{
	$meth = config__buildClassName($self).'_get';
	return $meth($self, $name, $default);
}

function config_set(&$self, $name, $value)
{
	$meth = config__buildClassName($self).'_set';
	return $meth($self, $name, $value);
}

function config_replace(&$self, $name, $value)
{
	$meth = config__buildClassName($self).'_replace';
	return $meth($self, $name, $value);
}

function config_hasKey(&$self, $name)
{
	$meth = config__buildClassName($self).'_hasKey';
	return $meth($self, $name);
}

function &config_keys(&$self)
{
	$meth = config__buildClassName($self).'_keys';
	return $meth($self);
}


function config_merge(&$self, &$other, $name)
{
	if (config_hasKey($other, $name))
	{
		$value = config_get($other, $name);
		config_set($self, $name, $value);
	}
}

function config_join(&$self, &$other, $name)
{
	config_replace($self, $name, config_get($other, $name));
}

function config_mergeConfigs(&$self, &$other)
{
	$names = config_keys($other);
	foreach ($names as $name) config_merge(&$self, &$other, $name);
}

function config_joinConfigs(&$self, &$other)
{
	$names = config_keys($other);
	foreach ($names as $name) config_join(&$self, &$other, $name);
}

function config_chainConfig(&$loader, &$self, $expr, $name)
{
	if (!isset($loader)) $loader =& new ConfigLoader();
	$loader->chainConfig($self, $expr, $name);
}

function config_seeConfig(&$loader, &$self, $folder, $name=NULL)
{
	if (!isset($loader)) $loader =& new ConfigLoader();
	$loader->seeConfig($self, $folder, $name);
}


/**
 * ������� ��� ������ ��� ���������� ���� ������
 */
function config__buildClassName(&$self)
{
	return is_scalar($self) ? $self .'_config': gettype($self).'_config';
}



/**
 * ����� array_config - ������ ��� ������
 */
function &array_config_get(&$self, $name, $default=NULL)
{
	$res = NULL;
	if (array_config_hasKey($self, $name)) $res =& $self[$name];
	else $res =& $default;
	return $res;
}

function array_config_set(&$self, $name, $value)
{
	$res = NULL;
	if (!array_config_hasKey($self, $name))
		$res = array_config_replace($self, $name, $value);
	else
		$res = array_config_get($self, $name);
	return $res;
}

function array_config_replace(&$self, $name, $value)
{
	$self[$name] = $value;
	return $self[$name];
}

function array_config_keys(&$self)
{
	return array_keys($self);
}

function array_config_hasKey(&$self, $name)
{
	return array_key_exists($name, $self);
}


/**
 * ����� object_config - ������ ��� ������
 */

function &object_config_get(&$self, $name, $default=NULL)
{
	if (object_config_hasKey($self, $name)) 
		$res =& $self->$name;
	else $res =& $default;
	return $res;
}

function object_config_set(&$self, $name, $value)
{
	$res = NULL;
	if (!object_config_hasKey($self, $name))
		$res =& object_config_replace($self, $name, &$value);
	else
		$res =& object_config_get($self, $name);
	return $res;
}

function object_config_replace(&$self, $name, $value)
{
	$self->$name =& $value;
	return $self->$name;
}

function object_config_keys(&$self)
{
	return array_keys(get_object_vars($self));
}

function object_config_hasKey(&$self, $name)
{
	return array_key_exists($name, (array)$self);
}




/**
 * ����� ConfigLoader - ��������� �������
 */
class ConfigLoader
{
	var $chain = array();
	var $sources = array();
	var $loading = False;

	function chainConfig(&$self, $expr, $name)
	{
		array_push($this->chain, array(&$self, $expr, $name));
		if (!$this->loading) $this->load();
	}

	function load()
	{
		$this->loading = True;
		while ($config_info = array_shift($this->chain))
		{
			//						 $self, $folder, $name
			$this->seeConfig(
				$config_info[0], 
				eval('return '.str_replace('\\','\\\\',$config_info[1]).';'), 
				$config_info[2]);
		}
		$this->loading;
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
		$config_loader =& $this;
		@include $source;
		return True;
	}

	function loadYaml(&$self, $source)
	{
		$ctx =& config_get($self, 'ctx');
		if (!class_exists('Spyc'))
		{
			$project_dir = $ctx->project_dir;
			require_once $project_dir.'core.nop/libs/spyc/spyc.php';
		}
		$data = Spyc::YAMLLoad($source);

		// ������ ��� ���������
		$env_cfg = config_get($data, $ctx->environment);
		if (isset($env_cfg)) config_mergeConfigs($self, $env_cfg);
		// ������ ��� ����
		$all_cfg = config_get($data, 'all');
		if (isset($all_cfg)) config_mergeConfigs($self, $all_cfg);

		return True;
	}

	/**
	 * ��������� ������ � ��������� ������ �� ��������� ����������
	 *
	 * @param string $folder -- ���������� ������ �������
	 * @param string $config_name -- ��� ����� ������� (��� ����������)
	 */
	function loadConfig(&$self, $source, $type='php')
	{
		$status = False;
		if (@is_readable($source))
		{
			switch ($type)
			{
			case 'php':
				$status = $this->loadPhp($self, $source);
				break;
			case 'yml':
				$status = $this->loadYaml($self, $source);
				break;
			}
			$this->addSource($source);
		}
		return $status;
	}

	/**
	 * ����� ������ � ��������� ������ � ��������� ����������
	 *
	 * @param string $folder -- ���������� ������ �������
	 * @param string $config_name -- ��� ����� ������� (��� ����������)
	 *
	 * @return array($path, $type) -- ������ ����, ��� �������
	 */
	function findConfig(&$self, $folder, $config_name)
	{
		$status = NULL;
		$ctx =& config_get($self, 'ctx');
		$environment = $ctx->environment;
		// ���� � ��������
		// ��������� ����� ������ �������� (���, ����������)
		$choises = array(
			array($config_name.'_'.$environment, 'php'),
			array($config_name,						 'yml'),
			array($config_name,						 'php'),
		);
		foreach ($choises as $choise)
		{
			list($name, $type) = $choise;
			$source = ($folder ? $folder.'/' : ''). $name.'.'.$type;
			if (@is_readable($source))
			{
				$status = array($source, $type);
				break;
			}
		}
		return $status;
	}

	/**
	 * ����� �� ��������� ������ � ��������� ������ �� ��������� ����������
	 *
	 * @param string $folder -- ���������� ������ �������
	 * @param string $config_name -- ��� ����� ������� (��� ����������)
	 */
	function seeConfig(&$self, $folder, $config_name)
	{
		return (
			  ($info = $this->findConfig($self, $folder, $config_name))
			&& $this->loadConfig($self, $info[0], $info[1])
		);
	}

}

?>
