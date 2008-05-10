<?php
/*

���������� �� ������ ���������� ���:
* �������������� ���� �� ������ ���������� ���
* ������������ ������ ��� ������ ���������� ���
* persistent querystring & form generation

RequestInfo( &$rh )

-------------------

// ������ � �����

* GetUrl()
- ��� ����������, ���������� �� $_REQUEST, $_SERVER
- ���������� ������-������������� URL, ��������������� �������� �������
- ����� �������������� ��������� �� ������� ������
- ����� ��������� ��� ��������� ��� ������ � �����

* Href( $url, $ignore_state=STATE_IGNORE )
- ���������� ���������� URL �� ��������������
- $state_ignore -- ���������� ��� ��� "������� ���������"

* _Href_Absolute( $url, $state="" ) -- ��� ����������� �����������

* Form( $action, $method=METHOD_GET, $form_bonus="", $ignore_state=STATE_USE )
- ���������� <form action=...> �� �������������� action
- $form_bonus   -- ������������ ������ ���� <form>
- $state_ignore -- ���������� ��� ��� "������� ���������", ����������� ��� � <input type=hidden...>

// ������ � �������

* Set( $key, $value, $weak=0 ) -- ���������� ���� � ��������
- $key   -- ��� ���� (case-sensitive)
- $value -- ��������������� ��������
- $weak  -- ���� ���������� � �������, �� �� ����� �������������� ������������ ����

* SetRef( $key, &$value, $weak=0 ) -- ���������� ���� �������

* &Get( $key ) -- �������� �������� ����
- $key   -- ��� ���� (case-sensitive)

* Free( $key=NULL ) -- �������� ����/�����
- $key -- ���� ���������, �� ������� ���� �����, ����� ������ ��������������� ����

// ����������� ������ � �����

* HrefPlus( $url, $key, $value=1 ) -- ������������ URL, ������� ���� ���� (�� ��������� ���� � ������)
- $url   -- ������������� URL, ��� � Href
- $key   -- ��� ���� (case-sensitive)
- $value -- ��������������� ��������
- NB: $key ����� ���� ����� ���� { $k=>$v }, ����� $value �� �����

* HrefMinus( $url, $key, $_bonus="" ) -- ������������ URL, ��������� ���� ���� ��� ������
- $url    -- ������������� URL, ��� � Href
- $key    -- ��� ���� (case-sensitive) �� ������, ������� ������������
- $_bonus -- ����������� ��������, ������������ ��� ������ �� HrefMinus

* _HrefMinusArray( $url, $key, $_bonus="" ) -- ��� ����������� �����������. �� ��������!



// ����������� ������ � ��������

* &Copy() -- ������� RI ����� �������

* Load( $keyset, $skip_char="_", $weak=0 ) -- ��������� ���� �� ������� ������ ���� �������
- $keyset    -- ���-������ ��� RI
- $skip_char -- ���������� ����, ������������ � ����� �������
- $weak      -- ���� ���������� � �������, �� �� ����� �������������� ������������ ����

* _Pack( $method=METHOD_GET, $bonus="", $only="" ) -- ��� ����������� �����������
- ��������� � ������ ��� GET/POST �������
- $method -- ��� ������ "?key=value&key=value" ��� "<input type=hidden..."
- $bonus  -- ���������� � ����� ������. ����� ��� METHOD_GET, ������ ��� ����� ���� "?key=value&bonus", � ����� "?bonus"
- $only   -- ������������ �������. ���� ������, �� �������� ������ �� ���� ������, ������� ���������� � only

================================================================== v.1 (kuso@npj)
*/
define( "STATE_USE",    0 );
define( "STATE_IGNORE", 1 );
define( "METHOD_GET",    "get" );
define( "METHOD_POST",   "post" );

class RequestInfo
{
	protected $q = "?"; // ��������� ��� ����������� ������������ �����
	protected $s = "&";

	protected $url             = "";             // ������������� URL, ������� ������������������ RI ����� GetUrl
	protected $values          = array();        // ������������� ��������� ���������
	protected $_compiled       = array("","");   // �������������� get-post ������ ���������
	protected $_compiled_ready = false;          // ���� ������������ _compiled == values

