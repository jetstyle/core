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

	/** вернуть страницу по классу контента */
	function &getPageDomain()
	{
		return $this->pageDomain;
	}

	/* перегружаем Execute
	*
	* @params $this->handler -   класс контроллера страницы которому делаем хандл
	*/
	function Execute( $handler="", $type="" )
	{
		$this->UseClass("Upload");
		$this->upload=&new Upload($this, $this->project_dir."files/",'', 'files/');

		//до хандла чтобы в вью была нода
		$this->tpl->setRef("node", $this->data);

		$this->page->handle();
		$this->page->rend();
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
		$this->tpl->set('print_href', $this->ri->hrefPlus('', array('print' => 1)));		

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
		{
			$this->tpl->set('html:print', '1');
		}
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

	function PrepareResult( $after_execute )
	{
		/*
		На этом уровне проверяем, нужно ли оборачивать результат в html.html
		Для дополнительной пост-обработки окружения перегружать этот метод в наследниках.
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