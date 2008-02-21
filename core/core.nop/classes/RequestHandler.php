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
	var $fixtures = array();
	var $use_fixtures = False;

	function Error($msg)
	{
		trigger_error($msg, E_USER_ERROR);
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

	
	function mapHandler($url)
	{
		$this->pageDomain = new PageDomain($this);
		
		if ($page = &$this->pageDomain->findPageByUrl($url))
		{
			$this->page =& $page;
			$this->data = $page->config;
			$this->params = $page->params;
			$this->path = $page->path;
		}
		else
		{
			$this->page =& $this->pageDomain->findPageByClass('_404');
		}
	}

	/** ������� �������� �� ������ �������� */
	function &getPageDomain()
	{
		return $this->pageDomain;
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