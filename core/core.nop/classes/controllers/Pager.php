<?php


/**
 *  ласс Pager - постранична€ листалка
 *
 * ¬ообще-то это контроллер
 *
 * Ћиталка с ограничением на количество записей,
 * _без изменени€ услови€ выборки_.
 */
$this->useClass('controllers/Controller');
class Pager extends Controller
{
	/**
	 * array(
	 *	  'model' => $o,
	 *
	 *	  'limit' => 10,
	 *	  'offset' => 10,
	 *			или 
	 *	   'page' => 10,
	 *	  или
	 *	  'req_limit' => 'lim',
	 *	  'req_offset' => 'off',
	 *			или
	 *	  'req_page' => 'page',
	 */
	var $config = array();

	function Pager(&$rh, $config)
	{
		$this->rh =& $rh;
		$this->config = $config;
	}

	function handle()
	{
		$this->limit = $this->getLimit();
		$this->offset = $this->getOffset();

		$this->model->limit = $this->getLimit();
		$this->model->offset = $this->getOffset();
	}

	function getNextPage()
	{
		$p = $this->getPage();
		return (($p + 1) * $this->getLimit() > $this->getCount())
			? NULL
			: $p + 1;
	}
	function getPrevPage()
	{
		$p = $this->getPage();
		return (($p - 1) * $this->getLimit() <= 0)
			? NULL
			: $p - 1;
	}
	function getInfo()
	{
		$s = array();

		$s['req_page'] = $this->config['req_page'];
		$s['req_page_size'] = $this->config['req_limit'];
		$s['req_offset'] = $this->config['req_offset'];
		$s['page'] = $this->getPage();
		$s['page_count'] = $this->getPagesCount();
		#if($m['page'] < $m['page_count'])
			$s['page_next'] = $this->getNextPage();
		#if($m['page'] >= 0)
			$s['page_prev'] = $this->getPrevPage();
		$s['page_size'] = $this->getLimit();

		$s['item'] = $this->getOffset();
		$s['item_count'] = $this->getCount();

		#$s['next_count'] = $this->getNextItemsPerPage();
		#$s['previous_count'] = $this->getPrevItemsPerPage();


		return $s;
	}

	function getPagesCount()
	{
		$count = $this->getCount() / $this->getLimit();
		return (integer)ceil($count);
	}

	function getCount()
	{
		if (!isset($this->count))
		{
			$this->count = $this->model->count();
		}
		return $this->count;
	}

	function getLimit()
	{
		if (isset($this->limit)) return $this->limit;

		if(isset($this->config['limit']))
		{
			$limit = $this->config['limit'];
		}
		elseif (($limit = $this->limitFromRequest()) && isset($limit))
		{
		}
		else
		{
			$limit = 0;
		}
		return $limit;
	}

	function reqVar($name)
	{
		return $this->rh->ri->get($name);
	}
	function limitFromRequest()
	{
		return isset($this->config['req_limit'])
			? $this->reqVar($this->config['req_limit'])
			: NULL;
	}
	function offsetFromRequest()
	{
		if (isset($this->config['req_offset']))
		{
			$offset = $this->reqVar($this->config['req_offset']);
		}
		elseif (isset($this->config['req_page']))
		{
			$offset = ($this->reqVar($this->config['req_page']) - 1) * $this->getLimit();
			$offset = $offset < 0 ? 0 : $offset;
		}
		else
		{
			$offset = NULL;
		}
		return $offset;
	}
	function getOffset()
	{
		if (isset($this->offset)) return $this->offset;

		if (isset($this->config['offset']))
		{
		  $offset = $this->config['offset'];
		}
		elseif ($this->config['page'])
		{
			$offset = ($this->config['page'] - 1) * $this->getLimit();
			$offset = $offset < 0 ? 0 : $offset;
		}
		elseif (($offset = $this->offsetFromRequest()) && isset($offset))
		{
		}
		else
		{
			$offset = 0;
		}
		return $offset;
	}
	function getPage()
	{
		return (int)ceil(1 + $this->getOffset() / $this->getLimit());
	}

}


/**
 * Ћисталка "год-мес€ц"
 * »спользуетс€ дл€ листани€ новостей
 *
 * ѕохоже это отдельный тип листалки, с ограничением на условие выборки
 * без ограничени€ на количество записей
 */
