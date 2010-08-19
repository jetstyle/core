<?php
/**
 *  Контроллер главной страницы
 *
 */
Finder::useClass("controllers/Controller");
class HomeController extends Controller
{
    private $defaultModulePath = "Content/default";

	function handle()
	{
		if (Locator::get('principal')->security('noguests'))
		{
		    if ( Locator::get('principal')->security('cmsModules', $this->defaultModulePath) )
		    {
                $url = Router::linkTo('Do')."/".$this->defaultModulePath;
            }
            else if ( $menuItem = Locator::getBlock("Menu")->getDefaultMenuItem() ) 
            {
                $url = Router::linkTo('Do')."/".$menuItem["href"];
            }
            else
                $url = Router::linkTo('Start');
    
    	    Controller::redirect(RequestInfo::$baseUrl.$url);
		}
		else
		{
			Controller::redirect(RequestInfo::$baseUrl.Router::linkTo('Users::login'));
		}
	}
}
?>
