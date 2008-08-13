<?php
class ModuleConfig 
{
	public $rh;
	public $moduleName = '';
	
	public function __construct()
	{
		$this->rh = RequestHandler::getInstance();
	}
	
	public function read($configFile)
	{
		include($configFile);
	}
	
	public function get($name)
	{
		return $this->$name;
	}
	
	public function getModuleName()
	{
		return $this->moduleName;
	}
}
?>