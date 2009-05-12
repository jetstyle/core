<?php
/**
 *  Show template
 *
 */

Finder::useClass("controllers/Controller");
class TplController extends Controller
{
	function handle()
	{
        parent::handle();
		Locator::get('tpl')->set('tpl', 'tpl');
        $this->siteMap = rtrim(implode("/", $this->params), "/");
	}
}
?>