<?
	$what = $rh->path->path_trail;
	if(!$what) $what = $rh->GetVar('tpl');
	
	//взято из /pict.php - вынести в отдельный модуль?
	//прибиваем хитрые пути
	$reg_exp = "/\.{2,}|\:\/|\/{2,}|home\//";
	while( preg_match( $reg_exp, $what ) )
		$what = preg_replace( $reg_exp, '', $what );
	
	$rh->HeadersNoCache();	

	//парсим и возвращаем
	if( $rh->GetVar('this') )
		echo $tpl->Parse( $what.'.html' );
	else{
    //вложенные обёртки
    $A = explode('/',$what);
    for( $i=count($A)-1; $i>0; $i-- )
    	$tpl->Parse( $A[$i].'.html', 'content' );
    $what = $A[0].'.html';
    //финальная обёртка  	
		$tpl->Parse( $what ,'html_body');
		echo $tpl->Parse('html.html');
	}
	
?>