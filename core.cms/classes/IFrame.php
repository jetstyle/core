<?php
	
class IFrame  
{	
	protected $config; //ссылка на объект класса ModuleConfig
	
	//for rendering
	protected $template = "iframe.html";

	public function __construct( &$config )
	{
		$this->config =& $config;
	}
	
	public function handle()
	{
		$tpl = &Locator::get('tpl');
		$id = mt_rand();
		$tpl->set( '_id', $id );
		$tpl->set( '_iframe_number', $id );
		$tpl->set('_class_name_1', "visible");
		$tpl->set('_class_name_2', "invisible");
		$tpl->set( '__url', $this->config->url );
	}
	
	public function getHtml()
	{
		return Locator::get('tpl')->parse( $this->template );
	}
}	
?>