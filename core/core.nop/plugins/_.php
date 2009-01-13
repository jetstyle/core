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
	define ('_PLUGIN_MESSAGE_MODE_TEXT', 1);
	define ('_PLUGIN_MESSAGE_MODE_MESSAGE', 2);

	function __Plugin_Message_GetText($msgid, $text, $ctx=NULL)
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
			$m->initialize($rh);
		}

		if (empty($msgid)) $msgid = substr(strip_tags($text), 0, 20).'...';

		$m->load(' AND (mode IN (1,2)) AND msgid='.$m->quote($msgid));
		if (empty($m->data))
		{
			if (!class_exists('Translit')) $rh->useLib('Translit/php', 'translit');
			if (class_exists('Translit'))
			{
				$t = Translit::Supertag($msgid);
				$row = array(
					'_supertag' => $t,
					'msgid' => $msgid,
					'text' => $text,
					'text_pre' => $text,
					'mode' => 2,
				);
				$m->insert($row);
				$m->data = array($row);
			}
		}
		return $m->data[0];
	}

	function __Plugin_Message_GetMessage($msgid, $tag, $ctx=NULL)
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
			$m->fields = array('title', 'title_pre', 'msgid', '_supertag');
			$m->initialize($rh);
		}

		if (empty($msgid)) $msgid = $tag;

		$m->load(' AND mode=0 AND msgid='.$m->quote($msgid));
		if (empty($m->data))
		{	
			if (!class_exists('Translit')) $rh->useLib('Translit/php', 'translit');
			if (class_exists('Translit'))
			{
				$t = Translit::Supertag($msgid);
				$row = array(
					'_supertag' => $t,
					'title_pre' => $tag,
					'title' => $tag,
					'msgid' => $msgid,
					'mode' => 0,
				);
				$m->insert($row);
				$m->data = array($row);
			}
		}
		return $m->data[0];
	}
}
define ('__MESSAGE_PLUGIN', 1);

$data_sources = array(); // ��� ����� ������ ������
$tpl->_SpawnCompiler();

if (isset($params['_']) || isset($params['text'])) 
{
	// ��� ��������������� �����
	$field_name = 'text_pre';
	$key = $params['text']?$params['text']:$params['_']; // �����
	$key = TemplateEngineCompiler::_phpString($key);
	$msgid = $params['msgid']; // ���
	$msgid = isset($msgid) 
		? TemplateEngineCompiler::_phpString($msgid) 
		: 'NULL';
	__Plugin_Message_GetText(NULL, NULL, &$rh);
	$data_sources[] = '__Plugin_Message_GetText('.$msgid.', '.$key.')';
}
else 
{
	// ��� �������
	$field_name = 'title_pre';
	$key = $params['tag']?$params['tag']:$params[0]; // ���
	$key = TemplateEngineCompiler::_phpString($key);
	$msgid = $params['msgid']; // ���
	$msgid = isset($msgid) 
		? TemplateEngineCompiler::_phpString($msgid) 
		: 'NULL';
	__Plugin_Message_GetMessage(NULL, NULL, &$rh);
	$data_sources[] = '__Plugin_Message_GetMessage('.$msgid.','.$key.')';
}

// ����� �� ����������
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

echo $item[$field_name];

?>
