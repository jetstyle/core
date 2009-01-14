<?php

class SqlDump
{
	protected $db;
	protected $fp = null;		// file pointer
	protected $buffer = '';
	
	public function __construct()
	{
		$this->db = &Locator::get('db');
	}
	
	public function dumpStructure($tableName, $tablePrefix, $filename = '', $dumpData = false)
	{
		$result = $this->db->query("SHOW CREATE TABLE ".$this->quoteName($tableName)."");
		
		if (is_array($result) && !empty($result))
		{
			$result[0]['Create Table'] = str_replace($tablePrefix, '%DB_PREFIX%', $result[0]['Create Table']);
			$createSql = $result[0]['Create Table'].";\n\n";
			
			if (!$dumpData)
			{
				$createSql = preg_replace('#AUTO_INCREMENT=\d+#', '', $createSql);
			}
			
			$createSql = preg_replace('/^CREATE TABLE/', 'CREATE TABLE IF NOT EXISTS', $createSql);
		}
		else
		{
			$createSql = '';
		}
		
		if (strlen($filename) > 0 && strlen($createSql) > 0)
		{
			$this->openFile($filename);
			$this->writeToFile($createSql);
			$this->closeFile();			
		}
		
		return $createSql;
	}
	
	public function dumpData($tableName, $tablePrefix, $filename = '', $where = '')
	{
		$sql = '';
		$row = 0;
		
		$result = $this->db->execute("SELECT * FROM ".$this->quoteName($tableName).(strlen($where) > 0 ? " WHERE ".$where : ""));
		
		if ($result)
		{
			if (strlen($filename) > 0)
			{
				$this->openFile($filename);
			}
							
			while ($r = $this->db->getRow($result))
			{
				if ($row == 0)
				{
					$sql .= "INSERT INTO ".$this->quoteName(str_replace($tablePrefix, '%DB_PREFIX%', $tableName))."(".implode(',', array_map(array(&$this, 'quoteName'), array_keys($r))).")\nVALUES \n";
				}
				else
				{
					$sql .= ",\n";
				}
				
				$sql .= '('.implode(',', array_map(array(&$this->db, 'quote'), $r)).')';
				
				if (strlen($filename) > 0)
				{
					$this->bufferedWriteToFile($sql);
					$sql = '';
				}
				
				$row++;
			}

			if ($row > 0)
			{
				$sql .= ";\n\n";
				
				if (strlen($filename) > 0)
				{
					$this->bufferedWriteToFile($sql);
					$this->flushBuffer();
					$this->closeFile();
				}
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