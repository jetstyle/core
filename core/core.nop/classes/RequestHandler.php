<?php
/**
 *
 * FIXME:
 * lucky@npj. Временный, и надеюсь последний *RequestHanlder
 * TODO:
 * lucky@npj: merge с BasicRequestHandler ибо нефик
 *
 Основной обработчик запроса. 
 Здесь перегрузим разбор урла.

 ===================

 !!!!!! редокументировать

 * MapHandler( $url ) -- Выбор обработчика на основе строки запроса и карты обработчиков.
 Поиск в контент-таблице реализовывать в наследниках.
 ВХОД:
 $url -- строка адресу внутри сайта: catalogue/trees/pice/qa
 ВЫХОД:
 $this->context_type    = "site" or "page"
 $this->context_address = "about/news"
 $this->context -- instance класса PetardePage или ссылка на RH
 $this->handler -- имя файла обработчика. Возможно, пустое, если не нашли обработчика.
 $this->params_string -- строка, остаток строки адреса
 $this->params -- массив, остаток строки адреса, разбитый по слешам


 =======================================================================      
 */


/**
 * Коллекция страниц сайта
 */
class BasicPageDomain
{
	var $possible_paths = NULL;
	var $handler = NULL;
	var $path = NULL;
	var $url = NULL;

	function BasicPageDomain(&$rh) { $this->rh =& $rh; }

	function &find($criteria=NULL) { return False; }

	function getPossiblePaths($url)
	{
		if (!isset($this->possible_paths)) 
		{
			$this->possible_paths =& $this->buildPossiblePaths($url);
		}
		return $this->possible_paths;
	}

	function &buildPossiblePaths($url)
	{
		return $this->buildMaxPaths($url);
	}

	function buildMaxPaths($url)
	{
		$url_parts = explode("/", rtrim($url, "/"));
		$max_path = array();
		do 
			$max_path[] = implode ("/", $url_parts);
		while (array_pop($url_parts) && $url_parts);
		return $max_path;
	}

	function getParams($url, $path)
	{
		return explode("/", trim(substr($url, strlen($path)+1)) );
	}

	function &buildPage($config)
	{
		$page = NULL;

		$page_cls = $config['class'];
		if (class_exists($page_cls))
		{
			$page =& new $page_cls($this->rh);
			$page->config = $config['config'];
			$page->domain =& $this;
			$page->url = $config['url'];
			$page->path = $config['path'];
			$page->params = $this->getParams($page->url, $page->path);
			$this->rh->_onCreatePage($page,$config);
		}

		return $page;
	}

}


/**
 * Класс ContentPageDomain -- страницы в дереве контента
 */
class ContentPageDomain extends BasicPageDomain
{

	function getPageClassByMode($mode)
	{
		return isset($this->rh->mode_map[$mode]) 
			? $this->rh->mode_map[$mode]
			: (($mode ? implode('', array_map(ucfirst, explode('_', $mode))) : "Content" ) .  "Page");
	}
	function getModeByPageClass($cls)
	{
		$res = strtolower(trim(preg_replace('#([A-Z])#', '_\\1', $cls), '_'));
		return $res;
	}

	function &find($criteria=NULL)
	{
		if (empty($criteria)) return False; // FIXME: lucky@npj -- вернуть все страницы?

		$this->rh->useClass('models/Content');
		$content =& new Content($this->rh);

		$where = '';
		if (isset($criteria['url']))
		{
			$url = $criteria['url'];
			$possible_paths = $this->getPossiblePaths($url);
			$where .= ' AND _path IN ('.$content->buildValues($possible_paths). ')';
		}
		if (isset($criteria['class']))
		{
			$where .= ' AND mode='.$content->quote($this->getModeByPageClass($criteria['class']));
		}

		$content->load($where);
		$data = $content->data[0];

		if (!empty($data))
		{
			$page_cls = $this->getPageClassByMode($data['mode']);
			$config = array (
				'class' => $page_cls,
				'config' => $data,
				'path' => $data['_path'],
				'url' => $url,
			);
			if ($this->rh->FindScript("classes/controllers", $page_cls))
			{
				$this->rh->UseClass("controllers/".$page_cls);
				if ($this->handler = &$this->buildPage($config))
				{
					return True;
				}
			}
		}
		return False;
	}


}


/**
 * Класс HanlderDomain -- старинцы среди хендлеров
 */
