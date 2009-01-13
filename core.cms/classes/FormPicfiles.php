<?php
Finder::useClass("FormSimple");
class FormPicfiles extends FormSimple
{
	public $delete_title = 'Удалить насовсем';
	
	protected $filesConfig = array();
	protected $inputs2configs = array();
	protected $configKey = '';
	
	protected $filesRubric = null;
	
	public function __construct( &$config )
	{
		Finder::useClass('FileManager');
		
		$key = explode('/', $config->componentPath);
		array_pop($key);
		$this->configKey = implode('/', $key);
		
		$this->filesConfig = FileManager::getConfig($this->configKey);
		
		// grep files inputs 
		if (is_array($this->filesConfig))
		{
			foreach ($this->filesConfig AS $key => &$conf)
			{
				if ($conf['input'])
				{
					$this->inputs2configs[$conf['input']] = &$conf; 
				}
			}
		}
		
		parent::__construct($config);
	}
	
	protected function initModel()
	{
		Finder::useModel('DBModel');
		$this->model = DBModel::factory('Files/cms_items');
	}

	protected function load()
	{
		if ($result = parent::load())
		{
			// load files
			
			Finder::useClass('FileManager');
			foreach ($this->filesConfig AS $key => $conf)
			{
				$this->item[$key] = FileManager::getFile($this->configKey.':'.$key, $this->id, true);

				// subconfigs
				if (is_array($conf['children']))
				{
					foreach ($conf['children'] AS $subKey => $subConf)
					{
						$this->item[$key.'_'.$subKey] = FileManager::getFile($this->configKey.':'.$key.'/'.$subKey, $this->id, true);
					}
				}
			}
		} 
		
		return $result;
	}
	
	protected function update()
	{
		$this->filters();
				
		$inputName = key($this->inputs2configs);
		$conf = $this->inputs2configs[$inputName];

		$file = FileManager::getFile($this->configKey.':'.$conf['key'], $this->id, true);
			
		if (is_uploaded_file($_FILES[$this->prefix.$inputName]['tmp_name']))
		{				
			try
			{
				$file->upload($_FILES[$this->prefix.$inputName]);
			}
			catch( UploadException $e )
			{
				$this->tpl->set($inputName.'_err', $e->getMessage());
				return false;
			}
	
			if (!$this->id)
			{
				$this->new_id = $this->id = $file['id'];
				RequestInfo::set($this->idGetVar, $this->id);
			}
		}
		elseif (!$this->id)
		{
			$this->tpl->set($inputName.'_err', 'Поле обязательно для заполнения');
		}
		
		
		if ($this->id)
		{
			// set rubric
			$rubric = $this->getFilesRubric();
			if(is_array($rubric) || ($rubric instanceof ArrayAccess))
			{
				$rubricId = intval($rubric['id']);
				if ($rubricId)
				{
					$file->addToRubric($rubricId);
				}
			}
			
			// @TODO: check upload state of file
			//        if we use file, that already exists, don't update title
			
			$file->updateData($this->postData);
		}
		else
		{
			return false;
		}
		
		return true;
	}
	
	protected function delete()
	{
		FileManager::getFile(null, $this->id)->delete();
				
		RequestInfo::free($this->idGetVar);
		return 2;
	}
	
	protected function getFilesRubric()
	{
		if ( null === $this->filesRubrics )
		{
			$parts = explode('/', $this->config->componentPath);
			$moduleName = array_shift($parts);
			
			$this->filesRubric = DBModel::factory('FilesRubrics');
			$this->filesRubric->loadOne('{id} = '.intval(RequestInfo::get('topic_id')));
		}
		
		return $this->filesRubric;
	}
}
?>