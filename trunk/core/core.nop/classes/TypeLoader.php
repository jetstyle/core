<?php

class TypeLoader extends Configurable
{

	function initialize(&$ctx, $config=NULL)
	{
		parent::initialize($ctx, $config);

		if (!isset($this->loader)) 
		{
			$this->ctx->useClass('models/Yaml');
			$this->loader =& new Yaml();
			$this->loader->initialize($this->ctx);
		}
		if (!isset($this->namespace)) $this->namespace = 'modules';
		if (!isset($this->schema_name)) $this->schema_name = 'schema';
	}

	function load($name, $level=0, $dr=1, $ext = 'php' )
	{
		$self = NULL;

		$cfg = $this->loadConfig($name, $level, $dr, 'yml');

		$type_name = key($cfg);
		$type_info =& $cfg[$type_name];

		$class_name = $type_info['class'];
		(
			($class_source = $this->rh->findScript('classes', $class_name, $level, $dr, 'php'))
			|| ($class_source = $this->rh->findScript($this->namespace, $name.'/'.$class_name, $level, $dr, 'php'))
		);

		if ($class_source)
		{
			$this->rh->_useScript($class_source);
			$class = end(explode('/', $class_name));
			$self =& new $class();
			config_joinConfigs($self, $type_info);
			$self->class = $class;
		}
		$this->data =& $self;
	}

	function loadConfig($name, $level=0, $dr=1, $type = 'yml' )
	{
		$this->ctx->useClass('models/Yaml');

		$cfg_name = $name.'/'.$this->schema_name;

		$cfg_source = $this->rh->findScript($this->namespace, $cfg_name, $level, $dr, $type);

		$this->loader->load($cfg_source);

		$data = $this->loader->data;

		$type_name = key($data);
		$type_info =& $data[$type_name];

		$extends = $data[$type_name]['extends'];

		if (isset($extends)) foreach ($extends as $v)
		{
			if ($other_data = $this->loadConfig($v))
			{
				$other_type = key($other_data);
				$type_info = array_merge($other_data[$other_type], $type_info);
			}
			else
			{
				$this->ctx->error('Type not Found');
			}
		}

		return $data;

	}

}


?>
