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
 	if ($_GET['order_list'] || $_POST['order_list'])
 	{
 		$itemId = intval($_GET['item_id'] ? $_GET['item_id'] : $_POST['item_id']);
 		$page = intval($_GET[$this->pageVar] ? $_GET[$this->pageVar] : $_POST[$this->pageVar]);
		if (!$page) $page = 1;
 		$destIndex = intval($_GET['index'] ? $_GET['index'] : $_POST['index']) + ($page - 1) * $this->perPage;

 	    $db = &Locator::get('db');
 		$destItem = $db->query(
 			"SELECT _order FROM ??".$this->config->table_name." ORDER BY _order LIMIT 1 OFFSET ".$destIndex
 		);
 		if (!$destItem[0]['_order']) {        	$destItem = $db->query(
	 			"SELECT _order FROM ??".$this->config->table_name." ORDER BY _order DESC LIMIT 1"
	 		);
 		}
 		$destOrder = $destItem[0]['_order'];
 		$sourceItem = $db->queryOne(
 			"SELECT _order FROM ??".$this->config->table_name." WHERE id = ".$itemId
 		);
 		$sourceOrder = $sourceItem['_order'];
 		if ($sourceOrder > $destOrder)
        	$db->execute("
        		UPDATE ??".$this->config->table_name."
        		SET _order = _order + 1
        		WHERE _order < ".$sourceOrder." AND _order >= ".$destOrder
        	);
 		else
 			$db->execute("
        		UPDATE ??".$this->config->table_name."
        		SET _order = _order - 1
        		WHERE _order > ".$sourceOrder." AND _order <= ".$destOrder
        	);
        $db->execute("
        	UPDATE ??".$this->config->table_name."
        	SET _order = ".$destOrder."
        	WHERE id = ".$itemId
        );
        if ($_POST['ajax_load'])
        	die('1');
        else
        	Controller::redirect(RequestInfo::hrefChange('',array('id'=>$itemId, 'order_list'=>'', 'items_id'=>'', 'index'=>'')));
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
 	/*if ($_GET['move_id'])
 	{
 		$db = &Locator::get('db');
        $items = $this->loadItems($_GET['items_list']);
        $updateItems = array();
	    foreach ($items as $i=>$item)
			if ($item['_state'] > 0) $updateItems[] = $item['id'];
      	if (!empty($updateItems))
      		$db->execute("UPDATE ??".$this->config->table_name." SET _state = _state - 1  WHERE id IN (".implode(',',$updateItems).")");
      	Controller::redirect(RequestInfo::hrefChange('',array('move_id'=>'', 'id'=>intval($_GET['move_id']))));
 	}*/
 	$tpl = &Locator::get('tpl');
    $tpl->set('page_url', RequestInfo::$baseUrl.RequestInfo::$pageUrl);
    $tpl->set('page_num', intval($_GET[$this->pageVar]));
  	$tpl->set('page_var', $this->pageVar);
  	$tpl->set('per_page', $this->perPage);
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