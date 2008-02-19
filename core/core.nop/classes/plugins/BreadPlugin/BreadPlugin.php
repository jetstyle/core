<?php

$this->useClass('controllers/Plugin');

class BreadPlugin extends RenderablePlugin
{
	var $config_vars = array('store_to');

	function initialize(&$ctx, $config)
	{
		if ($this->initialized) return;

		parent::initialize(&$ctx, $config);

		/*
		 * загрузим модель меню
		 * с условием на where
		 */
		$this->rh->UseClass("models/Bread");
		$model =& new Bread();
		$model->initialize($this->rh);
		$model->load();

		$this->models['bread'] =& $model;
	}

	function addItem($path, $title, $hide = 0)
	{
		$this->models['bread']->addItem(array('href'=>$path, 'title_short'=>$title, 'hide' => $hide));
	}

	function url_to(&$d)
	{
		$path = $d['href'];
		return $this->rh->base_url . $path;
	}

	function rend(&$ctx)
	{
		$this->models['bread']->data[count($this->models['bread']->data) - 1]['last'] = true;
		$this->rh->tpl->set($this->store_to, $this->models['bread']->data);
	}

}

?>