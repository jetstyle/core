<?
$this->UseClass("models/Model");

class BasicBread extends Model
{
    function load($where='')
    {
        if ($where =='' )
            $where = "AND _left<='".$this->rh->data['_left']."' AND _right>='".$this->rh->data['_right']."'";

        $sql = "SELECT id, title_pre, _path, _parent FROM ".$this->rh->db_prefix."content WHERE _state=0 ".$where." ORDER by _left ASC";
        $this->data = $this->rh->db->query($sql);
    }
    
    function addItem($item)
    {
        $this->data[] = $item;
    }
}
?>
