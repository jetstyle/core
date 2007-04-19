<?
  /*
    Небольшой класс для добавление записей в htcron-таблицу.
    
    * HTCron( &$rh ) -- конструктор
        $rh -- ссылка на объект класса RequestHandler
    
    * PutCommand( $command, $spec, $FIELDS ) -- вставляет запись в htcron-таблицу.
          Если запись с такой командой уже есть - ничего не вставляет
        $command -- url, который будет вызван
        $spec -- время запуска в крон-синтаксисе
        $FIELDS -- дополнительные поля для вставки записи
    
    ======================================================== zharik@in.jetstyle.ru
  */
  
 class HTCron {
  
  var $rh;
  var $table_name;
  
  function HTCron( &$rh ){
    $this->rh =& $rh;
    $this->table_name = $rh->project_name."_htcron";
  }
  
  function PutCommand( $command, $spec="* * * * * *", $FIELDS = array() ){
    $db =& $this->rh->db;
    $rs = $db->execute("SELECT id FROM ".$this->table_name." WHERE command='$command'");
    if($rs->EOF){
      foreach($FIELDS as $f=>$v){
        $sql1 .= ",".$f;
        $sql2 .= ",".$db->quote($v);
      }
      $sql = "INSERT INTO ".$this->table_name."(spec,command".$sql1.") VALUES('* * * * * *','$command'".$sql2.")";
      $db->execute($sql);
      return true;
    }else
      return false;
  }
 }
?>