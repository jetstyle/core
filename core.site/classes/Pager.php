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
	const DEFAULT_PAGE = 1;
	
	protected $pageVar = 'p';	// http request parameter name
 	protected $total = 0;		// total items
 	protected $perPage = 10;	// items per page
 	protected $p = NULL;		// current page
 	protected $frameSize = 7;	//
 	protected $data = array();

 	public function getPages()
 	{
 		$this->construct();
 		return $this->data;
 	}

 	public function setup($currentPage = 1, $total = 0, $perPage = 0, $frameSize = 0)
 	{
		if (isset($currentPage))
		{
			if ($currentPage < 1)
			{
				$currentPage = 1;
			}
			$this->setCurrentPage($currentPage);
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

 	public function getLimit()
 	{
 		return $this->perPage;
 	}

 	public function getOffset()
 	{
 		return (($this->getCurrentPage()-1) * $this->perPage);
 	}

 	protected function construct()
 	{
 		if ($this->total <= $this->perPage)
		{
			return;
		}

		$allPages = ceil($this->total / $this->perPage);

		$p = $this->getCurrentPage();
		
		if ($p < 1)
		{
			$p = 1;
		}
		elseif ($p >= $allPages)
		{
			$p = $allPages;
		}
		if ($this->frameSize > $allPages)
		{
			$this->frameSize = $allPages;
		}

		$htis->data = array (
			'pages' => array ()
		);

		if ($p <= ceil($this->frameSize / 2))
		{
			//echo 'left';
			for ($i = 1; $i <= $this->frameSize; $i++)
			{
				$this->data['pages'][$i] = $this->buildPage($i, $p, 0);
			}
			//если в левой части фрейма и страниц больше чем влазит во фрейм
			if ($allPages > $this->frameSize)
			{
				$this->data['pages'][$i-1]['has_more'] = $i;
				$this->data['pages'][$i-1]['has_more_url'] = $this->fixUrl( RequestInfo::hrefChange('',array ($this->getPageVar() => $i,'submit' => '')) );
				$this->data['pages'][$i-1]['last_url'] = $this->fixUrl( RequestInfo::hrefChange('',array ($this->getPageVar() => $allPages)) );
				$this->data['pages'][$i-1]['last_num'] = $allPages;
			}
		}
		elseif ($p > ($allPages - (ceil($this->frameSize / 2))))
		{
			//echo 'right';
			$start_from = ($allPages + 1 - $this->frameSize);
			for ($i = $start_from; $i <= $allPages; $i++)
			{
				$this->data['pages'][$i] = $this->buildPage($i, $p, $start_from);
			}

		}
		else
		{

			$start_from = ($p - (floor($this->frameSize / 2)));
			$end_to =  (p + (floor($this->frameSize / 2)));

			$end_to = $start_from + $this->frameSize ;
			//echo 'else '.$start_from." to ".$end_to;

			for ($i = $start_from; $i < $end_to ; $i++)
			{
				$this->data['pages'][$i] = $this->buildPage($i, $p, $start_from);
			}
			//если до конца больше фрейма и в нем нет последней страницы
			if ( $allPages > $i )
			{
				$this->data['pages'][$i-1]['has_more'] = $i;
				$this->data['pages'][$i-1]['has_more_url'] = $this->fixUrl( RequestInfo::hrefChange('',array ($this->getPageVar() => $i,'submit' => '')) );
				$this->data['pages'][$i-1]['last_url'] = $this->fixUrl( RequestInfo::hrefChange('',array ($this->getPageVar() => $allPages)) );
				$this->data['pages'][$i-1]['last_num'] = $allPages;

			}
		}

		if ($p > 1)
		{
			$this->data['prev_page'] = $this->fixUrl( RequestInfo::hrefChange('', array (
				$this->getPageVar() => ($p -1
			), 'submit' => ''))
			)
			;
		}
		if ($p <= ($allPages -1))
		{
			$this->data['next_page'] = $this->fixUrl( RequestInfo::hrefChange('', array (
																			  $this->getPageVar() => ($p +1
																			), 'submit' => ''))
			);
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
			$page['has_less_url'] = $this->fixUrl( RequestInfo::hrefChange('',array ($this->getPageVar() => $i-1)) );
			$page['first_url'] = $this->fixUrl( RequestInfo::hrefChange('',array ($this->getPageVar() => 1)) );
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
			if ($this->getPageVar())
			{
				$this->p = intval(RequestInfo::get($this->getPageVar()));
			}
			else
			{
				$this->p = self::DEFAULT_PAGE;
			}
		}
		return $this->p;
	}
	public function setCurrentPage($pageNo)
	{
		$this->p = $pageNo;
		return $this;
	}
 }
?>