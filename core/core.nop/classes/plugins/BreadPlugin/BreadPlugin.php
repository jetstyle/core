<?php

$this->useClass('controllers/Plugin');

class BreadPlugin extends RenderablePlugin
{
	var $config_vars = array('store_to', 'template');

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

	function addItem($path, $title)
	{
		$this->models['bread']->addItem(array('_path'=>$path, 'title_pre'=>$title));
	}

	function url_to(&$d)
	{
		$path = $d['_path'];
		return $this->rh->base_url . $path;
	}

	function rend(&$ctx)
	{
		$data = $this->models['bread']->data;
		foreach ($data as $k=>$v)
		{
			$data[$k]['link'] = $this->url_to($v);
		}
		$this->rh->UseClass("plugins/BreadPlugin/BreadView");
		$v =& new BreadView();
		$v->initialize($this->rh);
		$v->addModel($data, 'bread');
		$v->store_to = $this->store_to;
		$v->template = $this->template;
		$v->handle();
	}

}

?>
