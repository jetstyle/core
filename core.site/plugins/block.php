<?php
/**
 * 
 */
// underscored to camel case
$blockName = str_replace(" ","",ucwords(str_replace("_"," ",$params[0])));;
$tplName = 'blocks/'.$params[0].'.html';

Debug::trace('Blocks: parse block '.$params[0], 'blocks');

unset($params[0]);

try
{
	$block = Locator::getBlock($blockName);
}
catch(FileNotFoundException $e)
{
	$stackId = $tpl->addToStack($params);
	echo $tpl->parse($tplName);
	$tpl->freeStack($stackId);
	return;// $tpl->parse($tplName);
}

$block->setTplParams($params);

if ($controller = Locator::get('controller', true))
{
	$method = strtolower(substr($blockName,0,1)).substr($blockName,1)."WillRender";
	if (method_exists($controller, $method))
	{
		$controller->$method($block);
	}
}

// default
$storeTo = '*';
if ($params['store_to'])
{
	$storeTo = $params['store_to'];
}
else
{
	$config = $block->getConfig();
	if ($config['store_to'])
	{
		$storeTo = $config['store_to'];
	}
}

$params[$storeTo] = $block->getData();
$stackId = $tpl->addToStack($params);
if ($params['ret'])
{
	$res = $tpl->parse($tplName);
	$tpl->freeStack($stackId);
	return $res;
}
else echo $tpl->parse($tplName);
$tpl->freeStack($stackId);
?>
