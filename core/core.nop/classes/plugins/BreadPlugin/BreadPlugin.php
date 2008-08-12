<?php

Finder::useClass('Plugin');

class BreadPlugin extends Plugin
{
	var $config_vars = array('store_to');

	function initialize($config)
	{
		if ($this->initialized) return;

		parent::initialize($config);

		$current = &$this->rh->page;

		$model = & DBModel::factory('Content')
							->clearFields()
							->addFields(array('id','_left', '_right', '_level', '_path', '_parent'))
							->addField('title_short', 'IF(LENGTH(title_short) > 0, title_short, title_pre)')
							->addField('href', '_path')
							->setOrder(array('_left' => 'ASC'))
							->setWhere('_left <= '.DBModel::quote($current['_left']).' AND _right >= '.DBModel::quote($current['_right']))
							->load();

		$this->models['bread'] =& $model;
	}

	function addItem($path, $title, $hide = 0)
	{
		$this->models['bread'][] = array('href'=>$path, 'title_short'=>$title, 'hide' => $hide);
	}

	function rend()
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