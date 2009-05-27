<?php

Finder::useClass("controllers/Controller");
class LoginController extends Controller
{
	protected $params_map = array(
		array('default', array("ajax"=>"ajax")),
		array('default', array(NULL)),
	);

	function handle_default($params)
	{

		$prp = &Locator::get('principal');

		if ($prp->security('noguests'))
		{
			if (RequestInfo::get('logout'))
			{
				$redirectTo = RequestInfo::get('retpath') ?
							  RequestInfo::get('retpath') :
							  RequestInfo::$baseUrl.'login';
				$prp->logout();
				Controller::redirect($redirectTo);
			}
			else
			{
				$this->siteMap = 'logout';
				Locator::get('tpl')->set('username', $prp->get('login'));
			}
		}
		else
		{
			$config = array();
			if ($params["ajax"])
			{
			    Locator::get('tpl')->set('ajax', true);

			    $config['form_onsubmit'] = "onSubmit(); return false;";
			}
			Finder::useClass("forms/EasyForm");

			$config['on_after_event'] = &$this;
			$form =& new EasyForm('login', $config);
			Locator::get('tpl')->set('Form', $form->handle());

			$this->siteMap = 'login';
		}
	}

	public function onAfterEventForm($event, $form)
	{
		$data = array();

		if (is_array($form->fields))
		{
			foreach ($form->fields AS $field)
			{
				$data[$field->name] = $field->model->Model_GetDataValue();
			}
		}

		$prp = &Locator::get('principal');

		if ($prp->login($data['login'], $data['password'], $data['permanent']) === PrincipalInterface::AUTH)
		{
			$redirectTo = RequestInfo::get('retpath') ?
					  RequestInfo::get('retpath') :
					  RequestInfo::$baseUrl;

			if ($this->params[0]=="ajax")
			{
			    die("200");
			}

			Controller::redirect($redirectTo);
		}
		else
		{
			if ($this->params[0]=="ajax")
			{
			    die("Неверный логин/пароль");
			}
			$form->fields[1]->validator->_Invalidate("bad_pass", "Неверный логин и/или пароль", false);
		}
	}
}
?>
