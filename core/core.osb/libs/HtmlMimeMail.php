<?php
/***************************************
** Title.........: HTML Mime Mail class
** Version.......: 1.38
** Author........: Richard Heyes <richard.heyes@heyes-computing.net>
** Filename......: class.html.mime.mail.class
** Last changed..: 28 August 2001
** License.......: Free to use. If you find it useful
**                 though, feel free to buy me something
**                 from my wishlist :)
**                 http://www.amazon.co.uk/exec/obidos/wishlist/S8H2UOGMPZK6
***************************************/

class HtmlMimeMail{

	var $mime;
	var $html;
	var $body;
	var $do_html;
	var $multipart;
	var $html_text;
	var $html_images;
	var $image_types;
	var $build_params;
	var $headers;
	var $parts;
	var $charset;

/***************************************
** Constructor function. Sets the headers
** if supplied.
***************************************/

	function HtmlMimeMail($headers = ''){

		/***************************************
        ** Make sure this is defined. This should
		** be \r\n, but due to many people having
		** trouble with that, it is by default \n
		** If you leave it as is, you will be breaking
		** quite a few standards.
        ***************************************/
		if(!defined('CRLF'))
			define('CRLF', "\n", TRUE);

		/***************************************
        ** Initialise some variables.
        ***************************************/
		$this->html_images	= array();
		$this->headers		= array();
		$this->parts		= array();

		/***************************************
        ** If you want the auto load functionality
		** to find other image.file types, and the
		** extension and content type here.
        ***************************************/
		$this->image_types = array(
									'gif'	=> 'image/gif',
									'jpg'	=> 'image/jpeg',
									'jpeg'	=> 'image/jpeg',
									'jpe'	=> 'image/jpeg',
									'bmp'	=> 'image/bmp',
									'png'	=> 'image/png',
									'tif'	=> 'image/tiff',
									'tiff'	=> 'image/tiff',
									'swf'	=> 'application/x-shockwave-flash'
								  );
		$this->charset = 'iso-8859-1';

		/***************************************
        ** Set these up
        ***************************************/
		$this->build_params['html_encoding']	= 'quoted-printable';
		$this->build_params['text_encoding']	= '7bit';
		$this->build_params['text_wrap']		= 998;

		/***************************************
        ** Make sure the MIME version header is first.
        ***************************************/
		$this->headers[] = 'MIME-Version: 1.0';

		if($headers == '')
			return TRUE;

		if(is_string($headers))
			$headers = explode(CRLF, trim($headers));

		for($i=0; $i<count($headers); $i++){
			if(is_array($headers[$i]))
				for($j=0; $j<count($headers[$i]); $j++)
					if($headers[$i][$j] != '')
						$this->headers[] = $headers[$i][$j];

			if($headers[$i] != '')
				$this->headers[] = $headers[$i];
		}
	}

/***************************************
** Accessor function to set the body text.
** Body text is used if it's not an html
** mail being sent.
***************************************/

	function set_body($text = ''){
		if(is_string($text)){
			$this->body = $text;
			return TRUE;
		}
		return FALSE;
	}

/***************************************
** Accessor function to return the mime
** class variable. Purely for debug.
***************************************/

	function get_mime(){
		if(!isset($this->mime))
			$this->mime = '';
		return $this->mime;
	}

/***************************************
** Function to set a header. Shouldn't
** really be necessary as you could use
** the constructor and send functions,
** it's here nonetheless. Takes any number
** of arguments, which can be either
** strings or arrays full of strings.
** this function is php4 only and will
** return false otherwise. Will return
** true upon finishing.
***************************************/

	function add_header(){
		if((int)phpversion() < 4)
			return FALSE;

		$args = func_get_args();
		for($i=0; $i<count($args); $i++){
			if(is_array($args[$i]))
				for($j=0; $j<count($args[$i]); $j++)
					if($args[$i][$j] != '')
						$this->headers[] = $args[$i][$j];

			if($args[$i] != '')
				$this->headers[] = $args[$i];
		}
		return TRUE;
	}

/***************************************
** Accessor function to set the content charset.
***************************************/

	function set_charset($charset = 'iso-8859-1'){

		if(is_string($charset)){
			$this->charset = $charset;
			return TRUE;
	    }
	    return FALSE;
	}

/***************************************
** This function will read a file in
** from a supplied filename and return
** it. This can then be given as the first
** argument of the the functions
** add_html_image() or add_attachment().
***************************************/

	function get_file($filename){

		if($fp = fopen($filename, 'rb')){
			$return = fread($fp, filesize($filename));
			fclose($fp);
			return $return;

		}else
			return FALSE;
	}

/***************************************
** Function for extracting images from
** html source. This function will look
** through the html code supplied by add_html()
** and find any file that ends in one of the
** extensions defined in $obj->image_types.
** If the file exists it will read it in and
** embed it, (not an attachment).
**
** Function contributed by Dan Allen
***************************************/

