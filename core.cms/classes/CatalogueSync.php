<?php
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

	public function handle()
	{
		$this->checkConfig();

		$this->parseConfig();

		// TODO: remove hack
		if (defined('COMMAND_LINE') && COMMAND_LINE || 1==1)
		{
			@set_time_limit(0);
			@ignore_user_abort();

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
			else
			{
				$downloadResult = true;
			}

			if ($downloadResult)
			{
				$this->parseFile();
			}
			else
			{
				throw new JSException("Can't download file \"".$this->getSourceFilename()."\"");
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

			$this->cleanup();
			$this->writeToLog('finish');
			$this->closeLogFile();
			die('0');
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

		$keyField = $conf['key'];
		if (array_key_exists($keyField, $data) && $data[$keyField])
		{
			if ($conf['tree'])
			{
				if ($element->getLevel() > 1)
				{
					$data['_parent'] = $this->syncData[$key]['stack'][$element->getLevel() - 1][$keyField];
				}
				$this->syncData[$key]['stack'][$element->getLevel()] = $data;
			}

			if (!$conf['keep_old'])
			{
				$data['usn'] = $this->getUsnForKey($key);
			}
			
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
			$conf = $this->getFieldConf($k);
			if ($conf && !$conf['keep_old'])
			{
				$model = $this->getModelForKey($k);
				$model->delete('{usn} < '.DBModel::quote($this->getUsnForKey($k)));
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
				$model = clone $this->getModelForKey($key);
				$model->setFields(array('usn' => 'MAX({usn})'));
				$model->loadOne();
				$conf['usn'] = intval($model['usn']) + 1;
			}

			return $conf['usn'];
		}
		else
		{
			return null;
		}
	}

	protected function parseFile()
	{
		$this->writeToLog('parsing');

		Finder::useClass('XMLParser');
		$parser = new XMLParser();
		$parser->setSource($this->getLocalFilename());

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

	protected function cleanup()
	{

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
						$arr = $element->$v;
						if ($arr && $arr[0])
						{
							$result[$v] = $arr[0]->getContent();
						}
					}
					else
					{
						if (is_array($v) && $v['to'])
						{
							$arr = $element->$k;
							if ($arr && $arr[0])
							{
								$result[$v['to']] = $arr[0]->getContent();
							}
						}
						else
						{
							$arr = $element->$k;
							if ($arr && $arr[0])
							{
								$result[$v] = $arr[0]->getContent();
							}
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
						$result[$v] = $element[$v];
					}
					else
					{
						if (is_array($v) && $v['to'])
						{
							$result[$v['to']] = $element[$k];
						}
						else
						{
							$result[$k] = $element[$k];
						}
					}
				}
			}
		}

		return $result;
	}

	protected function getModelForKey($key)
	{
		$model = null;
		$conf = &$this->getFieldConf($key);
		if ($conf)
		{
			if (!array_key_exists('cached_model', $conf))
			{
				$conf['cached_model'] = $this->constructModelForKey($key);
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
			$model = DBModel::factory($conf['model']);
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
		fclose($this->logFileHandler);
	}

	protected function writeToLog($string)
	{
		$string = date('d.m.Y H:i:s').' '.$string."\n";
		fwrite($this->logFileHandler, $string);
	}
}
?>