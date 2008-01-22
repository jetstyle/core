<?
  /*
  Remail - абстрактный класс для создания рассылок на базе HTCron.
  ----------------------------------------------------------------
  
  * Remail( &$rh ) -- конструктор
      $rh -- ссылка на объект калсса RequestHandler
  
  * Send( $config ) -- рассылает письмо выбранным пользователям
      $config -- конфиг-массив
  
  * LoadCommand( $command, $FIELDS, $without_command = false ) -- загрузка записи из крон-таблицы
      $command -- собственно команда
      $FIELDS -- массив полей для загрузки
      $without_command -- если false, то умираем, если не нашли команду в таблице
  
  * DeleteCommand() -- удаляет ранее загруженную команду из таблицы
  
  * LoadUsers( $users_where, $user_fields ) -- грузит список пользователей по заданному условию
      $users_where -- sql-условие на выбор записей из таблицы подписки
      $user_fields -- массив полей для загрузки
  
  * MarkUserSent( $user, $send_field, $time ) -- 
        помечает время отправки для пользователя в указанное поле
      $user -- хэш с записью из таблицы подписки
      $send_field -- в какое поле писать пометку
      $time -- unix timestamp, который запишем в указанное поле
  
  ================================================================ zharik@in.jetstyle.ru
  
  */
  
class Remail{
  
  var $rh;
  var $cron_table_name; //имя крон-таблицы
  var $subscribe_table_name; //имя таблицы с записями о подписке
  var $command = array(); //хэш с записью команды
  var $mail; //объект класса HtmlMimeMail2
  
  //значения многих полей по умолчанию
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
    
    //сложный конфиг
    $config = array_merge( $this->config, $config );
    extract($config);
    
    $rh = &$this->rh;
    $tpl =& $rh->tpl;
    $db =& $rh->db;
    
    //проверка обязательных полей
    $FIELDS = array("subject","template","user_time_field","command");
    foreach($FIELDS as $f)
      if( $$f=="")
        $rh->debug->Error( "Remail: \$".$f." пуст(о) " );
    
    //грузим команду
    $command = $this->LoadCommand($command,$command_fields);
    
    if( $from=="" )
      $from = $rh->host_name." <".$rh->admin_email.">";
    
    $tpl->Assign('URL',$rh->url);
    $tpl->Assign('HOST_NAME',$rh->host_name);
    
    //грузим пользователей, которым ещё не отсылали письма по указанному критерию
    $USERS = $this->LoadUsers( $user_time_field."<'".$command[$subscr_time_field]."'", $user_fields );
    
    //бежим по юзерям и умело спамим 8))
    $text = '';
    $time = time();
    foreach($USERS as $user){
      //окончательно парсим письмо
      $tpl->AssignRef("user",$user);
    	$html = $tpl->parse($template);
//      die($html);
      //отсылаем письмо
//      echo $user["email"]."<br>";
      $this->mail->Send(
        $rh->email_address_mode=="compact" ? '<'.$user['email'].'>' : $user['fio'].' <'.$user['email'].'>',
        $subject,
        $html,
        $from
      );
      //помечаем, что этому юзверю всё отослали
      $this->MarkUserSent($user,$user_time_field,$time);
//      die();
    }
    
    //если дошли до сюда, то это значит, что мы разослали всё, что нужно
    //прибиваем запись
    $this->DeleteCommand();
  }
  
  function LoadCommand( $command, $FIELDS, $without_command = false ){
    $rs = $this->rh->db->execute("SELECT ".implode(",",$FIELDS)." FROM ".$this->cron_table_name." WHERE command='$command'");
    $this->command = $rs->fields;
    if( !$this->command["id"] && !$without_command )
      $this->rh->End("Remail: команда не найдена, <b>$command</b>");
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