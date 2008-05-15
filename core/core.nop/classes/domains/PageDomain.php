<?php
/**
 * @author lunatic lunatic@jetstyle.ru
 * 
 * Роутинг и поиск страниц
 * 
 */

class PageDomain
{
	protected $rh;
	protected $domains = array('Handler', 'Content');
	protected $pageDomains = null;						// domain Obj's
	protected $url2page = array();						// cache
	protected $cls2page = array();						// cache
	
	public function __construct(&$rh)
	{
		$this->rh = &$rh;
		if (!$this->rh->db)
			$this->domains = array('Handler');
	}
	
	public function &findPageByUrl($url)
	{
		$page = &$this->findPage(array('url' => $url));
		return $page;
	}
	
	public function &findPageByClass($class)
	{
		$page = &$this->findPage(array('class' => $class));
		return $page;
	}
	
	public function setDomains($domains)
	{
		$this->domains = $domains;
	}
	
	protected function &findPage($criteria, $pageDomains=NULL)
	{
		$page = NULL;
		$cls = strtolower($criteria['class']);
		$url = $criteria['url'];
		
		if (isset($url) && isset($this->url2page[$url]))
		{
			return $this->url2page[$url];
		}

		if (isset($cls) && isset($this->cls2page[$cls]))
		{
			return $this->cls2page[$cls];
		}

		if (isset($criteria['class']) && $criteria['class'] === '__self__')
		{
			$page =& $this->page;
		}
		else
		{
			if (!isset($pageDomains)) 
			{
				$pageDomains = &$this->getPageDomains();
			}
			foreach ($pageDomains as $pageDomain)
			{
				if (true === $pageDomain->find($criteria))
				{
					$page =& $pageDomain->handler;
					break;
				}
			}
		}
		
		if (isset($page))
		{
			$cls = strtolower(substr(get_class($page), 0, -strlen('Page')));
			$this->cls2page[$cls] =& $page;
			$this->url2page[$page->url] =& $page;
		}
		
		return $page;
	}
	
	protected function &getPageDomains()
	{
		if (empty($this->pageDomains))
		{
			$this->rh->useClass('domains/BasicPageDomain');
			foreach($this->domains AS $domain)
			{
				$className = ucfirst($domain).'PageDomain';
				$this->pageDomains[$domain] = new $className();
				$this->pageDomains[$domain]->initialize($this->rh);
			}
		}
		return $this->pageDomains;
	}
	
}

?>