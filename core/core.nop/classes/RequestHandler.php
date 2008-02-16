<?php
/**
 *
 * FIXME:
 * lucky@npj. ���������, � ������� ��������� *RequestHanlder
 * TODO:
 * lucky@npj: merge � BasicRequestHandler ��� �����
 *
 �������� ���������� �������. 
 ����� ���������� ������ ����.

 ===================

 !!!!!! �����������������

 * MapHandler( $url ) -- ����� ����������� �� ������ ������ ������� � ����� ������������.
 ����� � �������-������� ������������� � �����������.
 ����:
 $url -- ������ ������ ������ �����: catalogue/trees/pice/qa
 �����:
 $this->context_type    = "site" or "page"
 $this->context_address = "about/news"
 $this->context -- instance ������ PetardePage ��� ������ �� RH
 $this->handler -- ��� ����� �����������. ��������, ������, ���� �� ����� �����������.
 $this->params_string -- ������, ������� ������ ������
 $this->params -- ������, ������� ������ ������, �������� �� ������


 =======================================================================      
 */

/**
 * ��������� ������� �����
 */
class BasicPageDomain
{
	var $possible_paths = NULL;
	var $handler = NULL;
	var $path = NULL;
	var $url = NULL;
	var $config = array();

	function BasicPageDomain()
	{
	}

	function initialize(&$ctx, $config=NULL)
	{
		$this->rh =& $ctx;
		if (isset($config)) $this->config = array_merge($this->config, $config);
	}

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
			$page =& new $page_cls();
			$page->domain =& $this;
			$page->url = $config['url'];
			$page->path = $config['path'];
			$page->params = $this->getParams($page->url, $page->path);
			$this->rh->_onCreatePage($page,$config);
			$page->initialize($this->rh, $config['config']);
		}

		return $page;
	}

}


