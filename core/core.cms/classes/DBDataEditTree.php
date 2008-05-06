<?
/*
  DBDataEditTree -- класс для работы с деревом, хранящимся в таблице в БД: модификация
  ---------
  
  * DBDataEditTree( &$rh, $table_name, $SELECT_FIELDS=array(), $where="" )
      конструктор, параметры аналогичны DBDataEdit()
      вызывает также DBDataEdit::DBDataEdit

  * Restoe( $parent_id=0, $left=0 ) -- рекурсия, 
      восстанавливает вторичные статистики (_left, _right - см. traversaltree) по первичным (_parent)
      $parent_id -- ID узла, с которого начинаеть восстановление
      $left -- значение поля _left для узла, с которого начинаем восстановление

  * AddNew( $parent_id=0 ) -- добавляет новый узел, перетряхивает" вторичные статистики, возвращает ID нового узла
      $parent_id -- ID узла, к которому нужно добавить новый узел в качестве потомка

  * Delete($id) -- удаляет узел с указанным ID вместе с поддеревом, "перетряхивает" вторичные статистики
      $id -- ID узла, который нужно удалить
      
  * UpdateTree( $sql, $id=0 ) -- выполняет данный запрос на поддерево указанного узла, включая сам узел
      $sql -- шаблон sql-запроса, должен содержать вхождение '{$where}', которое будет заменено на поддерева
      $id -- ID узла, чъё поддерево нужно модифицировать

  * Exchange( $id1, $id2 ) -- меняет местами два узла, "перетряхивает" вторичные статистики
      на самом деле, просто меняет значения полей _order у узлов
      имеет смысл вызывать только для "братьев"
      $id1, $id2 -- ID узлов, которые нужно поменять местами

  * MoveUnder( $item_id, $folder_id ) -- перемещает один узел как потомок к другому
      осуществляет проверку на корректность, "перетряхивает" вторичные статистики
      $item_id -- ID узла, который нужно переместить
      $folder_id -- ID узла, под который нужно переместить

=============================================================== v.1 (Zharik)
*/

  $this->UseClass("DBDataEdit");
  
class DBDataEditTree extends DBDataEdit {
  
  function DBDataEditTree( &$rh, $table_name, $SELECT_FIELDS=array(), $where="" ){
    $SELECT_FIELDS = array_merge( $SELECT_FIELDS, array('_parent','_level','_left','_right','_order') );
    DBDataEdit::DBDataEdit( $rh, $table_name, $SELECT_FIELDS, $where, "_parent ASC, _order ASC" );
    //нужно грузить по id
    $this->result_mode = 2;
  }
  
  function Restore( $parent_id=0, $left=0 ) {
    
    //shortcuts
    $node =& $this->ITEMS[ $parent_id ];
    
    //_level
    if($node['id'])
      $node['_level'] = $this->ITEMS[ $node['_parent'] ]['_level'] + 1;
    
    /* Taken from http://www.sitepoint.com/article/1105/3 */
    
    // the right value of this node is the left value + 1
    $right = $left + 1;
    
    // get all children of this node
    $A =& $this->CHILDREN[$parent_id];
    $n = count($A); 
    for($i=0;$i<$n;$i++){
      // recursive execution of this function for each
      // child of this node
      // $right is the current right value, which is
      // incremented by the rebuild_tree function
      $right = $this->Restore( $A[$i], $right);
    }
    
    // we've got the left value, and now that we've processed
    // the children of this node we also know the right value
    $node['_left'] = $left;
    $node['_right'] = $right;
    
    //store in DB
//    print("UPDATE ".$this->table_name." SET _level='".$node['_level']."', _left='".$node['_left']."', _right='".$node['_right']."' WHERE id='".$node['id']."'<br>\n");
    $this->rh->db->execute("UPDATE ".$this->table_name." SET _level='".$node['_level']."', _left='".$node['_left']."', _right='".$node['_right']."' WHERE id='".$node['id']."'");
    
    // return the right value of this node + 1
    return $right + 1;
  }
  
  function AddNew( $parent_id=0 ){
    //add new
    $id = DBDataEdit::AddNew(array(
      '_parent' => $parent_id,
    ));
    //restore tree
    $this->Load();
    $this->Restore();
    return $id;
  }
  
  function Delete($id){
    //delete subtree as well
    $this->UpdateTree( 'DELETE FROM '.$this->table_name.' WHERE {$where}', $id );
    //delete this record
    DBDataEdit::Delete($id);
    //restore tree
    $this->Load();
    $this->Restore();
  }
  
  function UpdateTree( $sql, $id=0 ){
    $r = (object)$this->ITEMS[$id];
    $where = (($this->where)? $this->where.' AND ' : '')." _left>='".$r->_left."' AND _right<='".$r->_right."'";
    $this->rh->db->execute( str_replace( '{$where}', $where, $sql ) );
  }
  
  function Exchange( $id1, $id2 ){
    if( DBDataEdit::Exchange($id1,$id2) ){
      //restore tree
      $this->Load();
      $this->Restore();
    }
  }
  
  function MoveUnder( $item_id, $folder_id ){
    if( $item_id==$folder_id || !$item_id )
      throw new Exception('DBDataTree: $item_id==$folder_id || !$item_id');
    //shortcuts
    $item = (object)$this->ITEMS[$item_id];
    $folder = (object)$this->ITEMS[$folder_id];
    //check, if folder is subling of item
    if( $item->_left <= $folder->_left && $item->_right >= $folder->_right ) return;
    //update this item
    $this->rh->db->execute("UPDATE ".$this->table_name." SET _parent='".$folder->id."' WHERE id='".$item->id."'");
    //restore tree
    $this->Load();
    $this->Restore();
  }
  
}
?>