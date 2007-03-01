<?
  /*
    ������ ��� HhtmlMimeMail2
  */
  
class Module_Mail extends Module {
  
  var $core; //������ �� ������ ������ HtmlMimeMail2
  var $admin_mail; //���� �������� ����������� �� �����
  var $encodings = array(); //��������� ��� HtmlMimeMail2
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

    //���������
    $mail->setSubject( $subject );

    //������������ ���� ������
    if( is_array($body) )
      //���� html+txt
      $mail->setHtml( $body[0], $body[1] );
    else
      //���� ������ html
      $mail->setHtml( $body, $this->rh->tpl->Action("html2text",$body) );

    //��������� ������ ��������
    if( is_array($to) ) 
      $recipients = $to;
    else
      $recipients = array( $to );

    //�������� ������
    $mail->buildMessage($this->encodings,$this->send_mode);
    if( !$mail->send( $recipients, "mail")  ){
      if($this->admin_mail)
        mail( $this->admin_mail, "������� ���� �� ".$rh->project_name." ���������� 8((", $to."\n\n".$subject."\n\n".$html );
//        $rh->End();
      return false;
    }

    return true;
  }

}

?>