<?php
$this->UseClass("models/DBModel");

class BasicMenu extends Model
{
	// lucky@npj:
	// уровень, до которого подгружать меню
	// TODO:  вынести в конфиги
	var $level = 1;
	var $depth = 2;
	var $left = NULL;
	var $right = NULL;
	var $fields = array('id', 'title_pre', '_left', '_right', '_level', '_path', '_parent');
	var $order = array('_left');

	function load($where=NULL, $limit=NULL, $offset=NULL)
	{
		$this->rh->useClass('models/Content');
		$m =& new Content();
		if (isset($this->fields_info)) $m->fields_info = $this->fields_info;
		if (isset($this->fields)) $m->fields = $this->fields;
		if (isset($this->order)) $m->order = $this->order;
		$m->initialize($this->rh);
		if (!isset($where)) $where = array();
		$where[] = '(_level >= '.$m->quote($this->level)
			. ' AND _level <'.$m->quote($this->level + $this->depth) 
			.')';
		if (isset($this->left)) $where[] = '_left > ' . $m->quote($this->left);
		if (isset($this->right)) $where[] = '_right < ' . $m->quote($this->right);

		$this->where = implode(" AND ", $where);

		$m->load($this->where, $limit, $offset);

		$this->data = $m->data;
	}

}  


?>
