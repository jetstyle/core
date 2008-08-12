<?php
/**
 *  ���������� ������ ������� c������� ��� ����� �� ��� ������
 *
 */

Finder::useClass("controllers/BasicPage");
class BasicTplPage extends BasicPage
{
	var $plugins = array(
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
        $this->rh->site_map_path= rtrim(implode("/", $this->params), "/");
	}

}

?>
