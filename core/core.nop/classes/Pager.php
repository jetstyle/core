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
 	protected $total = 0;		// total items
 	protected $perPage = 10;	// items per page
 	protected $p = 1;			// current page
 	protected $frameSize = 7;	//
 	protected $data = array();
 	protected $rh = null;

 	public function __construct(&$rh)
 	{
 		$this->rh = &$rh;
 	}

 	public function getPages()
 	{
 		$this->construct();
 		return $this->data;
 	}

 	public function setup($currentPage = 1, $total = 0, $perPage = 0, $frameSize = 0)
 	{
 		$this->p = $currentPage;
 		if ($this->p < 1)
		{
			$this->p = 1;
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
 		return (($this->p-1) * $this->perPage);
 	}

 	protected function construct()
 	{
 		if ($this->total <= $this->perPage)
		{
			return;
		}

		$allPages = ceil($this->total / $this->perPage);

		if ($this->p < 1)
		{
			$p = 1;
		}
		elseif ($this->p >= $allPages)
		{
			$p = $allPages;
		}
		else
		{
			$p = $this->p;
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
				$this->data['pages'][$i-1]['has_more_url'] = $this->fixUrl( $this->rh->ri->HrefPlus('',array ('p' => $i,'submit' => '')) );
				$this->data['pages'][$i-1]['last_url'] = $this->fixUrl( $this->rh->ri->HrefPlus('',array ('p' => $allPages)) );
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
				$this->data['pages'][$i-1]['has_more_url'] = $this->fixUrl( $this->rh->ri->HrefPlus('',array ('p' => $i,'submit' => '')) );
				$this->data['pages'][$i-1]['last_url'] = $this->fixUrl( $this->rh->ri->HrefPlus('',array ('p' => $allPages)) );
				$this->data['pages'][$i-1]['last_num'] = $allPages;

			}
		}

		if ($p > 1)
		{
			$this->data['prev_page'] = $this->fixUrl( $this->rh->ri->HrefPlus('', array (
				'p' => ($p -1
			), 'submit' => ''))
			)
			;
		}
		if ($p <= ($allPages -1))
		{
			$this->data['next_page'] = $this->fixUrl( $this->rh->ri->HrefPlus('', array (
																			  'p' => ($p +1
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
					'url' => $this->fixUrl( $this->rh->ri->HrefPlus('',
					array (
						'p' => $i,
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
			$page['has_less_url'] = $this->fixUrl( $this->rh->ri->HrefPlus('',array ('p' => $i-1)) );
			$page['first_url'] = $this->fixUrl( $this->rh->ri->HrefPlus('',array ('p' => 1)) );
		}

		return $page;
 	}
 }
?>