<?php

require_once dirname(__FILE__).'/Config.php';
/**
 * ����� JsConfigurable - �������� ���� ��������
 *
 */
class Configurable
{

	function Configurable()
	{
	}

	function initialize(&$ctx, $config=NULL)
	{
		$this->ctx =& $ctx;
		$this->rh =& $ctx; // FIXME: lucky: ������ ���, ����� RH ���������� ���� ����������

		// �������� ���� �������� �� ��������� ����
		if (isset($config)) config_joinConfigs($this, $config);
		return True;
	}

}

?>
