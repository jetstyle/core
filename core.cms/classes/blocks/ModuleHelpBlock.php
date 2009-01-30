<?php
Finder::useClass('blocks/Block');
class ModuleHelpBlock extends Block
{	
	private $parents = array();
	private $node = array();
	private $totalNodes = 0;
	
	protected function constructData()
	{
		$data = array();
		if (Config::get('db_disable'))
		{
			$this->setData($data);
			return;
		}
		
		$tag = $this->getTplParam('tag');
		if ($tag)
		{
			$db = &Locator::get('db');
			try
			{
				$this->node = $db->queryOne("
					SELECT id, text_pre AS text, _left, _right, module_href
					FROM ??help_texts 
					WHERE module_href = ".$db->quote($tag)." AND _state = 0
				");
				
				if (!$this->node['id'])
				{
					$slashPos = strrpos($tag, '/');
					if ($slashPos)
					{
						$tag = substr($tag, 0, $slashPos);
						$this->node = $db->queryOne("
							SELECT id, text_pre AS text, _left, _right, module_href
							FROM ??help_texts 
							WHERE module_href = ".$db->quote($tag)." AND _state = 0
						");
					}
				}
				
				if ($this->node['id'])
				{
					$parentsResult = $db->execute("
						SELECT id, _left, _right 
						FROM ??help_texts 
						WHERE _left <= ".$db->quote($this->node['_left'])." AND _right >= ".$db->quote($this->node['_right'])." AND _state = 0
						ORDER BY _level ASC
					");
					
					$parent = null;
					
					while ($r = $db->getRow($parentsResult))
					{
						if (null == $parent)
						{
							$parent = $r;
						}
						$this->parents[$r['id']] = $r['id'];
					}
					
					Finder::useModel('DBModel');
					$model = new DBModel();
					$model->setTable('help_texts');
					$model->setFields(array('id', 'title' => 'title_pre', 'module_href', '_parent', '_level'));
					$model->where = '{_left} >= '.$db->quote($parent['_left']).' AND {_right} <= '.$db->quote($parent['_right']).' AND  {_state} = 0';
					$model->setOrder(array('_level' => 'ASC', '_order' => 'ASC'));
					$model->registerObserver('row', array($this, 'onRow'));
					$model->loadTree();
					
					if ($this->totalNodes > 1)
					{
						$data['menu'] = $model->getArray();
					}
					$data['node'] = $this->node;
					$data['help_url'] = Config::get('base_url').'do/Help/form';
				}
			}
			catch(DBException $e)
			{
				//
			}
		}
		
		$this->setData($data);
	}
	
	public function onRow(&$model, &$row)
	{
		$this->totalNodes++;
		if ($row['id'] == $this->node['id'])
		{
			$row['current'] = true;
		}
		elseif (array_key_exists($row['id'], $this->parents))
		{
			$row['selected'] = true;
		}
	}
	
}
?>