	protected $href_absolute   = false;          // if true, converts Href() result to "http://www.site.ru/..."
	// ��������� ��� ������������� ������ (� �������� ������� ��������):
	public $_host      = "www.pixel-apes.com";                              // ����, � �������� ��������
	public $_host_prot = "http://www.pixel-apes.com/";                      // ���� � ��������� ���������
	public $_base_url  = "something/like/this/";                            // ��� �� ����� (�� ������� ������)
	public $_base_full = "http://www.pixel-apes.com/something/like/this/";  // ��������� ��������� ���������� ���

	public function __construct( &$rh ) // -- ����������� ������ �� ������ =)
	{
		$this->rh = &$rh;
		 
		// 0. ��������� ��������� ��� ������
		$this->_host = preg_replace('/:.*/','',$_SERVER["HTTP_HOST"]);
		$this->_host_prot = "http://".$_SERVER["HTTP_HOST"];
		$this->_base_full = "http://".$this->_host.$this->rh->base_url;
		$this->_base_url  = $this->rh->base_url;

	}

	// ������ � ����� -----------------------------------------------------
	// v.0:  + mod_rewrite
	//       - 404
	//       - request_info
	//       - plain vanilla
	//
	// ��� ��������� ������� ������ ������� ���� GetUrl � Form.

	public function getUrl() // -- ���������� ������-������������� URL, ��������������� �������� �������
	{
		// RSS migrated ---
		if (isset($this->rh->rss)) $this->url = $this->rss->url;
		// ---- /rss

		// 1. �������� $url from ["page"]
		$this->url = $_REQUEST["page"];

		// 2. ������� ���������
		$this->Load( $_GET , "_" );    // GET  first
		//$this->Load( $_POST, "_" );    // POST second
		$this->Free("page");       // free "page", from where we receive nisht.

		return $this->url;
	}

	public function href( $url, $ignore_state=STATE_IGNORE ) // -- ���������� ���������� URL �� ��������������
	{
		if ($ignore_state == STATE_USE) 
		{
			$state = $this->_pack();
		}
		else 
		{
			$state = "";
		}
		
		return $this->_href_Absolute( $url, $state );
	}

	// (����������)
	protected function _href_Absolute( $url, $state="" ) // -- �� �������� ��������� � ���� ���������� ���������� URL
	{
		if (strpos($url, "http:") === 0) 
		{
			$prefix = "";
		}
		else
		{
			if ($this->href_absolute) 
			{
				$prefix = $this->_base_full;
			}
			else
			{
				$prefix = $this->_base_url;
			}
		}

		return $prefix.$url.$state;
	}

	public function form( $action, $method=METHOD_GET, $form_bonus="", $ignore_state=STATE_USE ) // -- ���������� <form..
	{
		if ($ignore_state == STATE_USE) $state = $this->_Pack(METHOD_POST);
		else                            $state = "";

		// mod_rewrite-only
		$_action = $this->Href( $action, STATE_IGNORE );

		return "<form action=\"".$_action."\" method=\"".$method."\" ".$form_bonus.">".$state;
	}

	// ������ � ������� ---------------------------------------------------

	public function set( $key, $value, $weak=0 ) // -- ���������� ���� � ��������
	{
		if ($weak) if (isset($this->values[$key])) return false;
		$this->_compiled_ready = 0;
		$this->values[$key] = $value;
		return true;
	}

	public function setRef( $key, &$value, $weak=0 ) // -- ���������� ���� �������
	{
		if ($weak) if (isset($this->values[$key])) return false;
		$this->_compiled_ready = 0;
		$this->values[$key] = &$value;
		return true;
	}

	public function &get( $key ) // -- �������� �������� ����
	{
		return $this->values[$key];
	}

	public function free( $key=NULL ) // -- �������� ����/�����
	{
		if ($key)
		if(is_array($key))
		{
			$kc = count($key);
			for($i=0; $i<kc; $i++) unset($this->values[$key[$i]]);
		}
		else unset($this->values[$key]);
		else $this->values = array();
		$this->_compiled_ready = 0;
	}

	// ����������� ������ � ����� --------------------------------------------

