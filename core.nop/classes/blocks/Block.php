<?php
class Block
{
	private $data = null;
	protected $config = array();
	
	public function __construct($config = array())
	{
		$this->config = $config;
	}
	
	/**
	 * ������� ������
	 */
	public function getConfig()
	{
		return $this->config;
	}

	public function &getData()
	{
		if (null === $this->data)
		{
			$this->constructData();
		}
		return $this->data;
	}
	
	protected function setData($data)
	{
		$this->data = $data;
	}
	
	protected function constructData()
	{
		
	}
}
?>