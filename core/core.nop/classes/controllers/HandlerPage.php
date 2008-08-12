<?php
/**
 *  �������� ��� ����������� ��������
 */

Finder::useClass("controllers/BasicPage");
class HandlerPage extends BasicPage
{

	function initialize(&$ctx, $config=NULL)
	{
		$this->path = $this->url;
		parent::initialize($ctx, $config);
	}

	function handle()
	{
		parent::handle();
		$handler = $this->config['handler'];
		$status = $this->rh->executeHandler($handler);
		// lucky@npj: �.�. ����� ������ � ���� ���������, ��� ���..
		return True;
	}

}


?>
