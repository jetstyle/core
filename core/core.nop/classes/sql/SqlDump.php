<?php

class SqlDump
{
	protected $rh;
	protected $fp = null;		// file pointer
	protected $buffer = '';
	
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
			$this->openFile($filename);
			$this->writeToFile($createSql);
			$this->closeFile();			
		}
		
		return $createSql;
	}
	
	public function dumpData($tableName, $filename = '', $where = '')
	{
		$sql = '';
		$row = 0;
		
		$result = $this->rh->db->execute("SELECT * FROM ".$this->quoteName($tableName).(strlen($where) > 0 ? " WHERE ".$where : ""));
		
		if ($result)
		{
			if (strlen($filename) > 0)
			{
				$this->openFile($filename);
			}
							
			while ($r = $this->rh->db->getRow($result))
			{
				if ($row == 0)
				{
					$sql .= "INSERT INTO ".$this->quoteName($tableName)."(".implode(',', array_map(array(&$this, 'quoteName'), array_keys($r))).")\nVALUES \n";
				}
				else
				{
					$sql .= ",\n";
				}
				
				$sql .= '('.implode(',', array_map(array(&$this->rh->db, 'quote'), $r)).')';
				
				if (strlen($filename) > 0)
				{
					$this->bufferedWriteToFile($sql);
					$sql = '';
				}
				
				$row++;
			}

			$sql .= ";\n\n";
			
			if (strlen($filename) > 0)
			{
				$this->bufferedWriteToFile($sql);
				$this->flushBuffer();
				$this->closeFile();
			}
		}
		
		return $sql;		
	}
	
	protected function quoteName($name)
	{
		return '`'.str_replace('`', '``', $name).'`';
	}
	
	protected function openFile($filename)
	{
		$this->fp = fopen($filename, 'a');
	}
	
	protected function writeToFile($data)
	{
		fwrite($this->fp, $data);
	}
	
	protected function bufferedWriteToFile($data)
	{
		$this->buffer .= $data;
		
		if (strlen($this->buffer) > 512000)
		{
			$this->flushBuffer();
		}
	}
	
	protected function flushBuffer()
	{
		if (strlen($this->buffer) > 0)
		{
			$this->writeToFile($this->buffer);
			$this->buffer = '';
		}
	}
	
	protected function closeFile()
	{
		fclose($this->fp);
	}
}


?>