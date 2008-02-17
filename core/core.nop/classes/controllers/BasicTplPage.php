<?php
/**
 *  Контроллер вывода массива cайтмапа или однго из его ключей
 *  
 */

$this->UseClass("controllers/BasicPage");
class BasicTplPage extends BasicPage
{

	function handle()
	{
        //например так
        $this->rh->tpl->set('tpl', 'tpl');
        $this->rh->site_map_path= rtrim(implode("/", $this->params), "/");
	}

}	

?>
