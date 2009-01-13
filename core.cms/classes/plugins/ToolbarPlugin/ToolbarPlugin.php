<?php
Finder::useClass('Plugin');

class ToolbarPlugin extends Plugin
{
	var $config_vars = array (
		'store_to',
	);

	var $title; //текущий заголовок меню
    
	function initialize($config = NULL)
	{
		parent :: initialize($config);

		if (!RequestInfo::get('hide_toolbar'))
 		{
 			Finder::useClass('Toolbar');
			$toolbar = new Toolbar();
			$this->data = $toolbar->getData();
			$this->goTo = $toolbar->getGoToList();
			$this->title = $toolbar->getTitle();
 		}
	}

	function rend()
	{
		$tpl = &Locator::get('tpl');
		$tpl->set($this->store_to, $this->data);
		$tpl->set('goto', $this->goTo);
	}
	
	function getTitle()
	{
	    return $this->title;
	}
}
?>