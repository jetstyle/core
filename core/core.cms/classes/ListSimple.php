<?php

class ListSimple 
{
	protected $rh; //ссылка на $rh
	protected $config; //ссылка на объект класса ModuleConfig
	protected $items;
	protected $pager;
	
	private $model;
	
	protected $loaded = false; //грузили или нет данные?
	protected $idGetVar = 'id';
	protected $idField = "id";

	protected $template = "list_simple.html"; //шаблон результата
	protected $template_list = "list_simple.html:List"; //откуда брать шаблоны элементов списка

	protected $template_trash_show = "list_simple.html:TrashShow";
	protected $template_trash_hide = "list_simple.html:TrashHide";
	
	protected $template_new = 'list_simple.html:add_new';
	protected $template_arrows = 'blocks/pager.html';
	
	protected $storeTo = "";

	protected $pageVar = 'p';
	protected $perPage = 20;
	protected $frameSize = 7;
	
	protected $prefix;
	
	protected $html;
	
	public function __construct( &$config )
	{
		$this->config =& $config;
		$this->rh = &$config->rh;
		
		if ($this->config->perPage)
		{
			$this->perPage = $this->config->perPage;
		}
		
		$this->config->SELECT_FIELDS[] = '_order';
		$this->config->SELECT_FIELDS[] = '_state';
		
		$this->storeTo = "list_".$config->getModuleName();
		$this->id = intval($this->rh->ri->get($this->idGetVar));
		
		$this->prefix = $config->moduleName.'_list_';
	}

	public function handle()
	{
		if( $this->updateListStruct() )
		{
			$this->rh->redirect( $this->rh->ri->hrefPlus('', array()));
		}
		 
		$tpl =& $this->rh->tpl;
				
		$this->load();

		$this->renderTrash();
		$this->renderAddNew();
		
		//render list
		$this->rh->useClass("ListObject");
		$list =& new ListObject( $this->rh, $this->items );
		$list->ASSIGN_FIELDS = $this->SELECT_FIELDS;
		$list->EVOLUTORS = $this->EVOLUTORS; //потомки могут добавить своего
		$list->EVOLUTORS["href"] = array( &$this, "_href" );
		$list->EVOLUTORS["title"] = array( &$this, "_title" );
		$list->EVOLUTORS['controls'] = array( &$this, '_controls' );
		$list->issel_function = array( &$this, '_current' );
		$list->parse( $this->template_list, '__list' );

		$this->renderPager();
	}
	
	public function setStoreTo($value)
	{
		$this->storeTo = $value;
	}
	
	public function load( $where = '' )
	{
		if( !$this->loaded )
		{
			$total = $this->getTotal($where);
			
			if ($total > 0)
			{
				$this->pager($total);
				
				$model = &$this->getModel();
				$model->load( $where, $this->pager->getLimit(), $this->pager->getOffset());
				$this->items = &$model->getData();
			}
						
			$this->loaded = true;
		}
	}
	
	public function getTotal($where)
	{
		return $this->getModel()->getCount($where);
	}
	
	public function _href(&$list)
	{
		return $this->rh->ri->hrefPlus('', array($this->idGetVar => $list->ITEMS[ $list->loop_index ][$this->idField]));
	}

	public function _title(&$list)
	{
		$r = &$list->ITEMS[ $list->loop_index ];
		return ( ($r['title'])? $r['title'] : "[".$r[$this->idField]."]" );
	}

	public function _current(&$list)
	{
		$r = &$list->ITEMS[ $list->loop_index ];
		return ($this->id == $r[$this->idGetVar] ? '_sel' : '').($r['_state']==1 ? '_hidden' : '').($r['_state']==2 ? '_del' : '');
	}

	public function _controls(&$list)
	{
		$tpl =& $this->rh->tpl;
		if( !$this->config->HIDE_CONTROLS['exchange'] )
		{
			return $tpl->parse( $list->tpl_item.'_Exchange' );
		}
		return '';
	}
	
	public function getHtml()
	{
		return $this->rh->tpl->parse( $this->template); 
	}
	
	protected function renderTrash()
	{
		//render trash switcher
		if (!$this->config->HIDE_CONTROLS['show_trash'])
		{
			$show_trash = $_GET['_show_trash'];
			$this->rh->tpl->set( '_show_trash_href', $this->rh->ri->hrefPlus('', array('_show_trash' => !$show_trash)));
			$this->rh->tpl->parse( $show_trash ? $this->template_trash_hide : $this->template_trash_show, '__trash_switch' );
		}
	}
	
	protected function renderAddNew()
	{
		if (!$this->config->HIDE_CONTROLS['add_new'])
		{
			//ссылка на новое
			$this->rh->tpl->set( '_add_new_href', $this->rh->ri->hrefPlus('', array($this->idGetVar => '', '_new' => 1)));
			$this->rh->tpl->set( '_add_new_title', $this->config->get('add_new_title') ? $this->config->get('add_new_title') : 'создать новый элемент' );
			$this->rh->tpl->Parse( $this->template_new, '__add_new' );
		}
	}
	
	protected function &getModel()
	{
		if (!$this->model)
		{
			$this->rh->useModel('DBModel');
			$this->model = new DBModel($this->rh);
			$this->model->setTable($this->config->table_name);
			$this->model->setFields($this->config->SELECT_FIELDS);
			$this->model->where = ( $_GET['_show_trash'] ? '{_state}>=0' : "{_state} <>2 " ) . ($this->config->where ? ' AND ' . $this->config->where : '') ;
			$this->model->setOrder($this->config->order_by);
		}
		
		return $this->model;
	}
	
	protected function updateListStruct()
	{
		$rh =& $this->rh;
		//params
		$id1 = intval($_POST['id1']);
		$id2 = intval($_POST['id2']);
		$action = $_POST['action'];
		
		$return = false;
		switch($action)
		{
			case 'exchange':
				
				$model = &$this->getModel();
				$model->load($model->quoteField($this->idField).'IN('.$id1.','.$id2.')');
								
				$data = array('_order' => $model[1]['_order']);
				$model->update($data, $model->quoteFieldShort($this->idField).'='.$model[0][$this->idField]);
				
				$data = array('_order' => $model[0]['_order']);
				$model->update($data, $model->quoteFieldShort($this->idField).'='.$model[1][$this->idField]);
				
				//возвращаем
				$return = true;
			break;
		}
		return $return;
	}
	
	protected function pager($total)
	{
		$this->rh->useClass('Pager');
		$this->pager = new Pager($this->rh);
		$this->pager->set(intval($this->rh->ri->get($this->pageVar)), $total, $this->perPage, $this->frameSize);
	}
	
	protected function renderPager()
	{
		if ($this->pager)
		{
			$this->rh->tpl->set('pager', $this->pager->getPages());
			$this->rh->tpl->parse('blocks/pager.html', '__arrows');
		}
	}
}

?>