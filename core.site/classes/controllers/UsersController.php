<?php
Finder::useClass("controllers/Controller");
class UsersController extends Controller
{

	protected $ajaxLogin = false;

	protected $params_map = array(
		array('activate',
			array(
				'activate' => 'activate',
				'key' => '[a-fA-F0-9]{32}',
			),
		),
		array('logout',
			array(
				'logout' => 'logout',
			),
		),
		array('login',
			array(
				'login' => 'login',
				'ajax' => 'ajax',
			),
			array(
				'login' => 'login',
			),
		),
		array('restore',
			array(
				'restore' => 'restore',
				'key' => '[a-fA-F0-9]{32}',
			),
			array(
				'restore' => 'restore',
				'changed' => 'changed',
			),
			array(
				'restore' => 'restore',
				'thanks' => 'thanks',
			),
			array(
				'restore' => 'restore',
			),
		),
		array('register',
			array(
				'register' => 'register',
				'thanks'=>'thanks',
			),
			array(
				'register' => 'register',
			),
		),
		array('edit',
			array(
				'edit' => 'edit',
				'thanks'=>'thanks',
			),
			array(
				'edit' => 'edit',
			),
		),
		array('default', array(NULL)),
		array('default', array()),
	);

	public function __construct()
	{
		// ok, let's cheat
		$siteMap = Locator::get('tpl')->getSiteMap();
		if (is_array($siteMap['users']) && is_array($siteMap['users']['views']))
		{
			foreach ($this->params_map AS $key => $v)
			{
				if ($v[0] == 'default')
				{
					continue;
				}

				if (!array_key_exists($v[0], $siteMap['users']['views']) && !in_array($v[0], $siteMap['users']['views']))
				{
					unset($this->params_map[$key]);
				}
			}
		}
		else
		{
			$this->params_map = array();
		}

		parent::__construct();
	}

	public function url_to($cls=NULL, $item=NULL)
	{
		$result = '';

		switch(strtolower($cls))
		{
			case 'login':
				$result = $this->path.'/login';
				break;

			case 'logout':
				$result = $this->path.'/logout';
				break;

			case 'logout_confirmed':
				$result = $this->path.'/logout?confirmed=true';
				break;

			case 'register':
				$result = $this->path.'/register';
				break;

			case 'edit':
				$result = $this->path.'/edit';
				break;

			case 'restore':
				$result = $this->path.'/restore';
				break;
		}

		if (!strlen($result))
		{
			$result = parent::url_to($cls, $item);
		}

		return $result;
	}

	// Form Events
	public function loginAfterEvent($event, $form)
	{
	    $openIdLogin = $form->getFieldByName('openid_login')->model_data;
		$login = $form->getFieldByName('login')->model_data;
		$password = $form->getFieldByName('password')->model_data;
		$isPermanent = $form->getFieldByName('permanent')->model_data;


		$prp = &Locator::get('principal');

		if ( $openIdLogin && $prp->loginOpenidStart( $openIdLogin ) )
		{
			$form->fields[0]->validator->_Invalidate("bad_openid", "OpenId not found there");
		}
		else if ($prp->login($login, $password, $isPermanent) === PrincipalInterface::AUTH)
		{
			if ($this->ajaxLogin)
			{
				die("200");
			}

			$redirectTo = RequestInfo::get('retpath') ?
					  RequestInfo::get('retpath') :
					  RequestInfo::$baseUrl;

			Controller::redirect($redirectTo);
		}
		else
		{
			if ($this->ajaxLogin)
			{
			    die("Неверный логин/пароль");
			}
			$form->getFieldByName('password')->_Invalidate("bad_pass", "Неверный логин и/или пароль", false);
		}
	}

	public function registerAfterEvent($event, $form)
	{
		$key = $this->generateKey();
		$data['key'] = $key;
		$form->config['db_model']->update($data, '{id} = '.DBModel::quote($form->data_id));
		$this->sendActivationMail($form->getFieldByName('email')->model->model_data, $key);
	}

