<?
/*
    Trash( $rh ) -- ����� ��� ������ � ��������
      $rh -- ������ �� $rh
      
  ---------
  * Delete( $table_name, $item_id, $module_title, $item_title, $view_link ) --
            ������� ������ � ������� ����, ���� ��� ������ ��� �������� � �������, ������� � ������������
      - $table_name -- ��� ������� � ��, ������ �� ������� ����� "�������"
      - $item_id -- ID ������ � ���� �������
      - $module_title -- �������� ������, ������� �������� �������
      - $item_title -- �������� ��������, ������� �������
      - $view_link -- ������ �� �������� ������� ��������
  
  * ToTrash( $table_name, $item_id, $module_title, $item_title, $view_link ) -- 
            ���������� ��������� ������ � �������, ���������  ����� ��, ��� � Delete()
  
  * FromTrash( $table_name, $item_id ) -- ��������������� ������ �� �������
      - $table_name -- ��� ������� � ��, ������ �� ������� ����� "�������"
      - $item_id -- ID ������ � ���� �������
  
  * Erase( $table_name, $item_id ) -- ������� ������ ������������
        ��������� ����� ��, ��� � FromTrash()
  
  * _ReadTrashRecord( $table_name, $item_id ) -- ���������� ������������� ������ �� ������ ������ ��� ��������� ������
        ��������� ����� ��, ��� � FromTrash()
  
  ---------
���������:
"����������� � �������" - �������� ������ ���������� ��� '_state=2',
    � �������� ������ �������� ������ �� �������� ��������
"�������������� �� �������" - �������� ������ ���������� ��� '_state=0',
    ��������� ��������������� ������ �� ������ ������
"������������� ��������" - �������� ������ ��������� �� ����� �������
    ��������� ��������������� ������ �� ������ ������
  
=============================================================== v.1 (Zharik)
*/
  
class Trash {
  
  var $rh;
  var $table_trash;
  var $table_trash_tables;
  var $table_id_field="id";
  
  function Trash(&$rh){
    $this->rh =& $rh;
    $this->table_trash = $rh->project_name.'_trash';
    $this->table_trash_tables = $rh->project_name.'_trash_tables';
  }
  
  function Delete( $table_name, $item_id, $module_title, $item_title, $view_link )
  {
    $sql = "SELECT ".$this->table_id_field.", _state FROM $table_name WHERE ".$this->table_id_field."='$item_id'";
    $rs = $this->rh->db->execute($sql);

    if( $rs->fields['_state']!=2 ){
      //������� � �������
      $this->ToTrash($table_name, $item_id, $module_title, $item_title, $view_link);
      return 1;
    }else{
        var_dump($table_name);
        echo '<hr>';
        var_dump($item_id);
        echo '<hr>';
        //die('erase');
      //������� ������
      $this->Erase($table_name, $item_id);
      return 2;
    }
  }
  
  function ToTrash( $table_name, $item_id, $module_title, $item_title, $view_link )
  {
    $db =& $this->rh->db;
    //�������� ������ � �������� ������� ��� ��������
    $db->execute("UPDATE $table_name SET _state=2 WHERE ".$this->table_id_field."='$item_id'");
    //��������� ������ � ������� ������
    $rs = $db->execute("SELECT id FROM ".$this->table_trash_tables." WHERE table_name='$table_name'");
    if( !$rs->EOF ) $table_id = $rs->fields['id'];
    else
    {
      $db->execute("INSERT INTO ".$this->table_trash_tables."(table_name) VALUES('$table_name')");
      $table_id = $db->Insert_ID();
    }
    $sql = "INSERT INTO ".$this->table_trash."(table_id,item_id,module_title,item_title,view_link)";
    $sql .= " VALUES('$table_id','$item_id','$module_title','$item_title','$view_link')";
    $db->execute($sql);
  }
  
  function FromTrash( $table_name, $item_id ){
    $db =& $this->rh->db;
    //������ ������ �� ������� ������
    $r = $this->_ReadTrashRecord($table_name, $item_id);
    //������� ������� "�����" � �������� �������
    $db->execute("UPDATE ".$r->table_name." SET _state=0 WHERE id='".$r->item_id."'");
    //������� ������ �� ������� ������
    $db->execute("DELETE FROM ".$this->table_trash." WHERE id='".$r->trash_id."'");
  }
  
  function Erase( $table_name, $item_id )
  {
    $db =& $this->rh->db;
    //������ ������ �� ������� ������
    $r = $this->_ReadTrashRecord($table_name, $item_id);
    //var_dump($r);
    //die();
    //������� ������ � �������� �������
    if($r->table_name)
      $db->execute("DELETE FROM ".$r->table_name." WHERE ".$this->table_id_field."='".$r->item_id."'");
    //������� ������ �� ������� ������
    $db->execute("DELETE FROM ".$this->table_trash." WHERE id='".$r->trash_id."'");
  }
  
  function _ReadTrashRecord( $table_name, $item_id ){
    $sql = "SELECT tr.id as trash_id, table_id, table_name, item_id";
    $sql .= " FROM ".$this->table_trash." as tr, ".$this->table_trash_tables." as tb ";
    $sql .= " WHERE tb.id=tr.table_id AND tr.item_id='$item_id' AND tb.table_name='$table_name'";
    $rs = $this->rh->db->execute($sql);
    return (object)$rs->fields;
  }
}
  
?>