class HanlderDomain extends BasicPageDomain
{

	function &find($criteria)
	{
		if (empty($criteria)) return False;

		if (!isset($criteria['url'])) return False;

		if (isset($criteria['url'])) 
			$url = $criteria['url'];

		$possible_paths = $this->getPossiblePaths($url);

		foreach ($possible_paths as $up)
		{
			if (isset($this->handlers_map[$up]) || isset($this->handlers_map[$up."/"]))
			{
				//если найдено точно соответсвие 
				if (isset($this->handlers_map[$up]))
				{
					$_handler = $this->handlers_map[$up];
				}
				//если мап многие ко многим
				else if ($this->handlers_map[$up."/"][strlen($this->handlers_map[$up."/"])-1]=="*")
				{
					$_handler = $url;
				}
				//каталог мапится на один хендлер (многие к одному)
				elseif ($this->handlers_map[$up."/"])
				{
					$_handler = $this->handlers_map[$up."/"];
				}

				/*
				 * Проверка наличия контроллера на диске
				 */
				if ($this->rh->FindScript("handlers", $_handler ))
				{
					$page_cls = 'HandlerPage';
					if ($this->rh->FindScript("classes/controllers", $page_cls))
					{
						$config = array (
							'class' => $page_cls,
							'config' => array (
								'handler'=>$_handler,
							),
							'path' => $data['_path'],
							'url' => $url,
						);
						$this->rh->UseClass("controllers/".$page_cls);
						if ($this->handler = &$this->buildPage($config))
						{
							return True;
						}
					}
				}
			}
		}
		return False;
	}

}

/**
 * Класс HanlderDomain -- старинцы среди хендлеров
 */
class SitemapDomain extends BasicPageDomain
{

	function &find($criteria)
	{
		if (empty($criteria)) return False;

		if (!isset($criteria['url'])) return False;
		if (isset($criteria['url'])) 
			$url = rtrim($criteria['url'], '/');

		/*
		 * Проверка наличия контроллера на диске
		 */
		if (isset($this->rh->site_map[$url]))
		{
			$page_cls = 'SiteMapPage';
			if ($this->rh->FindScript("classes/controllers", $page_cls))
			{
				$config = array (
					'class' => $page_cls,
					'config' => array (),
					'path' => '',
					'url' => '/'.$url,
				);
				$this->rh->UseClass("controllers/".$page_cls);
				if ($this->handler = &$this->buildPage($config))
				{
					return True;
				}
			}
		}
		return False;
	}

}


/**
 * Класс HanlderPageDomain -- старинцы среди классов страниц
 */
class HanlderPageDomain extends BasicPageDomain
{

	function findByUrl($url)
	{
		$possible_paths = $this->getPossiblePaths($url);

		foreach ($possible_paths as $up)
		{
			if (isset($this->handlers_map[$up]) || isset($this->handlers_map[$up."/"]))
			{
				//если найдено точно соответсвие 
				if (isset($this->handlers_map[$up]))
				{
					$_handler = $this->handlers_map[$up];
				}
				//если мап многие ко многим
				else if ($this->handlers_map[$up."/"][strlen($this->handlers_map[$up."/"])-1]=="*")
				{
					$_handler = $url;
				}
				//каталог мапится на один хендлер (многие к одному)
				elseif ($this->handlers_map[$up."/"])
				{
					$_handler = $this->handlers_map[$up."/"];
				}

				/*
				 * Проверка наличия контроллера на диске
				 */
				if (!empty($_handler))
				{
					$page_cls = $_handler;
					$config = array (
						'class' => $page_cls,
						'config' => array (),
						'path' => $up,
						'url' => $url,
					);
					if ($this->rh->FindScript("classes/controllers", $page_cls))
					{
						$this->rh->UseClass("controllers/".$page_cls);
						if ($this->handler = &$this->buildPage($config))
						{
							return True;
						}
					}
				}
			}
		}
		return False;
	}

	function findByClass($page_cls)
	{
		/*
		 * Проверка наличия контроллера на диске
		 */
		if (!empty($page_cls))
		{
			$config = array (
				'class' => $page_cls,
				'config' => array (),
				'path' => $this->rh->url,
				'url' => $this->rh->url,
			);
			if ($this->rh->FindScript("classes/controllers", $page_cls))
			{
				$this->rh->UseClass("controllers/".$page_cls);
				if ($this->handler = &$this->buildPage($config))
				{
					return True;
				}
			}
		}
		return False;
	}

