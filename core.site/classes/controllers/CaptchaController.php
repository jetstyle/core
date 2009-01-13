<?php
/**
 *  Show kcaptcha from libs/kcaptcha
 *
 *
 *  - in templates  use 
        <img class="nospam" src="{{/}}captcha" />
 *      <input name="keystring" value="" autocomplete="off" />
 *
 *  - in Controller use 
        @session_start();
        if(isset($_SESSION['captcha_keystring']) && $_SESSION['captcha_keystring'] ==  $_POST['keystring'])
        {
			echo "Correct";
		}
		else
		{
			echo "Wrong";
		}
 *
 *  12.09.2008
 *  nop@jetstyle.ru
 */

Finder::useClass("controllers/Controller");
class CaptchaController extends Controller
{
	function handle()
	{
        $r = Finder::useLib("kcaptcha");

        if(isset($_REQUEST[session_name()])){
	        session_start();
        }

        $captcha = new KCAPTCHA();

        if($_REQUEST[session_name()])
        {
	        $_SESSION['captcha_keystring'] = $captcha->getKeyString();
	        $_SESSION['up'] = true;
        }
        die();
	}
}
?>
