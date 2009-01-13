<?php
/*
 �������� ��� �������������������:
 * ������������ ����� �������
 * ����� ������������������� ��������� ��������

 MessageSet()

 ---------

 // �������� ������������������� �������

 * ConvertDate( $dt ) -- ��������������� �� "2004-05-20 23:37:20" => "20 ��� 2004"

 * NumberString( $count, $items_name = "items" ) -- ��������������� �� "23 pages" -> "23 ��������"
 - $count     -- ����� ������, ��� ������� ����� ������� �����
 - $items_name -- ������������� ���������� �� msg["Numbers"]
 - ���������� ������ "��������", ������ ��� ��������� ����� ���� � ������ ����������

 ---------

 // ������������ ������

 * SwitchTo( $lang = "ru" ) -- ������������ ����� "�� ����"
 - $lang -- ������� ������������

 * Get( $key, $level=false, $dir=-1 ) -- �������� �������� ���������
 - $key         -- ��� �����, �������� "email"
 - $level, $dir -- ��������� ��������� � ����� ������������,

 * Load( $ms_name="", $level=false, $dir=-1 ) -- ��������� messageset, �������� ����
 - $ms_name     -- ��� ����������� ��� �������� � ����������, �������� "form" ��� "ru_form.php"
 - $level, $dir -- ��������� ������ ����� ��� FindScript

 * Unload( $to_level = false ) -- ��������� messagesets �� ���������� ������
 - $to_level -- ���� �� ������, �� ����������� ������ �������
 - ���� =0, �� �������� "������" ����������
 =============================================================== v.1 (kuso@npj)
 */

class MessageSet
{
	private static $instance = null;
	
	private $MSGS      = array(); // ���� ������������
	private $MSG_NAMES = array(); // ���� ��� ��� ������������ �����
	private $lang = "default";

	private function __construct()
	{
		// always load default messageset
		$this->load();

		if (Config::exists('msg_default'))
		{
			$this->switchTo( Config::get('msg_default') );
		}
	}

	public function &getInstance()
	{
		if (null === self::$instance)
		{
			self::$instance = new self();
		}
		return self::$instance; 
	}
	
	public function switchTo( $lang = "ru" )
	{
		$names = $this->MSG_NAMES;
		// clean up ms
		$this->unLoad(1);
		// change lang
		$this->lang = $lang;
		// load`em back in stack
		foreach( $names AS $k => $name )
		{
			if ($k > 0)
			{
				$this->load( $name[0], $name[1], $name[2] );
			}
		}
	}

	public function get( $key, $level=false, $dir=-1 ) // -- �������� �������� ���������
	{
		//���������� ��������� ������� ������
		$n = count($this->MSGS);
		if($level===false) $level = $n - 1;
		$i = $level>=0 ? $level : $n - $level;

		//����
		for( ; $i>=0 && $i<$n; $i+=$dir )
		{
			if (isset($this->MSGS[$i][$key]))
			{
				return $this->MSGS[$i][$key];
			}
			//���� ������ ������ �� ����� ������ - ����� �������
			if($dir==0)
			{
				break;
			}
		}

		//������ �� �����
		return $key;
	}

	private function load( $ms_name="", $level=false, $dir=-1 ) // -- ��������� messageset, �������� ����
	{
		$this->MSG_NAMES[] = array($ms_name, $level, $dir);
		if ($ms_name) $ms_name = "_".$ms_name;
		{
			$script = Finder::findScript( "messagesets", $this->lang.$ms_name, $level, $dir );
		}
		if ($script) 
		{
			include($script);
		}
		else
		{
			$this->MSGS[] = array(); // load empty if not found at all
		}
	}

	function unload( $to_level = false ) // -- ��������� messagesets �� ���������� ������
	{
		if ($to_level === false) 
		{
			$to_level = sizeof($this->MSGS)-1;
		}
		$this->MSGS = array_slice( $this->MSGS, 0, $to_level );
		$this->MSG_NAMES = array_slice( $this->MSG_NAMES, 0, $to_level );
	}


	// �������� ������� ---------------------------------------------------------------------------------
	function convertDate( $dt )
	{
		$months = $this->get( "Months" );

		$dt = explode(" ",$dt);
		$d  = explode("-",$dt[0]);
		return ltrim($d[2],"0")."&nbsp;".$months[$d[1]-1]."&nbsp;".$d[0];
	}

	function numberString( $count, $items_name = "items" )
	{
		$numbers = $this->get( "Numbers" );

		if (!isset($numbers[$items_name])) return "!define *$items_name*!";
		$triad = $numbers[$items_name];

		$count %= 100;
		if (($count > 10) && ($count < 20)) $count = 10;
		$c = $count % 10;
		if ($c == 0) return $triad[2];
		if ($c == 1) return $triad[0];
		if ($c <= 4) return $triad[1];
		return $triad[2];
	}
}
?>
