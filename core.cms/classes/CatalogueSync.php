<?php
Finder::useModel('DBModel');
Finder::useClass('ModuleInterface');
class CatalogueSync implements ModuleInterface
{
	protected $config; //ссылка на объект класса ModuleConfig

	//templates
	protected $template = "form_sync.html";
	protected $prefix = "";
	protected $neededConfigVars = array('source');

	protected $logFileHandler = null;
	protected $downloadAttempts = 3;

	protected $syncData = array();
	
	public function __construct( &$config )
	{
	//base modules binds
		$this->config = $config;
		$this->prefix = implode('_', $config['module_path_parts']).'_';
	}
	
	public function getConfig()
	{
		return $this->config;
	}

	public function handle()
	{
		if (defined('COMMAND_LINE') && COMMAND_LINE)
		{
			if (defined('COMMAND_LINE_PARAMS') && COMMAND_LINE_PARAMS)
			{
				$params = unserialize(COMMAND_LINE_PARAMS);
				if ($params[0])
				{
					if ($params[0] == 'cleanup')
					{
						$this->delete();
						die();
					}
					else
					{
						$this->config['source'] = $params[0];
					}
				}
			}

			$this->startSync();
			die('0');
		}
		else
		{
			Locator::get('tpl')->set('source_filename', $this->getSourceFilename());
			Locator::get('tpl')->set('cron_line', Config::get('app_name').'/Do/'.$this->config['module_path']);

			if ($_POST[$this->prefix.'update'])
			{
				if (is_uploaded_file($_FILES[$this->prefix.'source_file']['tmp_name']))
				{
					$this->config['source'] = $_FILES[$this->prefix.'source_file']['tmp_name'];
					$this->startSync();
				}
				elseif ($_POST[$this->prefix.'source'])
				{
					$this->config['source'] = $_POST[$this->prefix.'source'];
					$this->startSync();
					Locator::get('db')->query("UPDATE ??config SET value = ".Locator::get('db')->quote($_POST[$this->prefix.'source'])." WHERE name = 'sync_source'");
				}
				elseif ($this->config['source'])
				{
					$this->startSync();
				}
			}
			elseif ($_POST[$this->prefix.'delete'])
			{
				$this->delete();
			}
		}
	}

	public function getHtml()
	{
		Finder::pushContext();
		Finder::prependDir(Config::get('cms_dir').'modules/'.$this->config['module_name'].'/');

		$tpl = Locator::get('tpl');
		$tpl->pushContext();

		$tpl->set( 'prefix', $this->prefix );

		$result = $tpl->parse($this->template);

		$tpl->popContext();
		Finder::popContext();

		return $result;
	}

	public function element($key, $element)
	{
		$model = $this->getModelForKey($key);
		$conf = $this->getFieldConf($key);

		$data = $this->prepareElement($key, $element);

		if ($conf['tree'])
		{
			if (!array_key_exists('_level', $data))
			{
				$data['_level'] = $element->getLevel();
			}

			if ($element->getLevel() > 1 && !array_key_exists('_parent', $data))
			{
				$data['_parent'] = $this->syncData[$key]['stack'][$element->getLevel() - 1][$keyField];
			}
			$this->syncData[$key]['stack'][$element->getLevel()] = $data;
		}

		if (!$conf['keep_old'])
		{
			$data['usn'] = $this->getUsnForKey($key);
		}

		if ($conf['custom_model'])
		{
			$model->syncItem($this, $data);
		}
		else
		{
			$keyField = $conf['key'];
			if (array_key_exists($keyField, $data) && $data[$keyField])
			{
				$model->loadOne("{".$keyField."} = ".$data[$keyField]);
				if (!$model[$keyField])
				{
					$model->insert($data);
				}
				else
				{
					$model->update($data, "{".$keyField."} = ".$data[$keyField]);
				}
			}
			else
			{
				$this->writeToLog($key.": no key found in dataset");
			}
		}
	}

	/**
	 *  dummy funcs for interface
	 */
	public function insert($postData = array())
	{

	}

