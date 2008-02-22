<?php

/**
 * nop:          
 *
 */


class TreeControlTrue {

	var $rh; //  $rh
	var $config; //    ModuleConfig
	var $loaded = false; //   ?

	var $state; // StateSet

	//templates
	var $template = "tree_control.html:content";
	var $template_head = "tree_control.html:Head";
	var $template_control = "tree_control.html:Control";
	var $template_response = "tree_control.html:Response";
	var $template_trash_show = "list_advanced.html:TrashShow";
	var $template_trash_hide = "list_advanced.html:TrashHide";
	var $store_to = "";
	var $_href_template; //   

	var $level_limit = 3;

	var $id_get_var = 'id';
	var $tree_behavior = 'explorer';//''classic

	var $EVOLUTORS = array();

	var $fields = array("id","title","_parent","_left","_right","_state","_level");
	var $pk_field = "id";

  var $update_path = true;
	var $table_name = "jetsite_content";

	function TreeControlTrue( &$rh ){
		//base modules binds
		$this->rh =& $rh;
		$this->config =& $rh->config;

		$this->prefix = $this->config->module_name.'_tree_';
		$this->result_mode = 2;

		$this->store_to = "content";

		//StateSet
		//		$this->state =& new StateSet($this->rh);
		//		$this->state->Set($this->rh->state);
		//  
		$this->id = (int)$this->rh->ri->get('id');
		//   
		//		$this->rh->state->Keep('_show_trash');
		// 
		$this->store_to = "__tree";
		$this->_href_template = $this->rh->path_rel."DualInner?";//."?".$this->rh->state->State();
	}

	function Load(){
		//load data
		if( !$this->loaded ){
			//  
			//			$cache =& $this->rh->cache;
			$object_class = 'trees';

			// lunatic: replace table_name 
			//$sql = "SELECT " . implode(",", $this->fields) . " FROM " . $this->rh->db_prefix . "content ORDER BY _order";
			$sql = "SELECT " . implode(",", $this->fields) . " FROM " . $this->table_name . " ORDER BY _order";
			
			$res = $this->rh->db->query($sql);

			foreach ($res as $r)
			{
				$this->ITEMS[ $r[$this->pk_field] ] = $r;
				$this->CHILDREN[ (integer)$r['_parent'] ][] = $r[$this->pk_field];
			}

			//			DBDataEditTree::Load( $this->rh->state->Get('_show_trash') ? '' : '_state<>2' );

			$this->loaded = true;
		}
	}

	function Handle(){

		$this->Load();

		$rh =& $this->rh;
		$tpl =& $rh->tpl;

		$action = $this->rh->ri->get('action');
		switch($action){

			case 'update':
				$title = $rh->ri->get('title');
				$id    = $rh->ri->get('itemId');
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
			// XML  xloadtree  
			//    ,   -   .
			//$rh->HeadersNoCache();
			ob_end_clean();
			header("Content-type: text/xml; charset=utf-8");//windows-1251
			echo $this->ToXML();
			die();
			$rh->End();
			break;

		default:
			//  
			$this->_Handle();
			//     
			//render trash switcher

			$tpl->set( '_href', $this->_href_template );

			$_href = str_replace('&amp;','&',$this->_href_template);
			$tpl->set( '_url_connect', $_href.'&action=update&_show_trash='.$show_trash.'&' );
			//				$_config_name = $this->config->PASSED[ count($this->config->PASSED) - 1 ];
			$tpl->set( '_url_xml', $_href.'&action=xml&'.$this->id_get_var.'='.$this->id.'&' );
			$tpl->set( '_behavior', $this->tree_behavior );
			$tpl->set( '_cur_id', $this->id );
			$tpl->set( '_level_limit', 3 );



			$tpl->Parse( $this->template_head, 'html_head', true );
			$tpl->Parse( $this->template_control, '__tree' );

			return $tpl->Parse( $this->template, $this->store_to, true );


/*				$show_trash = $this->rh->state->Get('_show_trash');
				$tpl->Assign( '_href', $this->_href_template.'&_show_trash='.(!$show_trash) );
				$tpl->Parse( $show_trash ? $this->template_trash_hide : $this->template_trash_show, '__trash_switch' );
				//   
				$this->rh->logs->ParseLink( $this->config->module_name, 0, '__logs' );
				// 
				$_href = str_replace('&amp;','&',$this->_href_template);
				$tpl->Assign( '_url_connect', $_href.'mode='.$this->config->GetPassed().'&action=update&_show_trash='.$show_trash.'&' );
				$_config_name = $this->config->PASSED[ count($this->config->PASSED) - 1 ];
				$tpl->Assign( '_url_xml', $_href.'mode='.$_config_name.'&action=xml&_show_trash='.$show_trash.'&'.$this->id_get_var.'='.$this->id.'&' );
				$tpl->Assign( '_behavior', $this->tree_behavior );
				$tpl->Assign( '_cur_id', $this->id );
				$tpl->Assign( '_level_limit', $this->config->level_limit  ? $this->config->level_limit : 100 );
				$tpl->Parse( $this->template_head, 'html_head', true );
				$tpl->Parse( $this->template_control, '__tree' );
				$tpl->Parse( $this->template, $this->store_to, true );
 */
			break;
		}

	}

