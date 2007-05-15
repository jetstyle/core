<?
/*
    Trash( $rh ) -- Класс для работы с корзиной
      $rh -- ссылка на $rh
      
  ---------
  * Delete( $table_name, $item_id, $module_title, $item_title, $view_link ) --
            удаляет запись в корзину либо, если эта запись уже помещена в корзину, удаляет её окончательно
      - $table_name -- имя таблицы в БД, запись из которой нужно "удалить"
      - $item_id -- ID записи в этой таблице
      - $module_title -- название модуля, элемент которого удаляем
      - $item_title -- название элемента, который удаляем
      - $view_link -- ссылка на просмотр данного элемента
  
  * ToTrash( $table_name, $item_id, $module_title, $item_title, $view_link ) -- 
            перемещает указанную запись в корзину, параметры  такие же, как у Delete()
  
  * FromTrash( $table_name, $item_id ) -- восстанавливает запись из корзины
      - $table_name -- имя таблицы в БД, запись из которой нужно "удалить"
      - $item_id -- ID записи в этой таблице
  
  * Erase( $table_name, $item_id ) -- удаляет запись окончательно
        параметры такие же, как у FromTrash()
  
  * _ReadTrashRecord( $table_name, $item_id ) -- возвращает синтетическую запись из таблиц мусора для указанной записи
        параметры такие же, как у FromTrash()
  
  ---------
Концепция:
"перемещение в корзину" - исходная запись помечается как '_state=2',
    в таблицах мусора создаётся запись об удалённом элементе
"восстановление из корзины" - исходная запись помечается как '_state=0',
    удаляются соответствующие записи из таблиц мусора
"окончательное удаление" - исходная запись удаляется из своей таблицы
    удаляются соответствующие записи из таблиц мусора
  
=============================================================== v.1 (Zharik)
*/
  
// поместить в конзину
define ('OSB_TRASH_WASTE', 1);
// удалить навсегда
define ('OSB_TRASH_ERASE', 2);

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
    $this->rh->db->execute($sql);
    $row = $this->rh->db->getRow();

    if( $row['_state']!=2 )
    {
      //удаляем в корзину
      $this->ToTrash($table_name, $item_id, $module_title, $item_title, $view_link);
      return OSB_TRASH_WASTE;
    }
    else
    {
      //удаляем совсем
      $this->Erase($table_name, $item_id);
      return OSB_TRASH_ERASE;
    }
  }
  
  function ToTrash( $table_name, $item_id, $module_title, $item_title, $view_link )
  {
    $db =& $this->rh->db;
    //помечаем запись в исходной таблице как удалённую
    $db->execute("UPDATE $table_name SET _state=2 WHERE ".$this->table_id_field."='$item_id'");
    //вставляем запись в таблицу мусора
    $rs = $db->queryOne("SELECT id FROM ".$this->table_trash_tables." WHERE table_name='$table_name'");
    
    if( $rs['id'] ) $table_id = $rs['id'];
    else
    {
      $s = "INSERT INTO ".$this->table_trash_tables."(table_name) VALUES('$table_name')";
      $table_id =  $db->Insert($s);
      /*
      echo $s.'<br>';
      var_dump($table_id);
      die();
      */
    }
    $sql = "INSERT INTO ".$this->table_trash."(table_id,item_id,module_title,item_title,view_link)";
    $sql .= " VALUES('$table_id','$item_id','$module_title','$item_title','$view_link')";
    $db->execute($sql);
  }
  
  function FromTrash( $table_name, $item_id ){
    $db =& $this->rh->db;
    //читаем запись из таблицы мусора
    $r = $this->_ReadTrashRecord($table_name, $item_id);
    //снимаем пометку "удалён" в исходной таблице
    $db->execute("UPDATE ".$r->table_name." SET _state=0 WHERE id='".$r->item_id."'");
    //удаляем запись из таблицы мусора
    $db->execute("DELETE FROM ".$this->table_trash." WHERE id='".$r->trash_id."'");
  }
  
  function Erase( $table_name, $item_id )
  {
    $db =& $this->rh->db;
    //читаем запись из таблицы мусора
    $r = $this->_ReadTrashRecord($table_name, $item_id);
    //удаляем запись в исходной таблице
    if($r->table_name)
      $db->execute("DELETE FROM ".$r->table_name." WHERE ".$this->table_id_field."='".$r->item_id."'");
    //удаляем запись из таблицы мусора
    $db->execute("DELETE FROM ".$this->table_trash." WHERE id='".$r->trash_id."'");
  }
  
  function _ReadTrashRecord( $table_name, $item_id ){
    $sql = "SELECT tr.id as trash_id, table_id, table_name, item_id";
    $sql .= " FROM ".$this->table_trash." as tr, ".$this->table_trash_tables." as tb ";
    $sql .= " WHERE tb.id=tr.table_id AND tr.item_id='$item_id' AND tb.table_name='$table_name'";
    $rs = $this->rh->db->execute($sql);
    //die($sql);
    //var_dump($this->rh->db->getRow());
    return $this->rh->db->getObject();
  }
}
  
?>
