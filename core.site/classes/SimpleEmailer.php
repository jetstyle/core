<?php

/**
 * SimpleEmailer - quick email sending
 *
 * @author echo.bjornd@gmail.com
 */

class SimpleEmailer 
{
	private $encodings = array(
		"html_encoding" => "quoted-printable",
		"text_encoding" => "quoted-printable",
		"text_wrap" => "60",
		"html_charset" => "Windows-1251",
		"text_charset" => "Windows-1251",
		"head_charset" => "Windows-1251",
	);

	/**
     * sendEmail
     *
     * @param string $to         recepient of the email
     * @param string $from       'from' header of the email
     * @param string $subject    subject of the email
     * @param string $text       text of the email
     */
	public function sendEmail($to, $from, $subject, $text) {
		Finder::useLib("HtmlMimeMail2");
		$mail  = new HtmlMimeMail2();
		$mail->setFrom($from);
		$mail->setHeader('X-Mailer', "HtmlMimeMail2");
		$mail->setHtml($text, strip_tags($text));
		$mail->setSubject($subject);
		$mail->buildMessage($this->encodings,'mail');
                if (is_array($to))
                {
                    $recipients = array();
                    foreach ($to as $t)
                    {
                        $em = trim($t);
                        $recipients[] = "<".$em.">";
                    }
                }
                else
                    $recipients = array('<'.$to.'>');
                return $mail->send( $recipients, 'mail');
	}

    public function setEncodings($encodings)
    {
        $this->encodings = $encodings;
    }
}

?>
