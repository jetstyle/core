<?php

Finder::useClass('FormSimple');

class FormFiles extends FormSimple
{
	protected $upload;
	protected $max_file_size = 55242880; //максимальный размер файла для загрузки
	protected $template_files = 'formfiles.html';

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
		parent::initModel();
		
		// add files fields to model
		$this->model->addFilesConfig($this->configKey);
	}
	
	/**
	 * Rubric for files
	 *
	 */
	protected function getFilesRubric()
	{
		if ( null === $this->filesRubric )
		{
			$parts = explode('/', $this->config->componentPath);
			$moduleName = array_shift($parts);
			
			$this->filesRubric = DBModel::factory('FilesRubrics');
			$this->filesRubric->loadOne('{module} = '.DBModel::quote($moduleName));
			
			if (!$this->filesRubric['id'])
			{
				$data = array(
					'module' => $moduleName,	
					'title' => $moduleName,
					'_state' => 0,
					'_created' => date('Y-m-d H:i:s'),
				);
				$id = $this->filesRubric->insert($data);
				
				$data = array(
					'_order' => $id,
				);				
				$this->filesRubric->update($data, '{id} = '.DBModel::quote($id));
				$this->filesRubric->loadOne('{id} = '.DBModel::quote($id));
			}
		}
		
		return $this->filesRubric;
	}
	
	protected function update()
	{
		$updateResult = parent :: update();
		if( $updateResult )
		{
			$filesRubric = $this->getFilesRubric();
			
			//загружаем и удаляем файлы
			foreach ($this->inputs2configs AS $inputName => $conf)
			{
				$file = FileManager::getFile($this->configKey.':'.$conf['key'], $this->id);
				
				if (is_uploaded_file($_FILES[$this->prefix.$inputName]['tmp_name']))
				{					
					try
					{
						$file->upload($_FILES[$this->prefix.$inputName]);
						$file->addToRubric($filesRubric['id']);					
					}
					catch( UploadException $e )
					{
						$this->tpl->set($inputName.'_err', $e->getMessage());
					}
				}
				elseif ($_POST[$this->prefix.$inputName.'_del'])
				{
					$file->deleteLink();
				}
			}
		}
		
		return $updateResult;
	}

	protected function delete()
	{
		$upload =& $this->upload;

		$res = parent :: delete();
		// delete forever
		if( 2 == $res )
		{
			if (!empty($this->inputs2configs))
			{
				foreach ($this->inputs2configs AS $conf)
				{
					$file = FileManager::getFile($this->configKey.':'.$conf['name'], $this->id);
					$file->delete();					
				}
			}
		}
		return $res;
	}
}
?>