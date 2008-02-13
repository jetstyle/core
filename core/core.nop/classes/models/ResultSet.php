<?php

class ResultSet implements IteratorAggregate, ArrayAccess, Countable, DataContainer
{
	private $model;
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
		if (isset($this->data[$key]))
			return $this->data[$key]; 
		elseif ($this->model->isForeignField($key))
		{
			$this->model->loadForeignField($key, $this->data);
			return $this->data[$key];
		}
	}

	public function offsetSet($key, $value) { $this->data[$key] = $value; }

	public function offsetUnset($key) { unset($this->data[$key]); }

	//implements Countable
	public function count() { return (!empty($this->data)) ? count($this->data) : 0; }
}

?>