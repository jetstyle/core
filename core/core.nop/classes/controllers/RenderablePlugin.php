<?php

$this->useClass('controllers/Plugin');
/**
 * Класс RenderablePlugin - плагин с мордой
 */
class RenderablePlugin extends Plugin
{
	var $config_vars = array(
		// шаблонная переменная, куда сохранять результат
		'store_to',
	);

	function initialize(&$ctx, $config) 
	{
		$parent_status = parent::initialize($ctx, $config);
		$this->factory->registerObserver('on_rend', array(&$this, 'rend'));
		return $parent_status;
	}

	function rend(&$ctx)
	{
	}

}
?>
