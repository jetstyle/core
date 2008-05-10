<?php
class ModuleConfig 
{
	public $rh;
	public $moduleName = '';
	
	public function __construct(&$rh)
	{
		$this->rh = &$rh;
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