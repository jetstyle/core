<?php
class Block
{
	private $data = null;
	private $tplParams = array();
	protected $config = array();
	
	public function __construct($config = array())
	{
		$this->config = $config;
	}
	
	/**
	 * Вернуть конфиг
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
	
	/**
	 * Params, passed to tpl
	 * @param $params array
	 * @return void
	 */
	public function setTplParams(&$params)
	{
		$this->tplParams = &$params;
	}
	
	public function getTplParam($key)
	{
		return $this->tplParams[$key];
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