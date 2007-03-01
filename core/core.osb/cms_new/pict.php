<?
	//��������� ������ ����
	$reg_exp = "/\.{2,}|\:\/|\/{2,}|home\//";
	while( preg_match( $reg_exp, $img ) )
		$img = preg_replace( $reg_exp, '', $img );
	
	if(!@file_exists($img)) die('���� �� ������');
	
	//�� ������� � �������������� ������������
	$A = pathinfo($img);
	$EXTS = array('gif','jpg','png','bmp');
	$ext = strtolower($A['extension']);
	if( !in_array( $ext, $EXTS ) ){
		echo "�������� ���������� �����.<br>\n";
		echo "������ ����: <b>".implode(', ',$EXTS)."</b>.<br>\n";
		echo "� �������: <b>".$A['extension'].'</b>.';
		die();
	}
	
	//���� �� ������������
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");             // Date in the past
  header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
  header("Cache-Control: no-cache");           // HTTP/1.1
  header("Pragma: no-cache");                                   // HTTP/1.0

  //�������, ��� ��� ��������
  $HEADERS = array(
  	'gif'=>'image/gif',
  	'jpg'=>'image/jpeg',
  	'png'=>'image/png', //???
  	'bmp'=>'image/bmp', //???
  );
  header("Content-type: ".$HEADERS[$ext]);
	
	//������ ��������
	readfile($img);

?>