	function &find($criteria)
	{
		if (empty($criteria)) return False;

		if (isset($criteria['url'])) return $this->findByUrl($criteria['url']);
		if (isset($criteria['class'])) return $this->findByClass($criteria['class']);
		return False;
	}

}

require_once dirname(__FILE__).'/ConfigProcessor.php';
require_once dirname(__FILE__).'/BasicRequestHandler.php';
class RequestHandler extends BasicRequestHandler 
{

	function _onCreatePage(&$page)
	{ 
	}

	function getPageDomains()
	{
		if (!isset($this->page_domains))
		{
			$this->page_domains = array();
			/*
			 * Пытаемся найти узел в хендлерах
			 */
			$hpc =& new HanlderPageDomain($this);
			$hpc->handlers_map =& $this->handlers_map;
			$this->page_domains[] =& $hpc;

			/*
			 * Пытаемся найти узел в таблице контент
			 */
			$cpc =& new ContentPageDomain($this);
			$this->page_domains[] =& $cpc;
		}
		return $this->page_domains;
	}

	function MapHandler($url)
	{
		$this->debug->MileStone();

		if ($page = &$this->findPage(array('url'=>$url)))
		{
			$this->page =& $page;
			$this->data = $page->config;
			$this->params = $page->params;
			$this->path = $page->path;
		}
		else
		{
			$this->page =& $this->findPage(array('class'=>'_404'));
		}
		return true;

	}

	function &findPage($criteria, $page_domains=NULL)
	{
		$page = NULL;
		if (!isset($page_domains)) $page_domains =& $this->getPageDomains();
		foreach ($page_domains as $page_domain)
		{
			if (True === $page_domain->find($criteria))
			{
				$page =& $page_domain->handler;
				break;
			}
		}
		return $page;
	}

	/** вернуть страницу по классу контента */
	function &getPageByContentType($cls)
	{
		$page = NULL;
		if (isset($this->cls2page[$cls])) return $this->cls2page[$cls];

		if ($page =& $this->findPage(array('class'=>$cls)))
		{
			$page->initialize();
		}

		$this->cls2page[$cls] =& $page;
		return $page;
	}

	/* перегружаем Execute
	 *
	 * @params $this->handler -   класс контроллера страницы которому делаем хандл
	 */
	function Execute( $handler="", $type="" )
	{
		$this->UseClass("Upload");
		$this->upload=&new Upload($this, "files/");

		//до хандла чтобы в вью была нода
		$this->tpl->setRef("node", $this->data);

		$this->page->initialize();
		$this->page->handle();
		$this->showSiteMap();
		//$type_handler = $this->CheckAccess( $type, $handler );

		//return RequestHandler::Execute( $type_handler );
	}

	// lucky@npj: для выполнения старых хендлеров из HandlerPage
	// в контексте rh
	function executeHandler($handler)
	{
		return BasicRequestHandler::Execute($handler);
	}
	/*
	 * Отработать по ключу сайтмапа
	 * TODO: А не дело ли это View ??
	 */
	function showSiteMap()
	{
		if($this->debug_show)	{
			$this->tpl->Set('DEBUG', $this->debug->getHtml());
		}

		$conf = $this->site_map[ $this->site_map_path ];

		if( is_array($conf) )
		{
			foreach($conf as $k=>$v)
			{
				//массив с шаблонами/значениями/инстркуциями
				if( is_array($v) )
				{
					$_v = "";
					foreach($v as $v1)
						$_v .= $this->_ConstructValue($v1);
					$this->tpl->set( $k, $_v );
				}else
					//значение переменной
					$this->tpl->set( $k, $this->_ConstructValue($v) );
			}
		}
		if ($this->ri->get('print'))
			$this->tpl->set('html:print', '1');
	}

	/*
	 * Вспомогательная функция для сайтмапа (this->End())
	 */
	function _ConstructValue( $v )
	{
		if( $v[0]=="@" )     //отпарсить шаблон
		{
			return $this->tpl->parse( substr($v,1) );
		}
		elseif( $v[0]=="{" )     //значение шаблонной переменной
		{
			return $this->tpl->get( substr(substr($v,2), 0, -2) );
		}
		else    //вставить текст
			return $v;
	}


} 

?>