class MonthPager extends Pager
{

	function initialize()
	{
		$pagesize = $this->config['page_size'];
		if (isset($this->config['page'])) 
			$yearmonth = $this->config['page'];
		else
			$yearmonth = $this->rh->ri->get($this->config['req_page']);
		if (!isset($yearmonth)) $yearmonth = $this->config['default_page'];

		list($dstart, $dend) = $this->getDateRange($yearmonth, $pagesize);
		$begin = $dstart->format('%Y-%m-%d %H:%i:%s');
		$end = $dend->format('%Y-%m-%d %H:%i:%s');

		$this->begin = $begin;
		$this->end = $end;

		$where = 
			' AND (inserted >= '.$this->model->quote($begin)
			.' AND inserted <= '.$this->model->quote($end) .')'
			;
		$this->page = $yearmonth;
		$this->page_size = $pagesize;
		$this->where = $where;
	}

	function handle()
	{
		$this->initialize();
		$this->model->load($this->where);
	}

	function getDateRange($yearmonth, $pagesize, $offset=0)
	{
		preg_match('#^(\d{4})(\d{2})$#', $yearmonth, $matches);
		list(,$year, $month) = $matches;

		$this->rh->useClass('CyrDate');
		$dstart = CyrDate::newFromStr('%d%m%Y', date('dmY', mktime(0,0,0,$month-$pagesize+1+$offset, 1, $year)));
		$dstart->monthStart();
		$dend = CyrDate::newFromStr('%d%m%Y', date('dmY', mktime(0,0,0,$month+$offset, 1, $year)));
		$dend->monthEnd();
		return array(&$dstart, $dend);
	}
	function getPage()
	{
		return $this->page;
	}
	function getLimit()
	{
		return $this->page_size;
	}
	function getPagesCount()
	{
		return 2;
	}
	function getOffset()
	{
		return $this->page;
	}
	function getNextPage()
	{
		list($dstart, $dend) = $this->getDateRange($this->page, $this->page_size, $this->page_size);
		$sql = 'select max(inserted) as val '. $this->getSubRequest($this->model->sql);
		$rs = $this->rh->db->queryOne($sql);
		$max = $rs['val'];
		if (!isset($max)) return NULL;
		$d =& CyrDate::newFromStr('%Y-%m-%d %H:%i:%s', $max);
		$res = $d->mktime() > $dstart->mktime() 
			? $this->buildArg($dstart, $dend)
			: NULL
			;
		return $res;
	}
	function getPrevPage()
	{
		list($dstart, $dend) = $this->getDateRange($this->page, $this->page_size, -$this->page_size);
		$sql = 'select min(inserted) as val '. $this->getSubRequest($this->model->sql);
		$rs = $this->rh->db->queryOne($sql);
		$min = $rs['val'];
		if (!isset($min)) return NULL;
		$d =& CyrDate::newFromStr('%Y-%m-%d %H:%i:%s', $min);
		$res = $d->mktime() < $dend->mktime() 
			? $this->buildArg($dstart, $dend)
			: NULL
			;
		return $res;
	}
	function buildArg(&$dstart, &$dend)
	{
		return $dend->format('%Y%m');
	}
	function getInfo()
	{
		$s = array();

		$s['req_page'] = $this->config['req_page'];
		$s['page'] = $this->getPage();
		$s['page_next'] = $this->getNextPage();
		$s['page_prev'] = $this->getPrevPage();
		$s['page_size'] = $this->getLimit();
		$s['page_count'] = $this->getPagesCount();

		$s['item'] = $this->getOffset();
		$s['item_count'] = $this->getCount();

		#$s['next_count'] = $this->getNextItemsPerPage();
		#$s['previous_count'] = $this->getPrevItemsPerPage();


		return $s;
	}
	function getSubRequest($sql)
	{
		$sql = implode('', explode($this->where, $this->model->sql));
		$sql = substr($sql, strpos($sql, 'FROM '));
		return $sql;
	}
	function getCount()
	{
		return 0;
		$sql = $this->getSubRequest($this->model->sql);
		$this->model->sql = $sql;
		$this->count = $this->model->count();
	}

}

?>
