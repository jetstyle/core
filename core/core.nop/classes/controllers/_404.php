<?php
/**
 * _404 Page
 *
 * @author lucky@npj
 */
$this->useClass('controllers/BasicPage');
class _404 extends BasicPage
{
	var $title = '404. Документ не найден';
	
	function handle() 
	{
		$this->rh->useLib('http');
		Http::status(404);
		$this->rh->site_map_path = '404';
		parent::handle();
	}

	function rend()
	{
		$this->rh->tpl->set('page', $this->rh->url);
		parent::rend();
	}
	
}	
?>
