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

require_once dirname(__FILE__).'/ConfigProcessor.php';
require_once dirname(__FILE__).'/BasicRequestHandler.php';
class RequestHandler extends BasicRequestHandler 
{
	/*
	 * ��������� ���� �� ���� ����
	 *
	 * lucky@npj: �������� �������� �������. DONT USE. :/
	 */
	function getPathByMode($mode)
	{
		$node = NULL;
		$db =& $this->db;

		if (!isset($this->mode_map[$mode])) 
		{
			$sql = "SELECT id, _path, mode  FROM ".$this->db_prefix."content "
				." WHERE _state=0 AND mode = ".$db->quote($mode); 
			$data = $this->db->queryOne($sql);
			$this->mode_map[$data['mode']] = $data;
		}

		return $this->mode_map[$mode]['_path'];
	}

	function MapHandler($url)
	{

		if ($url=="")
		{
			$this->handler = $this->handlers_map['/'];

			return true;
		}
		$this->debug->MileStone();
		$url_parts = explode("/", rtrim($url, "/"));

		$m = count ($url_parts);

		for ($i=$m; $i>0; $i--)
		{
			$up = implode ("/", $url_parts);

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
				if ($this->FindScript("classes/controllers", $_handler ))
				{
					$this->params = explode("/", trim(substr($url, strlen($up)+1)) );
					$this->handler = $_handler;
					return $this->handler;
				}
			}
			$max_path[] = $up;
			unset ($url_parts[count($url_parts)-1]);
		}

		/*
		 * �������� ����� ���� � ������� �������
		 */
		if (!$this->handler)
		{
			$this->useClass('models/Content');

			$content =& new Content($this);
			$where = ' AND _path IN ('.$content->buildValues($max_path). ')';
			$content->load($where);
			$this->data = $content->data[0];
			if (!empty($this->data))
			{
				//lucky@npj
				//
				$this->tpl->set('./', $this->tpl->get('/') . $data['_path']);
				$this->handler = $this->getPageClassByMode($this->data['mode']);
				$this->content_path = $data['_path'];
				$this->params = explode("/", trim(substr($url, strlen($this->data['_path'])+1)) );
				return $this->handler;
			}
		}

		//�� �� �� ����� ����������? ��������� 404.
		//������ ���� ����� ��������� ����������
		$this->handler = '_404';
		return true;

	}

	/** ������� �������� �� ������ �������� */
	function &getPageByContentType($cls)
	{
		$page = NULL;
		$this->useClass('models/Content');

		if (isset($this->cls2page[$cls])) return $this->cls2page[$cls];

		$mode = strtolower($cls);
		$content =& new Content($this);
		$where = ' AND mode = '.$content->quote($mode);
		$content->load($where);
		$node = $content->data[0];

		if (!empty($node))
		{
			$page_cls = $this->getPageClassByMode($node['mode']);
			$page =& $this->buildPageByClass($page_cls);
			$page->config =& $node;
			$page->initialize();
		}

		$this->cls2page[$cls] =& $page;
		return $page;
	}

	function getPageClassByMode($mode)
	{
		return ($mode ? ucfirst($mode) : "Content" ) .  "Page";
	}
	function &buildPageByClass($page_cls)
	{
		$this->UseClass("controllers/".$page_cls);
		$page =& new $page_cls($this);
		return $page;
	}

	/* ����������� Execute
	 *
	 * @params $this->handler -   ����� ����������� �������� �������� ������ �����
	 */
	function Execute( $handler="", $type="" )
	{
		$this->UseClass("Upload");
		$this->upload=&new Upload($this, "files/");

		$this->UseClass("controllers/".$this->handler);
		$this->controller =& new $this->handler($this);
		$this->controller->config = $this->data;

		//�� ������ ����� � ��� ���� ����
		$this->tpl->setRef("node", $this->data);

		$this->controller->initialize();
		$this->controller->handle();
		$this->showSiteMap();
		//$type_handler = $this->CheckAccess( $type, $handler );

		//return RequestHandler::Execute( $type_handler );
	}

	/*
	 * ���������� �� ����� ��������
	 * TODO: � �� ���� �� ��� View ??
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
			$this->tpl->set('html:print', '1');
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


} 

?>
