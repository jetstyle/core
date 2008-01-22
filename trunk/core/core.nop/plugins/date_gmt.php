<?php

/**
 * ����������� ���� ����� CyrDate
 * 
 * ���������
 *	 data		- ������ ����
 *	 iformat - ������ ������ ����
 *	 oformat - ������ ���� �� ������
 */

$date = (
	isset($params['date']) 
	? $params['date'] 
	: (
		isset($params['_']) 
		? $params['_']
		: $params[0]
	)
	);
$ifmt = isset($params['iformat']) 
	? $params['iformat'] :
	'%d.%m.%Y %H:%i';

if (is_string($date))
{
	$rh->useClass('CyrDate');
	$d =& CyrDate::newFromStr($ifmt, $date);
}
else
{
	$d =& $date;
}

if (isset($d))
{
	$d->ctx =& $rh;
	echo $d->getRss();
}
else
{
	echo $date;
}

?>