	function find_html_images($images_dir) {

		// Build the list of image extensions
		while(list($key,) = each($this->image_types))
			$extensions[] = $key;

		preg_match_all('/"([^"]+\.('.implode('|', $extensions).'))"/Ui', $this->html, $images);

		for($i=0; $i<count($images[1]); $i++){
			if(file_exists($images_dir.$images[1][$i])){
				$html_images[] = $images[1][$i];
				$this->html = str_replace($images[1][$i], basename($images[1][$i]), $this->html);
			}
		}

		if(!empty($html_images)){

			// If duplicate images are embedded, they may show up as attachments, so remove them.
			$html_images = array_unique($html_images);
			sort($html_images);
	
			for($i=0; $i<count($html_images); $i++){
				if($image = $this->get_file($images_dir.$html_images[$i])){
					$content_type = $this->image_types[substr($html_images[$i], strrpos($html_images[$i], '.') + 1)];
					$this->add_html_image($image, basename($html_images[$i]), $content_type);
				}
			}
		}
	}

/***************************************
** Adds a html part to the mail.
** Also replaces image names with
** content-id's.
***************************************/

	function add_html($html, $text, $images_dir = NULL){

		$this->do_html		= 1;
		$this->html			= $html;
		$this->html_text	= ($text == '') ? 'No text version was provided' : $text;

		if(isset($images_dir))
			$this->find_html_images($images_dir);
		
		if(is_array($this->html_images) AND count($this->html_images) > 0){
			for($i=0; $i<count($this->html_images); $i++)
				$this->html = str_replace($this->html_images[$i]['name'], 'cid:'.$this->html_images[$i]['cid'], $this->html);
		}
	}

/***************************************
** Adds an image to the list of embedded
** images.
***************************************/

	function add_html_image($file, $name = '', $c_type='application/octet-stream'){
		$this->html_images[] = array(
										'body'   => $file,
										'name'   => $name,
										'c_type' => $c_type,
										'cid'    => md5(uniqid(time()))
									);
	}


/***************************************
** Adds a file to the list of attachments.
***************************************/

	function add_attachment($file, $name = '', $c_type='application/octet-stream'){
		$this->parts[] = array(
								'body'   => $file,
								'name'   => $name,
								'c_type' => $c_type
							  );
	}

/***************************************
** Encodes text to quoted printable standard.
**
** Function contributed by Allan Hansen
***************************************/

	function quoted_printable_encode($input , $line_max = 76){
	
		$lines	= preg_split("/(?:\r\n|\r|\n)/", $input);
		$eol	= CRLF;
		$escape	= '=';
		$output	= '';
		
		while(list(, $line) = each($lines)){

			$linlen	 = strlen($line);
			$newline = '';

			for($i = 0; $i < $linlen; $i++){
				$char = substr($line, $i, 1);
				$dec  = ord($char);

				if(($dec == 32) AND ($i == ($linlen - 1)))				// convert space at eol only
					$char = '=20';

				elseif($dec == 9)
					;				// Do nothing if a tab.

				elseif(($dec == 61) OR ($dec < 32 ) OR ($dec > 126))
					$char = $escape.strtoupper(sprintf('%02s', dechex($dec)));
	
				if((strlen($newline) + strlen($char)) >= $line_max){	// CRLF is not counted
					$output  .= $newline.$escape.$eol;					// soft line break; " =\r\n" is okay
					$newline  = '';
				}
				$newline .= $char;
			} // end of for
			$output .= $newline.$eol;
		}
		return $output;
	}

/***************************************
** Function to return encoded text/html
** based upon the build params. Don't
** like this function name :(
***************************************/

	function get_encoded_data($data, $encoding){

		$return = '';

		switch($encoding){

			case '7bit':
				$return .=	'Content-Transfer-Encoding: 7bit'.CRLF.CRLF.
							chunk_split($data, $this->build_params['text_wrap']);
				break;

			case 'quoted-printable':
				$return .=	'Content-Transfer-Encoding: quoted-printable'.CRLF.CRLF.
							$this->quoted_printable_encode($data);
				break;

			case 'base64':
				$return .=	'Content-Transfer-Encoding: base64'.CRLF.CRLF.
							chunk_split(base64_encode($data));
				break;
		}

		return $return;
	}

/***************************************
** Builds html part of email.
***************************************/

