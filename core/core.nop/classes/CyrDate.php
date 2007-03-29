<?php

/**
 *  ласс -- дата и врем€
 *
 */
class CyrDate
{

	var $year=NULL;
	var $month=NULL;
	var $day=NULL;
	var $hour=NULL;
	var $minute=NULL;
	var $second=NULL;

	var $months = array(
		1 => '€нвар€',
		2 => 'феврал€',
		3 => 'марта',
		4 => 'апрел€',
		5 => 'ма€',
		6 => 'июн€',
		7 => 'июл€',
		8 => 'августа',
		9 => 'сент€бр€',
		10 => 'окр€бр€',
		11 => 'но€бр€',
		12 => 'декабр€',
	);
	var $en_months = array(
		1 => 'january',
		2 => 'february',
		3 => 'march',
		4 => 'april',
		5 => 'may',
		6 => 'june',
		7 => 'july',
		8 => 'august',
		9 => 'september',
		10 => 'october',
		11 => 'november',
		12 => 'december',
	);
	var $month = array(
		1 => '€нварь',
		2 => 'февраль',
		3 => 'март',
		4 => 'апрель',
		5 => 'май',
		6 => 'июнь',
		7 => 'июль',
		8 => 'август',
		9 => 'сент€брь',
		10 => 'окр€брь',
		11 => 'но€брь',
		12 => 'декабрь',
	);
	var $en_month = array(
		1 => 'January',
		2 => 'February',
		3 => 'March',
		4 => 'April',
		5 => 'May',
		6 => 'June',
		7 => 'July',
		8 => 'August',
		9 => 'September',
		10 => 'October',
		11 => 'November',
		12 => 'December',
	);
	var $en_week = array(
		0 => 'Monday',
		1 => 'Tuesday',
		2 => 'Wednesday',
		3 => 'Thursday',
		4 => 'Friday',
		5 => 'Saturday',
		6 => 'Sunday',
	);
	var $cyr_quarter = array(
		1 => 'I квартал', 
		2 => 'II квартал', 
		3 => 'III квартал', 
		4 => 'IV квартал'
	);
	var $fmts = array(
		'Q'=>'quarter',
		'Y'=>'year',
		'm'=>'month',
		'M'=>'months_str',
		'N'=>'month_str',
		'd'=>'day',
		'H'=>'hour',
		'i'=>'minute',
		's'=>'second',
		'B'=>'month_str',
		'b' => 'month_str3',
		'A' => 'week_str',
		'a' => 'week_str3',
		'z' => 'timezone',
	);
	var $prsrs = array(
		'Y'=>array('\d{4}',   'setYear'),
		'm'=>array('\d{1,2}', 'setMonth'),
		'd'=>array('\d{1,2}', 'setDay'),
		'H'=>array('\d{1,2}', 'setHour'),
		'i'=>array('\d{1,2}', 'setMinute'),
		's'=>array('\d{1,2}', 'setSecond'),
	);

	//!a constructor
	/** 
	 * —оздать объект CyrDate 
	 *
	 * @param string $date строка в формате %Y-%m-%d %H:%i:%s
	 */
	function CyrDate($date=NULL)
	{
		if(isset($date)) $this->fromStr('%Y-%m-%d %H:%i:%s', $date);
	}

	//!a constructor
	/** 
	 * —оздать объект CyrDate из строки $str, содержащей дату в формате $fmt
	 *
	 * @param string $fmt формат даты в $str
	 * @param string $str дата в виде строки
	 *
	 * @return object CyrDate
	 */
	function &newFromStr($fmt, $str)
	{
		$o = &new CyrDate();
		return $o->fromStr($fmt, $str);
	}
	// compability
	function &createFromStr($fmt, $str)
	{
		return CyrDate::newFromStr($fmt, $str);
	}

	function dayStart()
	{
		$this->hour = 0;
		$this->minute = 0;
		$this->second = 1;
	}
	function dayEnd()
	{
		$this->hour = 23;
		$this->minute = 59;
		$this->second = 59;
	}
	function getDayOfWeek()
	{
		$now = mktime($this->hour, $this->minute, $this->second, 
			$this->month, $this->day, $this->year);
		$day_of_week = intval(date('w',  // 0 - sunday, 1 - monday
			$now));
		$day_of_week = (int)(($day_of_week + 6) % 7); // 0 - monday, 6 - sunday
		return $day_of_week;
	}
	function weekStart()
	{
		$this->dayStart();
		$this->day = $this->day - $this->getDayOfWeek();

		$this->fromStr('%Y %m %d %H %i %s',
			date('Y m d H i s',
			mktime($this->hour, $this->minute, $this->second, 
				$this->month, $this->day, $this->year)));
	}
	function weekEnd()
	{
		$this->dayEnd();
		$this->day = $this->day + (6 - $this->getDayOfWeek());

		$this->fromStr('%Y %m %d %H %i %s',
			date('Y m d H i s',
			mktime($this->hour, $this->minute, $this->second, 
				$this->month, $this->day, $this->year)));
	}
	function monthStart()
	{
		$this->dayStart();
		$this->day = 1;
	}
	function monthEnd()
	{
		$this->dayEnd();
		$this->day = intval(date('t', mktime(0, 0, 0, $this->month, 1, $this->year)));
	}
	function quarterStart()
	{
		$this->month = ($this->getQuarter()) * 3 - 2;
		$this->monthStart();
	}
	function quarterEnd()
	{
		$this->month = ($this->getQuarter()) * 3;
		$this->monthEnd();
	}
	//!a constructor
	/** 
	 * —оздать объект CyrDate дл€ текущего времени
	 *
	 * @return object CyrDate
	 */
	function &newNow()
	{
		$o = &new CyrDate();
		return $o->now();
	}
	// compability, don't use
	function &createNow()
	{
		return CyrDate::newNow();
	}

