<?php

$this->useClass('controllers/Plugin');

class BreadPlugin extends RenderablePlugin
{
	var $config_vars = array('store_to');

	function initialize(&$ctx, $config)
	{
		if ($this->initialized) return;

		parent::initialize(&$ctx, $config);

		$current = &$this->rh->page->config;
		
		$model = & DBModel::factory('Content')
							->clearFields()
							->addFields(array('id','_left', '_right', '_level', '_path', '_parent'))
							->addField('title_short', 'IF(LENGTH(title_short) > 0, title_short, title_pre)')
							->addField('href', '_path')
							->setOrder(array('_left' => 'ASC'));
		
		$where = 
			'_left <= '.$model->quote($current['_left'])
			.' AND _right >= '.$model->quote($current['_right']);
		
		$model->load($where);

		$this->models['bread'] =& $model;
	}

	function addItem($path, $title, $hide = 0)
	{
//		$title = $this->smartTrim($title);
		$this->models['bread'][] = array('href'=>$path, 'title_short'=>$title, 'hide' => $hide);
	}

	function rend(&$ctx)
	{
		$total = count($this->models['bread']);
		$last = &$this->models['bread'][$total - 1];
		$last['last'] = true;
		$this->models['bread'][$total - 1] = $last;
		
		$this->rh->tpl->set($this->store_to, $this->models['bread']);
	}
	
	function smartTrim($txt)
	{
		$txt = trim($txt);
		if(strlen($txt) <= 35)
		{
			return $txt;
		}
		$_txt = substr($txt, 0, 35);
		return trim(substr($_txt, 0, strrpos($_txt, ' '))).'...';
	}

}

?>