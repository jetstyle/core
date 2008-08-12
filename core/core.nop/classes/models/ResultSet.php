<?php

Finder::useClass("models/DBModel"); //здесь DataContainer

class ResultSet implements IteratorAggregate, ArrayAccess, Countable, DataContainer
{
	protected $model;
	private $data;

	public function	init($model, $data)
	{
		$this->model = $model;
		$this->data  = $data;
	}

	public function &getData()
	{
		return $this->data;
	}

	public function getArray()
	{
		$result = array();

		if (is_array($this->data))
		{
			foreach ($this->data AS $k => $v)
			{
				if (is_object($v))
				{
					$result[$k] = $v->getArray();
				}
				else
				{
					$result[$k] = $v;
				}
			}
		}

		return $result;
	}

	/*
	 * Author dz
	 * реализация интерфейсов IteratorAggregate, ArrayAccess, Countable
	 *
	 */

	//implements IteratorAggregate
	public function getIterator()
	{
		return new ArrayIterator($this->data);
	}

	//implements ArrayAccess
	public function offsetExists($key) { return isset($this->data[$key]); }

	public function offsetGet($key)
	{
		if ($this->model->isForeignField($key))
		{
			if (!isset($this->data[$key]))
			{
				$this->model->loadForeignField($key, $this->data);
			}

//			$foreignFieldConf = $this->model->getForeignFieldConf($key);
//			if ($foreignFieldConf['type'] == 'has_one')
//			{
//				return $this->data[$key][0];
//			}
//			else
//			{
			return $this->data[$key];
//			}
		}
		elseif (isset($this->data[$key]))
		{
			return $this->data[$key];
		}

		/*
		if (isset($this->data[$key]))
		{
			return $this->data[$key];
		}
		elseif ($this->model->isForeignField($key))
		{
			$this->model->loadForeignField($key, $this->data);
			return $this->data[$key];
		}
		*/
	}

	public function offsetSet($key, $value) { $this->data[$key] = $value; }

	public function offsetUnset($key) { unset($this->data[$key]); }

	//implements Countable
	public function count() { return (!empty($this->data)) ? count($this->data) : 0; }

	public function __toString()
	{
		$res = "<br />object of " . get_class($this) . " values:";
		foreach ($this->data as $k=>$item)
		{
			$res .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;" . $k . " => ";
			if (is_object($item))
				$res .= $item->__toString();
			else
			{
				if (strlen($item) > 255)
					$item = substr(htmlentities($item), 0, 255) . "<font color='green'>&hellip;</font>";
				$res .=  $item;
			}
		}

		return $res;
	}
}

?>