	public function profileBeforeEvent($event, $form)
	{
		$userModel = clone $form->config['db_model'];
		$userModel->loadOne('{id} = '.DBModel::quote($form->data_id))->getArray();
		if ($userModel['email'] != $form->getFieldByName('email')->model->model_data)
		{
			$key = $this->generateKey();
			$data['key'] = $key;
			$data['email_active'] = 0;
			$userModel->update($data, '{id} = '.DBModel::quote($form->data_id));
			$this->sendActivationMail($form->getFieldByName('email')->model->model_data, $key);
		}
	}

	public function restoreAfterEvent($event, $form)
	{
        $login = $form->getFieldByName('login')->model->model_data;

        $model = clone Locator::get('principal')->getStorageModel();
        $model->loadByEmail($login);
        if (!$model->getId())
        {
            $model->loadByLogin($login);
        }

		if ($model['email'])
		{
			$this->sendPasswordMail($model);
		}
    }
	// End Form Events

	protected function handle_default()
	{
		Controller::redirect(RequestInfo::$baseUrl.$this->url_to('login'));
	}

	protected function handle_login($params)
	{
		$prp = &Locator::get('principal');
		
		// Perform HTTP Request to OpenID server to validate key
		if($_GET['openid_mode'] == 'id_res')
		{
		    $prp->loginOpenidProceed();
		}
		// User Canceled your Request
		else if ($_GET['openid_mode'] == 'cancel')
		{ 
		    Controller::redirect(RequestInfo::$baseUrl.$this->url_to('login')."?cancel");
		}
		
		if ($prp->security('noguests'))
		{
			Controller::redirect(RequestInfo::$baseUrl.$this->url_to('logout'));
		}

		$config = array();
		if ($params["ajax"])
		{
			Locator::get('tpl')->set('ajax', true);
			$config['form_onsubmit'] = "onSubmit(); return false;";
			$this->ajaxLogin = true;
		}

		Finder::useClass("forms/Form");
		$config['on_after_event'] = array(array(&$this, 'loginAfterEvent'));
		$form = new Form('login', $config);
		Locator::get('tpl')->set('Form', $form->handle());
	}

	protected function handle_logout($params)
	{
		$prp = &Locator::get('principal');

		if (!$prp->security('noguests'))
		{
			Controller::redirect(RequestInfo::$baseUrl.$this->url_to('login'));
		}

		if (RequestInfo::get('confirmed'))
		{
			$redirectTo = RequestInfo::get('retpath') ?
						  RequestInfo::get('retpath') :
						  RequestInfo::$baseUrl.$this->url_to('login');
			$prp->logout();
			Controller::redirect($redirectTo);
		}

		Locator::get('tpl')->set('username', $prp->get('login'));
	}

	protected function handle_register($params)
	{
		if ($params['thanks'])
		{
			Locator::get('tpl')->set('thanks', true);
		}
		else
		{
			$prp = &Locator::get('principal');
			if ($prp->security('noguests'))
			{
				Controller::redirect(RequestInfo::$baseUrl.$this->url_to('edit'));
			}

			$config = array();
			$model = clone Locator::get('principal')->getStorageModel();
			$config['db_model'] = $model;
			$config['success_url'] = RequestInfo::$baseUrl.$this->url_to('register').'/thanks';
			$config['on_after_event'] = array(array(&$this, 'registerAfterEvent'));

			Finder::useClass("forms/Form");
			$form = new Form('user_register', $config);
			Locator::get('tpl')->set('Form', $form->handle());
		}

		$this['title'] = 'Регистрация';
	}

	protected function handle_edit($params)
	{
		if ($params['thanks'])
		{
			Locator::get('tpl')->set('thanks', true);
		}
		else
		{
			$prp = &Locator::get('principal');
			if (!$prp->security('noguests'))
			{
				Controller::redirect(RequestInfo::$baseUrl.$this->url_to('login'));
			}

			$config = array();
			$model = clone Locator::get('principal')->getStorageModel();
			$config['db_model'] = $model;
			$config['success_url'] = RequestInfo::$baseUrl.$this->url_to('edit').'/thanks';

			$config['id'] = $prp->get('id');
			$config['on_before_event'] = array(array(&$this, 'profileBeforeEvent'));

			$form = new Form('user_edit', $config);
			Locator::get('tpl')->set('Form', $form->handle());
		}

		$this['title'] = 'Редактирование профиля';
	}

