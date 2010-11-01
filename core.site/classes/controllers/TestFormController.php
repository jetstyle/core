<?php
Finder::useClass("controllers/Controller");
class TestFormController extends Controller
{	
	protected $params_map = array(
		array('default', array(NULL)),
		array('default', array("thanks"=>"thanks")),
	);

	public function handle_default($config)
	{	
		Finder::useClass("forms/EasyForm");
        $form_config = array();
		$form_config['success_url'] = RequestInfo::$baseUrl.$this->url_to('default')."/thanks";
		//die($config['success_url']);
		$form = new EasyForm('test_form', $form_config);
		Locator::get('tpl')->set('Form',  $form->handle());
	}


}
?>
