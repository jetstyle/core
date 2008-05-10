<?php
$parent_id = isset($parent_id) ? $parent_id : 1;

//вызывать внутри Form*::Update
//меняем пути у всего поддерева

//грузим этот узел
$root = $db->queryOne("SELECT id,_left,_right,_path,_parent FROM ??".$this->config->table_name." WHERE id='".$this->id."'");
if ($root)
{
	//грузим поддерево
	$result = $db->execute("
		SELECT id, _supertag, _parent, _path
		FROM ??".$this->config->table_name."
		WHERE _left>= ".$root['_left']." AND _right <= ".$root['_right']."
	");
	
	if ($result)
	{
		$tree = array('children' => array(), 'items' => array());
		while ($r = $db->getRow($result))
		{
			$tree['children'][$r['_parent']][] = $r['id'];
			$tree['items'][$r['id']] = $r;
		}
		
		//обходим поддерево
		$STACK[] = $root['id'];
		while(count($STACK))
		{
			$id = array_pop($STACK);
			//собираем детей
			if (is_array($tree['children'][$id]))
			{
				foreach( $tree['children'][$id] as $_id )
				{
					$STACK[] = $_id;
				}
			}
			
			//модифицируем узел
			$r = $tree['items'][$id];
			
			if ($r['_parent'] == 0 && $parent_id !== 0)
			{
				$r['_path'] = '';
				$r['_supertag'] = '';
			}
			else
			{
				$r['_path'] = $tree['items'][ $r['_parent'] ]['_path'].( $r['_parent']!=$parent_id && (!$this->config->allow_empty_supertag || !empty($tree['items'][ $r['_parent'] ]['_path']) )  ? '/' : '').$r["_supertag"];
			}
			
			$db->execute("UPDATE ".$rh->db_prefix.$this->config->table_name." SET _supertag='".$r["_supertag"]."',_path='".$r['_path']."' WHERE id='".$r['id']."'");
			$tree['items'][$id] = $r;
		}
	}
}

?>