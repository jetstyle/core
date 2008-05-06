<?php
/**
 * Draggable List
 *
 * @author nop@jetstyle.ru
 * @version 0.1
 */

$this->UseClass('ListAdvanced');
  
class ListDrugs extends ListAdvanced  
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
    $this->rh->tpl->set('table_name', $this->table_name);   
    parent::Handle();
 }
}
  
?>