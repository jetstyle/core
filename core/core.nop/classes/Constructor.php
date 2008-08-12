<?php


class Constructor extends Configurable
{

	function &instance()
	{
	}

}


class ModuleConstructor extends Constructor
{

	function initialize(&$ctx, $config=NULL)
	{
		$status = parent::initialize($ctx, $config);
		config_set($this, 'type', 'modules');
		return $status;
	}

	/**
	 * Найти и загрузить конфиг модуля (т.е. самого себя)
	 * Создать объект
	 *  Инициализировать объект конфигом.
	 *  Вернуть объект
	 */
	function &instance()
	{
		$cfg = Finder::findScript($this->type, $this->name);
		if (empty($cfg)) return NULL;
		$status = config_seeConfig($loader, $self, $cfg, $this->name);
	}

}

?>
