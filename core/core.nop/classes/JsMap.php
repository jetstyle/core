<?php

/**
 * Класс JsMap - хеш
 */
class JsMap
{
	var $_data = array();

	function JsMap() {}

	function get($name)
	{
		$res = NULL;
		if ($this->hasKey($name))
			$res =& $this->_data[$name];
		return $res;
	}

	function set($name, $value)
	{
		$this->_data[$name] =& $value;
		$res =& $value;
		return $res;
	}

	function hasKey($name)
	{
		return array_key_exists($name, $this->_data);
	}

	function toArray()
	{
		return $this->_data;
	}

	function fromArray($data)
	{
		$this->_data =& $data;
	}

	function &getIterator()
	{
		trigger_error('JsMap::getIterator not implemented', E_USER_ERROR);
	}

}

?>
