<?php

/**
 * Форматирует дату через CyrDate
 * 
 * Параметры
 *	 data		- строка даты
 *	 iformat - формат строки даты
 *	 oformat - формат даты на выходе
 */

/* ------------ from render -------------- */
$template = $params[0];
// чтобы работал caller, надо пропатчить шаблонный движок
$caller = $params['_caller'];
unset($params[0]);
unset($params['_name']);
unset($params['_plain']);
unset($params['_caller']);

foreach( $params as $key => $v){
	if ($v[0]=='@'){     

		$subtemplate = substr( $v, 1 );

		// чтобы работала возможность опускать имя текущего шаблона
		// с помощью caller, надо пропатчить шаблонный движок
		if ($subtemplate[0]==':')
			$subtemplate = $caller.'.html'.$subtemplate;
		$rh->tpl->Set($key,$rh->tpl->parse($subtemplate));
	}else{
		// если у нас в параметрах присутствуют переменные подстановок,
		// например [[images]]
		$v = str_replace('[[','{{',$v);
		$v = str_replace(']]','}}',$v);
		$params[$key] = $rh->tpl->ParseInstant( $v );
	}
}
/* ------------// from render ---------------- */

$date = $params['date'];
$ifmt = $params['iformat'];
$ofmt = $params['oformat'];

$rh->useClass('CyrDate');
$d = CyrDate::newFromStr($ifmt, $date);
echo $d->format($ofmt);

?>
