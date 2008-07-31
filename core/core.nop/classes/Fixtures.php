<?php
/**
 * @author lunatic lunatic@jetstyle.ru
 *
 * @created 		12:40 31.07.2008
 * @last-modified 	12:40 31.07.2008
 */

class Fixtures
{
	protected $rh = null;
	protected $dir = '';
	protected $data = array();
	protected $cachedName = 'fixtures';
	protected $fileCacheObj = null;
	protected $filesHash = '';
	protected $files = array();
	
	public function __construct(&$rh)
	{
		$this->rh = &$rh;
	}
	
	public function setDir($dirName)
	{
		$this->dir = $dirName;
	}

	public function setCachedName($name)
	{
		$this->cachedName = $name;
	}
	
	public function get()
	{
		return $this->data;
	}
	
	public function load()
	{
		if (!is_dir($this->dir))
		{
			throw new Exception('Fixtures dir "'.$this->dir.'" doesn\'t exists.<br />If you don\'t need fixtures, disable it in config file. (use_fixtures=false)');
		}
		
		$this->countHash();
		
		// fixtures folder is empty
		if (empty($this->files))
		{
			return;
		}
		
		$this->fileCacheObj = new FileCache($this->cachedName.'.php');
		$sources = $this->fileCacheObj->getSources();
		
		// need to recompile	
		if ((count($sources) > 0 && $sources[0] !== $this->filesHash) || count($sources) == 0)
		{
			$this->compile();
		}
		else
		{
			$data = include $this->fileCacheObj->getFileName();
			$this->data = unserialize($data);
		}
	}
	
	/**
	 * Скомпилировать все фикстуры и положить их в кэш
	 *
	 */
	protected function compile()
	{
		$this->rh->useLib('spyc');
		foreach ($this->files AS $fileName)
		{
			$fileParts = pathinfo($fileName);
			$this->data[$fileParts['filename']] = Spyc :: YAMLLoad($this->dir.$fileName);
		}

		$this->fileCacheObj->addSource($this->filesHash);
		
		$str = "return '".str_replace("'", "\\'", serialize($this->data))."';";
		$this->fileCacheObj->write($str);
	}
	
	/**
	 * Подсчет хэша папки с фикстурами
	 *
	 */
	protected function countHash()
	{
		if ($handle = opendir($this->dir))
		{
			$this->files = array();
			$hash = '';
			
			while (false !== ($file = readdir($handle))) 
			{
				if ($file == '.svn' || $file == '.' || $file == '..')
				{
					continue;
				}
				
				$hash .= '|'.$file.'|'.filemtime($this->dir.'/'.$file).'|';
				$this->files[] = $file;
		    }
			closedir($handle);
			if (strlen($hash) > 0)
			{
				$hash = md5($hash);
			}
			$this->filesHash = $hash;
		}
	}
}
?>