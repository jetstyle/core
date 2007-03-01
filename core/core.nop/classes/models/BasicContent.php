<?php

$this->useClass('models/DBModel');
class BasicContent extends DBModel
{
	var $fields = array('id', 
		'mode',
		'title', 'title_pre', 
		'lead', 'lead_pre', 
		'text', 'text_pre', 
		'announce_id1', 'announce_id2', 
		'_path', '_parent', '_level', '_left', '_right');
	var $table = 'content';
	var $where = '_state = 0';
	var $order = array('_level');

	function select($where=NULL, $limit=NULL, $offset=NULL)
	{
		$sql = ' SELECT '. $this->buildFields($this->fields)
			.' FROM '. $this->buildTableNameAlias($this->table)
			. $this->buildWhere($where)
			. $this->buildOrderBy($this->order) . ' DESC '
			. $this->buildLimit($limit, $offset);
		$rs = $this->rh->db->query($sql);
		return $rs;
	}

	function load($where=NULL, $limit=NULL, $offset=NULL)
	{
		$this->data = $this->select($where, $limit, $offset);
	}
}


?>
