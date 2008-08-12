<?php
Finder::useClass('Plugin');

class ToolbarPlugin extends Plugin
{
	var $config_vars = array (
		'store_to',
	);

	function initialize($config = NULL)
	{
		parent :: initialize($config);

		if (!RequestInfo::get('hide_toolbar'))
 		{
 			Finder::useClass('Toolbar');
			$toolbar = new Toolbar($this->rh);
			$this->data = $toolbar->getData();
			$this->goTo = $toolbar->getGoToList();
 		}
	}

	function rend()
	{
		$this->rh->tpl->set($this->store_to, $this->data);
		$this->rh->tpl->set('goto', $this->goTo);
		
		// $this->rh->tpl->set('front_end_url',$this->rh->front_end->path_rel);
	}
}
?>