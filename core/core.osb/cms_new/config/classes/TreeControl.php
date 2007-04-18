<?

$this->UseClass("DBDataEditTree");

class TreeControl extends DBDataEditTree  {

	var $rh; //������ �� $rh
	var $config; //������ �� ������ ������ ModuleConfig
	var $loaded = false; //������� ��� ��� ������?

	var $state; //������������ StateSet

	//templates
	var $template = "tree_control.html";
	var $template_head = "tree_control.html:Head";
	var $template_control = "tree_control.html:Control";
	var $template_response = "tree_control.html:Response";
	var $template_trash_show = "list_advanced.html:TrashShow";
	var $template_trash_hide = "list_advanced.html:TrashHide";
	var $store_to = "";
	var $_href_template; //������ ��� ������������ ������

	var $id_get_var = 'id';
	var $tree_behavior = 'explorer';//''classic

	var $EVOLUTORS = array();


	function TreeControl( &$config ){
		//base modules binds
		$this->config =& $config;
		//DBData
		//		$config->Read('tree');
		$config->SELECT_FIELDS = array_merge( $config->SELECT_FIELDS, array('_parent','_state','_left','_right') );
		DBDataEditTree::DBDataEditTree( $config->rh, $config->table_name, $config->SELECT_FIELDS, $config->where );
		$this->prefix = $config->module_name.'_tree_';
		$this->result_mode = 2;
		//StateSet
		$this->state =& new StateSet($this->rh);
		$this->state->Set($this->rh->state);
		//��� ������������ ��������
		$this->id = $this->rh->state->keep('id','integer');
		//���������� ������ �� �������
		$this->rh->state->Keep('_show_trash');
		//��������� ��������
		$this->store_to = "tree_".$config->module_name;
		$this->_href_template = $this->rh->path_rel."do/".$config->module_name."?".$this->rh->state->State();
	}

	//��������� � TreeSimple::Load(). 
	//������ ������ ������ � ��� ��� �����������?
	function Load(){
		//load data
		if( !$this->loaded ){
			//��������� � ����
			$cache =& $this->rh->cache;
			$object_class = 'trees';
			//������� �� ����
/*			if( !($this->ITEMS =& $cache->Restore( $object_class, $this->config->table_name.'_ITEMS' )) || 
!($this->CHILDREN =& $cache->Restore( $object_class, $this->config->table_name.'_CHILDREN' )) ){*/
			//�� ���������� ����� ������, ��� ����� ��������� ����������-��� �������
			//������, ���� ���
			DBDataEditTree::Load( $this->rh->state->Get('_show_trash') ? '' : '_state<>2' );
			//����� � ���
			//				$cache->Store( $object_class, $this->config->table_name.'_ITEMS', 0, $this->ITEMS );
			//				$cache->Store( $object_class, $this->config->table_name.'_CHILDREN', 0, $this->CHILDREN );
			//			}
			$this->loaded = true;
		}
	}

	function Handle(){

		$this->Load();

		$rh =& $this->rh;
		$tpl =& $rh->tpl;

		//������������ ����� ��-�������
		$action = $rh->GetVar('action');

		switch($action){

		case 'update':
			//��������� �������� �� ��������� ���������
			$rh->HeadersNoCache();

			if( $new_id = $this->UpdateTreeStruct() )
				//������� ������ ���������
				$tpl->Assign('_new_id',$new_id);
			$tpl->Assign('_new_action',$this->_href_template.$this->id_get_var."=".$new_id);
			$tpl->Parse( $this->template_response, $this->store_to, true );
			break;

		case 'xml':
			//�������� XML ��� xloadtree � ������
			//���� �������� �� ������ ����, ��������� ��� - �� ������ ���.
			if( $rh->GetVar('logs')!='show' ){
				$rh->UseClass("DebugDummy");
				$rh->debug =& new DebugDummy();
			}
			//������ ���������, ��� ��� XML
			$rh->HeadersNoCache();
			// lucky@npj: ���� ��� iconv, �������� ��� ����
			if (function_exists('iconv'))
			{
				$this->xml_encoding = 'utf-8';
			}
			else
			{
				$this->xml_encoding = 'windows-1251';
			}
			header("Content-type: text/xml; charset=".$this->xml_encoding);
			echo $this->ToXML();
			die();
			//������
			$rh->End();
			break;

		default:
			//���������� ������� �����������
			$this->_Handle();
			//�������� ������ ��� ������� � ��������
			//render trash switcher
			$show_trash = $this->rh->state->Get('_show_trash');
			$tpl->Assign( '_href', $this->_href_template.'&_show_trash='.(!$show_trash) );
            
			$tpl->Parse( $show_trash ? $this->template_trash_hide : $this->template_trash_show, '__trash_switch' );

			//������ �� �������� �����
			$this->rh->logs->ParseLink( $this->config->module_name, 0, '__logs' );
            //���������� ������
			$_href = str_replace('&amp;','&',$this->_href_template);
			$_config_name = $this->config->PASSED[ 1 ];

			$tpl->Assign( '_url_connect', $_href.'mode='.$_config_name.'&action=update&_show_trash='.$show_trash.'&' );
			$tpl->Assign( '_url_xml', $_href.'mode='.$_config_name.'&action=xml&_show_trash='.$show_trash.'&'.$this->id_get_var.'='.$this->id.'&' );

			$tpl->Assign( '_behavior', $this->tree_behavior );
			$tpl->Assign( '_cur_id', $this->id );
			$tpl->Assign( '_level_limit', $this->config->level_limit  ? $this->config->level_limit : 100 );
			$tpl->Parse( $this->template_head, 'html_head', true );
			$tpl->Parse( $this->template_control, '__tree' );
			$tpl->Parse( $this->template, $this->store_to, true );
			break;
		}
	}