	function _Handle(){}

  function UpdateTreeStruct()
	{
		$rh =& $this->rh;
		$db =& $rh->db;

		if( $rh->ri->get('add') )
		{
			$id = $this->addNode();

			$this->loaded = false;
			$this->Load();
			$this->Restore();
			return $id;
		}
		elseif($delete = intval($rh->ri->get('delete')))
		{
			$node = $this->deleteNode($delete);

			if($node['id'])
			{
			$this->loaded = false;
			$this->Load();
			$this->Restore();
			return '1';
		}
			else
			{
				return '0';
			}
		}
		elseif($rh->ri->get('change'))
		{
			$itemId = intval($rh->ri->get('id'));
			$this->id = $itemId;
			$targetId = intval($rh->ri->get('target'));
			$beforeId = intval($rh->ri->get('before'));

			if($beforeId)
			{
        $sql1 = "
					SELECT _parent, _order as _order
					FROM ". $this->table_name ."
					WHERE id = '".$beforeId."'
					"     ;
				$node = $db->queryOne($sql1);

        $sql2 = "
					UPDATE ". $this->table_name ."
					SET _order = _order + 1
					WHERE _order >= " . $node['_order'] . " AND _parent = '" . $node['_parent'] . "'
					"     ;
				$db->query($sql2);
			}
			else
			{
				$node = $db->queryOne("
					SELECT (MAX(_order) + 1) AS _order
					FROM ". $this->table_name ."
					WHERE _parent = '".$targetId."'
				");
			}

      $sql = "UPDATE ". $this->table_name ."
      				SET _order = " . intval($node['_order']) . ", _parent = '".$targetId."'
			       	WHERE id = " . $itemId;
			       	

			$db->query($sql);

			$this->loaded = false;
			$this->Load();
			$this->Restore();

			$this->table_name = $this->table_name;

      if ($this->update_path)
  			include( $rh->FindScript('handlers','_update_tree_pathes') );

			return '1';
		}
		return '0';
	}
	
	function ToXML(){  //$iconv=true
		//start XML
		$str = "<?xml version=\"1.0\"?>\n\n";
		$str .= "<tree id=\"0\" >\n";

		//  ?
		$root_id = (int)$this->rh->ri->get("display_root");
		$root = $this->ITEMS[$root_id];

		//   
		$current = (object)$this->ITEMS[ (int)$this->rh->ri->get("id") ];
		$c_parent = (object)$this->ITEMS[ $current->_parent ];

		/* deep search */
		$stack = array();
		$cparent = $root_id;
		$level = array();
		//put root
		$arr =& $this->CHILDREN[$root_id];
		for($i=count($arr)-1;$i>=0;$i--) $stack[] = $arr[$i];
		//main loop
		while(count($stack)){
			$node = (object)$this->ITEMS[array_pop($stack)];
			$level[ $node->id ] = $level[ $node->_parent] + 1;

			//   ?
			//    ?
			if( $node->_left<=$c_parent->_left && $node->_right>=$c_parent->_right )
			{
				$display_children = true;
			}
			else
			{
				//    
				//   	    $display_children = !($this->config->display_limit>0 && $level[ $node->id ] >= $this->config->display_limit);
				$display_children = !($level[ $node->id ] >= $this->level_limit);
			}

			// -   xloadtree?
			$_is_folder = count($this->CHILDREN[$node->id]);
			$is_folder = $display_children && $_is_folder;

			//close subtrees
			if($node->_parent!=$cparent){
				for($i=0;$i<( $this->ITEMS[$cparent]['_level'] - $this->ITEMS[$node->_parent]['_level'] );$i++) $str .= "</item>\n";
				$cparent = $node->_parent;
			}
			//write node
			//action or src?
			$action_src = "action=\"".$this->_href_template.$this->id_get_var."=".$node->id."\"";
			if( $_is_folder && !$display_children )
				$action_src .= " src=\"".$this->_href_template."mode=tree&amp;action=xml&amp;display_root=".$node->id."\"";
			//    
			$_title = preg_replace( "/<.*?>/is", '', $node->title);
			//  
			$_title = str_replace('"','\'',$_title);
			//    utf
			$_title = $_title ? $_title : 'node_'.$node->id ;
			//$_title = iconv("CP1251","UTF-8", $_title);
			
			$str .= str_repeat(" ",$node->_level)."<item text=\"".$_title."\" ".$action_src." id=\"".$node->id."\" db_selected=\"".( $node->id==$this->id ? "1" : "" )."\" db_state=\"".$node->_state."\" ".(($is_folder)?">":"/>")."\n";
			//$str .= str_repeat(" ",$node->_level)."<tree text=\"text\" ".(($is_folder)?">":"/>")."\n";
			//
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
		$str .= "</tree>\n";

		return $str;
	}

  /*
		  ,      , 
		 _parent.
		->Load();
	*/
	function _KillOutsiders(){
		//  ,    
		$S[] = 0;
		while(count($S)){
			$id = array_pop($S);
			if(is_array($this->CHILDREN[$id]))
				$S = array_merge($S,$this->CHILDREN[$id]);
			$IDS[] = $id;
		}
		//    
		$where = "_state<2 AND id<>'".implode("' AND id<>'",$IDS)."'";
		$rh =& $this->rh;
		$db =& $rh->db;
		//    $this->rh->db->execute("UPDATE ".$this->table_name." SET _state=2 WHERE id<>'".implode("' AND id<>'",$IDS)."'");
		//    mail('zharinov@jetstyle.ru','tree sql',"UPDATE ".$this->table_name." SET _state=2 WHERE id<>'".implode("' AND id<>'",$IDS)."'");
		//    ,   
		$db->query("UPDATE ".$this->table_name." SET _parent=0,_left=-1,_right=-1 WHERE ".$where);
		//   
		$TO_KILL = $db->query("SELECT id,title FROM ".$this->table_name." WHERE ".$where);
		//    $TO_KILL = $rs->GetArray();
		foreach($TO_KILL as $r){
			//      $rh->logs->Put( ' :  ', $r['id'], $this->config->module_title, $r['title'], $this->_redirect.'&_show_trash=1' );
			//	  	$rh->trash->Delete( $this->config->table_name, $r['id'], $this->config->module_title, $r['title'], $rh->path_rel.'?'.str_replace('&amp;','&',$this->state->StateAll()).'&id='.$r['id'] );
			$db->query("DELETE FROM " . $this->table_name . " WHERE id=" . $r['id']);
		}
	}

	function Restore( $parent_id=0, $left=0 ) {

		//shortcuts
		$node =& $this->ITEMS[ $parent_id ];

		//_level
		if($node['id'])
			$node['_level'] = $this->ITEMS[ $node['_parent'] ]['_level'] + 1;

		/* Taken from http://www.sitepoint.com/article/1105/3 */

		// the right value of this node is the left value + 1
		$right = $left + 1;

		// get all children of this node
		$A =& $this->CHILDREN[$parent_id];
		$n = count($A); 
		for($i=0;$i<$n;$i++){
			// recursive execution of this function for each
			// child of this node
			// $right is the current right value, which is
			// incremented by the rebuild_tree function
			$right = $this->Restore( $A[$i], $right);
		}

		// we've got the left value, and now that we've processed
		// the children of this node we also know the right value
		$node['_left'] = $left;
		$node['_right'] = $right;

		//store in DB
		//    print("UPDATE ".$this->table_name." SET _level='".$node['_level']."', _left='".$node['_left']."', _right='".$node['_right']."' WHERE id='".$node['id']."'<br>\n");
		$this->rh->db->query("UPDATE ".$this->table_name." SET _level='".$node['_level']."', _left='".$node['_left']."', _right='".$node['_right']."' WHERE id='".$node['id']."'");

		// return the right value of this node + 1
		return $right + 1;
	}

  function addNode()
	{
		$rh =& $this->rh;
		$db =& $rh->db;

		//$rh->UseClass('Translit');
  		//$translit =& new Translit();

  		$node = array();

    $title = iconv("UTF-8", "CP1251", $rh->ri->get('newtitle'));
		$node['title'] = $title;
		if(strlen($node['title']) == 0)
		{
			$node['title'] = 'new';
		}
    
    $pre = $this->rh->tpl->action('typografica', $title);
 		$node['title_pre'] = $pre;
		$node['parent'] = intval($rh->ri->get('parent'));
		//$node['supertag'] = $translit->TranslateLink($node['title'], 100);

		$parentNode = $db->queryOne("
			SELECT _path
			FROM ". $this->table_name ."
			WHERE id = '".$node['parent']."'
		");

		$node['_path'] = $parentNode['_path'] ? $parentNode['_path'].'/'.$node['supertag'] : $node['supertag'];
		$order = $this->_getOrder($node['parent']);

		$sql = "INSERT INTO ". $this->table_name ."
      			(title, title_pre, _parent, _supertag, _path, _order)
		        VALUES (".$db->quote($node['title']).", ".$db->quote($node['title_pre']).", ".$db->quote( $node['parent'] ).", ".$db->quote($node['supertag']).", ".$db->quote($node['_path']).", ".$db->quote($order['_max']).")";
    //die($sql);
		$id = $db->insert($sql);

		return $id;
	}

	function deleteNode($node_id)
	{
		$rh =& $this->rh;
		$db =& $rh->db;

		$node = $db->queryOne("
			SELECT id, _left, _right, _state
			FROM ". $this->table_name ."
			WHERE id = '".$node_id."'
			");

		if(is_array($node) && !empty($node))
		{
			//  
			if($node['_state'] == 2)
			{
				$db->query("
					DELETE FROM ". $this->table_name ."
					WHERE _left >= ".$node['_left']." AND _right <= ".$node['_right']."
				");
			}
			// 
			else
			{
				$db->query("
					UPDATE ". $this->table_name ."
					SET _state = 2
					WHERE _left >= ".$node['_left']." AND _right <= ".$node['_right']."
				");
			}
		}

		return $node;
	}


	
  /**
   *   _order 
   */
	function _getOrder($parent)
	{
  	 $order = $this->rh->db->queryOne("
  			SELECT (MAX(_order) + 1) AS _max
  			FROM ". $this->table_name ."
  			WHERE _parent = ".$this->rh->db->quote($parent)."
  		");

  		return $order['_max'];
	}
	

    /**
     *    
     */
  	function saveTitle($id, $title)
  	{
  		$title = iconv("UTF-8", "CP1251", $title);

  		$sql = "UPDATE ".$this->table_name." SET title_short=".$this->rh->db->quote($title)." WHERE id=".$this->rh->db->quote($id);
  		$this->rh->db->execute($sql);
  		return $sql;
  	}
}

?>
