<?
	
	$this->UseClass("DBDataEdit");
	
class ListSimple extends DBDataEdit  {
	
	var $rh; //ссылка на $rh
	var $config; //ссылка на объект класса ModuleConfig
	var $loaded = false; //грузили или нет данные?
	var $id_get_var = 'id';
	
	//for ListObject
	var $template = "list_simple.html"; //шаблон результата
	var $template_list = "list_advanced.html:List"; //откуда брать шаблоны элементов списка
	var $store_to = "";
	var $_href_template; //шаблон для формирования ссылки
	var $template_trash_show = "list_simple.html:TrashShow";
	var $template_trash_hide = "list_simple.html:TrashHide";
	
	function ListSimple( &$config ){
		//base modules binds
		$this->config =& $config;
		//DBDataEdit
//		$config->Read("list");
		$config->SELECT_FIELDS[] = ($config->state_field) ? $config->state_field . " as '_state'" : '_state';
//	    	$where = $config->where.( $config->rh->GetVar('_show_trash') ? '' : ($config->where ? ' AND ' : '') . ( $config->state_field ? $config->state_field : "_state" ) . /*_state*/'<>2' );
	    	$where = ( $config->rh->GetVar('_show_trash') ? '_state>=0' : ( $config->state_field ? $config->state_field : "_state" ) . '<>2' ) . ($config->where ? ' AND ' . $config->where : '') ;

		DBDataEdit::DBDataEdit( $config->rh, $config->table_name, $config->SELECT_FIELDS, $where, $config->order_by, $config->limit );
		//for ListObject
		$this->store_to = "list_".$config->module_name;
		$this->_href_template = $config->_href_template ? $config->_href_template : $this->rh->path_rel."do/".$config->module_name."/form?".$this->rh->state->State();
		//для отслеживания текущего
		$this->id = $this->rh->GetVar('id');
		//запоминаем фильтр на корзину
		$this->rh->state->Keep('_show_trash');
	}
	
	function Handle(){
		$tpl =& $this->rh->tpl;
		
		$this->Load();
	
		//render trash switcher
        if (!$this->config->HIDE_CONTROLS['show_trash'])
        {
    		$show_trash = $this->rh->state->Get('_show_trash');
    		$tpl->Assign( '_href', $this->_href_template.'&_show_trash='.(!$show_trash) );
    		$tpl->Parse( $show_trash ? $this->template_trash_hide : $this->template_trash_show, '__trash_switch' );
        }
		//render list
		$this->rh->UseClass("ListObject");
		$list =& new ListObject( $this->rh, $this->ITEMS );
		$list->ASSIGN_FIELDS = $this->SELECT_FIELDS;
		$list->EVOLUTORS = $this->EVOLUTORS; //потомки могут добавить своего
		$list->EVOLUTORS["href"] = array( &$this, "_href" );
		$list->EVOLUTORS["title"] = array( &$this, "_title" );
		$list->issel_function = array( &$this, '_current' );
		$list->Parse( $this->template_list, '__list' );
		$tpl->Parse( $this->template, $this->store_to, true );
	}
	
	function Load( $where='' ){
		//load data
		if( !$this->loaded ){
			DBDataEdit::Load( $where );
			$this->loaded = true;
		}
	}
	
	function _href(&$list){
		return $this->_href_template.$this->id_get_var.'='.$list->DATA[ $list->loop_index ]['id'].'&';
	}
	
	function _title(&$list){
		$r = (object)$list->DATA[ $list->loop_index ];
    /*
		if($r->_state==2){
			$s1 = '<font color="red">';
			$s2 = '</font>';
		}
    */
		return $s1.( ($r->title)? $r->title : "[".$r->id."]" ).$s2;
	}
	
	function _current(&$list){
		$r = (object)$list->DATA[ $list->loop_index ];
		return ($this->id == $r->id ? '_sel' : '').($r->_state==1 ? '_hidden' : '').($r->_state==2 ? '_del' : '');
	}
}
	
?>