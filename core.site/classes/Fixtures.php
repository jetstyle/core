<?php
/**
 * Fixtures.
 * Load all fixtures and cache them.
 * 
 * @author lunatic <lunatic@jetstyle.ru>
 */
class Fixtures
{
	protected $dir = '';
	protected $data = array();
	protected $cachedName = 'fixtures';
	protected $cacheObj = null;
	protected $filesHash = '';
	protected $files = array();

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
			return;
		}

		$this->findFixtures();

		// fixtures folder is empty
		if (empty($this->files))
		{
			return;
		}
		
		$cacheObj = $this->getCacheObj();
		$cacheObj->setFile($this->cachedName.'.php');
		$cacheObj->addSources($this->files);
		$cacheObj->useHash(true);
				
		// need to recompile
		if (!$cacheObj->isValid())
		{
			// compile files
			Finder::useLib('spyc');
			foreach ($this->files AS $fileName)
			{
				$fileParts = pathinfo($fileName);
				$this->data[$fileParts['filename']] = Spyc :: YAMLLoad($fileName);
			}
			$str = "return '".str_replace("'", "\\'", serialize($this->data))."';";
			$cacheObj->write($str);
		}
		else
		{
			$data = include $cacheObj->getFileName();
			$this->data = unserialize($data);
		}
	}

	/**
	 * Подсчет хэша папки с фикстурами
	 *
	 */
	protected function findFixtures()
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
				$this->files[] = $this->dir.$file;
		    }
			closedir($handle);
		}
	}
	
	protected function getCacheObj()
	{
		if (null === $this->cacheObj)
		{
			$this->cacheObj = new FileCache();
		}
		return $this->cacheObj;
	}
}
?>