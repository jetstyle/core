<?php
$this->UseClass("models/DBModel");

class DBConfig extends DBModel
{
	protected $table = 'config';

	protected $fields = array(
		'name', 
		'value',
	); 

//	public function load($where=NULL, $limit=NULL, $offset=NULL)
//	{
//		parent::load($where, $limit, $offset);
//		$t = array();
//		foreach ($this->data as $v)
//		{
//			$key = strtr($v['name'], array('.' => '_'));
//			$value = $v['value'];
//
//			$t[$key] = $value;
//		}
//		$this->data = $t;
//	}
}  

?>
