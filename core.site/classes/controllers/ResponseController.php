<?php
/**
 *  12.05.2009
 *  lunatic@jetstyle.ru
 */

Finder::useClass("controllers/Controller");
class ResponseController extends Controller
{
	protected $params_map = array(
		array('404', array('404'=>'404')),
		array('403', array('403'=>'403')),
	);
	
	function handle_404($config)
	{
        Finder::useLib('http');
		Http::status(404);
	}
	
	function handle_403($config)
	{
        Finder::useLib('http');
		Http::status(403);
		
		$retPath .= $_SERVER['HTTPS'] ? 'https://' : 'http://';
		$retPath .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		$retPath = urlencode($retPath);
		
		Finder::useClass("forms/EasyForm");

        $formConfig = array();
        $formConfig['action'] = RequestInfo::$baseUrl.Router::linkTo('Login').'?retpath='.$retPath;
		$form = new EasyForm('login', $formConfig);
		
        Locator::get('tpl')->set('Form', $form->handle());
	}
}
?>
