<?
//������, ��� ������ �� �����. ���� �������, ���� �� ������ ������� - �� ����� ����������� ������������.
function action_accept_htmlarea( &$rh, &$PARAMS ){
	return $PARAMS['__string'];
	
	//��������� ������ ����� ����� ���������
//	$text = str_replace('\"','"',$PARAMS['__string']);
//	$text = str_replace("\'","'",$text);

  //��������� ����������� ���������
//  $text = preg_replace("/\<\/p\>\s*([\w\d\.\,\-\:\;\'\"\'])/is","</p>\n<p>\\1",$text);

//	return $text;
}

?>