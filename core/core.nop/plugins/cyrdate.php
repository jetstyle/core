<?php

/**
 * ����������� ���� ����� CyrDate
 * 
 * ���������
 *	 data		- ������ ����
 *	 iformat - ������ ������ ����
 *	 oformat - ������ ���� �� ������
 */

/* ------------ from render -------------- */
$template = $params[0];
// ����� ������� caller, ���� ���������� ��������� ������
$caller = $params['_caller'];
unset($params[0]);
unset($params['_name']);
unset($params['_plain']);
unset($params['_caller']);

foreach( $params as $key => $v){
	if ($v[0]=='@'){     

		$subtemplate = substr( $v, 1 );

		// ����� �������� ����������� �������� ��� �������� �������
		// � ������� caller, ���� ���������� ��������� ������
		if ($subtemplate[0]==':')
			$subtemplate = $caller.'.html'.$subtemplate;
		$rh->tpl->Set($key,$rh->tpl->parse($subtemplate));
	}else{
		// ���� � ��� � ���������� ������������ ���������� �����������,
		// �������� [[images]]
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
