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
 	if (RequestInfo::get('order_list'))
 	{
 		$db = &Locator::get('db');
    	$orders = explode(",",$_POST['order_items']) ;
	    foreach ($orders as $i=>$order)
	    {
	        $sql = "UPDATE ??".$this->config->table_name." SET _order=".$db->quote($i)." WHERE id=".$db->quote($order);
	        $db->execute($sql);
	    }
	    die('1');
 	}
 	if ($_GET['delete_list'])
 	{
 		$db = &Locator::get('db');
        $items = $this->loadItems($_GET['items_list']);
        $deleteItems = $updateItems = array();
	    foreach ($items as $i=>$item)
	    	if ($item['_state'] == 2)            	$deleteItems[] = $item['id'];
	    	else                $updateItems[] = $item['id'];
      	if (!empty($deleteItems))
      		$db->execute("DELETE FROM ??".$this->config->table_name." WHERE id IN (".implode(',',$deleteItems).")");
      	if (!empty($updateItems))
      		$db->execute("UPDATE ??".$this->config->table_name." SET _state = _state + 1  WHERE id IN (".implode(',',$updateItems).")");
      	Controller::redirect(RequestInfo::hrefChange('',array('delete_list'=>'', 'items_list'=>'')));
 	}
 	if ($_GET['restore_list'])
 	{
 		$db = &Locator::get('db');
        $items = $this->loadItems($_GET['items_list']);
        $updateItems = array();
	    foreach ($items as $i=>$item)
			if ($item['_state'] > 0) $updateItems[] = $item['id'];
      	if (!empty($updateItems))
      		$db->execute("UPDATE ??".$this->config->table_name." SET _state = _state - 1  WHERE id IN (".implode(',',$updateItems).")");
      	Controller::redirect(RequestInfo::hrefChange('',array('restore_list'=>'', 'items_list'=>'')));
 	}
 	$tpl = &Locator::get('tpl');
    $tpl->set('page_url', RequestInfo::$baseUrl.RequestInfo::$pageUrl);
    $tpl->set('group_delete_url', RequestInfo::hrefChange('',array('delete_list'=>'1')));
    $tpl->set('group_restore_url', RequestInfo::hrefChange('',array('restore_list'=>'1')));
    $tpl->set('group_operations', $this->config->group_operations);
    parent::Handle();
 }

	function loadItems($items) {		$db = &Locator::get('db');
		$items = explode(',',$items);
		foreach ($items as &$item) {        	$item = intval($item);
		}
		$items = implode(',',$items);
    	$items = $db->query("
    		SELECT id, _state
    		FROM ??".$this->config->table_name."
    		WHERE id IN (".$items.")"
    	);
    	return $items;
	}
}

?>