	function build_html($orig_boundary){
		$sec_boundary = '=_'.md5(uniqid(time()));
		$thr_boundary = '=_'.md5(uniqid(time()));

		if(count($this->html_images) == 0){
			$this->multipart .= '--'.$orig_boundary.CRLF.
								'Content-Type: multipart/alternative;'.CRLF.chr(9).'boundary="'.$sec_boundary.'"'.CRLF.CRLF.
								'--'.$sec_boundary.CRLF.
								'Content-Type: text/plain; charset="'.$this->charset.'"'.CRLF.
								$this->get_encoded_data($this->html_text, $this->build_params['text_encoding']).CRLF.
								'--'.$sec_boundary.CRLF.
								'Content-Type: text/html; charset="'.$this->charset.'"'.CRLF.
								$this->get_encoded_data($this->html, $this->build_params['html_encoding']).CRLF.
								'--'.$sec_boundary.'--'.CRLF.CRLF;

		}else{

			$this->multipart .= '--'.$orig_boundary.CRLF.
								'Content-Type: multipart/related;'.CRLF.chr(9).'boundary="'.$sec_boundary.'"'.CRLF.CRLF.
								'--'.$sec_boundary.CRLF.
								'Content-Type: multipart/alternative;'.CRLF.chr(9).'boundary="'.$thr_boundary.'"'.CRLF.CRLF.
								'--'.$thr_boundary.CRLF.
								'Content-Type: text/plain; charset="'.$this->charset.'"'.CRLF.
								$this->get_encoded_data($this->html_text, $this->build_params['text_encoding']).CRLF.
								'--'.$thr_boundary.CRLF.
								'Content-Type: text/html; charset="'.$this->charset.'"'.CRLF.
								$this->get_encoded_data($this->html, $this->build_params['html_encoding']).CRLF.
								'--'.$thr_boundary.'--'.CRLF;

			for($i=0; $i<count($this->html_images); $i++){
				$this->multipart .= '--'.$sec_boundary.CRLF;
				$this->build_html_image($i);
			}

			$this->multipart .= '--'.$sec_boundary.'--'.CRLF;
		}
	}

/***************************************
** Builds an embedded image part of an
** html mail.
***************************************/

	function build_html_image($i){
		$this->multipart .= 'Content-Type: '.$this->html_images[$i]['c_type'];

		if($this->html_images[$i]['name'] != '')
			$this->multipart .= '; name="'.$this->html_images[$i]['name'].'"'.CRLF;
		else
			$this->multipart .= CRLF;

		$this->multipart .= 'Content-ID: <'.$this->html_images[$i]['cid'].'>'.CRLF;
		$this->multipart .= $this->get_encoded_data($this->html_images[$i]['body'], 'base64').CRLF;
	}

/***************************************
** Builds a single part of a multipart
** message.
***************************************/

	function build_part($input){
		$message_part  = '';
		$message_part .= 'Content-Type: '.$input['c_type'];
		if($input['name'] != '')
			$message_part .= ';'.CRLF.chr(9).'name="'.$input['name'].'"'.CRLF;
		else
			$message_part .= CRLF;

		// Determine content encoding.
		if($input['c_type'] == 'text/plain'){
			$message_part.= $this->get_encoded_data($input['body'], 'quoted-printable').CRLF;

		}elseif($input['c_type'] == 'message/rfc822'){
			$message_part .= 'Content-Disposition: attachment'.CRLF;
			$message_part .= $this->get_encoded_data($input['body'], '7bit').CRLF;

		}else{
			$message_part .= 'Content-Disposition: attachment; filename="'.$input['name'].'"'.CRLF;
			$message_part .= $this->get_encoded_data($input['body'], 'base64').CRLF;
		}

		return $message_part;
	}

/***************************************
** Builds the multipart message from the
** list ($this->_parts). $params is an
** array of parameters that shape the building
** of the message. Currently supported are:
**
** $params['html_encoding'] - The type of encoding to use on html. Valid options are
**                            "7bit", "quoted-printable" or "base64" (all without quotes).
**                            7bit is EXPRESSLY NOT RECOMMENDED. Default is quoted-printable
** $params['text_encoding'] - The type of encoding to use on plain text Valid options are
**                            "7bit", "quoted-printable" or "base64" (all without quotes).
**                            Default is 7bit
** $params['text_wrap']     - The character count at which to wrap 7bit encoded data. By
**                            default this is 998.
***************************************/

