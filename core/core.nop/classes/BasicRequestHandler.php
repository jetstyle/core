<?php
/*
  �������� ���������� �������. 
  ���������� ������������������ ��������� � �������������� ���������. 
  ������ ������ ��� ��������� ����� ����� ������������ �������.

  ===================

  //����� ���������

  * BasicRequesthandler( $config_path = 'config/default.php' ) -- �����������,
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

class BasicRequestHandler extends ConfigProcessor {

	//���������� �� ������� ������ ��� �����������
	var $params = array();
	var $params_string = "";

	//���������� �� �������� �����������
	var $handler = ''; // site/handlers/{$handler}.php
	var $handler_full = false; // =/home/..../site/handlers/{$handler}.php

	//�����������
	function BasicRequestHandler( $config_path = 'config/default.php' )
	{

		//�������� �������� ���� ������������
		if (is_object($config_path))
		{
			config_joinConfigs($this, $config_path);
		}
		else
		if(@is_readable($config_path)) 
		{
			require_once($config_path);
		}
		else
		{
			$uri  = preg_replace("/\?.*$/", "",$_SERVER["REQUEST_URI"]);
			$page = $_REQUEST["page"];
			$uri  = substr( $uri, 0, strlen($uri)-strlen($page) );
			$uri  = rtrim( $uri, "/" )."/setup";
			die("Cannot read local configurations. May be you should try to <a href='".$uri."'>run installer</a>, if any?");
		}

		//��������� base_url
		if( !isset($this->base_url) )
			$this->base_url = dirname($_SERVER["PHP_SELF"]).( dirname($_SERVER["PHP_SELF"])!='/' ? '/' : '' );
		if( !isset($this->base_dir) )
			$this->base_dir = $_SERVER["DOCUMENT_ROOT"].$this->base_url;
		if (!isset($this->host_url) )
			$this->host_url = strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0,
				strpos($_SERVER['SERVER_PROTOCOL'], '/')))
				. '://'.$_SERVER['SERVER_NAME'].
				($_SERVER['SERVER_PORT'] === '80' ? '' : ':'.$_SERVER['SERVER_PORT']);

		$this->_SetDomains();

		//����������� �� ������
		if (get_magic_quotes_gpc()){
			$this->_FuckQuotes($_POST);
			$this->_FuckQuotes($_GET);
			$this->_FuckQuotes($_COOKIE);
			$this->_FuckQuotes($_REQUEST);
		}
		
		//������� ������� ����������
    //TODO: ��� ��� ���������
    $this->init();
	}
	
	private function init()
	{
    $this->initDebug();
    $this->initDBAL();
    $this->initTPL();
    $this->initMessageSet();
    
		Debug::trace("RH: constructor done");
	}
	
  private function initDebug()
  {
    //�������������� ������� �������
		if($this->enable_debug)
		{
			$this->useClass("Debug");
			Debug::init();
		}
		else
		{
			$this->useClass("DebugDummy");
		}
	}
	
	private function initDBAL()
  {
		Debug::trace("RH: creating DBAL");

		if ($this->db_al)
		{
			$this->UseClass("DBAL");
//			$this->db =& new DBAL( $this );
			$this->db =& DBAL::getInstance( $this );
			if($this->db_set_encoding)
			{
				$this->db->Query("SET NAMES ".$this->db_set_encoding);
			}
		}
  }

  /**
   *  �������� �������������
   *  TODO: ������ ��������� ���������� �� ������ ����� ���������������
   */
  private function initTPL()
  {
    // ��������� tpl � msg ���� ���
		if ($this->tpl_disable===true)
		{
			Debug::trace("RH: creating TPL : DISABLED");
		} else
		{
			Debug::trace("RH: creating TPL");
			$this->UseClass("TemplateEngine");
			$this->tpl =& new TemplateEngine( $this );
			$this->tpl->set( '/', $this->base_url );
		}
  }
  
  private function initMessageSet()
  {
    if ($this->msg_disable===true)
		{
			Debug::trace("RH: creating MSG : DISABLED");
		} else
		{
			Debug::trace("RH: creating MSG");
			$this->UseClass("MessageSet");
			$this->msg =& new MessageSet( $this );
			$this->tpl->msg =& $this->msg;
		}
  }

	// �������, ����������� ���� *_domain, ����� �������� ����� � ������ ����
	function _SetDomains()
	{
		if (!isset($this->base_domain))
			$this->base_domain    = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
		if (!isset($this->current_domain))
			$this->current_domain = preg_replace("/^www\./i", "", $_SERVER["HTTP_HOST"]);
		if (!isset($this->cookie_domain))
			// lucky@npj: see http://ru.php.net/manual/ru/function.setcookie.php#49350
			$this->cookie_domain = strpos($this->base_domain, '.') === false   ? false : ".".$this->base_domain;

		session_set_cookie_params(0, "/", $this->cookie_domain); 
	}  

	//�������� ������� ��������� �������
	function Handle( $ri=false )
	{
		if($ri)
			$this->ri =& $ri;

		if (!isset($this->ri))
		{
			//������������� $ri �� ���������
			$this->UseScript('classes','RequestInfo');
			$this->ri =& new RequestInfo($this); // kuso@npj: default RI ������ ���� � ����� ���������� ����
		}
		$this->url = $this->ri->GetUrl();

		//������������� ����������
		$this->InitPrincipal();
		//����������� �����������
		$this->MapHandler($this->url);

		//���������� ���������
		$this->InitEnvironment();

		//���������� �����������
		$this->Execute();
		return $this->tpl->Parse( "html.html" );
	}

	//������������� ����������.
	function &InitPrincipal()
	{

		$this->UseClass("Principal");
		$this->principal = &new Principal( $this, $this->principal_storage_model, 
			$this->principal_security_models );

		if ($this->principal->Identify() > PRINCIPAL_AUTH) 
		{
			$this->principal->Guest();
		}
		return $this->principal;

	}

	//���������� ������������ ���������.
	function InitEnvironment()
	{
		// �� ���� ������ �������� ������ ���������� ����� ��������
		// ��������� ���������� "/", ��������������� ����� �����
		$this->tpl->Set( "/", $this->ri->Href("") );
		$this->tpl->Set( "lib", $this->ri->Href($this->lib_href_part)."/" );
		$this->tpl->SetRef( "SITE", $this);
	}

	// ������, ����������� ��� RH
	function UseClass( $name, $level=0, $dr=1, $ext = 'php', $withSubDirs = false, $hideExc = false )
	{
		$this->UseScript("classes",$name,$level,$dr,$ext,$withSubDirs,$hideExc);
	}

	// ������, ����������� ��� RH
	function UseModel( $name, $level=0, $dr=1, $ext = 'php', $withSubDirs = false, $hideExc = false )
	{
		$this->UseScript("classes/models",$name,$level,$dr,$ext,$withSubDirs,$hideExc);
	}

	function UseLib( $library_name, $file_name="" )
	{
		// library is near core, library have no levels
		//$direction = 0;
		// lucky@npj: ��� ��� -- ��� ������. ������� � ����������, ����� � core
		$direction = 1;
		$level = 0; 
		// usually library have one file to link itself
		if ($file_name == "") $file_name = $library_name; 
		$ext="php";

		$this->UseScript( $this->lib_dir, $library_name."/".$file_name, $level, $direction, $ext);
	}

	function getPluralizeDir($classname)
	{
		$this->UseClass("Inflector");
		$words = preg_split('/[A-Z]/', $classname);
		$last_word = substr($classname, -strlen($words[count($words)-1])-1);
		$last_word = strtolower($last_word);
		return Inflector::pluralize($last_word);
	}

	function Redirect( $href )
	{
		if (strpos($href,"http://") !== 0) 
			$href = $this->ri->_host_prot.$href;

		header("Location: $href"); 
		exit;
	}

  /*
  ���������� ������
	*/

	// ������� "����������" ����� �� ���������������� �������
	// � ���� ������������ � �� ��������
	function _FuckQuotes(&$a)
	{
		if(is_array($a))
			foreach($a as $k => $v)
				if(is_array($v)) $this->_FuckQuotes($a[$k]);
				else $a[$k] = stripslashes($v);
	}

}

?>
