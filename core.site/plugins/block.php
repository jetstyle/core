<?php
/**
 * Общий workflow такой:
 *
 * Шаблонизатор парсит инклуды и те, которые начинаются с "b_" пытается прогнать через этот плагин, который
 * 1. Locator::getBlock() - создает объект класса блока из classes/blocks/ с конфигом блока из skins/.../conf
 * 2. Если у текущего контроллера есть метод *willRender - то он вызовется
 * 3. Данные объекта блока возьмутся ->getData() и установятся в store_to (по-умолчанию это *)
 * 4. Отпарсится шаблон блока 
 *
 * @param $params["ret"] - имя блока
 * @param $params["ret"] - вместо echo вернуть отпарсенный шаблон
 * @param $params["force_create"] - не кешировать блок, по-умолчанию false
 * @param $params["store_to"] - куда положить данные блока (->getData()), по-умолчанию *
 *
 * nop@jetstyle.ru
 */
$blockParts = explode('::', $params[0]);

if (count($blockParts) > 1)
{
    $blockName = str_replace(" ", "", ucwords($blockParts[0]." ".str_replace("_"," ",$blockParts[1])));;
    $tplName = $blockParts[0].'/blocks/'.$blockParts[1].'.html';
}
else
{
    $blockName = str_replace(" ","",ucwords( str_replace("_"," ", str_replace("b_", "", $blockParts[0]))));;
    $tplName = 'blocks/'.$blockParts[0].'.html';
}

Debug::trace('Blocks: parse template ['.$tplName.'] for block ['.$blockName.']', 'blocks');

unset($params[0]);

try
{
    $block = Locator::getBlock($blockName, $params['force_create']);
}
catch(FileNotFoundException $e)
{

    $tpl->pushContext();
    $tpl->load($params);
    
    //FIXME: very dirty hack
    $parts = explode(".", $tplName);
    $tpl->set("_", $tpl->get("images")."../templates/".$parts[0]."/");

    echo $tpl->parse($tplName);
    $tpl->popContext();

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

//FIXME: почему этот $blockName не такой как $blockName для Locator`a ?
$blockName = strtolower(substr($blockName,0,1)).substr($blockName, 1);

if ($controller)
{
    $method = $blockName."WillRender";
    if (method_exists($controller, $method))
    {
        $controller->$method($block);
    }
}

//Данные из блока. Подготовленные в его constructData
$params[$storeTo] = $block->getData();

$tpl->pushContext();
$tpl->load($params);
$res = $tpl->parse($tplName);

$tpl->popContext();

if ($block->getParam('ret'))
    return $res;
else
    echo $res;

?>