	public function update()
	{

	}

	public function load()
	{

	}

	protected function startSync()
	{
		$this->checkConfig();
		$this->parseConfig();

		@set_time_limit(0);
		@ignore_user_abort(true);

		$this->openLogFile();
		$this->writeToLog('start');

		$downloadResult = false;

		if ($this->isFileRemote())
		{
			$attempts = 0;
			do
			{
				$downloadResult = $this->downloadFile();
				$attempts++;
			}
			while (!$downloadResult && $attempts < $this->downloadAttempts);
		}
		else if ($this->isFileExists())
		{
			$downloadResult = true;
		}

		if ($downloadResult)
		{
			if ($this->isFileRemote())
			{
				$this->parseFile($this->getLocalFilename());
			}
			else
			{
				$this->parseFile($this->getSourceFilename());
			}
		}
		else
		{
			throw new JSException("Can't get file \"".$this->getSourceFilename()."\"");
		}

		$this->deleteOld();

		foreach ($this->syncData AS $k => $v)
		{
			$conf = $this->getFieldConf($k);
			if ($conf['tree'])
			{
				$model = $this->getModelForKey($k);
				$model->rebuild();
			}
		}

		$this->writeToLog('finish');
		$this->closeLogFile();
	}

	protected function downloadFile()
	{
		$result = true;

		$remoteHandle = fopen($this->getSourceFilename(), "rb");
		$localHandle = fopen($this->getLocalFilename(), "wb");

		if ($remoteHandle && $localHandle)
		{
			while (!feof($remoteHandle))
			{
				$contents = fread($remoteHandle, 8192);
				if ($contents === false)
				{
					$result = false;
					break;
				}
				$writeResult = fwrite($localHandle, $contents);
				if ($writeResult === false)
				{
					$result = false;
					break;
				}
			}
		}
		else
		{
			$result = false;
		}

		@fclose($remoteHandle);
		@fclose($localHandle);

		return $result;
	}

	protected function deleteOld()
	{
		foreach ($this->syncData AS $k => $v)
		{
			$conf = &$this->getFieldConf($k);
			if ($conf && !$conf['keep_old'])
			{
				$model = $this->getModelForKey($k);

				if ($conf['custom_model'])
				{
					$model->deleteOutdated($this->getUsnForKey($k));
				}
				else
				{
					$model->delete('{usn} < '.DBModel::quote($this->getUsnForKey($k)));
				}
			}
		}
	}

	protected function getUsnForKey($key)
	{
		$conf = &$this->getFieldConf($key);

		if ($conf)
		{
			if (!$conf['usn'])
			{
				if ($conf['custom_model'])
				{
					$conf['usn'] = $this->getModelForKey($key)->getUSN() + 1;
				}
				else
				{
					$model = clone $this->getModelForKey($key);
					$model->setFields(array('usn' => 'MAX({usn})'));
					$model->loadOne();
					$conf['usn'] = intval($model['usn']) + 1;
				}
			}

			return $conf['usn'];
		}
		else
		{
			return null;
		}
	}

	protected function parseFile($filename)
	{
		$this->writeToLog('parsing');

		Finder::useClass('XMLParser');
		$parser = new XMLParser();
		$parser->setSource($filename);

		foreach ($this->syncData AS $k => $v)
		{
			if (array_key_exists('fields', $v))
			{
				$parser->registerObserver($k, $v['tag'], array($this, 'element'), XMLParser::ITEM_WITH_CHILDREN);
			}
			else
			{
				$parser->registerObserver($k, $v['tag'], array($this, 'element'), XMLParser::ITEM);
			}
		}

		$parser->parse();
	}

	protected function delete()
	{
		$this->parseConfig();
		foreach ($this->syncData AS $k => $v)
		{
			$model = $this->getModelForKey($k);
			$conf = &$this->getFieldConf($k);

			if ($conf['custom_model'])
			{
				$model->deleteSyncedItems();
			}
			else
			{
				$model->clean(true);
			}
		}
	}

