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
		$this->siteMap = "register";
		
		Finder::useClass("forms/EasyForm");
		$config = array();
		$config['success_url'] = RequestInfo::$baseUrl.$this->path.'/thank';
		$config['on_after_event'] = &$this;
		$form =& new EasyForm('register', $config);
		Locator::get('tpl')->set('Form', $form->handle());
	}
	
	protected function handle_thank($config)
	{
		Locator::get('tpl')->set('thank', true);
		$this->siteMap = "register";
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
		
		$data['realm'] = 'site';
		
		$prp = Locator::get('principal');
		$storageModel = $prp->getStorageModel();
		$storageModel->insert($data);
	}
}
?>