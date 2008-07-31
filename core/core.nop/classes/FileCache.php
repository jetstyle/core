<?php

/**
 * Класс FileCache - управляет кешами
 */
class FileCache
{
	protected $filePath = '';
	protected $cacheSources = null;		// источники, прочитанные из закешированного файла
	protected $sources = array();		// источники, которые добавятся в заголовок кэша при записи
	protected $fp = null;
	
	public function __construct($filePath)
	{
		$this->filePath = $filePath;
	}

	public function getSources()
	{
		if ($this->cacheSources === null)
		{
			$this->getSourcesFromFile();
		}
		return $this->cacheSources;
	}
	
	public function addSource($path)
	{
		$this->sources[$path] = $path;
	}
	
	public function getFileName()
	{
		return $this->filePath;
	}
	
	public function isValid()
	{
		if (!file_exists($this->filePath))
		{
			return false;
		}

		$sources = $this->getSources();
		
		if (count($sources) > 0)
		{
			$status = True;
			$cachedMtime = @filemtime($this->filePath);
			
			foreach ($sources AS $fileSource)
			if (!is_file($fileSource) || $cachedMtime < @filemtime($fileSource)) 
			{
				$status = False;
				break;
			}
		}
		else
		{
			return false;
		}
		
		return $status;
	}

	public function write($str)
	{
		$this->openFileForWrite();
		
		$this->writeHeader();
		$this->writeLn($str);
		$this->writeFooter();
		$this->close();
		
		return true;
	}
	
	protected function getSourcesFromFile()
	{
		$this->cacheSources = array();
		
		try
		{
			$this->openFileForRead();
		}
		catch(Exception $e)
		{
			return;
		}
		
		$str = $this->readLn(); // <?php
		$str = $this->readLn();
		$count = intval(substr($str, 2));

		for($i=0; $i < $count; $i++)
		{
			$this->cacheSources[] = substr($this->readLn(), 2);
		}
		
		$this->close();
	}
	
	protected function close()
	{
		fclose($this->fp);
		$this->fp = null;
	}

	protected function _read($length=1024)
	{
		return fgets($this->fp, $length);
	}

	protected function readLn()
	{
		$str = $this->_read();
		return substr($str, 0, -1);
	}

	protected function openFileForWrite()
	{
		$this->fp = @fopen($this->filePath, 'w');
		if (!$this->fp)
		{
			throw new Exception("Can't write to file ".$this->filePath);
		}
		return $this->fp;
	}

	protected function openFileForRead()
	{
		$this->fp = @fopen($this->filePath, 'r');
		if (!$this->fp)
		{
			throw new Exception("Can't open file ".$this->filePath);
		}
		return $this->fp;
	}

	protected function writeHeader()
	{
		$this->writeLn('<?php');
		$this->writeLn('# '.count($this->sources));
		foreach ($this->sources as $source)
		{
			$this->writeLn('# '.$source);
		}
		$this->writeLn('');
	}

	protected function writeFooter()
	{
		$this->writeLn('?>');
	}

	protected function writeLn($str)
	{
		return $this->_write($str."\n");
	}

	protected function _write($str)
	{
		if ($this->fp === null) 
		{
			throw new Exception('FileCache:: can\'t write to file '.$this->filePath);
		}
		else
		{
			return fwrite($this->fp, $str);
		}
	}

}

?>