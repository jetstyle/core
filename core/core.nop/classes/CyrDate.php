<?php

/**
 * Êëàññ -- äàòà è âğåìÿ
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

	var $cyr_months = array(
		1 => 'ÿíâàğÿ',
		2 => 'ôåâğàëÿ',
		3 => 'ìàğòà',
		4 => 'àïğåëÿ',
		5 => 'ìàÿ',
		6 => 'èşíÿ',
		7 => 'èşëÿ',
		8 => 'àâãóñòà',
		9 => 'ñåíòÿáğÿ',
		10 => 'îêğÿáğÿ',
		11 => 'íîÿáğÿ',
		12 => 'äåêàáğÿ',
	);
	var $cyr_month = array(
		1 => 'ÿíâàğü',
		2 => 'ôåâğàëü',
		3 => 'ìàğò',
		4 => 'àïğåëü',
		5 => 'ìàé',
		6 => 'èşíü',
		7 => 'èşëü',
		8 => 'àâãóñò',
		9 => 'ñåíòÿáğü',
		10 => 'îêğÿáğü',
		11 => 'íîÿáğü',
		12 => 'äåêàáğü',
	);
	var $cyr_quarter = array(
		1 => 'I êâàğòàë', 
		2 => 'II êâàğòàë', 
		3 => 'III êâàğòàë', 
		4 => 'IV êâàğòàë'
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
	 * Ñîçäàòü îáúåêò CyrDate 
	 *
	 * @param string $date ñòğîêà â ôîğìàòå %Y-%m-%d %H:%i:%s
	 */
	function CyrDate($date=NULL)
	{
		if(isset($date)) $this->fromStr('%Y-%m-%d %H:%i:%s', $date);
	}

	//!a constructor
	/** 
	 * Ñîçäàòü îáúåêò CyrDate èç ñòğîêè $str, ñîäåğæàùåé äàòó â ôîğìàòå $fmt
	 *
	 * @param string $fmt ôîğìàò äàòû â $str
	 * @param string $str äàòà â âèäå ñòğîêè
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
		$this->second = 0;
	}
	function dayEnd()
	{
		$this->hour = 23;
		$this->minute = 59;
		$this->second = 59;
	}
	function weekStart()
	{
		$this->dayStart();
		$day_of_week = intval(date('w',  // 0 - sunday, 1 - monday
			mktime(0, 0, 1, $this->month, $this->day, $this->year)));
		$day_of_week = ($day_of_week + 6) % 7; // 0 - monday, 6 - sunday
		$this->day = $this->day - $day_of_week;

		$this->fromStr('%Y %m %d %H %i %s',
			date('Y m d H i s',
			mktime($this->hour, $this->minute, $this->second, 
				$this->month, $this->day, $this->year)));
	}
	function weekEnd()
	{
		$this->dayEnd();
		$now = mktime($this->hour, $this->minute, $this->second, 
			$this->month, $this->day, $this->year);
		$day_of_week = intval(date('w',  // 0 - sunday, 1 - monday
			$now));
		$day_of_week = ($day_of_week + 6) % 7; // 0 - monday, 6 - sunday
		$this->day = $this->day + (6 - $day_of_week);

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
	 * Ñîçäàòü îáúåêò CyrDate äëÿ òåêóùåãî âğåìåíè
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
	 * Èíèöèàëèçèğîâàòü îáúåêò òåêóùèì âğåìåíåì
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
	 * Èíèöèàëèçèğîâàòü îáúåêò èç ñòğîêè $str, ñîäåğæàùåé äàòó â ôîğìàòå $fmt
	 *
	 * @param string $fmt ôîğìàò äàòû â $str
	 * @param string $str äàòà â âèäå ñòğîêè
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
	function months_str() { return $this->cyr_months[$this->month]; }
	function month_str() { return $this->cyr_month[$this->month]; }

	// setters:
	function setYear($value) { $this->year = intval($value); }
	function setMonth($value) { $this->month = intval($value); }
	function setDay($value) { $this->day = intval($value); }
	function setHour($value) { $this->hour = intval($value); }
	function setMinute($value) { $this->minute = intval($value); }
	function setSecond($value) { $this->second = intval($value); }

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
}


?>
