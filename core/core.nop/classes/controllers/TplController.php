<?php
/**
 *  ���������� ������ ������� c������� ��� ����� �� ��� ������
 *
 */

Finder::useClass("controllers/BasicController");
class TplController extends BasicController
{
	protected $plugins = array(
		array('MenuPlugin', array(
			'__aspect' => 'MainMenu',
			'store_to' => 'menu',
			'level' => 2,
			'depth' => 2,
		)),
	);

	function handle()
	{
        //�������� ���
        parent::handle();

        $this->rh->tpl->set('tpl', 'tpl');
        $this->siteMap = rtrim(implode("/", $this->params), "/");
	}

}
?>