<?
  /*
    Обёртка для HhtmlMimeMail2
  */
  
class Module_Mail extends Module {
  
  var $core; //ссылка на объект класса HtmlMimeMail2
  var $admin_mail; //куда отсылать уведолмения об сбоях
  var $encodings = array(); //кодировки для HtmlMimeMail2
  var $send_mode = "mail"; //"smtp"
  
  function InitInstance(){
    $this->rh->UseLib("HtmlMimeMail2/HtmlMimeMail2");
    $mail =& new HtmlMimeMail2();
    $mail->setHeader('X-Mailer', "HtmlMimeMail2");
    $this->encodings = array(
      "html_encoding" => "quoted-printable",
      "text_encoding" => "quoted-printable",
      "text_wrap" => "60",
      "html_charset" => "Windows-1251",
      "text_charset" => "Windows-1251",
      "head_charset" => "Windows-1251",
    );
    $this->core =& $mail;
    $this->admin_mail = $rh->admin_mail;
  }

  function Send(  $to, $subject, $body, $from=false ){
    $mail =& $this->core;

    if( $from )
      $mail->setFrom($from);

    //заголовок
    $mail->setSubject( $subject );

    //конструируем тело письма
    if( is_array($body) )
      //даны html+txt
      $mail->setHtml( $body[0], $body[1] );
    else
      //дано только html
      $mail->setHtml( $body, $this->rh->tpl->Action("html2text",$body) );

    //формируем список рассылки
    if( is_array($to) ) 
      $recipients = $to;
    else
      $recipients = array( $to );

    //отсылаем письмо
    $mail->buildMessage($this->encodings,$this->send_mode);
    if( !$mail->send( $recipients, "mail")  ){
      if($this->admin_mail)
        mail( $this->admin_mail, "Отсылка мыла на ".$rh->project_name." обломалась 8((", $to."\n\n".$subject."\n\n".$html );
//        $rh->End();
      return false;
    }

    return true;
  }

}

?>