<?php
/**
 *  �������� ��� ����������� ��������
 */

$this->UseClass("controllers/BasicPage");
class HandlerPage extends BasicPage
{

	function handle()
	{
		$handler = $this->config['handler'];
		$status = $this->rh->executeHandler($handler);
		// lucky@npj: �.�. ����� ������ � ���� ���������, ��� ���..
		return True;
	}

}	


?>
