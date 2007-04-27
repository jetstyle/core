<?
$this->UseClass("TreeControl");
class TreeControlNew extends TreeControl 
{
	var $rh; //  $rh
	var $config; //    ModuleConfig
	var $loaded = false; //   ?
	
	var $state; // StateSet
	
	//templates
	var $template = "tree_control_new.html:content";
//	var $template_head = "tree_control.html:Head";
	var $template_control = "tree_control_new.html:Control";
//	var $template_response = "tree_control_new.html:Response";
	var $template_trash_show = "list_advanced.html:TrashShow";
	var $template_trash_hide = "list_advanced.html:TrashHide";
	var $store_to = "";
	var $_href_template; //   
	
	var $id_get_var = 'id';
	var $tree_behavior = 'explorer';//''classic
	
	var $EVOLUTORS = array();
	
	var $fields = array("id","title","_parent","_left","_right","_state","_level");
	var $pk_field = "id";

	var $table_name = "jetsite_content";
	
	function TreeControlNew( &$config )
    {
		parent::TreeControl(&$config);
        $this->store_to = "__tree";
	}
	
	function Handle()
    {
		$this->Load();

		$rh =& $this->rh;
		$tpl =& $rh->tpl;
		
		$action = $rh->getVar('action');
		switch($action){
			
			case 'update':
				
				/*if( $new_id = $this->UpdateTreeStruct() )
					$tpl->set('_new_id',$new_id);
				$tpl->set('_new_action',$this->_href_template.$this->id_get_var."=".$new_id);

				$res = $tpl->parse('html.html');*/
				
				$res = $this->UpdateTreeStruct();
				echo $res;
				die();
				
				echo $res;
				die('1');
			break;
			
			case 'xml':
			  	header("Content-type: text/xml; charset=".$this->xml_encoding);
				echo $this->ToXML();
				die();
				$rh->End();
			break;
			
			default:
				$tpl->set( '_href', $this->rh->path_rel."do/".$this->config->module_name."?");

				$_href = str_replace('&amp;','&',$this->_href_template);
				$tpl->set( '_url_connect', $_href.'&action=update&_show_trash='.$show_trash.'&' );
				$tpl->set( '_url_xml', $_href.'action=xml&'.$this->id_get_var.'='.$this->id.'&' );

				$tpl->set( '_behavior', $this->tree_behavior );
				$tpl->set( '_cur_id', $this->id );
				$tpl->set( '_level_limit', 3 );
				
				$tpl->Parse( $this->template_control, '__tree' );
				return $tpl->Parse( $this->template, $this->store_to, true );

			break;
		}

	}

