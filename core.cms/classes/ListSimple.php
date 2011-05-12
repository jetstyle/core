<?php

class ListSimple implements ModuleInterface
{
	protected $tpl;

	protected $config;		//ññûëêà íà îáúåêò êëàññà ModuleConfig
	protected $items;
	protected $pager;

	private $filtersObject = null;

	private $model = null;

	protected $loaded = false; //ãğóçèëè èëè íåò äàííûå?

	protected $idGetVar = 'id';		//
	protected $idField = "id";		// ïåğâè÷íûé êëş÷ òàáëèöû

	protected $template = "list_simple.html"; 				//øàáëîí ğåçóëüòàòà
	protected $template_list = "list_simple.html:list"; 	//îòêóäà áğàòü øàáëîíû ıëåìåíòîâ ñïèñêà

	protected $template_trash_show = "list_simple.html:trash_show";
	protected $template_trash_hide = "list_simple.html:trash_hide";

	protected $template_new = 'list_simple.html:add_new';
	protected $template_arrows = 'blocks/pager.html';

	protected $storeTo = "";

	protected $pageVar = 'p';
	protected $perPage = 20;
	protected $frameSize = 7;

	protected $prefix;

	protected $html;

	public function __construct( $config )
	{
		$this->config = $config;

		$this->db  = Locator::get('db');
		$this->tpl = Locator::get('tpl');

		if ($this->config['perPage'])
		{
			$this->perPage = $this->config['perPage'];
		}

		if ($this->config['frameSize'])
		{
			$this->frameSize = $this->config['frameSize'];
		}

		$this->prefix = @implode('_', $config['module_path_parts']).'_';
		$this->storeTo = $this->prefix.'tpl';

        //GET ïàğàìåòğû äëÿ ññûëêà add_new
        $this->config['add_new_get_params'] = array($this->idGetVar => '', '_new' => 1, 'order'=>'');

        Locator::get('tpl')->set( '_delete_title', $this->config['delete_title'] ? $this->config['delete_title'] : "Óäàëèòü" );

		$this->id = intval(RequestInfo::get($this->idGetVar));

	}

	public function insert($postData=array())
	{
	}
	public function update($updateData = null)
	{
	}

