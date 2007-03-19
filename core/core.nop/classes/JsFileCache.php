<?php

/**
 * Класс JsFileCache - управляет кешами
 */
class JsFileCache
{

	var $_sources = array();

	function JsFileCache()
	{
	}

	// FIXME: lucky: may be refactor interface
	function initialize($config)
	{
		$this->file_path = $config['file_path'];
	}

	function finalize()
	{
	}

	function isValid()
	{
		$cached_mtime = @filemtime($this->file_path);
		$this->fp = $this->getReadableFile();
		if (empty($this->fp)) return False;
		$str = $this->readLn(); // <?php
		$str = $this->readLn();
		$count = intval(substr($str, 2));
		$status = True;
		for($i=0; $i < $count; $i++)
		{
			$file_source = substr($this->readLn(), 2);
			if (!is_file($file_source) || $cached_mtime < @filemtime($file_source)) 
			{
				$status = False;
				break;
			}
		}
		$this->close();
		return $status;
	}

	function getFileName()
	{
		return $this->file_path;
	}

	function close()
	{
		$this->_close($this->fp);
		unset($this->fp);
	}

	function _read($fp, $length=1024)
	{
		return fgets($fp, $length);
	}

	function readLn()
	{
		$str = $this->_read($this->fp);
		return substr($str, 0, -1);
	}

	function load()
	{
	}

	function getWriteableFile()
	{
		return fopen($this->file_path, 'w');
	}

	function getReadableFile()
	{
		return @fopen($this->file_path, 'r');
	}

	function save($str)
	{
		$this->fp = $this->getWriteableFile();
		$this->writeHeader();
		$this->writeLn($str);
		$this->writeFooter();
		$this->close();
	}

	function writeHeader()
	{
		$sources = $this->getSources();
		$this->writeLn('<?php');
		$this->writeLn('# '.count($sources));
		foreach ($sources as $source)
		{
			$this->writeLn('# '.$source);
		}
		$this->writeLn('');
	}

	function writeFooter()
	{
		$this->writeLn('?>');
	}

	function writeLn($str)
	{
		return $this->_write($this->fp, $str."\n");
	}

	function _write($fp, $str)
	{
		if (empty($this->fp)) 
		{
			trigger_error('JsFileCache:: can\'t write to file '.$this->file_path);
			return False;
		}
		else
			return fwrite($fp, $str);
	}

	function _close($fp)
	{
		return fclose($fp);
	}

	function addSource($path)
	{
		$this->_sources[$path] = $path;
	}

	function getSources()
	{
		return $this->_sources;
	}

}

?>
