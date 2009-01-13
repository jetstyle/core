<?php

$this->UseClass("views/View");
class BreadView  extends View
{
	var $store_to = '_breadView';
	var $template = 'blocks/path.html:List';

	function Handle()
	{

		$this->rh->UseClass("ListObject");
		$this->count = count($this->models['bread'])-1;
		$list =& new ListObject($this->rh, $this->models['bread']);
		$list->EVOLUTORS['suffix'] = array(&$this, "is_sel");
		$list->implode = true;
		$out = $list->parse($this->template, $this->store_to);

	}

	function is_sel(&$list)
	{
		return ($list->loop_index == $this->count ) ? "_Sel" : "";
	} 
}
