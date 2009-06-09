<?php
class ModuleConfig 
{
	public $moduleName = '';
		
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