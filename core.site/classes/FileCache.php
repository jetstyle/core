<?php
/**
 * FileCache.
 * Cache one or many files to the cache directory. Validating cache.
 *
 * @author lunatic <lunatic@jetstyle.ru>
 */
class FileCache
{
	protected $filePath = '';
	protected $cacheSources = null;		// источники, прочитанные из закешированного файла
	protected $sources = array();		// источники, которые добавятся в заголовок кэша при записи
	protected $fp = null;
	protected $useHash = false;
	protected $hash = '';

	public function __construct($filePath = '')
	{
		if ($filePath)
		{
			$this->setFile($filePath);
		}
	}

	/**
	 * Set cached filename
	 *
	 * @param string $filePath
	 * @return void
	 */
	public function setFile($filePath)
	{
		$this->filePath = Config::get('cache_dir').$filePath;
		$this->cachedSources = null;
		$this->sources = array();
		if ($this->fp)
		{
			$this->close();
		}
	}

	/**
	 * Add source.
	 *
	 * @param string $path
	 * @return void
	 */
	public function addSource($path)
	{
		$this->sources[$path] = $path;
	}

	/**
	 * Add sources.
	 *
	 * @param array $data
	 * @return void
	 */
	public function addSources($data)
	{
		if (is_array($data))
		{
			foreach ($data AS $path)
			{
				$this->addSource($path);
			}
		}
	}

	/**
	 * Get sources, extracted from cached file.
	 *
	 * @return array
	 */
	public function getSources()
	{
		if ($this->cacheSources === null)
		{
			$this->getSourcesFromFile();
		}
		return $this->cacheSources;
	}

	/**
	 * Get cached filename.
	 *
	 * @return string
	 */
	public function getFileName()
	{
		return $this->filePath;
	}

	/**
	 * Use files hash for validating cache file.
	 *
	 * @param boolean $state
	 * @return void
	 */
	public function useHash($state)
	{
		$this->useHash = $state;
	}

	/**
	 * Check, if cached filed is valid.
	 *
	 * @return boolean
	 */
	public function isValid()
	{
		if (!file_exists($this->filePath))
		{
			return false;
		}

		$sources = &$this->sources;

		if (count($sources) > 0)
		{
			$status = True;

			if ($this->useHash)
			{
				$hash = $this->getHash();
				$cachedSources = $this->getSources();
				if (!is_array($cachedSources) || $hash !== $cachedSources[0])
				{
					$status = false;
				}
			}
			else
			{
				$cachedMtime = @filemtime($this->filePath);

				foreach ($sources AS $fileSource)
				{
					if (!is_file($fileSource) || $cachedMtime < @filemtime($fileSource))
					{
						$status = False;
						break;
					}
				}
			}
		}
		else
		{
			$status = False;
		}

		return $status;
	}

	/**
	 * Write to file.
	 *
	 * @param string $str
	 * @return boolean
	 */
	public function write($str)
	{
		try
		{
			$this->openFileForWrite();

			$this->writeHeader();
			$this->writeLn($str);
			$this->writeFooter();
			$this->close();
		}
		catch (Exception $e)
		{
        	if (!Config::get('no_cache')) throw $e;
		}

		return true;
	}

	protected function countHash()
	{
		$this->hash = '';
		foreach ($this->sources AS $fileSource)
		{
			$this->hash .= '|'.$fileSource.'|'.filemtime($fileSource).'|';
		}
		$this->hash = md5($this->hash);
	}

	protected function getHash()
	{
		if (strlen($this->hash) != 32)
		{
			$this->countHash();
		}

		return $this->hash;
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
			//try to make dir
			$pathInfo = pathinfo($this->filePath);
			$result = @mkdir($pathInfo['dirname'], 0775, true);
			if ($result)
			{
				$this->fp = @fopen($this->filePath, 'w');
			}

			if (!$this->fp)
			{
                $dirName = $pathInfo['dirname'];
                if (method_exists('Config', 'get'))
                {
                    $dirName = str_replace(Config::get('project_dir'), '', $dirName);
                }
                $humanMessage = 'Невозможно записать файл <span class="example">'.$pathInfo['basename'].'</span> в директорию <span class="example">'.$dirName.'</span>.';
                $humanMessage .= '<br />';
                $humanMessage .= 'Убедитесь, что запись файлов в данную папку разрешена.';
                throw new JSException("Can't write to file ".$this->filePath, '', $humanMessage);
			}
		}
		return $this->fp;
	}

	protected function openFileForRead()
	{
		$this->fp = @fopen($this->filePath, 'r');
		if (!$this->fp)
		{
			throw new JSException("Can't open file ".$this->filePath);
		}
		return $this->fp;
	}

	protected function writeHeader()
	{
		if ($this->useHash)
		{
			array_unshift($this->sources, $this->getHash());
		}

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
		return fwrite($this->fp, $str);
	}

}

?>