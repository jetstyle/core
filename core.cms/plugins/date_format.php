<?php
if (!$params['_']) $params['_'] = date('Y-m-d H:i:s');
if (!$params['format']) $params['format'] = '%d.%m.%Y';
$params['_'] = str_replace('0000-', date('Y') . '-', $params['_']);
return strftime($params['format'], strtotime($params['_']));
?>