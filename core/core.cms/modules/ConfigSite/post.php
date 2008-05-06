<?php
    
    if (function_exists("iconv"))
    {
        $title = iconv("UTF-8", "CP1251", $this->rh->getVar('title'));
        $name = str_replace("td_", "", $this->rh->getVar('name'));
        if (!empty($title) && !empty($name))
        {
            $sql = "UPDATE ??config SET title=".$this->rh->db->quote($title)." WHERE name=".$this->rh->db->quote($name);
            $this->rh->db->execute($sql);
            
            die('ok ');  
        }
        
        die('not enough params');
    }
    else
        die('please install iconv');
?>