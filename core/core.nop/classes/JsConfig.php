<?php

/**
 * Класс JsConfig - управляет конфигами
 */

require_once 'JsMap.php';

class JsConfig extends JsMap
{

	function JsConfig() { }

	function set_if_free($name, $value)
	{
		$res = NULL;
		if (!$this->hasKey($name))
		{
			$this->set($name, &$value);
			$res =& $value;
		}
		else
		{
			$res =& $this->get($name);
		}
		return $res;
	}

	function get_or_default($name, $default)
	{
		$res = NULL;
		if (!$this->hasKey($name)) $res =& $this->get($name);
		else $res =& $default;
		return $res;
	}

	/*
	function &clone()
	{
		$o =& new JsConfig();
		$o->fromArray($this->toArray());
		return $o;
	}
	 */

}

?>
