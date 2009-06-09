<?php
Finder::useClass("controllers/Controller");
class PasswordRestoreController extends Controller
{	
	protected $params_map = array(
		array('thank', array('thank'=>'thank')),
		array('change_thank',
			array(
				'change'=>'change',
				'thank'=>'thank',
			)
		),
		array('change',
			array(
				'change' => 'change',
				'id' => '\d+',
				'key' => '\w+',
			),
		),
		array('default', array(NULL)),
	);
	
	protected function handle_default()
	{
		Finder::useClass("forms/EasyForm");
		$config = array();
		$config['success_url'] = RequestInfo::$baseUrl.$this->path.'/thank';
		$config['on_after_event'] = array(array(&$this, 'restoreAfterEvent'));
		$form = & new EasyForm('password_restore', $config);
		Locator::get('tpl')->set('Form', $form->handle());
	
		$this->siteMap = 'password_restore';

	}
	
	protected function handle_thank($config)
	{
		$tpl = &Locator::get('tpl');
		$tpl->set('thank', true);
		$this->siteMap = "password_restore";
	}
	
	public function handle_change($config)
	{
		$db = &Locator::get('db');
		$error = false;

    	$model = &Locator::get('principal')->getStorageModel();
		$where = '{id} = '.intval($config['id']).' AND {key} = '.$db->quote($config['key']);
		$user = $model->loadOne($where)->getData();
		if ($user['id'])
		{
			Finder::useClass("forms/EasyForm");
			$config = array(
				'id' => $user['id'],
				'success_url' => RequestInfo::$baseUrl.$this->path.'/change/thank',
				'db_model' => Locator::get('principal')->getStorageModel(),
				'on_after_event' => array(array(&$this, 'changeAfterEvent')),
			);
			$form = & new EasyForm('password_change', $config);
			Locator::get('tpl')->set('Form', $form->handle());
		}
		else $error = true;
		
		Locator::get('tpl')->set('error', $error);
		
		$this->siteMap = 'password_change';
	}
	
	public function handle_change_thank($config)
	{
		Locator::get('tpl')->set('thank', true);
		$this->siteMap = "password_change";	
	}
	
	public function restoreAfterEvent($event, $form)
	{
		$login = $form->getFieldByName('login')->model->model_data;
		$model = Locator::get('principal')->getStorageModel();
		$model->loadOne('{login} = '.DBModel::quote($login));
	
		$key = $this->generateKey();
		$data['key'] = $key;		
		$model->update($data, '{id} = '.DBModel::quote($model['id']));
		$this->sendPasswordMail($model['id'], $model['email'], $key);
	}
	
	public function changeAfterEvent($event, $form)
	{
		$config = array('key'=>'');
		$model =  $form->config['db_model'];
		$model->update($config, '{id} = '.intval($this->params[1]));
		$user = $model->loadOne('{id} = '.intval($this->params[1]))->getData();
		Locator::get('principal')->login(
			$user['login'],
			$form->getFieldByName('password')->model->model_data
		);
	}
	
	private function sendPasswordMail($id, $to, $key)
	{
		$tpl = &Locator::get('tpl');
		
		$tpl->set('site_name', Config::get('project_title'));
		$changeLink = $this->url_to('change', array('change' => 'change', 'id' => $id, 'key' => $key));
		$tpl->set('change_link', RequestInfo::$baseUrl.$changeLink);
		$emailText = $tpl->parse('users/email_password_restore.html');
		
		Finder::useClass('SimpleEmailer');
		$emailer = new SimpleEmailer();
		$emailer->sendEmail(
			$to,
			Config::get('project_title').' <'.Config::get('admin_email').'>',
			'—сылка на изменение парол€',
			$emailText
		);	
	}
	
	private function generateKey()
	{
    	return md5($_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR'].time().rand());
	}
}
?>