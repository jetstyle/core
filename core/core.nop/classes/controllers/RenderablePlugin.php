<?php

$this->useClass('controllers/Plugin');
/**
 * ����� RenderablePlugin - ������ � ������
 */
class RenderablePlugin extends Plugin
{
	var $config_vars = array(
		// ��������� ����������, ���� ��������� ���������
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
