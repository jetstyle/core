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

  * UseClass( $name, $level=0, $dr=1, $ext = 'php' ) -- �� ��, ��� � UseScript, ��
				$type='classes', �������� ������ � 0-�� ������ �����

  * UseLib( $library_name, $file_name="" ) -- ���������� ���������� �� �������� /lib/

  * End() -- ������� ���������� ������

  * Redirect( $href ) -- �������� �� ��� ��������
	 ����:
		- $href -- ����������� ��� (�� "��������������"), ��������, ��������� $ri->Href( "/" );

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
	$dirName = Finder::getPluralizeDir($className);
	if (Finder::findDir("classes/" . $dirName))
	{
		Finder::useClass($dirName . "/" . $className);
	}
	else
	{
		Finder::useClass($className);
	}
}

class Finder {

	private static $DIRS = array('all' => array()); 				//���������� � �������� ����������� ��� ������� ������

	private static $stack = array();

	private static $searchHistory = array();	//���������� � ���, ��� �� �������� ����� ����. ������������ � ������ ���������� ������.
	private static $searchCache = array();
	private static $lib_dir;

	//���� ������ �� ������� ��������.
	public static function findScript( $type, $name, $level=0, $dr=1, $ext = 'php', $withSubDirs = false, $scope = 'all' )
	{
		//��������� ������� ������
		/*
		if (strlen($type) == 0)
		{
			throw new Exception("FindScript: <b>*type* �����</b>, type=<b>$type</b>, name=<b>$name</b>, level=<b>$level</b>, dr=<b>$dr</b>, ext=<b>$ext</b>");
		}
		else
		*/

		if (strlen($name) == 0)
		{
			throw new Exception("FindScript: <b>*name* �����</b>, type=<b>$type</b>, name=<b>$name</b>, level=<b>$level</b>, dr=<b>$dr</b>, ext=<b>$ext</b>");
		}

		if (self::$searchCache[$type][$name.'.'.$ext])
		{
			//echo '<hr>'.self::$searchCache[$type][$name.'.'.$ext];
			return self::$searchCache[$type][$name.'.'.$ext];
		}

		if (!is_array(self::$DIRS[$scope]))
		{
			return false;
		}

		//���������� ��������� ������� ������
		$n = count(self::$DIRS[$scope]);
		if($level===false) $level = $n - 1;
		$i = $level>=0 ? $level : $n - $level;

		self::$searchHistory = array();
		//����
		for( ; $i>=0 && $i<$n; $i+=$dr )
		{
                        unset ($fname_block); 
			//������ ������� ������ ���
			$dir =& self::$DIRS[$scope][$i];
			//if( !( is_array($dir) && !in_array($type,$dir) ) )
			//{
			$fname = $dir.($type ? $type."/" : "").$name.'.'.$ext;
                        self::$searchHistory[] = $fname;

                        //looking for b-* files in [type]/blocks/name/name.[type]
                        $word = substr($name, 0, 2);
                        if ( strpos($name, "b_")!==false ) //$word=="b-" || $word=="b_" 
                        {
                            $_name = str_replace("blocks/", "", $name);
                            $fname_block = $dir.($type ? "templates/blocks/".$_name."/" : "").$_name.'.'.$ext;
                            //var_dump($fname_block);
                          
                            //var_dump($fname_block, file_exists($fname_block));
                            
                        }
                        /*
                        else if ( $i==0 && ( substr($name, 0, 9)=="blocks/b_" ) ) // || substr($name, 0, 9)=="blocks/b-" 
                        {
                            $_name = str_replace("blocks/", "", $name);
                            $fname_block = $dir.($type ? "templates/".$name."/" : "").$_name.'.'.$ext;
                            
                            //var_dump($fname_block);
                        }
                        */

			if(@file_exists($fname))
			{
				//self::$searchCache[$type][$name.'.'.$ext] = $fname;
				return $fname;
			}
                        else if ($fname_block && @file_exists($fname_block) )
                        {
                                return $fname_block;
                        }

			if ($withSubDirs)
			{
				if ($file = self::recursiveFind($fname))
				{
					//self::$searchCache[$type][$name.'.'.$ext] = $file;
					return $file;
				}
			}
			//}
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

	public static function getPluralizeDir($classname)
	{
		Finder::useClass("Inflector");
		$words = preg_split('/[A-Z]/', $classname);
		$last_word = substr($classname, -strlen($words[count($words) - 1]) - 1);
		$last_word = strtolower($last_word);
		return Inflector :: pluralize($last_word);
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
	public static function findScript_( $type, $name, $level=0, $dr=1, $ext = 'php', $withSubDirs = false, $scope = 'all' )
	{
		if (!$fname = self::findScript($type,$name,$level,$dr,$ext,$withSubDirs, $scope))
		{
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
		$n = count(self::$DIRS['all']);
//		$level = $n - 1;
//		$i = $level>=0 ? $level : $n - $level;

		//����
		for( ; $i>=0 && $i<$n; $i-=1 )
		{
			//������ ������� ������ ���
			$dir = self::$DIRS['all'][$i];
	  		if (is_dir($dir . $name))
	  		{
	  			return true;
	  		}
		}

		//������ �� �����
		return false;
	}

	//����, ��� � FindScript_(), �� ����� ���� �������� ��������� ������
	public static function useScript( $type, $name, $level=0, $dr=1, $ext = 'php', $withSubDirs = false, $hideExc = false, $scope = 'all' ){
		$method = ($hideExc) ? "findScript" : "findScript_";
		if ($path = self::$method($type,$name,$level,$dr,$ext,$withSubDirs, $scope))
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

	public static function setDirs($DIRS)
	{
		foreach ($DIRS AS $key => $value)
		{
			if (!is_numeric($key))
			{
				self::$DIRS[$value][] = $key;
				$value = $key;
			}
			self::$DIRS['all'][] = $value;
		}
	}

	public static function replaceDirs($DIRS)
	{
		self::$DIRS = $DIRS;
	}

	public static function useClass($name, $scope = 'all')
	{
		if (class_exists($name, false)) return;
		Finder::useScript("classes", $name, 0, 1, 'php', false, false, $scope);
	}

	public static function useModel($name, $scope = 'all')
	{
		if (class_exists($name, false)) return;
		self::useScript("classes/models", $name, 0, 1, 'php', false, false, $scope);
	}

	public static function useLib($libraryName, $fileName = "")
	{
		if ($fileName == "")
		{
			$fileName = $libraryName;
		}

		Finder::useScript('libs', $libraryName . "/" . $fileName, 0, 1, 'php');
	}

	public static function prependDir($dir, $scope = null)
	{
		self::addDir($dir, $scope, 'prepend');
	}

	public static function appendDir($dir, $scope = null)
	{
		self::addDir($dir, $scope);
	}

	public static function pushContext()
	{
		self::$stack[] = self::$DIRS;
	}

	public static function popContext()
	{
		if (count(self::$stack))
		{
			self::$DIRS = array_pop(self::$stack);
		}
	}

	private static function addDir($dir, $scope, $type = 'append')
	{
		if ($type == 'prepend')
		{
			$func = 'array_unshift';
		}
		else
		{
			$func = 'array_push';
		}

		if (null !== $scope)
		{
			if (!is_array(self::$DIRS[$scope])) self::$DIRS[$scope] = array();

            if (($pos = array_search($dir, self::$DIRS[$scope])) !== false)
            {
                unset(self::$DIRS[$scope][$pos]);
            }

			$func(self::$DIRS[$scope], $dir);
		}

        if (($pos = array_search($dir, self::$DIRS['all'])) !== false)
        {
            unset(self::$DIRS['all'][$pos]);
        }

		$func(self::$DIRS['all'], $dir);
	}
}
?>
