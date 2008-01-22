<?
	
class TrashEdit {
	
	var $rh; //ссылка на $rh
	var $config; //ссылка на объект класса ModuleConfig
	var $state; //персональный StateSet
	
	var $template = "trash.html";
	var $store_to;
	
	var $loaded = false;
	var $arrows; //постраничный рубрикатор
	var $ITEMS; //загруженные данные
	
	function TrashEdit( &$config ){
		//base modules binds
		$this->config =& $config;
		$this->rh =& $config->rh;
		$this->prefix = $config->module_name.'_trash_';
		$this->store_to = "trash_".$config->module_name;
		//StateSet
		$this->state =& new StateSet($this->rh);
		$this->state->Set($this->rh->state);
		//постраничный рубрикатор
		$this->rh->UseClass('Arrows',0);
	  $this->arrows = new Arrows( $this->rh );
	  $this->arrows->outpice = 30;
	  $this->arrows->mega_outpice = 10;
	  $this->arrows->Set($this->state);
		include( $this->rh->FindScript('handlers','_arrows_format') );
		$this->arrows->href_suffix = $rh->path_rel.'do/'.$config->module_name;
	}
	
	function Load(){
		if(!$this->loaded){
			$trash =& $this->rh->trash;
			$_from = $trash->table_trash." as tr, ".$trash->table_trash_tables." as tb";
			$_where = "tb.id=tr.table_id";
			//постраницный рубрикатор
		  $this->arrows->Setup( $_from, $_where );
			$this->arrows->Restore();
			$limit = $this->arrows->Limit();
			//сложный запрос
			$sql = "SELECT _created, table_id, table_name, item_id, item_title, module_title, view_link";
			$sql .= " FROM $_from ";
			$sql .= " WHERE $_where ";
			$sql .= " ORDER BY _created DESC";
			$rs = $this->rh->db->SelectLimit( $sql, $limit[1], $limit[0] );
			$this->ITEMS = $rs->GetArray();
			$this->loade = true;
		}
	}
	
	function Handle(){
		$tpl =& $this->rh->tpl;
		
		//грузим
		$this->Load();
		
		//постраничный рубриктор
		$arrows =& $this->arrows;
	  $arrows->Restore();
		if( $arrows->mega_sum > 1 ){
			$this->arrows->Parse('arrows.html','__links_all');
			$tpl->Parse( $this->template.':Arrows', '_arrows' );
		}

		//обновляем
		if( $this->Update() )
			$this->rh->Redirect( $this->rh->url.'do/'.$this->config->module_name.'?'.$arrows->State() );
		
		$tpl->Assign( 'prefix', $this->prefix );
		$tpl->Assign( 'POST_STATE', $this->state->State(1) );
		
		//рендерим корзину
		$template_item = $this->template.':_items';
		$n = count($this->ITEMS);
		for($i=0;$i<$n;$i++){
			$r = (object)$this->ITEMS[$i];
			$tpl->AssignRef('*',$r);
			$tpl->Assign('_bgcolor', $i%2 ? '#eeeeee' : '#dddddd' );
			$tpl->Parse( $template_item, '_items', true );
		}
		
		//рендерим результат
		$tpl->Parse( $this->template, $this->store_to, true );
		
		//чистим мусор
		$tpl->Assign('_arrows','');
		$tpl->Assign('_items','');
	}
	
	function Update(){
		$rh =& $this->rh;
		$trash =& $rh->trash;
		if( $rh->GetVar( $this->prefix."update" ) ){
			//восстанавливаем
			$A1 = $rh->GetVar($this->prefix.'restore');
			if(is_array($A1))
				foreach($A1 as $r){
					$A = explode(':',$r);
//					echo 'FromTrash: '.$A[0].':'.$A[1].'<br>';
					$trash->FromTrash($A[0],$A[1]);
				}
			//удаляем
			$A2 = $rh->GetVar($this->prefix.'erase');
			if(is_array($A2))
				foreach($A2 as $r){
					$A = explode(':',$r);
//					echo 'Erase: '.$A[0].':'.$A[1].'<br>';
					$trash->Erase($A[0],$A[1]);
				}
			return true;
		}else return false;
	}
	
}
	
?>