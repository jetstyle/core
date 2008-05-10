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
 		}
	}
	
	function rend(& $ctx)
	{
		$this->rh->tpl->set($this->store_to, $this->data);
	}
}
?>