	protected function prepareElement($key, $element)
	{
		$result = array();
		$conf = $this->getFieldConf($key);
		if (is_array($conf))
		{
			if (is_array($conf['fields']))
			{
				foreach ($conf['fields'] AS $k => $v)
				{
					if (is_numeric($k))
					{
						$k = $v;
					}

					if ($v == '-')
					{
						$result[$k] = iconv('utf-8', 'cp1251', $element->getContent());
					}
					else
					{
						$arr = $element->$v;
						if ($arr && $arr[0])
						{
							$result[$k] = iconv('utf-8', 'cp1251', $arr[0]->getContent());
						}
					}
				}
			}

			if (is_array($conf['attributes']))
			{
				foreach ($conf['attributes'] AS $k => $v)
				{
					if (is_numeric($k))
					{
						$k = $v;
					}
					$result[$k] = iconv('utf-8', 'cp1251', $element[$v]);
				}
			}
		}

		return $result;
	}

	public function getModelForKey($key)
	{
		$model = null;
		$conf = &$this->getFieldConf($key);
		if ($conf)
		{
			if (!array_key_exists('cached_model', $conf))
			{
				$conf['cached_model'] = $this->constructModelForKey($key);
				if (in_array('SyncModelInterface', class_implements($conf['cached_model'])))
				{
					$conf['custom_model'] = true;
				}
				elseif (!in_array('DBModel', class_parents($conf['cached_model'])) && get_class($conf['cached_model'] != 'DBModel'))
				{
					throw new JSException("Model for key '".$key."' should implement SyncModelInterface or be the instance of DBModel or its children");
				}
			}
			$model = $conf['cached_model'];
		}
		return $model;
	}

	protected function constructModelForKey($key)
	{
		$model = null;
		$conf = $this->getFieldConf($key);
		if ($conf && $conf['model'])
		{
			try
			{
				$model = DBModel::factory($conf['model']);
			}
			catch (JSException $e)
			{
				$className = $conf['model'];
				if (Finder::findScript('classes', $className))
				{
					Finder::useClass($className);
					$model = new $className();
				}
				else
				{
					throw $e;
				}
			}
		}
		return $model;
	}

	protected function &getFieldConf($key)
	{
		return $this->syncData[$key];
	}

	protected function getLocalFilename()
	{
		return Config::get('files_dir').'import.xml';
	}

	protected function getSourceFilename()
	{
		$file = $this->config['source'];
		$file = str_replace('{files}', Config::get('files_dir'), $file);
		return $file;
	}

	protected function isFileRemote()
	{
		$result = false;
		$file = $this->getSourceFilename();

		if (preg_match('/^(http|ftp):\/\//i', $file))
		{
			$result = true;
		}

		return $result;
	}

	protected function isFileExists()
	{
		return file_exists($this->getSourceFilename());
	}

	protected function checkConfig()
	{
		if (is_array($this->neededConfigVars) && !empty($this->neededConfigVars))
		{
			if (!is_array($this->config))
			{
				throw new JSException("You must define \"".implode('", "', $this->neededConfigVars)."\" in config (".get_class($this).")");
			}

			foreach ($this->neededConfigVars AS $key)
			{
				if (!array_key_exists($key, $this->config))
				{
					throw new JSException("You must define \"".$key."\" in config (".get_class($this).")");
				}
			}
		}
	}

	protected function parseConfig()
	{
		foreach ($this->config AS $k => $v)
		{
			if (is_array($v) && $v['tag'])
			{
				$this->syncData[$k] = $v;
			}
		}
	}

	protected function openLogFile()
	{
		$this->logFileHandler = fopen(Config::get('files_dir').'import_log.txt', 'w+');
	}

	protected function closeLogFile()
	{
		if ($this->logFileHandler)
		{
			fclose($this->logFileHandler);
		}
	}

	protected function writeToLog($string)
	{
		if ($this->logFileHandler)
		{
			$string = date('d.m.Y H:i:s').' '.$string."\n";
			fwrite($this->logFileHandler, $string);
		}
	}
}
?>