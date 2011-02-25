<?php
Finder::useClass("controllers/Controller");
class TestFormController extends Controller
{	
	protected $params_map = array(
		array('default', array(NULL)),
		array('default', array("row_id"=>"\d+")),
		array('default', array("thanks"=>"thanks")),
	);

	public function handle_default($config)
	{
		Finder::useClass("forms/Form");
        $form_config = array();
		if ($config['row_id'])
		{
			$form_config['id'] = $config['row_id'];
		}
		$form_config['success_url'] = RequestInfo::$baseUrl.$this->url_to('default')."/thanks";
		$form = new Form('test_form', $form_config);
		Locator::get('tpl')->set('Form',  $form->handle());
	}
}
?>