	function _Handle(){}

	function AddNew( $FIELDS=array() ){
		return DBDataEdit::AddNew($FIELDS);
	}

	function UpdateTreeStruct(){
		$rh =& $this->rh;
		$db =& $rh->db;
		$ids = $rh->GetVar('ids');
		if( $n = count($ids) ){
			//������� ��� � �������, ��� �� ����� ����� ��������� ������
			$this->loaded = false;
			$this->rh->cache->Clear( 'trees', $this->config->table_name.'_ITEMS' );
			$this->rh->cache->Clear( 'trees', $this->config->table_name.'_CHILDREN' );
			//������ ��� �����
			$mode = $this->rh->GetVar('mode');
			$_href = $this->rh->url.'do/'.$this->config->module_name.( $mode ? '/'.$mode : '' ).'?'.$this->state->State();
			//��������� ����� �������
			if( $rh->GetVar('add') ){
				//���������� �� ������
				$rh->GetVar('parent','integer');
				if( $brother_id = $rh->GetVar('brother','integer') ){
					$rs = $db->execute("SELECT _parent FROM ".$this->config->table_name." WHERE id='$brother_id'");
					$parent_id = $rs->fields["_parent"];
					$add_brother_mode = true;
				}else
					$parent_id = $rh->GetVar('parent','integer');
				//���������
				$new_id = $this->AddNew(array(
					'_parent'=>$parent_id,
				));
				//����� � ����
				$this->rh->logs->Put( '��������� �������: ����������', 0, $this->config->module_title, $this->prefix.$this->SELECT_FIELDS[1].$this->suffix.$this->new_suffix, $_href );
			}else
				//�� ����� ����� � ����
				$this->rh->logs->Put( '��������� �������: ��������� ���������', 0, $this->config->module_title, '[�� ������]', $_href );
			//set _created,_order
			$db->Execute("UPDATE ".$this->config->table_name." SET _created=NULL,_order=id WHERE id='$new_id'");
			//��������� ������� ���������
			for($i=0;$i<$n;$i++){
				if( $children = $rh->GetVar('children_'.$ids[$i]) ){
					$chids = explode(':',$children);
					$m = count($chids);
					for($j=0;$j<$m;$j++){
						$sql = "UPDATE ".$this->config->table_name." SET _order='".$j."',_parent='".$ids[$i]."' WHERE id='".$chids[$j]."'";
						//						echo $sql.'<br>';
						$db->execute($sql);
					}
				}
			}
			//��������� ��������� ��������
			$this->Load();
			$this->Restore();
			$this->_KillOutsiders();
			//���� �������� �����, �� ����� ��������� ����� �����
			if( $add_brother_mode && $new_id ){
				//������ �����
				$rs = $db->execute("SELECT id,_order FROM ".$this->config->table_name." WHERE _parent='$parent_id' AND _state<2 ORDER BY _order ASC");
				$BRS = $rs->GetArray();
				//        print_r($BRS);
				//���� �����
				$m = count($BRS);
				for( $i=0; $i<$m; $i++ )
					if( $BRS[$i]["id"]==$brother_id )
						break;
				//������ ������� - ������ ����� ������� ��������� �������� ����, � ������ ����
				$i++;
				for( $j=$i; $j<$m-1; $j++ ){
					$a = $BRS[$j]["_order"];
					$BRS[$j]["_order"] = $BRS[$j+1]["_order"];
					$BRS[$j+1]["_order"] = $a;
				}
				//��������� ���������
				for(;$i<$m;$i++)
					$db->execute("UPDATE ".$this->config->table_name." SET _order='".$BRS[$i]["_order"]."' WHERE id='".$BRS[$i]["id"]."'");
				//          print("UPDATE ".$this->config->table_name." SET _order='".$BRS[$i]["_order"]."' WHERE id='".$BRS[$i]["id"]."'<br>\n");
			}
			//��������� ������� ������ ��� ID ����������� ������
			return $new_id ? $new_id : true;
		}
		return false;
	}

