<?php
/**
 * Draggable List
 *
 * @author nop@jetstyle.ru
 * @version 0.1
 */

Finder::useClass('ListSimple');

class ListDrugs extends ListSimple
{

  var $template = 'list_advanced_drugs.html';
  var $template_list = 'list_advanced_drugs.html:List';
  var $template_engine = 'tree_simple.html:Tree_Engine'; //Шаблон клиентского дивжка
  var $arrows_template = 'list_advanced_drugs.html:List_Arrows';
  var $template_new = 'list_advanced_drugs.html:add_new';

  var $state; //персональный StateSet

  var $arrows; //объект постраничной рубрикации


 function handle()
 {
 	if (RequestInfo::get('order_list')) {
 		$db = &Locator::get('db');
    	$orders = explode(",",$_POST['order']) ;

	    foreach ($orders as $i=>$order)
	    {
	    	//$out .= "$order = $i \n\r";
	        $sql = "UPDATE ??".$this->config->table_name." SET _order=".$db->quote($i)." WHERE id=".$db->quote($order);
	        $db->execute($sql);
	    }
	    die('1');
 	}
 	$tpl = &Locator::get('tpl');
    $tpl->set('page_url', RequestInfo::$baseUrl.RequestInfo::$pageUrl);
    parent::Handle();
 }
}

?>