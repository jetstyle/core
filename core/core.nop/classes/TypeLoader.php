<?php

class TypeLoader extends Configurable
{

	function initialize(&$ctx, $config=NULL)
	{
		parent::initialize($ctx, $config);
		if (!isset($this->namespace)) $this->namespace = 'plugins';
	}

	function load($name, $level=0, $dr=1, $ext = 'php' )
	{
		$self = NULL;

		$classSource = $this->rh->useClass($this->namespace.'/'.$name.'/'.$name);
			
		$self =& new $name();
		$self->class = $name;
		$this->data =& $self;
	}
}
?>