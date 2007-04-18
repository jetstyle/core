<?
/*
  DBDataView -- класс для работы с таблицами в БД: просмотр данных
  ---------
  
  * DBDataView( &$rh, $table_name, $SELECT_FIELDS=array(), $where="", $order_by="", $limit="" )
      первое поле из $SELECT_FIELDS в дальнейшем используется как id
  
  * Load($where="") - загрузка данных из БД
      $where - дополнительное условие, добавляется через AND к уже заданным
      записи грузятся в хэш $ITEMS[id], значения id - в массив $IDS в порядке поступления
      если загружена всего одна запись, то на неё создаётся ссылка $item
  
  var $SELECT_FIELDS - список полей для загрузки
  
  var $limit - числовое ограничение для числа загружаемых записей
  
  var $ITEMS - в заисимости от $result_mode:
              0 - массив загруженных записей (в порядке поступления)
              1 - хэш id=>record для загруженных записей
              2 - хэш id=>record для загруженных записей
                  + списки потомков в $CHILDREN
  
  var $IDS - массив id загруженных записей в порядке поступления
  
  --------
  ToDo:
    - документация  OK                                          
    - общая отладка OK
    - Отрезка HTML  не здесь (вынесено в форматтеры $tpl)

=============================================================== v.1 (Zharik)
*/
  $this->UseClass('Obj');
  
class DBDataView extends Obj {
  
  var $table_name = "";
  var $where = "";
  var $order_by = "";
  var $limit = 0;
  
  var $SELECT_FIELDS = array();
  var $IDS = array();
  var $ITEMS = array();
  var $CHILDREN = array(); //иногда грузим как дерево
  
  var $id_field = "";
  var $result_mode = 0;
  
  var $arrows;
  
  function DBDataView( &$rh, $table_name, $SELECT_FIELDS=array(), $where="", $order_by="", $limit="" ){
    //bind rh
    $this->rh =& $rh;
    //form item
    $this->table_name = $table_name;
    $this->where = $where;
    $this->order_by = $order_by;
    $this->limit = $limit;
    $this->SELECT_FIELDS = $SELECT_FIELDS;
  }
  
  function Load($where=""){
    //aliaces
    $db =& $this->rh->db;

    //function
    $this->ITEMS = array();
    //construct sql
    $sql = "SELECT ".implode(",",$this->SELECT_FIELDS)." FROM ".$this->table_name;
    $_where .= $this->where.(($where && $this->where)? ' AND ' : '' ).$where;
    $sql .= ($_where)? " WHERE ".$_where : "";
    $sql .= ($this->order_by)? " ORDER BY ".$this->order_by : "";
    //arrows
    if($this->arrows){
      $this->arrows->Setup( $this->table_name, $_where );
      $this->arrows->Restore();
      $ARR = $this->arrows->Limit();
    }
    //load data
//    print_r($this->SELECT_FIELDS);

    if($ARR) $rs = $db->SelectLimit( $sql, $ARR[1], $ARR[0] );
    else if($this->limit) $rs = $db->SelectLimit( $sql, $this->limit );
    else $rs = $db->execute( $sql );

    if($rs && $db->numRows > 0)
    {
      if( $this->result_mode === 0)
      {
        //array mode
        $result = $db->GetArray();
        if ($result)
            $this->ITEMS = $result;
      }
      else
      {
        $id_field = $this->SELECT_FIELDS[0];
        if( $this->result_mode === 1 )
        {
          //by id mode
          $this->IDS = array();

          while($row = $db->getRow())
          {
            $this->IDS[] = $row[$id_field];
            $this->ITEMS[ $row[$id_field] ] = $row;
            //$rs->MoveNext();
          }
        }
        else
        {
          //tree mode
          $this->CHILDREN = array();
          while($row = $db->getRow())
          {
            $this->ITEMS[ $row['id'] ] = $row;
            $this->CHILDREN[ (integer)$row['_parent'] ][] = $row['id'];
            //$rs->MoveNext();
          }
        }
      }
      
    }
    //usefull aliace
    if( count($this->ITEMS)==1 )
      $this->item = $this->ITEMS[0];
    return count($this->ITEMS)>0;
  }
  
  function FindById($id){
    if( $this->result_mode === 1 ) return $this->ITEMS[$id];
    $_id_field = $this->SELECT_FIELDS[0];
    $n = count($this->ITEMS);
    for($i=0;$i<$n;$i++)
      if( $id==$this->ITEMS[$i][$_id_field] )
        return $this->ITEMS[$i];
    return false;
  }
  
}

?>