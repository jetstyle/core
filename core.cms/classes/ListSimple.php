<?php

class ListSimple
{
	protected $tpl;
	
	protected $config;		//ññûëêà íà îáúåêò êëàññà ModuleConfig
	protected $items;
	protected $pager;

	private $model;

	protected $loaded = false; //ãğóçèëè èëè íåò äàííûå?

	protected $idGetVar = 'id';		//
	protected $idField = "id";		// ïåğâè÷íûé êëş÷ òàáëèöû

	protected $template = "list_simple.html"; 				//øàáëîí ğåçóëüòàòà
	protected $template_list = "list_simple.html:List"; 	//îòêóäà áğàòü øàáëîíû ıëåìåíòîâ ñïèñêà

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
	
		$this->db = &Locator::get('db');
		$this->tpl = &Locator::get('tpl');
		
		if ($this->config->perPage)
		{
			$this->perPage = $this->config->perPage;
		}

		if ($this->config->frameSize)
		{
			$this->frameSize = $frameSize;
		}

		$this->config->SELECT_FIELDS[] = '_order';
		$this->config->SELECT_FIELDS[] = '_state';

		$this->storeTo = "list_".$config->getModuleName();
		$this->id = intval(RequestInfo::get($this->idGetVar));

		$this->prefix = $config->moduleName.'_list_';
	}

	public function handle()
	{
		if( $this->updateListStruct() )
		{
			Controller::redirect( RequestInfo::hrefChange('', array()));
		}

		$this->load();

		$this->renderTrash();
		$this->renderAddNew();

		//render list
		Finder::useClass("ListObject");
		$list =& new ListObject( $this->items );
		$list->ASSIGN_FIELDS = $this->SELECT_FIELDS;
		$list->EVOLUTORS = $this->EVOLUTORS; //ïîòîìêè ìîãóò äîáàâèòü ñâîåãî
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
		return RequestInfo::hrefChange('', array($this->idGetVar => $list->ITEMS[ $list->loop_index ][$this->idField], '_new' => ''));
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
		if( !$this->config->HIDE_CONTROLS['exchange'] )
		{
			return $this->tpl->parse( $list->tpl_item.'_Exchange' );
		}
		return '';
	}

	public function getHtml()
	{
		return $this->tpl->parse( $this->template);
	}

	protected function renderTrash()
	{
		//render trash switcher
		if (!$this->config->HIDE_CONTROLS['show_trash'])
		{
			$show_trash = $_GET['_show_trash'];
			$this->tpl->set( '_show_trash_href', RequestInfo::hrefChange('', array('_show_trash' => !$show_trash)));
			$this->tpl->parse( $show_trash ? $this->template_trash_hide : $this->template_trash_show, '__trash_switch' );
		}
	}

	protected function renderAddNew()
	{
		if (!$this->config->HIDE_CONTROLS['add_new'])
		{
			//ññûëêà íà íîâîå
			$this->tpl->set( '_add_new_href', RequestInfo::hrefChange('', array($this->idGetVar => '', '_new' => 1)));
			$this->tpl->set( '_add_new_title', $this->config->get('add_new_title') ? $this->config->get('add_new_title') : 'ñîçäàòü íîâûé ıëåìåíò' );
			$this->tpl->Parse( $this->template_new, '__add_new' );
		}
	}

	protected function &getModel()
	{
		if (!$this->model)
		{
			Finder::useModel('DBModel');
			$this->model = new DBModel();
			$this->model->setTable($this->getTableName());
			$this->model->setFields($this->config->SELECT_FIELDS);
			$this->model->where = ( $_GET['_show_trash'] ? '{_state}>=0' : "{_state} <>2 " ) . ($this->config->where ? ' AND ' . $this->config->where : '') ;
			$this->model->setOrder($this->config->order_by);
		}

		return $this->model;
	}

	protected function getTableName()
	{
		if (!$this->config->table_name)
		{
			Finder::useClass('Inflector');
			$pathParts = explode('/', $this->config->componentPath);
			array_pop($pathParts);
			$pathParts = array_map(array(Inflector, 'underscore'), $pathParts);
			$this->config->table_name = strtolower(implode('_', $pathParts));
		}
		
		return $this->config->table_name;
	}
	
	/**
	 * Ìåíÿåì ıëåìåíòû ìåñòàìè
	 *
	 * @return boolean
	 */
	protected function updateListStruct()
	{
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

				//âîçâğàùàåì
				$return = true;
			break;
		}
		return $return;
	}

	protected function pager($total)
	{
		Finder::useClass('Pager');
		$this->pager = new Pager();
		$this->pager->setup(intval(RequestInfo::get($this->pageVar)), $total, $this->perPage, $this->frameSize);
	}

	protected function renderPager()
	{
		if ($this->pager)
		{
			$this->tpl->set('pager', $this->pager->getPages());
			$this->tpl->parse('blocks/pager.html', '__arrows');
		}
	}
}

?>