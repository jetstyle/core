<?php

class Yaml extends Configurable
{

	function initialize(&$ctx, $config=NULL)
	{
		parent::initialize($ctx, $config);
		Finder::useLib('spyc');
	}

	function load($source)
	{
		$this->data = Spyc::YAMLLoad($source);
	}

}

?>
