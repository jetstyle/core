<?
		//�������� ������ Form*::Update
		//������ ���� � ����� ���������
		
		//������ ���� ����
		$rs = $db->execute("SELECT id,_left,_right,_path,_parent FROM ".$this->table_name." WHERE id='".$this->id."'");
		$root = (object)$rs->fields;
		
		//������ ���������
		$tree =& new DBDataView( $rh, 
			$this->table_name, 
			array('id','_supertag','_parent','_path'), 
			$root->id ? "_left>='".$root->_left."' AND _right<='".$root->_right."' OR id='".$root->_parent."'" : ""
		);
		$tree->result_mode = 2;
		$tree->Load();

		//������� ���������
		$STACK[] = (integer)$root->id;//$this->id;
		while(count($STACK)){
			$id = array_pop($STACK);
			//�������� �����
			if(is_array($tree->CHILDREN[$id]))
				foreach( $tree->CHILDREN[$id] as $_id )
					$STACK[] = $_id;
			//������������ ����
			$r = $tree->ITEMS[$id];
			$r['_path'] = $tree->ITEMS[ $r['_parent'] ]['_path'].( $r['_parent'] ? '/' : '').$r["_supertag"];
			$db->execute("UPDATE ".$this->table_name." SET _supertag='".$r["_supertag"]."',_path='".$r['_path']."' WHERE id='".$r['id']."'");            
			$tree->ITEMS[$id] = $r;
		}

?>