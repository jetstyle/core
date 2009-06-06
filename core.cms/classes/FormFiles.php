<?php

Finder::useClass('FormSimple');

class FormFiles extends FormSimple
{
	const FILES_RUBRIC_TYPE_ID = 0;
	const PICTURES_RUBRIC_TYPE_ID = 1;
	
	protected $upload;
	protected $max_file_size = 55242880; //максимальный размер файла для загрузки
	protected $template_files = 'formfiles.html';

	protected $filesConfig = array();
	protected $inputs2configs = array();
	protected $configKey = '';
	
	protected $filesRubrics = array();
	
	private $uploadedFiles = array();
	
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
	protected function getFilesRubric($file = null)
	{
		if ($file && $file->isImage())
		{
			$rubricTypeId = self::PICTURES_RUBRIC_TYPE_ID;
		}
		else
		{
			$rubricTypeId = self::FILES_RUBRIC_TYPE_ID;
		}
		
		if ( !array_key_exists($rubricTypeId, $this->filesRubrics) )
		{
			$parts = explode('/', $this->config->componentPath);
			$moduleName = array_shift($parts);
			
			$rubric = DBModel::factory('FilesRubrics');
			$rubric->loadOne('{type_id} = '.DBModel::quote($rubricTypeId).' AND {module} = '.DBModel::quote($moduleName));
			
			if (!$rubric['id'])
			{
				$data = array(
					'module' => $moduleName,	
					'title' => $moduleName,
					'type_id' => $rubricTypeId,
					'_state' => 0,
					'_created' => date('Y-m-d H:i:s'),
				);
				$id = $rubric->insert($data);
				
				$data = array(
					'_order' => $id,
				);				
				$rubric->update($data, '{id} = '.DBModel::quote($id));
				$rubric->loadOne('{id} = '.DBModel::quote($id));
			}
			$this->filesRubrics[$rubricTypeId] = $rubric;
		}
		
		return $this->filesRubrics[$rubricTypeId];
	}
	
	protected function getUploadedFiles()
	{
		return $this->uploadedFiles;
	}
	
	protected function update()
	{
		$updateResult = parent :: update();
		if( $updateResult )
		{
			$this->uploadFiles();
		}
		
		return $updateResult;
	}
	
	protected function uploadFiles($objId = null, $isId = false)
	{
		if ($objId === null)
		{
			$objId = $this->id;
		}
				
		//загружаем и удаляем файлы
		foreach ($this->inputs2configs AS $inputName => $conf)
		{
			$file = FileManager::getFile($this->configKey.':'.$conf['key'], $objId, $isId);
			
			if (is_uploaded_file($_FILES[$this->prefix.$inputName]['tmp_name']))
			{					
				try
				{
					$file->upload($_FILES[$this->prefix.$inputName]);
					$filesRubric = $this->getFilesRubric($file);
					$file->addToRubric($filesRubric['id']);					
				}
				catch( UploadException $e )
				{
					$this->tpl->set($inputName.'_err', $e->getMessage());
				}
				
				$this->uploadedFiles[$inputName] = $file;
			}
			elseif ($_POST[$this->prefix.$inputName.'_del'])
			{
				if ($isId)
				{
					$filesRubric = $this->getFilesRubric($file);
					$file->removeFromRubric($filesRubric['id']);
				}
				else
				{
					$file->deleteLink();
				}
			}
		}
	}

	public function delete()
	{
		$upload =& $this->upload;

		$res = parent :: delete();
		// delete forever
		if( 2 == $res )
		{
			$this->deleteFiles();
		}
		return $res;
	}
	
	protected function deleteFiles($objId = 0, $isId = false)
	{
		if (!empty($this->inputs2configs))
		{
			$objId = intval($objId);
			if (!$objId)
			{
				$objId = $this->id;
			}
			
			if (!$objId) return;
			
			foreach ($this->inputs2configs AS $conf)
			{
                $file = FileManager::getFile($this->configKey.':'.$conf['key'], $objId, $isId);
				if ($isId)
				{
					$filesRubric = $this->getFilesRubric($file);
					$file->removeFromRubric($filesRubric['id']);
				}
				else
				{
					$file->deleteLink();
				}
			}
		}
	}
}
?>