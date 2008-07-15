<?php
/*
 ����������� �����, �������������� ���������������� ����������� ��������

 ===================

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

 */

function __autoload($className) 
{
	global $app;
	if ($app)
	{
		$dir_name = $app->getPluralizeDir($className);
		if ($app->findDir("classes/" . $dir_name))
		{
			$app->useClass($dir_name . "/" . $className);
		}	
		else
		{
			$app->useClass($className);
		}	
	}
	else
	{
		die("autoload: class <b>$className</b> not found");
	}
}

class ConfigProcessor {

	public $DIRS = array(); 				//���������� � �������� ����������� ��� ������� ������
	
	private $searchHistory = array();	//���������� � ���, ��� �� �������� ����� ����. ������������ � ������ ���������� ������.
	private $searchCache = array();
	
	//���� ������ �� ������� ��������.
	public function findScript( $type, $name, $level=0, $dr=1, $ext = 'php', $withSubDirs = false )
	{
		//��������� ������� ������
		if (strlen($type) == 0)
		{
			throw new Exception("FindScript: <b>*type* �����</b>, type=<b>$type</b>, name=<b>$name</b>, level=<b>$level</b>, dr=<b>$dr</b>, ext=<b>$ext</b>");
		}
		elseif (strlen($name) == 0)
		{
			throw new Exception("FindScript: <b>*name* �����</b>, type=<b>$type</b>, name=<b>$name</b>, level=<b>$level</b>, dr=<b>$dr</b>, ext=<b>$ext</b>");
		}
		
		if ($this->searchCache[$type][$name.'.'.$ext])
		{
			return $this->searchCache[$type][$name.'.'.$ext];
		}

		//���������� ��������� ������� ������
		$n = count($this->DIRS);
		if($level===false) $level = $n - 1;
		$i = $level>=0 ? $level : $n - $level;

		$this->searchHistory = array();
		
		//����
		for( ; $i>=0 && $i<$n; $i+=$dr )
		{
			//������ ������� ������ ���
			$dir =& $this->DIRS[$i];
			if( !( is_array($dir) && !in_array($type,$dir) ) )
			{
				$fname = (is_array($dir) ? $dir[0] : $dir).$type."/".$name.'.'.$ext;
				
				$this->searchHistory[] = $fname;
				
				if(@file_exists($fname))
				{
					$this->searchCache[$type][$name.'.'.$ext] = $fname;
					return $fname;
				}

				if ($withSubDirs)
				{
					if ($file = $this->recursiveFind((is_array($dir) ? $dir[0] : $dir).$type."/", $name . "." . $ext))
					{
						$this->searchCache[$type][$name.'.'.$ext] = $file;
						return $file;
					}
				}
			}
			//���� ������ ������ �� ����� ������ - ����� �������
			if($dr==0)
			break;
		}

		//������ �� �����
		return false;
	}

	private function recursiveFind($dir, $name)
	{
		if ($handle = @opendir($dir))
		{
			while (false !== ($file = readdir($handle)))
			{
				if ($file == "." || $file == "..")
				{
					continue ;
				}
				
				if (is_dir($dir . $file))
				{
					$this->searchHistory[] = $dir . $file;
					
					if ($res = $this->recursiveFind($dir . $file, $name))
					{
						closedir($handle);
						return $res;
					}
				}
				else
				{
					$this->searchHistory[] = $dir . $file;
					if ($file == $name)
					{
						return $dir . "/" . $file;
					}
				}
			}
			closedir($handle);
		}
		return false;
	}

	//newschool
	//����, ��� � FindScript(), �� � ������ �� ����������� ����� ������������ � �������
	public function findScript_( $type, $name, $level=0, $dr=1, $ext = 'php', $withSubDirs = false )
	{
		if (!$fname = $this->findScript($type,$name,$level,$dr,$ext,$withSubDirs))
		{
			throw new FileNotFoundException("name=<b>$name</b>, level=<b>$level</b>, dr=<b>$dr</b>", $type=="templates", $name, $this->rh, $this->searchHistory);
		}
		else
		{
			return $fname;
		}
	}

	public function findDir($name)
	{
		//���������� ��������� ������� ������
		$n = count($this->DIRS);
		$level = $n - 1;
		$i = $level>=0 ? $level : $n - $level;

		//����
		for( ; $i>=0 && $i<$n; $i-=1 )
		{
			//������ ������� ������ ���
			$dir =& $this->DIRS[$i];
	  if (is_dir($dir . $name))
	  return true;
		}

		//������ �� �����
		return false;
	}

	//����, ��� � FindScript_(), �� ����� ���� �������� ��������� ������
	public function useScript( $type, $name, $level=0, $dr=1, $ext = 'php', $withSubDirs = false, $hideExc = false ){
		$method = ($hideExc) ? "findScript" : "findScript_";
		if ($path = $this->$method($type,$name,$level,$dr,$ext,$withSubDirs))
		$this->_useScript( $path );
	}

	// ������ ������ � ��������� ����
	private function _useScript($source)
	{
		include_once( $source );
	}
}
?>