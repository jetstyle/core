<?
$this->UseClass("models/DBModel");

class DBConfig extends DBModel
{
	var $table = 'config';

	var $fields = array(
		'name', 
		'value',
	); 

	var $where = '1 = 1 ';

	function load($where=NULL, $limit=NULL, $offset=NULL)
	{
		parent::load($where, $limit, $offset);
		$t = array();
		foreach ($this->data as $v)
		{
			$key = strtr($v['name'], array('.' => '_'));
			$value = $v['value'];

			$t[$key] = $value;
		}
		$this->data = $t;
	}
}  

?>
