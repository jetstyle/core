<?php
/**
 *
 */
$blockParts = explode('::', $params[0]);

if (count($blockParts) > 1)
{
    $blockName = str_replace(" ", "", ucwords($blockParts[0]." ".str_replace("_"," ",$blockParts[1])));;
    $tplName = $blockParts[0].'/blocks/'.$blockParts[1].'.html';
}
else
{
    $blockName = str_replace(" ","",ucwords(str_replace("_"," ",$blockParts[0])));;
    $tplName = 'blocks/'.$blockParts[0].'.html';
}

Debug::trace('Blocks: parse block '.$tplName, 'blocks');

unset($params[0]);

try
{
	$block = Locator::getBlock($blockName, $params['force_create']);
}
catch(FileNotFoundException $e)
{
	$stackId = $tpl->addToStack($params);
	echo $tpl->parse($tplName);
	$tpl->freeStack($stackId);
	return;
}

$block->setTplParams($params);

$storeTo = $block->getParam('store_to');
if (!$storeTo)
{
    $storeTo = '*';
}

$controller = Locator::get('controller', true);
//$blockName = get_class($block);
$blockName = strtolower(substr($blockName,0,1)).substr($blockName, 1);

if ($controller)
{
    $method = $blockName."WillRender";
    if (method_exists($controller, $method))
    {
        $controller->$method($block);
    }
}

$params[$storeTo] = $block->getData();

$stackId = $tpl->addToStack($params);
$res = $tpl->parse($tplName);
$tpl->freeStack($stackId);

if ($block->getParam('ret'))
    return $res;
else
    echo $res;

?>
