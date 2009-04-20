<?php

abstract class PopupObjects
{
	protected $db = null;
	protected $upload = null;
	protected $rubricId = 0;			// ������� �������
	protected $rubrics = array();		// ������ �� ����� ��������� array(0 => array('id' => 0, 'title' => 'hello'), ....)
	protected $rubricsLoaded = false;
	protected $rubricsTable = "pictures_topics";
	protected $table = "pictures";
	protected $items = array();
	protected $pager = null;
	protected $pageVar = "p";

	public function __construct($fileConfig)
	{
		include($fileConfig);
		
		$this->db = &Locator::get('db');
		
		$this->upload =&Locator::get('upload');
		$this->upload->setDir(Config::get('files_dir').$this->upload_dir.'/');
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
		$total = $this->getItemsCount();
		if (0 == $total)
		{
			return $this->items;
		}

		Finder::useClass('Pager');
		$this->pager = new Pager();
		$this->pager->setup(intval(RequestInfo::get($this->pageVar)), $total, 8, 5);

		$result = $this->db->execute("
			SELECT id, title
			FROM ??".$this->table."
			WHERE ".($this->rubricId ? "rubric_id =".$this->db->quote($this->rubricId)." AND " : "")." _state = 0
			LIMIT ".$this->pager->getOffset().",".$this->pager->getLimit()."
		");

		if ($result)
		{
            while ($r = $this->db->getRow($result))
    		{
    			if ($data = $this->getFile($r))
				{
					$this->items[] = $data;
				}
    		}
		}

		return $this->items;
	}

	public function getPages()
	{
		if (null !== $this->pager)
		{
			return $this->pager->getPages();
		}
		else
		{
			return array();
		}
	}

	public function setTable($value)
	{
		$this->table = $value;
	}

	public function setRubricsTable($value)
	{
		$this->rubricsTable = $value;
	}

	abstract protected function getFile($data);

	protected function getItemsCount()
	{
		$res = $this->db->queryOne("
			SELECT COUNT(id) AS total
			FROM ??".$this->table."
			WHERE ".($this->rubricId ? "rubric_id =".$this->db->quote($this->rubricId)." AND " : "")." _state = 0
		");

		return intval($res['total']);
	}

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