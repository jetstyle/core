<?php
    class FKSelect
{
    function FKSelect(&$rh, $table)
    {
       $this->rh =& $rh; 
       $this->table = $table;
    }
    
    function getValues($show_empty = false, $default = false)
    {
        $this->rh->db->execute('SELECT `id`, `title` '.($default ? ", `_default`" : "").' FROM `'.$this->table.'` WHERE `_state` = 0 ORDER BY `title` ASC');
        $list = array();
        if($show_empty)
        {
        	$list = array("выбрать");
        }
        
        while ($row=$this->rh->db->getRow())
        {
          $list[$row['id']] = $row['title'];
          if($row['_default'])
          {
          	$this->default = $row['id'];
          }
        }
        return $list;
    }
    
    function getValuesInTitle($id="id", $title="title", $where="")
    {

        
        $this->rh->db->execute('SELECT '.$id.', '.$title.' FROM `'.$this->table.'` WHERE `_state` = 0 '.$where.' ORDER BY '.$title.' ASC');
        $list = array("0" => "пусто");
        while ($row = $this->rh->db->getRow())
        {
          $list[$row[$id]] = $row[$title];
        }
        return $list;
    }
    
    function getDefault()
    {
    	return $this->default;
    }
}

?>