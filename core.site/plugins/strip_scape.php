<?php
/* ������������ �� Smarty. */
/* lucky: ������������ � CMS Builder. */


$string = isset($params[0]) ? $params[0] : $params['_'];
//var_dump($string);
$res = str_replace("<br />", "&nbsp;", $string );
$res = strip_tags($res);
echo $res;

?>
