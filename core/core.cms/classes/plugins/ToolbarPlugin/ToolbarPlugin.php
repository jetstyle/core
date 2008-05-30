<?php
$this->useClass('controllers/Plugin');

class ToolbarPlugin extends RenderablePlugin
{
	var $config_vars = array (
		'store_to',
	);

	function initialize(& $ctx, $config = NULL)
	{
		parent :: initialize($ctx, $config);

		if (!$this->rh->ri->get('hide_toolbar'))
 		{
 			$this->rh->useClass('Toolbar');
			$toolbar = new Toolbar($this->rh);
			$this->data = $toolbar->getData();
			$this->goTo = $toolbar->getGoToList();
 		}
	}

	function rend(& $ctx)
	{
		$this->rh->tpl->set($this->store_to, $this->data);
		$this->rh->tpl->set('goto', $this->goTo);
		$this->rh->tpl->set('front_end_url',$this->rh->front_end->path_rel);
	}
}
?>