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
        $this->rh->site_map_path= rtrim(implode("/", $this->rh->params), "/");
	}

}	

?>
