<?php
$template = substr($params[0], 1);
unset($params[0]);
unset($params['_name']);
$stackId = $tpl->addToStack($params);
echo $tpl->parse($template);
$tpl->freeStack($stackId);
?>