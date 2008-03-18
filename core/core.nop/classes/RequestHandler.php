<?php

/*
  �������� ���������� �������. 
  ���������� ������������������ ��������� � �������������� ���������. 
  ������ ������ ��� ��������� ����� ����� ������������ �������.

  ===================

  //����� ���������

  * Requesthandler( $config_path = 'config/default.php' ) -- �����������,
					 ������ ������, ��������� �������������, ������ ������� ���������.
	 ����:
		- $config_path -- ���� �� ����� � ��������
	 �����:
		������� ���������: $rh->db, $rh->tpl, $rh->debug

  * Handle ( $ri=false ) -- ������������ �������� ������������������ ��������� �������.
	 ����:
		- $ri -- ���� ������, ������ ������ RequestInfo
		kuso@npj: ������������ �� ��������, ��� ��������� �� �������, � ������.
					 ���������� -- � ������������� ������
	 �����:
		������ � ������������ ������.

  * InitPrincipal () -- ������������� ����������. ������� �� ����������!
	 ����:
		������
		kuso@npj: imho -- ��� ����������
	 �����:
		������
		kuso@npj: imho -- ������ �� ������ ������-���������� �� Principal

  * MapHandler( $url ) -- ����� ����������� �� ������ ������ ������� � ����� ������������.
								  ����� � ������-������� ������������� � �����������.
	 ����:
		$this->handlers_map -- ���, �������� � ������������ ������� �����������
		$url -- ������ ������ ������ �����: catalogue/trees/pice/qa
	 �����:
		$this->handler - ��� ����� �����������. ��������, ������, ���� �� ����� �����������.
		$this->params_string -- ������, ������� ������ ������
		$this->params -- ������, ������� ������ ������, �������� �� ������

  * _UrlTrail(&$A,$i) -- ��������� ���������� �� ������� ������ ��� �����������. ��� ����������� �������������.
	 ����:
		- $A -- ������, ������ ������ �������, �������� �� ������
		- $i -- ������, ������� � �������� ����� ������������ �������
		kuso@npj: ����� ����� �������� ��������� "��������� �������"
		- $URL_SEPARATED (?)
		- $start_index
	 �����:
		$this->params
		$this->params_string

  * InitEnvironment() -- ���������� ����������� ���������. �� ������ ������ ����. ����������� � �����������.
	 ����: 
		������
	 �����:
		$this->db, $this->tpl, $this->debug

  * Execute( $handler='' ) -- ������ ���������� ����������� �� ����������.
	 ����:
		- $handler -- �������� ������� ���������� ����
		kuso@npj: � ���� �� ���� �������� ������ $handler, $params, $principal
					 ������� ��������� �� �������������.
					 ���� ������� ������ $handler, �� $handler, $params ������� �� $this->..
		zharik: ��������� ���� ���������� ������ $handler. ��������� ������� �� ���� ��������� ������������.
		kuso@npj: ��
	 �����:
		$this->tpl->VALUES['HTML:body'] ��� $this->tpl->VALUES['HTML:html']

  * PrepareResult () -- ����-��������� ����������� ������.
			���� $this->tpl->VALUES['HTML:html'] �����, �� ����������� $this->tpl->VALUES['HTML:body'] � html.html.
	 ����:
		$this->tpl->VALUES['HTML:body'] ��� $this->tpl->VALUES['HTML:html']
	 �����:
		������ � ������������ ������

  //����� ������

  * FindScript ( $type, $name, $level=false, $dr=-1, $ext = 'php' ) -- ���� ������ �� ������� ��������.
	 ����:
		$type -- ��������� �������, �������� classes, handlers, actions � ��.
		$name -- ������������� ��� ����� � �������� ������������, ��� ���������� 
		$level -- ������� �������, ������� � �������� ����� ������ ����
					 ���� �� �����, ������ ������ ������ ����������
		$dr -- ����������� ������, ��������� �������� : -1,0,+1
		$ext -- ���������� �����, ������ �� �����������
		$this->DIRS -- ������ �������� ���������� ��� ������� ������ �������,
		  ��� ������� ������ ����� ���� ������:
		  $dir_name -- ������, ��� �������� ����������
		  array( $dir_name, $TYPES ):
			 $dir_name -- ������, ��� �������� ����������
			 $TYPES -- ������������, ����� ���� �� ������ ����
	 �����:
		������ ��� �������, ������� ����� �������� � include()
		false, ���� ������ �� ������

  * FindScript_( $type, $name, $level=false, $dr=-1, $ext = 'php' ) -- �� ��, ��� � FindScript, 
				  �� � ������ �� ����������� ����� ������������ � �������.

  * UseScript( $type, $name, $level=false, $dr=-1, $ext = 'php' ) -- �� ��, ��� � FindScript_, 
				  �� ������������� �������� ������

  * UseClass( $name, $level=0, $dr=1, $ext = 'php' ) -- �� ��, ��� � UseScript, ��
				$type='classes', �������� ������ � 0-�� ������ �����

  * UseLib( $library_name, $file_name="" ) -- ���������� ���������� �� �������� /lib/

  * End() -- ������� ���������� ������

  * Redirect( $href ) -- �������� �� ��� ��������
	 ����:
		- $href -- ����������� ��� (�� "��������������"), ��������, ��������� $ri->Href( "/" );

  //��������������� �������

  * _FuckQuotes (&$a) -- ������� ������������ � ������� � ���� ������������ � �� �������� ����������.
	 ����:
		- $a -- ������ �� ������, ������� ����� ����������
	 �����:
		������������ ������ $a.

  * _SetDomains () -- �������, ����������� ���� *_domain, ����� �������� ����� � ������ ����
	 ���������:
		- $this->base_domain
		- $this->current_domain
		- $this->cookie_domain

 */

