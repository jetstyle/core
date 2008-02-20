<?php
/**
 * Класс отладки
 * @author lunatic lunatic@jetstyle.ru
 */
class Debug
{
	static protected $log;
	static protected $_milestone;
	static protected $milestone;
	static protected $mark = array();

	/**
    * Инициализация
    *
    */
	static public function init()
	{
		self::$milestone = self::_getmicrotime();
		self::$_milestone = self::$milestone;
		self::$mark['auto'] = self::$milestone;
		self::trace("<b>log started.</b>");
	}

	/**
    * работа с временными отметками
    */
	static protected function _getmicrotime()
	{
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}


	/**
    * Формирование лога
    *
    * @param $prefix
    * @param $separator
    * @param $postfix
    * @return HTML
    */
	static public function getHtml( $prefix="<div>Trace log:</div><ul><li>", $separator="</li><li>", $postfix="</li></ul><div id='debug_div' class='debug_div'></div>" )
	{
		$out = '';
		self::trace( "<b>log flushed.</b>");
		$out.=$prefix;
		$f=0;
		foreach (self::$log as $item)
		{
			if (!$f) $f=1; else $out.=$separator;
			$out.=$item;
		}
		$out.=$postfix;
		self::$log = array();
		return $out;
	}

	/**
	 * Добавление записи в лог
	 *
	 * @param string $what
	 * @param string $category категория записи
	 * @param string $label отметка, поставленная функцией mark(), от нее будет считаться время выполнения
	 */
	static public function trace( $what, $category = null, $label = null)
	{
		$m = self::_getmicrotime();
		$diff = $m - self::$_milestone;
		if($label)	
		{
			$diff1 = $m - self::$mark[$label];
			unset(self::$mark[$label]);
		}
		else
		{
			$diff1 = $m - self::$mark['auto'];
		}

		if (function_exists('memory_get_usage'))
		{
			$memory = number_format((memory_get_usage() / 1024));
		}
		self::$log[] = sprintf("[%0.4f] ",$diff).sprintf("[%0.4f] ",$diff1).( $memory ? " [$memory kb] " : "").$what;
		self::$mark['auto'] = $m;
	}

	/**
	 * Ставим контрольную отметку
	 *
	 * @param string $label
	 */
	static public function mark($label = 'auto')	
	{
		self::$mark[$label] = self::_getmicrotime();
	}

	/**
	 * умереть, тихо или громко
	 */ 
	static public function halt( $flush = 1 )
	{
		header("Content-Type: text/html; charset=windows-1251");
		if ($flush) echo self::$getHtml();
		die("К сожалению, произошла ошибка.");
	}
}
?>