	function build_message($params = array()){

		if(count($params) > 0)
			while(list($key, $value) = each($params))
				$this->build_params[$key] = $value;

		$boundary = '=_'.md5(uniqid(time()));

		// Determine what needs building
		$do_html  = (isset($this->do_html) AND $this->do_html == 1) ? 1 : 0;
		$do_text  = (isset($this->body)) ? 1 : 0;
		$do_parts = (count($this->parts) > 0) ? 1 : 0;

		// Need to make this a multipart email?
		if($do_html OR $do_parts){
			$this->headers[] = 'Content-Type: multipart/mixed;'.CRLF.chr(9).'boundary="'.$boundary.'"';
			$this->multipart = "This is a MIME encoded message.".CRLF.CRLF;

			// Build html parts
			if($do_html)
				$this->build_html($boundary);

			// Build plain text part
			elseif($do_text)
				$this->multipart .= '--'.$boundary.CRLF.$this->build_part(array('body' => $this->body, 'name' => '', 'c_type' => 'text/plain'));

		// No attachments or html, plain text
		}elseif($do_text AND !$do_parts){
			$this->headers[] = 'Content-Type: text/plain;'.CRLF.chr(9).'charset="'.$this->charset.'"';
			$this->multipart = $this->body.CRLF.CRLF;
		}

		// Build all attachments
		if($do_parts)
			for($i=0; $i<count($this->parts); $i++)
				$this->multipart.= '--'.$boundary.CRLF.$this->build_part($this->parts[$i]);

		// Add closing boundary
		$this->mime = ($do_parts OR $do_html) ? $this->multipart.'--'.$boundary.'--'.CRLF : $this->multipart;
	}

/***************************************
** Sends the mail.
***************************************/

	function send($to_name, $to_addr, $from_name, $from_addr, $subject = '', $headers = ''){

//		$to_name = "";

		$to		= ($to_name != '')   ? '"'.$to_name.'" <'.$to_addr.'>' : $to_addr;
		$from	= ($from_name != '') ? '"'.$from_name.'" <'.$from_addr.'>' : $from_addr;

		if(is_string($headers))
			$headers = explode(CRLF, trim($headers));

		for($i=0; $i<count($headers); $i++){
			if(is_array($headers[$i]))
				for($j=0; $j<count($headers[$i]); $j++)
					if($headers[$i][$j] != '')
						$xtra_headers[] = $headers[$i][$j];

			if($headers[$i] != '')
				$xtra_headers[] = $headers[$i];
		}
		if(!isset($xtra_headers))
			$xtra_headers = array();

/*		echo "<p>****************\n";
		echo "$to\n";
		echo 'From: '.$from.CRLF.implode(CRLF, $this->headers).CRLF.implode(CRLF, $xtra_headers);
		echo "</p>****************\n";*/
			
		return mail($to_addr, $subject, $this->mime, 'To: '.$to.CRLF.'From: '.$from.CRLF.implode(CRLF, $this->headers).CRLF.implode(CRLF, $xtra_headers));
	}

/***************************************
** Use this method to deliver using direct
** smtp connection. Relies upon Manuel Lemos'
** smtp mail delivery class available at:
** http://phpclasses.upperdesign.com
**
** void smtp_send( string *Name* of smtp object,
**		 string From address,
**		 array  To addresses,
**		 array  Extra headers)
***************************************/

	function smtp_send(&$smtp_obj, $from_addr, $to_addr, $xtra_headers = ''){

		$headers = $this->headers;
		if(is_array($xtra_headers))
			for(reset($xtra_headers); list(,$header) = each($xtra_headers); )
				$headers[] = $header;

		// the following: sendmessage(string from address, array to addresses, array headers, string body)
		$smtp_obj->sendmessage($from_addr, $to_addr, $headers, $this->mime);
	}

/***************************************
** Use this method to return the email
** in message/rfc822 format. Useful for
** adding an email to another email as
** an attachment. there's a commented
** out example in example.php.
**
** string get_rfc822(string To name,
**		   string To email,
**		   string From name,
**		   string From email,
**		   [string Subject,
**		    string Extra headers])
***************************************/

	function get_rfc822($to_name, $to_addr, $from_name, $from_addr, $subject = '', $headers = ''){

		// Make up the date header as according to RFC822
		$date = 'Date: '.date('D, d M y H:i:s');

		$to   = ($to_name   != '') ? 'To: "'.$to_name.'" <'.$to_addr.'>' : 'To: '.$to_addr;
		$from = ($from_name != '') ? 'From: "'.$from_name.'" <'.$from_addr.'>' : 'From: '.$from_addr;

		if(is_string($subject))
			$subject = 'Subject: '.$subject;

		if(is_string($headers))
			$headers = explode(CRLF, trim($headers));

		for($i=0; $i<count($headers); $i++){
			if(is_array($headers[$i]))
				for($j=0; $j<count($headers[$i]); $j++)
					if($headers[$i][$j] != '')
						$xtra_headers[] = $headers[$i][$j];

			if($headers[$i] != '')
				$xtra_headers[] = $headers[$i];
		}

		if(!isset($xtra_headers))
			$xtra_headers = array();

		return $date.CRLF.$from.CRLF.$to.CRLF.$subject.CRLF.implode(CRLF, $this->headers).CRLF.implode(CRLF, $xtra_headers).CRLF.CRLF.$this->mime;
	}


} // End of class.
?>
