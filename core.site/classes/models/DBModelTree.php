<?php


class DBModelTree extends DBModel
{

    public function &load($where=NULL, $limit=NULL, $offset=NULL)
    {
        $this->treeMinLevel = null;
        $this->treeRootId = null;

    	/**
    	 * we need to aggregate data by primary key
    	 */
    	if (!$this->keyField)
    	{
    		$this->setKeyField($this->getPk());
    	}
    	$this->registerObserver('row', array($this, 'treePrepareRow'));
    	$this->registerObserver('did_load', array($this, 'treeConstruct'));

    	parent::load($where);

    	$this->removeObserver('row', array($this, 'treePrepareRow'));
    	$this->removeObserver('did_load', array($this, 'treeConstruct'));

    	return $this;
    }

    public function insert(&$node)
    {
        $db = Locator::get('db');

        Finder::useClass('Translit');
        $translit = new Translit();

        if (!$node['title']) 
            $node['title'] = iconv("UTF-8", "CP1251", $_REQUEST['newtitle']);
            
        if(strlen($node['title']) == 0)
        {
                $node['title'] = 'Новый узел';
        }

        $node['title_pre'] = $this->tpl->action('typografica', $node['title']);
        
        if (!$node['parent']) $node['parent'] = intval($_REQUEST['parent']);
        if (!$node['before']) $node['before'] = intval($_REQUEST['before']);

        $node['supertag'] = $translit->supertag($node['title'], 20);

        $parentNode = $db->queryOne("
                SELECT _path
                FROM ??". $this->config['table'] ."
                WHERE id = '".$node['parent']."'
        ");

        $node['_path'] = $parentNode['_path'] ? $parentNode['_path'].'/'.$node['supertag'] : $node['supertag'];

        $order = null;

        if($node['before'])
        {
                $beforeNode = $db->queryOne("
                        SELECT _parent, _order
                        FROM ??". $this->config['table'] ."
                        WHERE ".$this->idField." = '".$node['before']."'
                ");

                if (is_array($beforeNode) && is_numeric($beforeNode['_order']))
                {
                        $db->query("
                                UPDATE ??". $this->config['table'] ."
                                SET _order = _order + 1
                                WHERE _order >= " . $db->quote($beforeNode['_order']) . " AND _parent = '" . $beforeNode['_parent'] . "'
                        ");

                        $order = $beforeNode['_order'];
                }
        }

        if (!is_numeric($order))
        {
                $order = $db->queryOne("
                        SELECT (MAX(_order) + 1) AS _max
                        FROM ??". $this->config['table'] ."
                        WHERE _parent = '".$node['parent']."'
                ");

                $order = intval($order['_max']);
        }

        if (isset($this->config['insert']) && is_array($this->config['insert']))
        {
                foreach ($this->config['insert'] AS $fieldName => $fieldValue)
                {
                        $additionFields .= ','.$fieldName;
                        $additionValues .= ','.$db->quote($fieldValue);
                }
        }

        $id = $db->insert("
                INSERT INTO ". DBAL::$prefix.$this->config['table'] ."
                (title, title_pre, _parent, _supertag, _path, _order, _state " . $additionFields . ")
                VALUES
                (".$this->db->quote($node['title']).", ".$db->quote($node['title_pre']).", ".$db->quote($node['parent']).", ".$db->quote($node['supertag']).", ".$db->quote($node['_path']).", ".$db->quote($order).", 1 ".$additionValues.")
        ");

        return $id;
    }


	function delete($where)//$nodeId
	{
		$db = &$this->db;

		$node = $db->queryOne("
			SELECT id, _left, _right, _state
			FROM ??". $this->config['table'] ."
			WHERE ".$where );

		if (is_array($node) && !empty($node))
		{
			// удаляем совсем
			if ($node['_state'] == 2)
			{
				$db->query("
					DELETE FROM ??". $this->config['table'] ."
					WHERE _left >= ".$node['_left']." AND _right <= ".$node['_right']." ".($this->config['where'] ? " AND ".$this->config['where'] : "")."
				");
			}
			// метим
			else
			{
				$db->query("
					UPDATE ??". $this->config['table'] ."
					SET _state = 2
					WHERE _left >= ".$node['_left']." AND _right <= ".$node['_right']." ".($this->config['where'] ? " AND ".$this->config['where'] : "")."
				");
			}
		}

		return $node;
	}

        public function getItems()
        {
            return $this->items;
        }
        
        public function getChildren()
        {
            return $this->children;
        }
}

?>