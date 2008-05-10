<?php

class Jetfiles
{
	protected $rh = null;
	protected $upload = null;
	protected $rubricId = 0;			// текущая рубрика
	protected $rubrics = array();		// массив со всеми рубриками array(0 => array('id' => 0, 'title' => 'hello'), ....)
	protected $rubricsLoaded = false;	
	protected $rubricsTable = "picfiles_topics";
	protected $table = "picfiles";
	protected $items = array();
	protected $pager = null;
	protected $pageVar = "p";
	
	public function __construct(&$rh, $fileConfig)
	{
		include($fileConfig);
		
		$this->rh = &$rh;
		$this->rh->useClass('Upload');
		$this->upload =& new Upload($this->rh, $this->rh->front_end->file_dir.$this->upload_dir.'/');		
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
		
		$this->rh->useClass('Pager');
		$this->pager = new Pager($this->rh);
		$this->pager->set(intval($this->rh->getVar($this->pageVar)), $total, 8, 5);
		
		$result = $this->rh->db->execute("
			SELECT id, title
			FROM ??".$this->table."
			WHERE ".($this->rubricId ? "topic_id =".$this->rh->db->quote($this->rubricId)." AND " : "")." _state = 0
			LIMIT ".$this->pager->getOffset().",".$this->pager->getLimit()."			
		");
		
		if ($result)
		{
            $filename = $this->_FILES['file'][0]['filename'];

            while ($r = $this->rh->db->getRow($result))
    		{
    			if ($file = $this->upload->getFile(str_replace("*", $r['id'], $filename)))
				{
					$this->items[] = array(
						'title' => $r['title'],
						'src' => $this->rh->front_end->path_rel.'files/'.$this->upload_dir.'/'.$file->name_short,
						'size' => $file->size,
						'ext' => $file->ext,
					);
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
	
	protected function getItemsCount()
	{
		$res = $this->rh->db->queryOne("
			SELECT COUNT(id) AS total
			FROM ??".$this->table."
			WHERE ".($this->rubricId ? "topic_id =".$this->rh->db->quote($this->rubricId)." AND " : "")." _state = 0
		");
		
		return intval($res['total']);
	}
	
	protected function loadRubrics()
	{
		$this->rubrics = $this->rh->db->query("
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