	public function handle()
	{
        if ($_POST['order_list'])
        {
            $page = intval($_POST[$this->pageVar]);
            if (!$page) $page = 1;
            $model = DBModel::factory($this->config['model']);
            if (sizeof($_POST['orders'])) {
                foreach ($_POST['orders'] as $id => $order) {
                    $data = array('_order' => $order + ($page - 1) * $this->perPage);
                    $model->update($data, '{id} = '.$id);
                }
            }
            die('1');
        }
		if ($_POST['delete_list'])
		{
			$items = explode(',', $_POST['delete_list']);
			foreach ($items as $id)
			{
				$this->getModel()->deleteToTrash(intval($id));
			}
			die('1');
		}
		if ($_POST['restore_list'])
		{
			$items = explode(',', $_POST['restore_list']);
			foreach ($items as $id)
			{
				$this->getModel()->restoreFromTrash(intval($id));
			}
			die('1');
		}

		$this->load();

		$tpl = &Locator::get('tpl');
		$tpl->set('page_url', RequestInfo::$baseUrl.RequestInfo::$pageUrl);
		$tpl->set('page_num', intval($_GET[$this->pageVar]));
		$tpl->set('page_var', $this->pageVar);
		$tpl->set('per_page', $this->perPage);
		$tpl->set('group_delete_url', RequestInfo::hrefChange('',array('delete_list'=>'1')));
		$tpl->set('group_restore_url', RequestInfo::hrefChange('',array('restore_list'=>'1')));
		$tpl->set('group_operations', $this->config['group_operations']);
		$tpl->set('drags', $this->config['drags']);

		$this->renderTrash();
		$this->renderAddNew();

		//render list
		Finder::useClass("ListObject");
		$list = new ListObject( $this->items );
		$list->ASSIGN_FIELDS = $this->SELECT_FIELDS;
		$list->EVOLUTORS = $this->EVOLUTORS; //ïîòîìêè ìîãóò äîáàâèòü ñâîåãî
		$list->EVOLUTORS["href"] = array( &$this, "_href" );
		$list->EVOLUTORS["title"] = array( &$this, "_title" );
		$list->issel_function = array( &$this, '_current' );
		$list->parse( $this->template_list, '__list' );

		$this->renderPager();

		$this->renderFilters();

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

	public function getHtml()
	{
		return $this->tpl->parse( $this->template);
	}

	public function getPrefix()
	{
		return $this->prefix;
	}

	public function getFiltersObject($key = null)
	{
		if (null === $this->filtersObject)
		{
			$this->filtersObject = $this->constructFiltersObject();
		}

		if ($key)
		{
			return $this->filtersObject->getByKey($key);
		}
		else
		{
			return $this->filtersObject;
		}
	}

	protected function renderTrash()
	{
	//render trash switcher
		if (!$this->config['hide_controls']['show_trash'])
		{
			$show_trash = $_GET['_show_trash'];
			$this->tpl->set( '_show_trash_href', RequestInfo::hrefChange('', array('_show_trash' => !$show_trash)));
			$this->tpl->parse( $show_trash ? $this->template_trash_hide : $this->template_trash_show, '__trash_switch' );
		}
	}

    //ññûëêà íà íîâîå
	protected function renderAddNew()
	{
		if (!$this->config['hide_controls']['add_new'])
		{
			$this->tpl->set( '_add_new_href', RequestInfo::hrefChange(( $this->config['add_new_href'] ? RequestInfo::$baseUrl."do/".$this->config['add_new_href'] : ''), $this->config['add_new_get_params']));

			$this->tpl->set( '_add_new_title', $this->config['add_new_title'] ? $this->config['add_new_title'] : "Äîáàâèòü" );
			$this->tpl->Parse( $this->template_new, '__add_new' );
		}
	}

	protected function renderFilters()
	{
		$html = $this->getFiltersObject()->getHtml();
		$this->tpl->set('__filter', $html);
	}

	protected function renderPager()
	{
		if ($this->pager)
		{
			$this->tpl->set('pager', $this->pager->getPages());
			$this->tpl->parse('blocks/pager.html', '__arrows');
		}
	}

	protected function &getModel()
	{
		if (null === $this->model)
		{
			$this->model = $this->constructModel();
			$this->applyFilters($this->model);
		}

		return $this->model;
	}

	protected function constructModel()
	{
		if (!$this->config['model'])
		{
			throw new JSException("You should set `model` param in config");
		}

		Finder::useModel('DBModel');
		$model = DBModel::factory($this->config['model']);
		$model->addFields(array('_order', '_state'));

		$model->where .= ($model->where ? " AND " : "" ).($_GET['_show_trash'] ? '{_state}>=0' : "{_state} <>2 ");

		return $model;
	}

	protected function applyFilters(&$model)
	{
		$filter = $this->getFiltersObject();
		$filter->apply($model);
	}

	protected function constructFiltersObject()
	{
		Finder::useClass('ListFilter');
		$config = array(
			'type' => 'wrapper',
			'filters' => $this->config['filters'],
		);

		$filterObj = ListFilter::factory($config, $this);

		return $filterObj;
	}

	protected function pager($total)
	{
		Finder::useClass('Pager');
		$this->pager = new Pager();
		$this->pager->setPageVar($this->pageVar);
		$this->pager->setup(intval(RequestInfo::get($this->pageVar)), $total, $this->perPage, $this->frameSize);

		if ($this->config['keepVars'])
		        $this->pager->setKeepVars($this->config['keepVars']);
	}

	public function getAllItems($where = '')
	{
		$model = &$this->getModel();
		$model->where = $where;
		$model->load();
		return $model->getArray();
	}

	public function getItems()
	{
		return $this->items;
	}
}
?>

