<?
  /*
    ��������� ����� ��� ���������� ������� � htcron-�������.
    
    * HTCron( &$rh ) -- �����������
        $rh -- ������ �� ������ ������ RequestHandler
    
    * PutCommand( $command, $spec, $FIELDS ) -- ��������� ������ � htcron-�������.
          ���� ������ � ����� �������� ��� ���� - ������ �� ���������
        $command -- url, ������� ����� ������
        $spec -- ����� ������� � ����-����������
        $FIELDS -- �������������� ���� ��� ������� ������
    
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