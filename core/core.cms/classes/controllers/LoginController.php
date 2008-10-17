<?php
Finder::useClass("controllers/Controller");
class LoginController extends Controller
{
	protected $plugins = array();

	protected $params_map = array(
		array('default', array(NULL)),
	);

	function handle_default($config)
	{
		$prp = &Locator::get('principal');
		if ($prp->isAuth())
		{
			if (RequestInfo::get('logout'))
			{
				$redirectTo = RequestInfo::get('retpath') ?
							  RequestInfo::get('retpath') :
							  RequestInfo::$baseUrl.'login';
				$prp->logout(urldecode($redirectTo));
			}
			else
			{
				if (RequestInfo::get('retpath'))
				{
					//die(RequestInfo::get('retpath'));
	            	Controller::redirect(urldecode(RequestInfo::get('retpath')));
				}
				else
				{
					Controller::redirect(RequestInfo::$baseUrl.'start');
				}
			}
		}
		else
		{
			$this->siteMap = 'login';
		}
	}
}
?>