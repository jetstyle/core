<?php
/**
 *  Sho
 *
 */

Finder::useClass("controllers/Controller");
class TplController extends Controller
{
	protected $plugins = array(
		array('MenuPlugin', array(
			'__aspect' => 'MainMenu',
			'store_to' => 'menu',
			'level' => 2,
			'depth' => 2,
		)),
	);

	function handle()
	{
        parent::handle();
		Locator::get('tpl')->set('tpl', 'tpl');
        $this->siteMap = rtrim(implode("/", $this->params), "/");
	}

}
?>