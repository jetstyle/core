<?php
/**
 * @author lunatic lunatic@jetstyle.ru
 * @author nop nop@jetstyle.ru
 *
 * @last-modified 23:36 27.02.2008
 */

 Finder::useClass('PagerInterface');

 class Pager implements PagerInterface
 {
	const FIRST_PAGE = 1;		// first page number
	
 	protected $total = 0;		// total items
 	protected $perPage = 10;	// items per page
 	protected $p = NULL;		// current page
 	protected $frameSize = 7;	//
 	protected $data = array();
	protected $pageVar = 'p';

 	public function getPages()
 	{
 		$this->construct();
 		return $this->data;
 	}

 	public function setup($currentPage = 0, $total = 0, $perPage = 0, $frameSize = 0)
 	{
 		if ($currentPage > 0)
		{
			$this->setCurrentPage(intval($currentPage));
		}

 		$this->total = $total;
 		if ($perPage > 0)
 		{
 			$this->perPage = $perPage;
 		}

 		if ($frameSize > 0)
 		{
 			$this->frameSize = $frameSize;
 		}
 	}

	public function getTotal()
	{
		return $this->total;
	}

 	public function getLimit()
 	{
 		return $this->perPage;
 	}

 	public function getOffset()
 	{
 		return (($this->getCurrentPage() - 1) * $this->perPage);
 	}
	public function getLastPage()
	{
		return intval(ceil($this->total / $this->perPage));
	}
 	protected function construct()
 	{
 		if ($this->total <= $this->perPage)
		{
			return;
		}

		$lastPage = $this->getLastPage();
		$currentPage = $this->getCurrentPage();

		if ($this->frameSize > $lastPage)
		{
			$this->frameSize = $lastPage;
		}

		$htis->data = array (
			'pages' => array ()
		);

		if ($currentPage <= ceil($this->frameSize / 2))
		{
			//echo 'left';
			for ($i = 1; $i <= $this->frameSize; $i++)
			{
				$this->data['pages'][$i] = $this->buildPage($i, $currentPage, 0);
			}
			//если в левой части фрейма и страниц больше чем влазит во фрейм
			if ($lastPage > $this->frameSize)
			{
				$this->data['pages'][$i-1]['has_more'] = $i;
				$this->data['pages'][$i-1]['has_more_url'] = $this->fixUrl( RequestInfo::hrefChange('',
					array (
						$this->getPageVar() => $i,
						'submit' => '',
					)
				));
				$this->data['pages'][$i-1]['last_url'] = $this->fixUrl( RequestInfo::hrefChange('',
					array (
						$this->getPageVar() => $lastPage,
					)
				));
				$this->data['pages'][$i-1]['last_num'] = $lastPage;
			}
		}
		elseif ($currentPage > ($lastPage - (ceil($this->frameSize / 2))))
		{
			//echo 'right';
			$start_from = ($lastPage + 1 - $this->frameSize);
			for ($i = $start_from; $i <= $lastPage; $i++)
			{
				$this->data['pages'][$i] = $this->buildPage($i, $currentPage, $start_from);
			}

		}
		else
		{

			$start_from = ($currentPage - (floor($this->frameSize / 2)));
			$end_to =  (p + (floor($this->frameSize / 2)));

			$end_to = $start_from + $this->frameSize ;
			//echo 'else '.$start_from." to ".$end_to;

			for ($i = $start_from; $i < $end_to ; $i++)
			{
				$this->data['pages'][$i] = $this->buildPage($i, $currentPage, $start_from);
			}
			//если до конца больше фрейма и в нем нет последней страницы
			if ( $lastPage > $i )
			{
				$this->data['pages'][$i-1]['has_more'] = $i;
				$this->data['pages'][$i-1]['has_more_url'] = $this->fixUrl( RequestInfo::hrefChange('',
					array (
						$this->getPageVar() => $i,
						'submit' => '',
					)
				));
				$this->data['pages'][$i-1]['last_url'] = $this->fixUrl( RequestInfo::hrefChange('',
					array (
						$this->getPageVar() => $lastPage,
					)
				));
				$this->data['pages'][$i-1]['last_num'] = $lastPage;

			}
		}

		if ($currentPage > 1)
		{
			$this->data['prev_page'] = $this->fixUrl( RequestInfo::hrefChange('',
				array (
					$this->getPageVar() => ($currentPage - 1),
					'submit' => '',
				)
			));
		}
		if ($currentPage <= ($lastPage -1))
		{
			$this->data['next_page'] = $this->fixUrl( RequestInfo::hrefChange('',
				array (
					$this->getPageVar() => ($currentPage +1),
					'submit' => '',
				)
			));
		}
 	}

 	/**
 	 * Делает поправку для аяксовых урлов
 	 */
 	protected function fixUrl($url)
 	{
 		$url = str_replace("/ajax", "", $url);
 		//var_dump($url);
 		return $url;
 	}

 	/**
 	 * nop: page builder for every case
 	 */
 	protected function buildPage($i, $p, $start_from)
 	{
 		$page = array (
					'num' => $i,
					'url' => $this->fixUrl( RequestInfo::hrefChange('',
					array (
						$this->getPageVar() => $i,
						'submit' => ''
					)
				)) );


		if ($i == $p)
		{
			$page['current'] = true;
		}

		//для первой страницы текущего фрейма проверим, можно ли отмотать фрейм назад
		if ( $i == $start_from && $start_from > 1 )
		{
			$page['has_less_url'] = $this->fixUrl( RequestInfo::hrefChange('',
				array ($this->getPageVar() => $i-1)
			));
			$page['first_url'] = $this->fixUrl( RequestInfo::hrefChange('',
				array ($this->getPageVar() => 1)
			));
		}

		return $page;
 	}

	public function getPageVar()
	{
		return $this->pageVar;
	}
	public function setPageVar($name)
	{
		$this->pageVar = $name;
		return $this;
	}
	
	public function getCurrentPage()
	{
		if (!isset($this->p))
		{
			$page = NULL;
			if ($this->getPageVar())
			{
				 $page = RequestInfo::get($this->getPageVar());
			}
			$this->setCurrentPage($this->recognizePage($page));
		}
		return $this->p;
	}
	public function setCurrentPage($pageNo)
	{
		if (!is_int($pageNo))
			throw new JSException("Invalid type of pageNo argument. Integer expected");
		$this->p = $pageNo;
		return $this;
	}
	protected function recognizePage($page)
	{
		$page = intval($page);
		if ($page < self::FIRST_PAGE)
		{
			$page = self::FIRST_PAGE;
		}
		elseif (NULL !== ($lastPage = $this->getLastPage()) && $page >= $lastPage)
		{
			$page = $lastPage;
		}
		return $page;
	}
 }
?>