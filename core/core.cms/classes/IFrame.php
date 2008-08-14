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
		Locator::get('tpl')->set( '__url', $this->config->url );
	}
	
	public function getHtml()
	{
		return Locator::get('tpl')->parse( $this->template );
	}
}	
?>