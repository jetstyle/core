<?php

$this->useClass("FormSimple");

class FormConfig extends FormSimple
{
	protected $template_item = "form_config.html";
	protected $row_number = 0;

	function Handle()
	{
		$tpl =& $this->rh->tpl;
		$this->load();

		$tpl->set('prefix', $this->prefix);
		foreach ($this->ITEMS AS $r)
		{
			//массив
			if ($r['type']==1 && false)
			{
				$vars = unserialize($r['value']);
				//var_dump($vars);
				//die();
				$this->renderTable($vars, $r);
			}
			//строка
			else
			{
				$this->row_number = 0;
				$tpl->setRef('*', $r);
				$tpl->parse($this->template_item.":Row", "rows", 1);
			}
		}
		$tpl->set("keys_length", $this->row_number);
		parent::handle();
	}
	 
	function renderTable($table, &$item)
	{
		$tpl=& $this->rh->tpl;
		 
		$tpl->set("_rows", "");
		foreach ($table as $row)
		{
			if ($this->row_number++ == 0)
			$this->renderArrayRow($row, "ArrayTD");

			$this->renderArrayRow($row);
		}
		 
		$tpl->set("_keys",  implode(",", array_keys($row)));
		//$tpl->parsE($this->template_item.":Td", "_keys", 1);
		 
		$item['row_number_1'] = $this->row_number-1;
		 
		$tpl->setRef("*", $item);
		$tpl->parse($this->template_item.":ArrayTable", "rows", 1);
		 
	}
	 
	function renderArrayRow($row, $tplt="ArrayInput" )
	{
		$tpl =& $this->rh->tpl;
		$tpl->set("cells", "");
		foreach ($row as $name=>$value)
		{
			$item = array("name"=>$name,
                          "value"=>$value,
                          "num"=>$this->row_number 
			);
			$tpl->setRef("*",$item);
			$tpl->parsE($this->template_item.":".$tplt, "cells", 1);
		}
		$tpl->parsE($this->template_item.":ArrayRow", "_rows", 1);
	}
	 
	 
	function load()
	{
		$sql = "SELECT * FROM ??".$this->config->table_name;
		//die($sql);
		$this->rh->db->execute($sql);
		while ($row = $this->rh->db->getRow())
		{
			$this->ITEMS[$row[$this->idField]] = $row;
		}

	}
	 
	 
	function update()
	{
		$db =& $this->rh->db;

		foreach ($this->ITEMS as $item)
		{
			if ($item['name']!='')
			{
				$value = $_POST[$this->prefix.$item['name']];
				$type  = intval($_POST[$this->prefix.$item['name']."_type"]);
				$title = $_POST[$this->prefix.'td_'.$item['name']];

				/*
				 if ($type==1)
				 {
				 //ключи массива
				 $value_parts = explode(",",$value);
				 //echo '<h2>'.$value.'</h2>';
				 foreach ($_POST as $p=>$v)
				 {
				 foreach ($value_parts as $vi)
				 {
				 //ключ массива
				 $vi = trim($vi);

				 //echo $p.' ,';
				 //не является ли прилетевшая переменная типа "ключ_"?
				 $pparts =  explode($vi."_", $p);

				 if ( $pparts[0]=="" && $pparts[1] > 0 )
				 {
				 //echo '<hr>'.$p." = ".$v;
				 $real_value[$pparts[1]][$vi] = $v;
				 }
				 }
				 }
				 //var_dump($real_value);
				 //die();
				 $value = serialize($real_value);

				 }
				 */

				$sql = "UPDATE ??".$this->config->table_name." SET ".(empty($title) ? "" : "title=".$db->quote($title).",")." type=".$type.", value=".$this->rh->db->quote($value)." WHERE name=".$this->rh->db->quote($item['name']);
				$this->rh->db->execute($sql);
			}
		}

		return true;
	}
}
?>