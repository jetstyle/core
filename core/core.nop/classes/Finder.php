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
		if (Finder::findDir("classes/" . $dir_name))
		{
			Finder::useClass($dir_name . "/" . $className);
		}
		else
		{
			Finder::useClass($className);
		}
	}
	else
	{
		die("autoload: class <b>$className</b> not found");
	}
}

class Finder {

	private static $DIRS = array(); 				//���������� � �������� ����������� ��� ������� ������

	private static $searchHistory = array();	//���������� � ���, ��� �� �������� ����� ����. ������������ � ������ ���������� ������.
	private static $searchCache = array();
	private static $lib_dir;

	//���� ������ �� ������� ��������.
	public static function findScript( $type, $name, $level=0, $dr=1, $ext = 'php', $withSubDirs = false )
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

		if (self::$searchCache[$type][$name.'.'.$ext])
		{
			return self::$searchCache[$type][$name.'.'.$ext];
		}

		//���������� ��������� ������� ������
		$n = count(self::$DIRS);
		if($level===false) $level = $n - 1;
		$i = $level>=0 ? $level : $n - $level;

		self::$searchHistory = array();

		//����
		for( ; $i>=0 && $i<$n; $i+=$dr )
		{
			//������ ������� ������ ���
			$dir =& self::$DIRS[$i];
			if( !( is_array($dir) && !in_array($type,$dir) ) )
			{
				$fname = (is_array($dir) ? $dir[0] : $dir).$type."/".$name.'.'.$ext;

				self::$searchHistory[] = $fname;

				if(@file_exists($fname))
				{
					self::$searchCache[$type][$name.'.'.$ext] = $fname;
					return $fname;
				}

				if ($withSubDirs)
				{
					if ($file = self::recursiveFind((is_array($dir) ? $dir[0] : $dir).$type."/", $name . "." . $ext))
					{
						self::$searchCache[$type][$name.'.'.$ext] = $file;
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

	public static function getDirsStack()
	{
		return self::$DIRS;
	}

	private static function recursiveFind($dir, $name)
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
					self::$searchHistory[] = $dir . $file;

					if ($res = self::recursiveFind($dir . $file, $name))
					{
						closedir($handle);
						return $res;
					}
				}
				else
				{
					self::$searchHistory[] = $dir . $file;
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
	public static function findScript_( $type, $name, $level=0, $dr=1, $ext = 'php', $withSubDirs = false )
	{
		if (!$fname = self::findScript($type,$name,$level,$dr,$ext,$withSubDirs))
		{
					var_dump(self::$DIRS);
					die();
			$e = new FileNotFoundException("File not found: <b>".$name.".".$ext."</b>", self::buildSearchHistory());
			$e->setFilename($name.".".$ext);
			throw $e;
		}
		else
		{
			return $fname;
		}
	}

	public static function findDir($name)
	{
		//���������� ��������� ������� ������
		$n = count(self::$DIRS);
		$level = $n - 1;
		$i = $level>=0 ? $level : $n - $level;

		//����
		for( ; $i>=0 && $i<$n; $i-=1 )
		{
			//������ ������� ������ ���
			$dir =& self::$DIRS[$i];
	  if (is_dir($dir . $name))
	  return true;
		}

		//������ �� �����
		return false;
	}

	//����, ��� � FindScript_(), �� ����� ���� �������� ��������� ������
	public static function useScript( $type, $name, $level=0, $dr=1, $ext = 'php', $withSubDirs = false, $hideExc = false ){
		$method = ($hideExc) ? "findScript" : "findScript_";
		if ($path = self::$method($type,$name,$level,$dr,$ext,$withSubDirs))
		self::_useScript( $path );
	}

	// ������ ������ � ��������� ����
	private static function _useScript($source)
	{
		include_once( $source );
	}

	private static function buildSearchHistory()
	{
		if(empty(self::$searchHistory))
		{
			return '';
		}

		$out = '<b>Search history:</b><ol>';

		foreach(self::$searchHistory AS $k => $v)
		{
			$out .= '<li>'.str_replace(Config::get('project_dir'), '', $v).'</li>';
		}

		$out .= '</ol>';
		return $out;
	}

	public static function setDirs($DIRS) {
		self::$DIRS = $DIRS;
	}

	public static function useClass($name, $level = 0, $dr = 1, $ext = 'php', $withSubDirs = false, $hideExc = false) {
		if (class_exists($name, false)) return;
		Finder::useScript("classes", $name, $level, $dr, $ext, $withSubDirs, $hideExc);
	}

	// ������, ����������� ��� RH
	public static function useModel($name, $level = 0, $dr = 1, $ext = 'php', $withSubDirs = false, $hideExc = false) {
		if (class_exists($name, false)) return;
		self::useScript("classes/models", $name, $level, $dr, $ext, $withSubDirs, $hideExc);
	}

	public static function useLib($libraryName, $fileName = "") 
	{
		if ($fileName == "")
		{
			$fileName = $libraryName;
		}

		Finder::useScript('libs', $libraryName . "/" . $fileName, 0, 1, 'php');
	}

	/*public static function useModule($name, $type = NULL) {
		self::useClass('ModuleLoader');
		$o = & new ModuleLoader();
		$o->initialize($this);
		$o->load($name);
		return $o->data;
	}*/

	public static function prependDir($dir) {
		array_unshift(self::$DIRS,$dir);
	}

	public static function appendDir($dir) {
		array_push(self::$DIRS,$dir);
	}
}
?>