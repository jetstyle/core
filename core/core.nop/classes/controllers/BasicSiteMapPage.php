<?php
/**
 *  ���������� ������ ������� c������� ��� ����� �� ��� ������
 *  
 */

$this->UseClass("controllers/BasicPage");
class BasicSiteMapPage extends BasicPage
{

	function handle()
	{
        //�������� ���
        $this->rh->tpl->set('tpl', 'tpl');
        $this->rh->site_map_path= rtrim(implode("/", $this->params), "/");
	}

}	

?>
