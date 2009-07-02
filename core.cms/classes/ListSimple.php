<?php

class ListSimple
{
	protected $tpl;

	protected $config;		//ññûëêà íà îáúåêò êëàññà ModuleConfig
	protected $items;
	protected $pager;

    private $filtersObjects = array();

	private $model = null;

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

		if ($this->config['perPage'])
		{
			$this->perPage = $this->config['perPage'];
		}

		if ($this->config['frameSize'])
		{
			$this->frameSize = $this->config['frameSize'];
		}

		//$this->config['fields'][] = '_order';
		//$this->config['fields'][] = '_state';

		$this->storeTo = "list_".$config['module_name'];
		$this->id = intval(RequestInfo::get($this->idGetVar));

		$this->prefix = $this->config['module_name'].'_list_';
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

        Locator::get('tpl')->set('module_name', $this->config['module_name']);

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

	public function _controls(&$list)
	{
		if( !$this->config['hide_controls']['exchange'] )
		{
			return $this->tpl->parse( $list->tpl_item.'_Exchange' );
		}
		return '';
	}

	public function getHtml()
	{
		return $this->tpl->parse( $this->template);
	}

    public function getPrefix()
    {
        return $this->prefix;
    }


    public function getFiltersObjects()
    {
        $filters = array();
        if (is_array($this->config['filters']))
        {
            foreach ($this->config['filters'] AS $key => $filter)
            {
                $filters[$key] = $this->getFilterObject($key);
            }
        }
        return $filters;
    }

    public function getFilterObject($key)
    {
        if (!array_key_exists($key, $this->filtersObjects))
        {
            $this->filtersObjects[$key] = $this->constructFiltersObject($key);
        }

        return $this->filtersObjects[$key];
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

	protected function renderAddNew()
	{
		if (!$this->config['hide_controls']['add_new'])
		{
			//ññûëêà íà íîâîå
			$this->tpl->set( '_add_new_href', RequestInfo::hrefChange('', array($this->idGetVar => '', '_new' => 1)));
			$this->tpl->set( '_add_new_title', $this->config['add_new_title'] );
			$this->tpl->Parse( $this->template_new, '__add_new' );
		}
	}

    protected function renderFilters()
    {
        $html = '';
        $filters = $this->getFiltersObjects();
        foreach ($filters AS $filter)
        {
            $html .= $filter->getHtml();
        }

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
		}

		return $this->model;
	}

    protected function constructModel()
    {
        Finder::useModel('DBModel');
        $model = DBModel::factory($this->config['model']);
        $model->addFields(array('_order', '_state'));

        $model->where .= $_GET['_show_trash'] ? '{_state}>=0' : "{_state} <>2 ";

        $this->applyFilters($model);

        return $model;
    }

    protected function applyFilters(&$model)
    {
        $filters = $this->getFiltersObjects();
        foreach ($filters AS $filter)
        {
            $filter->apply($model);
        }
    }

    protected function constructFiltersObject($key)
    {
        Finder::useClass('Inflector');
        
        $config = $this->config['filters'][$key];

        if (!is_array($config))
        {
            $config = array();
        }

        if (!$config['field'] && !is_numeric($key))
        {
            $config['field'] = $key;
        }

        $className = 'List'.Inflector::camelize($config['type']).'Filter';
        Finder::useClass($className);
        $filterObj = new $className($config, $this);

        if (!in_array('ListFilter', class_parents($filterObj)))
        {
            throw new JSException("Class \"".get_class($filterObj)."\" must extends from ListFilter");
        }

        return $filterObj;
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

	public function getAllItems($where = '')
	{
    	$model = &$this->getModel();
    	$model->where = $where;
		$model->load();
		return $model->getArray();
	}
}
?>