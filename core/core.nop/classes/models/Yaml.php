<?php

class Yaml extends Configurable
{

	function initialize(&$ctx, $config=NULL)
	{
		parent::initialize($ctx, $config);
		$this->ctx->useLib('spyc');
	}

	function load($source)
	{
		$this->data = Spyc::YAMLLoad($source);
	}

}

?>
