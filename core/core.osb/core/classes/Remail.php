<?
  /*
  Remail - ����������� ����� ��� �������� �������� �� ���� HTCron.
  ----------------------------------------------------------------
  
  * Remail( &$rh ) -- �����������
      $rh -- ������ �� ������ ������ RequestHandler
  
  * Send( $config ) -- ��������� ������ ��������� �������������
      $config -- ������-������
  
  * LoadCommand( $command, $FIELDS, $without_command = false ) -- �������� ������ �� ����-�������
      $command -- ���������� �������
      $FIELDS -- ������ ����� ��� ��������
      $without_command -- ���� false, �� �������, ���� �� ����� ������� � �������
  
  * DeleteCommand() -- ������� ����� ����������� ������� �� �������
  
  * LoadUsers( $users_where, $user_fields ) -- ������ ������ ������������� �� ��������� �������
      $users_where -- sql-������� �� ����� ������� �� ������� ��������
      $user_fields -- ������ ����� ��� ��������
  
  * MarkUserSent( $user, $send_field, $time ) -- 
        �������� ����� �������� ��� ������������ � ��������� ����
      $user -- ��� � ������� �� ������� ��������
      $send_field -- � ����� ���� ������ �������
      $time -- unix timestamp, ������� ������� � ��������� ����
  
  ================================================================ zharik@in.jetstyle.ru
  
  */
  
class Remail{
  
  var $rh;
  var $cron_table_name; //��� ����-�������
  var $subscribe_table_name; //��� ������� � �������� � ��������
  var $command = array(); //��� � ������� �������
  var $mail; //������ ������ HtmlMimeMail2
  
  //�������� ������ ����� �� ���������
  var $config = array(
//    "from" => "",
//    "template" => "",
//    "subject" => "",
    "user_fields" => array("id","email","fio","password"),
//    "user_time_field" => "",
    "subscr_time_field" => "timestamp",
//    "command" => "",
    "command_fields" => array("id","timestamp")
  );
  
  function Remail( &$rh ){
    $this->rh =& $rh;
    $this->cron_table_name = $rh->project_name."_htcron";
    $this->subscribe_table_name = $rh->project_name."_subscribe";
    $this->mail =& $rh->UseModule("Mail");
  }
  
  function Send( $config ){
    
    //������� ������
    $config = array_merge( $this->config, $config );
    extract($config);
    
    $rh = &$this->rh;
    $tpl =& $rh->tpl;
    $db =& $rh->db;
    
    //�������� ������������ �����
    $FIELDS = array("subject","template","user_time_field","command");
    foreach($FIELDS as $f)
      if( $$f=="")
        $rh->debug->Error( "Remail: \$".$f." ����(�) " );
    
    //������ �������
    $command = $this->LoadCommand($command,$command_fields);
    
    if( $from=="" )
      $from = $rh->host_name." <".$rh->admin_email.">";
    
    $tpl->Assign('URL',$rh->url);
    $tpl->Assign('HOST_NAME',$rh->host_name);
    
    //������ �������������, ������� ��� �� �������� ������ �� ���������� ��������
    $USERS = $this->LoadUsers( $user_time_field."<'".$command[$subscr_time_field]."'", $user_fields );
    
    //����� �� ������ � ����� ������ 8))
    $text = '';
    $time = time();
    foreach($USERS as $user){
      //������������ ������ ������
      $tpl->AssignRef("user",$user);
    	$html = $tpl->parse($template);
//      die($html);
      //�������� ������
//      echo $user["email"]."<br>";
      $this->mail->Send(
        $rh->email_address_mode=="compact" ? '<'.$user['email'].'>' : $user['fio'].' <'.$user['email'].'>',
        $subject,
        $html,
        $from
      );
      //��������, ��� ����� ������ �� ��������
      $this->MarkUserSent($user,$user_time_field,$time);
//      die();
    }
    
    //���� ����� �� ����, �� ��� ������, ��� �� ��������� ��, ��� �����
    //��������� ������
    $this->DeleteCommand();
  }
  
  function LoadCommand( $command, $FIELDS, $without_command = false ){
    $rs = $this->rh->db->execute("SELECT ".implode(",",$FIELDS)." FROM ".$this->cron_table_name." WHERE command='$command'");
    $this->command = $rs->fields;
    if( !$this->command["id"] && !$without_command )
      $this->rh->End("Remail: ������� �� �������, <b>$command</b>");
    return $this->command;
  }
  
  function DeleteCommand(){
    if( $this->command["id"] )
      $this->rh->db->execute("DELETE FROM ".$this->cron_table_name." WHERE id='".$this->command["id"]."'");
  }
  
  function LoadUsers( $users_where, $user_fields ){
    $rs = $this->rh->db->execute("SELECT ".implode(",",$user_fields)." FROM ".$this->subscribe_table_name." WHERE _state=0 AND ".$users_where);
    return $rs->GetArray();
  }
  
  function MarkUserSent( $user, $send_field, $time ){
    $this->rh->db->execute("UPDATE ".$this->subscribe_table_name." SET $send_field='$time' WHERE id='".$user['id']."'");
  }
}
?>