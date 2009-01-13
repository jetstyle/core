<?php

abstract class PopupObjects
{
	protected $db = null;
	protected $upload = null;
	protected $rubricId = 0;			// текуща€ рубрика
	protected $rubrics = array();		// массив со всеми рубриками array(0 => array('id' => 0, 'title' => 'hello'), ....)
	protected $rubricsLoaded = false;
	protected $rubricsTable = "files_rubrics";

	protected $items = array();
	
	protected $model = null;

	public function __construct()
	{		
		$this->db = &Locator::get('db');
	}

	public function setRubric($value)
	{
		$this->rubricId = $value;
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

		$model->registerObserver('row', array($this, 'getFile'));
		$model->load();
		
		return $this->items;
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
			$this->model->setPagerPerPage(8);
			
			
			if ($this->rubricId)
			{
				// condition on rubric
				$files2rubricsModel = &$this->model->getForeignModel('rubric');
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
			
			$file = FileManager::getFile($this->configKey);

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
	
	abstract public function getFile(&$model, &$row);

	protected function loadRubrics()
	{
		$this->rubrics = $this->db->query("
			SELECT id, title
			FROM ??".$this->rubricsTable."
			WHERE _state = 0
		", "id");

		// mark current rubric
		if (isset($this->rubrics[$this->rubricId]))
		{
			$this->rubrics[$this->rubricId]['selected'] = true;
		}
	}
}

?>