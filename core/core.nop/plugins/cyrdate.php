<?php

/**
 * Форматирует дату через CyrDate
 * 
 * Параметры
 *	 data		- строка даты
 *	 iformat - формат строки даты
 *	 oformat - формат даты на выходе
 */

$date = isset($params['date']) ? $params['date'] : $params['_'];
$ifmt = isset($params['iformat']) ? $params['iformat'] : '%d.%m.%Y';
$ofmt = isset($params['oformat']) ? $params['oformat'] : $params[0];

if (is_object($date))
{
	$d =& $date;
}
else
{
	$rh->useClass('CyrDate');
	$d =& CyrDate::newFromStr($ifmt, $date);
}

if (isset($d))
{
	$d->ctx =& $rh;
	if($params['ucfirst'])
	{
		echo ucfirst($d->format($ofmt));
	}
	else
	{
		echo $d->format($ofmt);
	}
	
}
else
{
	echo $date;
}

?>