	function ToXML()
    {
		//start XML
		$str = "<?xml version=\"1.0\" encoding=\"".$this->xml_encoding."\" ?>\n\n";
		$str .= "<tree id='0'>\n";

        $root_id = $this->getRootId();
        $root = $this->ITEMS[$root_id];

        //var_dump($this->ITEMS);
        //die();

        if (!$this->config->old_style)
        {
            $node = (object)$root;
            $str .= str_repeat(" ",$node->_level)."<item text=\"".($this->_getTitle($node->title) ? $this->_getTitle($node->title) : 'node_'.$node->id )."\" ".$this->_getAction($node->id, count($this->CHILDREN[$node->id]), true)." id=\"".$node->id."\" open=\"".( $node->id==$this->id ? "1" : "" )."\" db_state=\"".$node->_state."\" >\n";
		}
        $current = (object)$this->ITEMS[ $this->rh->getVar("id", "integer") ];
        $c_parent = (object)$this->ITEMS[ $current->_parent ];

		/* deep search */
		$stack = array();
		$cparent = $root_id;
		$level = array();
		//put root
		$arr =& $this->CHILDREN[$root_id];
		for($i=count($arr)-1;$i>=0;$i--) $stack[] = $arr[$i];
		//main loop
		while(count($stack))
        {
			$node = (object)$this->ITEMS[array_pop($stack)];
            $level[ $node->id ] = $level[ $node->_parent] + 1;
            if( $node->_left<=$c_parent->_left && $node->_right>=$c_parent->_right )
                $display_children = true;
            else
       	        $display_children = !(3>0 && $level[ $node->id ] >= 3);

      // -   xloadtree?
            $_is_folder = count($this->CHILDREN[$node->id]);
			$is_folder = $display_children && $_is_folder;

			//close subtrees
			if($node->_parent!=$cparent)
            {
				for($i=0;$i<( $this->ITEMS[$cparent]['_level'] - $this->ITEMS[$node->_parent]['_level'] );$i++) $str .= "</item>\n";
				$cparent = $node->_parent;
			}
			//write node
            //action or src?
     	    $action_src = "action=\"".$this->_href_template.$this->id_get_var."=".$node->id."\"";
            if( $_is_folder && !$display_children )
              	$action_src .= " src=\"".$this->_href_template."mode=tree&amp;action=xml&amp;display_root=".$node->id."\"";

            $_title = $this->_getTitle($node->title);
			$str .= str_repeat(" ",$node->_level)."<item text=\"".($_title ? $_title : 'node_'.$node->id )."\" ".$action_src." id=\"".$node->id."\" ".( $node->id==$this->id ? "select='true'" : "" )." db_state=\"".$node->_state."\" ".(($is_folder)?">":"/>")."\n";

			//put children
			if($is_folder){
				$arr = $this->CHILDREN[$node->id];
				for($i=count($arr)-1;$i>=0;$i--) $stack[] = $arr[$i];
				$cparent = $node->id;
			}
		}
		for( $i=(integer)$root["_level"] ; $i<$this->ITEMS[$cparent]['_level']; $i++ ) $str .= "</item>\n";
		
		//end XML
        if (!$this->config->old_style)
            $str .= "</item>\n";
		$str .= "</tree>\n";
        
		return $str;
	}
	
	
	function UpdateTreeStruct(){
		$rh =& $this->rh;
		$db =& $rh->db;

		if( $rh->getVar('add') )
		{
			$parent = intval($rh->getVar('parent'));
			$id = $db->insert("
				INSERT INTO ". $this->config->table_name ."
				(title, _parent)
				VALUES
				('new', '".$parent."')
			");
			
			$this->loaded = false;
			$this->Load();
			$this->Restore();
			return $id;
		}
		elseif($delete = intval($rh->getVar('delete')))	
		{
			
			$res = $db->queryOne("
				SELECT _left, _right
				FROM ". $this->config->table_name ."
				WHERE id = '".$delete."'
			");

			if(is_array($res) && !empty($res))	{
				$db->query("
					UPDATE ". $this->config->table_name ."
					SET _state = 2
					WHERE _left >= ".$res['_left']." AND _right <= ".$res['_right']."
				");
			}
			$this->loaded = false;
			$this->Load();
			$this->Restore();
			return '1';
		}
		elseif($rh->ri->get('change'))	
		{
			$itemId = intval($rh->getVar('id'));
			$targetId = intval($rh->getVar('target'));
			$beforeId = intval($rh->getVar('before'));

			$db->query("
				UPDATE ". $this->config->table_name ."
				SET _parent = '".$targetId."'
				WHERE id = '".$itemId."'
			");

			if($beforeId)	{

				$node = $db->queryOne("
					SELECT _parent, _order
					FROM ". $this->config->table_name ."
					WHERE id = '".$beforeId."'
				");

				$db->query("
					UPDATE ". $this->config->table_name ."
					SET _order = _order + 1
					WHERE _order >= " . $node['_order'] . " AND _parent = '" . $node['_parent'] . "'
				");

				$db->query("
					UPDATE ". $this->config->table_name ."
					SET _order = " . $node['_order'] . "
					WHERE id = " . $itemId  . "
				");
			}
			
			$this->loaded = false;
			$this->Load();
			$this->Restore();

			return '1';
		}
		return '0';
	}
	

}
	
?>