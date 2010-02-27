<?php

class PopupObjects
{
	protected $db = null;
	protected $upload = null;
	protected $rubricId = 0;			// текуща€ рубрика
	protected $rubrics = array();		// массив со всеми рубриками array(0 => array('id' => 0, 'title' => 'hello'), ....)
	protected $rubricsLoaded = false;
	protected $rubricsTable = "files_rubrics";
	protected $rubricsTypeId = 0;

	protected $items = array();
	
	protected $model = null;

	protected $configKey = '';
        
        protected $perPage = 10;

	public function __construct()
	{		
		$this->db = &Locator::get('db');
	}

	public function setRubric($value)
	{
		$this->rubricId = $value;
	}
	
	public function setRubricsTypeId($value)
	{
		$this->rubricsTypeId = $value;
	}
	
	public function setConfigKey($value)
	{
		$this->configKey = $value;
	}

	public function getRubrics()
	{
		if (!$this->rubricsLoaded)
		{
			$this->rubricsLoaded = true;
			$this->loadRubrics();
		}
		return $this->rubrics;
	}

	public function getItems()
	{
		$model = &$this->getModel();
		
		$total = $model->getCount();		
		if (0 == $total)
		{
			return $this->items;
		}

		$model->load();
		
		return $model->getArray();
	}
        
        public function setPerPage($perPage)
        {
            if ( $perPage > 0 )
                $this->perPage = $perPage;
        }

	public function getPages()
	{
		return $this->getModel()->getPages();
	}
	
	protected function &getModel()
	{
		if (!$this->model)
		{
			Finder::useModel('DBModel');
			$this->model = DBModel::factory('FilesModel/cms_list');
			
			$this->model->where = $this->model->where . ($this->where ? ($this->model->where ? ' AND ' : '') . $this->where : '') ;
			
			$this->model->enablePager();
			$this->model->setPagerPerPage($this->perPage);
			
			$this->model->addFilesConfig(array('config' => $this->configKey, 'lazy_load' => false));
			
			$files2rubricsModel = &$this->model->getForeignModel('rubric');
			$files2rubricsModel->where = $files2rubricsModel->where.( $files2rubricsModel->where ? " AND " : "" ). "{rubric.type_id} = ".DBModel::quote($this->rubricsTypeId);
			
			$this->model->setOrder('{_created} DESC'); // {rubric._order} ASC 
			
			if ($this->rubricId)
			{
				// condition on rubric
				$files2rubricsModel->where = $files2rubricsModel->where.( $files2rubricsModel->where ? " AND " : "" ). "{rubric_id} = ".DBModel::quote($this->rubricId);
			}
		}

		return $this->model;
	}

	public function handlePost()
	{		
		if (is_uploaded_file($_FILES['file']['tmp_name']))
		{			
			$rubricId = intval($_POST['rubric']);
			if (!$rubricId)
			{
				Locator::get('tpl')->set('file_err', 'ѕоле "–убрика" об€зательно дл€ заполнени€');
				return;
			}
			
			$file = FileManager::getFile($this->configKey.':picture');

			try
			{						
				$file->upload($_FILES['file']);
			}
			catch(UploadException $e)
			{
				Locator::get('tpl')->set('file_err', $e->getMessage());
			}
			
			if ($file['id'])
			{
				$file->addToRubric($rubricId);

				$data = array(
					'title' => $_POST['title'],
					'title_pre' => Locator::get('tpl')->action('typografica', $_POST['title'])
				);
				
				$file->updateData($data);
			}
		}
	}

	protected function loadRubrics()
	{
		$this->rubrics = $this->db->query("
			SELECT id, title
			FROM ??".$this->rubricsTable."
			WHERE type_id = ".$this->db->quote($this->rubricsTypeId)." AND _state = 0
		", "id");

		// mark current rubric
		if (isset($this->rubrics[$this->rubricId]))
		{
			$this->rubrics[$this->rubricId]['selected'] = true;
		}
	}
}

?>