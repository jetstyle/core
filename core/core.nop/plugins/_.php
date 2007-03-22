<?php

/**		
 *	{{!text tag=*}}	     
 *
 *	��������� �����
 *
 *	���� ����� ��� textDb, ������ ��������� ���������
 *
 */

/* ���� tag ���� � ��������� �������, �� ������ �� ������� :P */

if (!defined('__MESSAGE_PLUGIN'))
{
	function __Message_Plugin_Get_Text($tag, $ctx=NULL)
	{
		static $rh, $m;
		if (isset($ctx)) 
		{ 
			if (!isset($rh)) $rh = $ctx; 
			return; 
		}

		if (!isset($m))
		{
			$rh->useClass("models/Texts");
			$m =& new Texts();
			$m->fields = array('title', 'title_pre', '_supertag');
			$m->initialize($rh);
		}
		$m->load(' AND title='.$m->quote($tag));
		if (empty($m->data))
		{	
			if (!class_exists('Translit')) $rh->useLib('Translit/php', 'translit');
			if (class_exists('Translit'))
			{
				$t = Translit::Supertag($tag);
				$row = array(
					'_supertag' => $t,
					'title_pre' => $tag,
					'title' => $tag,
				);
				$m->insert($row);
				$m->data = array($row);
			}
		}
		return $m->data[0];
	}
}
define ('__MESSAGE_PLUGIN', 1);

$key = $params['tag']?$params['tag']:$params[0]; // ���

$key = TemplateEngineCompiler::_phpString($key);

$data_sources = array(); // ��� ����� ������ ������

__Message_Plugin_Get_Text(NULL, &$rh);
$data_sources[] = '__Message_Plugin_Get_Text('.$key.')';
// ����� �� ����������
// lucky: ��� $key ��� ��� ���������
if ($rh->use_fixtures)
	$data_sources[] = '$rh->FindScript("fixtures", '.$key.')';

foreach ($data_sources as $source)
{
	$expr = '$_ = '.$source.'; return $_;';
	$item = eval($expr);
	if (isset($item))
		break;
}

if (!isset($item)) return;

echo $item['title_pre'];

?>