	protected function handle_activate($config)
	{
		$error = false;

		$userModel = clone Locator::get('principal')->getStorageModel();
		$userModel->loadOne('{key} = '.DBModel::quote($config['key']));
		if ($userModel['id'])
		{
			$data = array(
				'email_active' => 1,
				'key' => $this->generateKey(),
			);

			$userModel->update($data, '{id} = '.DBModel::quote($userModel['id']));
		}
		else
		{
			$error = true;
		}

		Locator::get('tpl')->set('activate_error', $error);

		$this['title'] = 'Активация профиля';
	}

	protected function handle_restore($params)
	{
		if ($params['thanks'])
		{
			Locator::get('tpl')->set('thanks', true);
		}
		elseif ($params['changed'])
		{
			Locator::get('tpl')->set('password_change', true);
			Locator::get('tpl')->set('changed', true);
		}
		elseif ($params['key'])
		{
			Locator::get('tpl')->set('password_change', true);

			$db = &Locator::get('db');
			$error = false;

			$user = clone Locator::get('principal')->getStorageModel();
			$user->loadOne('{key} = '.$db->quote($params['key']));
			if ($user->getId())
			{
				Finder::useClass("forms/Form");
				$user->removeField('key');
				$config = array(
					'id' => $user->getId(),
					'success_url' => RequestInfo::$baseUrl.$this->url_to('restore').'/changed',
					'db_model' => $user,
					'on_after_event' => array(array(&$this, 'changeAfterEvent')),
					'fields' => array(
						'key' => array(
							'extends_from' => 'system',
							'model_default' => $this->generateKey(),
						)
					)
				);
				$form = new Form('password_change', $config);
				Locator::get('tpl')->set('Form', $form->handle());
			}
			else
			{
				$error = true;
			}

			Locator::get('tpl')->set('error', $error);
			
		}
		else
		{
			Finder::useClass("forms/Form");

			$config = array();
			$config['success_url'] = RequestInfo::$baseUrl.$this->url_to('restore').'/thanks';
			$config['on_after_event'] = array(array(&$this, 'restoreAfterEvent'));
			$form =  new Form('password_restore', $config);
			Locator::get('tpl')->set('Form', $form->handle());
		}

		$this['title'] = 'Восстановление пароля';
    }


	protected function sendActivationMail($to, $key)
	{
		$tpl = &Locator::get('tpl');

		$tpl->set('site_name', Config::get('project_title'));
		$actLink = $this->url_to('activate', array('activate' => 'activate', 'key' => $key));
		$tpl->set('act_link',RequestInfo::$baseUrl.$actLink);
		$emailText = $tpl->parse('users/emails/confirmation.html');

		Finder::useClass('SimpleEmailer');
		$emailer = new SimpleEmailer();
		$emailer->sendEmail(
			$to,
			Config::get('project_title').' <'.Config::get('admin_email').'>',
			'Подтверждение регистрации',
			$emailText
		);
	}

	protected function sendPasswordMail($user)
	{
        $tpl = &Locator::get('tpl');

        $tpl->set('site_name', Config::get('project_title'));
        $changeLink = RequestInfo::$baseUrl.$this->url_to('restore').'/'.$user['key'];
        $tpl->set('change_link', $changeLink);
        $tpl->set('restore_user', $user);
        $emailText = $tpl->parse('users/emails/password_restore.html');

        Finder::useClass('SimpleEmailer');
        $emailer = new SimpleEmailer();
        $emailer->sendEmail(
            $user['email'],
            Config::get('project_title').' <'.Config::get('admin_email').'>',
            'Ссылка на изменение пароля',
            $emailText
        );
    }

	protected function generateKey()
	{
		return md5($_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR'].time().rand());
	}
}
?>
