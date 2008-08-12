<?php
/*
 Абстрактный класс, обеспечивающий функциональность обнаружения скриптов

 ===================

 //поиск файлов

 * FindScript ( $type, $name, $level=false, $dr=-1, $ext = 'php' ) -- Ищет скрипт по уровням проектов.
 ВХОД:
 $type -- псевдотип скрипта, например classes, handlers, actions и пр.
 $name -- относительное имя файла в каталоге псевдокласса, без расширения
 $level -- уровень проекта, начиная с которого нужно искать файл
 если не задан, берётся равный самому последнему
 $dr -- направление поиска, возможные значения : -1,0,+1
 $ext -- расширение файла, обычно не указывается
 $this->DIRS -- массив корневых директорий для каждого уровня проекта,
 для каждого уровня может быть задано:
 $dir_name -- строка, имя корневой директории
 array( $dir_name, $TYPES ):
 $dir_name -- строка, имя корневой директории
 $TYPES -- перечисление, какие типы на уровне есть
 ВЫХОД:
 полное имя скрипта, которое можно вставить в include()
 false, если скрипт не найден

 * FindScript_( $type, $name, $level=false, $dr=-1, $ext = 'php' ) -- То же, что и FindScript,
 но в случае не обнаружения файла вываливается с ошибкой.

 * UseScript( $type, $name, $level=false, $dr=-1, $ext = 'php' ) -- То же, что и FindScript_,
 но дополнительно инклюдит скрипт

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

	private static $DIRS = array(); 				//информация о корневых директориях для каждого уровня

	private static $searchHistory = array();	//информация о том, где мы пытались найти файл. Используется в случае неудачного поиска.
	private static $searchCache = array();
	private static $lib_dir;

	//Ищет скрипт по уровням проектов.
	public static function findScript( $type, $name, $level=0, $dr=1, $ext = 'php', $withSubDirs = false )
	{

		//проверяем входные данные
		if (strlen($type) == 0)
		{
			throw new Exception("FindScript: <b>*type* пусто</b>, type=<b>$type</b>, name=<b>$name</b>, level=<b>$level</b>, dr=<b>$dr</b>, ext=<b>$ext</b>");
		}
		elseif (strlen($name) == 0)
		{
			throw new Exception("FindScript: <b>*name* пусто</b>, type=<b>$type</b>, name=<b>$name</b>, level=<b>$level</b>, dr=<b>$dr</b>, ext=<b>$ext</b>");
		}

		if (self::$searchCache[$type][$name.'.'.$ext])
		{
			return self::$searchCache[$type][$name.'.'.$ext];
		}

		//определяем начальный уровень поиска
		$n = count(self::$DIRS);
		if($level===false) $level = $n - 1;
		$i = $level>=0 ? $level : $n - $level;

		self::$searchHistory = array();

		//ищем
		for( ; $i>=0 && $i<$n; $i+=$dr )
		{
			//разбор каждого уровня тут
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
			//если искать только на одном уровне - сразу выходим
			if($dr==0)
			break;
		}

		//ничего не нашли
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
	//Тоже, что и FindScript(), но в случае не обнаружения файла вываливается с ошибкой
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
		//определяем начальный уровень поиска
		$n = count(self::$DIRS);
		$level = $n - 1;
		$i = $level>=0 ? $level : $n - $level;

		//ищем
		for( ; $i>=0 && $i<$n; $i-=1 )
		{
			//разбор каждого уровня тут
			$dir =& self::$DIRS[$i];
	  if (is_dir($dir . $name))
	  return true;
		}

		//ничего не нашли
		return false;
	}

	//Тоже, что и FindScript_(), но кроме того инклюдим найденный скрипт
	public static function useScript( $type, $name, $level=0, $dr=1, $ext = 'php', $withSubDirs = false, $hideExc = false ){
		$method = ($hideExc) ? "findScript" : "findScript_";
		if ($path = self::$method($type,$name,$level,$dr,$ext,$withSubDirs))
		self::_useScript( $path );
	}

	// Грузит скрипт в контексте меня
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

	// Алиасы, специфичные для RH
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