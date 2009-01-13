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
 	if (RequestInfo::get('order')) {
 		$db = &Locator::get('db');
    	$orders = explode(",",RequestInfo::get('order')) ;
	    $table = RequestInfo::get('table');

	    if (!empty($orders) && !empty($table))
	    foreach ($orders as $i=>$order)
	    {
	        $out .= "$order = $i \n\r";
	        $sql = "UPDATE ".$table." SET _order=".$db->quote($i)." WHERE id=".$db->quote($order);
	        $db->execute($sql);
	    }
	    die($out);
 	}
 	$tpl = &Locator::get('tpl');
    $tpl->set('table_name', Config::get('db_prefix').$this->config->table_name);
    parent::Handle();
 }
}

?>