require_once JS_CORE_DIR . 'classes/ConfigProcessor.php';

class RequestHandler extends ConfigProcessor {

	//���������� �� ������� ������ ��� �����������
	var $params = array ();
	var $params_string = "";

	//���������� �� �������� �����������
	var $handler = ''; // site/handlers/{$handler}.php
	var $handler_full = false; // =/home/..../site/handlers/{$handler}.php

	var $fixtures = array ();
	var $use_fixtures = False;

	public function __construct($config_path = 'config/default.php') {
		//�������� �������� ���� ������������
		if (is_object($config_path)) {
			config_joinConfigs($this, $config_path);
		} else
			if (@ is_readable($config_path)) {
				require_once ($config_path);
			} else {
				$uri = preg_replace("/\?.*$/", "", $_SERVER["REQUEST_URI"]);
				$page = $_REQUEST["page"];
				$uri = substr($uri, 0, strlen($uri) - strlen($page));
				$uri = rtrim($uri, "/") . "/setup";
				die("Cannot read local configurations. May be you should try to <a href='" . $uri . "'>run installer</a>, if any?");
			}

		//��������� base_url
		if (!isset ($this->base_url))
			$this->base_url = dirname($_SERVER["PHP_SELF"]) . (dirname($_SERVER["PHP_SELF"]) != '/' ? '/' : '');
		if (!isset ($this->base_dir))
			$this->base_dir = $_SERVER["DOCUMENT_ROOT"] . $this->base_url;
		if (!isset ($this->host_url))
			$this->host_url = strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0, strpos($_SERVER['SERVER_PROTOCOL'], '/'))) . '://' . $_SERVER['SERVER_NAME'] .
			 ($_SERVER['SERVER_PORT'] === '80' ? '' : ':' . $_SERVER['SERVER_PORT']);

		$this->_setDomains();

		//����������� �� ������
		if (get_magic_quotes_gpc()) {
			$this->_fuckQuotes($_POST);
			$this->_fuckQuotes($_GET);
			$this->_fuckQuotes($_COOKIE);
			$this->_fuckQuotes($_REQUEST);
		}

		//������� ������� ����������
		//TODO: ��� ��� ���������
		$this->init();
	}

	public function & getPageDomain() {
		return $this->pageDomain;
	}

	//�������� ������� ��������� �������
	public function handle($ri = false) {
		if ($ri)
		{
			$this->ri = & $ri;
		}

		if (!isset ($this->ri)) {
			//������������� $ri �� ���������
			$this->ri = & new RequestInfo($this); // kuso@npj: default RI ������ ���� � ����� ���������� ����
		}
		$this->url = $this->ri->GetUrl();

		//����������� �����������
		$this->mapHandler($this->url);

		//���������� ���������
		$this->initEnvironment();

		//���������� �����������
		$this->execute();
		return $this->tpl->Parse("html.html");
	}

	// ������, ����������� ��� RH
	public function useClass($name, $level = 0, $dr = 1, $ext = 'php', $withSubDirs = false, $hideExc = false) {
		$this->useScript("classes", $name, $level, $dr, $ext, $withSubDirs, $hideExc);
	}

	// ������, ����������� ��� RH
	public function useModel($name, $level = 0, $dr = 1, $ext = 'php', $withSubDirs = false, $hideExc = false) {
		$this->useScript("classes/models", $name, $level, $dr, $ext, $withSubDirs, $hideExc);
	}

	public function useLib($library_name, $file_name = "") {
		// library is near core, library have no levels
		//$direction = 0;
		// lucky@npj: ��� ��� -- ��� ������. ������� � ����������, ����� � core
		$direction = 1;
		$level = 0;
		// usually library have one file to link itself
		if ($file_name == "")
			$file_name = $library_name;
		$ext = "php";

		$this->useScript($this->lib_dir, $library_name . "/" . $file_name, $level, $direction, $ext);
	}

	public function useModule($name, $type = NULL) {
		$this->useClass('ModuleLoader');
		$o = & new ModuleLoader();
		$o->initialize($this);
		$o->load($name);
		return $o->data;
	}
	
	public function getPluralizeDir($classname) {
		$this->UseClass("Inflector");
		$words = preg_split('/[A-Z]/', $classname);
		$last_word = substr($classname, -strlen($words[count($words) - 1]) - 1);
		$last_word = strtolower($last_word);
		return Inflector :: pluralize($last_word);
	}

	public function redirect($href) {
		if (strpos($href, "http://") !== 0)
			$href = $this->ri->_host_prot . $href;

		header("Location: $href");
		exit;
	}
	
	protected function execute() {
		$this->page->handle();
		$this->page->rend();
		$this->showSiteMap();
	}

	protected function init() {
		$this->initDebug();
		$this->initDBAL();
		$this->initTPL();
		$this->initMessageSet();
		$this->initUpload();
		$this->initPrincipal();
		
		Debug :: trace("RH: constructor done");
	}

	protected function initUpload()
	{
		Debug :: mark("upload");
		$this->UseClass("Upload");
		$this->upload = & new Upload($this, $this->project_dir . "files/", '', 'files/');
		Debug :: trace("RH: created Upload", null, "upload");
	}
	
	protected function initDebug() {
		//�������������� ������� �������
		if ($this->enable_debug) {
			$this->useClass("Debug");
			Debug :: init();
		} else {
			$this->useClass("DebugDummy");
		}
	}

	protected function initDBAL() {
		Debug :: mark("db");
		if ($this->db_al) {
			$this->UseClass("DBAL");
			//			$this->db =& new DBAL( $this );
			$this->db = & DBAL :: getInstance($this);
			if ($this->db_set_encoding) {
				$this->db->Query("SET NAMES " . $this->db_set_encoding);
			}
		}
		Debug :: trace("RH: created DBAL", "db", "db");
	}

	/**
	 *  �������� �������������
	 *  TODO: ������ ��������� ���������� �� ������ ����� ���������������
	 */
	protected function initTPL() {
		// ��������� tpl ���� ���
		if ($this->tpl_disable === true) {
			Debug :: trace("RH: TPL DISABLED");
		} else {
			Debug :: mark("tpl");
			$this->UseClass("TemplateEngine");
			$this->tpl = & new TemplateEngine($this);
//			$this->tpl->set('/', $this->base_url);
			Debug :: trace("RH: created TPL", "tpl", "tpl");
		}
	}

	protected function initMessageSet() {
		if ($this->msg_disable === true) {
			Debug :: trace("RH: MSG DISABLED");
		} else {
			Debug :: mark("msg");
			$this->UseClass("MessageSet");
			$this->msg = & new MessageSet($this);
			$this->tpl->msg = & $this->msg;
			Debug :: trace("RH: created MSG", null, "msg");
		}
	}

	//������������� ����������.
	protected function initPrincipal() {
		$this->principal = & new Principal($this, $this->principal_storage_model, $this->principal_security_models);

		if ($this->principal->Identify() > PRINCIPAL_AUTH) {
			$this->principal->Guest();
		}
	}

	// �������, ����������� ���� *_domain, ����� �������� ����� � ������ ����
	protected function _setDomains() {
		if (!isset ($this->base_domain))
			$this->base_domain = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
		if (!isset ($this->current_domain))
			$this->current_domain = preg_replace("/^www\./i", "", $_SERVER["HTTP_HOST"]);
		if (!isset ($this->cookie_domain))
			// lucky@npj: see http://ru.php.net/manual/ru/function.setcookie.php#49350
			$this->cookie_domain = strpos($this->base_domain, '.') === false ? false : "." . $this->base_domain;

		session_set_cookie_params(0, "/", $this->cookie_domain);
	}

	protected function mapHandler($url) {
		$this->pageDomain = new PageDomain($this);

		if ($page = & $this->pageDomain->findPageByUrl($url)) {
			$this->page = & $page;
			$this->data = $page->config;
			$this->params = $page->params;
			$this->path = $page->path;
		} else {
			$this->page = & $this->pageDomain->findPageByClass('_404');
		}
	}

	//���������� ������������ ���������.
	protected function initEnvironment() {
		// �� ���� ������ �������� ������ ���������� ����� ��������
		// ��������� ���������� "/", ��������������� ����� �����
		$this->tpl->set("/", $this->ri->Href(""));
//		$this->tpl->set("lib", $this->ri->Href($this->lib_href_part) . "/");
//		$this->tpl->setRef("SITE", $this);
	}

	

	

