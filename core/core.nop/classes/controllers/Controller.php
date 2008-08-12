<?php
/*
 * Parent Controller
 *
 */

class Controller 
{
	var $config = array();
	protected $rh = null;
	
	public function __construct()
	{
		$this->rh = RequestHandler::getInstance();
	}
	
	public function initialize($config=NULL)
	{ 
		if (isset($config)) 
		{
			$this->config = $this->addConfig($config);
		}
		return True;
	}

	public function handle() 
	{
	}

	private function addConfig($config)
	{
		if (is_object($config) && $config instanceof DataContainer)
			$config = $config->getData();
		return array_merge($this->config, $config);
	}
}	
?>
