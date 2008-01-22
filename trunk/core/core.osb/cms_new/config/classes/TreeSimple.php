<?
	
	$this->UseClass("DBDataEditTree");
	
class TreeSimple extends DBDataEditTree  {
	
	var $rh; //������ �� $rh
	var $config; //������ �� ������ ������ ModuleConfig
	var $loaded = false; //������� ��� ��� ������?
	
	var $state; //������������ StateSet
	
	//templates
	var $template = "tree_simple.html";
	var $template_tree = "tree_simple.html:Tree";
	var $template_engine = "tree_simple.html:Tree_Engine"; //������ ����������� ������
	var $template_new = 'tree_simple.html:add_new'; //������ ����������� ������
	var $template_trash_show = "list_simple.html:TrashShow";
	var $template_trash_hide = "list_simple.html:TrashHide";
	var $store_to = "";
	var $_href_template; //������ ��� ������������ ������
	
	var $id_get_var = 'id';
	
	var $EVOLUTORS = array();
	
	function TreeSimple( &$config ){
		//base modules binds
		$this->config =& $config;
		//DBData
//		$config->Read('tree');
		DBDataEditTree::DBDataEditTree( $config->rh, $config->table_name, $config->SELECT_FIELDS, $config->where );
		$this->prefix = $config->module_name.'_tree_';
		$this->result_mode = 2;
		//��������� ��������
		$this->store_to = "tree_".$config->module_name;
		$this->_href_template = $this->rh->path_rel."do/".$config->module_name."/form?";
		//StateSet
		$this->state =& new StateSet($this->rh);
		$this->state->Set($this->rh->state);
		//��� ������������ ��������
		$this->id = $this->rh->GetVar('id');
		//���������� ������ �� �������
		$this->rh->state->Keep('_show_trash');
	}
	
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
		
		//��������, ������ ���������
		if( $this->UpdateTreeStruct() )
			$this->rh->Redirect( '?'.$this->state->State() );
		
		$tpl =& $this->rh->tpl;
		
		//render trash switcher
		$show_trash = $this->rh->state->Get('_show_trash');
		$tpl->Assign( '_href', $this->_href_template.'&_show_trash='.(!$show_trash) );
		$tpl->Parse( $show_trash ? $this->template_trash_hide : $this->template_trash_show, '__trash_switch' );
		
		//������ �� �����
		$tpl->Assign( '_add_new_href', $this->_href_template );
		$tpl->Parse( $this->template_new, '__add_new' );
		
		//assign some
		$tpl->Assign('prefix',$this->prefix);
		$tpl->Assign( 'POST_STATE', $this->state->State(1) );
		
		//������ �� �������
		$tpl->Parse( $this->template_engine, '__picker' );
		
		//render tree
		$this->rh->UseClass("ListObjectTree");
		$list =& new ListObjectTree( $this->rh, $this->ITEMS );
		$list->ASSIGN_FIELDS = $this->SELECT_FIELDS;
		$list->CHILDREN =& $this->CHILDREN;
		$list->EVOLUTORS['href'] = array( &$this, '_href' );
		$list->EVOLUTORS['add_href'] = array( &$this, '_add_href' );
		$list->EVOLUTORS['title'] = array( &$this, '_title' );
		$list->EVOLUTORS['width'] = array( &$this, '_width' );
		$list->EVOLUTORS['controls'] = array( &$this, '_controls' );
		//����������� ������ ����� �������� ���
		$list->EVOLUTORS = array_merge($list->EVOLUTORS,$this->EVOLUTORS);
		$list->issel_function = array( &$this, '_current' );
		$list->isfreezed_function = $this->isfreezed_function;
		$list->Parse( $this->template_tree, '__tree' );
		
		//������ �� �������� �����
		$this->rh->logs->ParseLink( $this->config->module_name, 0, '__logs' );
		
		//������������� ���������
		$tpl->Parse( $this->template, $this->store_to, true );
		
	}
	
  function UpdateTreeStruct(){
		$rh =& $this->rh;
		//params
		$id1 = $rh->GetVar('id1','integer');
		$id2 = $rh->GetVar('id2','integer');
		$action = $rh->GetVar('action');
		//actions
		$return = false;
		//������ ������������ ���
		if( $action ) $this->loaded = false;
		$this->rh->cache->Clear( 'trees', $this->config->table_name.'_ITEMS' );
		$this->rh->cache->Clear( 'trees', $this->config->table_name.'_CHILDREN' );
		//������ ��� �����
		$mode = $this->rh->GetVar('mode');
		$_href = $this->rh->url.'do/'.$this->config->module_name.( $mode ? '/'.$mode : '' ).'?'.$this->state->State();
		//��������� ��������
		switch($action){
			case 'add':
				$id = $this->AddNew( $id1 );
				$this->rh->state->Set('id',$id);
				$this->rh->Redirect( '?'.$this->state->State() );
				//����� � ����
				$this->rh->logs->Put( '������� ������: ����������', 0, $this->config->module_title, $this->prefix.$this->SELECT_FIELDS[1].$this->suffix.$this->new_suffix, $_href );
			break;
/*			case 'delete':
				$this->Delete( $rh->GetVar("_delete"), $rh->GetVar("hard") );
				$return = true;
			break;
			case 'restore':
				$this->tree->Restore( $id1 );
				$return = true;
			break;*/
			case 'exchange':
				$this->Exchange( $id1, $id2 );
				//����� � ����
				$item1 = $this->FindById($id1);
				$item2 = $this->FindById($id2);
				$this->rh->logs->Put( '������� ������: ����� �������', 0, $this->config->module_title, '"'.$item1[$this->SELECT_FIELDS[1]].'" - "'.$item2[$this->SELECT_FIELDS[1]].'"', $_href );
				//����������
				$return = true;
			break;
			case 'move_under':
				$this->MoveUnder( $id1, $id2 );
				//����� � ����
				$item1 = $this->FindById($id1);
				$item2 = $this->FindById($id2);
				$this->rh->logs->Put( '������� ������: ����������� ���', $id1, $this->config->module_title, '"'.$item1[$this->SELECT_FIELDS[1]].'" ��� "'.$item2[$this->SELECT_FIELDS[1]].'"', $_href );
				//����������
				$return = true;
			break;
			case 'restore':
				$this->Restore();
				$return = true;
			break;
		}
		return $return;
	}
	
	function _href(&$list){
		return $this->_href_template.$this->id_get_var.'='.$list->DATA[ $list->loop_index ]['id'].'&';
	}
	
	function _add_href(&$list){
		return $this->_href_template.'id1='.$list->DATA[ $list->loop_index ]['id'].'&action=add';
	}
	
	function _title(&$list){
		$r = (object)$list->DATA[ $list->loop_index ];
		return ($r->title)? $r->title : "[".$r->id."]";
	}
	
	function _width(&$list){
		return ($list->DATA[ $list->loop_index ]['_level']-1)*20;
	}
	
	function _current(&$list){
		return $this->id == $list->DATA[ $list->loop_index ]['id'] ? '_sel' : '';
	}
	
	function _controls(&$list){
		$tpl =& $this->rh->tpl;
		if( !$this->config->HIDE_CONTROLS['exchange'] )
			$controls .= $tpl->parse( $list->tpl_item.':exchange' );
		if( !$this->config->HIDE_CONTROLS['move_under'] )
			$controls .= $tpl->parse( $list->tpl_item.':move_under' );
		if( !$this->config->HIDE_CONTROLS['add_new'] )
			$controls .= $tpl->parse( $list->tpl_item.':add_new' );
		return $controls;
	}
}
	
?>