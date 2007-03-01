<?
$this->UseClass("models/DBModel");

class BasicMenu extends DBModel
{
	// lucky@npj:
	// уровень, до которого подгружать меню
	// TODO:  вынести в конфиги
	var $level = 1;
	var $depth = 2;
	var $left = NULL;
	var $right = NULL;
	var $fields = array('id', 'title_pre', '_left', '_right', '_level', '_path', '_parent');
	var $table = 'content';
	var $where = "_state = 0 AND _path <> '' ";

	function load($where='')
	{
		$where_sql = ' AND (_level >= '.$this->quote($this->level)
			. ' AND _level <'.$this->quote($this->level + $this->depth) 
			.')';
		$where_sql = $this->buildWhere($where_sql . $where);

		$sql = "SELECT ". $this->buildFields($this->fields) 
			. " FROM ".$this->buildTableNameAlias($this->table)
			. $where_sql
			. $this->buildOrderBy('_left');

		$this->data = $this->rh->db->query($sql);
	}

}  


?>
