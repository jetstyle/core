<?
/*
  ���������� ��� ��� HTML-������.
  ������ ������ ��������. ���� � �� ��� ������ - ������������ ��� ���. �����, ������������ ������ ������.
*/
function gen_lid( $text, $delim = "==##==" ){
  $text = preg_replace("/<\/p>/i",$delim,$text);
  $A = explode($delim,$text);
  if(!preg_match("/<table /i",$A[0]))
    return preg_replace("/<p.*?>/i",'',$A[0]);
  return "";
}
?>