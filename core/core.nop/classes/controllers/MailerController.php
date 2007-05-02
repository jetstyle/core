<?php

$this->UseClass("controllers/Controller");
class MailerController extends Controller
{
	var $template = NULL;
	var $from = NULL;
	var $to = NULL;

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
		$from = $this->from;
		$from = $this->quoteEmail($from);
		$to = $this->to;
		/*
		$to = is_array($this->to) ? $this->to : array($this->to);
		$to = array_map(array(&$this, 'quoteEmail'), $to);
		 */
		$text = $this->buildText();
		$html = $this->buildHtml();
		$subject = $this->buildSubject();

		if (0)
		{

			$this->rh->UseClass('HtmlMimeMail2');
			$mail = new HtmlMimeMail2();
			$mail->setFrom($from);
			$mail->setReturnPath($from);
			$mail->setSubject($subject);
			if ($text) $mail->setText($text);
			if ($html) $mail->setHtml($html);
			$mail->buildMessage($encodings, 'mail');

			//var_dump($html, $mail, $text); die();
			$err = $mail->send($to);

		}

		else

		{

			$this->rh->useClass('models/MailOutbox');
			$outbox =& new MailOutbox();
			$outbox->initialize($this->rh);
			$row = array(
				'from' => $from,
				'to' => $to,
				'subject' => $subject,
				'text' => $text,
				'html' => $html,
				);
			$status = $outbox->save($row);
		}
	}

	function quoteEmail($email)
	{
		return '<'.$email.'>';
	}

	function buildSubject()
	{
		$this->rh->tpl->set('*', $this->data);
		$out = $this->rh->tpl->Parse($this->template.':subject');
		return $out;
	}

	function buildText()
	{
		$this->rh->tpl->set('*', $this->data);
		$out = $this->rh->tpl->Parse($this->template.':text');
		return $out;
	}

	function buildHtml()
	{
		$this->rh->tpl->set('*', $this->data);
		$out = $this->rh->tpl->Parse($this->template.':html');
		return $out;
	}


}

?>
