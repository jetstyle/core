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
		if ($this->rh->principal->isAuth())
		{
			if (RequestInfo::get('logout'))
			{
				$redirectTo = RequestInfo::get('retpath') ?
							  RequestInfo::get('retpath') :
							  RequestInfo::$baseUrl.'login';
				$this->rh->principal->logout($redirectTo);
			} 
			else 
			{
				if (RequestInfo::get('retpath')) 
				{
	            	$this->rh->redirect(RequestInfo::get('retpath'));
				} 
				else 
				{
					$this->rh->redirect(RequestInfo::$baseUrl.'start');
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