<?php

/**
 * Форматирует дату через CyrDate
 * 
 * Параметры
 *	 data		- строка даты
 *	 iformat - формат строки даты
 *	 oformat - формат даты на выходе
 */

$date = $params['date'];
$ifmt = $params['iformat'];
$ofmt = $params['oformat'];

$rh->useClass('CyrDate');
$d = CyrDate::newFromStr($ifmt, $date);
if (isset($d))
{
	$d->ctx =& $rh;
	echo $d->format($ofmt);
}
else
	echo $date;

?>