	function ToXML(){  //$iconv=true
		//start XML
		$str = "<?xml version=\"1.0\" encoding=\"{$this->xml_encoding}\"?>\n\n";
		$str .= "<tree>\n";

		//������� �������� ����?
		$root_id = $this->rh->GetVar("display_root","integer");
		$root = $this->ITEMS[$root_id];

		//����� ��������� ������� ����
		$current = (object)$this->ITEMS[ $this->rh->GetVar("id","integer") ];
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

			//���������� ����� ��� ���?
			//���� �� ���� � �����������?
			if( $node->_left<=$c_parent->_left && $node->_right>=$c_parent->_right )
				$display_children = true;
			else
				//�������� ������ �� �������������� �����������
				$display_children = !($this->config->display_limit>0 && $level[ $node->id ] >= $this->config->display_limit);

			//���� - ����� ��� xloadtree?
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
			//���������� ��� ���� � ���������
			$_title = preg_replace( "/<.*?>/is", '', $node->title);
			//������� ���� ���������
			$_title = str_replace('"','\'',$_title);
			//���� ��������� �� � utf
			if (function_exists('iconv'))
			{
				$str .= str_repeat(" ",$node->_level)."<tree text=\"".iconv("CP1251","UTF-8", $_title ? $_title : 'node_'.$node->id )."\" ".$action_src." db_id=\"".$node->id."\" db_selected=\"".( $node->id==$this->id ? "1" : "" )."\" db_state=\"".$node->_state."\" ".(($is_folder)?">":"/>")."\n";
			}
			else
			{
				$str .= str_repeat(" ",$node->_level)."<tree text=\"".($_title ? $_title : 'node_'.$node->id )."\" ".$action_src." db_id=\"".$node->id."\" db_selected=\"".( $node->id==$this->id ? "1" : "" )."\" db_state=\"".$node->_state."\" ".(($is_folder)?">":"/>")."\n";
			}

			//			$str .= str_repeat(" ",$node->_level)."<tree text=\"text\" ".(($is_folder)?">":"/>")."\n";
			//��������� ����������� �� ������� �����������
			//put children
			if($is_folder){
				$arr = $this->CHILDREN[$node->id];
				for($i=count($arr)-1;$i>=0;$i--) $stack[] = $arr[$i];
				$cparent = $node->id;
			}
		}
		for( $i=(integer)$root["_level"] ; $i<$this->ITEMS[$cparent]['_level']; $i++ ) $str .= "</tree>\n";

		//end XML
		$str .= "</tree>\n";
		//mail ("nop@jetstyle.ru", "debug tree", $str);
		return $str;
	}

  /*
	 ��������� ��� ������ � �������, ������� �� ����� � ��������� ���������, 
	 ����������� �� �������� _parent.
	 ���������� ����� ->Load();
	*/
	function _KillOutsiders(){
		//�������� ��� ��, �������� � ��������� ���������
		$S[] = 0;
		while(count($S)){
			$id = array_pop($S);
			if(is_array($this->CHILDREN[$id]))
				$S = array_merge($S,$this->CHILDREN[$id]);
			$IDS[] = $id;
		}
		//������� ���� ��������� � �������
		$where = "_state<2 AND id<>'".implode("' AND id<>'",$IDS)."'";
		$rh =& $this->rh;
		$db =& $rh->db;
		//    $this->rh->db->execute("UPDATE ".$this->table_name." SET _state=2 WHERE id<>'".implode("' AND id<>'",$IDS)."'");
		//    mail('zharinov@jetstyle.ru','tree sql',"UPDATE ".$this->table_name." SET _state=2 WHERE id<>'".implode("' AND id<>'",$IDS)."'");
		//�������� �� �� ������ �������, � ���������� �����
		$this->rh->db->execute("UPDATE ".$this->table_name." SET _parent=0,_left=-1,_right=-1 WHERE ".$where);
		//������� �������� � �������
		$rs = $db->execute("SELECT id,title FROM ".$this->table_name." WHERE ".$where);
		$TO_KILL = $rs->GetArray();
		foreach($TO_KILL as $r){
			$rh->logs->Put( '��������� �������: �������� ���������', $r['id'], $this->config->module_title, $r['title'], $this->_redirect.'&_show_trash=1' );
			$rh->trash->Delete( $this->config->table_name, $r['id'], $this->config->module_title, $r['title'], $rh->path_rel.'?'.str_replace('&amp;','&',$this->state->StateAll()).'&id='.$r['id'] );
		}
	}
}

?>