	//!a manipulator
	/** 
	 * »нициализировать объект текущим временем
	 *
	 * @return object CyrDate
	 */
	function &now()
	{
		return $this->fromStr('%Y %m %d %H %i %s', date('Y m d H i s'));
	}

	function &date($fmt, $date)
	{
		$o = &new CyrDate($date);
		return $o->format($fmt);
	}

	function format($fmt)
	{
		$result = preg_replace_callback('#%([a-zA-Z])#', array(&$this, '_callback'), $fmt);
		return $result;
	}

	//!a manipulator
	/** 
	 * »нициализировать объект из строки $str, содержащей дату в формате $fmt
	 *
	 * @param string $fmt формат даты в $str
	 * @param string $str дата в виде строки
	 *
	 * @return object CyrDate
	 */
	function &fromStr($fmt, $str)
	{
		$this->parser = array();
		$fmt_re = preg_replace_callback('#%([a-zA-Z])#', array(&$this, '_fromstr'), $fmt);
		if (preg_match('#^'.$fmt_re.'$#', $str, $matches))
		{
			if (True === $this->_fromstr_prs($matches))
				return $this;
		}
		return NULL;
	}

	function getQuarter() { return intval($this->month / 4) + 1; }
	// renders/getters:
	function quarter() { return $this->cyr_quarter[$this->getQuarter()]; }
	function year() { return sprintf('%04d', $this->year); }
	function month() { return sprintf('%02d', $this->month); }
	function day() { return sprintf('%02d', $this->day); }
	function hour() { return sprintf('%02d', $this->hour); }
	function minute() { return sprintf('%02d', $this->minute); }
	function second() { return sprintf('%02d', $this->second); }

	function months_str() { return $this->_toStr('months', $this->month); }
	function month_str() { return $this->_toStr('month', $this->month); }
	function month_str3() { return substr($this->month_str(), 0, 3); }
	function week_str() { return $this->_toStr('week', $this->getDayOfWeek()); }
	function week_str3() { return substr($this->week_str(), 0, 3); }

	// setters:
	function setYear($value) { $this->year = intval($value); }
	function setMonth($value) { $this->month = intval($value); }
	function setDay($value) { $this->day = intval($value); }
	function setHour($value) { $this->hour = intval($value); }
	function setMinute($value) { $this->minute = intval($value); }
	function setSecond($value) { $this->second = intval($value); }

	function getIsMorning() { return ($this->hour() >= 4 && $this->hour() < 8); } 
	function getIsDay() { return ($this->hour() >= 8 && $this->hour() < 18); } 
	function getIsEvening() { return ($this->hour() >= 18 && $this->hour() < 22); } 
	function getIsNight() { return ($this->hour() >= 22 || $this->hour() < 4); } 

	function timezone() 
	{ 
		return (($this->tz >= 0) ? '+' : '-').sprintf('%04d', $this->tz * 100); 
	}
	function getRss()
	{
		$this->lang = 'en';
		$fmt = '%b, %d %a %H:%i:%s %z';
		return $this->format($fmt);
	}

	function mktime()
	{
		return mktime($this->hour(), $this->minute(), $this->second(), 
			$this->month(), $this->day(), $this->year());
	}

	// private: 
	function _callback($matches)
	{
		$value = $matches[1];
		return array_key_exists($value, $this->fmts) 
			? call_user_func(array(&$this, $this->fmts[$value]))
			: $value;
	}
	function _fromstr($matches)
	{
		$value = $matches[1];
		if( array_key_exists($value, $this->prsrs) )
		{
			$this->parser[] = $value;
			$value = '('.$this->prsrs[$value][0].')';
		}
		return $value;
	}
	function _fromstr_prs($matches)
	{
		if (count($matches) - 1 !== count($this->parser)) return NULL;

		for($i=0, $c=count($this->parser); $i<$c; $i++)
		{
			call_user_func(array(&$this, $this->prsrs[$this->parser[$i]][1]), $matches[$i+1]);
		}

		return True;
	}
	function _toStr($name, $value)
	{
		$lang = (isset($this->lang) 
			? $this->lang 
			: (
				isset($this->ctx->lang) 
				? $this->ctx->lang 
				: NULL));
		$src = 
			(
				isset($lang) 
				&& ($attr = $lang.'_'.$name) 
				&& (isset($this->$attr))
			)
			? $this->$attr
			: $this->$name;
		return $src[$value];
	}
}


?>
