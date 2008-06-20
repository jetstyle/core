<?php


class SqlDump
{
	protected $rh;
	
	public function __construct(&$rh)
	{
		$this->rh = &$rh;
	}
	
	public function dumpStructure($tableName, $filename = '')
	{
		$result = $this->rh->db->query("SHOW CREATE TABLE ".$this->quoteName($tableName)."");
		$createSql = $result[0]['Create Table'].';';
		
		if (strlen($filename) > 0)
		{
			$fp = fopen($filename, 'a');
			fwrite($fp, $createSql);
			fclose($fp);
		}
		
		return $createSql;
	}
	
	public function dumpData($tableName, $filename = '')
	{
		
	}
	
	protected function quoteName($name)
	{
		return '`'.str_replace('`', '``', $name).'`';
	}
}


?>