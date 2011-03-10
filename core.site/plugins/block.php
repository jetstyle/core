<?php
/**
 * ����� workflow �����:
 *
 * ������������ ������ ������� � ��, ������� ���������� � "b_" �������� �������� ����� ���� ������, �������
 * 1. Locator::getBlock() - ������� ������ ������ ����� �� classes/blocks/ � �������� ����� �� skins/.../conf
 * 2. ���� � �������� ����������� ���� ����� *willRender - �� �� ���������
 * 3. ������ ������� ����� ��������� ->getData() � ����������� � store_to (��-��������� ��� *)
 * 4. ���������� ������ ����� 
 *
 * @param $params["ret"] - ��� �����
 * @param $params["ret"] - ������ echo ������� ����������� ������
 * @param $params["force_create"] - �� ���������� ����, ��-��������� false
 * @param $params["store_to"] - ���� �������� ������ ����� (->getData()), ��-��������� *
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

//FIXME: ������ ���� $blockName �� ����� ��� $blockName ��� Locator`a ?
$blockName = strtolower(substr($blockName,0,1)).substr($blockName, 1);

if ($controller)
{
    $method = $blockName."WillRender";
    if (method_exists($controller, $method))
    {
        $controller->$method($block);
    }
}

//������ �� �����. �������������� � ��� constructData
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
