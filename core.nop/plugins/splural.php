<?php

/**		
 * ������ splural -- �������� ����� �����, ��������� � �������� ���������
 *
 *
 * ������
 *	{{!splural *v do=@:t}}
 *		  HINT: ������ �.�. ��������:
 *				{{:t Text.singualr}}����{{/:t}}
 *				{{:t Text.dual	   }}���{{/:t}}
 *				{{:t Text.plural  }}����{{/:t}}
 *
 *	2 ��� 3 ������ ����
 *	{{!splural *v "����" "���" "����"}}
 *
 *	���������� �����
 *	{{!splural *v "�" "���" "��" "���"}}
 *
 */

$value = $params['value']?$params['value']:$params[0]; // ����

$s = array();

$d1 = $value % 10; // ��������� ����
$d2 = ($value / 10) % 10; // ������������� ����

if ($d2 == 1) // 10, 11, 12, .. 19
{
	$s['plural'] = True;
	$s['form'] = 3;
}
else
{
	if ($d1 == 1)
	{
		$s['singular'] = True;
		$s['form'] = 1;
	}
	else
	if ($d1 == 2 || $d1 == 3 || $d1 == 4)
	{
		$s['dual'] = True;
		$s['form'] = 2;
	}
	else // 5, 6, 7, 8, 9, 0
	{
		$s['plural'] = True;
		$s['form'] = 3;
	}
}

$template_name = $params['do']?$params['do']:$params['use']; // ������

if (isset($template_name))
// ���������: ������ �����
{
	$old_s =& $tpl->get("Text");
	$tpl->setRef("Text", $s);
	$out = $tpl->parse(substr($template_name, 1));
	$tpl->setRef("Text", $old_s );
}
else
if (isset($params[4])) 
// ���������: ���������� �����
{
	$root = $params[1];
	switch ($s['form'])
	{
	case 1: $out = $root.$params[2]; break;
	case 2: $out = $root.$params[3]; break;
	case 3: $out = $root.(isset($params[4]) ? $params[4] : $params[3]); break;
	}
}
else						  
// ���������: �������������� �����
{
	switch ($s['form'])
	{
	case 1: $out = $params[1]; break;
	case 2: $out = $params[2]; break;
	case 3: $out = isset($params[3]) ? $params[3] : $params[2];
	}
}

echo $out;

?>