	public function hrefPlus( $url, $key, $value=1 )  // -- ������������ URL, ������� ���� ���� (�� ��������� ���� � ������)
	{
		if ($url === "") $url = $this->url;
		// ������� ������� �������� key=value
		if (is_array($key))
		{
			foreach($key as $k=>$v)
			{
				if($v!='')
				{
					$bonus .= $k."=".urlencode($v)."&";
				}
			}
			return $this->_hrefMinusArray( $url, $key, rtrim($bonus,'&') );
		}
		else
		{
			if($value!='')
			{
				$bonus = $key."=".urlencode($value);
			}
			// ������� �����, ������� ����� ���� �������� �� ����, ���� ������� ���
			return $this->hrefMinus( $url, $key, $bonus );
		}
	}

	public function hrefMinus( $url, $key, $_bonus="" ) // -- ������������ URL, ��������� ���� ����
	{
		if ($url === "") $url = $this->url;
		if (is_array($key))
		{
			$key = array_flip($key);
			return $this->_HrefMinusArray( $url, $key, $_bonus );
		}
		$data = "";
		$f=0;
		foreach($this->values as $k=>$v)
		if ($k != $key && $v!='' )
		{
			if ($f) $data.=$this->s; else $f=1;
			$data .= $k."=".urlencode($v);
		}
		if ($_bonus != "")
		if ($data != "") $data= $this->q . $data . $this->s . $_bonus;
		else $data = $this->q. $_bonus;
		else  $data = $this->q. $data;

		return $this->_Href_Absolute( $url, $data );
	}

	public function _hrefMinusArray( $url, $key, $_bonus="" ) // -- ��� ����������� �������������. �� ��������!
	{
		$data = "";
		$f=0;
		foreach($this->values as $k=>$v)
		if (!isset($key[$k]) && $v!='')
		{
			if ($f) $data.=$this->s; else $f=1;
			if(!is_array($v))
			{
				$data .= $k."=".urlencode($v);
			}
		}
		if ($_bonus != "")
		if ($data != "") $data= $this->q . $data . $this->s . $_bonus;
		else $data = $this->q. $_bonus;
		else  $data = $this->q. $data;

		return $this->_Href_Absolute( $url, $data );
	}

	// ����������� ������ � �������� -----------------------------------------

	protected function load( $keyset, $skip_char="_", $weak=0 ) // -- ��������� ���� �� ������� ������ ���� �������
	{
		if (is_object($keyset)) $data = &$keyset->values;
		else $data = &$keyset;
		foreach ($data as $k=>$v)
		if ( (($skip_char == "") || ($k[0] != $skip_char)) && (($weak==0) || (!isset($this->values[$k]))) )
		$this->values[$k] = $v;
		$ready = 0;
	}

	protected function _pack( $method=METHOD_GET, $bonus="", $only="" ) // -- ��������� � ������ ��� GET/POST �������
	{
		if (!$this->_compiled_ready)
		{
			$this->_compiled[METHOD_GET ] = "";
			$this->_compiled[METHOD_POST] = "";

			$f=0;
			foreach($this->values as $k=>$v)
			if( $v!='' )
			if (($only == "") || (strpos($k, $only) === 0))
			{
				if (is_array($v))
				{
					$v0 = array_map(htmlspecialchars, $v);
					$v1 = array_map(urlencode, $v);
				}
				else
				{
					$v0 = htmlspecialchars($v);
					$v1 = urlencode($v);
				}
				 
				if ($f) $this->_compiled[METHOD_GET ].=$this->s; else $f=1;
				$this->_compiled[METHOD_GET ] .= $k."=".$v1;
				$this->_compiled[METHOD_POST] .= "<input type='hidden' name='".$k."' value='".$v0."' />\n";
			}
			$this->_compiled_ready = 1;
		}
		$data = $this->_compiled[$method];
		if ($method == METHOD_POST) return $data.$bonus;

		if ($bonus != "")
		if ($data != "") $data=$this->q.$data.$this->s.$bonus;
		else $data.=$this->q.$bonus;
		else if ($data != "") $data = $this->q.$data;
		 
		return $data;
	}


	// EOC{ RequestInfo }
}


?>