//	function error($msg) {
//		trigger_error($msg, E_USER_ERROR);
//	}

	public function useFixture($type, $name) {

		if (!array_key_exists($name, $this->fixtures)) {
			if ($s = $this->FindScript($type, $name, false, -1, 'yml')) {
				if (!class_exists('Spyc'))
					$this->useLib('spyc');
				$this->fixtures[$name] = Spyc :: YAMLLoad($s);
			} else
				if ($s = $this->FindScript($type, $name, false, -1, 'php')) {
					$tpl = & $this->tpl;
					$this->fixtures[$name] = include $s;
				} else {
					$this->fixtures[$name] = NULL;
				}
		}
		return isset ($this->fixtures[$name]) ? $this->fixtures : NULL;

	}
//
	public function _onCreatePage(& $page) {
	}

	protected function showSiteMap() 
	{
		//TODO: extract and document setting of print params
		//nop
		$this->tpl->set('print_href', $this->ri->hrefPlus('', array (
			'print' => 1
		)));

		$conf = $this->site_map[$this->site_map_path];

		$this->_showSiteMapPath($conf);
		
		//nop: again print params
		if ($this->ri->get('print')) 
		{
			$this->tpl->set('html:print', '1');
		}
	}
	
	/**
	 * ��������� ���� ������ ��������
	 * ������ ����� ���� ������ extract class
	 * nop
	 */
	public function _showSiteMapPath($conf=array())
	{
		if (!empty($conf)) 
		{
			foreach ($conf as $k => $v) 
			{
				//������ � ���������/����������/������������
				if (is_array($v)) 
				{
					$_v = "";
					foreach ($v as $v1)
						$_v .= $this->_constructValue($v1);
					$this->tpl->set($k, $_v);
				} 
				else
					//�������� ����������
					$this->tpl->set($k, $this->_constructValue($v));
			}
		}	
	}

	/*
	* ��������������� ������� ��� �������� (this->End())
	*/
	protected function _constructValue($v) {
		if ($v[0] == "@") //��������� ������
			{
			return $this->tpl->parse(substr($v, 1));
		}
		elseif ($v[0] == "{") //�������� ��������� ����������
		{
			return $this->tpl->get(substr(substr($v, 2), 0, -2));
		} else //�������� �����
			return $v;
	}

	protected function prepareResult($after_execute) {
		/*
		�� ���� ������ ���������, ����� �� ����������� ��������� � html.html
		��� �������������� ����-��������� ��������� ����������� ���� ����� � �����������.
		*/
		$template = isset ($this->page->template) ? $this->page->template : 'html.html';

		$tpl = & $this->tpl;
		if (!$tpl->is("HTML:html")) {
			if (!$tpl->is("HTML:body"))
				$tpl->set("HTML:body", $after_execute);
			return $tpl->parse($template);
		} else
			return $tpl->get("HTML:html");
	}

	// ������� "����������" ����� �� ���������������� �������
	// � ���� ������������ � �� ��������
	protected function _fuckQuotes(& $a) {
		if (is_array($a))
			foreach ($a as $k => $v)
				if (is_array($v))
					$this->_FuckQuotes($a[$k]);
				else
					$a[$k] = stripslashes($v);
	}

}
?>