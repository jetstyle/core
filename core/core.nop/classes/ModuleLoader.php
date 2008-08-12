<?php

Finder::useClass('TypeLoader');

class ModuleLoader extends TypeLoader
{

	function initialize(&$ctx, $config=NULL)
	{
		parent::initialize($ctx, $config);

		if (!isset($this->config_name)) $this->config_name = 'config';
	}
}
?>