/**
 * ����� ContentPageDomain -- �������� � ������ ��������
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
		if ($res == 'content') $res = 0;
		return $res;
	}

	function &find($criteria=NULL)
	{
		if (empty($criteria)) return False; // FIXME: lucky@npj -- ������� ��� ��������?

		$this->rh->useClass('models/Content');
		$content =& new Content();
		$content->initialize($this->rh);

		$where = array();
		if (isset($criteria['url']))
		{
			$url = $criteria['url'];
			$possible_paths = $this->getPossiblePaths($url);
			$where[] = '_path IN ('.$content->buildValues($possible_paths). ')';
		}
		if (isset($criteria['class']))
		{
			$where[] = 'mode='.$content->quote($this->getModeByPageClass($criteria['class']));
		}
		$where = implode(" AND ", $where);

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

class ModuleContentPageDomain extends BasicPageDomain
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
		if ($res == 'content') $res = 0;
		return $res;
	}

	function &find($criteria=NULL)
	{
		if (empty($criteria)) return False; // FIXME: lucky@npj -- ������� ��� ��������?

		$this->rh->useClass('models/Content');
		$content =& new Content();
		$content->initialize($this->rh);

		$where = array();
		if (isset($criteria['url']))
		{
			$url = $criteria['url'];
			$possible_paths = $this->getPossiblePaths($url);
			$where[] = '_path IN ('.$content->buildValues($possible_paths). ')';
		}
		if (isset($criteria['class']))
		{
			$where[] = 'mode='.$content->quote($this->getModeByPageClass($criteria['class']));
		}
		$where = implode(" AND ", $where);

		$content->load($where);
		$data = $content->data[0];

		if (!empty($data))
		{
			$page_cls = $this->getPageClassByMode($data['mode']);
			$config = array (
			'config' => $data,
			'path' => $data['_path'],
			'url' => $url,
			);
			if ($config['page'] =& $this->rh->useModule('pages/'.$page_cls))
			{
				if ($this->handler = &$this->buildPage($config))
				{
					//var_dump($this->handler->plugins);
					return True;
				}
			}
		}
		return False;
	}

	function &buildPage($config)
	{
		$page =& $config['page'];
		$page->domain =& $this;
		$page->url = $config['url'];
		$page->path = $config['path'];
		$page->params = $this->getParams($page->url, $page->path);
		$this->rh->_onCreatePage($page,$config);
		$page->initialize($this->rh, $config['config']);

		return $page;
	}

}


/**
 * ����� HanlderDomain -- �������� ����� ���������
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
				//���� ������� ����� �����������
				if (isset($this->handlers_map[$up]))
				{
					$_handler = $this->handlers_map[$up];
				}
				//���� ��� ������ �� ������
				else if ($this->handlers_map[$up."/"][strlen($this->handlers_map[$up."/"])-1]=="*")
				{
					$_handler = $url;
				}
				//������� ������� �� ���� ������� (������ � ������)
				elseif ($this->handlers_map[$up."/"])
				{
					$_handler = $this->handlers_map[$up."/"];
				}

				/*
				* �������� ������� ����������� �� �����
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
 * ����� HanlderDomain -- �������� ����� ���������
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
		* �������� ������� ����������� �� �����
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
 * ����� HanlderPageDomain -- �������� ����� ������� �������
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
				//���� ������� ����� �����������
				if (isset($this->handlers_map[$up]))
				{
					$_handler = $this->handlers_map[$up];
				}
				//���� ��� ������ �� ������
				else if ($this->handlers_map[$up."/"][strlen($this->handlers_map[$up."/"])-1]=="*")
				{
					$_handler = $url;
				}
				//������� ������� �� ���� ������� (������ � ������)
				elseif ($this->handlers_map[$up."/"])
				{
					$_handler = $this->handlers_map[$up."/"];
				}

				/*
				* �������� ������� ����������� �� �����
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
		* �������� ������� ����������� �� �����
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



class ModuleHanlderPageDomain extends BasicPageDomain
{

	function findByUrl($url)
	{
		$possible_paths = $this->getPossiblePaths($url);

		foreach ($possible_paths as $up)
		{
			if (isset($this->handlers_map[$up]) || isset($this->handlers_map[$up."/"]))
			{
				//���� ������� ����� �����������
				if (isset($this->handlers_map[$up]))
				{
					$_handler = $this->handlers_map[$up];
				}
				//���� ��� ������ �� ������
				else if ($this->handlers_map[$up."/"][strlen($this->handlers_map[$up."/"])-1]=="*")
				{
					$_handler = $url;
				}
				//������� ������� �� ���� ������� (������ � ������)
				elseif ($this->handlers_map[$up."/"])
				{
					$_handler = $this->handlers_map[$up."/"];
				}

				/*
				* �������� ������� ����������� �� �����
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

/*					$config = array (
						
						'config' => $data,
						'path' => $data['_path'],
						'url' => $url,
					);*/
					if ($config['page'] =& $this->rh->useModule('pages/'.$page_cls))
					{
						if ($this->handler = &$this->buildPage($config))
						{
							//var_dump($this->handler->plugins);
							return True;
						}
					}


					/*if ($this->rh->FindScript("classes/controllers", $page_cls))
					{
						$this->rh->UseClass("controllers/".$page_cls);
						if ($this->handler = &$this->buildPage($config))
						{
							return True;
						}
					}*/
				}
			}
		}
		return False;
	}

	function &buildPage($config)
	{
		$page =& $config['page'];
		$page->domain =& $this;
		$page->url = $config['url'];
		$page->path = $config['path'];
		$page->params = $this->getParams($page->url, $page->path);
		$this->rh->_onCreatePage($page,$config);
		$page->initialize($this->rh, $config['config']);

		return $page;
	}
	
	function findByClass($page_cls)
	{
		/*
		* �������� ������� ����������� �� �����
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
	var $fixtures = array();
	var $use_fixtures = False;

	function Error($msg)
	{
		trigger_error($msg, E_USER_ERROR);
	}

	function RequestHandler($config_path='')
	{
		parent::BasicRequestHandler($config_path);

		$this->useClass('models/DBConfig');
		$c =& new DBConfig();
		$c->initialize($this);
		$c->load();
		config_joinConfigs($this, $c->data);
	}

	function useFixture($type, $name)
	{

		if (!array_key_exists($name, $this->fixtures))
		{
			if ($s = $this->FindScript( $type, $name, false, -1, 'yml'))
			{
				if (!class_exists('Spyc')) $this->useLib('spyc');
				$this->fixtures[$name] = Spyc::YAMLLoad($s);
			}
			else
			if ($s = $this->FindScript( $type, $name, false, -1, 'php'))
			{
				$tpl =& $this->tpl;
				$this->fixtures[$name] = include $s;
			}
			else
			{
				$this->fixtures[$name] = NULL;
			}
		}
		return isset($this->fixtures[$name])
		? $this->fixtures : NULL;

	}

	function useModule($name, $type=NULL)
	{
		$this->useClass('ModuleLoader');
		$o =& new ModuleLoader();
		$o->initialize($this);
		$o->load($name);
		return $o->data;
	}

	function _onCreatePage(&$page)
	{
	}

	function &getPageDomains()
	{
		if (!isset($this->page_domains))
		{
			$this->page_domains = array();
			/*
			* �������� ����� ���� � Page-���������
			*/
			$hpc =& new HanlderPageDomain();
			$hpc->initialize($this);
			$hpc->handlers_map =& $this->handlers_map;
			$this->page_domains[] =& $hpc;

			/*
			* �������� ����� ���� � ���������
			*/
			$hc =& new HanlderDomain();
			$hc->initialize($this);
			$hc->handlers_map =& $this->handlers_map;
			$this->page_domains[] =& $hc;

			/*
			* �������� ����� ������ � ������� �������
			*/
			$mpc =& new ModuleContentPageDomain();
			$mpc->initialize($this);
			$this->page_domains[] =& $mpc;

			/*
			* �������� ����� ���� � ������� �������
			*/
			$cpc =& new ContentPageDomain();
			$cpc->initialize($this);
			$this->page_domains[] =& $cpc;

			/*
			* �������� ����� ���� � ������� �������
			*/
			$smc =& new SitemapDomain();
			$smc->initialize($this);
			$this->page_domains[] =& $smc;
		}
		return $this->page_domains;
	}


	function MapHandler($url)
	{
//		$this->debug->MileStone();

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
		$cls = strtolower($criteria['class']);
		$url = $criteria['url'];
		
		if (isset($url) && isset($this->url2page[$url]))
		return $this->url2page[$url];

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
			if (!isset($page_domains)) $page_domains = $this->getPageDomains();
			foreach ($page_domains as $page_domain)
			{
				if (True === $page_domain->find($criteria))
				{
					$page =& $page_domain->handler;
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

	/** ������� �������� �� ������ �������� */
	function &getPageByContentType($cls)
	{
		return $this->findPage(array('class'=>$cls));
	}

	/* ����������� Execute
	*
	* @params $this->handler -   ����� ����������� �������� �������� ������ �����
	*/
	function Execute( $handler="", $type="" )
	{
		$this->UseClass("Upload");
		$this->upload=&new Upload($this, $this->project_dir."files/",'', 'files/');

		//�� ������ ����� � ��� ���� ����
		$this->tpl->setRef("node", $this->data);

		$this->page->handle();
		$this->page->rend();
		$this->showSiteMap();
		//$type_handler = $this->CheckAccess( $type, $handler );

		//return RequestHandler::Execute( $type_handler );
	}

	// lucky@npj: ��� ���������� ������ ��������� �� HandlerPage
	// � ��������� rh
	function executeHandler($handler)
	{
		return BasicRequestHandler::Execute($handler);
	}

	function finalize() { return $this->End(); }

	/*
	* ���������� �� ����� ��������
	* TODO: � �� ���� �� ��� View ??
	*/
	function showSiteMap()
	{
		$this->tpl->set('print_href', $this->ri->hrefPlus('', array('print' => 1)));		

		$conf = $this->site_map[ $this->site_map_path ];

		if( is_array($conf) )
		{
			foreach($conf as $k=>$v)
			{
				//������ � ���������/����������/������������
				if( is_array($v) )
				{
					$_v = "";
					foreach($v as $v1)
					$_v .= $this->_ConstructValue($v1);
					$this->tpl->set( $k, $_v );
				}else
				//�������� ����������
				$this->tpl->set( $k, $this->_ConstructValue($v) );
			}
		}
		if ($this->ri->get('print'))
		{
			$this->tpl->set('html:print', '1');
		}
	}

	/*
	* ��������������� ������� ��� �������� (this->End())
	*/
	function _ConstructValue( $v )
	{
		if( $v[0]=="@" )     //��������� ������
		{
			return $this->tpl->parse( substr($v,1) );
		}
		elseif( $v[0]=="{" )     //�������� ��������� ����������
		{
			return $this->tpl->get( substr(substr($v,2), 0, -2) );
		}
		else    //�������� �����
		return $v;
	}

	function PrepareResult( $after_execute )
	{
		/*
		�� ���� ������ ���������, ����� �� ����������� ��������� � html.html
		��� �������������� ����-��������� ��������� ����������� ���� ����� � �����������.
		*/
		$template = isset($this->page->template)
		? $this->page->template
		: 'html.html';

		$tpl =& $this->tpl;
		if( !$tpl->Is("HTML:html") )
		{
			if (!$tpl->Is("HTML:body")) $tpl->Set("HTML:body", $after_execute);
			return $tpl->Parse( $template );
		}
		else
		return $tpl->get("HTML:html");
	}

}

?>

