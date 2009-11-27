<?php
$template = substr($params[0], 1);
unset($params[0]);
unset($params['_name']);
$tpl->pushContext();
$tpl->load($params);
echo $tpl->parse($template);
$tpl->popContext();
?>