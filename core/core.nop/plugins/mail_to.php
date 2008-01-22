<?php

/**		
 * ссылка на емейл
 * nop
 */

if (!function_exists('pregtrim'))
{
	function pregtrim($str) 
	{
		return preg_replace("/[^\x20-\xFF]/","",@strval($str));
	}

	function checkmail($email) 
	{
		// режем левые символы и крайние пробелы
		$mail=trim(pregtrim($email));
		// если пусто - выход
		if (strlen($mail)==0) return false;
		if (!preg_match("/^[\.a-z0-9_-]{1,20}@(([a-z0-9-]+\.)+(ru|tv|com|net|org|mil|edu|gov|arpa|info|biz|inc|name|[a-z]{2})|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})$/is",$mail))
		return false;

		return true;//$mail;
	}
}
$out = array();
$email_str = $params['_']  ? $params['_'] : $params[0]; // тип

$email_parts = preg_split("/\s|,/", $email_str);

foreach ($email_parts as $email)
{
    if (checkmail($email))
    {
    	$email=trim(pregtrim($email));
    	$out[] = "<a href='mailto:".$email."'>".$email."</a>";
    }
}

if ( !empty($out) )
{
    $out_str = implode(", ", $out);
    echo $out_str;
}

?>