<?php
Finder::useClass("controllers/Controller");
class RegisterController extends Controller
{	
	protected $params_map = array(
		array('thank', array('thank'=>'thank')),
		array('activate',
			array(
				'activate' => 'activate',
				'id' => '\d+',
				'key' => '\w+',
			),
		),
		array('default', array(NULL)),
	);
	
	protected function handle_default()
	{
		Finder::useClass("forms/EasyForm");
		$config['success_url'] = RequestInfo::$baseUrl.$this->path.'/thank';
		$config['db_model'] = &Locator::get('principal')->getStorageModel();
		$config['fields']['realm']['model_default'] = 'site';
		
		$prp = &Locator::get('principal');
		if ($prp->security('noguests'))
		{
			$this->data['title'] = 'Редактирование профиля';
			$config['id'] = $prp->get('id');
			$config['buttons'] = array('update');
			$config['fields']['password']['password_optional'] = true;
			$config['on_before_event'] = array(array(&$this, 'profileBeforeEvent'));	
		}
		else
		{
			$config['on_after_event'] = array(array(&$this, 'registerAfterEvent'));	
		}
		
		$form =& new EasyForm('register', $config);
		Locator::get('tpl')->set('Form', $form->handle());
		
		$this->siteMap = "register";
	}
	
	protected function handle_thank($config)
	{
		$prp = &Locator::get('principal');
		$tpl = &Locator::get('tpl');
		$tpl->set('is_auth', $prp->security('noguests'));
		$tpl->set('thank', true);
		$this->siteMap = "register";
	}
	
	public function handle_activate($config)
	{
		$db = &Locator::get('db');
		$error = false;

    	$model = Locator::get('principal')->getStorageModel();
		$where = '{id} = '.intval($config['id']).' AND {key} = '.$db->quote($config['key']);
		$user = $model->loadOne($where)->getData();
		if ($user['id'])
		{
			$data = array('email_active' => 1);
			$model->update($data, '{id} = '.intval($config['id']));
			if ($db->affectedRows() != 1) $error = true;
		}
		else $error = true;

		if ($error)
		{
        	$this->siteMap = 'activate-error';
		}
		else
		{
       		$this->siteMap = 'activate-ok';
		}
	}
	
	public function registerAfterEvent($event, $form)
	{
		$key = $this->generateKey();
		$data['key'] = $key;
		$form->config['db_model']->update($data, '{id} = '.DBModel::quote($form->data_id));
		$this->sendActivationMail($form->data_id, $form->getFieldByName('email')->model->model_data, $key);
	}
	
	public function profileBeforeEvent($event, $form)
	{
		$user = $form->config['db_model']->loadOne('{id} = '.DBModel::quote($form->data_id))->getArray();
		if ($user['email'] != $form->getFieldByName('email')->model->model_data)
		{
			$key = $this->generateKey();
			$data['key'] = $key;
			$data['email_active'] = 0;
			$form->config['db_model']->update($data, '{id} = '.DBModel::quote($form->data_id));
			$this->sendActivationMail($form->data_id, $form->getFieldByName('email')->model->model_data, $key);	
		}
	}
	
	private function sendActivationMail($id, $to, $key)
	{
		$tpl = &Locator::get('tpl');
		
		$tpl->set('site_name', Config::get('project_title'));
		$actLink = $this->url_to('activate', array('activate' => 'activate', 'id' => $id, 'key' => $key));
		$tpl->set('act_link',RequestInfo::$baseUrl.$actLink);
		$emailText = $tpl->parse('users/email_confirmation.html');
		
		Finder::useClass('SimpleEmailer');
		$emailer = new SimpleEmailer();
		$emailer->sendEmail(
			$to,
			Config::get('project_title').' <'.Config::get('admin_email').'>',
			'Подтверждение регистрации',
			$emailText
		);	
	}
	
	private function generateKey()
	{
    	return md5($_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR'].time().rand());
	}
}
?>