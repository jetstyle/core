<?php
$str = str_replace('.', ',', $params['_']);
list($int, $div) = explode(',', $str);
$int = number_format($int, 0, ',', ' ');
$int = str_replace(' ', '&nbsp;', $int);
if ($div) $int = $int . ',' . $div;
return $int;
?>