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

	var $fields = array("id","title_short AS title","_parent","_left","_right","_state","_level");
	var $pk_field = "id";

	var $table_name = "jetsite_content";

	function TreeControlNew( &$config )
	{
		parent::TreeControl(&$config);
		$this->store_to = "__tree";
		$this->_href_to_module = $this->rh->path_rel."do/".$config->module_name;//."?".$this->rh->state->State();
	}

	function Handle()
	{
		$this->Load();

		$rh =& $this->rh;
		$tpl =& $rh->tpl;

		$action = $rh->getVar('action');
		switch($action)
		{
		case 'update':
			$title = $rh->getVar('title');
			$id    = $rh->getVar('itemId');

			if (!empty($title))
			{
				$res = $this->saveTitle($id, $title);
			}
			else
			{    
				$res = $this->UpdateTreeStruct();
			}
			echo $res;
			die();
			break;

		case 'xml':
			header("Content-type: text/xml; charset=".$this->xml_encoding);
			echo $this->ToXML();
			die();
			$rh->End();
			break;

		default:
			$show_trash = $this->rh->state->Get('_show_trash');

			//$this->_href_template = $this->rh->path_rel."do/".$this->config->module_name."?";
			$rh->state->free('_show_trash');
			$tpl->set( '_href', $this->_href_to_module.'?'.$rh->state->state().(!$show_trash ? '_show_trash='.!$show_trash : '' ) );
			$tpl->Parse( $show_trash ? $this->template_trash_hide : $this->template_trash_show, '__trash_switch' );



			$tpl->set( '_href', $this->_href_to_module.'?' );
			$this->_href_actions = $this->rh->path_rel."do/".$this->config->module_name."/tree?";
			$_href = str_replace('&amp;','&',$this->_href_actions);

			$tpl->set( '_url_connect', $_href.'&action=update&_show_trash='.$show_trash.'&' );
			$tpl->set( '_url_xml', $_href."action=xml&".$this->id_get_var.'='.$this->id.'&_show_trash='.$show_trash.'&' );

			$tpl->set( '_behavior', $this->tree_behavior );
			$tpl->set( '_cur_id', $this->id );
			$tpl->set( '_level_limit', 3 );

				/*
				$xml_string = $this->toXML();
					 $xml_string = str_replace('"', "'", $xml_string);
				$tpl->set( 'xml_string', $xml_string );
				 */
			if ($_COOKIE['tree_control_btns'] == 'true')
				$tpl->set("toggleEditTreeClass", "class='toggleEditTreeClass-Sel'");
			$tpl->Parse( $this->template_control, '__tree' );

			return $tpl->Parse( $this->template, $this->store_to, true );

			break;
		}

	}

	function saveTitle($id, $title)
	{
		$title = iconv("UTF-8", "CP1251", $title);

		$sql = "UPDATE ".$this->table_name." SET title_short=".$this->rh->db->quote($title)." WHERE id=".$this->rh->db->quote($id);   
		$this->rh->db->execute($sql);
		return $sql;
	}

	function ToXML()
	{
		//start XML
		$str = "<?xml version=\"1.0\" encoding=\"".$this->xml_encoding."\" ?>\n\n";
		$str .= $this->xmlOpenTag('tree', array('id', 0))."\n";

		$root_id = $this->getRootId();
		$root = $this->ITEMS[$root_id];

		//var_dump($this->ITEMS);
		//die();
		$current = (object)$this->ITEMS[ $this->rh->getVar("id", "integer") ];
		$c_parent = (object)$this->ITEMS[ $current->_parent ];

		$c = $this->ITEMS[ $this->rh->getVar("id", "integer") ];
		do
		{
			$this->to_root[] = $c['id'];
			$c = $this->ITEMS[$c['_parent']] ;
		} while($c);


		if (!$this->config->old_style)
		{
			$node = (object)$root;
			//$str .= str_repeat(" ",$node->_level)."<item text=\"".($this->_getTitle($node))."\" ".$this->_getAction($node->id, count($this->CHILDREN[$node->id]), true)." id=\"".$node->id."\" open='1' db_state=\"".$node->_state."\" >\n";
			$arr = array(
					'text',		 $this->_getTitle($node),
					'id',			 $node->id,
					'open',		 1,
					'db_state',	 $node->_state,
				);
			if($current->id == 1)
			{
				array_push($arr, 'select', 'true');
			}
			$xml_attrs =  array_merge(
				$arr,
				$this->_getAction($node->id, count($this->CHILDREN[$node->id]), true)
			);
			$str .= str_repeat(" ",$node->_level) . $this->xmlOpenTag('item', $xml_attrs)."\n";
		}

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
				for($i=0;$i<( $this->ITEMS[$cparent]['_level'] - $this->ITEMS[$node->_parent]['_level'] );$i++) $str .= $this->xmlCloseTag('item')."\n";
				$cparent = $node->_parent;
			}
			//write node
			//action or src?
			$title = $this->_getTitle($node);
			//$str .= str_repeat(" ",$node->_level)."<item text=\"".($title)."\" ".$this->_getOpen($node)." id=\"".$node->id."\" ".( $node->id==$this->id ? "select='true'" : "" )." db_state=\"".$node->_state."\" ".(($is_folder)?">":"/>")."\n";
			$xml_attrs = array(
					array('text',			$title),
					array("id",				$node->id),
					array('db_state',		$node->_state),
					array('style',		$node->_state == 1 ? 'background-color: pink;' : ''),
			);
			if ($t = $this->_getOpen($node)) 
				$xml_attrs[] = array('open', $t);
			if ($node->id == $this->id) 
				$xml_attrs[] = array('select', 'true');

			$str .= str_repeat(" ",$node->_level)
				.( $is_folder 
					 ? $this->xmlOpenTag('item', $xml_attrs) 
					 : $this->xmlTag('item', $xml_attrs) 
				 )
				."\n";

			//put children
			if($is_folder)
			{
				$arr = $this->CHILDREN[$node->id];
				for($i=count($arr)-1;$i>=0;$i--) $stack[] = $arr[$i];
				$cparent = $node->id;
			}
		}
		for( $i=(integer)$root["_level"] ; $i<$this->ITEMS[$cparent]['_level']; $i++ ) $str .= "</item>\n";

		//end XML
		if (!$this->config->old_style)
			$str .= $this->xmlCloseTag('item')."\n";
		$str .= $this->xmlCloseTag('tree')."\n";

		return $str;
	}

	function _getOpen(&$node)
	{
		$ret = "";
		if (@in_array($node->id, $this->to_root))
			//$ret = " open='1' ";
			$ret = 1;

		return $ret;
	}

	function UpdateTreeStruct()
	{
		$rh =& $this->rh;
		$db =& $rh->db;

		if( $rh->getVar('add') )
		{
			$parent = intval($rh->getVar('parent'));
			$id = $db->insert("
				INSERT INTO ". $this->config->table_name ."
				(title, title_short, _parent)
				VALUES
				('new', 'new', '".$parent."')
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
		elseif($rh->getVar('change'))	
		{
			$itemId = intval($rh->getVar('id'));
			$this->id = $itemId;
			$targetId = intval($rh->getVar('target'));
			$beforeId = intval($rh->getVar('before'));

			$db->query("
				UPDATE ". $this->config->table_name ."
				SET _parent = '".$targetId."'
				WHERE id = '".$itemId."'
				");

			if($beforeId)
			{
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
			
			$this->table_name = $this->config->table_name;
			
			include( $rh->FindScript('handlers','_update_tree_pathes') );
			
			return '1';
		}
		return '0';
	}


}

?>