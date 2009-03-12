<?php
Finder::useClass("controllers/Controller");
class RegisterController extends Controller
{	
	protected $params_map = array(
		array('thank', array('thank'=>'thank')),
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
			$config['id'] = $prp->get('id');
			$config['buttons'] = array('update');
			$config['fields']['password']['password_optional'] = true;
		}
		
		$form =& new EasyForm('register', $config);
		Locator::get('tpl')->set('Form', $form->handle());
		
		$this->siteMap = "register";
	}
	
	protected function handle_thank($config)
	{
		Locator::get('tpl')->set('thank', true);
		$this->siteMap = "register";
	}
}
?>