<?php

/**
 * nop:  Вот этот класс надо использовать для работы с деревом
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

	var $id_get_var = 'id';
	var $tree_behavior = 'explorer';//''classic

	var $EVOLUTORS = array();

	var $fields = array("id","title","_parent","_left","_right","_state","_level");
	var $pk_field = "id";

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

			$sql = "SELECT " . implode(",", $this->fields) . " FROM " . $this->rh->db_prefix . "content ORDER BY _order";
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

		$action = $_GET['action'];
		var_dump($this->rh->ri->get('add'));
		die('====');
		//die('zxcv '.var_export($_POST, true) );
		switch($action){

		case 'update':

      //    
			//				$rh->HeadersNoCache();
			if( $new_id = $this->UpdateTreeStruct() )
				$tpl->set('_new_id',$new_id);
			$tpl->set('_new_action',$this->_href_template.$this->id_get_var."=".$new_id);
			//				$tpl->Parse( $this->template_response, 'html_body', true );
			//				return $tpl->Parse( $this->template_response, 'html_body', true );
			$tpl->Parse( $this->template_response, 'HTML:body', true );
			$res = $tpl->parse('html.html');
			echo $res;
			die('1');
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

	function AddNew( $FIELDS=array() )
	{
		//aliaces
		$db =& $this->rh->db;
		$rh =& $this->rh;
		//function
		$VALUES = array();
		//base values
		for($i=0;$i<count($this->UPDATE_FIELDS);$i++){
			$_field = $this->UPDATE_FIELDS[$i];
			$VALUES[$_field] = $rh->ri->get( $this->prefix.$_field.$this->suffix.$this->new_suffix );
		}
		//manual values may be
		if(!is_array($FIELDS)) $FIELDS = array();
		$VALUES = array_merge($VALUES,$FIELDS);
		//execute
		reset($VALUES);
		$sql1 = $sql2 = "";
		foreach($VALUES as $field=>$value){
			$sql1 .= (($sql1)?",":"").$field;
			$sql2 .= (($sql2)?",":"").$db->Quote( $VALUES[$field] );
		}

		$sql = "INSERT INTO ".$this->table_name."($sql1) VALUES($sql2)";
		$id = $db->insert($sql);
		return $id;
	}

	function UpdateTreeStruct(){
		$rh =& $this->rh;
		$db =& $rh->db;
		$ids = $rh->ri->get('ids');
		if( $n = count($ids) ){
			//   ,      
			$this->loaded = false;
			//  
			$mode = $this->rh->ri->get('mode');
			$_href = $this->rh->url.'do/DualInner'.( $mode ? '/'.$mode : '' );//.'?'.$this->state->State();
			//  
			if( $rh->ri->get('add') )
			{
				$rh->ri->get('parent');
				if( $brother_id = (int)$rh->ri->get('brother') )
				{
					$rs = $db->queryOne("SELECT _parent FROM ".$this->table_name." WHERE id='$brother_id'");
					$parent_id = $rs["_parent"];
					$add_brother_mode = true;
				}
				else
					$parent_id = (int)$rh->ri->get('parent');
				//
				$new_id = $this->AddNew(array(
					'_parent'=>$parent_id,
				));
			}
			else
				//set _created,_order
				$db->query("UPDATE ".$this->table_name." SET _created=NULL,_order=id WHERE id='$new_id'");

			for($i=0;$i<$n;$i++){
				if( $children = $rh->ri->get('children_'.$ids[$i]) ){
					$chids = explode(':',$children);
					$m = count($chids);
					for($j=0;$j<$m;$j++){
						$sql = "UPDATE ".$this->table_name." SET _order='".$j."',_parent='".$ids[$i]."' WHERE id='".$chids[$j]."'";
						//						echo $sql.'<br>';
						$db->query($sql);
					}
				}
			}
			//  
			$this->Load();
			$this->Restore();
			$this->_KillOutsiders();

			if( $add_brother_mode && $new_id )
			{
				// 
				$BRS = $db->query("SELECT id,_order FROM ".$this->table_name." WHERE _parent='$parent_id' AND _state<2 ORDER BY _order ASC");
				//        $BRS = $rs->GetArray();
				//        print_r($BRS);
				// 
				$m = count($BRS);
				for( $i=0; $i<$m; $i++ )
					if( $BRS[$i]["id"]==$brother_id )
						break;
				//  -      ,   
				$i++;
				for( $j=$i; $j<$m-1; $j++ ){
					$a = $BRS[$j]["_order"];
					$BRS[$j]["_order"] = $BRS[$j+1]["_order"];
					$BRS[$j+1]["_order"] = $a;
				}
				// 
				for(;$i<$m;$i++)
					$db->query("UPDATE ".$this->table_name." SET _order='".$BRS[$i]["_order"]."' WHERE id='".$BRS[$i]["id"]."'");
				//          print("UPDATE ".$this->config->table_name." SET _order='".$BRS[$i]["_order"]."' WHERE id='".$BRS[$i]["id"]."'<br>\n");
			}
			//    ID  

			return $new_id ? $new_id : true;
		}
		return false;
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
				$display_children = true;
			else
				//    
				//   	    $display_children = !($this->config->display_limit>0 && $level[ $node->id ] >= $this->config->display_limit);
				$display_children = !(3>0 && $level[ $node->id ] >= 3);

			// -   xloadtree?
			$_is_folder = count($this->CHILDREN[$node->id]);
			$is_folder = $display_children && $_is_folder;

			//close subtrees
			if($node->_parent!=$cparent){
				for($i=0;$i<( $this->ITEMS[$cparent]['_level'] - $this->ITEMS[$node->_parent]['_level'] );$i++) $str .= "</tree>\n";
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
			$str .= str_repeat(" ",$node->_level)."<item text=\"".iconv("CP1251","UTF-8", $_title ? $_title : 'node_'.$node->id )."\" ".$action_src." id=\"".$node->id."\" db_selected=\"".( $node->id==$this->id ? "1" : "" )."\" db_state=\"".$node->_state."\" ".(($is_folder)?">":"/>")."\n";

			//			$str .= str_repeat(" ",$node->_level)."<tree text=\"text\" ".(($is_folder)?">":"/>")."\n";
			//    
			//put children
			if($is_folder){
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

}

?>

