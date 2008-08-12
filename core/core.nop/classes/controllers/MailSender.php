<?php

Finder::useClass("controllers/Controller");
/**
 * Отправляет почту из outbox'а
 */
class MailSender extends Controller
{

	function initialize(&$ctx, $config=NULL)
	{
		parent::initialize($ctx, $config);

		if (!isset($this->outbox))
		{
			Finder::useClass('models/MailOutbox');
			$outbox =& new MailOutbox();
			$outbox->initialize($this->rh);
			$outbox->limit = 30;
			$this->outbox =& $outbox;
		}
	}

	function handle()
	{
		parent::handle();

		$encodings = array(
     		"html_encoding" => "quoted-printable",
     		"text_encoding" => "quoted-printable",
     		"text_wrap" => "60",
     		"html_charset" => "Windows-1251",
     		"text_charset" => "Windows-1251",
	    	"head_charset" => "Windows-1251",
    	);

		$outbox =& $this->outbox;

		$outbox->load();

		Finder::useClass('HtmlMimeMail2');
		foreach ($outbox->data as $k=>$v)
		{
			$from = $v['from'];
			$to = $v['to'];
			$subject = $v['subject'];
			$text = $v['text'];
			$html = $v['html'];

			$mail = new HtmlMimeMail2();
			$mail->setFrom($from);
			$mail->setReturnPath($from);
			$mail->setSubject($subject);
			if ($text) $mail->setText($text);
			if ($html) $mail->setHtml($html);
			$mail->buildMessage($encodings, 'mail');
			$to = is_array($to) ? $to : array($to);
			$to = array_map(array(&$this, 'quoteEmail'), $to);
			$status = $mail->send($to);

			if ($status)
			{
				if ($this->delete_after_send)
				{
					$outbox->delete($v);
				}
				else
				{
					$v['_state'] = '1';
					$outbox->save($v);
				}
			}
		}

	}

	function quoteEmail($email)
	{
		return '<'.$email.'>';
	}


}

?>
