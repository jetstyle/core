<?php

require_once 'Config.php';
/**
 * Класс JsConfigurable - прототип всех объектов
 *
 */
class Configurable
{

	function Configurable()
	{
	}

	function initialize(&$ctx, $config=NULL)
	{
		$this->ctx =& $ctx;
		$this->rh =& $ctx; // FIXME: lucky: удалим это, когда RH перестанет быть контекстом

		// замещает свои атрибуты на указанные явно
		if (isset($config)) config_joinConfigs($this, $config);
		return True;
	}

}

?>
