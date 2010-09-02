<?php
if (!$params['_']) $params['_'] = date('Y-m-d H:i:s');
if (!$params['format']) $params['format'] = '%d.%m.%Y';
return strftime($params['format'], strtotime($params['_']));
?>