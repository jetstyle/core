<?php
Finder::useClass("controllers/Controller");
class LoginController extends Controller
{
	protected $params_map = array(
		array('default', array(NULL)),
	);

	function handle_default($config)
	{
		$prp = &Locator::get('principal');
		
		if ($_POST['_event'])
		{
			if ($prp->login($_POST['_login'], $_POST['_password'], $_POST['_permanent']) === PrincipalInterface::AUTH)
			{
			    
				$redirectTo = RequestInfo::get('retpath') ?
						  RequestInfo::get('retpath') :
						  RequestInfo::$baseUrl;

				Controller::redirect($redirectTo);
			}

		}
		else
		{
			if ($prp->security('noguests'))
			{
				if (RequestInfo::get('logout'))
				{
					$redirectTo = RequestInfo::get('retpath') ?
								  RequestInfo::get('retpath') :
								  RequestInfo::$baseUrl.'login';
					$prp->logout();
				}
				else
				{
					$redirectTo = RequestInfo::$baseUrl;
				}
				Controller::redirect($redirectTo);
			}
		}
		
			
		Finder::useClass("forms/EasyForm");
		$form =& new EasyForm('login',$config);
		
		Locator::get('tpl')->set('Form', $form->handle());

		$this->siteMap = 'login';
	}
}
?>